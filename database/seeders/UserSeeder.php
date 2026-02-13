<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Executa o processo configurado.
     */
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'password' => 'password',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'user@example.com',
        ], [
            'name' => 'Regular User',
            'password' => 'password',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }
}
