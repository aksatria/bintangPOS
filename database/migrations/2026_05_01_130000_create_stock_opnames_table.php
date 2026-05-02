<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('adjusted_items')->default(0);
            $table->bigInteger('total_difference')->default(0);
            $table->text('note')->nullable();
            $table->text('posted_note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};

