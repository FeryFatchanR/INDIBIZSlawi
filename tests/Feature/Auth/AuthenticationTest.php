<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Support\Roles;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
    }

    public function test_login_page_is_available_and_guest_is_redirected(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/leads')->assertRedirect('/admin/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->withRole(Roles::ADMINISTRATOR)->create(['password' => 'rahasia-sekali-123']);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'rahasia-sekali-123')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->withRole(Roles::ADMINISTRATOR)->create(['password' => 'rahasia-sekali-123']);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'salah')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }

    public function test_logout(): void
    {
        $this->actingAsRole(Roles::ADMINISTRATOR);

        $this->post(route('filament.admin.auth.logout'))->assertRedirect();
        $this->assertGuest();
    }

    public function test_user_without_role_or_inactive_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/admin')->assertForbidden();

        $this->actingAs(User::factory()->inactive()->withRole(Roles::SUPER_ADMIN)->create());
        $this->get('/admin')->assertForbidden();
    }

    public function test_role_permission_matrix_on_admin_pages(): void
    {
        $matrix = [
            Roles::SUPER_ADMIN => ['/admin/users' => 200, '/admin/roles' => 200, '/admin/website-settings' => 200, '/admin/pages' => 200, '/admin/leads' => 200, '/admin/activity-logs' => 200],
            Roles::ADMINISTRATOR => ['/admin/users' => 403, '/admin/roles' => 403, '/admin/website-settings' => 403, '/admin/pages' => 200, '/admin/products' => 200, '/admin/leads' => 200, '/admin/media-assets' => 200],
            Roles::MARKETING => ['/admin/website-settings' => 403, '/admin/users' => 403, '/admin/campaigns' => 200, '/admin/banners' => 200, '/admin/testimonials' => 200, '/admin/leads' => 200, '/admin/pages/create' => 403, '/admin/conversion-report' => 403],
            Roles::SALES => ['/admin/leads' => 200, '/admin/pages' => 403, '/admin/products' => 403, '/admin/banners' => 403, '/admin/media-assets' => 403, '/admin/website-settings' => 403],
        ];

        foreach ($matrix as $role => $urls) {
            $this->actingAsRole($role);
            foreach ($urls as $url => $status) {
                $this->get($url)->assertStatus($status);
            }
        }
    }
}
