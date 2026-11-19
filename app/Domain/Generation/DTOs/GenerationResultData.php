<?php

declare(strict_types=1);

namespace App\Domain\Generation\DTOs;

final readonly class GenerationResultData
{
    public function __construct(
        public string $providerGenerationId,
        public ?string $imageUrl,
        public string $actualProviderCost,
        public bool $succeeded,
        public ?string $failureReason = null,
    ) {}
}
