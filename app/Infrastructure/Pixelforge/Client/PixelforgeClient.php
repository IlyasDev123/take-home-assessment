<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\Client;

use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PixelforgeClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeoutSeconds = 30,
    ) {}

    public function generate(GenerationRequest $request): GenerationResponse
    {
        $headers = ['Authorization' => "Bearer {$this->apiKey}"];

        if ($request->idempotencyKey !== null) {
            $headers['Idempotency-Key'] = $request->idempotencyKey;
        }

        $response = $this->makeRequest(fn () => Http::withHeaders($headers)
            ->timeout($this->timeoutSeconds)
            ->post("{$this->baseUrl}/generations", $request->toArray()));

        return new GenerationResponse(
            providerGenerationId: $response['id'],
            status:               $response['status'],
        );
    }

    public function getGeneration(string $providerGenerationId): GenerationStatusResponse
    {
        $response = $this->makeRequest(fn () => Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->get("{$this->baseUrl}/generations/{$providerGenerationId}"));

        return $this->mapGenerationResponse($response);
    }

    /**
     * Fetch provider usage data for a date range.
     * GET /v1/usage?start_date=YYYY-MM-DD&end_date=YYYY-MM-DD
     *
     * @return array<string, mixed>
     */
    public function getUsage(?string $startDate = null, ?string $endDate = null): array
    {
        $params = array_filter([
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ]);

        $response = $this->makeRequest(fn () => Http::withToken($this->apiKey)
            ->timeout($this->timeoutSeconds)
            ->get("{$this->baseUrl}/usage", $params));

        return $response;
    }

    /**
     * Verify a Pixelforge webhook signature.
     *
     * Header format: "t=<unix_timestamp>,v1=<hmac-sha256>"
     * Signed payload: "<timestamp>.<raw_body>"
     * Rejects timestamps older than 300 seconds.
     */
    public function verifyWebhookSignature(
        string $rawPayload,
        string $signatureHeader,
        string $secret,
    ): bool {
        // Parse "t=1234567890,v1=abc123..."
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            [$k, $v] = array_pad(explode('=', $part, 2), 2, '');
            $parts[trim($k)] = trim($v);
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $timestamp = (int) $parts['t'];

        // Reject replayed webhooks older than 5 minutes
        if (abs(time() - $timestamp) > 300) {
            Log::warning('Pixelforge webhook rejected: timestamp too old.', [
                'timestamp' => $timestamp,
                'now'       => time(),
                'diff'      => time() - $timestamp,
            ]);
            return false;
        }

        $expectedSig = hash_hmac('sha256', "{$timestamp}.{$rawPayload}", $secret);

        return hash_equals($expectedSig, $parts['v1']);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /** @param array<string, mixed> $data */
    private function mapGenerationResponse(array $data): GenerationStatusResponse
    {
        $error = $data['error'] ?? null;

        return new GenerationStatusResponse(
            providerGenerationId: $data['id'],
            status:               $data['status'],
            imageUrl:             $data['image_url'] ?? null,
            imageUrlExpiresAt:    $data['image_url_expires_at'] ?? null,
            usage:                $data['usage'] ?? null,
            rates:                $data['rates'] ?? null,
            failureReason:        is_array($error) ? ($error['message'] ?? null) : null,
            errorCode:            is_array($error) ? ($error['code'] ?? null) : null,
        );
    }

    private function makeRequest(callable $fn): array
    {
        try {
            /** @var Response $response */
            $response = $fn();
        } catch (ConnectionException $e) {
            throw PixelforgeException::connectionFailed($e->getMessage());
        }

        $status = $response->status();

        if ($status === 401) {
            throw PixelforgeException::invalidApiKey();
        }

        if ($status === 409) {
            throw PixelforgeException::fromHttpStatus(409, $response->json() ?: []);
        }

        if ($status === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 60);
            throw PixelforgeException::rateLimited($retryAfter);
        }

        if ($status === 503) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 30);
            throw PixelforgeException::overloaded($retryAfter);
        }

        if ($response->serverError()) {
            throw PixelforgeException::serverError($status, $response->body());
        }

        if ($response->clientError()) {
            throw PixelforgeException::fromHttpStatus($status, $response->json() ?: []);
        }

        $response->throw();

        return $response->json();
    }
}
