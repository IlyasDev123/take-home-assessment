<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\WebhookPayload;
use App\Infrastructure\Pixelforge\Webhooks\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class WebhookController extends Controller
{
    public function __construct(
        private readonly ImageGenerationProviderInterface $provider,
        private readonly WebhookProcessor $processor,
    ) {}

    public function pixelforge(Request $request): Response
    {
        $rawPayload       = $request->getContent();
        $signatureHeader  = $request->header('Pixelforge-Signature', '');
        $eventId          = $request->header('Pixelforge-Event-Id', '');
        $eventType        = $request->header('Pixelforge-Event-Type', '');

        if (!$this->provider->verifyWebhookSignature($rawPayload, $signatureHeader)) {
            Log::warning('Pixelforge webhook signature verification failed.', [
                'ip'      => $request->ip(),
                'eventId' => $eventId,
            ]);
            return response('Unauthorized', 401);
        }

        if (empty($eventId)) {
            return response('Bad Request: missing Pixelforge-Event-Id', 400);
        }

        $data = $request->json()->all();

        try {
            // Handle ping events gracefully (no generation object)
            if ($eventType === 'ping' || ($data['type'] ?? '') === 'ping') {
                Log::info('Pixelforge ping event received.');
                return response('OK', 200);
            }

            $payload = WebhookPayload::fromRequest($eventId, $eventType ?: ($data['type'] ?? ''), $data);
            $this->processor->process($payload);
        } catch (\Throwable $e) {
            Log::error('Pixelforge webhook processing error.', [
                'error'    => $e->getMessage(),
                'eventId'  => $eventId,
                'eventType'=> $eventType,
            ]);

            // Return 200 for permanently unprocessable events (bad payload shape)
            // so Pixelforge stops retrying something we can never handle.
            return response('Accepted', 200);
        }

        return response('OK', 200);
    }
}
