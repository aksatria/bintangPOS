<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_alert_states')) {
            return;
        }

        Schema::table('audit_alert_states', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_alert_states', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            }
        });

        // Migrate previous key namespace format "..._b{branchId}" into branch_id column.
        DB::table('audit_alert_states')
            ->select(['id', 'key'])
            ->whereNull('branch_id')
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    if (! preg_match('/^(.*)_b(\d+)$/', (string) $row->key, $matches)) {
                        continue;
                    }

                    DB::table('audit_alert_states')
                        ->where('id', $row->id)
                        ->update([
                            'key' => $matches[1],
                            'branch_id' => (int) $matches[2],
                        ]);
                }
            });

        Schema::table('audit_alert_states', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['key', 'branch_id'], 'audit_alert_states_key_branch_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_alert_states')) {
            return;
        }

        Schema::table('audit_alert_states', function (Blueprint $table) {
            $table->dropUnique('audit_alert_states_key_branch_unique');
            $table->unique('key');
            if (Schema::hasColumn('audit_alert_states', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};

