<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustCreditsRequest;
use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Support\Admin\AdminAudit;
use App\Support\Tokens\TokenWallet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class CreditAdminController extends Controller
{
    public function index(): Response
    {
        $purchases = TokenPurchase::query()
            ->with(['user:id,name,email,business_name'])
            ->latest('id')
            ->limit(1500)
            ->get()
            ->map(fn (TokenPurchase $p) => [
                'id' => $p->id,
                'kind' => 'purchase',
                'pack_name' => $p->pack_name,
                'tokens' => $p->tokens,
                'price' => $p->price,
                'status' => $p->status,
                'reference' => $p->reference,
                'processor' => $p->processor,
                'when' => ($p->paid_at ?? $p->created_at)?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'created_iso' => ($p->paid_at ?? $p->created_at)?->toIso8601String(),
                'user' => $p->user ? [
                    'id' => $p->user->id,
                    'name' => $p->user->displayBusinessName(),
                    'email' => $p->user->email,
                ] : null,
            ])
            ->values();

        $transactions = TokenTransaction::query()
            ->with(['user:id,name,email,business_name'])
            ->latest('id')
            ->limit(1500)
            ->get()
            ->map(fn (TokenTransaction $tx) => [
                'id' => $tx->id,
                'kind' => 'transaction',
                'type' => $tx->type,
                'amount' => $tx->amount,
                'balance_after' => $tx->balance_after,
                'action' => $tx->action,
                'description' => $tx->description,
                'when' => $tx->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:ia'),
                'created_iso' => $tx->created_at?->toIso8601String(),
                'user' => $tx->user ? [
                    'id' => $tx->user->id,
                    'name' => $tx->user->displayBusinessName(),
                    'email' => $tx->user->email,
                ] : null,
            ])
            ->values();

        return Inertia::render('Admin/Credits/Index', [
            'purchases' => $purchases,
            'transactions' => $transactions,
        ]);
    }

    public function adjust(AdjustCreditsRequest $request, TokenWallet $wallet): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->where('email', $data['email'])->firstOrFail();
        abort_unless($user->isRegularUser(), 422);

        $before = (int) $user->token_balance;

        try {
            if ($data['direction'] === 'credit') {
                $wallet->credit($user, (int) $data['amount'], TokenTransaction::ACTION_ADMIN_ADJUST, $data['reason']);
            } else {
                $wallet->debit($user, (int) $data['amount'], TokenTransaction::ACTION_REFUND, $data['reason']);
            }
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'amount' => $exception->getMessage(),
            ]);
        }

        AdminAudit::record(
            'credits.adjusted',
            "{$request->user()->name} {$data['direction']}ed {$data['amount']} tokens for {$user->email}.",
            $user,
            ['token_balance' => $before],
            ['token_balance' => (int) $user->fresh()->token_balance, 'reason' => $data['reason']],
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Balance updated',
            'message' => "{$user->email} is now at {$user->fresh()->token_balance} tokens.",
        ]);
    }
}
