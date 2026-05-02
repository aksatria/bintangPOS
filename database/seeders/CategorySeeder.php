<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Minuman', 'description' => 'Air mineral, teh, kopi, susu, minuman ringan'],
            ['name' => 'Makanan Ringan', 'description' => 'Snack kemasan, keripik, biskuit, wafer'],
            ['name' => 'Sembako', 'description' => 'Beras, gula, minyak, tepung, bahan pokok'],
            ['name' => 'Makanan Instan', 'description' => 'Mie instan, bubur instan, makanan cepat saji'],
            ['name' => 'Bumbu Dapur', 'description' => 'Bumbu racik, saus, kecap, sambal'],
            ['name' => 'Roti & Kue', 'description' => 'Roti kemasan, bolu, cake kecil'],
            ['name' => 'Frozen Food', 'description' => 'Sosis, nugget, bakso, makanan beku'],
            ['name' => 'Produk Susu', 'description' => 'Susu UHT, yoghurt, keju, krimer'],
            ['name' => 'Perawatan Tubuh', 'description' => 'Sabun, sampo, pasta gigi, deodorant'],
            ['name' => 'Kebersihan Rumah', 'description' => 'Sabun cuci, pewangi, pembersih lantai'],
            ['name' => 'Perlengkapan Bayi', 'description' => 'Popok, tisu basah, minyak telon'],
            ['name' => 'ATK', 'description' => 'Pulpen, buku tulis, perlengkapan sekolah/kantor'],
            ['name' => 'Obat Ringan', 'description' => 'Vitamin, obat demam, obat batuk ringan'],
            ['name' => 'Buah Segar', 'description' => 'Buah harian dalam kemasan'],
            ['name' => 'Lainnya', 'description' => 'Produk umum di luar kategori utama'],
        ];

        foreach ($categories as $category) {
            Category::query()->updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
