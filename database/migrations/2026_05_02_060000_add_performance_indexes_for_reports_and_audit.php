<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['status', 'payment_method', 'sold_at'], 'sales_status_payment_method_sold_at_idx');
            $table->index(['status', 'payment_due_at'], 'sales_status_payment_due_at_idx');
        });

        Schema::table('cashier_audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'audit_logs_action_created_at_idx');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_at_idx');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['product_id', 'created_at'], 'sale_items_product_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('sale_items_product_created_at_idx');
        });

        Schema::table('cashier_audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_created_at_idx');
            $table->dropIndex('audit_logs_user_created_at_idx');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_status_payment_method_sold_at_idx');
            $table->dropIndex('sales_status_payment_due_at_idx');
        });
    }
};
