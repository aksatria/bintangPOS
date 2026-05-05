<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchase_items', 'received_quantity')) {
            Schema::table('supplier_purchase_items', function (Blueprint $table) {
                $table->unsignedInteger('received_quantity')->default(0)->after('quantity');
                $table->unsignedInteger('returned_quantity')->default(0)->after('received_quantity');
            });
        }

        if (Schema::hasColumn('supplier_purchase_items', 'received_quantity')) {
            DB::table('supplier_purchase_items')
                ->whereIn('supplier_purchase_id', DB::table('supplier_purchases')->where('status', 'received')->select('id'))
                ->where('supplier_purchase_items.received_quantity', 0)
                ->update(['received_quantity' => DB::raw('quantity')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchase_items', 'received_quantity')) {
            Schema::table('supplier_purchase_items', function (Blueprint $table) {
                $table->dropColumn(['received_quantity', 'returned_quantity']);
            });
        }
    }
};
