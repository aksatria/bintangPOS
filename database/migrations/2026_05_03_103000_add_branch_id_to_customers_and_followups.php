<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->index('branch_id');
        });

        Schema::table('customer_followups', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->index('branch_id');
        });

        $defaultBranchId = (int) (DB::table('branches')->orderBy('id')->value('id') ?? 0);
        if ($defaultBranchId <= 0) {
            return;
        }

        DB::table('customers')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('customer_followups')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
    }

    public function down(): void
    {
        Schema::table('customer_followups', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex('customer_followups_branch_id_index');
            $table->dropColumn('branch_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropIndex('customers_branch_id_index');
            $table->dropColumn('branch_id');
        });
    }
};

