<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Events\GenerationCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class HandleGenerationCompleted implements ShouldQueue
{
    public function __construct(private readonly CreditService $creditService) {}

    public function handle(GenerationCompleted $event): void
    {
        $generation = $event->generation;
        $user       = $generation->user;

        if ($generation->actual_user_cost === null) {
            Log::warning('GenerationCompleted: no actual_user_cost to deduct.', [
                'generation_id' => $generation->id,
            ]);
            return;
        }

        $cost = Money::fromDecimalString($generation->actual_user_cost);

        if ($cost->isZero()) {
            return;
        }

        $this->creditService->deductForGeneration($user, $cost, $generation);

        Log::info('Credits deducted for generation.', [
            'generation_id' => $generation->id,
            'deducted'      => (string) $cost,
        ]);
    }
}
