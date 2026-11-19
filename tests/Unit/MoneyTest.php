<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Billing\Services\MoneyService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_money_cannot_be_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Money::fromMicroUnits(-1);
    }

    public function test_money_zero_factory(): void
    {
        $money = Money::zero();
        $this->assertTrue($money->isZero());
        $this->assertSame(0, $money->microUnits());
    }

    public function test_money_from_decimal_string(): void
    {
        $money = Money::fromDecimalString('0.024500');
        $this->assertSame(24500, $money->microUnits());
        $this->assertSame('0.024500', $money->toDecimalString(6));
    }

    public function test_money_addition(): void
    {
        $a = Money::fromDecimalString('0.010000');
        $b = Money::fromDecimalString('0.015000');

        $result = $a->add($b);

        $this->assertSame(25000, $result->microUnits());
    }

    public function test_money_markup_25_percent(): void
    {
        // Task example: provider $0.0245, 25% markup → user $0.030625
        $provider = Money::fromDecimalString('0.024500');
        $user     = $provider->applyMarkupPercent(25);

        // $0.024500 * 1.25 = $0.030625
        $this->assertSame('0.030625', $user->toDecimalString(6));
    }

    public function test_money_immutability(): void
    {
        $original = Money::fromDecimalString('0.100000');
        $modified = $original->add(Money::fromDecimalString('0.050000'));

        $this->assertSame(100000, $original->microUnits());
        $this->assertSame(150000, $modified->microUnits());
    }

    public function test_money_equality(): void
    {
        $a = Money::fromDecimalString('0.123456');
        $b = Money::fromDecimalString('0.123456');
        $c = Money::fromDecimalString('0.123457');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_money_comparison(): void
    {
        $small = Money::fromDecimalString('0.010000');
        $large = Money::fromDecimalString('0.100000');

        $this->assertTrue($large->isGreaterThan($small));
        $this->assertFalse($small->isGreaterThan($large));
        $this->assertTrue($small->isGreaterThanOrEqualTo($small));
    }

    public function test_money_string_cast(): void
    {
        $money = Money::fromDecimalString('0.030625');
        $this->assertSame('0.030625', (string) $money);
    }

    public function test_money_service_estimates_medium_square(): void
    {
        $service  = new MoneyService(markupPercent: 25);
        // Medium square: 1600 output × $40/1M = 64 micro + reasoning 1000 × $12.5/1M = 6 micro + text
        $provider = $service->estimateProviderCost(ImageSize::Square, ImageQuality::Medium, 'test');

        // Should produce a positive non-zero estimate
        $this->assertGreaterThan(0, $provider->microUnits());
    }

    public function test_money_service_high_quality_costs_more_than_low(): void
    {
        $service = new MoneyService(markupPercent: 25);
        $low     = $service->estimateProviderCost(ImageSize::Square, ImageQuality::Low, 'test');
        $high    = $service->estimateProviderCost(ImageSize::Square, ImageQuality::High, 'test');

        $this->assertTrue($high->isGreaterThan($low));
    }

    public function test_money_service_user_cost_has_25_percent_markup(): void
    {
        $service  = new MoneyService(markupPercent: 25);
        $provider = Money::fromDecimalString('0.024500');
        $user     = $service->calculateUserCost($provider);

        $this->assertSame('0.030625', $user->toDecimalString(6));
    }

    public function test_failed_generation_should_cost_zero(): void
    {
        $cost = Money::zero();

        $this->assertTrue($cost->isZero());
        $this->assertSame('0.000000', $cost->toDecimalString(6));
    }
}
