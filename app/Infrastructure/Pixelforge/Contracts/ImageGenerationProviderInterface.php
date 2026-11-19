<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\Contracts;

use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;

interface ImageGenerationProviderInterface
{
    /**
     * Submit a new generation request and return the provider-assigned ID.
     */
    public function generate(GenerationRequest $request): GenerationResponse;

    /**
     * Poll for the current state of a generation.
     */
    public function getGeneration(string $providerGenerationId): GenerationStatusResponse;

    /**
     * Verify a webhook signature. Returns true if the signature is valid.
     */
    public function verifyWebhookSignature(string $rawPayload, string $signature): bool;
}
