<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DefaultUserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['name' => 'Web Demo User', 'email' => 'web@example.com', 'password' => 'password123'],
            ['name' => 'API Demo User', 'email' => 'api@example.com', 'password' => 'password123'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                ['name' => $account['name'], 'password' => Hash::make($account['password'])],
            );
        }
    }
}