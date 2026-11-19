<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge;

use App\Domain\Generation\Models\Generation;
use App\Jobs\SimulateGenerationJob;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;
use Illuminate\Support\Str;

/**
 * Fake provider for local development / demo.
 * Dispatches SimulateGenerationJob which runs the full generation lifecycle
 * internally — no external HTTP calls needed.
 *
 * Enable via PIXELFORGE_DRIVER=fake in .env
 */
final class FakePixelforgeProvider implements ImageGenerationProviderInterface
{
    public function __construct(private readonly string $webhookSecret = '') {}

    public function generate(GenerationRequest $request): GenerationResponse
    {
        $providerGenerationId = 'gen_fake_' . Str::random(16);

        // Dispatch with a 5-second delay so the UI briefly shows "queued" status
        SimulateGenerationJob::dispatch($providerGenerationId, 'processing')
            ->delay(now()->addSeconds(5));

        return new GenerationResponse(
            providerGenerationId: $providerGenerationId,
            status:               'queued',
        );
    }

    public function getGeneration(string $providerGenerationId): GenerationStatusResponse
    {
        // Return current DB state so polling-based fallback works correctly
        $generation = Generation::where('provider_generation_id', $providerGenerationId)->first();

        if ($generation === null) {
            return new GenerationStatusResponse(
                providerGenerationId: $providerGenerationId,
                status:               'queued',
            );
        }

        $status = $generation->status->value;

        if ($status === 'succeeded') {
            return new GenerationStatusResponse(
                providerGenerationId: $providerGenerationId,
                status:               'succeeded',
                imageUrl:             $generation->image_url,
                imageUrlExpiresAt:    $generation->image_url_expires_at?->toIso8601String(),
                usage:                null,
                rates:                null,
            );
        }

        if ($status === 'failed') {
            return new GenerationStatusResponse(
                providerGenerationId: $providerGenerationId,
                status:               'failed',
                failureReason:        $generation->failure_reason,
                errorCode:            $generation->error_code,
            );
        }

        return new GenerationStatusResponse(
            providerGenerationId: $providerGenerationId,
            status:               $status,
        );
    }

    public function verifyWebhookSignature(string $rawPayload, string $signatureHeader): bool
    {
        if ($this->webhookSecret === '') {
            return true;
        }

        // Parse "t=<ts>,v1=<hmac>"
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        return hash_equals(
            hash_hmac('sha256', "{$parts['t']}.{$rawPayload}", $this->webhookSecret),
            $parts['v1'],
        );
    }
}
