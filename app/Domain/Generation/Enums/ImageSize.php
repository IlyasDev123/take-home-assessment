<?php

declare(strict_types=1);

namespace App\Domain\Generation\Enums;

enum ImageSize: string
{
    case Square      = '1024x1024';
    case LandscapeHD = '1536x1024';
    case PortraitHD  = '1024x1536';

    public function label(): string
    {
        return match ($this) {
            self::Square      => 'Square (1024×1024)',
            self::LandscapeHD => 'Landscape HD (1536×1024)',
            self::PortraitHD  => 'Portrait HD (1024×1536)',
        };
    }

    /** Approximate output token multiplier relative to square */
    public function outputTokenMultiplier(): float
    {
        return match ($this) {
            self::Square      => 1.0,
            self::LandscapeHD => 1.5,
            self::PortraitHD  => 1.5,
        };
    }
}
