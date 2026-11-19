<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\DTOs;

final readonly class GenerationRequest
{
    public function __construct(
        public string $prompt,
        public string $size,
        public string $quality,
        /** @var string[] */
        public array $referenceImages = [],
        /** @var array<string, string> */
        public array $metadata = [],
        public ?string $idempotencyKey = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $body = [
            'prompt'  => $this->prompt,
            'size'    => $this->size,
            'quality' => $this->quality,
        ];

        if (!empty($this->referenceImages)) {
            $body['reference_images'] = $this->referenceImages;
        }

        if (!empty($this->metadata)) {
            $body['metadata'] = $this->metadata;
        }

        return $body;
    }
}
