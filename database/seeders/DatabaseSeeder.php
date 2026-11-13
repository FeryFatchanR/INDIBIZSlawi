<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder tidak membuat akun dengan password bawaan. Buat Super Admin dengan:
     * php artisan app:create-super-admin
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            ContentSeeder::class,
        ]);
    }
}
