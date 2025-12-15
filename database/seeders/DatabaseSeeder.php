<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
{
    // Admin
    User::updateOrCreate(
        ['email' => 'admin@example.com'],
        [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]
    );

    // User biasa
    User::updateOrCreate(
        ['email' => 'user@example.com'],
        [
            'name' => 'User Biasa',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]
    );
}
}
