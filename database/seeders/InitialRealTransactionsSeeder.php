<?php

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InitialRealTransactionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Sale::query()->exists()) {
            $this->command?->warn('Sales sudah ada. Seeder dilewati agar tidak duplikat.');
            return;
        }

        $cashier = User::query()->whereIn('role', ['owner', 'admin', 'kasir'])->first();
        if (! $cashier) {
            $this->command?->warn('User kasir/admin/owner tidak ditemukan.');
            return;
        }

        $products = Product::query()
            ->active()
            ->where('stock', '>', 3)
            ->orderBy('id')
            ->take(42)
            ->get();

        if ($products->count() < 12) {
            $this->command?->warn('Produk aktif tidak cukup untuk membuat transaksi awal.');
            return;
        }

        DB::transaction(function () use ($cashier, $products): void {
            $today = Carbon::today();
            $invoiceCounter = 1;
            $cursor = 0;

            for ($d = 6; $d >= 0; $d--) {
                $date = $today->copy()->subDays($d);

                for ($t = 0; $t < 2; $t++) {
                    $picked = $products->slice($cursor, 3)->values();
                    $cursor = ($cursor + 3) % max($products->count(), 1);

                    $subtotal = 0;
                    $itemsPayload = [];

                    foreach ($picked as $index => $product) {
                        $qty = ($index % 2) + 1;
                        $qty = min($qty, max($product->stock, 1));
                        $lineSubtotal = (float) $product->selling_price * $qty;
                        $subtotal += $lineSubtotal;

                        $itemsPayload[] = [
                            'product' => $product,
                            'qty' => $qty,
                            'subtotal' => $lineSubtotal,
                        ];
                    }

                    $discountAmount = $t === 1 ? (float) round($subtotal * 0.03) : 0.0;
                    $taxAmount = (float) round(max($subtotal - $discountAmount, 0) * 0.11);
                    $total = max($subtotal - $discountAmount + $taxAmount, 0);
                    $paidAmount = $total + 5000;
                    $changeAmount = 5000;

                    $invoice = sprintf('INV-%s-%04d', $date->format('Ymd'), $invoiceCounter++);
                    $soldAt = $date->copy()->setTime(9 + ($t * 5), 15 + ($d % 3) * 10, 0);

                    $sale = Sale::query()->create([
                        'user_id' => $cashier->id,
                        'invoice_number' => $invoice,
                        'customer_name' => $t === 0 ? 'Pelanggan Toko' : 'Pelanggan Member',
                        'subtotal' => $subtotal,
                        'discount_amount' => $discountAmount,
                        'tax_amount' => $taxAmount,
                        'total_amount' => $total,
                        'paid_amount' => $paidAmount,
                        'change_amount' => $changeAmount,
                        'status' => SaleStatus::Paid->value,
                        'note' => null,
                        'sold_at' => $soldAt,
                    ]);

                    foreach ($itemsPayload as $payload) {
                        /** @var Product $product */
                        $product = $payload['product'];
                        $qty = (int) $payload['qty'];

                        SaleItem::query()->create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'sku' => $product->sku,
                            'barcode' => $product->barcode,
                            'unit' => $product->unit,
                            'unit_price' => $product->selling_price,
                            'purchase_price' => $product->purchase_price,
                            'quantity' => $qty,
                            'discount_amount' => 0,
                            'subtotal' => $payload['subtotal'],
                        ]);

                        $product->decrement('stock', $qty);
                    }
                }
            }
        });

        $this->command?->info('Transaksi awal realistis berhasil dibuat.');
    }
}

