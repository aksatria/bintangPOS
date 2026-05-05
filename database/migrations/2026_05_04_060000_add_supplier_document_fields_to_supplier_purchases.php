<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_purchases', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number', 80)->nullable()->after('number');
            }
            if (! Schema::hasColumn('supplier_purchases', 'delivery_note_number')) {
                $table->string('delivery_note_number', 80)->nullable()->after('supplier_invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_purchases', 'delivery_note_number')) {
                $table->dropColumn('delivery_note_number');
            }
            if (Schema::hasColumn('supplier_purchases', 'supplier_invoice_number')) {
                $table->dropColumn('supplier_invoice_number');
            }
        });
    }
};
