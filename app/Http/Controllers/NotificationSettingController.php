<?php

namespace App\Http\Controllers;

use App\Enums\SaleStatus;
use App\Models\CashierAuditLog;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Support\AppliesBranchScope;
use App\Support\TelegramNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationSettingController extends Controller
{
    use AppliesBranchScope;

    public function edit()
    {
        $setting = StoreSetting::query()->firstOrCreate(
            ['id' => 1],
            ['name' => config('app.name', 'BINTANG')]
        );

        $recentTelegramLogs = $this->applyBranchScope(CashierAuditLog::query(), request()->user())
            ->whereIn('action', ['telegram_send_ok', 'telegram_send_failed'])
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.notification-settings', [
            'setting' => $setting,
            'recentTelegramLogs' => $recentTelegramLogs,
            'defaultChatId' => env('TELEGRAM_CHAT_ID', ''),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'telegram_enabled' => ['nullable', 'boolean'],
            'telegram_notify_checkout_anomaly' => ['nullable', 'boolean'],
            'telegram_checkout_fail_threshold' => ['required', 'integer', 'min:1', 'max:100'],
            'telegram_approval_sla_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'telegram_daily_summary_enabled' => ['nullable', 'boolean'],
            'telegram_daily_summary_time' => ['required', 'date_format:H:i'],
            'telegram_override_chat_id' => ['nullable', 'string', 'max:40'],
            'stock_opname_require_manager_approval' => ['nullable', 'boolean'],
        ]);

        $setting = StoreSetting::query()->firstOrFail();
        $setting->update([
            'telegram_enabled' => (bool) ($validated['telegram_enabled'] ?? false),
            'telegram_notify_checkout_anomaly' => (bool) ($validated['telegram_notify_checkout_anomaly'] ?? false),
            'telegram_checkout_fail_threshold' => (int) $validated['telegram_checkout_fail_threshold'],
            'telegram_approval_sla_minutes' => (int) $validated['telegram_approval_sla_minutes'],
            'telegram_daily_summary_enabled' => (bool) ($validated['telegram_daily_summary_enabled'] ?? false),
            'telegram_daily_summary_time' => $validated['telegram_daily_summary_time'],
            'telegram_override_chat_id' => trim((string) ($validated['telegram_override_chat_id'] ?? '')) ?: null,
            'stock_opname_require_manager_approval' => (bool) ($validated['stock_opname_require_manager_approval'] ?? false),
        ]);

        return back()->with('status', 'Pengaturan notifikasi Telegram berhasil disimpan.');
    }

    public function test(Request $request)
    {
        $request->validate([
            'chat_id' => ['nullable', 'string', 'max:40'],
        ]);

        $chatId = trim((string) $request->input('chat_id', ''));
        $target = $chatId !== '' ? $chatId : TelegramNotifier::defaultChatId();
        $ok = TelegramNotifier::send('Tes notifikasi Telegram POS: koneksi berhasil.', 'manual_test', $target);

        return response()->json([
            'ok' => $ok,
            'message' => $ok ? 'Pesan tes berhasil dikirim.' : 'Gagal kirim tes. Cek token/chat id.',
        ]);
    }

    public function previewWeeklySla(): \Illuminate\Http\JsonResponse
    {
        $user = request()->user();
        $isOwner = (bool) ($user?->hasAnyRole(['owner']) ?? false);
        $branchId = (int) ($user?->branch_id ?? 0);
        $end = now()->endOfDay();
        $start = now()->subDays(6)->startOfDay();

        $reviewed = DB::table('approval_requests')
            ->when(! $isOwner && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->whereBetween('reviewed_at', [$start, $end]);

        $reviewedCount = (int) (clone $reviewed)->count();
        $rejectedCount = (int) (clone $reviewed)->where('status', 'rejected')->count();
        $autoExpiredCount = (int) DB::table('approval_requests')
            ->when(! $isOwner && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'rejected')
            ->whereBetween('reviewed_at', [$start, $end])
            ->where('review_note', 'like', '[AUTO-EXPIRED]%')
            ->count();

        $reviewRows = DB::table('approval_requests')
            ->when(! $isOwner && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->whereIn('status', ['approved', 'rejected'])
            ->whereNotNull('reviewed_at')
            ->whereBetween('reviewed_at', [$start, $end])
            ->get(['created_at', 'reviewed_at']);
        $avgReviewMinutes = (int) round((float) $reviewRows->map(function ($row) {
            try {
                return max(0, Carbon::parse((string) $row->created_at)->diffInMinutes(Carbon::parse((string) $row->reviewed_at)));
            } catch (\Throwable) {
                return 0;
            }
        })->avg());

        $overduePending = (int) DB::table('approval_requests')
            ->when(! $isOwner && $branchId > 0, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(120))
            ->count();

        $topBottleneck = DB::table('approval_requests')
            ->leftJoin('users', 'users.id', '=', 'approval_requests.assigned_to')
            ->selectRaw('approval_requests.assigned_to, users.name as assignee_name, COUNT(*) as total')
            ->when(! $isOwner && $branchId > 0, fn ($q) => $q->where('approval_requests.branch_id', $branchId))
            ->where('status', 'pending')
            ->whereNotNull('approval_requests.assigned_to')
            ->groupBy('approval_requests.assigned_to', 'users.name')
            ->orderByDesc('total')
            ->limit(3)
            ->get();

        $lines = [
            'Weekly SLA Report',
            ...((! $isOwner && $branchId > 0) ? ['Cabang Scope: #'.$branchId] : []),
            'Periode: '.$start->format('d/m/Y').' - '.$end->format('d/m/Y'),
            'Reviewed: '.number_format($reviewedCount, 0, ',', '.'),
            'Reject Rate: '.($reviewedCount > 0 ? number_format(($rejectedCount / $reviewedCount) * 100, 1, ',', '.') : '0,0').'%',
            'Avg Review: '.number_format($avgReviewMinutes, 0, ',', '.').' menit',
            'Auto Expired: '.number_format($autoExpiredCount, 0, ',', '.'),
            'Pending Overdue Saat Ini: '.number_format($overduePending, 0, ',', '.'),
        ];

        if ($topBottleneck->isNotEmpty()) {
            $lines[] = 'Top Bottleneck Assignee:';
            foreach ($topBottleneck as $idx => $row) {
                $name = trim((string) ($row->assignee_name ?? ''));
                if ($name === '') {
                    $name = '#'.(int) $row->assigned_to;
                }
                $lines[] = ($idx + 1).'. '.$name.' ('.(int) $row->total.' pending)';
            }
        }

        return response()->json([
            'ok' => true,
            'preview' => implode("\n", $lines),
        ]);
    }

    public static function buildDailySummaryMessage(?Carbon $date = null): string
    {
        return self::buildDailySummaryMessageForBranch($date, null, true);
    }

    public static function buildDailySummaryMessageForBranch(?Carbon $date = null, ?int $branchId = null, bool $isOwner = false): string
    {
        $date = ($date ?: now())->copy();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $omzet = (float) Sale::query()
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('sold_at', [$start, $end])
            ->where('status', SaleStatus::Paid->value)
            ->sum('total_amount');

        $trx = (int) Sale::query()
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('sold_at', [$start, $end])
            ->where('status', SaleStatus::Paid->value)
            ->count();

        $hpp = (float) SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('sales.branch_id', $branchId))
            ->where('sales.status', SaleStatus::Paid->value)
            ->whereBetween('sales.sold_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(sale_items.purchase_price * sale_items.quantity), 0) as total')
            ->value('total');

        $expenses = (float) Expense::query()
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $net = $omzet - $hpp - $expenses;
        $low = Product::query()
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->active()
            ->whereColumn('stock', '<=', 'low_stock_threshold')
            ->count();
        $out = Product::query()
            ->when(! $isOwner && $branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->active()
            ->where('stock', '<=', 0)
            ->count();

        $idr = fn ($v) => 'Rp '.number_format((float) $v, 0, ',', '.');

        return implode("\n", [
            'Ringkasan Harian POS ('.$date->translatedFormat('d M Y').')',
            '- Omzet: '.$idr($omzet),
            '- Transaksi Paid: '.number_format($trx, 0, ',', '.'),
            '- HPP: '.$idr($hpp),
            '- Pengeluaran: '.$idr($expenses),
            '- Laba Bersih: '.$idr($net),
            '- Produk Stok Menipis: '.number_format($low, 0, ',', '.'),
            '- Produk Stok Habis: '.number_format($out, 0, ',', '.'),
        ]);
    }
}
