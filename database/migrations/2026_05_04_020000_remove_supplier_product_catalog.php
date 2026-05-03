<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplier_products')) {
            Schema::drop('supplier_products');
        }

        if (Schema::hasColumn('supplier_purchase_items', 'product_id') && DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE supplier_purchase_items DROP FOREIGN KEY supplier_purchase_items_product_id_foreign');
            DB::statement('ALTER TABLE supplier_purchase_items MODIFY product_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE supplier_purchase_items ADD CONSTRAINT supplier_purchase_items_product_id_foreign FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL');
        }
    }

    public function down(): void
    {
        // Intentionally no-op: product catalogs should not be stored per supplier.
    }
};
