<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function getOrCreate(User $user): Wallet
    {
        return Wallet::firstOrCreate(['user_id' => $user->id], ['balance' => 0]);
    }

    /**
     * افزایش موجودی کیف پول (مثلاً Refund یا پاداش) — بخش ۱۸.
     * با lockForUpdate در برابر Race Condition دو تراکنش هم‌زمان محافظت می‌شود
     * (همان الگوی InventoryService).
     */
    public function credit(User $user, int $amount, ?string $reference = null, ?string $note = null): Wallet
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ باید مثبت باشد.');
        }

        return DB::transaction(function () use ($user, $amount, $reference, $note) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first()
                ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

            $wallet->increment('balance', $amount);

            $wallet->transactions()->create([
                'type' => 'credit',
                'amount' => $amount,
                'reference' => $reference,
                'note' => $note,
            ]);

            return $wallet->fresh();
        });
    }

    /**
     * کسر از موجودی کیف پول. اگر موجودی کافی نباشد، رد می‌شود (بخش ۳۵:
     * جلوگیری از موجودی منفی، مشابه اصل جلوگیری از Overselling).
     */
    public function debit(User $user, int $amount, ?string $reference = null, ?string $note = null): Wallet
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('مبلغ باید مثبت باشد.');
        }

        return DB::transaction(function () use ($user, $amount, $reference, $note) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet || $wallet->balance < $amount) {
                throw new \DomainException('موجودی کیف پول کافی نیست.');
            }

            $wallet->decrement('balance', $amount);

            $wallet->transactions()->create([
                'type' => 'debit',
                'amount' => $amount,
                'reference' => $reference,
                'note' => $note,
            ]);

            return $wallet->fresh();
        });
    }
}
