<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StockTransferDemoSeeder extends Seeder
{
    public function run(): void
    {
        $pusat = Branch::query()->updateOrCreate(
            ['code' => 'PUSAT'],
            ['name' => 'Pusat', 'is_active' => true]
        );

        $surabaya = Branch::query()->updateOrCreate(
            ['code' => 'SBY'],
            ['name' => 'Surabaya', 'is_active' => true]
        );

        $requester = User::query()->updateOrCreate(
            ['email' => 'admin.pusat.demo@local.test'],
            [
                'name' => 'Admin Pusat Demo',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'branch_id' => $pusat->id,
            ]
        );

        $receiver = User::query()->updateOrCreate(
            ['email' => 'admin.sby.demo@local.test'],
            [
                'name' => 'Admin Surabaya Demo',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'branch_id' => $surabaya->id,
            ]
        );

        $category = Category::query()->updateOrCreate(
            ['name' => 'Mutasi Demo'],
            ['description' => 'Kategori produk demo mutasi antar cabang', 'is_active' => true]
        );

        $transferRows = [
            ['sku' => 'AMX-500', 'name' => 'Amoxicillin 500mg', 'unit' => 'strip', 'qty' => 30, 'pusat_stock_after' => 70],
            ['sku' => 'PCM-500', 'name' => 'Paracetamol 500mg', 'unit' => 'strip', 'qty' => 50, 'pusat_stock_after' => 120],
            ['sku' => 'VIT-C1K', 'name' => 'Vitamin C 1000', 'unit' => 'botol', 'qty' => 20, 'pusat_stock_after' => 40],
        ];

        $transfer = StockTransfer::query()->updateOrCreate(
            ['code' => 'TRF-DEMO-PUSAT-SBY-001'],
            [
                'source_branch_id' => $pusat->id,
                'destination_branch_id' => $surabaya->id,
                'requested_by' => $requester->id,
                'approved_by' => $receiver->id,
                'received_by' => $receiver->id,
                'status' => StockTransfer::STATUS_RECEIVED,
                'note' => 'Demo mutasi stok dari pusat ke Surabaya.',
                'delivery_ref' => 'SJ-DEMO-001',
                'courier_name' => 'Kurir Internal',
                'approved_at' => now()->subHours(2),
                'received_at' => now()->subHour(),
            ]
        );

        StockTransferItem::query()->where('stock_transfer_id', $transfer->id)->delete();

        foreach ($transferRows as $row) {
            $sourceProduct = Product::query()->updateOrCreate(
                ['branch_id' => $pusat->id, 'sku' => $row['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $row['name'],
                    'barcode' => null,
                    'purchase_price' => 10000,
                    'selling_price' => 13000,
                    'stock' => $row['pusat_stock_after'],
                    'low_stock_threshold' => 10,
                    'unit' => $row['unit'],
                    'is_active' => true,
                ]
            );

            Product::query()->updateOrCreate(
                ['branch_id' => $surabaya->id, 'name' => $row['name']],
                [
                    'category_id' => $category->id,
                    'sku' => $row['sku'] . '-B' . $surabaya->id,
                    'barcode' => null,
                    'purchase_price' => 10000,
                    'selling_price' => 13000,
                    'stock' => $row['qty'],
                    'low_stock_threshold' => 10,
                    'unit' => $row['unit'],
                    'is_active' => true,
                ]
            );

            StockTransferItem::query()->create([
                'stock_transfer_id' => $transfer->id,
                'source_product_id' => $sourceProduct->id,
                'sku' => $row['sku'],
                'product_name' => $row['name'],
                'unit' => $row['unit'],
                'requested_qty' => $row['qty'],
                'received_qty' => $row['qty'],
            ]);
        }
    }
}

