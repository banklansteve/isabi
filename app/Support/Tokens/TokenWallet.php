<?php

namespace App\Support\Tokens;

use App\Models\TokenPurchase;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class TokenWallet
{
    public function credit(
        User $user,
        int $amount,
        string $action,
        string $description,
        ?Model $related = null,
        array $meta = [],
    ): TokenTransaction {
        if ($amount < 1) {
            throw new InvalidArgumentException('Credit amount must be at least 1.');
        }

        return $this->mutate(
            user: $user,
            type: TokenTransaction::TYPE_CREDIT,
            amount: $amount,
            action: $action,
            description: $description,
            related: $related,
            meta: $meta,
        );
    }

    public function debit(
        User $user,
        int $amount,
        string $action,
        string $description,
        ?Model $related = null,
        array $meta = [],
    ): TokenTransaction {
        if ($amount < 1) {
            throw new InvalidArgumentException('Debit amount must be at least 1.');
        }

        return $this->mutate(
            user: $user,
            type: TokenTransaction::TYPE_DEBIT,
            amount: $amount,
            action: $action,
            description: $description,
            related: $related,
            meta: $meta,
        );
    }

    /**
     * Fulfil a completed purchase: credit tokens once.
     */
    public function fulfilPurchase(TokenPurchase $purchase): TokenTransaction
    {
        return DB::transaction(function () use ($purchase) {
            $purchase = TokenPurchase::query()
                ->whereKey($purchase->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($purchase->status === TokenPurchase::STATUS_COMPLETED && $purchase->paid_at) {
                $existing = TokenTransaction::query()
                    ->where('user_id', $purchase->user_id)
                    ->where('action', TokenTransaction::ACTION_PURCHASE)
                    ->where('related_type', $purchase->getMorphClass())
                    ->where('related_id', $purchase->id)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $user = User::query()->whereKey($purchase->user_id)->lockForUpdate()->firstOrFail();

            $purchase->forceFill([
                'status' => TokenPurchase::STATUS_COMPLETED,
                'paid_at' => $purchase->paid_at ?? now(),
            ])->save();

            return $this->credit(
                user: $user,
                amount: (int) $purchase->tokens,
                action: TokenTransaction::ACTION_PURCHASE,
                description: "Purchased {$purchase->pack_name} pack ({$purchase->tokens} tokens)",
                related: $purchase,
                meta: [
                    'pack_key' => $purchase->pack_key,
                    'reference' => $purchase->reference,
                    'price' => $purchase->price,
                ],
            );
        });
    }

    private function mutate(
        User $user,
        string $type,
        int $amount,
        string $action,
        string $description,
        ?Model $related,
        array $meta,
    ): TokenTransaction {
        return DB::transaction(function () use ($user, $type, $amount, $action, $description, $related, $meta) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $balance = (int) $locked->token_balance;

            if ($type === TokenTransaction::TYPE_DEBIT) {
                if ($balance < $amount) {
                    throw new RuntimeException('Insufficient token balance.');
                }
                $balance -= $amount;
            } else {
                $balance += $amount;
            }

            $locked->forceFill(['token_balance' => $balance])->save();

            return TokenTransaction::query()->create([
                'user_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $balance,
                'action' => $action,
                'description' => $description,
                'meta' => $meta ?: null,
                'related_type' => $related?->getMorphClass(),
                'related_id' => $related?->getKey(),
            ]);
        });
    }
}
