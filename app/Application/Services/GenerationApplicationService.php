<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\Services\MoneyService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\DTOs\CreateGenerationData;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use App\Domain\Generation\Exceptions\InsufficientCreditsException;
use App\Domain\Generation\Models\Generation;
use App\Domain\Generation\Services\GenerationService;
use App\Jobs\SendGenerationToPixelforge;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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

        SendGenerationToPixelforge::dispatch($generation);

        return $generation;
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
