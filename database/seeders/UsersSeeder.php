<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UsersSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@admin.com',
                'role' => 'admin',
                'password' => '12345',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'ana@gmail.com'],
            [
                'name' => 'Ana',
                'email' => 'ana@gmail.com',
                'role' => 'client',
                'password' => '12345',
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'daniel@daniel.com'],
            [
                'name' => 'Daniel',
                'email' => 'daniel@daniel.com',
                'role' => 'client',
                'password' => '12345',
            ]
        );
    }
}

