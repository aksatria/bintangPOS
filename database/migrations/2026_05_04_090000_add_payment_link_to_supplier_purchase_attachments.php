<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchase_attachments', 'supplier_purchase_payment_id')) {
            Schema::table('supplier_purchase_attachments', function (Blueprint $table) {
                $table->foreignId('supplier_purchase_payment_id')
                    ->nullable()
                    ->after('supplier_purchase_id')
                    ->constrained('supplier_purchase_payments')
                    ->nullOnDelete();
                $table->index(['supplier_purchase_payment_id', 'kind'], 'sppa_payment_kind_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchase_attachments', 'supplier_purchase_payment_id')) {
            Schema::table('supplier_purchase_attachments', function (Blueprint $table) {
                $table->dropIndex('sppa_payment_kind_idx');
                $table->dropConstrainedForeignId('supplier_purchase_payment_id');
            });
        }
    }
};
