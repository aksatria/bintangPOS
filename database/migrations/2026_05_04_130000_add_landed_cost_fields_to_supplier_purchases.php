<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchases', 'shipping_accounting_treatment')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->string('shipping_accounting_treatment', 24)->default('inventory')->after('shipping_amount');
                $table->string('shipping_allocation_method', 24)->default('by_value')->after('shipping_accounting_treatment');
                $table->decimal('inventory_shipping_amount', 15, 2)->default(0)->after('shipping_allocation_method');
                $table->decimal('expense_shipping_amount', 15, 2)->default(0)->after('inventory_shipping_amount');
            });
        }

        if (! Schema::hasColumn('supplier_purchase_items', 'shipping_allocation_amount')) {
            Schema::table('supplier_purchase_items', function (Blueprint $table) {
                $table->decimal('shipping_allocation_amount', 15, 2)->default(0)->after('line_total');
                $table->decimal('landed_unit_cost', 15, 2)->default(0)->after('shipping_allocation_amount');
                $table->decimal('landed_line_total', 15, 2)->default(0)->after('landed_unit_cost');
            });
        }

        DB::table('supplier_purchases')->orderBy('id')->chunkById(200, function ($purchases): void {
            foreach ($purchases as $purchase) {
                $items = DB::table('supplier_purchase_items')
                    ->where('supplier_purchase_id', $purchase->id)
                    ->orderBy('id')
                    ->get();

                if ($items->isEmpty()) {
                    continue;
                }

                $shipping = (float) ($purchase->shipping_amount ?? 0);
                $subtotal = (float) $items->sum(fn ($item) => (float) $item->line_total);
                $allocatedSoFar = 0.0;
                $lastIndex = $items->count() - 1;

                foreach ($items->values() as $index => $item) {
                    $qty = max((int) $item->quantity, 1);
                    $lineTotal = (float) $item->line_total;
                    $allocation = 0.0;
                    if ($shipping > 0 && $subtotal > 0) {
                        $allocation = $index === $lastIndex
                            ? round($shipping - $allocatedSoFar, 2)
                            : round($shipping * ($lineTotal / $subtotal), 2);
                        $allocatedSoFar += $allocation;
                    }

                    $landedLine = round($lineTotal + $allocation, 2);
                    DB::table('supplier_purchase_items')
                        ->where('id', $item->id)
                        ->update([
                            'shipping_allocation_amount' => $allocation,
                            'landed_unit_cost' => round($landedLine / $qty, 2),
                            'landed_line_total' => $landedLine,
                        ]);
                }

                DB::table('supplier_purchases')
                    ->where('id', $purchase->id)
                    ->update([
                        'shipping_accounting_treatment' => 'inventory',
                        'shipping_allocation_method' => 'by_value',
                        'inventory_shipping_amount' => $shipping,
                        'expense_shipping_amount' => 0,
                    ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchase_items', 'shipping_allocation_amount')) {
            Schema::table('supplier_purchase_items', function (Blueprint $table) {
                $table->dropColumn(['shipping_allocation_amount', 'landed_unit_cost', 'landed_line_total']);
            });
        }

        if (Schema::hasColumn('supplier_purchases', 'shipping_accounting_treatment')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->dropColumn([
                    'shipping_accounting_treatment',
                    'shipping_allocation_method',
                    'inventory_shipping_amount',
                    'expense_shipping_amount',
                ]);
            });
        }
    }
};
