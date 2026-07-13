<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@kdanalytiks.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('Kenya@254'),
                'role' => \App\Enums\UserRole::Admin,
                'status' => \App\Enums\UserStatus::Active,
            ]
        );

        $this->call(KenyanDataSeeder::class);
    }
}
