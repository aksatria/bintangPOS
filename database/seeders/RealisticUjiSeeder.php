<?php

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RealisticUjiSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $owner = $this->resolveUser(UserRole::Owner->value);
            $admin = $this->resolveUser(UserRole::Admin->value);
            $cashier = $this->resolveUser(UserRole::Cashier->value);

            $category = Category::query()->firstOrCreate(
                ['name' => 'Obat & Kesehatan'],
                ['description' => 'Data uji nyata untuk POS', 'is_active' => true]
            );

            $products = $this->seedProducts($category->id);
            $customers = $this->seedCustomers();

            $sales = $this->seedSales($products, $customers, $owner, $admin, $cashier);
            $this->seedApprovalQueue($sales, $owner, $admin);
        });
    }

    private function resolveUser(string $role): User
    {
        return User::query()->where('role', $role)->orderBy('id')->firstOrFail();
    }

    private function seedProducts(int $categoryId): array
    {
        $rows = [
            ['name' => 'Paracetamol 500mg 10 Tablet', 'sku' => 'UJI-PCT-500-10', 'purchase_price' => 3200, 'selling_price' => 5500, 'stock' => 180],
            ['name' => 'Amoxicillin 500mg 10 Kapsul', 'sku' => 'UJI-AMX-500-10', 'purchase_price' => 7800, 'selling_price' => 11500, 'stock' => 120],
            ['name' => 'Vitamin C 500mg 30 Tablet', 'sku' => 'UJI-VITC-500-30', 'purchase_price' => 13200, 'selling_price' => 18900, 'stock' => 95],
            ['name' => 'Oralit Sachet', 'sku' => 'UJI-ORL-001', 'purchase_price' => 1400, 'selling_price' => 2500, 'stock' => 240],
            ['name' => 'Masker Medis 3 Ply 50 pcs', 'sku' => 'UJI-MSK-3PLY-50', 'purchase_price' => 18500, 'selling_price' => 26900, 'stock' => 62],
            ['name' => 'Antiseptic Spray 60ml', 'sku' => 'UJI-ANT-SPR-60', 'purchase_price' => 12800, 'selling_price' => 18500, 'stock' => 76],
        ];

        $out = [];
        foreach ($rows as $row) {
            $out[] = Product::query()->updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'category_id' => $categoryId,
                    'name' => $row['name'],
                    'barcode' => $row['sku'],
                    'purchase_price' => $row['purchase_price'],
                    'selling_price' => $row['selling_price'],
                    'stock' => $row['stock'],
                    'low_stock_threshold' => 15,
                    'unit' => 'pcs',
                    'is_active' => true,
                ]
            );
        }

        return $out;
    }

    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'Farah Prakasa', 'phone' => '081210000101', 'email' => 'farah.prakasa@example.test'],
            ['name' => 'Rizky Pratama', 'phone' => '081210000102', 'email' => 'rizky.pratama@example.test'],
            ['name' => 'Dewi Lestari', 'phone' => '081210000103', 'email' => 'dewi.lestari@example.test'],
            ['name' => 'Nadia Anjani', 'phone' => '081210000104', 'email' => 'nadia.anjani@example.test'],
            ['name' => 'Maya Putri', 'phone' => '081210000105', 'email' => 'maya.putri@example.test'],
        ];

        $out = [];
        foreach ($rows as $row) {
            $out[] = Customer::query()->updateOrCreate(
                ['phone' => $row['phone']],
                array_merge($row, ['address' => 'Kediri', 'is_active' => true])
            );
        }

        return $out;
    }

    private function seedSales(array $products, array $customers, User $owner, User $admin, User $cashier): array
    {
        $template = [
            [
                'invoice' => 'UJI-INV-20260502-0001',
                'user_id' => $cashier->id,
                'customer_id' => $customers[0]->id,
                'status' => SaleStatus::Paid->value,
                'method' => 'cash',
                'sold_at' => now()->subHours(8),
                'items' => [
                    ['sku' => 'UJI-PCT-500-10', 'qty' => 2],
                    ['sku' => 'UJI-ORL-001', 'qty' => 3],
                ],
            ],
            [
                'invoice' => 'UJI-INV-20260502-0002',
                'user_id' => $cashier->id,
                'customer_id' => $customers[1]->id,
                'status' => SaleStatus::Paid->value,
                'method' => 'qris',
                'sold_at' => now()->subHours(6),
                'items' => [
                    ['sku' => 'UJI-VITC-500-30', 'qty' => 1],
                    ['sku' => 'UJI-ANT-SPR-60', 'qty' => 1],
                ],
            ],
            [
                'invoice' => 'UJI-INV-20260502-0003',
                'user_id' => $admin->id,
                'customer_id' => $customers[2]->id,
                'status' => SaleStatus::Pending->value,
                'method' => 'debit',
                'sold_at' => now()->subHours(4),
                'items' => [
                    ['sku' => 'UJI-AMX-500-10', 'qty' => 2],
                ],
            ],
            [
                'invoice' => 'UJI-INV-20260502-0004',
                'user_id' => $owner->id,
                'customer_id' => $customers[3]->id,
                'status' => SaleStatus::Cancelled->value,
                'method' => 'transfer',
                'sold_at' => now()->subHours(2),
                'items' => [
                    ['sku' => 'UJI-MSK-3PLY-50', 'qty' => 1],
                ],
            ],
            [
                'invoice' => 'UJI-INV-20260502-0005',
                'user_id' => $cashier->id,
                'customer_id' => $customers[4]->id,
                'status' => SaleStatus::Paid->value,
                'method' => 'mixed',
                'sold_at' => now()->subHour(),
                'items' => [
                    ['sku' => 'UJI-PCT-500-10', 'qty' => 5],
                    ['sku' => 'UJI-VITC-500-30', 'qty' => 2],
                    ['sku' => 'UJI-ORL-001', 'qty' => 2],
                ],
                'breakdown' => [
                    ['method' => 'cash', 'amount' => 20000],
                    ['method' => 'qris', 'amount' => 25000],
                ],
            ],
        ];

        $productMap = collect($products)->keyBy('sku');
        $out = [];

        foreach ($template as $row) {
            $subtotal = 0.0;
            $itemsPayload = [];
            foreach ($row['items'] as $item) {
                $product = $productMap->get($item['sku']);
                if (! $product) {
                    continue;
                }
                $line = ((float) $product->selling_price) * (int) $item['qty'];
                $subtotal += $line;
                $itemsPayload[] = [
                    'product' => $product,
                    'qty' => (int) $item['qty'],
                    'subtotal' => $line,
                ];
            }

            $tax = round($subtotal * 0.01, 2);
            $total = $subtotal + $tax;
            $paid = $row['status'] === SaleStatus::Paid->value ? $total : 0;

            $sale = Sale::query()->updateOrCreate(
                ['invoice_number' => $row['invoice']],
                [
                    'user_id' => $row['user_id'],
                    'customer_id' => $row['customer_id'],
                    'customer_name' => Customer::query()->find($row['customer_id'])?->name,
                    'subtotal' => $subtotal,
                    'discount_amount' => 0,
                    'tax_amount' => $tax,
                    'rounding_amount' => 0,
                    'admin_fee_amount' => 0,
                    'total_amount' => $total,
                    'paid_amount' => $paid,
                    'change_amount' => 0,
                    'payment_method' => $row['method'],
                    'payment_breakdown' => $row['breakdown'] ?? null,
                    'status' => $row['status'],
                    'sold_at' => Carbon::parse($row['sold_at']),
                    'note' => $row['method'] === 'qris' ? '[QRIS] Ref: UJI-REF-'.substr($row['invoice'], -4).' | Issuer: MIDTRANS' : null,
                ]
            );

            $sale->items()->delete();
            foreach ($itemsPayload as $itemPayload) {
                $product = $itemPayload['product'];
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'barcode' => $product->barcode,
                    'unit' => $product->unit,
                    'unit_price' => (float) $product->selling_price,
                    'purchase_price' => (float) $product->purchase_price,
                    'quantity' => (int) $itemPayload['qty'],
                    'discount_amount' => 0,
                    'subtotal' => (float) $itemPayload['subtotal'],
                ]);
            }

            $out[$row['invoice']] = $sale;
        }

        return $out;
    }

    private function seedApprovalQueue(array $sales, User $owner, User $admin): void
    {
        $queueRows = [
            [
                'type' => 'sale.quick_refund',
                'status' => 'pending',
                'requested_by' => $admin->id,
                'reviewed_by' => null,
                'title' => 'Approval Refund UJI-INV-20260502-0002',
                'reason' => 'Customer batal, minta refund penuh.',
                'payload' => [
                    'sale_id' => $sales['UJI-INV-20260502-0002']->id ?? 0,
                    'invoice' => 'UJI-INV-20260502-0002',
                    'reason' => 'Customer batal, minta refund penuh.',
                    'total' => (float) ($sales['UJI-INV-20260502-0002']->total_amount ?? 0),
                ],
            ],
            [
                'type' => 'sale.quick_void',
                'status' => 'approved',
                'requested_by' => $admin->id,
                'reviewed_by' => $owner->id,
                'title' => 'Approval Void UJI-INV-20260502-0003',
                'reason' => 'Pembayaran debit timeout, transaksi dibatalkan.',
                'review_note' => 'Disetujui, lakukan void.',
                'reviewed_at' => now()->subMinutes(40),
                'payload' => [
                    'sale_id' => $sales['UJI-INV-20260502-0003']->id ?? 0,
                    'invoice' => 'UJI-INV-20260502-0003',
                    'reason' => 'Pembayaran debit timeout, transaksi dibatalkan.',
                ],
            ],
            [
                'type' => 'report.export.pdf',
                'status' => 'rejected',
                'requested_by' => $admin->id,
                'reviewed_by' => $owner->id,
                'title' => 'Approval Export PDF Besar',
                'reason' => 'Audit bulanan cabang.',
                'review_note' => 'Ditolak, gunakan rentang tanggal lebih kecil.',
                'reviewed_at' => now()->subMinutes(15),
                'payload' => [
                    'format' => 'pdf',
                    'selected_count' => 412,
                    'selected_total' => 154320000,
                    'fingerprint' => sha1('uji-pdf-1'),
                ],
            ],
            [
                'type' => 'report.export.excel',
                'status' => 'pending',
                'requested_by' => $admin->id,
                'reviewed_by' => null,
                'title' => 'Approval Export Excel Besar',
                'reason' => 'Rekonsiliasi kuartalan finance.',
                'payload' => [
                    'format' => 'excel',
                    'selected_count' => 367,
                    'selected_total' => 121450000,
                    'fingerprint' => sha1('uji-excel-1'),
                ],
            ],
        ];

        foreach ($queueRows as $row) {
            ApprovalRequest::query()->updateOrCreate(
                ['title' => $row['title']],
                [
                    'type' => $row['type'],
                    'status' => $row['status'],
                    'requested_by' => $row['requested_by'],
                    'reviewed_by' => $row['reviewed_by'],
                    'reason' => $row['reason'],
                    'payload' => $row['payload'],
                    'review_note' => $row['review_note'] ?? null,
                    'reviewed_at' => $row['reviewed_at'] ?? null,
                ]
            );
        }

        CashierAuditLog::query()->create([
            'user_id' => $admin->id,
            'action' => 'seed_realistic_uji_loaded',
            'context' => [
                'seed' => self::class,
                'sales_injected' => count($sales),
                'approvals_injected' => count($queueRows),
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Seeder',
        ]);
    }
}

