<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\SupplierPurchasePayment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SupplierCompleteSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->where('is_active', true)->get();
        if ($branches->isEmpty()) {
            return;
        }

        $supplierTemplates = [
            [
                'name' => 'PT Farma Sentosa Nusantara',
                'code' => 'SUP-DMO-FSN',
                'phone' => '021-5551001',
                'email' => 'sales@farmasentosa.co.id',
                'address' => 'Jl. Industri Farmasi No. 11, Jakarta',
                'payment_term_days' => 0,
                'note' => 'Fokus obat resep & injeksi.',
                'is_active' => true,
            ],
            [
                'name' => 'CV Medika Prima Logistik',
                'code' => 'SUP-DMO-MPL',
                'phone' => '021-5551002',
                'email' => 'order@medikaprima.id',
                'address' => 'Jl. Gudang Sehat No. 8, Tangerang',
                'payment_term_days' => 0,
                'note' => 'Pengiriman cepat area Jabodetabek.',
                'is_active' => true,
            ],
            [
                'name' => 'PT Herbal Indo Distribusi',
                'code' => 'SUP-DMO-HID',
                'phone' => '021-5551003',
                'email' => 'cs@herbalindo.co.id',
                'address' => 'Jl. Raya Herbal No. 27, Bogor',
                'payment_term_days' => 0,
                'note' => 'Produk herbal dan suplemen.',
                'is_active' => true,
            ],
            [
                'name' => 'PT Alkes Mandiri Jaya',
                'code' => 'SUP-DMO-AMJ',
                'phone' => '021-5551004',
                'email' => 'b2b@alkesmandiri.com',
                'address' => 'Jl. Teknologi Medis No. 4, Bekasi',
                'payment_term_days' => 0,
                'note' => 'Pemasok alat kesehatan.',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            $admin = User::query()
                ->where('branch_id', $branch->id)
                ->whereIn('role', ['owner', 'admin'])
                ->orderBy('id')
                ->first();

            $products = Product::query()
                ->where('branch_id', $branch->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->take(8)
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            $suppliers = collect($supplierTemplates)->map(function (array $template) use ($branch) {
                return Supplier::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'code' => $template['code']],
                    $template + ['branch_id' => $branch->id]
                );
            })->values();

            $purchaseScenarios = [
                ['status' => 'received', 'payment_status' => 'paid', 'days_ago' => 20, 'pay_ratio' => 1.0, 'term_days' => 14],
                ['status' => 'received', 'payment_status' => 'partial', 'days_ago' => 12, 'pay_ratio' => 0.45, 'term_days' => 21],
                ['status' => 'received', 'payment_status' => 'overdue', 'days_ago' => 18, 'pay_ratio' => 0.20, 'term_days' => 7],
                ['status' => 'received', 'payment_status' => 'unpaid', 'days_ago' => 3, 'pay_ratio' => 0.0, 'term_days' => 30],
            ];

            foreach ($suppliers as $idx => $supplier) {
                $scenario = $purchaseScenarios[$idx % count($purchaseScenarios)];
                $orderedAt = Carbon::now()->subDays((int) $scenario['days_ago'])->startOfDay();
                $termDays = (int) $scenario['term_days'];
                $dueDate = $termDays > 0 ? $orderedAt->copy()->addDays($termDays) : null;

                $number = sprintf(
                    'PO-DEM-%s-%02d',
                    strtoupper((string) $branch->code),
                    $idx + 1
                );

                $purchase = SupplierPurchase::query()->updateOrCreate(
                    ['number' => $number],
                    [
                        'branch_id' => $branch->id,
                        'supplier_id' => $supplier->id,
                        'created_by' => $admin?->id,
                        'status' => (string) $scenario['status'],
                        'ordered_at' => $orderedAt->toDateString(),
                        'payment_term_days' => $termDays,
                        'due_date' => $dueDate?->toDateString(),
                        'received_at' => $orderedAt->copy()->addDay(),
                        'subtotal' => 0,
                        'discount_amount' => 0,
                        'tax_amount' => 0,
                        'shipping_amount' => 0,
                        'total_amount' => 0,
                        'paid_amount' => 0,
                        'remaining_amount' => 0,
                        'payment_status' => (string) $scenario['payment_status'],
                        'paid_at' => null,
                        'note' => 'Data demo supplier lengkap untuk monitoring hutang.',
                    ]
                );

                SupplierPurchaseItem::query()
                    ->where('supplier_purchase_id', $purchase->id)
                    ->delete();
                SupplierPurchasePayment::query()
                    ->where('supplier_purchase_id', $purchase->id)
                    ->delete();

                $selectedProducts = $products->shuffle()->take(min(3, $products->count()))->values();
                $subtotal = 0.0;

                foreach ($selectedProducts as $lineNo => $product) {
                    $qty = 5 + ($lineNo * 2);
                    $unitCost = (float) max((float) $product->purchase_price, 1000);
                    $lineTotal = $qty * $unitCost;
                    $subtotal += $lineTotal;

                    SupplierPurchaseItem::query()->create([
                        'supplier_purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'product_name' => (string) $product->name,
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'line_total' => $lineTotal,
                    ]);
                }

                $total = $subtotal;
                $paid = round($total * (float) $scenario['pay_ratio'], 2);
                $remaining = max($total - $paid, 0);
                $paymentStatus = $remaining <= 0
                    ? 'paid'
                    : (($dueDate && $dueDate->isPast()) ? 'overdue' : ((float) $paid > 0 ? 'partial' : 'unpaid'));

                $purchase->update([
                    'subtotal' => $subtotal,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $paid > 0 ? $orderedAt->copy()->addDays(3) : null,
                ]);

                if ($paid > 0) {
                    if ($paymentStatus === 'paid') {
                        SupplierPurchasePayment::query()->create([
                            'supplier_purchase_id' => $purchase->id,
                            'received_by' => $admin?->id,
                            'paid_at' => $orderedAt->copy()->addDays(3),
                            'amount' => $paid,
                            'payment_method' => 'transfer',
                            'note' => 'Pelunasan hutang supplier (demo).',
                        ]);
                    } else {
                        $firstPay = round($paid * 0.6, 2);
                        $secondPay = round($paid - $firstPay, 2);

                        SupplierPurchasePayment::query()->create([
                            'supplier_purchase_id' => $purchase->id,
                            'received_by' => $admin?->id,
                            'paid_at' => $orderedAt->copy()->addDays(2),
                            'amount' => $firstPay,
                            'payment_method' => 'cash',
                            'note' => 'Pembayaran termin 1 (demo).',
                        ]);

                        if ($secondPay > 0) {
                            SupplierPurchasePayment::query()->create([
                                'supplier_purchase_id' => $purchase->id,
                                'received_by' => $admin?->id,
                                'paid_at' => $orderedAt->copy()->addDays(4),
                                'amount' => $secondPay,
                                'payment_method' => 'transfer',
                                'note' => 'Pembayaran termin 2 (demo).',
                            ]);
                        }
                    }
                }
            }
        }
    }
}
