<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Events\GenerationFailed;
use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SendGenerationToPixelforge implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    /** @var array<int, int> seconds to wait before retrying */
    public array $backoff = [30, 120, 300];

    public function __construct(public readonly Generation $generation) {}

    public function handle(ImageGenerationProviderInterface $provider): void
    {
        $generation = $this->generation;

        if ($generation->isInTerminalState()) {
            return;
        }

        $request = new GenerationRequest(
            prompt:           $generation->prompt,
            size:             $generation->size->value,
            quality:          $generation->quality->value,
            referenceImages:  $generation->reference_images ?? [],
            metadata:         ['internal_generation_id' => (string) $generation->id],
            idempotencyKey:   "gen-{$generation->id}",
        );

        $response = $provider->generate($request);

        $generation->provider_generation_id = $response->providerGenerationId;
        $generation->transitionTo(GenerationStatus::Queued);
        $generation->save();

        Log::info('Generation submitted to Pixelforge.', [
            'generation_id'          => $generation->id,
            'provider_generation_id' => $response->providerGenerationId,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendGenerationToPixelforge job permanently failed.', [
            'generation_id' => $this->generation->id,
            'error'         => $exception->getMessage(),
        ]);

        $this->generation->transitionTo(GenerationStatus::Failed);
        $this->generation->failure_reason      = 'Failed to submit to provider: ' . $exception->getMessage();
        $this->generation->actual_provider_cost = '0.000000';
        $this->generation->actual_user_cost     = '0.000000';
        $this->generation->completed_at         = now();
        $this->generation->save();

        GenerationFailed::dispatch($this->generation, $this->generation->failure_reason);
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addHour();
    }
}
