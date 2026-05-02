<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('rounding_amount', 15, 2)->default(0)->after('tax_amount');
            $table->decimal('admin_fee_amount', 15, 2)->default(0)->after('rounding_amount');
            $table->timestamp('payment_due_at')->nullable()->after('sold_at');
            $table->unsignedInteger('payment_attempt_count')->default(0)->after('payment_due_at');
            $table->json('payment_attempt_logs')->nullable()->after('payment_attempt_count');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'rounding_amount',
                'admin_fee_amount',
                'payment_due_at',
                'payment_attempt_count',
                'payment_attempt_logs',
            ]);
        });
    }
};
