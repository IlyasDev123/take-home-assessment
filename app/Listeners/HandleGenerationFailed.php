<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Domain\Generation\Events\GenerationFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

final class HandleGenerationFailed implements ShouldQueue
{
    public function handle(GenerationFailed $event): void
    {
        Log::warning('Generation failed.', [
            'generation_id' => $event->generation->id,
            'reason'        => $event->reason,
        ]);
    }
}
