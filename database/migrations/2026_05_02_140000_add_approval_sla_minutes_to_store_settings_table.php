<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('store_settings', 'telegram_approval_sla_minutes')) {
                $table->unsignedInteger('telegram_approval_sla_minutes')
                    ->default(120)
                    ->after('telegram_checkout_fail_threshold');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            if (Schema::hasColumn('store_settings', 'telegram_approval_sla_minutes')) {
                $table->dropColumn('telegram_approval_sla_minutes');
            }
        });
    }
};

