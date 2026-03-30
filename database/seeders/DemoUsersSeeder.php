<?php

declare(strict_types=1);

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
        $password = (string) env('SEED_DEMO_PASSWORD', 'admin123!');
        $hashed = Hash::make($password);

        Role::findOrCreate(UserRole::Admin->value, 'web');
        Role::findOrCreate(UserRole::User->value, 'web');

        $adminEmail = (string) env('SEED_ADMIN_EMAIL', 'admin@test.com');
        if (! User::query()->where('email', $adminEmail)->exists()) {
            $admin = User::query()->create([
                'name' => 'Demo Administrator',
                'email' => $adminEmail,
                'phone' => '+36201110001',
                'bank_account' => '117730909900000011110001',
                'password' => $hashed,
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),
            ]);
            $admin->assignRole(UserRole::Admin);
        } else {
            User::query()->where('email', $adminEmail)->update([
                'name' => 'Demo Administrator',
                'phone' => '+36201110001',
                'bank_account' => '117730909900000011110001',
            ]);
        }

        $userEmail = (string) env('SEED_USER_EMAIL', 'user@test.com');
        if (! User::query()->where('email', $userEmail)->exists()) {
            $user = User::query()->create([
                'name' => 'User Demo',
                'email' => $userEmail,
                'phone' => '+36202220002',
                'bank_account' => '117730909900000022220002',
                'password' => $hashed,
                'terms_accepted_at' => now(),
                'email_verified_at' => now(),
            ]);
            $user->assignRole(UserRole::User);
        } else {
            User::query()->where('email', $userEmail)->update([
                'name' => 'Morgan Demo Participant',
                'phone' => '+36202220002',
                'bank_account' => '117730909900000022220002',
            ]);
        }
    }
}
