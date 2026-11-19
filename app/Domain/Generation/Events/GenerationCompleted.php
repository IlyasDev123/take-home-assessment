<?php

declare(strict_types=1);

namespace App\Domain\Generation\Events;

use App\Domain\Generation\Models\Generation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class GenerationCompleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly Generation $generation) {}
}
