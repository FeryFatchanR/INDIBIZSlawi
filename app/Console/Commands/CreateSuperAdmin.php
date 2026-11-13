<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Roles;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-super-admin {--email= : Email} {--name= : Nama}')]
#[Description('Buat akun Super Admin baru atau jadikan user yang ada sebagai Super Admin')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        if (! Role::query()->where('name', Roles::SUPER_ADMIN)->exists()) {
            $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        }

        $email = $this->option('email') ?: text('Email', required: true);
        if (Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->fails()) {
            $this->error('Format email tidak valid.');

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $name = $this->option('name') ?: text('Nama', required: true);
            $plain = password('Password (min. 12 karakter)', required: true,
                validate: fn (string $v) => strlen($v) < 12 ? 'Minimal 12 karakter.' : null);

            $user = new User(['name' => $name, 'email' => $email, 'password' => $plain]);
            $user->forceFill(['email_verified_at' => now(), 'is_active' => true])->save();
        } else {
            $user->forceFill(['is_active' => true])->save();
        }

        $user->assignRole(Roles::SUPER_ADMIN);
        $this->info("Super Admin {$email} siap. Login di ".url('/admin'));

        return self::SUCCESS;
    }
}
