<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\AuditAlertState;
use App\Models\CashierAuditLog;
use App\Models\CustomerFollowUp;
use App\Models\CustomerDebtPayment;
use App\Models\Expense;
use App\Models\CashReconciliation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Models\User;
use App\Models\StockTransfer;
use App\Models\Branch;
use App\Models\CustomerDebt;
use App\Models\SupplierPurchase;
use App\Support\TelegramNotifier;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use AppliesBranchScope;

    public function __invoke(Request $request)
    {
        [$viewData] = $this->buildDashboardData($request);

        return view('dashboard', $viewData);
    }

    public function exportSnapshotPdf(Request $request)
    {
        [$viewData] = $this->buildDashboardData($request);

        $pdf = Pdf::loadView('pdf.dashboard-summary', $viewData)->setPaper('a4', 'portrait');

        return $pdf->download(sprintf('dashboard-snapshot-%s-%s.pdf', str_replace('-', '', $viewData['startDate']), str_replace('-', '', $viewData['endDate'])));
    }

    public function auditAnomalyStatus()
    {
        $setting = StoreSetting::query()->first();
        $checkoutFailThreshold = (int) ($setting?->telegram_checkout_fail_threshold ?: env('DASHBOARD_CHECKOUT_FAIL_THRESHOLD', 5));
        $checkoutFailLastHour = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->where('action', 'checkout_failed_client')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        $bucket = now()->format('YmdH');
        $ackState = $this->branchAlertStateIdentity('checkout_fail_spike_ack_'.$bucket);
        $notifyState = $this->branchAlertStateIdentity('checkout_fail_spike_notified_'.$bucket);

        $todayStart = now()->startOfDay();
        $warningActions = ['checkout_failed_client', 'stock_sync_adjusted'];
        $criticalActions = ['idle_warning'];
        $todayLogs = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->where('created_at', '>=', $todayStart)
            ->select('action')
            ->get();
        $todayWarning = $todayLogs->whereIn('action', $warningActions)->count();
        $todayCritical = $todayLogs->whereIn('action', $criticalActions)->count();
        $todayInfo = max(0, $todayLogs->count() - $todayWarning - $todayCritical);
        $anomaly = $checkoutFailLastHour >= $checkoutFailThreshold;
        $isAcknowledged = AuditAlertState::query()
            ->where($ackState)
            ->exists();

        if ($anomaly && (bool) ($setting?->telegram_enabled) && (bool) ($setting?->telegram_notify_checkout_anomaly)) {
            $alreadyNotified = AuditAlertState::query()
                ->where($notifyState)
                ->exists();
            if (! $alreadyNotified) {
                $this->sendAuditAnomalyNotification($checkoutFailLastHour, $checkoutFailThreshold);
                AuditAlertState::query()->updateOrCreate(
                    $notifyState,
                    ['value' => ['count' => $checkoutFailLastHour, 'threshold' => $checkoutFailThreshold, 'at' => now()->toDateTimeString()]]
                );
            }
        }

        return response()->json([
            'checkoutFailLastHour' => $checkoutFailLastHour,
            'checkoutFailThreshold' => $checkoutFailThreshold,
            'checkoutFailAnomaly' => $anomaly,
            'checkoutFailAcknowledged' => $isAcknowledged,
            'todayInfo' => $todayInfo,
            'todayWarning' => $todayWarning,
            'todayCritical' => $todayCritical,
            'checkedAt' => now()->toIso8601String(),
        ]);
    }

    public function acknowledgeAuditAnomaly()
    {
        $bucket = now()->format('YmdH');
        $ackState = $this->branchAlertStateIdentity('checkout_fail_spike_ack_'.$bucket);

        AuditAlertState::query()->updateOrCreate(
            $ackState,
            ['value' => ['ack_by' => auth()->id(), 'at' => now()->toDateTimeString()]]
        );

        return response()->json(['ok' => true]);
    }

    public function testTelegramAlert()
    {
        $ok = TelegramNotifier::send('Tes notifikasi Telegram POS: koneksi berhasil.', 'dashboard_test');

        return response()->json([
            'ok' => $ok,
            'message' => $ok
                ? 'Pesan tes Telegram berhasil dikirim.'
                : 'Gagal kirim Telegram. Periksa TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID di .env',
        ]);
    }

    private function sendAuditAnomalyNotification(int $count, int $threshold): void
    {
        $message = "ALERT POS: checkout gagal {$count} kali dalam 1 jam (ambang {$threshold}).";

        try {
            TelegramNotifier::send($message, 'checkout_anomaly');

            $webhookUrl = (string) env('AUDIT_ALERT_WEBHOOK_URL', '');
            if ($webhookUrl !== '') {
                Http::timeout(8)->post($webhookUrl, [
                    'event' => 'checkout_fail_spike',
                    'count' => $count,
                    'threshold' => $threshold,
                    'message' => $message,
                    'at' => now()->toIso8601String(),
                ]);
            }
        } catch (\Throwable $e) {
            // Jangan ganggu endpoint status jika notifikasi gagal.
        }
    }

    private function buildDashboardData(Request $request): array
    {
        Carbon::setLocale('id');

        $today = Carbon::today();
        $userId = (int) (auth()->id() ?? 0);
        $prefKey = 'dashboard.filters.'.$userId;
        $savedFilter = (array) session($prefKey, []);

        $preset = (string) $request->query('preset', ($savedFilter['preset'] ?? '7_hari'));
        $compactMode = $request->boolean('compact');
        $autoRefresh = $request->boolean('auto_refresh');
        $refreshSeconds = max(15, min((int) $request->query('refresh_seconds', 60), 300));
        $isManager = auth()->user()?->hasAnyRole(['owner', 'admin']) ?? false;
        $actor = auth()->user();
        $actorBranchId = (int) ($actor?->branch_id ?? 0);
        $isOwner = $actor?->hasAnyRole(['owner']) ?? false;

        $startDateQuery = (string) $request->query('start_date', ($savedFilter['start_date'] ?? ''));
        $endDateQuery = (string) $request->query('end_date', ($savedFilter['end_date'] ?? ''));

        [$startDate, $endDate] = $this->resolveRange($request, $preset, $startDateQuery, $endDateQuery);
        $periodDays = $startDate->diffInDays($endDate) + 1;

        $todaySales = $this->applyBranchScope(Sale::query(), auth()->user())
            ->whereDate('sold_at', $today)
            ->paid();

        $rangeSales = $this->applyBranchScope(Sale::query(), auth()->user())
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->paid();

        $omzetToday = (float) $todaySales->sum('total_amount');
        $transactionsToday = (int) $todaySales->count();
        $yesterday = $today->copy()->subDay();
        $yesterdaySales = $this->applyBranchScope(Sale::query(), auth()->user())->whereDate('sold_at', $yesterday)->paid();
        $omzetYesterday = (float) $yesterdaySales->sum('total_amount');
        $transactionsYesterday = (int) $yesterdaySales->count();
        $omzetRange = (float) (clone $rangeSales)->sum('total_amount');
        $transactionsRange = (int) (clone $rangeSales)->count();
        $monthlyTarget = (float) config('app.dashboard_monthly_target', env('DASHBOARD_MONTHLY_TARGET', 50000000));
        $daysInMonth = max(1, $endDate->daysInMonth);
        $periodTarget = ($monthlyTarget / $daysInMonth) * $periodDays;
        $targetAchievementPct = $periodTarget > 0 ? ($omzetRange / $periodTarget) * 100 : 0;
        $targetStatus = $targetAchievementPct >= 100 ? 'aman' : ($targetAchievementPct >= 70 ? 'perhatian' : 'kritis');
        $targetStatusLabel = match ($targetStatus) {
            'aman' => 'Aman',
            'perhatian' => 'Perhatian',
            default => 'Kritis',
        };

        $hppRange = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isOwner && $actorBranchId > 0, fn ($q) => $q->where('sales.branch_id', $actorBranchId))
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereBetween('sales.sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->selectRaw('COALESCE(SUM(sale_items.purchase_price * sale_items.quantity), 0) as total')
            ->value('total');

        $expensesRange = (float) $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('amount');

        $expensesToday = (float) $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereDate('date', $today->toDateString())
            ->sum('amount');

        $expensesLast7Days = (float) $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereBetween('date', [$today->copy()->subDays(6)->toDateString(), $today->toDateString()])
            ->sum('amount');

        $expensesThisMonth = (float) $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereBetween('date', [$today->copy()->startOfMonth()->toDateString(), $today->copy()->endOfMonth()->toDateString()])
            ->sum('amount');

        $expenseRows = $this->applyBranchScope(Expense::query(), auth()->user())
            ->selectRaw('DATE(date) as expense_date, SUM(amount) as total')
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get()
            ->keyBy('expense_date');

        $expenseTrendLabels = [];
        $expenseTrendData = [];
        for ($i = 0; $i < $periodDays; $i++) {
            $d = $startDate->copy()->addDays($i)->toDateString();
            $expenseTrendLabels[] = Carbon::parse($d)->translatedFormat('d M');
            $expenseTrendData[] = (float) ($expenseRows[$d]->total ?? 0);
        }

        $expenseTopCategories = $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $expenseTotalForPercent = max($expensesRange, 1);
        $expenseTopCategories = $expenseTopCategories->map(function ($row) use ($expenseTotalForPercent) {
            $row->pct = round((((float) $row->total) / $expenseTotalForPercent) * 100, 1);
            return $row;
        });

        $expenseAuditRows = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
            ->with('user:id,name')
            ->whereIn('action', ['expense_created', 'expense_updated', 'expense_deleted', 'expense_duplicated'])
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->latest('id')
            ->limit(6)
            ->get();

        $settingBudget = StoreSetting::query()->first();
        $expenseDailyBudget = (float) ($settingBudget?->expense_daily_budget ?? 1000000);
        $expenseMonthlyBudget = (float) ($settingBudget?->expense_monthly_budget ?? 30000000);
        $expenseOverBudgetDaily = $expenseDailyBudget > 0 && $expensesToday > $expenseDailyBudget;
        $expenseOverBudgetMonthly = $expenseMonthlyBudget > 0 && $expensesThisMonth > $expenseMonthlyBudget;

        $recentExpenses = $this->applyBranchScope(Expense::query(), auth()->user())
            ->with('user:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'user_id', 'title', 'category', 'amount', 'date']);

        $grossProfitRange = $omzetRange - $hppRange;
        $netProfitRange = $grossProfitRange - $expensesRange;
        $creditSalesRange = (float) $this->applyBranchScope(CustomerDebt::query(), auth()->user())
            ->whereBetween('debt_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->sum('principal_amount');
        $installmentCashInRange = (float) CustomerDebtPayment::query()
            ->join('customer_debts', 'customer_debts.id', '=', 'customer_debt_payments.customer_debt_id')
            ->when(! $isOwner && $actorBranchId > 0, fn ($q) => $q->where('customer_debts.branch_id', $actorBranchId))
            ->whereBetween('customer_debt_payments.paid_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->sum('customer_debt_payments.amount');
        $outstandingDebtTotal = (float) $this->applyBranchScope(CustomerDebt::query(), auth()->user())
            ->whereIn('status', ['active', 'overdue'])
            ->sum('remaining_amount');
        $accrualRevenueRange = $omzetRange + $creditSalesRange;

        $previousStart = $startDate->copy()->subDays($periodDays);
        $previousEnd = $startDate->copy()->subDay();

        $previousSales = $this->applyBranchScope(Sale::query(), auth()->user())
            ->whereBetween('sold_at', [$previousStart->copy()->startOfDay(), $previousEnd->copy()->endOfDay()])
            ->paid();

        $omzetPrevious = (float) (clone $previousSales)->sum('total_amount');
        $transactionsPrevious = (int) (clone $previousSales)->count();

        $hppPrevious = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isOwner && $actorBranchId > 0, fn ($q) => $q->where('sales.branch_id', $actorBranchId))
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereBetween('sales.sold_at', [$previousStart->copy()->startOfDay(), $previousEnd->copy()->endOfDay()])
            ->selectRaw('COALESCE(SUM(sale_items.purchase_price * sale_items.quantity), 0) as total')
            ->value('total');

        $expensesPrevious = (float) $this->applyBranchScope(Expense::query(), auth()->user())
            ->whereBetween('date', [$previousStart->toDateString(), $previousEnd->toDateString()])
            ->sum('amount');

        $netProfitPrevious = ($omzetPrevious - $hppPrevious) - $expensesPrevious;

        $insights = [
            [
                'title' => 'Omzet Periode',
                'current' => $omzetRange,
                'previous' => $omzetPrevious,
                'unit' => 'currency',
            ],
            [
                'title' => 'Transaksi Paid',
                'current' => $transactionsRange,
                'previous' => $transactionsPrevious,
                'unit' => 'count',
            ],
            [
                'title' => 'Laba Bersih',
                'current' => $netProfitRange,
                'previous' => $netProfitPrevious,
                'unit' => 'currency',
            ],
        ];

        $inventoryAssetValue = (float) $this->applyBranchScope(Product::query(), auth()->user())
            ->active()
            ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as total')
            ->value('total');

        $periodSalesQtySub = SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isOwner && $actorBranchId > 0, fn ($q) => $q->where('sales.branch_id', $actorBranchId))
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereBetween('sales.sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('sale_items.product_id')
            ->selectRaw('sale_items.product_id, SUM(sale_items.quantity) as sold_qty');

        $showAllLow = $request->boolean('all_low');
        $showAllOut = $request->boolean('all_out');
        $showAllBest = $request->boolean('all_best');

        $lowStockProducts = $this->applyBranchScope(Product::query(), auth()->user())
            ->active()
            ->lowStock()
            ->leftJoinSub($periodSalesQtySub, 'period_qty', fn ($join) => $join->on('products.id', '=', 'period_qty.product_id'))
            ->orderByDesc(DB::raw('COALESCE(period_qty.sold_qty, 0)'))
            ->orderBy('products.stock')
            ->select('products.*')
            ->when(! $showAllLow, fn ($q) => $q->take(5))
            ->get();

        $outOfStockProducts = $this->applyBranchScope(Product::query(), auth()->user())
            ->active()
            ->where('stock', '<=', 0)
            ->leftJoinSub($periodSalesQtySub, 'period_qty', fn ($join) => $join->on('products.id', '=', 'period_qty.product_id'))
            ->orderByDesc(DB::raw('COALESCE(period_qty.sold_qty, 0)'))
            ->orderBy('products.name')
            ->select('products.*')
            ->when(! $showAllOut, fn ($q) => $q->take(8))
            ->get();
        $outOfStockCount = $outOfStockProducts->count();

        $bestSellingProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isOwner && $actorBranchId > 0, fn ($q) => $q->where('sales.branch_id', $actorBranchId))
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereBetween('sales.sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->select('sale_items.product_name', DB::raw('SUM(sale_items.quantity) as total_qty'))
            ->groupBy('sale_items.product_name')
            ->orderByDesc('total_qty')
            ->when(! $showAllBest, fn ($q) => $q->limit(5))
            ->get();

        $topTransactions = $this->applyBranchScope(Sale::query(), auth()->user())
            ->with('user:id,name')
            ->paid()
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get(['id', 'invoice_number', 'user_id', 'sold_at', 'total_amount']);

        $chartRows = $this->applyBranchScope(Sale::query(), auth()->user())
            ->selectRaw('DATE(sold_at) as date, SUM(total_amount) as omzet')
            ->paid()
            ->whereBetween('sold_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $labels = [];
        $data = [];
        $presetLabel = match ($preset) {
            'hari_ini' => 'Hari Ini',
            '30_hari' => '30 Hari Terakhir',
            'custom' => 'Custom',
            default => '7 Hari Terakhir',
        };
        $periodText = $startDate->translatedFormat('d M Y').' - '.$endDate->translatedFormat('d M Y');
        $kpiComparisons = [
            'omzetTodayPct' => $this->percentageChange($omzetToday, $omzetYesterday),
            'transactionsTodayPct' => $this->percentageChange($transactionsToday, $transactionsYesterday),
        ];

        for ($i = 0; $i < $periodDays; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->translatedFormat('d M');
            $data[] = (float) ($chartRows[$date->toDateString()]->omzet ?? 0);
        }

        $miniSparkLabels = [];
        $miniOmzet = [];
        $miniTransactions = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $miniSparkLabels[] = $date->translatedFormat('d M');
            $miniOmzet[] = (float) $this->applyBranchScope(Sale::query(), auth()->user())->paid()->whereDate('sold_at', $date)->sum('total_amount');
            $miniTransactions[] = (int) $this->applyBranchScope(Sale::query(), auth()->user())->paid()->whereDate('sold_at', $date)->count();
        }

        $auditSummary = [
            'today_info' => 0,
            'today_warning' => 0,
            'today_critical' => 0,
        ];
        $followupSummary = [
            'today_total' => 0,
            'today_done' => 0,
            'today_overdue' => 0,
            'today_conversion' => 0,
        ];
        $cashReconSummary = [
            'today_reconciled' => 0,
            'today_diff_plus' => 0.0,
            'today_diff_minus' => 0.0,
            'today_unreconciled_cashier' => 0,
        ];
        $checkoutFailLastHour = 0;
        $storeSetting = StoreSetting::query()->first();
        $checkoutFailThreshold = (int) ($storeSetting?->telegram_checkout_fail_threshold ?: env('DASHBOARD_CHECKOUT_FAIL_THRESHOLD', 5));
        $checkoutFailAnomaly = false;
        $checkoutFailAcknowledged = false;

        if ($isManager) {
            $todayStart = now()->startOfDay();
            $warningActions = ['checkout_failed_client', 'stock_sync_adjusted'];
            $criticalActions = ['idle_warning'];

            $todayLogs = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
                ->where('created_at', '>=', $todayStart)
                ->select('action')
                ->get();

            $warningCount = $todayLogs->whereIn('action', $warningActions)->count();
            $criticalCount = $todayLogs->whereIn('action', $criticalActions)->count();
            $infoCount = max(0, $todayLogs->count() - $warningCount - $criticalCount);

            $auditSummary = [
                'today_info' => $infoCount,
                'today_warning' => $warningCount,
                'today_critical' => $criticalCount,
            ];

            $checkoutFailLastHour = $this->applyBranchScope(CashierAuditLog::query(), auth()->user())
                ->where('action', 'checkout_failed_client')
                ->where('created_at', '>=', now()->subHour())
                ->count();

            $checkoutFailAnomaly = $checkoutFailLastHour >= $checkoutFailThreshold;
            $ackState = $this->branchAlertStateIdentity('checkout_fail_spike_ack_'.now()->format('YmdH'));
            $checkoutFailAcknowledged = AuditAlertState::query()->where($ackState)->exists();

            $todayFollowups = $this->applyBranchScope(CustomerFollowUp::query(), auth()->user())
                ->whereDate('reminder_at', now()->toDateString());
            $todayFollowupDone = (clone $todayFollowups)->where('status', 'selesai');
            $overdueFollowups = $this->applyBranchScope(CustomerFollowUp::query(), auth()->user())
                ->whereIn('status', ['baru', 'proses', 'gagal'])
                ->whereNotNull('reminder_at')
                ->where('reminder_at', '<', now())
                ->count();

            $doneCustomerIds = (clone $todayFollowupDone)->pluck('customer_id')->filter()->unique();
            $conversionCount = $doneCustomerIds->isEmpty()
                ? 0
                : $this->applyBranchScope(Sale::query(), auth()->user())
                    ->paid()
                    ->whereDate('sold_at', now()->toDateString())
                    ->whereIn('customer_id', $doneCustomerIds->values())
                    ->distinct('customer_id')
                    ->count('customer_id');

            $followupSummary = [
                'today_total' => (int) (clone $todayFollowups)->count(),
                'today_done' => (int) $todayFollowupDone->count(),
                'today_overdue' => (int) $overdueFollowups,
                'today_conversion' => (int) $conversionCount,
            ];

            $todayDate = now()->toDateString();
            $todayRecons = $this->applyBranchScope(CashReconciliation::query(), auth()->user())->whereDate('shift_date', $todayDate)->get();
            $todayDiffPlus = (float) $todayRecons->where('difference', '>', 0)->sum('difference');
            $todayDiffMinus = (float) abs($todayRecons->where('difference', '<', 0)->sum('difference'));
            $activeCashiers = $this->applyBranchScope(User::query(), auth()->user())
                ->where('role', 'kasir')
                ->count();
            $cashReconSummary = [
                'today_reconciled' => (int) $todayRecons->count(),
                'today_diff_plus' => $todayDiffPlus,
                'today_diff_minus' => $todayDiffMinus,
                'today_unreconciled_cashier' => max($activeCashiers - (int) $todayRecons->count(), 0),
            ];
        }

        $branchTransferKpis = $this->buildBranchTransferKpis(auth()->user());
        $branchDebtKpis = $this->buildBranchDebtKpis(auth()->user());
        $branchSupplierDebtKpis = $this->buildBranchSupplierDebtKpis(auth()->user());

        session()->put($prefKey, [
            'preset' => $preset,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        return [[
            'selectedPreset' => $preset,
            'compactMode' => $compactMode,
            'autoRefresh' => $autoRefresh,
            'refreshSeconds' => $refreshSeconds,
            'isManager' => $isManager,
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'periodDays' => $periodDays,
            'presetLabel' => $presetLabel,
            'periodText' => $periodText,
            'lastUpdatedText' => now()->translatedFormat('d M Y, H:i'),
            'monthlyTarget' => $monthlyTarget,
            'periodTarget' => $periodTarget,
            'targetAchievementPct' => $targetAchievementPct,
            'targetStatus' => $targetStatus,
            'targetStatusLabel' => $targetStatusLabel,
            'showAllLow' => $showAllLow,
            'showAllOut' => $showAllOut,
            'showAllBest' => $showAllBest,
            'omzetToday' => $omzetToday,
            'transactionsToday' => $transactionsToday,
            'omzetRange' => $omzetRange,
            'transactionsRange' => $transactionsRange,
            'hppRange' => $hppRange,
            'expensesRange' => $expensesRange,
            'expensesToday' => $expensesToday,
            'expensesLast7Days' => $expensesLast7Days,
            'expensesThisMonth' => $expensesThisMonth,
            'expenseTrendLabels' => $expenseTrendLabels,
            'expenseTrendData' => $expenseTrendData,
            'expenseTopCategories' => $expenseTopCategories,
            'expenseAuditRows' => $expenseAuditRows,
            'expenseDailyBudget' => $expenseDailyBudget,
            'expenseMonthlyBudget' => $expenseMonthlyBudget,
            'expenseOverBudgetDaily' => $expenseOverBudgetDaily,
            'expenseOverBudgetMonthly' => $expenseOverBudgetMonthly,
            'recentExpenses' => $recentExpenses,
            'grossProfitRange' => $grossProfitRange,
            'netProfitRange' => $netProfitRange,
            'creditSalesRange' => $creditSalesRange,
            'installmentCashInRange' => $installmentCashInRange,
            'outstandingDebtTotal' => $outstandingDebtTotal,
            'accrualRevenueRange' => $accrualRevenueRange,
            'kpiComparisons' => $kpiComparisons,
            'insights' => $insights,
            'previousPeriodText' => $previousStart->translatedFormat('d M Y').' - '.$previousEnd->translatedFormat('d M Y'),
            'inventoryAssetValue' => $inventoryAssetValue,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'outOfStockCount' => $outOfStockCount,
            'bestSellingProducts' => $bestSellingProducts,
            'topTransactions' => $topTransactions,
            'chartLabels' => $labels,
            'chartData' => $data,
            'miniSparkLabels' => $miniSparkLabels,
            'miniOmzet' => $miniOmzet,
            'miniTransactions' => $miniTransactions,
            'auditSummary' => $auditSummary,
            'checkoutFailLastHour' => $checkoutFailLastHour,
            'checkoutFailThreshold' => $checkoutFailThreshold,
            'checkoutFailAnomaly' => $checkoutFailAnomaly,
            'checkoutFailAcknowledged' => $checkoutFailAcknowledged,
            'followupSummary' => $followupSummary,
            'cashReconSummary' => $cashReconSummary,
            'branchTransferKpis' => $branchTransferKpis,
            'branchDebtKpis' => $branchDebtKpis,
            'branchSupplierDebtKpis' => $branchSupplierDebtKpis,
        ]];
    }

    private function buildBranchTransferKpis(?User $user)
    {
        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        $isOwner = $user?->hasAnyRole(['owner']) ?? false;
        $approvalRules = (array) (StoreSetting::query()->first()?->approval_rules ?? []);
        $thresholdOverdue = max(1, (int) data_get($approvalRules, 'stock_transfer_kpi.overdue_warning_count', 3));
        $thresholdApproveSla = max(5, (int) data_get($approvalRules, 'stock_transfer_kpi.approve_sla_minutes', 60));
        $thresholdReceiveSla = max(5, (int) data_get($approvalRules, 'stock_transfer_kpi.receive_sla_minutes', 180));
        $thresholdDiscrepancyPct = max(0, min(100, (float) data_get($approvalRules, 'stock_transfer_kpi.discrepancy_warning_pct', 5)));

        $query = StockTransfer::query()
            ->with(['items:id,stock_transfer_id,requested_qty,received_qty'])
            ->where('created_at', '>=', now()->subDays(30));

        if (! $isOwner && $activeBranchId > 0) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('source_branch_id', $activeBranchId)
                    ->orWhere('destination_branch_id', $activeBranchId);
            });
        }

        $transfers = $query->get();
        if ($transfers->isEmpty()) {
            return collect();
        }

        $branchIds = $transfers->pluck('source_branch_id')->filter()->unique()->values();
        $branchNames = Branch::query()->whereIn('id', $branchIds)->pluck('name', 'id');

        return $transfers
            ->groupBy('source_branch_id')
            ->map(function ($rows, $branchId) use ($branchNames, $thresholdOverdue, $thresholdApproveSla, $thresholdReceiveSla, $thresholdDiscrepancyPct) {
                $approveMinutes = $rows
                    ->filter(fn ($t) => $t->approved_at && $t->created_at)
                    ->map(fn ($t) => $t->created_at->diffInMinutes($t->approved_at))
                    ->values();
                $receiveMinutes = $rows
                    ->filter(fn ($t) => $t->received_at && $t->approved_at)
                    ->map(fn ($t) => $t->approved_at->diffInMinutes($t->received_at))
                    ->values();
                $receivedItems = $rows
                    ->where('status', StockTransfer::STATUS_RECEIVED)
                    ->flatMap(fn ($t) => $t->items)
                    ->filter(fn ($item) => $item->received_qty !== null)
                    ->values();
                $discrepancyItems = $receivedItems
                    ->filter(fn ($item) => (int) $item->received_qty !== (int) $item->requested_qty)
                    ->count();

                return [
                    'branch_name' => (string) ($branchNames[(int) $branchId] ?? ('Branch #'.$branchId)),
                    'total_transfer' => (int) $rows->count(),
                    'overdue_transfer' => (int) $rows
                        ->whereIn('status', [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED])
                        ->filter(fn ($t) => optional($t->created_at)->lte(now()->subHours(24)))
                        ->count(),
                    'avg_approve_minutes' => $approveMinutes->isNotEmpty() ? (float) round($approveMinutes->avg(), 1) : null,
                    'avg_receive_minutes' => $receiveMinutes->isNotEmpty() ? (float) round($receiveMinutes->avg(), 1) : null,
                    'discrepancy_rate' => $receivedItems->count() > 0 ? (float) round(($discrepancyItems / $receivedItems->count()) * 100, 2) : 0.0,
                    'thresholds' => [
                        'overdue_warning_count' => $thresholdOverdue,
                        'approve_sla_minutes' => $thresholdApproveSla,
                        'receive_sla_minutes' => $thresholdReceiveSla,
                        'discrepancy_warning_pct' => $thresholdDiscrepancyPct,
                    ],
                ];
            })
            ->sortByDesc('total_transfer')
            ->values();
    }

    private function buildBranchDebtKpis(?User $user)
    {
        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        $isOwner = $user?->hasAnyRole(['owner']) ?? false;

        $query = CustomerDebt::query()->whereIn('status', ['active', 'overdue']);
        if (! $isOwner && $activeBranchId > 0) {
            $query->where('branch_id', $activeBranchId);
        }

        $rows = $query
            ->selectRaw('branch_id, COUNT(*) as active_count, SUM(CASE WHEN status = "overdue" THEN 1 ELSE 0 END) as overdue_count, COALESCE(SUM(remaining_amount),0) as total_remaining')
            ->groupBy('branch_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $branchNames = Branch::query()->whereIn('id', $rows->pluck('branch_id')->filter()->all())->pluck('name', 'id');

        return $rows->map(function ($row) use ($branchNames) {
            $activeCount = (int) ($row->active_count ?? 0);
            $overdueCount = (int) ($row->overdue_count ?? 0);
            return [
                'branch_name' => (string) ($branchNames[(int) $row->branch_id] ?? ('Branch #'.(int) $row->branch_id)),
                'active_count' => $activeCount,
                'overdue_count' => $overdueCount,
                'total_remaining' => (float) ($row->total_remaining ?? 0),
                'overdue_rate' => $activeCount > 0 ? round(($overdueCount / $activeCount) * 100, 2) : 0.0,
            ];
        })->sortByDesc('total_remaining')->values();
    }

    private function buildBranchSupplierDebtKpis(?User $user)
    {
        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        $isOwner = $user?->hasAnyRole(['owner']) ?? false;

        $query = SupplierPurchase::query()->whereIn('payment_status', ['unpaid', 'partial', 'overdue']);
        if (! $isOwner && $activeBranchId > 0) {
            $query->where('branch_id', $activeBranchId);
        }

        $rows = $query
            ->selectRaw('branch_id, COUNT(*) as open_count, SUM(CASE WHEN payment_status = "overdue" THEN 1 ELSE 0 END) as overdue_count, COALESCE(SUM(remaining_amount),0) as total_outstanding')
            ->groupBy('branch_id')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $branchNames = Branch::query()->whereIn('id', $rows->pluck('branch_id')->filter()->all())->pluck('name', 'id');

        return $rows->map(function ($row) use ($branchNames) {
            $openCount = (int) ($row->open_count ?? 0);
            $overdueCount = (int) ($row->overdue_count ?? 0);
            return [
                'branch_name' => (string) ($branchNames[(int) $row->branch_id] ?? ('Branch #'.(int) $row->branch_id)),
                'open_count' => $openCount,
                'overdue_count' => $overdueCount,
                'total_outstanding' => (float) ($row->total_outstanding ?? 0),
                'overdue_rate' => $openCount > 0 ? round(($overdueCount / $openCount) * 100, 2) : 0.0,
            ];
        })->sortByDesc('total_outstanding')->values();
    }

    public static function compactRupiah(float|int $value): string
    {
        $abs = abs((float) $value);

        if ($abs >= 1_000_000_000_000) {
            return 'Rp '.number_format($value / 1_000_000_000_000, 1, ',', '.').' T';
        }

        if ($abs >= 1_000_000_000) {
            return 'Rp '.number_format($value / 1_000_000_000, 1, ',', '.').' M';
        }

        if ($abs >= 1_000_000) {
            return 'Rp '.number_format($value / 1_000_000, 1, ',', '.').' Jt';
        }

        if ($abs >= 1_000) {
            return 'Rp '.number_format($value / 1_000, 1, ',', '.').' Rb';
        }

        return 'Rp '.number_format($value, 0, ',', '.');
    }

    private function resolveRange(Request $request, string $preset, string $startOverride = '', string $endOverride = ''): array
    {
        $today = Carbon::today();

        if ($preset === 'hari_ini') {
            return [$today->copy(), $today->copy()];
        }

        if ($preset === '30_hari') {
            return [$today->copy()->subDays(29), $today->copy()];
        }

        if ($preset === 'custom') {
            $start = $request->query('start_date', $startOverride);
            $end = $request->query('end_date', $endOverride);

            try {
                $startDate = $start ? Carbon::parse((string) $start)->startOfDay() : $today->copy()->subDays(6)->startOfDay();
                $endDate = $end ? Carbon::parse((string) $end)->endOfDay() : $today->copy()->endOfDay();
            } catch (\Throwable $e) {
                return [$today->copy()->subDays(6), $today->copy()];
            }

            if ($startDate->greaterThan($endDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }

            return [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()];
        }

        return [$today->copy()->subDays(6), $today->copy()];
    }

    private function percentageChange(float|int $current, float|int $previous): ?float
    {
        if ((float) $previous === 0.0) {
            return null;
        }

        return (($current - $previous) / abs($previous)) * 100;
    }

    private function branchAlertStateIdentity(string $baseKey): array
    {
        $user = auth()->user();
        $branchId = (int) ($user?->branch_id ?? 0);
        $isOwner = $user?->hasAnyRole(['owner']) ?? false;

        if ($isOwner || $branchId <= 0) {
            return [
                'key' => $baseKey,
                'branch_id' => null,
            ];
        }

        return [
            'key' => $baseKey,
            'branch_id' => $branchId,
        ];
    }
}
