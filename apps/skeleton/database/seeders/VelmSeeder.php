<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Velm\Framework\Auth\UserProvisioner;
use Velm\Framework\VelmManager;

final class VelmSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) config('velm.bootstrap_admin.email', 'admin@velm.test');
        $password = (string) config('velm.bootstrap_admin.password', 'password');

        User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Velm Admin',
                'password' => Hash::make($password),
            ],
        );

        if (app()->bound(VelmManager::class)) {
            UserProvisioner::bootstrapAdminProfile(
                app(VelmManager::class)->environment(),
                $email,
            );
        }
    }
}
