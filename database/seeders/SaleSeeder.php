<?php

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake('id_ID');

        SaleItem::query()->delete();
        Sale::query()->delete();

        $products = Product::query()->where('is_active', true)->get();
        $cashiers = User::query()->whereIn('role', [UserRole::Cashier->value, UserRole::Admin->value])->get();

        if ($products->isEmpty() || $cashiers->isEmpty()) {
            return;
        }

        $customers = Customer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);
        $customerFallbackName = 'Pelanggan Umum';

        $startDate = Carbon::today()->subDays(180);
        $dayCount = 181;
        $dailySequence = [];

        // Data 6 bulan terakhir agar dashboard + laporan terisi padat.
        for ($d = 0; $d < $dayCount; $d++) {
            $date = $startDate->copy()->addDays($d);
            $dateKey = $date->toDateString();

            $transactionsToday = $faker->numberBetween(8, 28);

            for ($i = 0; $i < $transactionsToday; $i++) {
                $itemCount = $faker->numberBetween(1, 8);
                $pickedProducts = $products->random($itemCount);

                $subtotal = 0;
                $itemsPayload = [];

                foreach ($pickedProducts as $product) {
                    $qty = $faker->numberBetween(1, 5);
                    $itemDiscount = $faker->boolean(20) ? $faker->numberBetween(0, 3000) : 0;
                    $itemSubtotal = max(($product->selling_price * $qty) - $itemDiscount, 0);

                    $subtotal += $itemSubtotal;

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
                        'subtotal' => $itemSubtotal,
                    ];
                }

                $discountAmount = $faker->boolean(35) ? $faker->numberBetween(0, min(15000, (int) floor($subtotal * 0.15))) : 0;
                $taxAmount = (int) round(max($subtotal - $discountAmount, 0) * 0.11);
                $total = max($subtotal - $discountAmount + $taxAmount, 0);

                $status = $faker->randomElement([
                    SaleStatus::Paid->value,
                    SaleStatus::Paid->value,
                    SaleStatus::Paid->value,
                    SaleStatus::Paid->value,
                    SaleStatus::Pending->value,
                    SaleStatus::Cancelled->value,
                ]);

                $paidAmount = 0;
                $changeAmount = 0;

                if ($status === SaleStatus::Paid->value) {
                    $paidAmount = $total + $faker->numberBetween(0, 10000);
                    $changeAmount = max($paidAmount - $total, 0);
                } elseif ($status === SaleStatus::Pending->value) {
                    $paidAmount = $faker->numberBetween(0, (int) max($total - 1, 0));
                }

                $dailySequence[$dateKey] = ($dailySequence[$dateKey] ?? 0) + 1;
                $invoice = sprintf('INV-%s-%04d', $date->format('Ymd'), $dailySequence[$dateKey]);

                $pickedCustomer = $customers->isNotEmpty() ? $customers->random() : null;
                $sale = Sale::query()->create([
                    'user_id' => $cashiers->random()->id,
                    'invoice_number' => $invoice,
                    'customer_id' => $pickedCustomer?->id,
                    'customer_name' => $pickedCustomer?->name ?: $customerFallbackName,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $total,
                    'paid_amount' => $paidAmount,
                    'change_amount' => $changeAmount,
                    'status' => $status,
                    'note' => $faker->boolean(18) ? $faker->sentence() : null,
                    'sold_at' => $date->copy()->setTime($faker->numberBetween(7, 22), $faker->numberBetween(0, 59), $faker->numberBetween(0, 59)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($itemsPayload as $item) {
                    $item['sale_id'] = $sale->id;
                    SaleItem::query()->create($item);
                }
            }
        }
    }
}
