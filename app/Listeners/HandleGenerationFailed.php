<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Generation\Events\GenerationFailed;
use Illuminate\Support\Facades\Log;

final class HandleGenerationFailed
{
    public function handle(GenerationFailed $event): void
    {
        Log::warning('Generation failed.', [
            'generation_id' => $event->generation->id,
            'reason'        => $event->reason,
        ]);
    }
}
