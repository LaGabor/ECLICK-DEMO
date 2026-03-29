<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $password = (string) env('SEED_DEMO_PASSWORD', 'admin123');
        $hashed = Hash::make($password);

        Role::findOrCreate(UserRole::Admin->value, 'web');
        Role::findOrCreate(UserRole::User->value, 'web');

        $adminEmail = (string) env('SEED_ADMIN_EMAIL', 'admin@example.com');
        if (! User::query()->where('email', $adminEmail)->exists()) {
            $admin = User::query()->create([
                'name' => 'Admin',
                'email' => $adminEmail,
                'phone' => null,
                'bank_account' => null,
                'password' => $hashed,
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),
            ]);
            $admin->assignRole(UserRole::Admin);
        }

        $userEmail = (string) env('SEED_USER_EMAIL', 'user@example.com');
        if (! User::query()->where('email', $userEmail)->exists()) {
            $user = User::query()->create([
                'name' => 'Demo User',
                'email' => $userEmail,
                'phone' => '+36209876543',
                'bank_account' => '11773090987654321012345678',
                'password' => $hashed,
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),
            ]);
            $user->assignRole(UserRole::User);
        }
    }
}
