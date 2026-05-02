<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificationSettingController;
use App\Models\AuditAlertState;
use App\Models\Branch;
use App\Models\StoreSetting;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendTelegramDailySummary extends Command
{
    protected $signature = 'telegram:send-daily-summary';

    protected $description = 'Kirim ringkasan harian POS ke Telegram sesuai jadwal pengaturan.';

    public function handle(): int
    {
        $setting = StoreSetting::query()->first();
        if (! $setting || ! $setting->telegram_enabled || ! $setting->telegram_daily_summary_enabled) {
            return self::SUCCESS;
        }

        $nowTime = now()->format('H:i');
        if ($setting->telegram_daily_summary_time !== $nowTime) {
            return self::SUCCESS;
        }

        $today = now()->toDateString();
        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $successCount = 0;
        $failedCount = 0;

        if ($branches->isEmpty()) {
            if (! $this->alreadySentForDate($today, null)) {
                $ok = TelegramNotifier::send(
                    NotificationSettingController::buildDailySummaryMessageForBranch(now(), null, true),
                    'daily_summary'
                );

                if ($ok) {
                    $this->markSentForDate($today, null);
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        } else {
            foreach ($branches as $branch) {
                if ($this->alreadySentForDate($today, (int) $branch->id)) {
                    continue;
                }

                $message = NotificationSettingController::buildDailySummaryMessageForBranch(now(), (int) $branch->id, false);
                $message = "[Cabang: {$branch->name}]\n".$message;
                $ok = TelegramNotifier::send($message, 'daily_summary');

                if ($ok) {
                    $this->markSentForDate($today, (int) $branch->id);
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }
        }

        if ($successCount > 0 && $failedCount === 0) {
            $setting->update(['telegram_daily_summary_sent_at' => now()]);
        }

        if ($failedCount > 0) {
            $this->error("Ringkasan harian: {$successCount} sukses, {$failedCount} gagal.");
        } else {
            $this->info("Ringkasan harian: {$successCount} cabang terkirim.");
        }

        return self::SUCCESS;
    }

    private function alreadySentForDate(string $date, ?int $branchId): bool
    {
        return AuditAlertState::query()
            ->where('key', $this->dailySummaryStateKey($date))
            ->where('branch_id', $branchId)
            ->exists();
    }

    private function markSentForDate(string $date, ?int $branchId): void
    {
        AuditAlertState::query()->updateOrCreate(
            [
                'key' => $this->dailySummaryStateKey($date),
                'branch_id' => $branchId,
            ],
            [
                'value' => [
                    'sent_at' => Carbon::now()->toDateTimeString(),
                ],
            ]
        );
    }

    private function dailySummaryStateKey(string $date): string
    {
        return 'telegram_daily_summary_sent_'.str_replace('-', '', $date);
    }
}
