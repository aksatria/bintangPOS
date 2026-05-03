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

class StockTransferBatchSeeder extends Seeder
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

        $catalog = [
            ['sku' => 'AMX-500', 'name' => 'Amoxicillin 500mg', 'unit' => 'strip'],
            ['sku' => 'PCM-500', 'name' => 'Paracetamol 500mg', 'unit' => 'strip'],
            ['sku' => 'VIT-C1K', 'name' => 'Vitamin C 1000', 'unit' => 'botol'],
            ['sku' => 'CTZ-10', 'name' => 'Cetirizine 10mg', 'unit' => 'strip'],
            ['sku' => 'ORS-200', 'name' => 'Oralit 200ml', 'unit' => 'sachet'],
            ['sku' => 'ZNC-20', 'name' => 'Zinc 20mg', 'unit' => 'strip'],
        ];

        foreach ($catalog as $item) {
            Product::query()->updateOrCreate(
                ['branch_id' => $pusat->id, 'sku' => $item['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $item['name'],
                    'barcode' => null,
                    'purchase_price' => 10000,
                    'selling_price' => 13000,
                    'stock' => 400,
                    'low_stock_threshold' => 15,
                    'unit' => $item['unit'],
                    'is_active' => true,
                ]
            );
        }

        $statuses = [
            StockTransfer::STATUS_RECEIVED,
            StockTransfer::STATUS_RECEIVED,
            StockTransfer::STATUS_APPROVED,
            StockTransfer::STATUS_REQUESTED,
            StockTransfer::STATUS_REJECTED,
            StockTransfer::STATUS_CANCELLED,
            StockTransfer::STATUS_RECEIVED,
            StockTransfer::STATUS_APPROVED,
            StockTransfer::STATUS_REQUESTED,
            StockTransfer::STATUS_RECEIVED,
        ];

        foreach ($statuses as $idx => $status) {
            $seq = str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT);
            $code = "TRF-DEMO-PUSAT-SBY-{$seq}";

            $transfer = StockTransfer::query()->updateOrCreate(
                ['code' => $code],
                [
                    'source_branch_id' => $pusat->id,
                    'destination_branch_id' => $surabaya->id,
                    'requested_by' => $requester->id,
                    'approved_by' => in_array($status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_RECEIVED, StockTransfer::STATUS_REJECTED], true) ? $receiver->id : null,
                    'received_by' => $status === StockTransfer::STATUS_RECEIVED ? $receiver->id : null,
                    'status' => $status,
                    'note' => "Demo batch transfer {$seq}",
                    'delivery_ref' => "SJ-DEMO-{$seq}",
                    'courier_name' => 'Kurir Internal',
                    'reject_reason' => $status === StockTransfer::STATUS_REJECTED ? 'Stok tujuan masih mencukupi.' : null,
                    'approved_at' => in_array($status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_RECEIVED, StockTransfer::STATUS_REJECTED], true) ? now()->subDays(10 - $idx) : null,
                    'received_at' => $status === StockTransfer::STATUS_RECEIVED ? now()->subDays(9 - $idx) : null,
                ]
            );

            StockTransferItem::query()->where('stock_transfer_id', $transfer->id)->delete();

            $lineItems = collect($catalog)->shuffle()->take(3)->values();
            foreach ($lineItems as $line) {
                $qty = (int) random_int(10, 60);
                $sourceProduct = Product::query()->where('branch_id', $pusat->id)->where('sku', $line['sku'])->first();
                if (! $sourceProduct) {
                    continue;
                }

                StockTransferItem::query()->create([
                    'stock_transfer_id' => $transfer->id,
                    'source_product_id' => $sourceProduct->id,
                    'sku' => $line['sku'],
                    'product_name' => $line['name'],
                    'unit' => $line['unit'],
                    'requested_qty' => $qty,
                    'received_qty' => $status === StockTransfer::STATUS_RECEIVED ? $qty : null,
                ]);

                if ($status === StockTransfer::STATUS_RECEIVED) {
                    $destSku = $line['sku'] . '-B' . $surabaya->id;
                    $dest = Product::query()->firstOrCreate(
                        ['branch_id' => $surabaya->id, 'sku' => $destSku],
                        [
                            'category_id' => $category->id,
                            'name' => $line['name'],
                            'barcode' => null,
                            'purchase_price' => 10000,
                            'selling_price' => 13000,
                            'stock' => 0,
                            'low_stock_threshold' => 15,
                            'unit' => $line['unit'],
                            'is_active' => true,
                        ]
                    );
                    $dest->increment('stock', $qty);
                }
            }
        }
    }
}

