<?php

declare(strict_types=1);

namespace App\Domain\Generation\Exceptions;

use RuntimeException;

final class GenerationNotFoundException extends RuntimeException
{
    public static function forId(int $id): self
    {
        return new self("Generation [{$id}] not found.");
    }

    public static function forProviderId(string $providerId): self
    {
        return new self("Generation with provider ID [{$providerId}] not found.");
    }
}
