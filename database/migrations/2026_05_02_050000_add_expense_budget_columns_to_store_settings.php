<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('store_settings', 'expense_daily_budget')) {
                $table->decimal('expense_daily_budget', 15, 2)->default(1000000)->after('stock_opname_outlier_threshold');
            }
            if (! Schema::hasColumn('store_settings', 'expense_monthly_budget')) {
                $table->decimal('expense_monthly_budget', 15, 2)->default(30000000)->after('expense_daily_budget');
            }
            if (! Schema::hasColumn('store_settings', 'expense_large_threshold')) {
                $table->decimal('expense_large_threshold', 15, 2)->default(1000000)->after('expense_monthly_budget');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (Schema::hasColumn('store_settings', 'expense_large_threshold')) {
                $table->dropColumn('expense_large_threshold');
            }
            if (Schema::hasColumn('store_settings', 'expense_monthly_budget')) {
                $table->dropColumn('expense_monthly_budget');
            }
            if (Schema::hasColumn('store_settings', 'expense_daily_budget')) {
                $table->dropColumn('expense_daily_budget');
            }
        });
    }
};

