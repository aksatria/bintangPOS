<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignId('source_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('product_name', 180);
            $table->string('unit', 40)->nullable();
            $table->unsignedInteger('requested_qty');
            $table->unsignedInteger('received_qty')->nullable();
            $table->timestamps();

            $table->index(['stock_transfer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};

