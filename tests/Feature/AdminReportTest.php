<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get(route('admin.report'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Report')
            ->has('daily')
            ->has('users')
            ->has('totals')
        );
    }

    public function test_non_admin_cannot_view_report(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get(route('admin.report'));

        $response->assertForbidden();
    }

    public function test_guest_cannot_view_report(): void
    {
        $response = $this->get(route('admin.report'));

        $response->assertRedirect(route('login'));
    }
}
