<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CashierAuditLog;
use App\Models\Customer;
use App\Http\Requests\CheckoutRequest;
use App\Models\PosHold;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\CashReconciliation;
use App\Models\User;
use App\Enums\SaleStatus;
use App\Services\SaleService;
use App\Support\AppliesBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class PosController extends Controller
{
    use AppliesBranchScope;

    private const POS_PRODUCTS_PER_PAGE = 18;

    public function __construct(private readonly SaleService $saleService)
    {
    }

    public function index(Request $request)
    {
        $this->expireOverduePendingSales();
        $store = StoreSetting::query()->first();
        $query = trim((string) $request->string('q'));
        $categoryId = (int) $request->integer('category_id', 0);
        $productPage = max(1, (int) $request->integer('page', 1));
        $productsPager = $this->buildProductQuery($query, $categoryId)
            ->paginate(self::POS_PRODUCTS_PER_PAGE, ['id', 'name', 'sku', 'barcode', 'selling_price', 'stock', 'low_stock_threshold', 'unit', 'image'], 'page', $productPage);

        $recentSales = $this->applyBranchScope(Sale::query(), $request->user())
            ->with('user:id,name')
            ->latest('sold_at')
            ->limit(5)
            ->get(['id', 'invoice_number', 'user_id', 'total_amount', 'status', 'sold_at']);

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $customers = $this->applyBranchScope(Customer::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'phone', 'email', 'address']);

        $today = now()->toDateString();
        $shiftSales = $this->applyBranchScope(Sale::query(), $request->user())
            ->where('user_id', auth()->id())
            ->whereDate('sold_at', $today)
            ->get(['status', 'total_amount']);
        $shiftSummary = [
            'total_transactions' => $shiftSales->count(),
            'paid_transactions' => $shiftSales->where('status', SaleStatus::Paid)->count(),
            'pending_transactions' => $shiftSales->where('status', SaleStatus::Pending)->count(),
            'omzet_paid' => (float) $shiftSales->where('status', SaleStatus::Paid)->sum('total_amount'),
            'cash_expected' => (float) $this->applyBranchScope(Sale::query(), $request->user())
                ->where('user_id', auth()->id())
                ->whereDate('sold_at', $today)
                ->where('status', SaleStatus::Paid)
                ->where('payment_method', 'cash')
                ->sum('total_amount'),
        ];

        $quickPayPresets = collect($store?->quick_pay_presets ?? [10000, 20000, 50000, 100000])
            ->map(fn ($x) => (int) $x)
            ->filter(fn ($x) => $x > 0)
            ->take(6)
            ->values()
            ->all();
        $pendingFollowups = $this->applyBranchScope(Sale::query(), $request->user())
            ->where('status', SaleStatus::Pending)
            ->whereNotNull('payment_due_at')
            ->whereDate('sold_at', now()->toDateString())
            ->whereIn('payment_method', ['qris', 'debit', 'transfer', 'e_wallet'])
            ->whereBetween('payment_due_at', [now(), now()->addMinutes(15)])
            ->orderBy('payment_due_at')
            ->limit(5)
            ->get(['id', 'invoice_number', 'payment_method', 'payment_due_at']);

        return view('pos.index', [
            'products' => $productsPager->items(),
            'productPagination' => [
                'current_page' => $productsPager->currentPage(),
                'last_page' => $productsPager->lastPage(),
                'per_page' => $productsPager->perPage(),
                'total' => $productsPager->total(),
            ],
            'q' => $query,
            'categoryId' => $categoryId,
            'categories' => $categories,
            'recentSales' => $recentSales,
            'shiftSummary' => $shiftSummary,
            'customers' => $customers,
            'paymentMethodLimits' => [
                'cash' => 100_000_000,
                'qris' => 50_000_000,
                'debit' => 50_000_000,
                'transfer' => 100_000_000,
                'e_wallet' => 20_000_000,
            ],
            'paymentMethodOverpayRules' => is_array($store?->payment_overpay_rules) ? $store->payment_overpay_rules : [],
            'quickPayPresets' => $quickPayPresets,
            'buyXGetYRules' => is_array($store?->promo_buy_x_get_y_rules) ? $store->promo_buy_x_get_y_rules : [],
            'pendingTimeoutMinutes' => (int) ($store?->pending_non_cash_timeout_minutes ?? 30),
            'managerApprovalDiscountPct' => (float) env('POS_MANAGER_APPROVAL_DISCOUNT_PERCENT', 30),
            'pendingFollowups' => $pendingFollowups,
        ]);
    }

    public function search(Request $request)
    {
        $query = trim((string) $request->string('q'));
        $categoryId = (int) $request->integer('category_id', 0);
        $page = max(1, (int) $request->integer('page', 1));
        $productsPager = $this->buildProductQuery($query, $categoryId)
            ->paginate(self::POS_PRODUCTS_PER_PAGE, ['id', 'name', 'sku', 'barcode', 'selling_price', 'stock', 'low_stock_threshold', 'unit', 'image'], 'page', $page);

        return response()->json([
            'data' => $productsPager->items(),
            'meta' => [
                'current_page' => $productsPager->currentPage(),
                'last_page' => $productsPager->lastPage(),
                'per_page' => $productsPager->perPage(),
                'total' => $productsPager->total(),
            ],
        ]);
    }

    private function buildProductQuery(string $query, int $categoryId)
    {
        return $this->applyBranchScope(Product::query(), auth()->user())
            ->with('category:id,name')
            ->active()
            ->when($categoryId > 0, fn ($builder) => $builder->where('category_id', $categoryId))
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
                $builder->orderByRaw(
                    "CASE
                        WHEN barcode = ? THEN 1
                        WHEN sku = ? THEN 2
                        WHEN name LIKE ? THEN 3
                        ELSE 4
                    END",
                    [$query, $query, $query.'%']
                );
            })
            ->orderBy('name');
    }

    public function checkout(CheckoutRequest $request)
    {
        $payload = $request->validated();
        $printAfterCheckout = (bool) ($payload['print_after_checkout'] ?? false);
        $openReceiptPdf = (bool) ($payload['open_receipt_pdf'] ?? false);
        $sourceHoldId = (string) ($payload['source_hold_id'] ?? '');
        unset($payload['print_after_checkout']);
        unset($payload['open_receipt_pdf']);
        unset($payload['source_hold_id']);

        $sale = $this->saleService->createSale($request->user(), $payload);
        $sale->load('items.product');

        // Jika checkout berasal dari hold dan transaksi benar-benar lunas,
        // hapus hold agar tidak muncul lagi di daftar.
        if ($sourceHoldId !== '' && $sale->status === SaleStatus::Paid) {
            $deleted = PosHold::query()
                ->where('user_id', $request->user()->id)
                ->where('hold_code', $sourceHoldId)
                ->delete();

            if ($deleted > 0) {
                $this->logAudit($request, 'hold_deleted_after_checkout', [
                    'hold_id' => $sourceHoldId,
                    'sale_id' => $sale->id,
                    'invoice' => $sale->invoice_number,
                ]);
            }
        }

        $this->logAudit($request, 'checkout_success', [
            'sale_id' => $sale->id,
            'invoice' => $sale->invoice_number,
            'status' => $sale->status->value,
            'total_amount' => (float) $sale->total_amount,
            'qris_reference_id' => (string) ($payload['qris_reference_id'] ?? ''),
            'qris_issuer' => (string) ($payload['qris_issuer'] ?? ''),
        ]);

        $lowStockProducts = $sale->items
            ->filter(fn ($item) => $item->product && $item->product->isLowStock())
            ->pluck('product.name')
            ->filter()
            ->unique()
            ->values();

        return redirect()
            ->route('sales.show', ['sale' => $sale, 'autoprint' => $printAfterCheckout ? 1 : 0, 'open_receipt_pdf' => $openReceiptPdf ? 1 : 0, 'clear_hold_id' => $sourceHoldId])
            ->with('success', 'Transaksi berhasil disimpan.')
            ->with('stock_warning', $lowStockProducts->all());
    }

    public function quickRefund(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
            'manager_approval_email' => ['required', 'email', 'max:150'],
            'manager_approval_password' => ['required', 'string', 'max:120'],
        ]);

        $manager = $this->resolveManagerApproval(
            (string) $validated['manager_approval_email'],
            (string) $validated['manager_approval_password']
        );

        $invoice = trim((string) $validated['invoice_number']);
        $reason = trim((string) $validated['reason']);
        $actorName = $request->user()?->name ?? 'system';

        DB::transaction(function () use ($invoice, $reason, $manager, $request, $actorName): void {
            $sale = $this->applyBranchScope(Sale::query(), $request->user())
                ->with('items')
                ->where('invoice_number', $invoice)
                ->lockForUpdate()
                ->first();

            if (! $sale) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'Invoice tidak ditemukan.',
                ]);
            }
            if ($sale->status !== SaleStatus::Paid) {
                throw ValidationException::withMessages([
                    'invoice_number' => 'Retur cepat hanya untuk transaksi berstatus PAID.',
                ]);
            }

            $productIds = $sale->items->pluck('product_id')->filter()->unique()->values();
            $products = $this->applyBranchScope(Product::query(), $request->user())
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($sale->items as $item) {
                $product = $products->get($item->product_id);
                if ($product) {
                    $product->increment('stock', (int) $item->quantity);
                }
            }

            $sale->status = SaleStatus::Cancelled;
            $sale->payment_breakdown = null;
            $sale->change_amount = 0;
            $sale->note = trim(($sale->note ? $sale->note."\n" : '').'[REFUND-POS] '.$reason.' | by: '.$actorName.' | approved: '.$manager->name.' | at: '.now()->format('d/m/Y H:i:s'));
            $sale->save();

            $this->logAudit($request, 'sale_refunded_pos', [
                'sale_id' => $sale->id,
                'invoice' => $sale->invoice_number,
                'reason' => $reason,
                'approved_by' => $manager->id,
                'approved_name' => $manager->name,
            ]);
        });

        return response()->json([
            'ok' => true,
            'message' => "Retur cepat invoice {$invoice} berhasil disimpan.",
        ]);
    }

    public function reconcileShift(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $shiftDate = now()->toDateString();
        $expectedCash = (float) $this->applyBranchScope(Sale::query(), $request->user())
            ->where('user_id', $request->user()->id)
            ->whereDate('sold_at', $shiftDate)
            ->where('status', SaleStatus::Paid)
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $actualCash = (float) $payload['actual_cash'];
        $difference = $actualCash - $expectedCash;

        $reconcile = CashReconciliation::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'shift_date' => $shiftDate,
            ],
            [
                'branch_id' => $request->user()->branch_id,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
                'note' => trim((string) ($payload['note'] ?? '')),
            ]
        );

        $this->logAudit($request, 'shift_cash_reconciled', [
            'shift_date' => $shiftDate,
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'difference' => $difference,
            'reconciliation_id' => $reconcile->id,
        ]);

        return response()->json([
            'ok' => true,
            'data' => [
                'shift_date' => $shiftDate,
                'expected_cash' => $expectedCash,
                'actual_cash' => $actualCash,
                'difference' => $difference,
            ],
        ]);
    }

    public function auditEvent(Request $request)
    {
        $payload = $request->validate([
            'action' => ['required', 'string', 'max:60'],
            'context' => ['nullable', 'array'],
        ]);

        $allowed = ['hold_saved', 'hold_loaded', 'hold_deleted', 'hold_overwritten', 'hold_renamed', 'checkout_failed_client', 'checkout_submit_started', 'cart_cleared', 'idle_warning', 'stock_sync_adjusted'];
        if (! in_array($payload['action'], $allowed, true)) {
            return response()->json(['ok' => false], 422);
        }

        $this->logAudit($request, $payload['action'], $payload['context'] ?? []);

        return response()->json(['ok' => true]);
    }

    public function listHolds(Request $request): JsonResponse
    {
        $this->cleanupExpiredHolds($request->user()->id);
        $days = (int) $request->integer('days', 0);
        $fromDate = $days > 0 ? now()->subDays($days) : null;

        $holds = $this->applyBranchScope(PosHold::query(), $request->user())
            ->where('user_id', $request->user()->id)
            ->when($fromDate, fn ($q) => $q->where('updated_at', '>=', $fromDate))
            ->latest('updated_at')
            ->limit(20)
            ->get()
            ->map(fn (PosHold $hold) => $this->transformHold($hold))
            ->values();

        return response()->json(['data' => $holds]);
    }

    public function saveHold(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'id' => ['nullable', 'string', 'max:40'],
            'label' => ['required', 'string', 'max:100'],
            'cart' => ['required', 'array', 'min:1'],
            'cart.*.id' => ['required'],
            'cart.*.name' => ['required', 'string'],
            'cart.*.quantity' => ['required', 'numeric', 'min:1'],
            'cart.*.price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'paid' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', Rule::in(['paid', 'pending'])],
            'payment_method' => ['nullable', 'string', 'max:40'],
            'split_payment_enabled' => ['nullable', 'boolean'],
            'split_payments' => ['nullable', 'array'],
            'qris_reference_id' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9][A-Za-z0-9\-_.\/]{2,119}$/'],
            'qris_issuer' => ['nullable', 'string', 'max:120'],
            'customer_id' => ['nullable'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'customer_email' => ['nullable', 'string', 'max:150'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:1000'],
            'saved_at' => ['nullable', 'string', 'max:80'],
        ]);

        $user = $request->user();
        $holdCode = trim((string) ($payload['id'] ?? ''));
        if ($holdCode === '') {
            $holdCode = 'HOLD-'.now()->format('YmdHis').'-'.strtoupper(substr((string) str()->uuid(), 0, 6));
        }

        $hold = PosHold::query()->firstOrNew([
            'user_id' => $user->id,
            'hold_code' => $holdCode,
        ]);

        $hold->branch_id = $user->branch_id;
        $hold->label = (string) $payload['label'];
        $hold->payload = $payload;
        $hold->total_qty = (int) collect($payload['cart'] ?? [])->sum(fn ($row) => (float) ($row['quantity'] ?? 0));
        $hold->save();

        return response()->json([
            'ok' => true,
            'data' => $this->transformHold($hold),
        ]);
    }

    public function loadHold(Request $request, PosHold $hold): JsonResponse
    {
        abort_unless($hold->user_id === $request->user()->id, 404);

        return response()->json([
            'ok' => true,
            'data' => $this->transformHold($hold),
        ]);
    }

    public function deleteHold(Request $request, PosHold $hold): JsonResponse
    {
        abort_unless($hold->user_id === $request->user()->id, 404);
        $hold->delete();

        return response()->json(['ok' => true]);
    }

    public function renameHold(Request $request, PosHold $hold): JsonResponse
    {
        abort_unless($hold->user_id === $request->user()->id, 404);
        $payload = $request->validate([
            'label' => ['required', 'string', 'max:100'],
        ]);

        $hold->label = trim((string) $payload['label']);
        $hold->save();

        return response()->json([
            'ok' => true,
            'data' => $this->transformHold($hold),
        ]);
    }

    private function transformHold(PosHold $hold): array
    {
        $payload = is_array($hold->payload) ? $hold->payload : [];

        return [
            'id' => $hold->hold_code,
            'label' => $hold->label,
            'cart' => $payload['cart'] ?? [],
            'discount' => (float) ($payload['discount'] ?? 0),
            'tax' => (float) ($payload['tax'] ?? 0),
            'paid' => (float) ($payload['paid'] ?? 0),
            'status' => $payload['status'] ?? 'paid',
            'payment_method' => $payload['payment_method'] ?? 'cash',
            'split_payment_enabled' => (bool) ($payload['split_payment_enabled'] ?? false),
            'split_payments' => $payload['split_payments'] ?? [],
            'qris_reference_id' => (string) ($payload['qris_reference_id'] ?? ''),
            'qris_issuer' => (string) ($payload['qris_issuer'] ?? ''),
            'customer_id' => (string) ($payload['customer_id'] ?? ''),
            'customer_name' => (string) ($payload['customer_name'] ?? ''),
            'customer_phone' => (string) ($payload['customer_phone'] ?? ''),
            'customer_email' => (string) ($payload['customer_email'] ?? ''),
            'customer_address' => (string) ($payload['customer_address'] ?? ''),
            'note' => (string) ($payload['note'] ?? ''),
            'saved_at' => (string) ($payload['saved_at'] ?? optional($hold->updated_at)->toIso8601String()),
            'totalQty' => (int) $hold->total_qty,
        ];
    }

    private function cleanupExpiredHolds(int $userId): void
    {
        PosHold::query()
            ->where('user_id', $userId)
            ->where('branch_id', auth()->user()?->branch_id)
            ->where('updated_at', '<', now()->subHours(24))
            ->delete();
    }

    private function logAudit(Request $request, string $action, array $context = []): void
    {
        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'context' => $context,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    private function expireOverduePendingSales(): void
    {
        $overdue = $this->applyBranchScope(Sale::query(), auth()->user())
            ->where('status', SaleStatus::Pending)
            ->whereNotNull('payment_due_at')
            ->where('payment_due_at', '<', now())
            ->whereIn('payment_method', ['qris', 'debit', 'transfer', 'e_wallet'])
            ->get();

        foreach ($overdue as $sale) {
            $sale->status = SaleStatus::Cancelled;
            $sale->note = trim(($sale->note ? $sale->note."\n" : '').'[EXPIRED] pending non-cash melewati timeout pada '.now()->format('d/m/Y H:i:s'));
            $sale->save();
        }
    }

    private function resolveManagerApproval(string $email, string $password): User
    {
        $manager = User::query()
            ->where('email', trim($email))
            ->whereIn('role', ['owner', 'admin'])
            ->first();

        if (! $manager || ! Hash::check($password, (string) $manager->password)) {
            throw ValidationException::withMessages([
                'manager_approval_email' => 'Approval manager tidak valid.',
            ]);
        }

        return $manager;
    }
}
