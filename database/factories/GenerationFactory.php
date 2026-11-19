<?php

namespace Database\Factories;

use App\Domain\Generation\Models\Generation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Generation>
 */
class GenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Generation::class;

    public function definition(): array
    {
        return [
            'user_id'                 => \App\Models\User::factory(),
            'provider_generation_id'  => 'gen_' . $this->faker->uuid(),
            'prompt'                  => $this->faker->sentence(8),
            'size'                    => '1024x1024',
            'quality'                 => 'medium',
            'reference_images'        => [],
            'status'                  => 'pending',
            'estimated_provider_cost' => '0.020000',
            'actual_provider_cost'    => null,
            'estimated_user_cost'     => '0.025000',
            'actual_user_cost'        => null,
            'image_url'               => null,
            'failure_reason'          => null,
            'completed_at'            => null,
        ];
    }

    public function succeeded(): self
    {
        return $this->state([
            'status'               => 'succeeded',
            'actual_provider_cost' => '0.024500',
            'actual_user_cost'     => '0.030625',
            'image_url'            => 'https://cdn.pixelforge.fake/images/' . $this->faker->uuid() . '.png',
            'completed_at'         => now(),
        ]);
    }

    public function failed(): self
    {
        return $this->state([
            'status'               => 'failed',
            'actual_provider_cost' => '0.000000',
            'actual_user_cost'     => '0.000000',
            'failure_reason'       => 'Provider error',
            'completed_at'         => now(),
        ]);
    }
}
