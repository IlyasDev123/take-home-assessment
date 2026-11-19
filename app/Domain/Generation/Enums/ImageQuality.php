<?php

declare(strict_types=1);

namespace App\Domain\Generation\Enums;

enum ImageQuality: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';

    public function label(): string
    {
        return match ($this) {
            self::Low    => 'Low',
            self::Medium => 'Medium',
            self::High   => 'High',
        };
    }

    /** Estimated output tokens for a square image at this quality */
    public function estimatedOutputTokens(): int
    {
        return match ($this) {
            self::Low    => 350,
            self::Medium => 1600,
            self::High   => 4000,
        };
    }

    /** Estimated reasoning tokens at this quality */
    public function estimatedReasoningTokens(): int
    {
        return match ($this) {
            self::Low    => 0,
            self::Medium => 1000,
            self::High   => 5000,
        };
    }
}
