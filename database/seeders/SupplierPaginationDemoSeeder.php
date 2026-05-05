<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\SupplierPurchasePayment;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SupplierPaginationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::query()->where('is_active', true)->orderBy('id')->get();
        if ($branches->isEmpty()) {
            return;
        }

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

        $itemCatalog = [
            ['name' => 'Amoxicillin 500mg Strip', 'qty' => 24, 'cost' => 6200],
            ['name' => 'Paracetamol 500mg Box', 'qty' => 18, 'cost' => 8500],
            ['name' => 'Vitamin C 500mg Botol', 'qty' => 14, 'cost' => 12500],
            ['name' => 'Masker Medis 3 Ply Box', 'qty' => 10, 'cost' => 18500],
            ['name' => 'Hand Sanitizer 500ml', 'qty' => 12, 'cost' => 16000],
            ['name' => 'Kasa Steril 16x16 Pack', 'qty' => 16, 'cost' => 9800],
            ['name' => 'Syringe 3ml Box', 'qty' => 8, 'cost' => 32000],
            ['name' => 'Omeprazole 20mg Strip', 'qty' => 20, 'cost' => 7100],
        ];

        foreach ($branches as $branch) {
            $admin = User::query()
                ->where(function ($query) use ($branch) {
                    $query->where('branch_id', $branch->id)->orWhereNull('branch_id');
                })
                ->whereIn('role', ['owner', 'admin'])
                ->orderBy('id')
                ->first();

            foreach ($supplierNames as $index => $supplierName) {
                $i = $index + 1;
                $code = sprintf('SUP-PG-%02d', $i);
                $supplier = Supplier::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'code' => $code],
                    [
                        'name' => $supplierName,
                        'phone' => sprintf('021-5599%04d', $i),
                        'email' => sprintf('order%02d@%s.test', $i, strtolower(str_replace(['PT ', 'CV ', 'UD ', ' '], ['', '', '', '-'], $supplierName))),
                        'address' => sprintf('Jl. Distribusi Kesehatan No. %02d, %s', $i, $branch->name),
                        'payment_term_days' => 0,
                        'note' => 'Data supplier demo konsisten untuk uji pembelian, hutang, dan daftar supplier.',
                        'is_active' => true,
                    ]
                );

                $orderedAt = Carbon::now()->subDays($i + 2)->startOfDay();
                $termDays = [7, 14, 21, 30][$index % 4];
                $dueDate = $orderedAt->copy()->addDays($termDays);
                $number = sprintf('PO-PG-%s-%02d', strtoupper((string) $branch->code), $i);
                $status = $i % 6 === 0 ? 'draft' : 'received';
                $shipping = [0, 12000, 18000, 25000][$index % 4];
                $discount = $i % 5 === 0 ? 7500 : 0;
                $tax = $i % 3 === 0 ? 11000 : 0;
                $shippingTreatment = $i % 4 === 0 ? 'expense' : 'inventory';

                $pickedItems = [
                    $itemCatalog[$index % count($itemCatalog)],
                    $itemCatalog[($index + 3) % count($itemCatalog)],
                ];

                $subtotal = collect($pickedItems)->sum(fn (array $item) => (float) $item['qty'] * (float) $item['cost']);
                $total = max($subtotal - $discount + $tax + $shipping, 0);
                $payRatio = [1.0, 0.45, 0.0, 0.2][$index % 4];
                $paid = $status === 'received' ? round($total * $payRatio, 2) : 0;
                $remaining = max($total - $paid, 0);
                $paymentStatus = $remaining <= 0
                    ? 'paid'
                    : (($dueDate->isPast()) ? 'overdue' : ($paid > 0 ? 'partial' : 'unpaid'));

                $purchase = SupplierPurchase::query()->updateOrCreate(
                    ['number' => $number],
                    [
                        'branch_id' => $branch->id,
                        'supplier_id' => $supplier->id,
                        'created_by' => $admin?->id,
                        'supplier_invoice_number' => $status === 'received' ? sprintf('INV-%s-%04d', strtoupper((string) $branch->code), $i) : null,
                        'delivery_note_number' => $status === 'received' ? sprintf('SJ-%s-%04d', strtoupper((string) $branch->code), $i) : null,
                        'supplier_invoice_amount' => $status === 'received' ? $total : null,
                        'status' => $status,
                        'ordered_at' => $orderedAt->toDateString(),
                        'payment_term_days' => $termDays,
                        'due_date' => $dueDate->toDateString(),
                        'received_at' => $status === 'received' ? $orderedAt->copy()->addDay()->setTime(10, 0) : null,
                        'subtotal' => $subtotal,
                        'discount_amount' => $discount,
                        'tax_amount' => $tax,
                        'shipping_amount' => $shipping,
                        'shipping_accounting_treatment' => $shippingTreatment,
                        'shipping_allocation_method' => 'by_value',
                        'inventory_shipping_amount' => $shippingTreatment === 'inventory' ? $shipping : 0,
                        'expense_shipping_amount' => $shippingTreatment === 'expense' ? $shipping : 0,
                        'total_amount' => $total,
                        'paid_amount' => $paid,
                        'remaining_amount' => $remaining,
                        'payment_status' => $status === 'draft' ? 'unpaid' : $paymentStatus,
                        'reconciliation_status' => $status === 'received' ? 'matched' : 'unchecked',
                        'reconciliation_note' => $status === 'received' ? 'Data demo sudah cocok dengan invoice supplier.' : null,
                        'paid_at' => $paid > 0 ? $orderedAt->copy()->addDays(2)->setTime(14, 30) : null,
                        'note' => 'Seeder konsisten: termin berada di pembelian, produk diketik manual, ongkir mengikuti perlakuan akuntansi.',
                    ]
                );

                JournalEntry::query()
                    ->whereIn('event', ['supplier_receive', 'supplier_payment'])
                    ->where(function ($query) use ($purchase) {
                        $query->where(function ($sourceQuery) use ($purchase) {
                            $sourceQuery
                                ->where('source_type', SupplierPurchase::class)
                                ->where('source_id', $purchase->id);
                        })->orWhere('memo', 'like', '%'.$purchase->number.'%');
                    })
                    ->delete();

                SupplierPurchaseItem::query()->where('supplier_purchase_id', $purchase->id)->delete();
                SupplierPurchasePayment::query()->where('supplier_purchase_id', $purchase->id)->delete();

                $this->createItems($purchase, $pickedItems, $shipping, $shippingTreatment, $status);
                $this->createPayments($purchase, $paid, $admin?->id, $orderedAt);
                $this->postAccounting($purchase, $admin?->id);
            }
        }
    }

    private function createItems(SupplierPurchase $purchase, array $items, float $shipping, string $shippingTreatment, string $status): void
    {
        $subtotal = collect($items)->sum(fn (array $item) => (float) $item['qty'] * (float) $item['cost']);
        $allocated = 0.0;
        $lastIndex = count($items) - 1;

        foreach ($items as $idx => $item) {
            $qty = (int) $item['qty'];
            $unitCost = (float) $item['cost'];
            $lineTotal = $qty * $unitCost;
            $allocation = 0.0;
            if ($shippingTreatment === 'inventory' && $shipping > 0 && $subtotal > 0) {
                $allocation = $idx === $lastIndex ? round($shipping - $allocated, 2) : round($shipping * ($lineTotal / $subtotal), 2);
                $allocated += $allocation;
            }
            $landedLine = $lineTotal + $allocation;
            $landedUnit = $qty > 0 ? round($landedLine / $qty, 2) : $unitCost;

            SupplierPurchaseItem::query()->create([
                'supplier_purchase_id' => $purchase->id,
                'product_id' => null,
                'product_name' => (string) $item['name'],
                'quantity' => $qty,
                'received_quantity' => $status === 'received' ? $qty : 0,
                'returned_quantity' => 0,
                'unit_cost' => $unitCost,
                'line_total' => $lineTotal,
                'shipping_allocation_amount' => $allocation,
                'landed_unit_cost' => $landedUnit,
                'landed_line_total' => $landedLine,
            ]);
        }
    }

    private function createPayments(SupplierPurchase $purchase, float $paid, ?int $adminId, Carbon $orderedAt): void
    {
        if ($paid <= 0) {
            return;
        }

        $paymentRows = (float) $purchase->remaining_amount <= 0
            ? [['amount' => $paid, 'method' => 'transfer', 'note' => 'Pelunasan hutang supplier demo.']]
            : [
                ['amount' => round($paid * 0.6, 2), 'method' => 'cash', 'note' => 'DP / pembayaran awal supplier demo.'],
                ['amount' => round($paid * 0.4, 2), 'method' => 'transfer', 'note' => 'Pembayaran termin supplier demo.'],
            ];

        foreach ($paymentRows as $offset => $row) {
            if ((float) $row['amount'] <= 0) {
                continue;
            }

            SupplierPurchasePayment::query()->create([
                'supplier_purchase_id' => $purchase->id,
                'received_by' => $adminId,
                'paid_at' => $orderedAt->copy()->addDays(2 + $offset)->setTime(14, 30),
                'amount' => (float) $row['amount'],
                'payment_method' => (string) $row['method'],
                'reference_number' => sprintf('PAY-%s-%02d', $purchase->number, $offset + 1),
                'note' => (string) $row['note'],
            ]);
        }
    }

    private function postAccounting(SupplierPurchase $purchase, ?int $adminId): void
    {
        if ($purchase->status !== 'received') {
            return;
        }

        $service = app(AccountingService::class);
        $purchase->load('payments');
        $service->postSupplierReceive($purchase, $adminId);
        foreach ($purchase->payments as $payment) {
            $service->postSupplierPayment($payment, $adminId);
        }
    }
}
