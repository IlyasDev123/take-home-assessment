<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge;

use App\Infrastructure\Pixelforge\Client\PixelforgeClient;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;

final class PixelforgeProvider implements ImageGenerationProviderInterface
{
    public function __construct(
        private readonly PixelforgeClient $client,
        private readonly string $webhookSecret,
    ) {}

    public function generate(GenerationRequest $request): GenerationResponse
    {
        return $this->client->generate($request);
    }

    public function getGeneration(string $providerGenerationId): GenerationStatusResponse
    {
        return $this->client->getGeneration($providerGenerationId);
    }

    /**
     * @param string $signatureHeader  Full "t=<ts>,v1=<hmac>" header value
     */
    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        return $this->client->verifyWebhookSignature($rawPayload, $signatureHeader, $this->webhookSecret);
    }
}
