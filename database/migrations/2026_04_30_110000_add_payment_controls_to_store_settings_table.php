<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('cash_rounding_enabled')->default(false)->after('receipt_footer');
            $table->unsignedInteger('cash_rounding_step')->default(100)->after('cash_rounding_enabled');
            $table->json('quick_pay_presets')->nullable()->after('cash_rounding_step');
            $table->json('payment_admin_fee_rules')->nullable()->after('quick_pay_presets');
            $table->json('payment_overpay_rules')->nullable()->after('payment_admin_fee_rules');
            $table->unsignedInteger('pending_non_cash_timeout_minutes')->default(30)->after('payment_overpay_rules');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'cash_rounding_enabled',
                'cash_rounding_step',
                'quick_pay_presets',
                'payment_admin_fee_rules',
                'payment_overpay_rules',
                'pending_non_cash_timeout_minutes',
            ]);
        });
    }
};
