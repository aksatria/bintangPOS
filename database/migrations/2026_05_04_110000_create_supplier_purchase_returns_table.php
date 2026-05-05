<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier_purchase_returns')) {
            Schema::create('supplier_purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('supplier_purchase_id')->constrained('supplier_purchases')->cascadeOnDelete();
                $table->foreignId('supplier_purchase_item_id')->constrained('supplier_purchase_items')->cascadeOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('quantity');
                $table->decimal('unit_cost', 15, 2);
                $table->decimal('amount', 15, 2);
                $table->string('reason', 160);
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index(['supplier_purchase_id', 'created_at'], 'spr_purchase_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_returns');
    }
};
