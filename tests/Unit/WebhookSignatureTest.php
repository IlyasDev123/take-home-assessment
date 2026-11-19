<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Infrastructure\Pixelforge\Client\PixelforgeClient;
use Tests\TestCase;

class WebhookSignatureTest extends TestCase
{
    private PixelforgeClient $client;
    private const SECRET = 'super-secret-webhook-key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new PixelforgeClient(
            apiKey:  'test-key',
            baseUrl: 'https://pixelforge.promptedit.dev/v1',
        );
    }

    public function test_valid_signature_passes(): void
    {
        $payload   = '{"type":"generation.succeeded","data":{"object":{"id":"gen_123"}}}';
        $timestamp = time();
        $hmac      = hash_hmac('sha256', "{$timestamp}.{$payload}", self::SECRET);
        $header    = "t={$timestamp},v1={$hmac}";

        $this->assertTrue(
            $this->client->verifyWebhookSignature($payload, $header, self::SECRET)
        );
    }

    public function test_invalid_hmac_fails(): void
    {
        $payload   = '{"type":"generation.succeeded"}';
        $timestamp = time();
        $header    = "t={$timestamp},v1=bad_signature_value";

        $this->assertFalse(
            $this->client->verifyWebhookSignature($payload, $header, self::SECRET)
        );
    }

    public function test_tampered_payload_fails(): void
    {
        $original  = '{"type":"generation.succeeded","data":{"object":{"status":"succeeded"}}}';
        $tampered  = '{"type":"generation.succeeded","data":{"object":{"status":"failed"}}}';
        $timestamp = time();
        $hmac      = hash_hmac('sha256', "{$timestamp}.{$original}", self::SECRET);
        $header    = "t={$timestamp},v1={$hmac}";

        $this->assertFalse(
            $this->client->verifyWebhookSignature($tampered, $header, self::SECRET)
        );
    }

    public function test_wrong_secret_fails(): void
    {
        $payload   = '{"type":"generation.succeeded"}';
        $timestamp = time();
        $hmac      = hash_hmac('sha256', "{$timestamp}.{$payload}", 'correct-secret');
        $header    = "t={$timestamp},v1={$hmac}";

        $this->assertFalse(
            $this->client->verifyWebhookSignature($payload, $header, 'wrong-secret')
        );
    }

    public function test_expired_timestamp_fails(): void
    {
        $payload   = '{"type":"generation.succeeded"}';
        $oldTs     = time() - 400; // older than 300s threshold
        $hmac      = hash_hmac('sha256', "{$oldTs}.{$payload}", self::SECRET);
        $header    = "t={$oldTs},v1={$hmac}";

        $this->assertFalse(
            $this->client->verifyWebhookSignature($payload, $header, self::SECRET)
        );
    }

    public function test_missing_v1_part_fails(): void
    {
        $this->assertFalse(
            $this->client->verifyWebhookSignature('payload', 'malformed-header', self::SECRET)
        );
    }
}
