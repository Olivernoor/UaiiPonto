<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Criar usuário admin padrão
        User::firstOrCreate([
            'email' => 'admin@uaiiponto.com',
        ], [
            'name' => 'Administrador',
            'password' => Hash::make('123456'),
            'organization' => 'UaiiPonto Sistemas',
            'role' => 'admin',
        ]);

        // Criar usuário de teste
        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'organization' => 'Test Organization',
            'password' => Hash::make('password'),
        ]);
    }
}
