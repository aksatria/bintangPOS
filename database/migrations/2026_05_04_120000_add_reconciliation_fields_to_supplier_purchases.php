<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_purchases', 'supplier_invoice_amount')) {
                $table->decimal('supplier_invoice_amount', 15, 2)->nullable()->after('delivery_note_number');
            }
            if (! Schema::hasColumn('supplier_purchases', 'reconciliation_status')) {
                $table->string('reconciliation_status', 30)->default('unchecked')->after('payment_status');
            }
            if (! Schema::hasColumn('supplier_purchases', 'reconciliation_note')) {
                $table->text('reconciliation_note')->nullable()->after('reconciliation_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_purchases', 'reconciliation_note')) {
                $table->dropColumn('reconciliation_note');
            }
            if (Schema::hasColumn('supplier_purchases', 'reconciliation_status')) {
                $table->dropColumn('reconciliation_status');
            }
            if (Schema::hasColumn('supplier_purchases', 'supplier_invoice_amount')) {
                $table->dropColumn('supplier_invoice_amount');
            }
        });
    }
};
