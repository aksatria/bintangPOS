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

class CustomerSegmentScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->where('is_active', true)->orderBy('id')->limit(30)->get();
        $cashiers = User::query()
            ->whereIn('role', [UserRole::Owner->value, UserRole::Admin->value, UserRole::Cashier->value])
            ->orderBy('id')
            ->get();

        if ($products->count() < 6 || $cashiers->isEmpty()) {
            $this->command?->warn('CustomerSegmentScenarioSeeder dilewati: produk/kasir aktif belum cukup.');
            return;
        }

        DB::transaction(function () use ($products, $cashiers): void {
            $seq = (int) Sale::query()->count() + 1;

            $pendingCustomers = $this->upsertScenarioCustomers('SEG-PENDING', 3, '08191000', 'pending.test');
            $activeCustomers = $this->upsertScenarioCustomers('SEG-AKTIF', 3, '08192000', 'aktif.test');
            $sleepingCustomers = $this->upsertScenarioCustomers('SEG-TIDUR', 3, '08193000', 'tidur.test');

            foreach ($pendingCustomers as $customer) {
                $this->createSaleWithItems(
                    $customer,
                    $cashiers->random()->id,
                    $products->shuffle()->take(rand(2, 4))->values(),
                    SaleStatus::Pending->value,
                    now()->subDays(rand(0, 6))->setTime(rand(9, 20), rand(0, 59)),
                    $seq
                );
                $seq++;

                $this->createSaleWithItems(
                    $customer,
                    $cashiers->random()->id,
                    $products->shuffle()->take(rand(2, 4))->values(),
                    SaleStatus::Paid->value,
                    now()->subDays(rand(0, 6))->setTime(rand(9, 20), rand(0, 59)),
                    $seq
                );
                $seq++;
            }

            foreach ($activeCustomers as $customer) {
                $this->createSaleWithItems(
                    $customer,
                    $cashiers->random()->id,
                    $products->shuffle()->take(rand(2, 4))->values(),
                    SaleStatus::Paid->value,
                    now()->subDays(rand(0, 7))->setTime(rand(9, 20), rand(0, 59)),
                    $seq
                );
                $seq++;
            }

            foreach ($sleepingCustomers as $customer) {
                $this->createSaleWithItems(
                    $customer,
                    $cashiers->random()->id,
                    $products->shuffle()->take(rand(2, 4))->values(),
                    SaleStatus::Paid->value,
                    now()->subDays(rand(45, 90))->setTime(rand(9, 20), rand(0, 59)),
                    $seq
                );
                $seq++;
            }
        });

        $this->command?->info('CustomerSegmentScenarioSeeder selesai: segmen pending, aktif non-pending, dan tidur sudah dibuat.');
    }

    private function upsertScenarioCustomers(string $prefix, int $count, string $phonePrefix, string $emailDomain)
    {
        $rows = collect();
        for ($i = 1; $i <= $count; $i++) {
            $name = sprintf('%s %02d', $prefix, $i);
            $phone = $phonePrefix.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
            $email = strtolower(str_replace(' ', '.', $name)).'@'.$emailDomain;

            $customer = Customer::query()->updateOrCreate(
                ['phone' => $phone],
                [
                    'name' => $name,
                    'email' => $email,
                    'address' => 'Data skenario segmen '.$prefix,
                    'is_active' => true,
                ]
            );

            $rows->push($customer);
        }

        return $rows;
    }

    private function createSaleWithItems(Customer $customer, int $userId, $products, string $status, Carbon $soldAt, int $sequence): void
    {
        $subtotal = 0.0;
        $itemsPayload = [];

        foreach ($products as $product) {
            $qty = rand(1, 3);
            $itemDiscount = rand(0, 1) === 1 ? rand(0, 2000) : 0;
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

        $discountAmount = (float) rand(0, (int) min(10000, floor($subtotal * 0.08)));
        $taxAmount = (float) round(max($subtotal - $discountAmount, 0) * 0.11);
        $total = max($subtotal - $discountAmount + $taxAmount, 0);
        $paidAmount = $status === SaleStatus::Paid->value
            ? $total + rand(0, 3000)
            : (float) rand(0, (int) max($total - 1, 0));
        $changeAmount = $status === SaleStatus::Paid->value ? max($paidAmount - $total, 0) : 0;

        $sale = Sale::query()->create([
            'user_id' => $userId,
            'customer_id' => $customer->id,
            'invoice_number' => sprintf('SEG-%s-%05d', $soldAt->format('Ymd'), $sequence),
            'customer_name' => $customer->name,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
            'paid_amount' => $paidAmount,
            'change_amount' => $changeAmount,
            'status' => $status,
            'note' => 'Seeder segment scenario',
            'sold_at' => $soldAt,
        ]);

        foreach ($itemsPayload as $item) {
            $item['sale_id'] = $sale->id;
            SaleItem::query()->create($item);
        }
    }
}
