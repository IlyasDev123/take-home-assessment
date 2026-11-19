<?php

declare(strict_types=1);

namespace App\Domain\Generation\Services;

use App\Domain\Billing\Services\MoneyService;
use App\Domain\Generation\DTOs\CreateGenerationData;
use App\Domain\Generation\Events\GenerationCreated;
use App\Domain\Generation\Models\Generation;

final class GenerationService
{
    public function __construct(private readonly MoneyService $moneyService) {}

    public function create(CreateGenerationData $data): Generation
    {
        $providerCost = $this->moneyService->estimateProviderCost(
            $data->size,
            $data->quality,
            $data->prompt,
            count($data->referenceImages),
        );
        $userCost = $this->moneyService->calculateUserCost($providerCost);

        $generation = Generation::create([
            'user_id'                 => $data->userId,
            'prompt'                  => $data->prompt,
            'size'                    => $data->size->value,
            'quality'                 => $data->quality->value,
            'reference_images'        => $data->referenceImages,
            'status'                  => 'pending',
            'estimated_provider_cost' => (string) $providerCost,
            'estimated_user_cost'     => (string) $userCost,
        ]);

        GenerationCreated::dispatch($generation);

        return $generation;
    }
}
