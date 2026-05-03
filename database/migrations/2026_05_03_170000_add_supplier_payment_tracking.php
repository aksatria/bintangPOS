<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_purchases', function (Blueprint $table) {
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('payment_status', 20)->default('unpaid')->after('remaining_amount');
            $table->dateTime('paid_at')->nullable()->after('payment_status');
            $table->index(['branch_id', 'payment_status', 'due_date'], 'sp_branch_payment_due_idx');
        });

        Schema::create('supplier_purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_purchase_id')->constrained('supplier_purchases')->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['supplier_purchase_id', 'paid_at'], 'spp_purchase_paid_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_payments');

        Schema::table('supplier_purchases', function (Blueprint $table) {
            $table->dropIndex('sp_branch_payment_due_idx');
            $table->dropColumn(['paid_amount', 'remaining_amount', 'payment_status', 'paid_at']);
        });
    }
};
