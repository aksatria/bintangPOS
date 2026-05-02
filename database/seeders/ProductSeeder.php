<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('id_ID');
        $categories = Category::query()->orderBy('id')->get();

        if ($categories->isEmpty()) {
            return;
        }

        $units = ['pcs', 'pack', 'box', 'botol', 'sachet', 'kaleng'];

        // Generate banyak produk agar semua tabel/listing terisi penuh.
        for ($i = 1; $i <= 220; $i++) {
            $category = $categories->random();
            $categoryCode = Str::upper(Str::substr(Str::slug($category->name, ''), 0, 3));
            $sku = sprintf('%s-%04d', $categoryCode ?: 'PRD', $i);

            $purchasePrice = $faker->numberBetween(1500, 85000);
            $sellingPrice = (int) round($purchasePrice * $faker->randomFloat(2, 1.1, 1.55));
            $threshold = $faker->numberBetween(4, 18);

            // 20% produk low-stock untuk widget/alert dashboard.
            $isLow = $i % 5 === 0;
            $stock = $isLow
                ? $faker->numberBetween(0, max($threshold, 1))
                : $faker->numberBetween($threshold + 3, $threshold + 140);

            Product::query()->updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $category->id,
                    'name' => $this->makeProductName($faker, $category->name, $i),
                    'barcode' => '899'.str_pad((string) (100000000 + $i), 9, '0', STR_PAD_LEFT),
                    'purchase_price' => $purchasePrice,
                    'selling_price' => max($sellingPrice, $purchasePrice + 300),
                    'stock' => $stock,
                    'low_stock_threshold' => $threshold,
                    'unit' => $units[array_rand($units)],
                    'is_active' => true,
                ]
            );
        }
    }

    private function makeProductName($faker, string $categoryName, int $index): string
    {
        $labels = [
            'Premium', 'Ekonomis', 'Family Pack', 'Value Pack', 'Original',
            'Fresh', 'Classic', 'Special', 'Harian', 'Pilihan',
        ];

        return trim(sprintf(
            '%s %s %s %d',
            Str::title($faker->words(2, true)),
            Str::title($categoryName),
            $labels[array_rand($labels)],
            $index
        ));
    }
}
