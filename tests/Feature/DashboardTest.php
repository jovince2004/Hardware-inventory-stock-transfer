<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_are_redirected_to_the_inventory_dashboard()
    {
        $this->actingAs($user = User::factory()->create());

        $this->get('/dashboard')->assertRedirect(route('items.index', absolute: false));
    }

    public function test_authenticated_users_see_the_inventory_dashboard_at_home()
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect(route('dashboard', absolute: false));
    }
}
