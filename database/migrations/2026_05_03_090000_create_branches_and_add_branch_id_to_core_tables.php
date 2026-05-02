<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 50)->unique();
            $table->string('address', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        foreach (['users', 'products', 'sales', 'expenses', 'pos_holds', 'stock_opnames', 'cash_reconciliations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
                $table->index('branch_id');
            });
        }

        $defaultBranchId = DB::table('branches')->insertGetId([
            'name' => 'Pusat',
            'code' => 'PUSAT',
            'address' => null,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('products')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);

        DB::table('sales')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('expenses')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('pos_holds')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('stock_opnames')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
        DB::table('cash_reconciliations')->whereNull('branch_id')->update(['branch_id' => $defaultBranchId]);
    }

    public function down(): void
    {
        foreach (['cash_reconciliations', 'stock_opnames', 'pos_holds', 'expenses', 'sales', 'products', 'users'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex([$table->getTable().'_branch_id_index']);
                $table->dropColumn('branch_id');
            });
        }

        Schema::dropIfExists('branches');
    }
};
