<?php

declare(strict_types=1);

namespace App\Domain\Generation\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GenerationWebhookEvent extends Model
{
    protected $fillable = [
        'provider_event_id',
        'generation_id',
        'event_type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload'      => 'array',
        'processed_at' => 'datetime',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }

    public function isProcessed(): bool
    {
        return $this->processed_at !== null;
    }

    public function markProcessed(): void
    {
        $this->processed_at = now();
    }
}
