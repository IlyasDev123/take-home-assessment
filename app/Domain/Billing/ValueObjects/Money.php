<?php

declare(strict_types=1);

namespace App\Domain\Billing\ValueObjects;

use InvalidArgumentException;
use Stringable;

/**
 * Immutable money value object using integer micro-units (1/1_000_000 of a dollar)
 * to avoid all floating-point precision issues.
 */
final class Money implements Stringable
{
    private const SCALE = 1_000_000;

    private function __construct(private readonly int $microUnits)
    {
        if ($microUnits < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative.');
        }
    }

    public static function fromMicroUnits(int $microUnits): self
    {
        return new self($microUnits);
    }

    public static function fromDecimalString(string $decimal): self
    {
        if (!preg_match('/^\d+(\.\d+)?$/', $decimal)) {
            throw new InvalidArgumentException("Invalid decimal string: {$decimal}");
        }

        // Use BCMath to avoid any float involvement
        $scaled = bcmul($decimal, (string) self::SCALE, 0);

        return new self((int) $scaled);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function microUnits(): int
    {
        return $this->microUnits;
    }

    public function toDecimalString(int $precision = 6): string
    {
        return bcdiv((string) $this->microUnits, (string) self::SCALE, $precision);
    }

    public function add(self $other): self
    {
        return new self($this->microUnits + $other->microUnits);
    }

    public function subtract(self $other): self
    {
        $result = $this->microUnits - $other->microUnits;
        return new self(max(0, $result));
    }

    public function multiplyByFloat(float $multiplier): self
    {
        // Use BCMath: convert float to string with enough precision
        $multiplierStr = number_format($multiplier, 10, '.', '');
        $result = bcmul((string) $this->microUnits, $multiplierStr, 0);

        return new self((int) $result);
    }

    public function applyMarkupPercent(int $percent): self
    {
        $markupMicro = (int) round($this->microUnits * $percent / 100);

        return new self($this->microUnits + $markupMicro);
    }

    public function isZero(): bool
    {
        return $this->microUnits === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->microUnits > $other->microUnits;
    }

    public function isGreaterThanOrEqualTo(self $other): bool
    {
        return $this->microUnits >= $other->microUnits;
    }

    public function equals(self $other): bool
    {
        return $this->microUnits === $other->microUnits;
    }

    public function formatDollars(): string
    {
        return '$' . number_format((float) $this->toDecimalString(6), 6, '.', ',');
    }

    public function __toString(): string
    {
        return $this->toDecimalString(6);
    }
}
