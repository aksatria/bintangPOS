<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->text('receive_note')->nullable()->after('courier_name');
            $table->string('dispatch_proof_path', 255)->nullable()->after('receive_note');
            $table->string('receive_proof_path', 255)->nullable()->after('dispatch_proof_path');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->text('discrepancy_reason')->nullable()->after('received_qty');
        });
    }

    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['discrepancy_reason']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn(['receive_note', 'dispatch_proof_path', 'receive_proof_path']);
        });
    }
};

