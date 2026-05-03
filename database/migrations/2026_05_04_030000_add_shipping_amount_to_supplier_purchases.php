<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchases', 'shipping_amount')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->decimal('shipping_amount', 15, 2)->default(0)->after('tax_amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchases', 'shipping_amount')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->dropColumn('shipping_amount');
            });
        }
    }
};
