<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Billing\Services\MoneyService;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Events\GenerationCompleted;
use App\Domain\Generation\Events\GenerationFailed;
use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class PollGenerationStatus implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly Generation $generation) {}

    public function handle(
        ImageGenerationProviderInterface $provider,
        MoneyService $moneyService,
    ): void {
        $generation = $this->generation->fresh();

        if ($generation === null || $generation->isInTerminalState()) {
            return;
        }

        if ($generation->provider_generation_id === null) {
            return;
        }

        $status = $provider->getGeneration($generation->provider_generation_id);

        if ($status->isProcessing()) {
            if ($generation->status === GenerationStatus::Queued) {
                $generation->transitionTo(GenerationStatus::Processing);
                $generation->save();
            }
            return;
        }

        if ($status->isCompleted()) {
            $generation->transitionTo(GenerationStatus::Succeeded);
            $generation->image_url    = $status->imageUrl;
            $generation->completed_at = now();

            if ($status->hasUsageData()) {
                $providerCost = $moneyService->calculateActualCostFromUsage($status->usage, $status->rates);
                $userCost     = $moneyService->calculateUserCost($providerCost);

                $generation->actual_provider_cost = (string) $providerCost;
                $generation->actual_user_cost     = (string) $userCost;
            }

            $generation->save();
            GenerationCompleted::dispatch($generation);
            return;
        }

        if ($status->isFailed()) {
            $generation->transitionTo(GenerationStatus::Failed);
            $generation->failure_reason      = $status->failureReason;
            $generation->actual_provider_cost = '0.000000';
            $generation->actual_user_cost     = '0.000000';
            $generation->completed_at         = now();
            $generation->save();

            GenerationFailed::dispatch($generation, $status->failureReason);
        }
    }
}
