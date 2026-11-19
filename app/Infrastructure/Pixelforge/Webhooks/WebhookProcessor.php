<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\Webhooks;

use App\Domain\Billing\Services\MoneyService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Events\GenerationCompleted;
use App\Domain\Generation\Events\GenerationFailed;
use App\Domain\Generation\Exceptions\GenerationNotFoundException;
use App\Domain\Generation\Models\Generation;
use App\Domain\Generation\Models\GenerationWebhookEvent;
use App\Infrastructure\Pixelforge\DTOs\WebhookPayload;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class WebhookProcessor
{
    public function __construct(private readonly MoneyService $moneyService) {}

    public function process(WebhookPayload $payload): void
    {
        DB::transaction(function () use ($payload): void {
            // ── Idempotency ───────────────────────────────────────────────
            $event = GenerationWebhookEvent::where('provider_event_id', $payload->eventId)
                ->lockForUpdate()
                ->first();

            if ($event !== null && $event->isProcessed()) {
                Log::info('Duplicate webhook event ignored.', [
                    'provider_event_id' => $payload->eventId,
                    'event_type'        => $payload->eventType,
                ]);
                return;
            }

            // ── Locate generation ─────────────────────────────────────────
            $generation = Generation::where('provider_generation_id', $payload->providerGenerationId)
                ->first();

            if ($generation === null) {
                throw GenerationNotFoundException::forProviderId($payload->providerGenerationId);
            }

            // ── Out-of-order guard ────────────────────────────────────────
            if ($generation->isInTerminalState()) {
                Log::info('Webhook ignored: generation already terminal.', [
                    'generation_id' => $generation->id,
                    'status'        => $generation->status->value,
                    'event_type'    => $payload->eventType,
                ]);
                return;
            }

            // ── Persist raw event ─────────────────────────────────────────
            if ($event === null) {
                $event = GenerationWebhookEvent::create([
                    'provider_event_id' => $payload->eventId,
                    'generation_id'     => $generation->id,
                    'event_type'        => $payload->eventType,
                    'payload'           => [
                        'event_type'    => $payload->eventType,
                        'status'        => $payload->status,
                        'has_usage'     => $payload->usage !== null,
                    ],
                ]);
            }

            // ── Dispatch to correct handler ───────────────────────────────
            match ($payload->eventType) {
                'generation.succeeded' => $this->handleSucceeded($generation, $payload),
                'generation.failed'    => $this->handleFailed($generation, $payload),
                'generation.processing'=> $this->handleProcessing($generation),
                default => Log::warning('Unknown Pixelforge event type.', [
                    'event_type' => $payload->eventType,
                ]),
            };

            $event->markProcessed();
            $event->save();
        });
    }

    private function handleSucceeded(Generation $generation, WebhookPayload $payload): void
    {
        $generation->transitionTo(GenerationStatus::Succeeded);
        $generation->image_url              = $payload->imageUrl;
        $generation->image_url_expires_at   = $payload->imageUrlExpiresAt;
        $generation->completed_at           = now();

        if ($payload->usage !== null && $payload->rates !== null) {
            $providerCost = $this->moneyService->calculateActualCostFromUsage(
                $payload->usage,
                $payload->rates,
            );
            $userCost = $this->moneyService->calculateUserCost($providerCost);

            $generation->actual_provider_cost = (string) $providerCost;
            $generation->actual_user_cost     = (string) $userCost;
        }

        $generation->save();

        // Dispatch AFTER the transaction commits so a credit-deduction failure
        // cannot roll back the status update and leave the generation stuck.
        DB::afterCommit(function () use ($generation): void {
            try {
                GenerationCompleted::dispatch($generation);
            } catch (Throwable $e) {
                Log::error('Failed to fire GenerationCompleted event.', [
                    'generation_id' => $generation->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        });
    }

    private function handleFailed(Generation $generation, WebhookPayload $payload): void
    {
        $generation->transitionTo(GenerationStatus::Failed);
        $generation->failure_reason       = $payload->failureReason;
        $generation->error_code           = $payload->errorCode;
        $generation->actual_provider_cost = '0.000000';
        $generation->actual_user_cost     = '0.000000';
        $generation->completed_at         = now();
        $generation->save();

        DB::afterCommit(function () use ($generation, $payload): void {
            try {
                GenerationFailed::dispatch($generation, $payload->failureReason);
            } catch (Throwable $e) {
                Log::error('Failed to fire GenerationFailed event.', [
                    'generation_id' => $generation->id,
                    'error'         => $e->getMessage(),
                ]);
            }
        });
    }

    private function handleProcessing(Generation $generation): void
    {
        if ($generation->status === GenerationStatus::Queued) {
            $generation->transitionTo(GenerationStatus::Processing);
            $generation->save();
        }
    }
}
