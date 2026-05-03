<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('destination_branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 40)->unique();
            $table->string('status', 20)->default('requested');
            $table->text('note')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['source_branch_id', 'status']);
            $table->index(['destination_branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};

