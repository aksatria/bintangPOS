<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Aksatria Arya', 'phone' => '081234567801', 'email' => 'aksatria@example.com', 'address' => 'Jakarta Selatan', 'is_active' => true],
            ['name' => 'Siti Rahma', 'phone' => '081234567802', 'email' => 'siti.rahma@example.com', 'address' => 'Bandung', 'is_active' => true],
            ['name' => 'Budi Santoso', 'phone' => '081234567803', 'email' => 'budi.santoso@example.com', 'address' => 'Surabaya', 'is_active' => true],
            ['name' => 'Dewi Lestari', 'phone' => '081234567804', 'email' => 'dewi.lestari@example.com', 'address' => 'Yogyakarta', 'is_active' => true],
            ['name' => 'Rizky Pratama', 'phone' => '081234567805', 'email' => 'rizky.pratama@example.com', 'address' => 'Semarang', 'is_active' => true],
            ['name' => 'Maya Putri', 'phone' => '081234567806', 'email' => 'maya.putri@example.com', 'address' => 'Bogor', 'is_active' => true],
            ['name' => 'Andi Wijaya', 'phone' => '081234567807', 'email' => 'andi.wijaya@example.com', 'address' => 'Depok', 'is_active' => true],
            ['name' => 'Nadia Anjani', 'phone' => '081234567808', 'email' => 'nadia.anjani@example.com', 'address' => 'Bekasi', 'is_active' => true],
            ['name' => 'Fajar Nugroho', 'phone' => '081234567809', 'email' => 'fajar.nugroho@example.com', 'address' => 'Tangerang', 'is_active' => true],
            ['name' => 'Lina Marlina', 'phone' => '081234567810', 'email' => 'lina.marlina@example.com', 'address' => 'Malang', 'is_active' => true],
        ];

        foreach ($rows as $row) {
            Customer::query()->updateOrCreate(
                ['phone' => $row['phone']],
                [
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'address' => $row['address'],
                    'is_active' => $row['is_active'],
                ]
            );
        }
    }
}
