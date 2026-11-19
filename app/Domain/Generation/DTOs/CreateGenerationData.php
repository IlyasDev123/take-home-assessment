<?php

declare(strict_types=1);

namespace App\Domain\Generation\DTOs;

use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;

final readonly class CreateGenerationData
{
    public function __construct(
        public int $userId,
        public string $prompt,
        public ImageSize $size,
        public ImageQuality $quality,
        /** @var string[] */
        public array $referenceImages = [],
    ) {}
}
