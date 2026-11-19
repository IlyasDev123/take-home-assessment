<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domain\Billing\Models\Credit;
use App\Domain\Billing\Services\CreditService;
use App\Domain\Billing\ValueObjects\Money;
use App\Domain\Generation\Enums\GenerationStatus;
use App\Domain\Generation\Models\Generation;
use App\Infrastructure\Pixelforge\Contracts\ImageGenerationProviderInterface;
use App\Infrastructure\Pixelforge\DTOs\GenerationResponse;
use App\Infrastructure\Pixelforge\DTOs\GenerationStatusResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_buy_credits(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id, 'balance' => '0.000000']);

        $response = $this->actingAs($user)->post(route('credits.purchase'), ['amount' => '10']);

        $response->assertRedirect(route('credits.index'));

        $this->assertDatabaseHas('credits', [
            'user_id' => $user->id,
            'balance' => '10.000000',
        ]);

        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $user->id,
            'type'    => 'credit',
        ]);
    }

    public function test_generation_blocked_when_insufficient_credits(): void
    {
        $mock = $this->createMock(ImageGenerationProviderInterface::class);
        $mock->method('generate')->willReturn(new GenerationResponse(providerGenerationId: 'pf_x', status: 'queued'));
        $mock->method('getGeneration')->willReturn(new GenerationStatusResponse(providerGenerationId: 'pf_x', status: 'processing'));
        $this->app->instance(ImageGenerationProviderInterface::class, $mock);

        $user = User::factory()->create();
        // Give user $0 balance
        Credit::create(['user_id' => $user->id, 'balance' => '0.000000']);

        $response = $this->actingAs($user)->post(route('generations.store'), [
            'prompt'  => 'Test prompt',
            'size'    => '1024x1024',
            'quality' => 'medium',
        ]);

        // Should redirect back with error, not create a generation
        $response->assertRedirect(route('generations.create'));
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('generations', ['user_id' => $user->id]);
    }

    public function test_credits_deducted_after_generation_succeeds(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id, 'balance' => '10.000000']);

        $generation = Generation::factory()->create([
            'user_id'          => $user->id,
            'status'           => GenerationStatus::Succeeded->value,
            'actual_user_cost' => '0.030625',
        ]);

        $service = $this->app->make(CreditService::class);
        $service->deductForGeneration($user, Money::fromDecimalString('0.030625'), $generation);

        $credit = Credit::where('user_id', $user->id)->first();
        $this->assertEqualsWithDelta(10.0 - 0.030625, (float) $credit->balance, 0.000001);
    }

    public function test_credits_page_shows_balance(): void
    {
        $user = User::factory()->create();
        Credit::create(['user_id' => $user->id, 'balance' => '5.250000']);

        $response = $this->actingAs($user)->get(route('credits.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Credits/Index')
            ->where('balance', '5.250000')
        );
    }
}
