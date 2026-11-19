<?php

declare(strict_types=1);

namespace App\Infrastructure\Pixelforge\DTOs;

use InvalidArgumentException;

/**
 * Parsed Pixelforge webhook event.
 *
 * Webhook body shape:
 * {
 *   "type": "generation.succeeded",
 *   "data": {
 *     "object": { ...full generation object... }
 *   }
 * }
 *
 * Event ID and type also arrive as headers:
 *   Pixelforge-Event-Id
 *   Pixelforge-Event-Type
 */
final readonly class WebhookPayload
{
    /**
     * @param array<string,mixed>|null $usage
     * @param array<string,mixed>|null $rates
     */
    public function __construct(
        public string  $eventId,
        public string  $eventType,
        public string  $providerGenerationId,
        public string  $status,
        public ?string $imageUrl          = null,
        public ?string $imageUrlExpiresAt = null,
        public ?array  $usage             = null,
        public ?array  $rates             = null,
        public ?string $failureReason     = null,
        public ?string $errorCode         = null,
    ) {}

    /** @param array<string, mixed> $body */
    public static function fromRequest(string $eventId, string $eventType, array $body): self
    {
        $generation = $body['data']['object'] ?? null;

        if (!is_array($generation)) {
            throw new InvalidArgumentException('Webhook payload missing data.object.');
        }

        $error = $generation['error'] ?? null;

        return new self(
            eventId:              $eventId,
            eventType:            $eventType,
            providerGenerationId: $generation['id'],
            status:               $generation['status'],
            imageUrl:             $generation['image_url'] ?? null,
            imageUrlExpiresAt:    $generation['image_url_expires_at'] ?? null,
            usage:                $generation['usage'] ?? null,
            rates:                $generation['rates'] ?? null,
            failureReason:        is_array($error) ? ($error['message'] ?? null) : null,
            errorCode:            is_array($error) ? ($error['code'] ?? null) : null,
        );
    }
}
