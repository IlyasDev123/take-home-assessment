<?php

declare(strict_types=1);

namespace App\Domain\Generation\Exceptions;

use App\Domain\Generation\Enums\GenerationStatus;
use RuntimeException;

final class InvalidStatusTransitionException extends RuntimeException
{
    public static function from(GenerationStatus $from, GenerationStatus $to): self
    {
        return new self(
            "Cannot transition generation from [{$from->value}] to [{$to->value}]."
        );
    }
}
