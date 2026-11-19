<?php

declare(strict_types=1);

namespace App\Domain\Billing\Services;

use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;

/**
 * Token-based cost model matching the Pixelforge pricing spec.
 *
 * Rates (per 1M tokens):
 *   Input text   $5.00
 *   Input image  $10.00
 *   Output       $40.00
 *   Reasoning    $12.50
 *
 * Token estimates:
 *   Text         ~1 token per 4 characters
 *   Ref images   765 tokens each (up to 4)
 *   Output       quality × size multiplier (280–6,450 range)
 *   Reasoning    quality-driven (0–8,000 range)
 */
final class MoneyService
{
    // Rates in micro-dollars per token ($X.00/1M = X micro-dollars per token)
    private const INPUT_TEXT_RATE_MICRO_PER_TOKEN   = 5;      // $5.00/1M
    private const INPUT_IMAGE_RATE_MICRO_PER_TOKEN  = 10;     // $10.00/1M
    private const OUTPUT_RATE_MICRO_PER_TOKEN       = 40;     // $40.00/1M
    private const REASONING_RATE_MICRO_PER_TOKEN    = 12;     // $12.50/1M (rounded; full precision below)

    // Tokens per reference image (from spec)
    private const TOKENS_PER_REFERENCE_IMAGE = 765;

    public function __construct(private readonly int $markupPercent = 25) {}

    public function estimateProviderCost(
        ImageSize $size,
        ImageQuality $quality,
        string $prompt = '',
        int $referenceImageCount = 0,
    ): Money {
        // Input text tokens (~1 per 4 chars)
        $textTokens  = max(1, (int) ceil(mb_strlen($prompt) / 4));
        $imageTokens = $referenceImageCount * self::TOKENS_PER_REFERENCE_IMAGE;

        // Output tokens — quality × size multiplier
        $outputTokens    = (int) ceil($quality->estimatedOutputTokens() * $size->outputTokenMultiplier());
        $reasoningTokens = $quality->estimatedReasoningTokens();

        return $this->computeCost($textTokens, $imageTokens, $outputTokens, $reasoningTokens);
    }

    /**
     * Calculate actual provider cost from the usage object returned by Pixelforge.
     *
     * @param array{input_text_tokens: int, input_image_tokens: int, output_tokens: int, reasoning_tokens: int} $usage
     * @param array{input_text: string, input_image: string, output: string, reasoning: string} $rates
     */
    public function calculateActualCostFromUsage(array $usage, array $rates): Money
    {
        // Use BCMath throughout — rates are exact decimal strings
        $cost = '0';
        $scale = 10;

        $cost = bcadd($cost, bcmul((string) $usage['input_text_tokens'],   bcdiv($rates['input_text'],  '1000000', $scale), $scale), $scale);
        $cost = bcadd($cost, bcmul((string) $usage['input_image_tokens'],  bcdiv($rates['input_image'], '1000000', $scale), $scale), $scale);
        $cost = bcadd($cost, bcmul((string) $usage['output_tokens'],       bcdiv($rates['output'],      '1000000', $scale), $scale), $scale);
        $cost = bcadd($cost, bcmul((string) $usage['reasoning_tokens'],    bcdiv($rates['reasoning'],   '1000000', $scale), $scale), $scale);

        return Money::fromDecimalString(bcadd($cost, '0', 6)); // normalize to 6dp
    }

    public function calculateUserCost(Money $providerCost): Money
    {
        return $providerCost->applyMarkupPercent($this->markupPercent);
    }

    public function formatForDisplay(Money $money): string
    {
        return $money->formatDollars();
    }

    public function parseProviderCost(string $costDecimal): Money
    {
        return Money::fromDecimalString($costDecimal);
    }

    // ─── Private ─────────────────────────────────────────────────────────────

    private function computeCost(
        int $textTokens,
        int $imageTokens,
        int $outputTokens,
        int $reasoningTokens,
    ): Money {
        // All arithmetic in integer micro-units to stay float-free
        // Rate is X micro-dollars per token (per 1M × 1M = per token)
        $microUnits = 0;
        $microUnits += intdiv($textTokens    * self::INPUT_TEXT_RATE_MICRO_PER_TOKEN,  1);
        $microUnits += intdiv($imageTokens   * self::INPUT_IMAGE_RATE_MICRO_PER_TOKEN, 1);
        $microUnits += intdiv($outputTokens  * self::OUTPUT_RATE_MICRO_PER_TOKEN,       1);

        // Reasoning: $12.50/1M = 12.5 micro per token — multiply by 25 and divide by 2
        $microUnits += intdiv($reasoningTokens * 25, 2);

        return Money::fromMicroUnits($microUnits);
    }
}
