<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('approval_requests', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('requested_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('approval_requests', 'snoozed_until')) {
                $table->timestamp('snoozed_until')->nullable()->after('reviewed_at');
            }
            if (! Schema::hasColumn('approval_requests', 'snooze_note')) {
                $table->string('snooze_note', 255)->nullable()->after('snoozed_until');
            }
        });
    }

    public function down(): void
    {
        Schema::table('approval_requests', function (Blueprint $table) {
            if (Schema::hasColumn('approval_requests', 'snooze_note')) {
                $table->dropColumn('snooze_note');
            }
            if (Schema::hasColumn('approval_requests', 'snoozed_until')) {
                $table->dropColumn('snoozed_until');
            }
            if (Schema::hasColumn('approval_requests', 'assigned_to')) {
                $table->dropConstrainedForeignId('assigned_to');
            }
        });
    }
};

