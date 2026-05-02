<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Exports\SalesReportExport;
use App\Models\CashierAuditLog;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ApprovalRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Support\AppliesBranchScope;
use App\Support\TelegramNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $this->validateReportFilters($request);
        [$startDate, $endDate, $period] = $this->resolvePeriod($request);
        $productQuery = trim((string) $request->query('product', ''));
        $customerId = (int) $request->integer('customer_id', 0);
        $paymentMethod = (string) $request->query('payment_method', 'all');
        $qrisReference = trim((string) $request->query('qris_reference', ''));
        $compact = $request->boolean('compact', false);

        $salesQuery = $this->applyBranchScope(Sale::query(), $request->user())
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()]);

        if ($productQuery !== '') {
            $salesQuery->whereHas('items', function ($query) use ($productQuery) {
                $query->where('product_name', 'like', "%{$productQuery}%")
                    ->orWhere('sku', 'like', "%{$productQuery}%");
            });
        }
        if ($customerId > 0) {
            $salesQuery->where('customer_id', $customerId);
        }
        if ($paymentMethod !== 'all') {
            $salesQuery->where('payment_method', $paymentMethod);
        }
        if ($qrisReference !== '') {
            $salesQuery->where('note', 'like', '%[QRIS] Ref:%')
                ->where('note', 'like', '%'.$qrisReference.'%');
        }

        $sales = (clone $salesQuery)
            ->with([
                'items:id,sale_id,product_name,quantity,unit_price,subtotal',
                'user:id,name',
                'customer:id,name',
            ])
            ->latest('sold_at')
            ->paginate(20)
            ->withQueryString();
        $sales->getCollection()->transform(function ($sale) {
            [$qrisRef, $qrisIssuer] = $this->extractQrisFromNote((string) ($sale->note ?? ''));
            $sale->qris_reference_id = $qrisRef;
            $sale->qris_issuer = $qrisIssuer;

            return $sale;
        });
        $customers = $this->applyBranchScope(Customer::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paidSales = (clone $salesQuery)
            ->where('status', SaleStatus::Paid->value)
            ->with(['items:id,sale_id,purchase_price,quantity', 'user:id,name'])
            ->get();

        $omzet = (float) $paidSales->sum('total_amount');
        $paidSaleIds = $paidSales->pluck('id')->values();
        $modal = $paidSaleIds->isNotEmpty()
            ? (float) SaleItem::query()
                ->whereIn('sale_id', $paidSaleIds)
                ->selectRaw('COALESCE(SUM(purchase_price * quantity), 0) as modal_total')
                ->value('modal_total')
            : 0.0;
        $expenses = (float) $this->applyBranchScope(Expense::query(), $request->user())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $profit = $omzet - $modal - $expenses;

        $paymentSummary = $paidSales
            ->groupBy(fn ($sale) => (string) ($sale->payment_method ?: 'cash'))
            ->map(function ($rows, $method) use ($omzet) {
                $amount = (float) $rows->sum('total_amount');
                return [
                    'method' => $method,
                    'count' => (int) $rows->count(),
                    'amount' => $amount,
                    'pct' => $omzet > 0 ? ($amount / $omzet) * 100 : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $cashierPaymentStats = $paidSales
            ->groupBy(fn ($sale) => (int) ($sale->user_id ?? 0))
            ->map(function ($rows) {
                $cashierName = (string) ($rows->first()?->user?->name ?: 'Kasir Tidak Diketahui');
                $totalPaid = (float) $rows->sum('total_amount');
                $totalTx = (int) $rows->count();
                $byMethod = $rows->groupBy(fn ($sale) => (string) ($sale->payment_method ?: 'cash'));
                $favoriteMethod = (string) ($byMethod->sortByDesc(fn ($methodRows) => $methodRows->count())->keys()->first() ?: 'cash');
                $mixedCount = (int) ($byMethod->get('mixed')?->count() ?? 0);
                $splitPct = $totalTx > 0 ? ($mixedCount / $totalTx) * 100 : 0;

                return [
                    'cashier_name' => $cashierName,
                    'total_tx' => $totalTx,
                    'total_paid' => $totalPaid,
                    'favorite_method' => $favoriteMethod,
                    'split_count' => $mixedCount,
                    'split_pct' => $splitPct,
                ];
            })
            ->sortByDesc('total_paid')
            ->values();

        return view('reports.index', [
            'sales' => $sales,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'productQuery' => $productQuery,
            'customerId' => $customerId,
            'paymentMethod' => $paymentMethod,
            'qrisReference' => $qrisReference,
            'customers' => $customers,
            'omzet' => $omzet,
            'modal' => $modal,
            'expenses' => $expenses,
            'profit' => $profit,
            'paymentSummary' => $paymentSummary,
            'cashierPaymentStats' => $cashierPaymentStats,
            'compact' => $compact,
        ]);
    }

    public function exportExcel(Request $request)
    {
        $this->validateReportFilters($request);
        $this->enforceExportRateLimit($request);
        [$startDate, $endDate] = $this->resolvePeriod($request);
        $customerId = (int) $request->integer('customer_id', 0);
        $paymentMethod = (string) $request->query('payment_method', 'all');
        $qrisReference = trim((string) $request->query('qris_reference', ''));
        $exportReason = trim((string) $request->query('export_reason', ''));
        $selectedIds = collect($request->query('selected_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        [$selectedCount, $selectedTotal] = $this->resolveExportSelectionMetrics($startDate, $endDate, $customerId, $paymentMethod, $qrisReference, $selectedIds);
        if ($response = $this->queueLargeExportApprovalIfNeeded($request, 'excel', $selectedCount, $selectedTotal, $selectedIds, $startDate, $endDate, $customerId, $paymentMethod, $qrisReference)) {
            return $response;
        }

        $filename = sprintf('laporan-penjualan-%s-%s.xlsx', $startDate->format('Ymd'), $endDate->format('Ymd'));
        $this->logReportExport($request, 'report_export_excel', $selectedIds, $startDate, $endDate, $paymentMethod, $qrisReference, $selectedCount, $selectedTotal, $exportReason, '');

        return Excel::download(new SalesReportExport(
            $startDate,
            $endDate,
            $customerId,
            $paymentMethod,
            $qrisReference,
            $selectedIds,
            [
                'exported_at' => now()->format('d/m/Y H:i:s'),
                'exported_by' => (string) ($request->user()?->name ?? '-'),
                'export_reason' => $exportReason,
                'approved_by' => '',
            ],
            (int) ($request->user()?->branch_id ?? 0),
            (bool) ($request->user()?->hasAnyRole(['owner']) ?? false)
        ), $filename);
    }

    public function exportPdf(Request $request)
    {
        $this->validateReportFilters($request);
        $this->enforceExportRateLimit($request);
        [$startDate, $endDate, $period] = $this->resolvePeriod($request);
        $customerId = (int) $request->integer('customer_id', 0);
        $paymentMethod = (string) $request->query('payment_method', 'all');
        $qrisReference = trim((string) $request->query('qris_reference', ''));
        $exportReason = trim((string) $request->query('export_reason', ''));
        $selectedIds = collect($request->query('selected_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        [$selectedCount, $selectedTotal] = $this->resolveExportSelectionMetrics($startDate, $endDate, $customerId, $paymentMethod, $qrisReference, $selectedIds);
        if ($response = $this->queueLargeExportApprovalIfNeeded($request, 'pdf', $selectedCount, $selectedTotal, $selectedIds, $startDate, $endDate, $customerId, $paymentMethod, $qrisReference)) {
            return $response;
        }
        $this->logReportExport($request, 'report_export_pdf', $selectedIds, $startDate, $endDate, $paymentMethod, $qrisReference, $selectedCount, $selectedTotal, $exportReason, '');

        $sales = $this->applyBranchScope(Sale::query(), $request->user())
            ->with(['items', 'user:id,name', 'customer:id,name'])
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($paymentMethod !== 'all', fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($qrisReference !== '', function ($q) use ($qrisReference) {
                $q->where('note', 'like', '%[QRIS] Ref:%')
                    ->where('note', 'like', '%'.$qrisReference.'%');
            })
            ->when(! empty($selectedIds), fn ($q) => $q->whereIn('id', $selectedIds))
            ->latest('sold_at')
            ->get();
        $sales->transform(function ($sale) {
            [$qrisRef, $qrisIssuer] = $this->extractQrisFromNote((string) ($sale->note ?? ''));
            $sale->qris_reference_id = $qrisRef;
            $sale->qris_issuer = $qrisIssuer;

            return $sale;
        });

        $paidSales = $sales->where('status', SaleStatus::Paid);
        $omzet = (float) $paidSales->sum('total_amount');
        $modal = (float) $paidSales->sum(fn ($sale) => $sale->items->sum(fn ($item) => $item->purchase_price * $item->quantity));
        $expenses = (float) $this->applyBranchScope(Expense::query(), $request->user())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');
        $paymentSummary = $paidSales
            ->groupBy(fn ($sale) => (string) ($sale->payment_method ?: 'cash'))
            ->map(function ($rows, $method) use ($omzet) {
                $amount = (float) $rows->sum('total_amount');
                return [
                    'method' => $method,
                    'count' => (int) $rows->count(),
                    'amount' => $amount,
                    'pct' => $omzet > 0 ? ($amount / $omzet) * 100 : 0,
                ];
            })
            ->sortByDesc('amount')
            ->values();

        $pdf = Pdf::loadView('pdf.report-sales', [
            'sales' => $sales,
            'period' => $period,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'omzet' => $omzet,
            'modal' => $modal,
            'expenses' => $expenses,
            'profit' => $omzet - $modal - $expenses,
            'paymentSummary' => $paymentSummary,
            'exportMeta' => [
                'exported_at' => now()->format('d/m/Y H:i:s'),
                'exported_by' => (string) ($request->user()?->name ?? '-'),
                'export_reason' => $exportReason,
                'approved_by' => '',
                'selected_count' => $selectedCount,
                'selected_total' => $selectedTotal,
            ],
        ])->setPaper('a4', 'landscape');

        return $pdf->download(sprintf('laporan-penjualan-%s-%s.pdf', $startDate->format('Ymd'), $endDate->format('Ymd')));
    }

    private function logReportExport(
        Request $request,
        string $action,
        array $selectedIds,
        Carbon $startDate,
        Carbon $endDate,
        string $paymentMethod,
        string $qrisReference,
        ?int $selectedCount = null,
        ?float $selectedTotal = null,
        string $exportReason = '',
        string $approvedBy = ''
    ): void {
        $selectedCount = $selectedCount ?? count($selectedIds);
        $selectedTotal = $selectedTotal ?? ($selectedCount > 0
            ? (float) $this->applyBranchScope(Sale::query(), $request->user())->whereIn('id', $selectedIds)->sum('total_amount')
            : 0.0);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'context' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'payment_method' => $paymentMethod,
                'qris_reference' => $qrisReference,
                'selected_count' => $selectedCount,
                'selected_total' => $selectedTotal,
                'selected_ids' => $selectedIds,
                'export_reason' => $exportReason,
                'approved_by' => $approvedBy,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function resolveExportSelectionMetrics(
        Carbon $startDate,
        Carbon $endDate,
        int $customerId,
        string $paymentMethod,
        string $qrisReference,
        array $selectedIds
    ): array {
        $query = $this->applyBranchScope(Sale::query(), auth()->user())
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->when($customerId > 0, fn ($q) => $q->where('customer_id', $customerId))
            ->when($paymentMethod !== 'all', fn ($q) => $q->where('payment_method', $paymentMethod))
            ->when($qrisReference !== '', function ($q) use ($qrisReference) {
                $q->where('note', 'like', '%[QRIS] Ref:%')
                    ->where('note', 'like', '%'.$qrisReference.'%');
            })
            ->when(! empty($selectedIds), fn ($q) => $q->whereIn('id', $selectedIds));

        return [(int) (clone $query)->count(), (float) (clone $query)->sum('total_amount')];
    }

    private function queueLargeExportApprovalIfNeeded(
        Request $request,
        string $format,
        int $selectedCount,
        float $selectedTotal,
        array $selectedIds,
        Carbon $startDate,
        Carbon $endDate,
        int $customerId,
        string $paymentMethod,
        string $qrisReference
    ) {
        $setting = \App\Models\StoreSetting::query()->first();
        $approvalRules = is_array($setting?->approval_rules) ? $setting->approval_rules : [];
        $minRows = max(1, (int) data_get($approvalRules, 'export_min_rows', 300));
        $minTotal = max(1, (float) data_get($approvalRules, 'export_min_total', 100000000));
        $isLarge = $selectedCount >= $minRows || $selectedTotal >= $minTotal;
        if (! $isLarge) {
            return null;
        }

        $reason = trim((string) $request->query('export_reason', ''));
        if ($reason === '' || mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'export_reason' => 'Export besar wajib alasan minimal 5 karakter.',
            ]);
        }

        $fingerprint = sha1(json_encode([
            'format' => $format,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'customer_id' => $customerId,
            'payment_method' => $paymentMethod,
            'qris_reference' => $qrisReference,
            'selected_ids' => $selectedIds,
            'selected_count' => $selectedCount,
            'selected_total' => round($selectedTotal, 2),
        ]));

        $approvedRequestId = (int) $request->query('approval_request_id', 0);
        if ($approvedRequestId > 0) {
            $approvedMatch = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
                ->where('id', $approvedRequestId)
                ->where('status', 'approved')
                ->where('type', "report.export.$format")
                ->where('payload->fingerprint', $fingerprint)
                ->exists();
            if ($approvedMatch) {
                return null;
            }
        }

        $existingApproved = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('type', "report.export.$format")
            ->where('status', 'approved')
            ->where('requested_by', (int) ($request->user()?->id ?? 0))
            ->where('payload->fingerprint', $fingerprint)
            ->exists();
        if ($existingApproved) {
            return null;
        }

        $existingPending = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('type', "report.export.$format")
            ->where('status', 'pending')
            ->where('requested_by', (int) ($request->user()?->id ?? 0))
            ->where('payload->fingerprint', $fingerprint)
            ->first();

        if (! $existingPending) {
            $approval = ApprovalRequest::query()->create([
                'branch_id' => $request->user()?->branch_id,
                'type' => "report.export.$format",
                'status' => 'pending',
                'requested_by' => (int) ($request->user()?->id ?? 0),
                'title' => 'Approval Export '.strtoupper($format).' Besar',
                'reason' => $reason,
                'payload' => [
                    'format' => $format,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'customer_id' => $customerId,
                    'payment_method' => $paymentMethod,
                    'qris_reference' => $qrisReference,
                    'selected_ids' => $selectedIds,
                    'selected_count' => $selectedCount,
                    'selected_total' => $selectedTotal,
                    'fingerprint' => $fingerprint,
                ],
            ]);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'action' => 'approval_request_created',
                'context' => [
                    'type' => "report.export.$format",
                    'selected_count' => $selectedCount,
                    'selected_total' => $selectedTotal,
                    'fingerprint' => $fingerprint,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) ($request->userAgent() ?? ''),
            ]);

            if (TelegramNotifier::enabled()) {
                TelegramNotifier::send(
                    implode("\n", [
                        'Approval Request Baru',
                        'Tipe: report.export.'.$format,
                        'Approval ID: #'.$approval->id,
                        'Requester: '.($request->user()?->name ?? '-'),
                        'Selected Count: '.number_format($selectedCount, 0, ',', '.'),
                        'Selected Total: Rp '.number_format($selectedTotal, 0, ',', '.'),
                        'Alasan: '.$reason,
                    ]),
                    'approval_request_created',
                    TelegramNotifier::defaultChatId()
                );
            }
        }

        return redirect()->route('reports.index', $request->query())->with('error', 'Export besar masuk antrian approval. Lanjutkan setelah disetujui di Approval Queue.');
    }

    private function enforceExportRateLimit(Request $request): void
    {
        $userId = (string) ($request->user()?->id ?? 'guest');
        $ip = (string) ($request->ip() ?? 'unknown');
        $key = 'report-export:'.$userId.':'.$ip;
        $maxAttempts = 8;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = (int) RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'export_rate_limit' => "Terlalu banyak export. Coba lagi dalam {$retryAfter} detik.",
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }

    private function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return '';
        }

        [$name, $domain] = explode('@', $email, 2);
        $nameLen = mb_strlen($name);
        if ($nameLen <= 2) {
            $maskedName = mb_substr($name, 0, 1).'*';
        } else {
            $maskedName = mb_substr($name, 0, 2).str_repeat('*', max(1, $nameLen - 2));
        }

        return $maskedName.'@'.$domain;
    }

    private function extractQrisFromNote(string $note): array
    {
        if (preg_match_all('/\[QRIS\]\s*Ref:\s*(.*?)\s*\|\s*Issuer:\s*(.*)/', $note, $matches, PREG_SET_ORDER) && count($matches) > 0) {
            $last = $matches[count($matches) - 1];
            $ref = trim((string) ($last[1] ?? ''));
            $issuer = trim((string) ($last[2] ?? ''));

            return [$ref, $issuer];
        }

        return ['', ''];
    }

    private function resolvePeriod(Request $request): array
    {
        $period = $request->string('period', 'daily')->toString();

        $today = Carbon::today();
        $startDate = $today->copy();
        $endDate = $today->copy();

        if ($period === 'weekly') {
            $startDate = $today->copy()->startOfWeek(Carbon::MONDAY);
            $endDate = $today->copy()->endOfWeek(Carbon::SUNDAY);
        } elseif ($period === 'monthly') {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
        } elseif ($period === 'custom') {
            $startDate = Carbon::parse($request->input('start_date', $today->toDateString()));
            $endDate = Carbon::parse($request->input('end_date', $today->toDateString()));
        }

        if ($endDate->lt($startDate)) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        return [$startDate, $endDate, $period];
    }

    private function validateReportFilters(Request $request): void
    {
        $allowedPeriods = ['daily', 'weekly', 'monthly', 'custom'];
        $allowedPaymentMethods = ['all', 'cash', 'qris', 'debit', 'transfer', 'e_wallet', 'mixed'];

        $request->validate([
            'period' => ['nullable', Rule::in($allowedPeriods)],
            'payment_method' => ['nullable', Rule::in($allowedPaymentMethods)],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'qris_reference' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.\/]{2,119}$/'],
            'selected_ids' => ['nullable', 'array', 'max:1000'],
            'selected_ids.*' => ['integer', 'min:1', 'distinct'],
        ]);

        $period = (string) $request->query('period', 'daily');
        if ($period !== 'custom') {
            return;
        }

        if (! $request->filled('start_date') || ! $request->filled('end_date')) {
            throw ValidationException::withMessages([
                'start_date' => 'Periode custom wajib mengisi tanggal awal dan akhir.',
                'end_date' => 'Periode custom wajib mengisi tanggal awal dan akhir.',
            ]);
        }

        $startDate = Carbon::parse((string) $request->query('start_date'))->startOfDay();
        $endDate = Carbon::parse((string) $request->query('end_date'))->endOfDay();
        $days = $startDate->diffInDays($endDate);
        if ($days > 366) {
            throw ValidationException::withMessages([
                'end_date' => 'Rentang periode custom maksimal 366 hari.',
            ]);
        }
    }
}
