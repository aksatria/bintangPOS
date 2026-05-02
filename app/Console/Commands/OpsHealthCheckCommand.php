<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OpsHealthCheckCommand extends Command
{
    protected $signature = 'ops:health-check {--telegram : Kirim alert Telegram saat anomali}';

    protected $description = 'Cek kesehatan operasional: failed jobs, queue backlog, SLA overdue, error audit.';

    public function handle(): int
    {
        $failedJobs = $this->safeCount('failed_jobs');
        $queueBacklog = $this->safeCount('jobs');
        $overdueApprovals = (int) ApprovalRequest::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2))
            ->count();
        $criticalAuditErrors = (int) CashierAuditLog::query()
            ->whereIn('action', ['telegram_send_failed', 'runtime_config_import_failed', 'approval_execution_failed'])
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $thresholdFailedJobs = (int) env('OPS_ALERT_FAILED_JOBS_THRESHOLD', 5);
        $thresholdQueueBacklog = (int) env('OPS_ALERT_QUEUE_BACKLOG_THRESHOLD', 100);
        $thresholdOverdueApprovals = (int) env('OPS_ALERT_OVERDUE_APPROVALS_THRESHOLD', 3);
        $thresholdCriticalErrors = (int) env('OPS_ALERT_CRITICAL_ERRORS_THRESHOLD', 3);

        $isUnhealthy = $failedJobs >= $thresholdFailedJobs
            || $queueBacklog >= $thresholdQueueBacklog
            || $overdueApprovals >= $thresholdOverdueApprovals
            || $criticalAuditErrors >= $thresholdCriticalErrors;

        $this->table(['Metric', 'Value', 'Threshold'], [
            ['failed_jobs', $failedJobs, $thresholdFailedJobs],
            ['queue_backlog', $queueBacklog, $thresholdQueueBacklog],
            ['overdue_approvals', $overdueApprovals, $thresholdOverdueApprovals],
            ['critical_audit_errors_1h', $criticalAuditErrors, $thresholdCriticalErrors],
        ]);

        CashierAuditLog::query()->create([
            'user_id' => null,
            'action' => 'ops_health_snapshot',
            'context' => [
                'failed_jobs' => $failedJobs,
                'queue_backlog' => $queueBacklog,
                'overdue_approvals' => $overdueApprovals,
                'critical_audit_errors_1h' => $criticalAuditErrors,
                'is_unhealthy' => $isUnhealthy,
            ],
            'ip_address' => 'cli',
            'user_agent' => 'artisan',
        ]);

        if ($isUnhealthy && $this->option('telegram') && TelegramNotifier::enabled()) {
            $fingerprint = sha1(json_encode([
                $failedJobs >= $thresholdFailedJobs,
                $queueBacklog >= $thresholdQueueBacklog,
                $overdueApprovals >= $thresholdOverdueApprovals,
                $criticalAuditErrors >= $thresholdCriticalErrors,
            ]));
            $dedupSeconds = max(120, (int) env('OPS_ALERT_DEDUP_SECONDS', 900));
            $dedupKey = 'ops-health-alert:dedup:'.$fingerprint;
            $consecutiveKey = 'ops-health-alert:consecutive';
            $consecutive = (int) Cache::get($consecutiveKey, 0) + 1;
            Cache::put($consecutiveKey, $consecutive, now()->addHours(6));

            $escalationLevel = $consecutive >= 6 ? 3 : ($consecutive >= 3 ? 2 : 1);
            if (! Cache::has($dedupKey)) {
                $sent = TelegramNotifier::send(
                    implode("\n", [
                        'ALERT Ops Health Tidak Sehat',
                        "Escalation Level: L{$escalationLevel}",
                        "Consecutive unhealthy: {$consecutive}",
                        "failed_jobs: {$failedJobs} (th {$thresholdFailedJobs})",
                        "queue_backlog: {$queueBacklog} (th {$thresholdQueueBacklog})",
                        "overdue_approvals: {$overdueApprovals} (th {$thresholdOverdueApprovals})",
                        "critical_errors_1h: {$criticalAuditErrors} (th {$thresholdCriticalErrors})",
                    ]),
                    'ops_health_alert',
                    TelegramNotifier::defaultChatId()
                );
                CashierAuditLog::query()->create([
                    'user_id' => null,
                    'action' => 'ops_health_alert',
                    'context' => [
                        'sent' => (bool) $sent,
                        'escalation_level' => (int) $escalationLevel,
                        'consecutive_unhealthy' => (int) $consecutive,
                        'fingerprint' => $fingerprint,
                    ],
                    'ip_address' => 'cli',
                    'user_agent' => 'artisan',
                ]);
                Cache::put($dedupKey, 1, now()->addSeconds($dedupSeconds));
            }
        } else {
            Cache::forget('ops-health-alert:consecutive');
        }

        return $isUnhealthy ? self::FAILURE : self::SUCCESS;
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
