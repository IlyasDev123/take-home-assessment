<?php

declare(strict_types=1);

namespace App\Domain\Generation\Models;

use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use App\Domain\Generation\Exceptions\InvalidStatusTransitionException;
use App\Models\User;
use Database\Factories\GenerationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Generation extends Model
{
    /** @use HasFactory<GenerationFactory> */
    use HasFactory;

    protected static function newFactory(): GenerationFactory
    {
        return GenerationFactory::new();
    }

    protected $fillable = [
        'user_id',
        'provider_generation_id',
        'prompt',
        'size',
        'quality',
        'reference_images',
        'status',
        'estimated_provider_cost',
        'actual_provider_cost',
        'estimated_user_cost',
        'actual_user_cost',
        'image_url',
        'image_url_expires_at',
        'failure_reason',
        'error_code',
        'completed_at',
    ];

    protected $casts = [
        'size'                    => ImageSize::class,
        'quality'                 => ImageQuality::class,
        'status'                  => GenerationStatus::class,
        'reference_images'        => 'array',
        'estimated_provider_cost' => 'string',
        'actual_provider_cost'    => 'string',
        'estimated_user_cost'     => 'string',
        'actual_user_cost'        => 'string',
        'image_url_expires_at'    => 'datetime',
        'completed_at'            => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function webhookEvents(): HasMany
    {
        return $this->hasMany(GenerationWebhookEvent::class);
    }

    public function transitionTo(GenerationStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw InvalidStatusTransitionException::from($this->status, $newStatus);
        }

        $this->status = $newStatus;
    }

    public function isInTerminalState(): bool
    {
        return $this->status->isTerminal();
    }

    public function isPending(): bool
    {
        return $this->status === GenerationStatus::Pending;
    }

    public function needsPolling(): bool
    {
        return in_array($this->status, [
            GenerationStatus::Queued,
            GenerationStatus::Processing,
        ], true);
    }
}
