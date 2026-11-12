<?php

namespace Tests;

use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Seed role & permission (dipanggil test yang memakai RefreshDatabase). */
    protected function seedRoles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function userWithRole(string $role, array $attributes = []): User
    {
        return User::factory()->withRole($role)->create($attributes);
    }

    protected function actingAsRole(string $role): User
    {
        $user = $this->userWithRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    protected function superAdmin(): User
    {
        return $this->actingAsRole(Roles::SUPER_ADMIN);
    }
}
