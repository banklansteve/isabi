<?php

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseTokenPackRequest;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Support\ActivityLogger;
use App\Support\Tokens\ReviewLinkGate;
use App\Support\Tokens\TokenCatalog;
use App\Support\Tokens\TokenWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TokenController extends Controller
{
    public function index(Request $request, ReviewLinkGate $gate): Response
    {
        $user = $request->user();
        $status = $gate->status($user);

        $transactions = TokenTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(fn (TokenTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => $tx->amount,
                'balance_after' => $tx->balance_after,
                'action' => $tx->action,
                'description' => $tx->description,
                'created_label' => $tx->created_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y · g:ia'),
            ]);

        $purchases = TokenPurchase::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (TokenPurchase $p) => [
                'id' => $p->id,
                'pack_name' => $p->pack_name,
                'tokens' => $p->tokens,
                'price' => $p->price,
                'status' => $p->status,
                'reference' => $p->reference,
                'created_label' => $p->created_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
            ]);

        return Inertia::render('Tokens/Index', [
            'status' => $status,
            'transactions' => $transactions,
            'purchases' => $purchases,
            'currencySymbol' => (string) config('pricing.currency_symbol', '₦'),
            'actions' => [
                'review_link' => (int) config('pricing.credits.actions.review_link', 1),
                'qr_download' => (int) config('pricing.credits.actions.qr_download', 1),
                'vanity_slug' => (int) config('pricing.credits.actions.vanity_slug', 5),
            ],
        ]);
    }

    public function buy(Request $request, ReviewLinkGate $gate): Response
    {
        $user = $request->user();

        return Inertia::render('Tokens/Buy', [
            'status' => $gate->status($user),
            'packs' => TokenCatalog::packs(),
            'currencySymbol' => (string) config('pricing.currency_symbol', '₦'),
            'instantFulfill' => (bool) config('pricing.payments.instant_fulfill', true),
        ]);
    }

    public function purchase(
        PurchaseTokenPackRequest $request,
        TokenWallet $wallet,
    ): RedirectResponse {
        $user = $request->user();
        $pack = TokenCatalog::pack($request->validated('pack'));

        if (! $pack) {
            return back()->with('toast', [
                'type' => 'error',
                'message' => 'That pack is no longer available.',
                'duration' => 4500,
            ]);
        }

        $purchase = DB::transaction(function () use ($user, $pack, $wallet) {
            $purchase = TokenPurchase::query()->create([
                'user_id' => $user->id,
                'pack_key' => $pack['key'],
                'pack_name' => $pack['name'],
                'tokens' => $pack['tokens'],
                'price' => $pack['price'],
                'currency' => (string) config('pricing.currency', 'NGN'),
                'status' => TokenPurchase::STATUS_PENDING,
                'reference' => 'TKN-'.strtoupper(Str::random(12)),
                'processor' => (bool) config('pricing.payments.instant_fulfill', true)
                    ? 'instant'
                    : 'pending_gateway',
                'meta' => [
                    'per_token' => $pack['per_token'],
                ],
            ]);

            if ((bool) config('pricing.payments.instant_fulfill', true)) {
                $wallet->fulfilPurchase($purchase);
            }

            return $purchase->fresh();
        });

        ActivityLogger::log(
            action: 'tokens.purchased',
            summary: "{$user->name} purchased {$purchase->tokens} tokens ({$purchase->pack_name}).",
            user: $user,
            properties: [
                'reference' => $purchase->reference,
                'tokens' => $purchase->tokens,
                'price' => $purchase->price,
            ],
        );

        $message = $purchase->status === TokenPurchase::STATUS_COMPLETED
            ? "{$purchase->tokens} tokens added to your wallet."
            : 'Purchase started — tokens will appear once payment is confirmed.';

        return redirect()
            ->route('tokens.index')
            ->with('toast', [
                'type' => 'success',
                'title' => $purchase->status === TokenPurchase::STATUS_COMPLETED
                    ? 'Tokens added'
                    : 'Purchase started',
                'message' => $message,
                'duration' => 5500,
            ]);
    }
}
