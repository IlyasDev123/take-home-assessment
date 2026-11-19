<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\MoneyService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\DTOs\CreateGenerationData;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use App\Domain\Generation\Exceptions\InsufficientCreditsException;
use App\Domain\Generation\Models\Generation;
use App\Domain\Generation\Services\GenerationService;
use App\Infrastructure\Pixelforge\Client\PixelforgeException;
use App\Jobs\SendGenerationToPixelforge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class GenerationApplicationService
{
    public function __construct(
        private readonly GenerationService $generationService,
        private readonly MoneyService $moneyService,
        private readonly CreditService $creditService,
    ) {}

    public function initiateGeneration(CreateGenerationData $data): Generation
    {
        $user          = User::findOrFail($data->userId);
        $estimatedCost = $this->moneyService->estimateProviderCost(
            $data->size,
            $data->quality,
            $data->prompt,
            count($data->referenceImages),
        );
        $estimatedUserCost = $this->moneyService->calculateUserCost($estimatedCost);

        $generation = DB::transaction(function () use ($user, $estimatedUserCost, $data): Generation {
            // Lock the credit row and verify balance before creating the generation
            if (!$this->creditService->checkAndLock($user, $estimatedUserCost)) {
                throw new InsufficientCreditsException($estimatedUserCost);
            }

            return $this->generationService->create($data);
        });

        try {
            SendGenerationToPixelforge::dispatch($generation);
        } catch (PixelforgeException $e) {
            // With QUEUE_CONNECTION=sync the job runs inline — catch provider
            // errors here so we can mark the generation failed and surface a
            // friendly message rather than a 500 page.
            Log::error('Failed to submit generation to Pixelforge.', [
                'generation_id' => $generation->id,
                'error_code'    => $e->getErrorCode(),
                'error'         => $e->getMessage(),
            ]);

            $this->markGenerationFailed($generation, $e->getMessage());

            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error dispatching generation job.', [
                'generation_id' => $generation->id,
                'error'         => $e->getMessage(),
            ]);

            $this->markGenerationFailed($generation, 'An unexpected error occurred. Please try again.');

            throw $e;
        }

        return $generation;
    }

    private function markGenerationFailed(Generation $generation, string $reason): void
    {
        try {
            if (!$generation->isInTerminalState()) {
                $generation->transitionTo(GenerationStatus::Failed);
            }
            $generation->failure_reason       = $reason;
            $generation->actual_provider_cost = '0.000000';
            $generation->actual_user_cost     = '0.000000';
            $generation->completed_at         = now();
            $generation->save();
        } catch (\Throwable) {
            // Best-effort cleanup — don't mask the original exception.
        }
    }

    /** @return array{provider: string, user: string} */
    public function estimateCost(
        ImageSize $size,
        ImageQuality $quality,
        string $prompt = '',
        int $referenceImageCount = 0,
    ): array {
        $providerCost = $this->moneyService->estimateProviderCost(
            $size,
            $quality,
            $prompt,
            $referenceImageCount,
        );
        $userCost = $this->moneyService->calculateUserCost($providerCost);

        return [
            'provider' => $this->moneyService->formatForDisplay($providerCost),
            'user'     => $this->moneyService->formatForDisplay($userCost),
        ];
    }
}
