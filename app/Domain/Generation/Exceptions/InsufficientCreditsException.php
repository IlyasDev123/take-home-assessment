<?php

declare(strict_types=1);

namespace App\Domain\Generation\Exceptions;

use App\Domain\Billing\ValueObjects\Money;
use RuntimeException;

final class InsufficientCreditsException extends RuntimeException
{
    public function __construct(public readonly Money $required)
    {
        parent::__construct(
            "Insufficient credits. You need {$required->formatDollars()} to generate this image."
        );
    }
}
