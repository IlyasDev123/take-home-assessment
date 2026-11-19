<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\DTOs;

final readonly class GenerationStatusResponse
{
    /**
     * @param array{input_text_tokens:int,input_image_tokens:int,output_tokens:int,reasoning_tokens:int}|null $usage
     * @param array{input_text:string,input_image:string,output:string,reasoning:string}|null                 $rates
     */
    public function __construct(
        public string  $providerGenerationId,
        public string  $status,
        public ?string $imageUrl              = null,
        public ?string $imageUrlExpiresAt     = null,
        public ?array  $usage                 = null,
        public ?array  $rates                 = null,
        public ?string $failureReason         = null,
        public ?string $errorCode             = null,
    ) {}

    public function isCompleted(): bool
    {
        return $this->status === 'succeeded';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return in_array($this->status, ['queued', 'processing'], true);
    }

    public function hasUsageData(): bool
    {
        return $this->usage !== null && $this->rates !== null;
    }
}
