<?php

declare(strict_types=1);

namespace App\Domain\Generation\Enums;

enum GenerationStatus: string
{
    case Pending    = 'pending';
    case Queued     = 'queued';
    case Processing = 'processing';
    case Succeeded  = 'succeeded';
    case Failed     = 'failed';

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Succeeded, self::Failed => true,
            default => false,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending    => in_array($next, [self::Queued, self::Failed], true),
            self::Queued     => in_array($next, [self::Processing, self::Succeeded, self::Failed], true),
            self::Processing => in_array($next, [self::Succeeded, self::Failed], true),
            self::Succeeded, self::Failed => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Queued     => 'Queued',
            self::Processing => 'Processing',
            self::Succeeded  => 'Succeeded',
            self::Failed     => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'gray',
            self::Queued     => 'blue',
            self::Processing => 'yellow',
            self::Succeeded  => 'green',
            self::Failed     => 'red',
        };
    }
}
