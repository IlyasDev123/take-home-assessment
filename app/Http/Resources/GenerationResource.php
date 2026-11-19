<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class GenerationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id'                      => $this->id,
            'prompt'                  => $this->prompt,
            'size'                    => [
                'value' => $this->size->value,
                'label' => $this->size->label(),
            ],
            'quality'                 => [
                'value' => $this->quality->value,
                'label' => $this->quality->label(),
            ],
            'reference_images'        => $this->reference_images ?? [],
            'status'                  => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'estimated_provider_cost' => $this->estimated_provider_cost,
            'actual_provider_cost'    => $this->actual_provider_cost,
            'estimated_user_cost'     => $this->estimated_user_cost,
            'actual_user_cost'        => $this->actual_user_cost,
            'image_url'               => $this->image_url,
            'failure_reason'          => $this->failure_reason,
            'completed_at'            => $this->completed_at?->toIso8601String(),
            'created_at'              => $this->created_at->toIso8601String(),
            'updated_at'              => $this->updated_at->toIso8601String(),
        ];
    }
}
