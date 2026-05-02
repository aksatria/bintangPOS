<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class SystemHealthController extends Controller
{
    use AppliesBranchScope;

    public function index()
    {
        $failedJobs = $this->safeCount('failed_jobs');
        $queueBacklog = $this->safeCount('jobs');
        $pendingApprovals = (int) $this->applyBranchScope(ApprovalRequest::query(), auth()->user())->where('status', 'pending')->count();
        $overdueApprovals = (int) $this->applyBranchScope(ApprovalRequest::query(), auth()->user())
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2))
            ->count();
        $criticalErrors1h = (int) $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->whereIn('action', ['telegram_send_failed', 'runtime_config_import_failed', 'approval_execution_failed'])
            ->where('created_at', '>=', now()->subHour())
            ->count();
        $lastHealth = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->where('action', 'ops_health_snapshot')
            ->latest('id')
            ->first();

        return view('admin.system-health', [
            'metrics' => [
                'failed_jobs' => $failedJobs,
                'queue_backlog' => $queueBacklog,
                'pending_approvals' => $pendingApprovals,
                'overdue_approvals' => $overdueApprovals,
                'critical_errors_1h' => $criticalErrors1h,
            ],
            'lastHealth' => $lastHealth,
        ]);
    }

    public function runNow(Request $request)
    {
        Artisan::call('ops:health-check --telegram');

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'ops_health_manual_run',
            'context' => ['by' => (string) ($request->user()?->email ?? '-')],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);

        return back()->with('status', 'Health check manual berhasil dijalankan.');
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
