<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\DTOs\WebhookPayload;
use App\Infrastructure\Pixelforge\Webhooks\WebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Simulates Pixelforge's async generation lifecycle for the fake provider.
 *
 * Stage 'processing': fires a generation.processing webhook, then chains 'completion'.
 * Stage 'completion' : fires generation.succeeded or generation.failed.
 *
 * Both stages run through the real WebhookProcessor so all idempotency,
 * state machine, and cost-calculation logic is exercised identically to
 * production.
 */
final class SimulateGenerationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        private readonly string $providerGenerationId,
        private readonly string $stage = 'processing',
    ) {}

    public function handle(WebhookProcessor $processor): void
    {
        $generation = Generation::where('provider_generation_id', $this->providerGenerationId)->first();

        if ($generation === null || $generation->isInTerminalState()) {
            return;
        }

        if ($this->stage === 'processing') {
            $this->handleProcessingStage($processor, $generation);
        } else {
            $this->handleCompletionStage($processor, $generation);
        }
    }

    private function handleProcessingStage(WebhookProcessor $processor, Generation $generation): void
    {
        $processor->process(new WebhookPayload(
            eventId:              'sim_processing_' . $this->providerGenerationId,
            eventType:            'generation.processing',
            providerGenerationId: $this->providerGenerationId,
            status:               'processing',
        ));

        // Chain completion stage after 15 seconds
        self::dispatch($this->providerGenerationId, 'completion')
            ->delay(now()->addSeconds(15));
    }

    private function handleCompletionStage(WebhookProcessor $processor, Generation $generation): void
    {
        // 5% failure rate for realistic simulation
        $shouldFail = (abs(crc32($this->providerGenerationId)) % 20) === 0;

        if ($shouldFail) {
            $processor->process(new WebhookPayload(
                eventId:              'sim_completion_' . $this->providerGenerationId,
                eventType:            'generation.failed',
                providerGenerationId: $this->providerGenerationId,
                status:               'failed',
                failureReason:        'Simulated provider error: content policy violation.',
                errorCode:            'content_policy_violation',
            ));
            return;
        }

        // Deterministic image: same prompt → same picture (seed from prompt hash)
        $seed     = abs(crc32($generation->prompt)) % 1000;
        [$w, $h]  = $this->dimensionsFor($generation->size->value);
        $imageUrl = "https://picsum.photos/seed/{$seed}/{$w}/{$h}";

        // Realistic token usage based on prompt length + quality
        $textTokens      = max(10, (int) ceil(mb_strlen($generation->prompt) / 4));
        $imageTokens     = count($generation->reference_images ?? []) * 765;
        $outputTokens    = $this->outputTokensFor($generation->quality->value, $generation->size->value);
        $reasoningTokens = $this->reasoningTokensFor($generation->quality->value);

        $processor->process(new WebhookPayload(
            eventId:              'sim_completion_' . $this->providerGenerationId,
            eventType:            'generation.succeeded',
            providerGenerationId: $this->providerGenerationId,
            status:               'succeeded',
            imageUrl:             $imageUrl,
            imageUrlExpiresAt:    now()->addHours(24)->toIso8601String(),
            usage: [
                'input_text_tokens'  => $textTokens,
                'input_image_tokens' => $imageTokens,
                'output_tokens'      => $outputTokens,
                'reasoning_tokens'   => $reasoningTokens,
            ],
            rates: [
                'input_text'  => '5.000000',
                'input_image' => '10.000000',
                'output'      => '40.000000',
                'reasoning'   => '12.500000',
            ],
        ));
    }

    /** @return array{int,int} */
    private function dimensionsFor(string $size): array
    {
        return match ($size) {
            '1536x1024' => [1536, 1024],
            '1024x1536' => [1024, 1536],
            default     => [1024, 1024],
        };
    }

    private function outputTokensFor(string $quality, string $size): int
    {
        $base = match ($quality) {
            'low'  => 350,
            'high' => 4000,
            default => 1600,
        };
        $multiplier = in_array($size, ['1536x1024', '1024x1536'], true) ? 1.5 : 1.0;
        return (int) ceil($base * $multiplier);
    }

    private function reasoningTokensFor(string $quality): int
    {
        return match ($quality) {
            'low'  => 0,
            'high' => 5000,
            default => 1000,
        };
    }
}
