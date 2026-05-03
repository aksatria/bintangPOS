<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('number', 50)->unique();
            $table->date('debt_date');
            $table->date('due_date')->nullable();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('status', 20)->default('active');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status', 'due_date']);
        });

        Schema::create('customer_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_debt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('paid_at');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 30)->default('cash');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['customer_debt_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_debt_payments');
        Schema::dropIfExists('customer_debts');
    }
};
