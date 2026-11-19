<?php

declare(strict_types=1);

use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Models\Generation;
use App\Jobs\PollGenerationStatus;
use Illuminate\Support\Facades\Schedule;

// Process all pending queue jobs — runs on every cron tick.
// On cPanel (no persistent worker) a single cron runs `schedule:run`
// every minute; this command drains the queue within that tick.
Schedule::command('queue:work --stop-when-empty --tries=3 --timeout=60')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

// Poll generations stuck in queued/processing for more than 2 minutes
// (fallback when Pixelforge webhook delivery fails)
Schedule::call(function (): void {
    Generation::whereIn('status', [
        GenerationStatus::Queued->value,
        GenerationStatus::Processing->value,
    ])
        ->where('updated_at', '<', now()->subMinutes(2))
        ->whereNotNull('provider_generation_id')
        ->chunkById(50, function ($generations): void {
            foreach ($generations as $generation) {
                PollGenerationStatus::dispatch($generation);
            }
        });
})->everyMinute()->name('poll-generation-status')->withoutOverlapping();
