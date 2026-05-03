<?php

namespace App\Http\Controllers;

use App\Models\CashierAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseItem;
use App\Models\SupplierPurchasePayment;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierManagementController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $suppliers = $this->applyBranchScope(Supplier::query(), $request->user())
            ->when($q !== '', function ($builder) use ($q) {
                $builder->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                });
            })
            ->when($status === 'active', fn ($builder) => $builder->where('is_active', true))
            ->when($status === 'inactive', fn ($builder) => $builder->where('is_active', false))
            ->latest('id')
            ->paginate(10, ['*'], 'suppliers_page')
            ->withQueryString();

        $purchases = $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->with([
                'supplier:id,name,code',
                'creator:id,name',
                'items' => fn ($q) => $q->orderBy('id'),
                'payments' => fn ($q) => $q->latest('paid_at')->limit(3),
            ])
            ->withCount('items')
            ->latest('id')
            ->paginate(12, ['*'], 'purchases_page')
            ->withQueryString();

        $purchaseIds = $purchases->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $supplierPurchaseApprovals = [];
        if ($purchaseIds !== []) {
            $supplierPurchaseApprovals = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
                ->where('type', 'supplier.purchase_approval')
                ->latest('id')
                ->get(['id', 'status', 'payload', 'review_note', 'reviewed_at'])
                ->filter(fn (ApprovalRequest $approval) => in_array((int) data_get((array) $approval->payload, 'supplier_purchase_id'), $purchaseIds, true))
                ->unique(fn (ApprovalRequest $approval) => (int) data_get((array) $approval->payload, 'supplier_purchase_id'))
                ->mapWithKeys(fn (ApprovalRequest $approval) => [(int) data_get((array) $approval->payload, 'supplier_purchase_id') => [
                    'id' => (int) $approval->id,
                    'status' => (string) $approval->status,
                    'review_note' => (string) ($approval->review_note ?? ''),
                    'reviewed_at' => $approval->reviewed_at?->format('d/m/Y H:i'),
                ]])
                ->all();
        }

        $supplierDebtSummary = [
            'total_outstanding' => (float) $this->applyBranchScope(SupplierPurchase::query(), $request->user())
                ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                ->sum('remaining_amount'),
            'due_this_week' => (float) $this->applyBranchScope(SupplierPurchase::query(), $request->user())
                ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                ->whereNotNull('due_date')
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->sum('remaining_amount'),
            'overdue_total' => (float) $this->applyBranchScope(SupplierPurchase::query(), $request->user())
                ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString())
                ->sum('remaining_amount'),
        ];

        $supplierOptions = $this->applyBranchScope(Supplier::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.suppliers', [
            'suppliers' => $suppliers,
            'purchases' => $purchases,
            'supplierOptions' => $supplierOptions,
            'supplierDebtSummary' => $supplierDebtSummary,
            'supplierPurchaseApprovals' => $supplierPurchaseApprovals,
            'filters' => compact('q', 'status'),
        ]);
    }

    public function suppliersStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branchId = ActiveBranchContext::resolveBranchId($request->user());
        $code = $this->generateSupplierCode($branchId);

        Supplier::query()->create([
            'branch_id' => $branchId,
            'name' => trim((string) $validated['name']),
            'code' => $code,
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
            'email' => trim((string) ($validated['email'] ?? '')) ?: null,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'payment_term_days' => 0,
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return back()->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function suppliersUpdate(Request $request, Supplier $supplier)
    {
        $user = $request->user();
        $supplier = ($user && $user->hasAnyRole(['owner']))
            ? Supplier::query()->findOrFail($supplier->id)
            : $this->applyBranchScope(Supplier::query(), $user)->findOrFail($supplier->id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:60', 'alpha_dash'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branchId = ActiveBranchContext::resolveBranchId($request->user());
        $code = strtoupper(trim((string) $validated['code']));
        $exists = Supplier::query()
            ->where('branch_id', $branchId)
            ->where('code', $code)
            ->whereKeyNot($supplier->id)
            ->exists();
        if ($exists) {
            return back()->withErrors(['code' => 'Kode supplier sudah dipakai pada cabang ini.'])->withInput();
        }

        $supplier->update([
            'name' => trim((string) $validated['name']),
            'code' => $code,
            'phone' => trim((string) ($validated['phone'] ?? '')) ?: null,
            'email' => trim((string) ($validated['email'] ?? '')) ?: null,
            'address' => trim((string) ($validated['address'] ?? '')) ?: null,
            'payment_term_days' => 0,
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('status', 'Supplier berhasil diperbarui.');
    }

    public function suppliersDestroy(Supplier $supplier)
    {
        $user = request()->user();
        $supplier = ($user && $user->hasAnyRole(['owner']))
            ? Supplier::query()->findOrFail($supplier->id)
            : $this->applyBranchScope(Supplier::query(), $user)->findOrFail($supplier->id);

        if (SupplierPurchase::query()->where('supplier_id', $supplier->id)->exists()) {
            return back()->withErrors(['supplier' => 'Supplier tidak bisa dihapus karena sudah punya transaksi pembelian.']);
        }

        $supplier->delete();

        return back()->with('status', 'Supplier berhasil dihapus.');
    }

    public function purchasesStore(Request $request)
    {
        $validated = $this->validatePurchasePayload($request, true);

        $branchId = ActiveBranchContext::resolveBranchId($request->user());
        $supplier = $this->applyBranchScope(Supplier::query(), $request->user())
            ->findOrFail((int) $validated['supplier_id']);
        $orderedAt = Carbon::parse((string) $validated['ordered_at']);
        $paymentTermDays = (int) ($validated['payment_term_days'] ?? 0);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $shipping = (float) ($validated['shipping_amount'] ?? 0);
        $downPayment = (float) ($validated['down_payment_amount'] ?? 0);
        $downPaymentMethod = (string) ($validated['down_payment_method'] ?? 'cash');

        $approvalQueued = false;
        DB::transaction(function () use ($validated, $request, $branchId, $supplier, $orderedAt, $paymentTermDays, $discount, $tax, $shipping, $downPayment, $downPaymentMethod, &$approvalQueued): void {
            $purchase = SupplierPurchase::query()->create([
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'created_by' => $request->user()?->id,
                'number' => $this->generatePurchaseNumber(),
                'status' => 'draft',
                'ordered_at' => $orderedAt->toDateString(),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $paymentTermDays > 0 ? $orderedAt->copy()->addDays($paymentTermDays)->toDateString() : null,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            ]);

            $subtotal = 0.0;
            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = $qty * $unitCost;
                $subtotal += $lineTotal;

                SupplierPurchaseItem::query()->create([
                    'supplier_purchase_id' => $purchase->id,
                    'product_id' => null,
                    'product_name' => trim((string) $item['product_name']),
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ]);
            }

            $total = max($subtotal - $discount + $tax + $shipping, 0);
            $requiresApproval = $downPayment > 0 && $this->requiresOwnerApprovalForSupplierPayment($request, $purchase, min($downPayment, $total));
            $paid = $requiresApproval ? 0.0 : min($downPayment, $total);
            $remaining = max($total - $paid, 0);
            $purchase->update([
                'subtotal' => $subtotal,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining <= 0
                    ? 'paid'
                    : ($paid > 0 ? 'partial' : (($purchase->due_date && Carbon::parse((string) $purchase->due_date)->isPast()) ? 'overdue' : 'unpaid')),
                'paid_at' => $remaining <= 0 && $paid > 0 ? now() : null,
            ]);

            if ($requiresApproval) {
                $this->createSupplierPaymentApproval(
                    $request,
                    $purchase,
                    min($downPayment, $total),
                    $downPaymentMethod,
                    'Uang muka pembelian supplier sebelum barang diterima.'
                );
                $approvalQueued = true;
            } elseif ($paid > 0) {
                SupplierPurchasePayment::query()->create([
                    'supplier_purchase_id' => $purchase->id,
                    'received_by' => $request->user()?->id,
                    'paid_at' => now(),
                    'amount' => $paid,
                    'payment_method' => $downPaymentMethod,
                    'note' => 'Uang muka pembelian supplier.',
                ]);
            }

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $branchId,
                'action' => 'supplier_purchase_created',
                'context' => [
                    'supplier_purchase_id' => $purchase->id,
                    'number' => $purchase->number,
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'total_amount' => $total,
                    'down_payment_amount' => $paid,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', $approvalQueued
            ? 'Pembelian supplier berhasil dibuat. DP masuk antrian approval owner.'
            : 'Pembelian supplier berhasil dibuat.');
    }

    public function purchasesRequestApproval(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with('supplier')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with('supplier')->findOrFail($purchase->id);

        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Hanya draft pembelian yang bisa diajukan persetujuan.']);
        }

        $existing = ApprovalRequest::query()
            ->where('branch_id', $purchase->branch_id)
            ->where('type', 'supplier.purchase_approval')
            ->whereIn('status', ['pending', 'approved'])
            ->where('payload->supplier_purchase_id', $purchase->id)
            ->latest('id')
            ->first();

        if ($existing?->status === 'pending') {
            return back()->with('status', 'Pembelian ini sudah menunggu persetujuan owner.');
        }
        if ($existing?->status === 'approved') {
            return back()->with('status', 'Pembelian ini sudah disetujui owner.');
        }

        ApprovalRequest::query()->create([
            'branch_id' => $purchase->branch_id,
            'type' => 'supplier.purchase_approval',
            'status' => 'pending',
            'requested_by' => (int) $request->user()->id,
            'title' => "Persetujuan Pembelian {$purchase->number}",
            'reason' => 'Persetujuan pembelian dari supplier sebelum barang diterima.',
            'payload' => [
                'supplier_purchase_id' => (int) $purchase->id,
                'number' => (string) $purchase->number,
                'supplier_name' => (string) ($purchase->supplier?->name ?? '-'),
                'total_amount' => (float) $purchase->total_amount,
            ],
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'approval_request_created',
            'context' => [
                'type' => 'supplier.purchase_approval',
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);

        return back()->with('status', 'Pembelian supplier diajukan ke approval owner.');
    }

    public function purchasesUpdate(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with('items')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with('items')->findOrFail($purchase->id);

        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Hanya pembelian draft yang bisa diedit.']);
        }

        $validated = $this->validatePurchasePayload($request, false);
        $supplier = $this->applyBranchScope(Supplier::query(), $request->user())
            ->findOrFail((int) $validated['supplier_id']);
        $orderedAt = Carbon::parse((string) $validated['ordered_at']);
        $paymentTermDays = (int) ($validated['payment_term_days'] ?? 0);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $shipping = (float) ($validated['shipping_amount'] ?? 0);

        DB::transaction(function () use ($purchase, $validated, $request, $supplier, $orderedAt, $paymentTermDays, $discount, $tax, $shipping): void {
            $locked = SupplierPurchase::query()->with('items')->lockForUpdate()->findOrFail($purchase->id);
            if ($locked->status !== 'draft') {
                abort(422, 'Status pembelian berubah, edit draft dibatalkan.');
            }

            $subtotal = 0.0;
            $newItems = [];
            foreach ($validated['items'] as $item) {
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];
                $lineTotal = $qty * $unitCost;
                $subtotal += $lineTotal;
                $newItems[] = [
                    'product_id' => null,
                    'product_name' => trim((string) $item['product_name']),
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            $total = max($subtotal - $discount + $tax + $shipping, 0);
            $paid = (float) $locked->paid_amount;
            if ($total < $paid) {
                abort(422, 'Total baru lebih kecil dari nominal yang sudah dibayar. Kurangi pembayaran dulu atau sesuaikan nilai draft.');
            }

            $locked->items()->delete();
            foreach ($newItems as $item) {
                $locked->items()->create($item);
            }

            $remaining = max($total - $paid, 0);
            $dueDate = $paymentTermDays > 0 ? $orderedAt->copy()->addDays($paymentTermDays)->toDateString() : null;
            $locked->update([
                'supplier_id' => $supplier->id,
                'ordered_at' => $orderedAt->toDateString(),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'total_amount' => $total,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining <= 0
                    ? 'paid'
                    : ($paid > 0 ? 'partial' : (($dueDate && Carbon::parse($dueDate)->isPast()) ? 'overdue' : 'unpaid')),
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            ]);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $locked->branch_id,
                'action' => 'supplier_purchase_draft_updated',
                'context' => [
                    'supplier_purchase_id' => $locked->id,
                    'number' => $locked->number,
                    'supplier_id' => $supplier->id,
                    'total_amount' => $total,
                    'remaining_amount' => $remaining,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Draft pembelian berhasil diperbarui.');
    }

    public function purchasesReceive(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Hanya pembelian status draft yang bisa diterima.']);
        }
        if (! $this->supplierPurchaseApproved($purchase)) {
            return back()->withErrors(['purchase' => 'Pembelian harus disetujui owner sebelum barang diterima.']);
        }

        DB::transaction(function () use ($purchase, $request): void {
            $purchase->load('items.product');
            foreach ($purchase->items as $item) {
                if (! $item->product) {
                    continue;
                }

                $item->product->increment('stock', (int) $item->quantity);
                $item->product->update([
                    'purchase_price' => (float) $item->unit_cost,
                ]);
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $purchase->branch_id,
                'action' => 'supplier_purchase_received',
                'context' => [
                    'supplier_purchase_id' => $purchase->id,
                    'number' => $purchase->number,
                    'items_count' => $purchase->items->count(),
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Pembelian diterima, stok berhasil diperbarui.');
    }

    public function purchasesPay(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'debit', 'qris', 'e_wallet'])],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($purchase->status, ['draft', 'received'], true)) {
            return back()->withErrors(['purchase' => 'Pembayaran supplier hanya bisa untuk pembelian draft atau received.']);
        }
        if ($purchase->status === 'draft' && ! $this->supplierPurchaseApproved($purchase)) {
            return back()->withErrors(['purchase' => 'Pembelian harus disetujui owner sebelum pembayaran draft dicatat.']);
        }

        if ($this->requiresOwnerApprovalForSupplierPayment($request, $purchase, (float) $validated['amount'])) {
            $this->createSupplierPaymentApproval(
                $request,
                $purchase,
                (float) $validated['amount'],
                (string) $validated['payment_method'],
                trim((string) ($validated['note'] ?? '')) ?: 'Pembayaran supplier berisiko.'
            );

            return back()->with('status', 'Pembayaran berisiko masuk antrian approval owner.');
        }

        DB::transaction(function () use ($purchase, $request, $validated): void {
            $locked = SupplierPurchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ((float) $locked->remaining_amount <= 0) {
                abort(422, 'Hutang supplier ini sudah lunas.');
            }

            $amount = min((float) $validated['amount'], (float) $locked->remaining_amount);
            $newRemaining = $this->recordSupplierPayment(
                $locked,
                $request,
                $amount,
                (string) $validated['payment_method'],
                trim((string) ($validated['note'] ?? '')) ?: null,
                ! empty($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : now()
            );

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $locked->branch_id,
                'action' => 'supplier_purchase_paid',
                'context' => [
                    'supplier_purchase_id' => $locked->id,
                    'number' => $locked->number,
                    'payment_amount' => $amount,
                    'remaining_amount' => $newRemaining,
                    'payment_status' => $locked->fresh()->payment_status,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Pembayaran hutang supplier berhasil dicatat.');
    }

    private function generatePurchaseNumber(): string
    {
        $prefix = 'PO-' . now()->format('Ymd');
        $latest = SupplierPurchase::query()
            ->where('number', 'like', $prefix . '-%')
            ->latest('id')
            ->value('number');

        $next = 1;
        if (is_string($latest)) {
            $parts = explode('-', $latest);
            $last = (int) end($parts);
            $next = $last + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }

    private function validatePurchasePayload(Request $request, bool $allowDownPayment): array
    {
        $manualItems = collect((array) $request->input('items', []))
            ->filter(fn ($row) => trim((string) ($row['product_name'] ?? '')) !== '')
            ->values()
            ->all();
        $bulkItems = $this->parseBulkPurchaseItems((string) $request->input('bulk_items', ''));
        $rawItems = collect($manualItems)->merge($bulkItems)->values()->all();
        $request->merge(['items' => $rawItems]);

        $rules = [
            'supplier_id' => ['required', 'integer', Rule::exists('suppliers', 'id')],
            'ordered_at' => ['required', 'date'],
            'payment_term_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:160'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];

        if ($allowDownPayment) {
            $rules['down_payment_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['down_payment_method'] = ['nullable', Rule::in(['cash', 'transfer', 'debit', 'qris', 'e_wallet'])];
        }

        return $request->validate($rules);
    }

    private function requiresOwnerApprovalForSupplierPayment(Request $request, SupplierPurchase $purchase, float $amount): bool
    {
        if ($request->user()?->hasAnyRole(['owner'])) {
            return false;
        }

        return false;
    }

    private function supplierPurchaseApproved(SupplierPurchase $purchase): bool
    {
        return ApprovalRequest::query()
            ->where('branch_id', $purchase->branch_id)
            ->where('type', 'supplier.purchase_approval')
            ->where('status', 'approved')
            ->where('payload->supplier_purchase_id', $purchase->id)
            ->exists();
    }

    private function createSupplierPaymentApproval(Request $request, SupplierPurchase $purchase, float $amount, string $method, string $reason): void
    {
        $amount = min(max($amount, 0), (float) $purchase->total_amount);
        if ($amount <= 0) {
            return;
        }

        $fingerprint = sha1(json_encode([
            'purchase_id' => (int) $purchase->id,
            'amount' => round($amount, 2),
            'method' => $method,
            'remaining' => round((float) $purchase->remaining_amount, 2),
        ]));

        $existingPending = ApprovalRequest::query()
            ->where('branch_id', $purchase->branch_id)
            ->where('type', 'supplier.purchase_payment')
            ->where('status', 'pending')
            ->where('payload->fingerprint', $fingerprint)
            ->exists();
        if ($existingPending) {
            return;
        }

        ApprovalRequest::query()->create([
            'branch_id' => $purchase->branch_id,
            'type' => 'supplier.purchase_payment',
            'status' => 'pending',
            'requested_by' => (int) $request->user()->id,
            'title' => "Approval Pembayaran {$purchase->number}",
            'reason' => $reason,
            'payload' => [
                'supplier_purchase_id' => (int) $purchase->id,
                'number' => (string) $purchase->number,
                'amount' => $amount,
                'payment_method' => $method,
                'reason' => $reason,
                'fingerprint' => $fingerprint,
            ],
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'approval_request_created',
            'context' => [
                'type' => 'supplier.purchase_payment',
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'amount' => $amount,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) ($request->userAgent() ?? ''),
        ]);
    }

    private function recordSupplierPayment(SupplierPurchase $purchase, Request $request, float $amount, string $method, ?string $note = null, ?Carbon $paidAt = null): float
    {
        SupplierPurchasePayment::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'received_by' => $request->user()?->id,
            'paid_at' => $paidAt ?: now(),
            'amount' => $amount,
            'payment_method' => $method,
            'note' => $note,
        ]);

        $newPaid = (float) $purchase->paid_amount + $amount;
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

        return $newRemaining;
    }

    private function generateSupplierCode(?int $branchId): string
    {
        $branchCode = (string) DB::table('branches')->where('id', $branchId)->value('code');
        $branchCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($branchCode)) ?: 'GLB';
        $prefix = 'SUP-' . $branchCode . '-';

        $latest = Supplier::query()
            ->where('branch_id', $branchId)
            ->where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if (is_string($latest) && preg_match('/(\d+)$/', $latest, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $code = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (Supplier::query()->where('branch_id', $branchId)->where('code', $code)->exists());

        return $code;
    }

    private function parseBulkPurchaseItems(string $text): array
    {
        return collect(preg_split('/\R/u', $text) ?: [])
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(function (string $line): ?array {
                $parts = preg_split('/\s*[|;\t]\s*/u', $line);
                if (! is_array($parts) || count($parts) < 3) {
                    $parts = str_getcsv($line);
                }

                if (count($parts) < 3) {
                    return null;
                }

                $unitCost = array_pop($parts);
                $quantity = array_pop($parts);
                $productName = trim(implode(' ', array_map('trim', $parts)));

                if ($productName === '') {
                    return null;
                }

                return [
                    'product_name' => $productName,
                    'quantity' => max((int) $quantity, 1),
                    'unit_cost' => $this->normalizeMoneyInput((string) $unitCost),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeMoneyInput(string $value): float
    {
        $clean = preg_replace('/[^0-9,.-]/', '', trim($value)) ?: '0';
        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (str_contains($clean, ',')) {
            $clean = str_replace(',', '.', $clean);
        }

        return max((float) $clean, 0.0);
    }
}
