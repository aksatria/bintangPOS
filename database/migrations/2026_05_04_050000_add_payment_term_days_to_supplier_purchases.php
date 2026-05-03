<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('supplier_purchases', 'payment_term_days')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->unsignedInteger('payment_term_days')->default(0)->after('ordered_at');
            });
        }

        if (Schema::hasColumn('supplier_purchases', 'payment_term_days')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('UPDATE supplier_purchases SET payment_term_days = GREATEST(DATEDIFF(due_date, ordered_at), 0) WHERE due_date IS NOT NULL');
            } elseif (DB::getDriverName() === 'sqlite') {
                DB::statement("UPDATE supplier_purchases SET payment_term_days = MAX(CAST(julianday(due_date) - julianday(ordered_at) AS INTEGER), 0) WHERE due_date IS NOT NULL");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supplier_purchases', 'payment_term_days')) {
            Schema::table('supplier_purchases', function (Blueprint $table) {
                $table->dropColumn('payment_term_days');
            });
        }
    }
};
