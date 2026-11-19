<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\Client;

use RuntimeException;

final class PixelforgeException extends RuntimeException
{
    private ?int    $retryAfter;
    private ?string $errorCode;

    private function __construct(
        string  $message,
        int     $httpCode  = 0,
        ?int    $retryAfter = null,
        ?string $errorCode  = null,
    ) {
        parent::__construct($message, $httpCode);
        $this->retryAfter = $retryAfter;
        $this->errorCode  = $errorCode;
    }

    public function getRetryAfter(): ?int    { return $this->retryAfter; }
    public function getErrorCode(): ?string  { return $this->errorCode; }

    public function isIdempotencyConflict(): bool
    {
        return $this->errorCode === 'idempotency_key_reused';
    }

    public function isRateLimited(): bool    { return $this->code === 429; }
    public function isServerError(): bool    { return $this->code >= 500; }
    public function isConnectionError(): bool { return $this->code === 0; }

    // ── Factories ────────────────────────────────────────────────────────────

    public static function rateLimited(int $retryAfter = 60): self
    {
        return new self(
            "Provider rate limit reached. Please wait {$retryAfter} seconds and try again.",
            429, $retryAfter,
        );
    }

    public static function overloaded(int $retryAfter = 30): self
    {
        return new self(
            "Provider is currently overloaded. Please try again in a moment.",
            503, $retryAfter,
        );
    }

    public static function serverError(int $statusCode, string $body): self
    {
        return new self(
            "Provider returned a server error. Please try again.",
            $statusCode, null,
        );
    }

    public static function connectionFailed(string $message): self
    {
        return new self(
            "Could not connect to the generation provider. Please check your connection and try again.",
            0,
        );
    }

    public static function idempotencyConflict(string $apiMessage = ''): self
    {
        return new self(
            "This generation was already submitted to the provider. Please check your generation history.",
            409, null, 'idempotency_key_reused',
        );
    }

    public static function invalidApiKey(): self
    {
        return new self("Invalid or missing API key. Please contact support.", 401);
    }

    public static function fromHttpStatus(int $status, array $body): self
    {
        $code    = $body['error']['code']    ?? null;
        $message = $body['error']['message'] ?? "Unexpected provider error (HTTP {$status}).";

        if ($code === 'idempotency_key_reused') {
            return self::idempotencyConflict($message);
        }

        return new self($message, $status, null, $code);
    }
}
