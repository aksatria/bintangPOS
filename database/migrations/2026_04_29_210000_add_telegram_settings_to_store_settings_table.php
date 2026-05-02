<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->boolean('telegram_enabled')->default(false)->after('receipt_footer');
            $table->boolean('telegram_notify_checkout_anomaly')->default(true)->after('telegram_enabled');
            $table->unsignedInteger('telegram_checkout_fail_threshold')->default(5)->after('telegram_notify_checkout_anomaly');
            $table->boolean('telegram_daily_summary_enabled')->default(false)->after('telegram_checkout_fail_threshold');
            $table->string('telegram_daily_summary_time', 5)->default('21:00')->after('telegram_daily_summary_enabled');
            $table->string('telegram_override_chat_id', 40)->nullable()->after('telegram_daily_summary_time');
            $table->timestamp('telegram_daily_summary_sent_at')->nullable()->after('telegram_override_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_enabled',
                'telegram_notify_checkout_anomaly',
                'telegram_checkout_fail_threshold',
                'telegram_daily_summary_enabled',
                'telegram_daily_summary_time',
                'telegram_override_chat_id',
                'telegram_daily_summary_sent_at',
            ]);
        });
    }
};

