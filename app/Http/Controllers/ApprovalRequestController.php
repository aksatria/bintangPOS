<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\ApprovalRequest;
use App\Models\CashierAuditLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchasePayment;
use App\Models\User;
use App\Support\AppliesBranchScope;
use App\Support\BusinessHourSla;
use App\Support\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalRequestController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $user = $request->user();
        $pref = (array) data_get((array) ($user?->ui_preferences ?? []), 'approval_queue', []);
        $filterKeys = ['status', 'type', 'priority', 'execution', 'result_tag', 'pending_order', 'assignee', 'hide_snoozed'];
        $hasFilterQuery = collect($filterKeys)->contains(fn ($k) => $request->query($k) !== null);

        $status = (string) $request->query('status', $hasFilterQuery ? 'pending' : ((string) ($pref['status'] ?? 'pending')));
        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }
        $type = (string) $request->query('type', $hasFilterQuery ? 'all' : ((string) ($pref['type'] ?? 'all')));
        $priority = (string) $request->query('priority', $hasFilterQuery ? 'all' : ((string) ($pref['priority'] ?? 'all')));
        $execution = (string) $request->query('execution', $hasFilterQuery ? 'all' : ((string) ($pref['execution'] ?? 'all')));
        $resultTag = (string) $request->query('result_tag', $hasFilterQuery ? 'all' : ((string) ($pref['result_tag'] ?? 'all')));
        $pendingOrder = (string) $request->query('pending_order', $hasFilterQuery ? 'oldest' : ((string) ($pref['pending_order'] ?? 'oldest')));
        $assignee = (string) $request->query('assignee', $hasFilterQuery ? 'all' : ((string) ($pref['assignee'] ?? 'all')));
        $hideSnoozed = $request->query('hide_snoozed') !== null
            ? $request->boolean('hide_snoozed')
            : (!$hasFilterQuery && ! empty($pref['hide_snoozed']));

        if ($user && $hasFilterQuery) {
            $prefs = (array) ($user->ui_preferences ?? []);
            $prefs['approval_queue'] = [
                'status' => $status,
                'type' => $type,
                'priority' => $priority,
                'execution' => $execution,
                'result_tag' => $resultTag,
                'pending_order' => $pendingOrder,
                'assignee' => $assignee,
                'hide_snoozed' => $hideSnoozed,
            ];
            $user->ui_preferences = $prefs;
            $user->save();
        }

        $query = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->with(['requester:id,name', 'reviewer:id,name', 'assignee:id,name']);
        $store = StoreSetting::query()->first();
        $approvalRules = is_array($store?->approval_rules) ? $store->approval_rules : [];
        $saleSlaMinutes = max(5, (int) data_get($approvalRules, 'sla_minutes_sale', 120));
        $exportSlaMinutes = max(5, (int) data_get($approvalRules, 'sla_minutes_export', 360));
        $businessHourRules = [
            'start' => (string) data_get($approvalRules, 'business_hours.start', '08:00'),
            'end' => (string) data_get($approvalRules, 'business_hours.end', '22:00'),
            'workdays' => (array) data_get($approvalRules, 'business_hours.workdays', [1, 2, 3, 4, 5, 6, 7]),
        ];
        $saleHighMinutes = max(5, (int) floor($saleSlaMinutes / 2));

        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($type !== 'all') {
            $query->where('type', $type);
        }
        if ($priority !== 'all') {
            $query->whereRaw($this->prioritySql($saleHighMinutes, $saleSlaMinutes, $exportSlaMinutes).' = ?', [$priority]);
        }
        if ($execution !== 'all') {
            $query->where('status', 'approved')
                ->where('type', 'like', 'report.export.%');
            if ($execution === 'pending') {
                $query->whereNull('payload->export_executed_at');
            } elseif ($execution === 'done') {
                $query->whereNotNull('payload->export_executed_at');
            }
        }
        if ($resultTag === 'auto_expired') {
            $query->where('status', 'rejected')
                ->where('review_note', 'like', '[AUTO-EXPIRED]%');
        } elseif ($resultTag === 'executed') {
            $query->where('status', 'approved')
                ->where('type', 'like', 'report.export.%')
                ->whereNotNull('payload->export_executed_at');
        } elseif ($resultTag === 'not_executed') {
            $query->where('status', 'approved')
                ->where('type', 'like', 'report.export.%')
                ->whereNull('payload->export_executed_at');
        }
        if ($assignee === 'mine') {
            $query->where('assigned_to', (int) ($request->user()?->id ?? 0));
        } elseif ($assignee === 'unassigned') {
            $query->whereNull('assigned_to');
        }
        if ($hideSnoozed) {
            $query->where(function ($q) {
                $q->whereNull('snoozed_until')
                    ->orWhere('snoozed_until', '<=', now());
            });
        }

        $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC");
        $query->orderByRaw("CASE ".$this->prioritySql($saleHighMinutes, $saleSlaMinutes, $exportSlaMinutes)." WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'normal' THEN 2 ELSE 3 END ASC");
        if ($pendingOrder === 'newest') {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN created_at END DESC");
        } else {
            $query->orderByRaw("CASE WHEN status = 'pending' THEN created_at END ASC");
        }
        $query->orderByDesc('id');
        $rejectReasonPresets = (array) data_get($approvalRules, 'reject_reason_presets', StoreSetting::DEFAULT_APPROVAL_RULES['reject_reason_presets']);
        $settingSla = (int) ($store?->telegram_approval_sla_minutes ?? 0);
        $slaMinutes = $settingSla > 0
            ? max(5, $settingSla)
            : max(5, (int) env('APPROVAL_SLA_ESCALATION_MINUTES', 120));
        $pendingRows = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('status', 'pending')
            ->get(['id', 'type', 'created_at', 'assigned_to']);
        $pendingBusinessMinutes = [];
        foreach ($pendingRows as $pRow) {
            $pendingBusinessMinutes[(int) $pRow->id] = BusinessHourSla::diffInBusinessMinutes(
                $pRow->created_at,
                now(),
                $businessHourRules
            );
        }
        $pendingCount = (int) $pendingRows->count();
        $overdueCount = (int) $pendingRows->filter(function ($row) use ($pendingBusinessMinutes, $saleSlaMinutes, $exportSlaMinutes) {
            $minutes = (int) ($pendingBusinessMinutes[(int) $row->id] ?? 0);
            $isSaleType = in_array((string) $row->type, ['sale.quick_refund', 'sale.quick_void'], true);
            $threshold = $isSaleType ? $saleSlaMinutes : $exportSlaMinutes;
            return $minutes >= $threshold;
        })->count();
        $approvedNotExecutedCount = (int) $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('status', 'approved')
            ->where('type', 'like', 'report.export.%')
            ->whereNull('payload->export_executed_at')
            ->count();
        $reviewedLast7 = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->whereIn('status', ['approved', 'rejected'])
            ->where('reviewed_at', '>=', now()->subDays(7));
        $reviewedLast7Count = (int) (clone $reviewedLast7)->count();
        $rejectedLast7Count = (int) (clone $reviewedLast7)->where('status', 'rejected')->count();
        $reviewedRows = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->get(['created_at', 'reviewed_at']);
        $avgReviewMinutes = (int) round((float) $reviewedRows
            ->map(fn ($row) => BusinessHourSla::diffInBusinessMinutes($row->created_at, $row->reviewed_at, $businessHourRules))
            ->avg());
        $autoExpiredLast7Count = (int) $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('status', 'rejected')
            ->where('reviewed_at', '>=', now()->subDays(7))
            ->where('review_note', 'like', '[AUTO-EXPIRED]%')
            ->count();
        $pendingAgingBuckets = [
            'lt30' => (int) $pendingRows->filter(fn ($r) => ((int) ($pendingBusinessMinutes[(int) $r->id] ?? 0)) < 30)->count(),
            'm30_120' => (int) $pendingRows->filter(function ($r) use ($pendingBusinessMinutes) {
                $m = (int) ($pendingBusinessMinutes[(int) $r->id] ?? 0);
                return $m >= 30 && $m < 120;
            })->count(),
            'm120_240' => (int) $pendingRows->filter(function ($r) use ($pendingBusinessMinutes) {
                $m = (int) ($pendingBusinessMinutes[(int) $r->id] ?? 0);
                return $m >= 120 && $m < 240;
            })->count(),
            'gt240' => (int) $pendingRows->filter(fn ($r) => ((int) ($pendingBusinessMinutes[(int) $r->id] ?? 0)) >= 240)->count(),
        ];
        $reviewerWorkloads = $this->applyBranchScope(User::query(), $user)
            ->whereIn('role', ['owner', 'admin'])
            ->orderBy('name')
            ->get(['id', 'name', 'role'])
            ->map(function (User $reviewer) use ($saleSlaMinutes, $exportSlaMinutes, $pendingRows, $pendingBusinessMinutes, $businessHourRules, $user) {
                $basePendingRows = $pendingRows->filter(fn ($r) => (int) ($r->assigned_to ?? 0) === (int) $reviewer->id);
                $pending = (int) $basePendingRows->count();
                $overdue = (int) $basePendingRows->filter(function ($r) use ($pendingBusinessMinutes, $saleSlaMinutes, $exportSlaMinutes) {
                    $minutes = (int) ($pendingBusinessMinutes[(int) $r->id] ?? 0);
                    $isSaleType = in_array((string) $r->type, ['sale.quick_refund', 'sale.quick_void'], true);
                    $threshold = $isSaleType ? $saleSlaMinutes : $exportSlaMinutes;
                    return $minutes >= $threshold;
                })->count();
                $avgReview = (int) round((float) $this->applyBranchScope(ApprovalRequest::query(), $user)
                    ->whereIn('status', ['approved', 'rejected'])
                    ->where('reviewed_by', (int) $reviewer->id)
                    ->whereNotNull('reviewed_at')
                    ->where('reviewed_at', '>=', now()->subDays(7))
                    ->get(['created_at', 'reviewed_at'])
                    ->map(fn ($row) => BusinessHourSla::diffInBusinessMinutes($row->created_at, $row->reviewed_at, $businessHourRules))
                    ->avg());

                return [
                    'name' => (string) $reviewer->name,
                    'role' => (string) ($reviewer->role?->value ?? $reviewer->role ?? '-'),
                    'pending' => (int) $pending,
                    'overdue' => (int) $overdue,
                    'avg_review_minutes' => $avgReview,
                ];
            })
            ->sortByDesc(fn (array $row) => [$row['overdue'], $row['pending']])
            ->values()
            ->take(6);
        $actionTemplates = (array) data_get($approvalRules, 'action_templates', [
            'refund_fast' => ['label' => 'Template Refund', 'approve_note' => 'Refund disetujui sesuai bukti transaksi.', 'reject_note' => 'Refund ditolak: dokumen pendukung belum lengkap.', 'snooze_note' => 'Tunda untuk verifikasi tambahan.'],
            'void_fast' => ['label' => 'Template Void', 'approve_note' => 'Void disetujui karena transaksi belum settle.', 'reject_note' => 'Void ditolak: transaksi sudah tidak memenuhi syarat.', 'snooze_note' => 'Tunda untuk klarifikasi petugas.'],
            'export_large' => ['label' => 'Template Export', 'approve_note' => 'Export disetujui untuk kebutuhan audit.', 'reject_note' => 'Export ditolak: alasan belum sesuai kebijakan.', 'snooze_note' => 'Tunda sambil validasi scope data.'],
        ]);

        $reviewedLast14 = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '>=', now()->subDays(14))
            ->get(['created_at', 'reviewed_at']);
        $weekdayHeat = collect(range(1, 7))->mapWithKeys(function ($day) use ($reviewedLast14, $businessHourRules) {
            $items = $reviewedLast14->filter(fn ($r) => (int) $r->reviewed_at?->dayOfWeekIso === $day);
            $avg = (int) round((float) $items->map(fn ($r) => BusinessHourSla::diffInBusinessMinutes($r->created_at, $r->reviewed_at, $businessHourRules))->avg());
            return [$day => ['count' => (int) $items->count(), 'avg' => $avg]];
        })->all();
        $hourHeat = collect(range(8, 22))->mapWithKeys(function ($h) use ($reviewedLast14, $businessHourRules) {
            $items = $reviewedLast14->filter(fn ($r) => (int) $r->reviewed_at?->hour === $h);
            $avg = (int) round((float) $items->map(fn ($r) => BusinessHourSla::diffInBusinessMinutes($r->created_at, $r->reviewed_at, $businessHourRules))->avg());
            return [$h => ['count' => (int) $items->count(), 'avg' => $avg]];
        })->all();
        [$weeklyTrend, $monthlyTrend] = $this->buildSlaTrends($businessHourRules);
        $lastEscalationRun = $this->applyBranchScope(CashierAuditLog::query(), $request->user())
            ->where('action', 'approval_sla_escalation_run')
            ->latest('id')
            ->first(['created_at', 'context']);

        return view('approvals.index', [
            'rows' => $query->paginate(20)->withQueryString(),
            'status' => $status,
            'type' => $type,
            'priority' => $priority,
            'execution' => in_array($execution, ['all', 'pending', 'done'], true) ? $execution : 'all',
            'result_tag' => in_array($resultTag, ['all', 'auto_expired', 'executed', 'not_executed'], true) ? $resultTag : 'all',
            'pending_order' => in_array($pendingOrder, ['oldest', 'newest'], true) ? $pendingOrder : 'oldest',
            'assignee' => in_array($assignee, ['all', 'mine', 'unassigned'], true) ? $assignee : 'all',
            'hide_snoozed' => $hideSnoozed,
            'types' => $this->applyBranchScope(ApprovalRequest::query(), $request->user())->select('type')->distinct()->orderBy('type')->pluck('type'),
            'approvers' => $this->applyBranchScope(User::query(), $request->user())->whereIn('role', ['owner', 'admin'])->orderBy('name')->get(['id', 'name']),
            'rejectReasonPresets' => collect($rejectReasonPresets)->filter(fn ($x) => trim((string) $x) !== '')->values(),
            'reviewerWorkloads' => $reviewerWorkloads,
            'actionTemplates' => $actionTemplates,
            'slaWeekdayHeat' => $weekdayHeat,
            'slaHourHeat' => $hourHeat,
            'slaTrendWeekly' => $weeklyTrend,
            'slaTrendMonthly' => $monthlyTrend,
            'lastEscalationRun' => $lastEscalationRun,
            'summary' => [
                'pending' => $pendingCount,
                'overdue' => $overdueCount,
                'approved_not_executed' => $approvedNotExecutedCount,
                'sla_minutes' => $slaMinutes,
                'avg_review_minutes_last_7d' => $avgReviewMinutes,
                'reject_rate_last_7d' => $reviewedLast7Count > 0 ? round(($rejectedLast7Count / $reviewedLast7Count) * 100, 1) : 0,
                'auto_expired_last_7d' => $autoExpiredLast7Count,
                'pending_aging_buckets' => $pendingAgingBuckets,
                'sla_minutes_sale' => $saleSlaMinutes,
                'sla_minutes_export' => $exportSlaMinutes,
                'business_hours' => $businessHourRules,
            ],
        ]);
    }

    private function buildSlaTrends(array $businessHourRules): array
    {
        $weekly = [];
        for ($i = 7; $i >= 0; $i--) {
            $weekStart = now()->startOfWeek()->subWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek();
            $rows = $this->applyBranchScope(ApprovalRequest::query(), auth()->user())
                ->whereIn('status', ['approved', 'rejected'])
                ->whereNotNull('reviewed_at')
                ->whereBetween('reviewed_at', [$weekStart, $weekEnd])
                ->get(['status', 'review_note', 'created_at', 'reviewed_at']);
            $total = (int) $rows->count();
            $rejected = (int) $rows->where('status', 'rejected')->count();
            $autoExpired = (int) $rows->filter(fn ($r) => $r->status === 'rejected' && str_starts_with((string) ($r->review_note ?? ''), '[AUTO-EXPIRED]'))->count();
            $avg = (int) round((float) $rows->map(fn ($r) => BusinessHourSla::diffInBusinessMinutes($r->created_at, $r->reviewed_at, $businessHourRules))->avg());
            $weekly[] = [
                'label' => $weekStart->format('d M'),
                'reviewed' => $total,
                'reject_rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0.0,
                'avg_review' => $avg,
                'auto_expired' => $autoExpired,
            ];
        }

        $monthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $mStart = now()->startOfMonth()->subMonths($i);
            $mEnd = $mStart->copy()->endOfMonth();
            $rows = $this->applyBranchScope(ApprovalRequest::query(), auth()->user())
                ->whereIn('status', ['approved', 'rejected'])
                ->whereNotNull('reviewed_at')
                ->whereBetween('reviewed_at', [$mStart, $mEnd])
                ->get(['status', 'review_note', 'created_at', 'reviewed_at']);
            $total = (int) $rows->count();
            $rejected = (int) $rows->where('status', 'rejected')->count();
            $autoExpired = (int) $rows->filter(fn ($r) => $r->status === 'rejected' && str_starts_with((string) ($r->review_note ?? ''), '[AUTO-EXPIRED]'))->count();
            $avg = (int) round((float) $rows->map(fn ($r) => BusinessHourSla::diffInBusinessMinutes($r->created_at, $r->reviewed_at, $businessHourRules))->avg());
            $monthly[] = [
                'label' => $mStart->format('M Y'),
                'reviewed' => $total,
                'reject_rate' => $total > 0 ? round(($rejected / $total) * 100, 1) : 0.0,
                'avg_review' => $avg,
                'auto_expired' => $autoExpired,
            ];
        }

        return [$weekly, $monthly];
    }

    private function prioritySql(int $saleHighMinutes, int $saleSlaMinutes, int $exportSlaMinutes): string
    {
        $saleCriticalAt = now()->subMinutes($saleSlaMinutes)->toDateTimeString();
        $saleHighAt = now()->subMinutes($saleHighMinutes)->toDateTimeString();
        $exportHighAt = now()->subMinutes($exportSlaMinutes)->toDateTimeString();

        return "CASE
            WHEN status <> 'pending' THEN 'done'
            WHEN type IN ('sale.quick_refund','sale.quick_void') AND created_at <= '{$saleCriticalAt}' THEN 'critical'
            WHEN type IN ('sale.quick_refund','sale.quick_void') AND created_at <= '{$saleHighAt}' THEN 'high'
            WHEN type LIKE 'report.export.%' AND created_at <= '{$exportHighAt}' THEN 'high'
            ELSE 'normal'
        END";
    }

    public function approve(Request $request, ApprovalRequest $approval)
    {
        return $this->withApprovalLock($approval->id, function () use ($request, $approval) {
            if ($approval->status !== 'pending') {
                return back()->with('error', 'Approval ini sudah diproses sebelumnya.');
            }

            $validated = $request->validate([
                'review_note' => ['nullable', 'string', 'max:500'],
            ]);

            DB::transaction(function () use ($approval, $request, $validated): void {
                $approval->refresh();
                if ($approval->status !== 'pending') {
                    throw ValidationException::withMessages([
                        'review_note' => 'Approval sudah diproses user lain.',
                    ]);
                }

                $this->executeApproval($approval, $request);

                $approval->status = 'approved';
                $approval->review_note = trim((string) ($validated['review_note'] ?? ''));
                $approval->reviewed_by = $request->user()?->id;
                $approval->reviewed_at = now();
                $approval->save();

                $this->sendApprovalTelegram('approved', $approval, $request->user()?->name, $approval->review_note);
            });

            $response = back()->with('success', 'Approval disetujui dan aksi berhasil dieksekusi.');
            if (str_starts_with((string) $approval->type, 'report.export.')) {
                $response->with('approval_export_execute_url', route('admin.approvals.execute-export', $approval));
            }

            return $response;
        });
    }

    public function reject(Request $request, ApprovalRequest $approval)
    {
        return $this->withApprovalLock($approval->id, function () use ($request, $approval) {
            if ($approval->status !== 'pending') {
                return back()->with('error', 'Approval ini sudah diproses sebelumnya.');
            }

            $validated = $request->validate([
                'review_note' => ['required', 'string', 'min:5', 'max:500'],
            ]);

            $approval->status = 'rejected';
            $approval->review_note = trim((string) $validated['review_note']);
            $approval->reviewed_by = $request->user()?->id;
            $approval->reviewed_at = now();
            $approval->save();

            $this->sendApprovalTelegram('rejected', $approval, $request->user()?->name, $approval->review_note);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'approval_request_rejected',
                'context' => [
                    'approval_id' => $approval->id,
                    'type' => $approval->type,
                    'title' => $approval->title,
                    'review_note' => $approval->review_note,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);

            return back()->with('success', 'Approval ditolak.');
        });
    }

    public function approveBulk(Request $request)
    {
        $validated = $request->validate([
            'approval_ids' => ['required', 'array', 'min:1', 'max:200'],
            'approval_ids.*' => ['integer', 'min:1', 'distinct'],
            'review_note' => ['nullable', 'string', 'max:500'],
        ]);

        $ids = collect($validated['approval_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $done = 0;

        $key = 'approval-bulk-approve-'.sha1(json_encode($ids->all()));
        return $this->withNamedLock($key, function () use ($ids, $request, $validated, &$done) {
            DB::transaction(function () use ($ids, $request, $validated, &$done): void {
            $rows = $this->applyBranchScope(ApprovalRequest::query(), request()->user())->whereIn('id', $ids)->lockForUpdate()->get();
                foreach ($rows as $approval) {
                    if ($approval->status !== 'pending') {
                        continue;
                    }
                    $this->executeApproval($approval, $request);
                    $approval->status = 'approved';
                    $approval->review_note = trim((string) ($validated['review_note'] ?? ''));
                    $approval->reviewed_by = $request->user()?->id;
                    $approval->reviewed_at = now();
                    $approval->save();
                    $done++;
                }
            });

            if ($done > 0) {
                TelegramNotifier::send(
                    "Bulk approval selesai.\nTotal approved: {$done}\nOleh: ".($request->user()?->name ?? 'System'),
                    'approval_bulk_approved',
                    TelegramNotifier::defaultChatId()
                );
            }

            return back()->with('success', "Bulk approve berhasil untuk {$done} request.");
        });
    }

    public function rejectBulk(Request $request)
    {
        $validated = $request->validate([
            'approval_ids' => ['required', 'array', 'min:1', 'max:200'],
            'approval_ids.*' => ['integer', 'min:1', 'distinct'],
            'review_note' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $ids = collect($validated['approval_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $done = 0;

        $key = 'approval-bulk-reject-'.sha1(json_encode($ids->all()));
        return $this->withNamedLock($key, function () use ($ids, $request, $validated, &$done) {
            DB::transaction(function () use ($ids, $request, $validated, &$done): void {
            $rows = $this->applyBranchScope(ApprovalRequest::query(), request()->user())->whereIn('id', $ids)->lockForUpdate()->get();
                foreach ($rows as $approval) {
                    if ($approval->status !== 'pending') {
                        continue;
                    }
                    $approval->status = 'rejected';
                    $approval->review_note = trim((string) $validated['review_note']);
                    $approval->reviewed_by = $request->user()?->id;
                    $approval->reviewed_at = now();
                    $approval->save();
                    $done++;
                }
            });

            if ($done > 0) {
                TelegramNotifier::send(
                    "Bulk reject selesai.\nTotal rejected: {$done}\nOleh: ".($request->user()?->name ?? 'System')."\nCatatan: ".trim((string) $validated['review_note']),
                    'approval_bulk_rejected',
                    TelegramNotifier::defaultChatId()
                );
            }

            return back()->with('success', "Bulk reject berhasil untuk {$done} request.");
        });
    }

    public function executeExport(ApprovalRequest $approval)
    {
        return $this->withApprovalLock($approval->id, function () use ($approval) {
            if ($approval->status !== 'approved' || ! str_starts_with((string) $approval->type, 'report.export.')) {
                return back()->with('error', 'Approval export belum siap dieksekusi.');
            }

            $payload = is_array($approval->payload) ? $approval->payload : [];
            if (! empty($payload['export_executed_at'])) {
                return back()->with('error', 'Export untuk approval ini sudah pernah dieksekusi.');
            }

            $format = (string) ($payload['format'] ?? '');
            if (! in_array($format, ['excel', 'pdf'], true)) {
                return back()->with('error', 'Format export tidak valid.');
            }

            $payload['export_executed_at'] = now()->toIso8601String();
            $payload['export_executed_by'] = (int) (auth()->id() ?? 0);
            $payload['export_executed_by_name'] = (string) (auth()->user()?->name ?? '-');
            $approval->payload = $payload;
            $approval->save();

        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => 'approval_export_executed',
            'context' => [
                'approval_id' => $approval->id,
                'type' => $approval->type,
                'format' => $format,
            ],
            'ip_address' => (string) request()?->ip(),
            'user_agent' => (string) (request()?->userAgent() ?? ''),
        ]);

        $query = [
            'period' => 'custom',
            'start_date' => (string) ($payload['start_date'] ?? now()->toDateString()),
            'end_date' => (string) ($payload['end_date'] ?? now()->toDateString()),
            'customer_id' => (int) ($payload['customer_id'] ?? 0),
            'payment_method' => (string) ($payload['payment_method'] ?? 'all'),
            'qris_reference' => (string) ($payload['qris_reference'] ?? ''),
            'export_reason' => (string) ($approval->reason ?? ''),
            'approval_request_id' => (int) $approval->id,
        ];

        $selectedIds = collect((array) ($payload['selected_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();

        $route = $format === 'excel' ? 'reports.export.excel' : 'reports.export.pdf';
        if (! empty($selectedIds)) {
            foreach ($selectedIds as $id) {
                $query['selected_ids'][] = $id;
            }
        }

            return redirect()->route($route, $query);
        });
    }

    public function assign(Request $request, ApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return back()->with('error', 'Hanya request pending yang bisa di-assign.');
        }
        $validated = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $userId = (int) ($validated['assigned_to'] ?? 0);
        $assignee = null;
        if ($userId > 0) {
            $assignee = $this->applyBranchScope(User::query(), $request->user())->whereIn('role', ['owner', 'admin'])->find($userId);
            if (! $assignee) {
                throw ValidationException::withMessages(['assigned_to' => 'Assignee harus owner/admin.']);
            }
        }
        $approval->assigned_to = $assignee?->id;
        $approval->save();

        return back()->with('success', 'Assignee approval berhasil diperbarui.');
    }

    public function snooze(Request $request, ApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return back()->with('error', 'Hanya request pending yang bisa di-snooze.');
        }
        $payload = is_array($approval->payload) ? $approval->payload : [];
        $escalationLevel = (int) data_get($payload, 'sla_escalation_last_level', 0);
        if ($escalationLevel >= 3) {
            return back()->with('error', 'Request sudah level escalation 3. Snooze dinonaktifkan, wajib approve/reject.');
        }
        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'in:15,30,60'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $minutes = (int) $validated['minutes'];
        $approval->snoozed_until = now()->addMinutes($minutes);
        $approval->snooze_note = trim((string) ($validated['note'] ?? ''));
        $approval->save();

        return back()->with('success', "Approval disnooze {$minutes} menit.");
    }

    public function unsnooze(ApprovalRequest $approval)
    {
        if ($approval->status !== 'pending') {
            return back()->with('error', 'Hanya request pending yang bisa dibuka snooze-nya.');
        }
        $approval->snoozed_until = null;
        $approval->snooze_note = null;
        $approval->save();

        return back()->with('success', 'Snooze approval dibuka.');
    }

    public function bulkExecuteExports(Request $request)
    {
        $validated = $request->validate([
            'approval_ids' => ['required', 'array', 'min:1', 'max:200'],
            'approval_ids.*' => ['integer', 'min:1', 'distinct'],
        ]);
        $ids = collect($validated['approval_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        $done = 0;

        $key = 'approval-bulk-export-exec-'.sha1(json_encode($ids->all()));
        return $this->withNamedLock($key, function () use ($ids, &$done) {
            DB::transaction(function () use ($ids, &$done): void {
                $rows = $this->applyBranchScope(ApprovalRequest::query(), request()->user())->whereIn('id', $ids)->lockForUpdate()->get();
                foreach ($rows as $approval) {
                    if ($approval->status !== 'approved' || ! str_starts_with((string) $approval->type, 'report.export.')) {
                        continue;
                    }
                    $payload = is_array($approval->payload) ? $approval->payload : [];
                    if (! empty($payload['export_executed_at'])) {
                        continue;
                    }
                    $payload['export_executed_at'] = now()->toIso8601String();
                    $payload['export_executed_by'] = (int) (auth()->id() ?? 0);
                    $payload['export_executed_by_name'] = (string) (auth()->user()?->name ?? '-');
                    $approval->payload = $payload;
                    $approval->save();
                    $done++;
                }
            });

            return back()->with('success', "Bulk execute export sukses untuk {$done} request.");
        });
    }

    private function executeApproval(ApprovalRequest $approval, Request $request): void
    {
        if (str_starts_with((string) $approval->type, 'report.export.')) {
            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'approval_request_approved',
                'context' => [
                    'approval_id' => $approval->id,
                    'type' => $approval->type,
                    'title' => $approval->title,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);

            return;
        }

        $payload = is_array($approval->payload) ? $approval->payload : [];
        if ($approval->type === 'supplier.purchase_payment') {
            $this->executeSupplierPurchasePayment($approval, $request);
            return;
        }
        if ($approval->type === 'supplier.purchase_approval') {
            $this->executeSupplierPurchaseApproval($approval, $request);
            return;
        }

        $saleId = (int) ($payload['sale_id'] ?? 0);
        $reason = trim((string) ($payload['reason'] ?? ''));

        if ($saleId <= 0 || $reason === '') {
            throw ValidationException::withMessages([
                'review_note' => 'Payload approval tidak valid.',
            ]);
        }

        $sale = $this->applyBranchScope(Sale::query(), $request->user())->findOrFail($saleId);
        if ($approval->type === 'sale.quick_refund') {
            $this->executeQuickRefund($sale, $reason, $request);
            return;
        }

        if ($approval->type === 'sale.quick_void') {
            $this->executeQuickVoid($sale, $reason, $request);
            return;
        }

        throw ValidationException::withMessages([
            'review_note' => 'Tipe approval tidak didukung.',
        ]);
    }

    private function executeSupplierPurchasePayment(ApprovalRequest $approval, Request $request): void
    {
        $payload = is_array($approval->payload) ? $approval->payload : [];
        $purchaseId = (int) ($payload['supplier_purchase_id'] ?? 0);
        $amount = (float) ($payload['amount'] ?? 0);
        $method = (string) ($payload['payment_method'] ?? 'cash');

        if ($purchaseId <= 0 || $amount <= 0 || ! in_array($method, ['cash', 'transfer', 'debit', 'qris', 'e_wallet'], true)) {
            throw ValidationException::withMessages([
                'review_note' => 'Payload pembayaran supplier tidak valid.',
            ]);
        }

        $purchaseQuery = $request->user()?->hasAnyRole(['owner'])
            ? SupplierPurchase::query()
            : $this->applyBranchScope(SupplierPurchase::query(), $request->user());
        $purchase = $purchaseQuery->lockForUpdate()->findOrFail($purchaseId);

        if (! in_array($purchase->status, ['draft', 'received'], true)) {
            throw ValidationException::withMessages([
                'review_note' => 'Status pembelian supplier sudah tidak bisa dibayar.',
            ]);
        }

        if ((float) $purchase->remaining_amount <= 0) {
            throw ValidationException::withMessages([
                'review_note' => 'Hutang supplier ini sudah lunas.',
            ]);
        }

        $paymentAmount = min($amount, (float) $purchase->remaining_amount);
        SupplierPurchasePayment::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'received_by' => $request->user()?->id,
            'paid_at' => now(),
            'amount' => $paymentAmount,
            'payment_method' => $method,
            'note' => '[APPROVED] '.trim((string) ($payload['reason'] ?? 'Pembayaran supplier berisiko.')),
        ]);

        $newPaid = (float) $purchase->paid_amount + $paymentAmount;
        $newRemaining = max((float) $purchase->total_amount - $newPaid, 0);
        $newStatus = $newRemaining <= 0
            ? 'paid'
            : (($purchase->due_date && $purchase->due_date->isPast()) ? 'overdue' : 'partial');

        $purchase->update([
            'paid_amount' => $newPaid,
            'remaining_amount' => $newRemaining,
            'payment_status' => $newStatus,
            'paid_at' => $newRemaining <= 0 ? now() : $purchase->paid_at,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_paid',
            'context' => [
                'approval_id' => $approval->id,
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'payment_amount' => $paymentAmount,
                'remaining_amount' => $newRemaining,
                'payment_status' => $newStatus,
                'approved_via_queue' => true,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function executeSupplierPurchaseApproval(ApprovalRequest $approval, Request $request): void
    {
        $payload = is_array($approval->payload) ? $approval->payload : [];
        $purchaseId = (int) ($payload['supplier_purchase_id'] ?? 0);
        if ($purchaseId <= 0) {
            throw ValidationException::withMessages([
                'review_note' => 'Payload persetujuan pembelian supplier tidak valid.',
            ]);
        }

        $purchaseQuery = $request->user()?->hasAnyRole(['owner'])
            ? SupplierPurchase::query()
            : $this->applyBranchScope(SupplierPurchase::query(), $request->user());
        $purchase = $purchaseQuery->findOrFail($purchaseId);
        if ($purchase->status !== 'draft') {
            throw ValidationException::withMessages([
                'review_note' => 'Pembelian supplier sudah tidak berstatus draft.',
            ]);
        }

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_approved',
            'context' => [
                'approval_id' => $approval->id,
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'total_amount' => (float) $purchase->total_amount,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function executeQuickRefund(Sale $sale, string $reason, Request $request): void
    {
        if ($sale->status !== SaleStatus::Paid) {
            throw ValidationException::withMessages([
                'review_note' => 'Refund hanya berlaku untuk transaksi PAID.',
            ]);
        }

        $lockedSale = $this->applyBranchScope(Sale::query(), $request->user())->with('items')->lockForUpdate()->findOrFail($sale->id);
        if ($lockedSale->status !== SaleStatus::Paid) {
            throw ValidationException::withMessages([
                'review_note' => 'Transaksi sudah tidak berstatus PAID.',
            ]);
        }

        $productIds = $lockedSale->items->pluck('product_id')->filter()->unique()->values();
        $products = $this->applyBranchScope(Product::query(), $request->user())->whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');
        foreach ($lockedSale->items as $item) {
            $product = $products->get($item->product_id);
            if ($product) {
                $product->increment('stock', (int) $item->quantity);
            }
        }

        $lockedSale->status = SaleStatus::Cancelled;
        $lockedSale->payment_breakdown = null;
        $lockedSale->change_amount = 0;
        $lockedSale->note = trim(($lockedSale->note ? $lockedSale->note."\n" : '').'[REFUND-APPROVED] '.$reason.' | by: '.($request->user()?->name ?? 'system').' | at: '.now()->format('d/m/Y H:i:s'));
        $lockedSale->save();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'sale_refunded',
            'context' => [
                'sale_id' => $lockedSale->id,
                'invoice' => $lockedSale->invoice_number,
                'reason' => $reason,
                'total' => (float) $lockedSale->total_amount,
                'approved_via_queue' => true,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function executeQuickVoid(Sale $sale, string $reason, Request $request): void
    {
        if ($sale->status !== SaleStatus::Pending) {
            throw ValidationException::withMessages([
                'review_note' => 'Void cepat hanya untuk transaksi PENDING.',
            ]);
        }

        $lockedSale = $this->applyBranchScope(Sale::query(), $request->user())->lockForUpdate()->findOrFail($sale->id);
        if ($lockedSale->status !== SaleStatus::Pending) {
            throw ValidationException::withMessages([
                'review_note' => 'Transaksi sudah tidak berstatus PENDING.',
            ]);
        }

        $lockedSale->status = SaleStatus::Cancelled;
        $lockedSale->payment_breakdown = null;
        $lockedSale->paid_amount = 0;
        $lockedSale->change_amount = 0;
        $lockedSale->note = trim(($lockedSale->note ? $lockedSale->note."\n" : '').'[VOID-APPROVED] '.$reason.' | by: '.($request->user()?->name ?? 'system').' | at: '.now()->format('d/m/Y H:i:s'));
        $lockedSale->save();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'sale_voided',
            'context' => [
                'sale_id' => $lockedSale->id,
                'invoice' => $lockedSale->invoice_number,
                'reason' => $reason,
                'approved_via_queue' => true,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function sendApprovalTelegram(string $event, ApprovalRequest $approval, ?string $actorName, ?string $note = null): void
    {
        if (! TelegramNotifier::enabled()) {
            return;
        }

        $headline = $event === 'approved' ? 'Approval Disetujui' : 'Approval Ditolak';
        $message = implode("\n", [
            $headline,
            'ID: #'.$approval->id,
            'Tipe: '.$approval->type,
            'Judul: '.$approval->title,
            'Pemroses: '.($actorName ?: '-'),
            'Catatan: '.trim((string) ($note ?: '-')),
        ]);

        TelegramNotifier::send($message, 'approval_'.$event, TelegramNotifier::defaultChatId());
    }

    private function withApprovalLock(int $approvalId, callable $callback)
    {
        return $this->withNamedLock("approval-op-{$approvalId}", $callback);
    }

    private function withNamedLock(string $key, callable $callback)
    {
        $lock = Cache::lock($key, 10);
        if (! $lock->get()) {
            return back()->with('error', 'Operasi sedang diproses. Coba lagi beberapa detik.');
        }

        try {
            return $callback();
        } finally {
            optional($lock)->release();
        }
    }
}
