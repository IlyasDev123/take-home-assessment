<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\DTOs;

final readonly class GenerationResponse
{
    public function __construct(
        public string $providerGenerationId,
        public string $status,
    ) {}
}
