<?php

declare(strict_types=1);

namespace App\Domain\Billing\Services;

use App\Domain\Billing\Models\Credit;
use App\Domain\Billing\Models\CreditTransaction;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Models\Generation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class CreditService
{
    public function getBalance(User $user): Money
    {
        $credit = Credit::where('user_id', $user->id)->first();

        if ($credit === null) {
            return Money::zero();
        }

        return Money::fromDecimalString($credit->balance === '0' ? '0.000000' : $credit->balance);
    }

    public function canAfford(User $user, Money $amount): bool
    {
        return $this->getBalance($user)->isGreaterThanOrEqualTo($amount);
    }

    /**
     * Atomically checks balance and locks the credit row.
     * Returns false if insufficient funds. Must be called inside a transaction.
     */
    public function checkAndLock(User $user, Money $required): bool
    {
        $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();

        if ($credit === null) {
            return false;
        }

        $balance = Money::fromDecimalString(
            $credit->balance !== '0' ? $credit->balance : '0.000000'
        );

        return $balance->isGreaterThanOrEqualTo($required);
    }

    public function topUp(User $user, Money $amount, string $description = 'Credit purchase'): void
    {
        DB::transaction(function () use ($user, $amount, $description): void {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => '0.000000'],
            );

            $current    = Money::fromDecimalString($credit->balance !== '0' ? $credit->balance : '0.000000');
            $newBalance = $current->add($amount);

            $credit->balance = $newBalance->toDecimalString(6);
            $credit->save();

            CreditTransaction::create([
                'user_id'       => $user->id,
                'generation_id' => null,
                'type'          => 'credit',
                'amount'        => $amount->toDecimalString(6),
                'description'   => $description,
                'balance_after' => $newBalance->toDecimalString(6),
            ]);
        });
    }

    public function deductForGeneration(User $user, Money $amount, Generation $generation): void
    {
        DB::transaction(function () use ($user, $amount, $generation): void {
            $credit = Credit::where('user_id', $user->id)->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => '0.000000'],
            );

            $current = Money::fromDecimalString($credit->balance !== '0' ? $credit->balance : '0.000000');

            // Deduct — clamp to zero if somehow below (failed gen shouldn't reach here, but guard anyway)
            $newBalance = $current->isGreaterThan($amount)
                ? $current->subtract($amount)
                : Money::zero();

            $credit->balance = $newBalance->toDecimalString(6);
            $credit->save();

            CreditTransaction::create([
                'user_id'       => $user->id,
                'generation_id' => $generation->id,
                'type'          => 'debit',
                'amount'        => $amount->toDecimalString(6),
                'description'   => "Generation #{$generation->id}",
                'balance_after' => $newBalance->toDecimalString(6),
            ]);
        });
    }

    public function ensureCreditRecord(User $user): void
    {
        Credit::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => '0.000000'],
        );
    }
}
