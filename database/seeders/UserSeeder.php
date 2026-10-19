<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['admin', 'Administrator Bank', 'admin'],
            ['teller1', 'Teller Satu', 'teller'],
            ['teller2', 'Teller Dua', 'teller'],
        ];

        foreach ($users as [$username, $fullName, $role]) {
            $password = Str::random(12);

            $user = User::firstOrCreate(
                ['username' => $username],
                [
                    'password_hash' => Hash::make($password),
                    'full_name' => $fullName,
                    'role' => $role,
                    'status' => 'active',
                ]
            );

            if ($user->wasRecentlyCreated) {
                $this->command->info("User [{$username}] ({$role}) -> password: {$password}");
            }
        }
    }
}
