<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchase_payments', 'reference_number')) {
            Schema::table('supplier_purchase_payments', function (Blueprint $table) {
                $table->string('reference_number', 120)->nullable()->after('payment_method');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchase_payments', 'reference_number')) {
            Schema::table('supplier_purchase_payments', function (Blueprint $table) {
                $table->dropColumn('reference_number');
            });
        }
    }
};
