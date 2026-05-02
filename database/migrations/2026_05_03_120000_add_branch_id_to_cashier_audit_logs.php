<?php

use App\Models\Branch;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cashier_audit_logs')) {
            return;
        }

        Schema::table('cashier_audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('cashier_audit_logs', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
                $table->index(['branch_id', 'created_at'], 'cashier_audit_logs_branch_created_idx');
            }
        });

        $defaultBranchId = Branch::query()->where('code', 'PUSAT')->value('id');
        if (! $defaultBranchId) {
            return;
        }

        $hasUserBranchColumn = Schema::hasTable('users') && Schema::hasColumn('users', 'branch_id');

        DB::table('cashier_audit_logs')
            ->select(['id', 'user_id'])
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($defaultBranchId, $hasUserBranchColumn): void {
                $userIds = collect($rows)
                    ->pluck('user_id')
                    ->filter()
                    ->unique()
                    ->values();

                $userBranchMap = [];
                if ($hasUserBranchColumn && $userIds->isNotEmpty()) {
                    $userBranchMap = DB::table('users')
                        ->whereIn('id', $userIds)
                        ->pluck('branch_id', 'id')
                        ->all();
                }

                foreach ($rows as $row) {
                    DB::table('cashier_audit_logs')
                        ->where('id', $row->id)
                        ->update([
                            'branch_id' => (int) ($userBranchMap[$row->user_id] ?? $defaultBranchId),
                        ]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cashier_audit_logs')) {
            return;
        }

        Schema::table('cashier_audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('cashier_audit_logs', 'branch_id')) {
                $table->dropIndex('cashier_audit_logs_branch_created_idx');
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
