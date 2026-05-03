<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->unsignedInteger('reserved_qty')->default(0)->after('requested_qty');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn('reserved_qty');
        });
    }
};

