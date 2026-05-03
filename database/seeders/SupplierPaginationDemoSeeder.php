<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\SupplierPurchasePayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SupplierPaginationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('is_active', true)->orderBy('id')->first();
        if (! $branch) {
            return;
        }

        $admin = User::query()
            ->where('branch_id', $branch->id)
            ->whereIn('role', ['owner', 'admin'])
            ->orderBy('id')
            ->first();

        $supplierNames = [
            'PT Sumber Medika Pratama',
            'CV Cahaya Farmasi Abadi',
            'PT Nusantara Alkes Sejahtera',
            'UD Mitra Sehat Bersama',
            'PT Prima Distribusi Obat',
            'CV Berkah Herbal Mandiri',
            'PT Sentra Vitamin Indonesia',
            'CV Logistik Farma Utama',
            'PT Karya Medika Lestari',
            'UD Sehat Jaya Perkasa',
            'PT Global Alat Kesehatan',
            'CV Anugerah Farma Sentosa',
            'PT Mandiri Sedia Medika',
            'UD Tunas Herbal Nusantara',
            'PT Indo Sehat Distribusi',
            'CV Sarana Medika Gemilang',
        ];

        foreach ($supplierNames as $index => $supplierName) {
            $i = $index + 1;
            $supplier = Supplier::query()->updateOrCreate(
                ['branch_id' => $branch->id, 'code' => sprintf('SUP-PG-%02d', $i)],
                [
                    'name' => $supplierName,
                    'phone' => sprintf('021-5599%04d', $i),
                    'email' => sprintf('supplier.pagination%02d@example.test', $i),
                    'address' => sprintf('Alamat demo pagination supplier nomor %02d', $i),
                    'payment_term_days' => 0,
                    'note' => 'Data demo untuk melihat pagination supplier.',
                    'is_active' => true,
                ]
            );

            $orderedAt = Carbon::now()->subDays($i);
            $number = sprintf('PO-PG-%s-%02d', strtoupper((string) $branch->code), $i);
            $lineA = 50000 + ($i * 5000);
            $lineB = 12500 + ($i * 2500);
            $subtotal = $lineA + $lineB;
            $shipping = $i % 3 === 0 ? 15000 : 0;
            $total = $subtotal + $shipping;
            $paid = $i % 4 === 0 ? min(50000, $total) : 0;
            $paymentTermDays = [7, 14, 21, 30][$i % 4];

            $purchase = SupplierPurchase::query()->updateOrCreate(
                ['number' => $number],
                [
                    'branch_id' => $branch->id,
                    'supplier_id' => $supplier->id,
                    'created_by' => $admin?->id,
                    'status' => $i % 5 === 0 ? 'draft' : 'received',
                    'ordered_at' => $orderedAt->toDateString(),
                    'payment_term_days' => $paymentTermDays,
                    'due_date' => $orderedAt->copy()->addDays($paymentTermDays)->toDateString(),
                    'received_at' => $i % 5 === 0 ? null : $orderedAt->copy()->addDay(),
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'shipping_amount' => $shipping,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'remaining_amount' => max($total - $paid, 0),
                    'payment_status' => $paid > 0 ? 'partial' : 'unpaid',
                    'paid_at' => null,
                    'note' => 'Data demo untuk melihat pagination pembelian.',
                ]
            );

            SupplierPurchaseItem::query()->where('supplier_purchase_id', $purchase->id)->delete();
            SupplierPurchasePayment::query()->where('supplier_purchase_id', $purchase->id)->delete();
            SupplierPurchaseItem::query()->create([
                'supplier_purchase_id' => $purchase->id,
                'product_id' => null,
                'product_name' => sprintf('Barang Demo A %02d', $i),
                'quantity' => 10 + $i,
                'unit_cost' => 5000,
                'line_total' => $lineA,
            ]);
            SupplierPurchaseItem::query()->create([
                'supplier_purchase_id' => $purchase->id,
                'product_id' => null,
                'product_name' => sprintf('Barang Demo B %02d', $i),
                'quantity' => 5 + $i,
                'unit_cost' => 2500,
                'line_total' => $lineB,
            ]);

            if ($paid > 0) {
                SupplierPurchasePayment::query()->create([
                    'supplier_purchase_id' => $purchase->id,
                    'received_by' => $admin?->id,
                    'paid_at' => $orderedAt->copy()->addDay(),
                    'amount' => $paid,
                    'payment_method' => 'transfer',
                    'note' => 'Pembayaran demo pagination.',
                ]);
            }
        }
    }
}
