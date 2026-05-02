<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_type', 30)->default('note');
            $table->string('status', 20)->default('baru'); // baru|proses|selesai|gagal
            $table->text('note')->nullable();
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('reminded_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'reminder_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_followups');
    }
};

