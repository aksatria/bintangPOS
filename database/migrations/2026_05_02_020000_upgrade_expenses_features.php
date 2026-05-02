<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'category')) {
                $table->string('category', 60)->default('Operasional')->after('user_id');
                $table->index('category');
            }
            if (!Schema::hasColumn('expenses', 'receipt_path')) {
                $table->string('receipt_path')->nullable()->after('note');
            }
            if (!Schema::hasColumn('expenses', 'delete_reason')) {
                $table->string('delete_reason', 255)->nullable()->after('receipt_path');
            }
            if (!Schema::hasColumn('expenses', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('expenses', 'delete_reason')) {
                $table->dropColumn('delete_reason');
            }
            if (Schema::hasColumn('expenses', 'receipt_path')) {
                $table->dropColumn('receipt_path');
            }
            if (Schema::hasColumn('expenses', 'category')) {
                $table->dropIndex(['category']);
                $table->dropColumn('category');
            }
        });
    }
};

