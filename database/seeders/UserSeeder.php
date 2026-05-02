<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultBranchId = Branch::query()->firstOrCreate(
            ['code' => 'PUSAT'],
            ['name' => 'Pusat', 'is_active' => true]
        )->id;

        $baseUsers = [
            ['name' => 'Bintang Pratama', 'email' => 'owner@bintang.test', 'role' => UserRole::Owner->value],
            ['name' => 'Raka Wijaya', 'email' => 'admin@bintang.test', 'role' => UserRole::Admin->value],
            ['name' => 'Nadia Putri', 'email' => 'kasir@bintang.test', 'role' => UserRole::Cashier->value],
        ];

        foreach ($baseUsers as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'branch_id' => $defaultBranchId,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $faker = fake('id_ID');

        for ($i = 1; $i <= 4; $i++) {
            User::query()->updateOrCreate(
                ['email' => "admin{$i}@bintang.test"],
                [
                    'name' => $faker->firstName().' '.$faker->lastName(),
                    'role' => UserRole::Admin->value,
                    'branch_id' => $defaultBranchId,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        for ($i = 1; $i <= 14; $i++) {
            User::query()->updateOrCreate(
                ['email' => "kasir{$i}@bintang.test"],
                [
                    'name' => $faker->firstName().' '.$faker->lastName(),
                    'role' => UserRole::Cashier->value,
                    'branch_id' => $defaultBranchId,
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
