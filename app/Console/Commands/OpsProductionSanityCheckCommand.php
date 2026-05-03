<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OpsProductionSanityCheckCommand extends Command
{
    protected $signature = 'ops:production-sanity-check {--strict : Return failure jika ada warning/critical} {--telegram : Kirim ringkasan ke Telegram saat warning/critical}';

    protected $description = 'Sanity check produksi: scheduler heartbeat, queue health, backup/drill freshness, pending approval.';

    public function handle(): int
    {
        $warnings = 0;
        $criticals = 0;

        $heartbeatAt = Cache::get('ops.scheduler.heartbeat_at');
        $heartbeatAge = $heartbeatAt ? now()->diffInMinutes(Carbon::parse((string) $heartbeatAt)) : null;
        $failedJobs = $this->safeCount('failed_jobs');
        $queueBacklog = $this->safeCount('jobs');
        $overdueApprovals = (int) ApprovalRequest::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2))
            ->count();

        $lastBackup = CashierAuditLog::query()->where('action', 'database_backup_created')->latest('id')->first();
        $lastDrill = CashierAuditLog::query()->where('action', 'disaster_recovery_drill_completed')->latest('id')->first();
        $backupAgeHours = $lastBackup?->created_at ? now()->diffInHours($lastBackup->created_at) : null;
        $drillAgeDays = $lastDrill?->created_at ? now()->diffInDays($lastDrill->created_at) : null;

        $rows = [];

        $heartbeatStatus = 'OK';
        if ($heartbeatAge === null || $heartbeatAge > 10) {
            $heartbeatStatus = 'CRITICAL';
            $criticals++;
        } elseif ($heartbeatAge > 3) {
            $heartbeatStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['scheduler_heartbeat_age_min', $heartbeatAge ?? '-', '<=3', $heartbeatStatus];

        $failedJobsStatus = 'OK';
        if ($failedJobs >= 20) {
            $failedJobsStatus = 'CRITICAL';
            $criticals++;
        } elseif ($failedJobs >= 5) {
            $failedJobsStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['failed_jobs', $failedJobs, '<5', $failedJobsStatus];

        $queueStatus = 'OK';
        if ($queueBacklog >= 300) {
            $queueStatus = 'CRITICAL';
            $criticals++;
        } elseif ($queueBacklog >= 100) {
            $queueStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['queue_backlog', $queueBacklog, '<100', $queueStatus];

        $approvalStatus = 'OK';
        if ($overdueApprovals >= 15) {
            $approvalStatus = 'CRITICAL';
            $criticals++;
        } elseif ($overdueApprovals >= 3) {
            $approvalStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['overdue_approvals_2h', $overdueApprovals, '<3', $approvalStatus];

        $backupStatus = 'OK';
        if ($backupAgeHours === null || $backupAgeHours > 48) {
            $backupStatus = 'CRITICAL';
            $criticals++;
        } elseif ($backupAgeHours > 24) {
            $backupStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['last_backup_age_hours', $backupAgeHours ?? '-', '<=24', $backupStatus];

        $drillStatus = 'OK';
        if ($drillAgeDays === null || $drillAgeDays > 45) {
            $drillStatus = 'WARN';
            $warnings++;
        }
        $rows[] = ['last_drill_age_days', $drillAgeDays ?? '-', '<=30', $drillStatus];

        $this->table(['Metric', 'Value', 'Target', 'Status'], $rows);

        $this->line("Summary: {$criticals} critical, {$warnings} warning.");
        if ($criticals > 0) {
            $this->error('Sanity check menemukan issue critical.');
        } elseif ($warnings > 0) {
            $this->warn('Sanity check menemukan warning.');
        } else {
            $this->info('Sanity check produksi aman.');
        }

        if ($this->option('strict') && ($warnings > 0 || $criticals > 0)) {
            $this->notifyTelegram($warnings, $criticals, true);
            return self::FAILURE;
        }

        $this->notifyTelegram($warnings, $criticals, false);

        return $criticals > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function notifyTelegram(int $warnings, int $criticals, bool $strictFailed): void
    {
        if (! $this->option('telegram') || ! TelegramNotifier::enabled()) {
            return;
        }
        if ($warnings === 0 && $criticals === 0) {
            return;
        }

        $fingerprint = sha1(json_encode([$warnings, $criticals, $strictFailed]));
        $dedupKey = 'ops-production-sanity:telegram:'.$fingerprint;
        if (Cache::has($dedupKey)) {
            return;
        }

        $level = $criticals > 0 ? 'CRITICAL' : 'WARNING';
        $message = implode("\n", [
            'Ops Production Sanity Check',
            'Level: '.$level,
            'Warnings: '.$warnings,
            'Criticals: '.$criticals,
            'Strict mode: '.($strictFailed ? 'FAIL' : 'PASS'),
            'At: '.now()->format('Y-m-d H:i:s'),
        ]);

        TelegramNotifier::send($message, 'ops_production_sanity_alert', TelegramNotifier::defaultChatId());
        Cache::put($dedupKey, 1, now()->addMinutes(15));
    }

    private function safeCount(string $table): int
    {
        try {
            return (int) DB::table($table)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
