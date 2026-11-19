<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\Client;

use RuntimeException;

final class PixelforgeException extends RuntimeException
{
    private ?int $retryAfter;

    private function __construct(string $message, int $code = 0, ?int $retryAfter = null)
    {
        parent::__construct($message, $code);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    public static function rateLimited(int $retryAfter = 60): self
    {
        return new self("Pixelforge API rate limit exceeded. Retry after {$retryAfter}s.", 429, $retryAfter);
    }

    public static function overloaded(int $retryAfter = 30): self
    {
        return new self("Pixelforge API overloaded. Retry after {$retryAfter}s.", 503, $retryAfter);
    }

    public static function serverError(int $statusCode, string $body): self
    {
        return new self("Pixelforge API server error [{$statusCode}]: {$body}", $statusCode);
    }

    public static function connectionFailed(string $message): self
    {
        return new self("Pixelforge API connection failed: {$message}", 0);
    }
}
