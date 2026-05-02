<?php

namespace Database\Seeders;

use App\Enums\SaleStatus;
use App\Models\CashierAuditLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleRefundItem;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OperationalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::query()
            ->whereIn('role', ['owner', 'admin'])
            ->orderBy('id')
            ->first();

        if (! $manager) {
            return;
        }

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(6)
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($manager, $products): void {
            $this->seedPostedStockOpname($manager->id, $products);
            $this->seedOpenStockOpname($manager->id, $products);
            $this->seedRefundTrail($manager->id);
        });
    }

    private function seedPostedStockOpname(int $userId, $products): void
    {
        $code = 'OPN-DEMO-POSTED';
        $session = StockOpname::query()->firstOrCreate(
            ['code' => $code],
            [
                'user_id' => $userId,
                'status' => 'posted',
                'total_items' => $products->count(),
                'adjusted_items' => 0,
                'total_difference' => 0,
                'note' => 'Contoh sesi opname posted untuk demo.',
                'posted_note' => 'Demo posting otomatis.',
                'started_at' => Carbon::now()->subDays(2),
                'posted_at' => Carbon::now()->subDays(2)->addHour(),
            ]
        );

        foreach ($products as $idx => $product) {
            $system = (int) $product->stock;
            $counted = max($system + (($idx % 3) - 1), 0); // -1,0,+1 pattern
            $diff = $counted - $system;
            StockOpnameItem::query()->updateOrCreate(
                [
                    'stock_opname_id' => $session->id,
                    'product_id' => $product->id,
                ],
                [
                    'system_stock' => $system,
                    'counted_stock' => $counted,
                    'difference' => $diff,
                ]
            );
        }

        $session->total_items = (int) $session->items()->count();
        $session->adjusted_items = (int) $session->items()->where('difference', '!=', 0)->count();
        $session->total_difference = (int) $session->items()->sum('difference');
        $session->save();

        CashierAuditLog::query()->firstOrCreate(
            [
                'action' => 'stock_opname_posted',
                'context->code' => $code,
            ],
            [
                'user_id' => $userId,
                'context' => [
                    'stock_opname_id' => $session->id,
                    'code' => $code,
                    'adjusted_items' => (int) $session->adjusted_items,
                    'total_difference' => (int) $session->total_difference,
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
            ]
        );
    }

    private function seedOpenStockOpname(int $userId, $products): void
    {
        $hasOpen = StockOpname::query()->where('status', 'open')->exists();
        if ($hasOpen) {
            return;
        }

        $code = 'OPN-DEMO-OPEN';
        $session = StockOpname::query()->firstOrCreate(
            ['code' => $code],
            [
                'user_id' => $userId,
                'status' => 'open',
                'total_items' => $products->count(),
                'adjusted_items' => 0,
                'total_difference' => 0,
                'note' => 'Contoh sesi opname OPEN untuk input kasir.',
                'started_at' => Carbon::now()->subDay(),
            ]
        );

        foreach ($products as $product) {
            StockOpnameItem::query()->updateOrCreate(
                [
                    'stock_opname_id' => $session->id,
                    'product_id' => $product->id,
                ],
                [
                    'system_stock' => (int) $product->stock,
                    'counted_stock' => null,
                    'difference' => null,
                ]
            );
        }

        $session->total_items = (int) $session->items()->count();
        $session->save();
    }

    private function seedRefundTrail(int $userId): void
    {
        $sale = Sale::query()
            ->where('status', SaleStatus::Paid->value)
            ->whereHas('items')
            ->orderByDesc('id')
            ->first();

        if (! $sale) {
            return;
        }

        $item = $sale->items()->orderBy('id')->first();
        if (! $item) {
            return;
        }

        $qty = max(1, min(1, (int) $item->quantity));
        $unit = ((float) $item->subtotal) / max(1, (int) $item->quantity);
        $refundAmount = $unit * $qty;

        SaleRefundItem::query()->firstOrCreate(
            [
                'sale_id' => $sale->id,
                'sale_item_id' => $item->id,
                'reason' => 'Contoh partial refund demo',
            ],
            [
                'product_id' => $item->product_id,
                'quantity' => $qty,
                'refund_amount' => $refundAmount,
                'created_by' => $userId,
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ]
        );

        CashierAuditLog::query()->firstOrCreate(
            [
                'action' => 'sale_partial_refunded',
                'context->invoice' => $sale->invoice_number,
                'context->seed_token' => 'demo_refund',
            ],
            [
                'user_id' => $userId,
                'context' => [
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                    'reason' => 'Contoh partial refund demo',
                    'refund_total' => (float) $refundAmount,
                    'items' => [
                        [
                            'sale_item_id' => $item->id,
                            'quantity' => $qty,
                        ],
                    ],
                    'approved_by' => 'de**@demo.local',
                    'approval_reason' => 'Simulasi audit',
                    'seed_token' => 'demo_refund',
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'created_at' => now()->subHours(5),
                'updated_at' => now()->subHours(5),
            ]
        );
    }
}
