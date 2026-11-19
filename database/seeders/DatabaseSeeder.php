<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Billing\Models\Credit;
use App\Domain\Billing\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user — full access, pre-loaded with $50
        $admin = User::firstOrCreate(
            ['email' => 'admin@pixelforge.test'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );
        $this->giveCredits($admin, '50.000000', 'Seed — initial admin credits');

        // Regular demo user — pre-loaded with $10
        $demo = User::firstOrCreate(
            ['email' => 'demo@pixelforge.test'],
            [
                'name'     => 'Demo User',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ]
        );
        $this->giveCredits($demo, '10.000000', 'Seed — welcome credits');
    }

    private function giveCredits(User $user, string $amount, string $description): void
    {
        $credit = Credit::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => '0.000000']
        );

        // Only top-up if balance is still zero (idempotent re-seed)
        if (bccomp($credit->balance, '0.000000', 6) === 0) {
            $newBalance = bcadd('0.000000', $amount, 6);
            $credit->update(['balance' => $newBalance]);

            CreditTransaction::create([
                'user_id'       => $user->id,
                'generation_id' => null,
                'type'          => 'credit',
                'amount'        => $amount,
                'description'   => $description,
                'balance_after' => $newBalance,
            ]);
        }
    }
}
