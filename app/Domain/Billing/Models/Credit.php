<?php

declare(strict_types=1);

namespace App\Domain\Billing\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    protected $fillable = ['user_id', 'balance'];

    protected $casts = [
        'balance' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
