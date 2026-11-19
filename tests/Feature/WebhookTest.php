<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Billing\Models\Credit;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Models\Generation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.pixelforge.webhook_secret' => self::WEBHOOK_SECRET,
            'services.pixelforge.driver'          => 'real',
        ]);
    }

    public function test_valid_completed_webhook_updates_generation(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Queued);

        $response = $this->postWebhook(
            eventId:   'evt_001',
            eventType: 'generation.succeeded',
            generation: $generation,
            overrides:  [
                'status'   => 'succeeded',
                'image_url'=> 'https://cdn.pixelforge.fake/images/abc.png',
                'usage'    => ['input_text_tokens' => 50, 'input_image_tokens' => 0, 'output_tokens' => 1600, 'reasoning_tokens' => 1000],
                'rates'    => ['input_text' => '5.000000', 'input_image' => '10.000000', 'output' => '40.000000', 'reasoning' => '12.500000'],
            ],
        );

        $response->assertOk();

        $generation->refresh();
        $this->assertSame(GenerationStatus::Succeeded->value, $generation->status->value);
        $this->assertSame('https://cdn.pixelforge.fake/images/abc.png', $generation->image_url);
        $this->assertNotNull($generation->actual_user_cost);
        $this->assertNotNull($generation->completed_at);
    }

    public function test_failed_generation_costs_zero(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Queued);

        $response = $this->postWebhook(
            eventId:   'evt_002',
            eventType: 'generation.failed',
            generation: $generation,
            overrides:  [
                'status' => 'failed',
                'error'  => ['code' => 'content_policy_violation', 'message' => 'Prompt rejected.'],
            ],
        );

        $response->assertOk();

        $generation->refresh();
        $this->assertSame(GenerationStatus::Failed->value, $generation->status->value);
        $this->assertEqualsWithDelta(0.0, (float) $generation->actual_provider_cost, 0.0000001);
        $this->assertEqualsWithDelta(0.0, (float) $generation->actual_user_cost, 0.0000001);
    }

    public function test_duplicate_webhook_is_ignored(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Queued);

        $this->postWebhook('evt_003', 'generation.succeeded', $generation, [
            'status'   => 'succeeded',
            'image_url'=> 'https://cdn.pixelforge.fake/images/abc.png',
            'usage'    => ['input_text_tokens' => 50, 'input_image_tokens' => 0, 'output_tokens' => 1600, 'reasoning_tokens' => 1000],
            'rates'    => ['input_text' => '5.000000', 'input_image' => '10.000000', 'output' => '40.000000', 'reasoning' => '12.500000'],
        ])->assertOk();

        // Second delivery with same event ID
        $this->postWebhook('evt_003', 'generation.succeeded', $generation, [
            'status'   => 'succeeded',
            'image_url'=> 'https://cdn.pixelforge.fake/images/abc.png',
            'usage'    => ['input_text_tokens' => 50, 'input_image_tokens' => 0, 'output_tokens' => 1600, 'reasoning_tokens' => 1000],
            'rates'    => ['input_text' => '5.000000', 'input_image' => '10.000000', 'output' => '40.000000', 'reasoning' => '12.500000'],
        ])->assertOk();

        $this->assertDatabaseCount('generation_webhook_events', 1);
    }

    public function test_invalid_signature_is_rejected(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Queued);
        $timestamp  = time();
        $rawBody    = json_encode(['type' => 'generation.succeeded', 'data' => ['object' => ['id' => $generation->provider_generation_id, 'status' => 'succeeded']]]);

        // Send a valid timestamp but wrong HMAC
        $badSignature = "t={$timestamp},v1=bad_hmac_value";

        $response = $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [], [], [],
            [
                'CONTENT_TYPE'              => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE' => $badSignature,
                'HTTP_PIXELFORGE_EVENT_ID'  => 'evt_bad',
                'HTTP_PIXELFORGE_EVENT_TYPE'=> 'generation.succeeded',
            ],
            $rawBody,
        );

        $response->assertUnauthorized();
        $generation->refresh();
        $this->assertSame(GenerationStatus::Queued->value, $generation->status->value);
    }

    public function test_out_of_order_webhook_does_not_regress_terminal_state(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Succeeded);

        // Try to deliver a "processing" event after already succeeded
        $response = $this->postWebhook('evt_late', 'generation.processing', $generation, [
            'status' => 'processing',
        ]);

        $response->assertOk();
        $generation->refresh();
        $this->assertSame(GenerationStatus::Succeeded->value, $generation->status->value);
    }

    public function test_webhook_cost_calculation_uses_usage_times_rates(): void
    {
        $generation = $this->createGenerationInStatus(GenerationStatus::Queued);

        $this->postWebhook('evt_cost', 'generation.succeeded', $generation, [
            'status'   => 'succeeded',
            'image_url'=> 'https://cdn.pixelforge.fake/images/x.png',
            'usage'    => [
                'input_text_tokens'  => 1000,
                'input_image_tokens' => 0,
                'output_tokens'      => 2500,
                'reasoning_tokens'   => 0,
            ],
            'rates' => [
                'input_text'  => '5.000000',
                'input_image' => '10.000000',
                'output'      => '40.000000',
                'reasoning'   => '12.500000',
            ],
        ]);

        $generation->refresh();

        // (1000 × 5/1M) + (2500 × 40/1M) = 0.005000 + 0.100000 = 0.105000
        $this->assertEqualsWithDelta(0.105, (float) $generation->actual_provider_cost, 0.001);
        // User = 0.105000 × 1.25 = 0.131250
        $this->assertEqualsWithDelta(0.131250, (float) $generation->actual_user_cost, 0.001);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function createGenerationInStatus(GenerationStatus $status): Generation
    {
        $user = User::factory()->create();
        // Ensure the user has a credit record so the completion listener can deduct
        Credit::create(['user_id' => $user->id, 'balance' => '10.000000']);

        return Generation::factory()->create([
            'user_id'                => $user->id,
            'status'                 => $status->value,
            'provider_generation_id' => 'gen_test_' . uniqid(),
            'estimated_provider_cost'=> '0.065000',
            'estimated_user_cost'    => '0.081250',
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function postWebhook(
        string $eventId,
        string $eventType,
        Generation $generation,
        array $overrides = [],
    ): \Illuminate\Testing\TestResponse {
        $generationData = array_merge([
            'id'                     => $generation->provider_generation_id,
            'status'                 => 'succeeded',
            'image_url'              => null,
            'image_url_expires_at'   => null,
            'usage'                  => null,
            'rates'                  => null,
            'error'                  => null,
        ], $overrides);

        $body      = json_encode(['type' => $eventType, 'data' => ['object' => $generationData]]);
        $timestamp = time();
        $hmac      = hash_hmac('sha256', "{$timestamp}.{$body}", self::WEBHOOK_SECRET);
        $signature = "t={$timestamp},v1={$hmac}";

        return $this->call(
            'POST',
            route('webhooks.pixelforge'),
            [], [], [],
            [
                'CONTENT_TYPE'               => 'application/json',
                'HTTP_PIXELFORGE_SIGNATURE'  => $signature,
                'HTTP_PIXELFORGE_EVENT_ID'   => $eventId,
                'HTTP_PIXELFORGE_EVENT_TYPE' => $eventType,
            ],
            $body,
        );
    }
}
