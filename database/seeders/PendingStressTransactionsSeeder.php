<?php

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PendingStressTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(10)
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(25)
            ->get();

        $cashiers = User::query()
            ->whereIn('role', [UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value])
            ->orderBy('id')
            ->get();

        if ($customers->isEmpty() || $products->count() < 5 || $cashiers->isEmpty()) {
            $this->command?->warn('PendingStressTransactionsSeeder dilewati: customer/produk/kasir belum mencukupi.');
            return;
        }

        DB::transaction(function () use ($customers, $products, $cashiers): void {
            $now = now();
            $invoiceCounter = (int) Sale::query()->count() + 1;

            foreach ($customers as $customer) {
                // 6 transaksi pending per customer agar pola pending terlihat jelas.
                for ($i = 0; $i < 6; $i++) {
                    $pickedProducts = $products->shuffle()->take(rand(2, 4))->values();
                    $subtotal = 0.0;
                    $itemsPayload = [];

                    foreach ($pickedProducts as $product) {
                        $qty = rand(1, 3);
                        $itemDiscount = rand(0, 1) === 1 ? rand(0, 2500) : 0;
                        $lineSubtotal = max(((float) $product->selling_price * $qty) - $itemDiscount, 0);
                        $subtotal += $lineSubtotal;

                        $itemsPayload[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'sku' => $product->sku,
                            'barcode' => $product->barcode,
                            'unit' => $product->unit,
                            'unit_price' => $product->selling_price,
                            'purchase_price' => $product->purchase_price,
                            'quantity' => $qty,
                            'discount_amount' => $itemDiscount,
                            'subtotal' => $lineSubtotal,
                        ];
                    }

                    $discountAmount = rand(0, 1) === 1 ? (float) rand(0, (int) min(12000, floor($subtotal * 0.1))) : 0.0;
                    $taxAmount = (float) round(max($subtotal - $discountAmount, 0) * 0.11);
                    $total = max($subtotal - $discountAmount + $taxAmount, 0);
                    $paidAmount = (float) rand(0, (int) max($total - 1, 0));
                    $soldAt = Carbon::parse($now)->subDays(rand(0, 20))->setTime(rand(8, 21), rand(0, 59), rand(0, 59));

                    $sale = Sale::query()->create([
                        'user_id' => $cashiers->random()->id,
                        'customer_id' => $customer->id,
                        'invoice_number' => sprintf('PND-%s-%05d', $soldAt->format('Ymd'), $invoiceCounter++),
                        'customer_name' => $customer->name,
                        'subtotal' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $total,
                        'paid_amount' => $paidAmount,
                        'change_amount' => 0,
                        'status' => SaleStatus::Pending->value,
                        'note' => 'Seeder pending stress test',
                        'sold_at' => $soldAt,
                    ]);

                    foreach ($itemsPayload as $item) {
                        $item['sale_id'] = $sale->id;
                        SaleItem::query()->create($item);
                    }
                }
            }
        });

        $this->command?->info('PendingStressTransactionsSeeder selesai: transaksi pending terarah berhasil ditambahkan.');
    }
}
