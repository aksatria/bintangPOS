<?php

namespace App\Http\Controllers;

use App\Exports\CashierAuditLogExport;
use App\Models\CashierAuditLog;
use App\Models\User;
use App\Enums\UserRole;
use App\Support\AppliesBranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class CashierAuditLogController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        [$from, $to, $fromDate, $toDate, $userId, $action, $q, $exportType, $sortMode] = $this->resolveFilters($request);
        $isSqlite = DB::connection()->getDriverName() === 'sqlite';

        $baseQuery = $this->buildFilteredQuery($fromDate, $toDate, $userId, $action, $q, $exportType);
        $logsQuery = (clone $baseQuery);
        if ($sortMode === 'export_priority') {
            if ($isSqlite) {
                $logs = $logsQuery->latest('id')->get()
                    ->sortByDesc(function ($log) {
                        if (! in_array((string) $log->action, ['report_export_excel', 'report_export_pdf'], true)) {
                            return 0;
                        }

                        return (float) (($log->context['selected_total'] ?? 0));
                    })
                    ->values();
                $logs = new \Illuminate\Pagination\LengthAwarePaginator(
                    $logs->forPage(request()->integer('page', 1), 30),
                    $logs->count(),
                    30,
                    request()->integer('page', 1),
                    ['path' => request()->url(), 'query' => request()->query()]
                );
            } else {
                $logs = $logsQuery->orderByRaw("
                    CASE
                        WHEN action IN ('report_export_excel', 'report_export_pdf')
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_total')) AS DECIMAL(20,2))
                        ELSE 0
                    END DESC
                ")->latest('id')->paginate(30)->withQueryString();
            }
        } else {
            $logs = $logsQuery->latest('id')->paginate(30)->withQueryString();
        }

        $cashiers = $this->applyBranchScope(User::query(), $request->user())
            ->whereIn('role', [
                UserRole::Owner->value,
                UserRole::Admin->value,
                UserRole::Cashier->value,
            ])
            ->orderBy('name')
            ->get(['id', 'name']);

        $actions = $this->applyBranchScope(CashierAuditLog::query(), $request->user())
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        $summaryQuery = $this->buildFilteredQuery($fromDate, $toDate, $userId, $action, $q, $exportType);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'hold_events' => (clone $summaryQuery)->whereIn('action', ['hold_saved', 'hold_loaded', 'hold_deleted'])->count(),
            'checkout_failed' => (clone $summaryQuery)->where('action', 'checkout_failed_client')->count(),
            'stock_adjusted' => (clone $summaryQuery)->where('action', 'stock_sync_adjusted')->count(),
            'customer_merged' => (clone $summaryQuery)->where('action', 'customer_merged')->count(),
            'expense_events' => (clone $summaryQuery)->whereIn('action', ['expense_created', 'expense_updated', 'expense_deleted', 'expense_duplicated'])->count(),
            'report_export_excel' => (clone $summaryQuery)->where('action', 'report_export_excel')->count(),
            'report_export_pdf' => (clone $summaryQuery)->where('action', 'report_export_pdf')->count(),
            'report_export_total' => 0.0,
        ];
        $latestExpenseLog = (clone $summaryQuery)
            ->whereIn('action', ['expense_created', 'expense_updated', 'expense_deleted', 'expense_duplicated'])
            ->with('user:id,name')
            ->latest('id')
            ->first();
        // Keep export intelligence panels stable: do not inherit current action/export chips.
        $exportBaseQuery = $this->buildFilteredQuery($fromDate, $toDate, $userId, '', '', 'all')
            ->whereIn('action', ['report_export_excel', 'report_export_pdf']);
        if ($isSqlite) {
            $exportRows = (clone $exportBaseQuery)->with('user:id,name')->latest('id')->get();
            $summary['report_export_total'] = (float) $exportRows->sum(fn ($row) => (float) (($row->context['selected_total'] ?? 0)));
            $topExportCashiers = $exportRows
                ->groupBy('user_id')
                ->map(function ($rows, $uid) {
                    return (object) [
                        'user_id' => (int) $uid,
                        'export_count' => $rows->count(),
                        'export_total' => (float) $rows->sum(fn ($row) => (float) (($row->context['selected_total'] ?? 0))),
                        'user' => $rows->first()?->user,
                    ];
                })
                ->sort(function ($a, $b) {
                    if ($a->export_total === $b->export_total) {
                        return $b->export_count <=> $a->export_count;
                    }

                    return $b->export_total <=> $a->export_total;
                })
                ->take(5)
                ->values();
            $exportAnomalies = $exportRows
                ->filter(function ($row) {
                    $count = (int) (($row->context['selected_count'] ?? 0));
                    $total = (float) (($row->context['selected_total'] ?? 0));
                    return $count >= 200 || $total >= 50000000;
                })
                ->take(8)
                ->values();
        } else {
            $summary['report_export_total'] = (float) (clone $summaryQuery)
                ->whereIn('action', ['report_export_excel', 'report_export_pdf'])
                ->selectRaw("COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_total')) AS DECIMAL(20,2))), 0) as export_total")
                ->value('export_total');
            $topExportCashiers = (clone $exportBaseQuery)
                ->selectRaw('user_id, COUNT(*) as export_count')
                ->selectRaw("COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_total')) AS DECIMAL(20,2))), 0) as export_total")
                ->with('user:id,name')
                ->groupBy('user_id')
                ->orderByDesc('export_total')
                ->orderByDesc('export_count')
                ->limit(5)
                ->get();
            $exportAnomalies = (clone $exportBaseQuery)
                ->where(function ($query) {
                    $query->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_count')) AS UNSIGNED) >= 200")
                        ->orWhereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_total')) AS DECIMAL(20,2)) >= 50000000");
                })
                ->latest('id')
                ->limit(8)
                ->get();
        }
        $incidentActions = [
            'ops_health_snapshot',
            'ops_health_manual_run',
            'ops_health_alert',
            'database_backup_created',
            'database_restore_dry_run',
            'database_restore_executed',
            'disaster_recovery_drill_completed',
            'rbac_temp_grants_auto_revoked_role_changed',
            'rbac_temp_grants_auto_revoked_user_deleted',
            'telegram_send_failed',
        ];
        $incidentTimeline = $this->applyBranchScope(CashierAuditLog::query(), $request->user())
            ->with('user:id,name')
            ->whereIn('action', $incidentActions)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('audit-logs.index', [
            'logs' => $logs,
            'cashiers' => $cashiers,
            'actions' => $actions,
            'from' => $from,
            'to' => $to,
            'userId' => $userId,
            'action' => $action,
            'q' => $q,
            'exportType' => $exportType,
            'sortMode' => $sortMode,
            'summary' => $summary,
            'latestExpenseLog' => $latestExpenseLog,
            'topExportCashiers' => $topExportCashiers,
            'exportAnomalies' => $exportAnomalies,
            'incidentTimeline' => $incidentTimeline,
        ]);
    }

    public function exportExcel(Request $request)
    {
        [$from, $to, $fromDate, $toDate, $userId, $action, $q, $exportType] = $this->resolveFilters($request);
        $rows = $this->buildFilteredQuery($fromDate, $toDate, $userId, $action, $q, $exportType)
            ->with('user:id,name')
            ->latest('id')
            ->get()
            ->map(fn ($log) => [
                'waktu' => $log->created_at?->format('d/m/Y H:i:s'),
                'kasir' => $log->user?->name ?? '-',
                'aksi' => $log->action,
                'level' => $this->severityOf($log->action),
                'context' => json_encode($log->context ?? [], JSON_UNESCAPED_UNICODE),
                'ip' => $log->ip_address ?? '-',
                'user_agent' => $log->user_agent ?? '-',
            ]);

        $filename = 'audit-log-kasir-'.$from.'-'.$to.'.xlsx';

        return Excel::download(new CashierAuditLogExport($rows), $filename);
    }

    public function exportPdf(Request $request)
    {
        [$from, $to, $fromDate, $toDate, $userId, $action, $q, $exportType] = $this->resolveFilters($request);
        $logs = $this->buildFilteredQuery($fromDate, $toDate, $userId, $action, $q, $exportType)
            ->with('user:id,name')
            ->latest('id')
            ->get();

        $pdf = Pdf::loadView('pdf.audit-logs', [
            'logs' => $logs,
            'from' => $from,
            'to' => $to,
            'severityOf' => fn ($actionName) => $this->severityOf((string) $actionName),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-log-kasir-'.$from.'-'.$to.'.pdf');
    }

    private function resolveFilters(Request $request): array
    {
        $from = (string) $request->query('from', now()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());
        $userId = (int) $request->integer('user_id', 0);
        $action = trim((string) $request->query('action', ''));
        $q = trim((string) $request->query('q', ''));
        $exportType = trim((string) $request->query('export_type', 'all'));
        if (! in_array($exportType, ['all', 'excel', 'pdf', 'mass'], true)) {
            $exportType = 'all';
        }
        $sortMode = trim((string) $request->query('sort', 'latest'));
        if (! in_array($sortMode, ['latest', 'export_priority'], true)) {
            $sortMode = 'latest';
        }

        try {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
        } catch (\Throwable $e) {
            $fromDate = now()->startOfDay();
            $toDate = now()->endOfDay();
            $from = $fromDate->toDateString();
            $to = $toDate->toDateString();
        }

        return [$from, $to, $fromDate, $toDate, $userId, $action, $q, $exportType, $sortMode];
    }

    private function buildFilteredQuery(Carbon $fromDate, Carbon $toDate, int $userId, string $action, string $q, string $exportType = 'all')
    {
        return $this->applyBranchScope(CashierAuditLog::query(), request()->user())
            ->with('user:id,name')
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->when($userId > 0, fn ($query) => $query->where('user_id', $userId))
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($exportType !== 'all', function ($query) use ($exportType) {
                if ($exportType === 'excel') {
                    $query->where('action', 'report_export_excel');
                } elseif ($exportType === 'pdf') {
                    $query->where('action', 'report_export_pdf');
                } elseif ($exportType === 'mass') {
                    $query->whereIn('action', ['report_export_excel', 'report_export_pdf'])
                        ->where(function ($inner) {
                            if (DB::connection()->getDriverName() === 'sqlite') {
                                $inner->whereRaw("CAST(json_extract(context, '$.selected_count') AS INTEGER) >= 200");
                            } else {
                                $inner->whereRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(context, '$.selected_count')) AS UNSIGNED) >= 200");
                            }
                        });
                }
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $jsonInvoiceExpr = DB::connection()->getDriverName() === 'sqlite'
                        ? "json_extract(context, '$.invoice')"
                        : "JSON_UNQUOTE(JSON_EXTRACT(context, '$.invoice'))";
                    $jsonHoldExpr = DB::connection()->getDriverName() === 'sqlite'
                        ? "json_extract(context, '$.hold_id')"
                        : "JSON_UNQUOTE(JSON_EXTRACT(context, '$.hold_id'))";
                    $inner->where('action', 'like', "%{$q}%")
                        ->orWhere('ip_address', 'like', "%{$q}%")
                        ->orWhere('user_agent', 'like', "%{$q}%")
                        ->orWhereRaw("{$jsonInvoiceExpr} LIKE ?", ["%{$q}%"])
                        ->orWhereRaw("{$jsonHoldExpr} LIKE ?", ["%{$q}%"])
                        ->orWhereRaw("CAST(context AS CHAR) LIKE ?", ["%{$q}%"]);
                });
            });
    }

    private function severityOf(string $action): string
    {
        return match ($action) {
            'checkout_failed_client', 'stock_sync_adjusted' => 'warning',
            'idle_warning' => 'critical',
            'customer_merged' => 'info',
            default => 'info',
        };
    }
}
