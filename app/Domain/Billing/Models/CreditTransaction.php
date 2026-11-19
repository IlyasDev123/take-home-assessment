<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Domain\Generation\Models\Generation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'generation_id',
        'type',
        'amount',
        'description',
        'balance_after',
    ];

    protected $casts = [
        'amount'        => 'string',
        'balance_after' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generation(): BelongsTo
    {
        return $this->belongsTo(Generation::class);
    }
}
