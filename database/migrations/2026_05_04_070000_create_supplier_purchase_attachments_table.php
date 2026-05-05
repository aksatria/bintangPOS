<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_purchase_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_purchase_id')->constrained('supplier_purchases')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 40)->default('other');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['supplier_purchase_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_purchase_attachments');
    }
};
