<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class VelmSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@velm.test'],
            [
                'name' => 'Velm Admin',
                'password' => Hash::make('password'),
            ],
        );
    }
}
