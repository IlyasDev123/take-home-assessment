<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Billing\Models\Credit;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\GenerationRequest;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_generation(): void
    {
        $this->mockPixelforgeProvider();

        $user = User::factory()->create();
        $this->giveUserCredits($user, '10.000000');

        $response = $this->actingAs($user)->post(route('generations.store'), [
            'prompt'  => 'A beautiful sunset over mountains',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('generations', [
            'user_id' => $user->id,
            'prompt'  => 'A beautiful sunset over mountains',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);
    }

    public function test_generation_stores_estimated_cost(): void
    {
        $this->mockPixelforgeProvider();

        $user = User::factory()->create();
        $this->giveUserCredits($user, '10.000000');

        $this->actingAs($user)->post(route('generations.store'), [
            'prompt'  => 'Test prompt',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        $generation = Generation::where('user_id', $user->id)->first();

        $this->assertNotNull($generation);
        $this->assertGreaterThan(0, (float) $generation->estimated_provider_cost);
        $this->assertGreaterThan(0, (float) $generation->estimated_user_cost);
        // User cost must be greater than provider cost (25% markup)
        $this->assertGreaterThan(
            (float) $generation->estimated_provider_cost,
            (float) $generation->estimated_user_cost,
        );
    }

    public function test_unauthenticated_user_cannot_create_generation(): void
    {
        $response = $this->post(route('generations.store'), [
            'prompt'  => 'Test prompt',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_user_cannot_view_another_users_generation(): void
    {
        $ownerUser  = User::factory()->create();
        $attackUser = User::factory()->create();

        $generation = Generation::factory()->create([
            'user_id' => $ownerUser->id,
            'status'  => GenerationStatus::Succeeded->value,
        ]);

        $response = $this->actingAs($attackUser)
            ->get(route('generations.show', $generation));

        $response->assertForbidden();
    }

    public function test_generation_validation_requires_prompt(): void
    {
        $user = User::factory()->create();
        $this->giveUserCredits($user, '10.000000');

        $response = $this->actingAs($user)->post(route('generations.store'), [
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        $response->assertSessionHasErrors(['prompt']);
    }

    public function test_generation_validation_requires_valid_size(): void
    {
        $user = User::factory()->create();
        $this->giveUserCredits($user, '10.000000');

        $response = $this->actingAs($user)->post(route('generations.store'), [
            'prompt'  => 'Test',
            'size'    => 'invalid-size',
            'quality' => 'medium',
        ]);

        $response->assertSessionHasErrors(['size']);
    }

    private function giveUserCredits(User $user, string $balance): void
    {
        Credit::create(['user_id' => $user->id, 'balance' => $balance]);
    }

    private function mockPixelforgeProvider(): void
    {
        $mock = $this->createMock(ImageGenerationProviderInterface::class);

        $mock->method('generate')->willReturn(
            new GenerationResponse(
                providerGenerationId: 'pf_' . uniqid(),
                status:               'queued',
            )
        );

        $mock->method('getGeneration')->willReturn(
            new GenerationStatusResponse(
                providerGenerationId: 'pf_123',
                status:               'processing',
            )
        );

        $this->app->instance(ImageGenerationProviderInterface::class, $mock);
    }
}
