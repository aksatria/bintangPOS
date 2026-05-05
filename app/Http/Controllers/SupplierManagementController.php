<?php

namespace App\Http\Controllers;

use App\Exports\SupplierPurchasesExport;
use App\Models\CashierAuditLog;
use App\Models\ApprovalRequest;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchaseAttachment;
use App\Models\SupplierPurchaseItem;
use App\Models\SupplierPurchasePayment;
use App\Models\SupplierPurchaseReturn;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use App\Services\AccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SupplierManagementController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $purchaseQ = trim((string) $request->query('purchase_q', ''));
        $purchaseStatus = (string) $request->query('purchase_status', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $approvalFilter = (string) $request->query('approval_status', 'all');
        $purchaseSupplierId = (int) $request->query('purchase_supplier_id', 0);
        $dueFrom = trim((string) $request->query('due_from', ''));
        $dueTo = trim((string) $request->query('due_to', ''));
        $attachmentFilter = (string) $request->query('attachment_filter', 'all');
        $debtFilter = (string) $request->query('debt_filter', 'all');

        $suppliers = $this->applyBranchScope(Supplier::query(), $request->user())
            ->withCount('purchases')
            ->withSum([
                'purchases as open_debt_amount' => fn ($query) => $query
                    ->where('remaining_amount', '>', 0)
                    ->whereIn('payment_status', ['unpaid', 'partial', 'overdue']),
            ], 'remaining_amount')
            ->withMax('purchases as last_purchase_at', 'ordered_at')
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

        $purchasesQuery = $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->with([
                'supplier:id,name,code',
                'creator:id,name',
                'items' => fn ($q) => $q->orderBy('id'),
                'payments' => fn ($q) => $q->latest('paid_at'),
                'attachments' => fn ($q) => $q->latest('id'),
            ])
            ->withCount('items')
            ->withCount('attachments')
            ->when($purchaseSupplierId > 0, fn ($builder) => $builder->where('supplier_id', $purchaseSupplierId))
            ->when($purchaseQ !== '', function ($builder) use ($purchaseQ) {
                $builder->where(function ($sub) use ($purchaseQ) {
                    $sub->where('number', 'like', "%{$purchaseQ}%")
                        ->orWhere('supplier_invoice_number', 'like', "%{$purchaseQ}%")
                        ->orWhere('delivery_note_number', 'like', "%{$purchaseQ}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$purchaseQ}%"));
                });
            })
            ->when(in_array($purchaseStatus, ['draft', 'partial_received', 'received', 'cancelled'], true), fn ($builder) => $builder->where('status', $purchaseStatus))
            ->when(in_array($paymentStatus, ['unpaid', 'partial', 'overdue', 'paid'], true), fn ($builder) => $builder->where('payment_status', $paymentStatus))
            ->when($debtFilter === 'open', fn ($builder) => $builder->where('remaining_amount', '>', 0))
            ->when($debtFilter === 'closed', fn ($builder) => $builder->where('remaining_amount', '<=', 0))
            ->when($dueFrom !== '', fn ($builder) => $builder->whereDate('due_date', '>=', $dueFrom))
            ->when($dueTo !== '', fn ($builder) => $builder->whereDate('due_date', '<=', $dueTo))
            ->when($attachmentFilter === 'with', fn ($builder) => $builder->has('attachments'))
            ->when($attachmentFilter === 'without', fn ($builder) => $builder->doesntHave('attachments'));

        if (in_array($approvalFilter, ['none', 'pending', 'approved', 'rejected', 'stale'], true)) {
            $approvalFilteredIds = $this->supplierPurchaseIdsMatchingApprovalFilter(clone $purchasesQuery, $request, $approvalFilter);
            $purchasesQuery->whereIn('id', $approvalFilteredIds === [] ? [0] : $approvalFilteredIds);
        }

        $purchases = $purchasesQuery->latest('id')->paginate(12, ['*'], 'purchases_page')->withQueryString();

        $purchaseIds = $purchases->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $supplierPurchaseApprovals = [];
        $supplierPurchaseApprovalHistory = [];
        if ($purchaseIds !== []) {
            $purchaseFingerprints = $purchases->getCollection()
                ->mapWithKeys(fn (SupplierPurchase $purchase) => [(int) $purchase->id => $this->supplierPurchaseFingerprint($purchase)])
                ->all();
            $approvalRows = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
                ->where('type', 'supplier.purchase_approval')
                ->with(['requester:id,name', 'reviewer:id,name'])
                ->latest('id')
                ->get(['id', 'status', 'requested_by', 'reviewed_by', 'payload', 'review_note', 'reviewed_at', 'created_at'])
                ->filter(fn (ApprovalRequest $approval) => in_array((int) data_get((array) $approval->payload, 'supplier_purchase_id'), $purchaseIds, true));

            $supplierPurchaseApprovalHistory = $approvalRows
                ->groupBy(fn (ApprovalRequest $approval) => (int) data_get((array) $approval->payload, 'supplier_purchase_id'))
                ->map(fn ($rows) => $rows->take(4)->map(fn (ApprovalRequest $approval) => [
                    'id' => (int) $approval->id,
                    'status' => (string) $approval->status,
                    'requester' => (string) ($approval->requester?->name ?? '-'),
                    'reviewer' => (string) ($approval->reviewer?->name ?? '-'),
                    'review_note' => (string) ($approval->review_note ?? ''),
                    'requested_at' => $approval->created_at?->format('d/m/Y H:i'),
                    'reviewed_at' => $approval->reviewed_at?->format('d/m/Y H:i'),
                    'is_current' => (string) data_get((array) $approval->payload, 'fingerprint', '') === '' || (string) data_get((array) $approval->payload, 'fingerprint', '') === (string) ($purchaseFingerprints[(int) data_get((array) $approval->payload, 'supplier_purchase_id')] ?? ''),
                ])->values()->all())
                ->all();

            foreach ($purchaseIds as $purchaseId) {
                $rows = $approvalRows
                    ->filter(fn (ApprovalRequest $approval) => (int) data_get((array) $approval->payload, 'supplier_purchase_id') === (int) $purchaseId)
                    ->values();
                $current = $rows->first(function (ApprovalRequest $approval) use ($purchaseFingerprints, $purchaseId) {
                    $fingerprint = (string) data_get((array) $approval->payload, 'fingerprint', '');
                    return $fingerprint === '' || $fingerprint === (string) ($purchaseFingerprints[(int) $purchaseId] ?? '');
                });
                $latest = $rows->first();
                if ($current) {
                    $supplierPurchaseApprovals[(int) $purchaseId] = [
                        'id' => (int) $current->id,
                        'status' => (string) $current->status,
                        'review_note' => (string) ($current->review_note ?? ''),
                        'reviewed_at' => $current->reviewed_at?->format('d/m/Y H:i'),
                        'is_stale' => false,
                    ];
                } elseif ($latest) {
                    $supplierPurchaseApprovals[(int) $purchaseId] = [
                        'id' => (int) $latest->id,
                        'status' => 'stale',
                        'review_note' => 'Draft berubah setelah persetujuan terakhir.',
                        'reviewed_at' => $latest->reviewed_at?->format('d/m/Y H:i'),
                        'is_stale' => true,
                    ];
                }
            }
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
        $supplierDebtDashboard = $this->supplierDebtDashboard($request);
        $supplierDebtReport = $this->supplierDebtReport($request);

        $supplierOptions = $this->applyBranchScope(Supplier::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.suppliers', [
            'suppliers' => $suppliers,
            'purchases' => $purchases,
            'supplierOptions' => $supplierOptions,
            'supplierDebtSummary' => $supplierDebtSummary,
            'supplierDebtDashboard' => $supplierDebtDashboard,
            'supplierDebtReport' => $supplierDebtReport,
            'supplierPurchaseApprovals' => $supplierPurchaseApprovals,
            'supplierPurchaseApprovalHistory' => $supplierPurchaseApprovalHistory,
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'approvalStatusLabels' => $this->approvalStatusLabels(),
            'filters' => compact('q', 'status', 'purchaseQ', 'purchaseStatus', 'paymentStatus', 'approvalFilter', 'purchaseSupplierId', 'dueFrom', 'dueTo', 'attachmentFilter', 'debtFilter'),
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

    public function suppliersShow(Request $request, Supplier $supplier)
    {
        $user = $request->user();
        $supplier = ($user && $user->hasAnyRole(['owner']))
            ? Supplier::query()->findOrFail($supplier->id)
            : $this->applyBranchScope(Supplier::query(), $user)->findOrFail($supplier->id);

        $purchasesQuery = $this->applyBranchScope(SupplierPurchase::query(), $user)
            ->where('supplier_id', $supplier->id);

        $summaryRows = (clone $purchasesQuery)->get([
            'id',
            'status',
            'payment_status',
            'ordered_at',
            'due_date',
            'total_amount',
            'paid_amount',
            'remaining_amount',
        ]);

        $purchases = (clone $purchasesQuery)
            ->with(['items' => fn ($q) => $q->orderBy('id'), 'payments' => fn ($q) => $q->latest('paid_at')])
            ->withCount(['items', 'attachments'])
            ->latest('ordered_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $today = now()->startOfDay();
        $summary = [
            'purchase_count' => (int) $summaryRows->count(),
            'total_purchase' => (float) $summaryRows->sum('total_amount'),
            'open_debt' => (float) $summaryRows->sum('remaining_amount'),
            'paid_total' => (float) $summaryRows->sum('paid_amount'),
            'overdue_count' => (int) $summaryRows
                ->filter(fn (SupplierPurchase $purchase) => (float) $purchase->remaining_amount > 0 && $purchase->due_date && $purchase->due_date->lt($today))
                ->count(),
            'last_order' => $summaryRows
                ->filter(fn (SupplierPurchase $purchase) => $purchase->ordered_at !== null)
                ->sortByDesc(fn (SupplierPurchase $purchase) => $purchase->ordered_at?->timestamp ?? 0)
                ->first()?->ordered_at?->format('d/m/Y') ?? '-',
        ];

        $auditLogs = $this->applyBranchScope(CashierAuditLog::query(), $user)
            ->with('user:id,name')
            ->where(function ($query) use ($supplier) {
                $query->where('context->supplier_id', $supplier->id)
                    ->orWhere('context->supplier_name', $supplier->name);
            })
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.supplier-show', [
            'supplier' => $supplier,
            'summary' => $summary,
            'purchases' => $purchases,
            'auditLogs' => $auditLogs,
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'auditActionLabels' => $this->supplierAuditActionLabels(),
        ]);
    }

    public function supplierDebtsReport(Request $request)
    {
        $query = $this->supplierDebtReportQuery($request)
            ->with(['supplier:id,name,code,phone,email', 'creator:id,name'])
            ->withCount(['items', 'attachments'])
            ->latest('due_date')
            ->latest('id');

        $rows = $query->paginate(20)->withQueryString();
        $summaryRows = $this->supplierDebtReportQuery($request)->get();
        $supplierOptions = $this->applyBranchScope(Supplier::query(), $request->user())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('admin.supplier-debts-report', [
            'rows' => $rows,
            'supplierOptions' => $supplierOptions,
            'summary' => $this->supplierDebtReportSummary($summaryRows),
            'agingSummary' => $this->supplierDebtAgingSummary($summaryRows),
            'filters' => $this->supplierDebtReportFilters($request),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
        ]);
    }

    public function supplierDebtsExportExcel(Request $request)
    {
        $rows = $this->supplierDebtReportQuery($request)
            ->with(['supplier:id,name,code', 'items' => fn ($q) => $q->orderBy('id')])
            ->withCount(['items', 'attachments'])
            ->latest('due_date')
            ->limit(5000)
            ->get();

        return Excel::download(
            new SupplierPurchasesExport($rows),
            'laporan-hutang-supplier-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function supplierDebtsExportPdf(Request $request)
    {
        $rows = $this->supplierDebtReportQuery($request)
            ->with(['supplier:id,name,code'])
            ->withCount(['items', 'attachments'])
            ->latest('due_date')
            ->limit(500)
            ->get();

        $pdf = Pdf::loadView('pdf.supplier-debts-report', [
            'rows' => $rows,
            'summary' => $this->supplierDebtReportSummary($rows),
            'agingSummary' => $this->supplierDebtAgingSummary($rows),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-hutang-supplier-'.now()->format('Ymd-His').'.pdf');
    }

    public function purchasesShow(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()
                ->with(['supplier', 'creator', 'items', 'payments.receiver', 'payments.proofAttachments.uploader', 'attachments.uploader', 'returns.item', 'returns.creator'])
                ->withCount('items')
                ->withCount('attachments')
                ->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)
                ->with(['supplier', 'creator', 'items', 'payments.receiver', 'payments.proofAttachments.uploader', 'attachments.uploader', 'returns.item', 'returns.creator'])
                ->withCount('items')
                ->withCount('attachments')
                ->findOrFail($purchase->id);

        $fingerprint = $this->supplierPurchaseFingerprint($purchase);
        $approvalRows = $this->applyBranchScope(ApprovalRequest::query(), $user)
            ->where('type', 'supplier.purchase_approval')
            ->where('payload->supplier_purchase_id', $purchase->id)
            ->with(['requester:id,name', 'reviewer:id,name'])
            ->latest('id')
            ->get();

        $approvalHistory = $approvalRows->map(fn (ApprovalRequest $approval) => [
            'id' => (int) $approval->id,
            'status' => (string) $approval->status,
            'requester' => (string) ($approval->requester?->name ?? '-'),
            'reviewer' => (string) ($approval->reviewer?->name ?? '-'),
            'review_note' => (string) ($approval->review_note ?? ''),
            'requested_at' => $approval->created_at?->format('d/m/Y H:i'),
            'reviewed_at' => $approval->reviewed_at?->format('d/m/Y H:i'),
            'is_current' => in_array((string) data_get((array) $approval->payload, 'fingerprint', ''), ['', $fingerprint], true),
        ])->values();

        $auditLogs = $this->applyBranchScope(CashierAuditLog::query(), $user)
            ->with('user:id,name')
            ->whereIn('action', [
                'supplier_purchase_created',
                'supplier_purchase_approval_requested',
                'supplier_purchase_duplicated',
                'supplier_purchase_draft_updated',
                'supplier_purchase_reconciled',
                'supplier_purchase_attachment_uploaded',
                'supplier_purchase_attachment_deleted',
                'supplier_purchase_payment_proof_uploaded',
                'supplier_purchase_cancelled',
                'supplier_purchase_partial_received',
                'supplier_purchase_received',
                'supplier_purchase_returned',
                'supplier_purchase_paid',
                'approval_request_created',
            ])
            ->where(function ($query) use ($purchase) {
                $query->where('context->supplier_purchase_id', $purchase->id)
                    ->orWhere('context->new_supplier_purchase_id', $purchase->id)
                    ->orWhere('context->source_supplier_purchase_id', $purchase->id);
            })
            ->latest('id')
            ->limit(30)
            ->get();

        return view('admin.supplier-purchase-show', [
            'purchase' => $purchase,
            'approvalHistory' => $approvalHistory,
            'auditLogs' => $auditLogs,
            'currentFingerprint' => $fingerprint,
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
            'approvalStatusLabels' => $this->approvalStatusLabels(),
            'auditActionLabels' => $this->supplierAuditActionLabels(),
        ]);
    }

    public function purchasesStore(Request $request)
    {
        $validated = $this->validatePurchasePayload($request, true);

        $branchId = ActiveBranchContext::resolveBranchId($request->user());
        $supplier = $this->applyBranchScope(Supplier::query(), $request->user())
            ->findOrFail((int) $validated['supplier_id']);
        $orderedAt = Carbon::parse((string) $validated['ordered_at']);
        $paymentTermDays = (int) ($validated['payment_term_days'] ?? 0);
        $dueDate = ! empty($validated['due_date'])
            ? Carbon::parse((string) $validated['due_date'])->toDateString()
            : ($paymentTermDays > 0 ? $orderedAt->copy()->addDays($paymentTermDays)->toDateString() : null);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $shipping = (float) ($validated['shipping_amount'] ?? 0);
        $shippingTreatment = (string) ($validated['shipping_accounting_treatment'] ?? 'inventory');
        $shippingMethod = 'by_value';
        $downPayment = (float) ($validated['down_payment_amount'] ?? 0);
        $downPaymentMethod = (string) ($validated['down_payment_method'] ?? 'cash');

        $approvalQueued = false;
        DB::transaction(function () use ($validated, $request, $branchId, $supplier, $orderedAt, $paymentTermDays, $dueDate, $discount, $tax, $shipping, $shippingTreatment, $shippingMethod, $downPayment, $downPaymentMethod, &$approvalQueued): void {
            $purchase = SupplierPurchase::query()->create([
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'created_by' => $request->user()?->id,
                'number' => $this->generatePurchaseNumber($branchId),
                'supplier_invoice_number' => trim((string) ($validated['supplier_invoice_number'] ?? '')) ?: null,
                'delivery_note_number' => trim((string) ($validated['delivery_note_number'] ?? '')) ?: null,
                'status' => 'draft',
                'ordered_at' => $orderedAt->toDateString(),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $dueDate,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'shipping_accounting_treatment' => $shippingTreatment,
                'shipping_allocation_method' => $shippingMethod,
                'inventory_shipping_amount' => $shippingTreatment === 'inventory' ? $shipping : 0,
                'expense_shipping_amount' => $shippingTreatment === 'expense' ? $shipping : 0,
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            ]);

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
                    'received_quantity' => 0,
                    'returned_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }

            foreach ($this->applyShippingAllocationToItems($newItems, $shipping, $shippingTreatment) as $item) {
                SupplierPurchaseItem::query()->create([
                    'supplier_purchase_id' => $purchase->id,
                    ...$item,
                ]);
            }

            $total = max($subtotal - $discount + $tax + $shipping, 0);
            if ($downPayment > $total) {
                throw ValidationException::withMessages([
                    'down_payment_amount' => 'DP tidak boleh lebih besar dari total pembelian.',
                ]);
            }
            $requiresApproval = $downPayment > 0 && $this->requiresOwnerApprovalForSupplierPayment($request, $purchase, $downPayment);
            $paid = $requiresApproval ? 0.0 : $downPayment;
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
                    $downPayment,
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
            ? SupplierPurchase::query()->with(['supplier', 'items'])->withCount('attachments')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with(['supplier', 'items'])->withCount('attachments')->findOrFail($purchase->id);

        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Hanya draft pembelian yang bisa diajukan persetujuan.']);
        }
        if (! $this->purchaseHasApprovalDocument($purchase)) {
            return back()->withErrors(['purchase' => 'Isi nomor invoice/surat jalan atau upload minimal 1 lampiran sebelum ajukan persetujuan.']);
        }

        $existing = ApprovalRequest::query()
            ->where('branch_id', $purchase->branch_id)
            ->where('type', 'supplier.purchase_approval')
            ->whereIn('status', ['pending', 'approved'])
            ->where('payload->supplier_purchase_id', $purchase->id)
            ->where('payload->fingerprint', $this->supplierPurchaseFingerprint($purchase))
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
                'remaining_amount' => (float) $purchase->remaining_amount,
                'ordered_at' => optional($purchase->ordered_at)->toDateString(),
                'due_date' => optional($purchase->due_date)->toDateString(),
                'payment_term_days' => (int) ($purchase->payment_term_days ?? 0),
                'supplier_invoice_number' => (string) ($purchase->supplier_invoice_number ?? ''),
                'delivery_note_number' => (string) ($purchase->delivery_note_number ?? ''),
                'items_count' => (int) $purchase->items->count(),
                'items_preview' => $purchase->items
                    ->take(5)
                    ->map(fn (SupplierPurchaseItem $item) => [
                        'name' => (string) $item->product_name,
                        'quantity' => (int) $item->quantity,
                        'unit_cost' => (float) $item->unit_cost,
                        'line_total' => (float) $item->line_total,
                    ])
                    ->values()
                    ->all(),
                'fingerprint' => $this->supplierPurchaseFingerprint($purchase),
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

    public function purchasesDuplicate(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with('items')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with('items')->findOrFail($purchase->id);

        $newPurchase = null;
        DB::transaction(function () use ($purchase, $request, &$newPurchase): void {
            $orderedAt = now();
            $paymentTermDays = (int) ($purchase->payment_term_days ?? 0);
            $dueDate = $paymentTermDays > 0 ? $orderedAt->copy()->addDays($paymentTermDays)->toDateString() : null;

            $newPurchase = SupplierPurchase::query()->create([
                'branch_id' => $purchase->branch_id,
                'supplier_id' => $purchase->supplier_id,
                'created_by' => $request->user()?->id,
                'number' => $this->generatePurchaseNumber((int) $purchase->branch_id),
                'supplier_invoice_number' => null,
                'delivery_note_number' => null,
                'status' => 'draft',
                'ordered_at' => $orderedAt->toDateString(),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $dueDate,
                'subtotal' => (float) $purchase->subtotal,
                'discount_amount' => (float) $purchase->discount_amount,
                'tax_amount' => (float) $purchase->tax_amount,
                'shipping_amount' => (float) $purchase->shipping_amount,
                'shipping_accounting_treatment' => (string) ($purchase->shipping_accounting_treatment ?? 'inventory'),
                'shipping_allocation_method' => (string) ($purchase->shipping_allocation_method ?? 'by_value'),
                'inventory_shipping_amount' => (float) ($purchase->inventory_shipping_amount ?? 0),
                'expense_shipping_amount' => (float) ($purchase->expense_shipping_amount ?? 0),
                'total_amount' => (float) $purchase->total_amount,
                'paid_amount' => 0,
                'remaining_amount' => (float) $purchase->total_amount,
                'payment_status' => 'unpaid',
                'note' => trim('Duplikasi dari '.$purchase->number.'. '.(string) ($purchase->note ?? '')),
            ]);

            foreach ($purchase->items as $item) {
                $newPurchase->items()->create([
                    'product_id' => null,
                    'product_name' => (string) $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'received_quantity' => 0,
                    'returned_quantity' => 0,
                    'unit_cost' => (float) $item->unit_cost,
                    'line_total' => (float) $item->line_total,
                    'shipping_allocation_amount' => (float) ($item->shipping_allocation_amount ?? 0),
                    'landed_unit_cost' => (float) ($item->landed_unit_cost ?: $item->unit_cost),
                    'landed_line_total' => (float) ($item->landed_line_total ?: $item->line_total),
                ]);
            }

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $purchase->branch_id,
                'action' => 'supplier_purchase_duplicated',
                'context' => [
                    'source_supplier_purchase_id' => $purchase->id,
                    'source_number' => $purchase->number,
                    'new_supplier_purchase_id' => $newPurchase->id,
                    'new_number' => $newPurchase->number,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return redirect()
            ->route('admin.supplier-purchases.show', $newPurchase)
            ->with('status', 'Draft pembelian berhasil diduplikasi.');
    }

    public function purchasesExportExcel(Request $request)
    {
        $query = $this->supplierPurchasesFilteredQuery($request);
        $approvalFilter = (string) $request->query('approval_status', 'all');
        if (in_array($approvalFilter, ['none', 'pending', 'approved', 'rejected', 'stale'], true)) {
            $approvalFilteredIds = $this->supplierPurchaseIdsMatchingApprovalFilter(clone $query, $request, $approvalFilter);
            $query->whereIn('id', $approvalFilteredIds === [] ? [0] : $approvalFilteredIds);
        }

        $query = $query
            ->with(['supplier:id,name,code', 'items' => fn ($q) => $q->orderBy('id')])
            ->withCount(['items', 'attachments'])
            ->latest('id');

        $rows = $query->limit(5000)->get();

        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
            'action' => 'supplier_purchase_export_excel',
            'context' => [
                'rows' => $rows->count(),
                'total_amount' => (float) $rows->sum('total_amount'),
                'remaining_amount' => (float) $rows->sum('remaining_amount'),
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return Excel::download(
            new SupplierPurchasesExport($rows),
            'pembelian-supplier-'.now()->format('Ymd-His').'.xlsx'
        );
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
        $dueDate = ! empty($validated['due_date'])
            ? Carbon::parse((string) $validated['due_date'])->toDateString()
            : ($paymentTermDays > 0 ? $orderedAt->copy()->addDays($paymentTermDays)->toDateString() : null);
        $discount = (float) ($validated['discount_amount'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $shipping = (float) ($validated['shipping_amount'] ?? 0);
        $shippingTreatment = (string) ($validated['shipping_accounting_treatment'] ?? 'inventory');
        $shippingMethod = 'by_value';

        DB::transaction(function () use ($purchase, $validated, $request, $supplier, $orderedAt, $paymentTermDays, $dueDate, $discount, $tax, $shipping, $shippingTreatment, $shippingMethod): void {
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
                    'received_quantity' => 0,
                    'returned_quantity' => 0,
                    'unit_cost' => $unitCost,
                    'line_total' => $lineTotal,
                ];
            }
            $newItems = $this->applyShippingAllocationToItems($newItems, $shipping, $shippingTreatment);

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
            $locked->update([
                'supplier_id' => $supplier->id,
                'supplier_invoice_number' => trim((string) ($validated['supplier_invoice_number'] ?? '')) ?: null,
                'delivery_note_number' => trim((string) ($validated['delivery_note_number'] ?? '')) ?: null,
                'ordered_at' => $orderedAt->toDateString(),
                'payment_term_days' => $paymentTermDays,
                'due_date' => $dueDate,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'tax_amount' => $tax,
                'shipping_amount' => $shipping,
                'shipping_accounting_treatment' => $shippingTreatment,
                'shipping_allocation_method' => $shippingMethod,
                'inventory_shipping_amount' => $shippingTreatment === 'inventory' ? $shipping : 0,
                'expense_shipping_amount' => $shippingTreatment === 'expense' ? $shipping : 0,
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

    public function purchasesAttachmentStore(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        $validated = $request->validate([
            'kind' => ['required', Rule::in(['supplier_invoice', 'delivery_note', 'payment_proof', 'received_photo', 'other'])],
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('attachment');
        $path = $file->store("supplier-purchases/{$purchase->id}", 'public');

        SupplierPurchaseAttachment::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'uploaded_by' => $user?->id,
            'kind' => (string) $validated['kind'],
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $user?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_attachment_uploaded',
            'context' => [
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'kind' => (string) $validated['kind'],
                'path' => $path,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Lampiran pembelian supplier berhasil diunggah.');
    }

    public function purchasesAttachmentDestroy(Request $request, SupplierPurchaseAttachment $attachment)
    {
        $user = $request->user();
        $attachment = SupplierPurchaseAttachment::query()
            ->with('purchase')
            ->findOrFail($attachment->id);
        $purchase = $attachment->purchase;

        if (! $purchase) {
            abort(404);
        }

        if (! ($user && $user->hasAnyRole(['owner']))) {
            $this->applyBranchScope(SupplierPurchase::query(), $user)
                ->whereKey($purchase->id)
                ->firstOrFail();
        }

        Storage::disk('public')->delete((string) $attachment->path);
        $attachmentKind = (string) $attachment->kind;
        $attachment->delete();

        CashierAuditLog::query()->create([
            'user_id' => $user?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_attachment_deleted',
            'context' => [
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'kind' => $attachmentKind,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Lampiran pembelian supplier berhasil dihapus.');
    }

    public function purchasesAttachmentDownload(Request $request, SupplierPurchaseAttachment $attachment)
    {
        $user = $request->user();
        $attachment = SupplierPurchaseAttachment::query()
            ->with('purchase')
            ->findOrFail($attachment->id);
        $purchase = $attachment->purchase;

        if (! $purchase) {
            abort(404);
        }

        if (! ($user && $user->hasAnyRole(['owner']))) {
            $this->applyBranchScope(SupplierPurchase::query(), $user)
                ->whereKey($purchase->id)
                ->firstOrFail();
        }

        $path = (string) $attachment->path;
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $path,
            $attachment->original_name ?: basename($path)
        );
    }

    public function purchasesPaymentProofStore(Request $request, SupplierPurchasePayment $payment)
    {
        $user = $request->user();
        $payment = SupplierPurchasePayment::query()
            ->with('purchase')
            ->findOrFail($payment->id);
        $purchase = $payment->purchase;

        if (! $purchase) {
            abort(404);
        }

        if (! ($user && $user->hasAnyRole(['owner']))) {
            $this->applyBranchScope(SupplierPurchase::query(), $user)
                ->whereKey($purchase->id)
                ->firstOrFail();
        }

        $validated = $request->validate([
            'attachment' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('attachment');
        $path = $file->store("supplier-purchases/{$purchase->id}/payments/{$payment->id}", 'public');

        SupplierPurchaseAttachment::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'supplier_purchase_payment_id' => $payment->id,
            'uploaded_by' => $user?->id,
            'kind' => 'payment_proof',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $user?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_payment_proof_uploaded',
            'context' => [
                'supplier_purchase_id' => $purchase->id,
                'supplier_purchase_payment_id' => $payment->id,
                'number' => $purchase->number,
                'payment_amount' => (float) $payment->amount,
                'path' => $path,
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Bukti pembayaran supplier berhasil diunggah.');
    }

    public function purchasesReconcile(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        $validated = $request->validate([
            'supplier_invoice_amount' => ['nullable', 'numeric', 'min:0'],
            'reconciliation_status' => ['required', Rule::in(['unchecked', 'matched', 'mismatch', 'dispute'])],
            'reconciliation_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $invoiceAmount = array_key_exists('supplier_invoice_amount', $validated) && $validated['supplier_invoice_amount'] !== null
            ? (float) $validated['supplier_invoice_amount']
            : null;
        $difference = $invoiceAmount !== null ? round($invoiceAmount - (float) $purchase->total_amount, 2) : null;

        $purchase->update([
            'supplier_invoice_amount' => $invoiceAmount,
            'reconciliation_status' => (string) $validated['reconciliation_status'],
            'reconciliation_note' => trim((string) ($validated['reconciliation_note'] ?? '')) ?: null,
        ]);

        CashierAuditLog::query()->create([
            'user_id' => $user?->id,
            'branch_id' => $purchase->branch_id,
            'action' => 'supplier_purchase_reconciled',
            'context' => [
                'supplier_purchase_id' => $purchase->id,
                'number' => $purchase->number,
                'supplier_invoice_amount' => $invoiceAmount,
                'total_amount' => (float) $purchase->total_amount,
                'difference' => $difference,
                'reconciliation_status' => (string) $validated['reconciliation_status'],
            ],
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        return back()->with('status', 'Rekonsiliasi invoice supplier berhasil disimpan.');
    }

    public function purchasesCancel(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        if ($purchase->status !== 'draft') {
            return back()->withErrors(['purchase' => 'Hanya draft pembelian yang bisa dibatalkan.']);
        }

        DB::transaction(function () use ($purchase, $request): void {
            $locked = SupplierPurchase::query()->lockForUpdate()->findOrFail($purchase->id);
            if ($locked->status !== 'draft') {
                abort(422, 'Status pembelian berubah, pembatalan dibatalkan.');
            }

            $locked->update([
                'status' => 'cancelled',
                'remaining_amount' => 0,
                'payment_status' => (float) $locked->paid_amount > 0 ? 'partial' : 'paid',
                'note' => trim(($locked->note ? $locked->note."\n" : '').'[CANCELLED] Dibatalkan oleh '.($request->user()?->name ?? 'user').' pada '.now()->format('d/m/Y H:i')),
            ]);

            ApprovalRequest::query()
                ->where('branch_id', $locked->branch_id)
                ->where('type', 'supplier.purchase_approval')
                ->where('status', 'pending')
                ->where('payload->supplier_purchase_id', $locked->id)
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => $request->user()?->id,
                    'reviewed_at' => now(),
                    'review_note' => '[CANCELLED] Draft pembelian dibatalkan.',
                ]);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $locked->branch_id,
                'action' => 'supplier_purchase_cancelled',
                'context' => [
                    'supplier_purchase_id' => $locked->id,
                    'number' => $locked->number,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Draft pembelian berhasil dibatalkan.');
    }

    public function purchasesReceive(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->findOrFail($purchase->id);

        if (! in_array($purchase->status, ['draft', 'partial_received'], true)) {
            return back()->withErrors(['purchase' => 'Hanya pembelian draft atau diterima sebagian yang bisa diterima.']);
        }
        if ($purchase->status === 'draft' && ! $this->supplierPurchaseApproved($purchase)) {
            return back()->withErrors(['purchase' => 'Pembelian harus disetujui owner sebelum barang diterima.']);
        }

        DB::transaction(function () use ($purchase, $request): void {
            $purchase->load('items.product');
            foreach ($purchase->items as $item) {
                $remainingQty = max((int) $item->quantity - (int) $item->received_quantity, 0);
                if ($remainingQty <= 0) {
                    continue;
                }

                if (! $item->product) {
                    $item->increment('received_quantity', $remainingQty);
                } else {
                    $item->product->increment('stock', $remainingQty);
                    $item->product->update([
                        'purchase_price' => $this->effectiveInventoryUnitCost($item),
                    ]);
                    $item->increment('received_quantity', $remainingQty);
                }
            }

            $purchase->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
            app(AccountingService::class)->postSupplierReceive($purchase->fresh('items'), $request->user()?->id);

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

    public function purchasesReceivePartial(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with('items.product')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with('items.product')->findOrFail($purchase->id);

        if (! in_array($purchase->status, ['draft', 'partial_received'], true)) {
            return back()->withErrors(['purchase' => 'Penerimaan sebagian hanya untuk draft atau pembelian diterima sebagian.']);
        }
        if ($purchase->status === 'draft' && ! $this->supplierPurchaseApproved($purchase)) {
            return back()->withErrors(['purchase' => 'Pembelian harus disetujui owner sebelum barang diterima.']);
        }

        $validated = $request->validate([
            'received' => ['required', 'array'],
            'received.*' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $receivedTotal = 0;
        DB::transaction(function () use ($purchase, $request, $validated, &$receivedTotal): void {
            $locked = SupplierPurchase::query()->with('items.product')->lockForUpdate()->findOrFail($purchase->id);
            foreach ($locked->items as $item) {
                $qty = (int) data_get($validated, 'received.'.$item->id, 0);
                if ($qty <= 0) {
                    continue;
                }

                $remainingQty = max((int) $item->quantity - (int) $item->received_quantity, 0);
                if ($qty > $remainingQty) {
                    throw ValidationException::withMessages([
                        'received.'.$item->id => 'Qty terima melebihi sisa pesanan untuk '.$item->product_name.'.',
                    ]);
                }

                if ($item->product) {
                    $item->product->increment('stock', $qty);
                    $item->product->update(['purchase_price' => $this->effectiveInventoryUnitCost($item)]);
                }
                $item->increment('received_quantity', $qty);
                $receivedTotal += $qty;
            }

            if ($receivedTotal <= 0) {
                throw ValidationException::withMessages(['received' => 'Isi minimal 1 qty barang yang diterima.']);
            }

            $locked->refresh()->load('items');
            $allReceived = $locked->items->every(fn (SupplierPurchaseItem $item) => (int) $item->received_quantity >= (int) $item->quantity);
            $locked->update([
                'status' => $allReceived ? 'received' : 'partial_received',
                'received_at' => $allReceived ? now() : $locked->received_at,
                'note' => trim(($locked->note ? $locked->note."\n" : '').($validated['note'] ?? '')) ?: $locked->note,
            ]);
            if ($allReceived) {
                app(AccountingService::class)->postSupplierReceive($locked->fresh('items'), $request->user()?->id);
            }

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $locked->branch_id,
                'action' => 'supplier_purchase_partial_received',
                'context' => [
                    'supplier_purchase_id' => $locked->id,
                    'number' => $locked->number,
                    'received_quantity' => $receivedTotal,
                    'status' => $locked->fresh()->status,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Penerimaan sebagian berhasil dicatat.');
    }

    public function purchasesReturnStore(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with('items.product')->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with('items.product')->findOrFail($purchase->id);

        if (! in_array($purchase->status, ['partial_received', 'received'], true)) {
            return back()->withErrors(['return' => 'Retur hanya bisa dibuat setelah barang diterima.']);
        }

        $validated = $request->validate([
            'supplier_purchase_item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($purchase, $request, $validated): void {
            $locked = SupplierPurchase::query()->with('items.product')->lockForUpdate()->findOrFail($purchase->id);
            $item = $locked->items->firstWhere('id', (int) $validated['supplier_purchase_item_id']);
            if (! $item) {
                throw ValidationException::withMessages(['supplier_purchase_item_id' => 'Item retur tidak ditemukan.']);
            }

            $qty = (int) $validated['quantity'];
            $returnableQty = max((int) $item->received_quantity - (int) $item->returned_quantity, 0);
            if ($qty > $returnableQty) {
                throw ValidationException::withMessages(['quantity' => 'Qty retur melebihi barang diterima yang belum diretur.']);
            }

            $returnUnitCost = $this->effectiveInventoryUnitCost($item);
            $amount = $qty * $returnUnitCost;
            $baseReturnAmount = $qty * (float) $item->unit_cost;
            $returnedShippingAmount = max($amount - $baseReturnAmount, 0);
            $newSubtotal = max((float) $locked->subtotal - $baseReturnAmount, 0);
            $newTotal = max((float) $locked->total_amount - $amount, 0);
            if ((float) $locked->paid_amount > $newTotal) {
                throw ValidationException::withMessages(['return' => 'Retur membuat pembayaran lebih besar dari total baru. Koreksi pembayaran dulu sebelum retur.']);
            }

            $return = SupplierPurchaseReturn::query()->create([
                'supplier_purchase_id' => $locked->id,
                'supplier_purchase_item_id' => $item->id,
                'created_by' => $request->user()?->id,
                'quantity' => $qty,
                'unit_cost' => $returnUnitCost,
                'amount' => $amount,
                'reason' => trim((string) $validated['reason']),
                'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            ]);

            $item->increment('returned_quantity', $qty);
            if ($item->product) {
                $item->product->decrement('stock', $qty);
            }

            $remaining = max($newTotal - (float) $locked->paid_amount, 0);
            $locked->update([
                'subtotal' => $newSubtotal,
                'inventory_shipping_amount' => max((float) ($locked->inventory_shipping_amount ?? 0) - $returnedShippingAmount, 0),
                'total_amount' => $newTotal,
                'remaining_amount' => $remaining,
                'payment_status' => $remaining <= 0 ? 'paid' : (((float) $locked->paid_amount > 0) ? 'partial' : (($locked->due_date && $locked->due_date->isPast()) ? 'overdue' : 'unpaid')),
            ]);
            app(AccountingService::class)->postSupplierReturn($return, $request->user()?->id);

            CashierAuditLog::query()->create([
                'user_id' => $request->user()?->id,
                'branch_id' => $locked->branch_id,
                'action' => 'supplier_purchase_returned',
                'context' => [
                    'supplier_purchase_id' => $locked->id,
                    'number' => $locked->number,
                    'item' => $item->product_name,
                    'quantity' => $qty,
                    'amount' => $amount,
                    'remaining_amount' => $remaining,
                ],
                'ip_address' => (string) $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);
        });

        return back()->with('status', 'Retur pembelian supplier berhasil dicatat dan hutang disesuaikan.');
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
            'payment_reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if (! in_array($purchase->status, ['draft', 'partial_received', 'received'], true)) {
            return back()->withErrors(['purchase' => 'Pembayaran supplier hanya bisa untuk pembelian draft, diterima sebagian, atau received.']);
        }
        if ($purchase->status === 'draft' && ! $this->supplierPurchaseApproved($purchase)) {
            return back()->withErrors(['purchase' => 'Pembelian harus disetujui owner sebelum pembayaran draft dicatat.']);
        }
        if ((float) $validated['amount'] > (float) $purchase->remaining_amount) {
            return back()->withErrors(['amount' => 'Nominal bayar tidak boleh lebih besar dari sisa hutang.']);
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

            $amount = (float) $validated['amount'];
            if ($amount > (float) $locked->remaining_amount) {
                abort(422, 'Nominal bayar tidak boleh lebih besar dari sisa hutang.');
            }
            [$newRemaining, $payment] = $this->recordSupplierPayment(
                $locked,
                $request,
                $amount,
                (string) $validated['payment_method'],
                trim((string) ($validated['payment_reference'] ?? '')) ?: null,
                trim((string) ($validated['note'] ?? '')) ?: null,
                ! empty($validated['paid_at']) ? Carbon::parse((string) $validated['paid_at']) : now()
            );
            app(AccountingService::class)->postSupplierPayment($payment, $request->user()?->id);

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

    public function purchasesPdf(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with(['supplier', 'creator', 'items', 'payments.receiver', 'attachments.uploader'])->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with(['supplier', 'creator', 'items', 'payments.receiver', 'attachments.uploader'])->findOrFail($purchase->id);

        $pdf = Pdf::loadView('pdf.supplier-purchase', [
            'purchase' => $purchase,
            'purchaseStatusLabels' => $this->purchaseStatusLabels(),
            'paymentStatusLabels' => $this->paymentStatusLabels(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('purchase-order-'.$purchase->number.'.pdf');
    }

    public function purchasesReturnsPdf(Request $request, SupplierPurchase $purchase)
    {
        $user = $request->user();
        $purchase = ($user && $user->hasAnyRole(['owner']))
            ? SupplierPurchase::query()->with(['supplier', 'items', 'returns.item', 'returns.creator'])->findOrFail($purchase->id)
            : $this->applyBranchScope(SupplierPurchase::query(), $user)->with(['supplier', 'items', 'returns.item', 'returns.creator'])->findOrFail($purchase->id);

        $pdf = Pdf::loadView('pdf.supplier-purchase-returns', [
            'purchase' => $purchase,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('nota-retur-'.$purchase->number.'.pdf');
    }

    private function generatePurchaseNumber(?int $branchId): string
    {
        $branchCode = (string) DB::table('branches')->where('id', $branchId)->value('code');
        $branchCode = preg_replace('/[^A-Z0-9]/', '', strtoupper($branchCode)) ?: 'GLOBAL';
        $prefix = 'PO-' . $branchCode . '-' . now()->format('Ym');
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

    private function supplierDebtReportQuery(Request $request)
    {
        $filters = $this->supplierDebtReportFilters($request);

        return $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->where('remaining_amount', '>', 0)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->when((int) $filters['supplier_id'] > 0, fn ($query) => $query->where('supplier_id', (int) $filters['supplier_id']))
            ->when($filters['payment_status'] !== 'all', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when($filters['due_from'] !== '', fn ($query) => $query->whereDate('due_date', '>=', $filters['due_from']))
            ->when($filters['due_to'] !== '', fn ($query) => $query->whereDate('due_date', '<=', $filters['due_to']))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                $q = (string) $filters['q'];
                $query->where(function ($sub) use ($q) {
                    $sub->where('number', 'like', "%{$q}%")
                        ->orWhere('supplier_invoice_number', 'like', "%{$q}%")
                        ->orWhere('delivery_note_number', 'like', "%{$q}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['aging_bucket'] !== 'all', fn ($query) => $this->applyAgingBucket($query, $filters['aging_bucket']));
    }

    private function supplierDebtReportFilters(Request $request): array
    {
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $agingBucket = (string) $request->query('aging_bucket', 'all');

        return [
            'q' => trim((string) $request->query('q', '')),
            'supplier_id' => (int) $request->query('supplier_id', 0),
            'payment_status' => in_array($paymentStatus, ['all', 'unpaid', 'partial', 'overdue'], true) ? $paymentStatus : 'all',
            'aging_bucket' => in_array($agingBucket, ['all', 'not_due', '1_7', '8_14', '15_30', 'over_30'], true) ? $agingBucket : 'all',
            'due_from' => trim((string) $request->query('due_from', '')),
            'due_to' => trim((string) $request->query('due_to', '')),
        ];
    }

    private function applyAgingBucket($query, string $bucket)
    {
        $today = now()->toDateString();

        return match ($bucket) {
            'not_due' => $query->where(function ($sub) use ($today) {
                $sub->whereNull('due_date')->orWhereDate('due_date', '>=', $today);
            }),
            '1_7' => $query->whereDate('due_date', '<', $today)->whereDate('due_date', '>=', now()->subDays(7)->toDateString()),
            '8_14' => $query->whereDate('due_date', '<', now()->subDays(7)->toDateString())->whereDate('due_date', '>=', now()->subDays(14)->toDateString()),
            '15_30' => $query->whereDate('due_date', '<', now()->subDays(14)->toDateString())->whereDate('due_date', '>=', now()->subDays(30)->toDateString()),
            'over_30' => $query->whereDate('due_date', '<', now()->subDays(30)->toDateString()),
            default => $query,
        };
    }

    private function supplierDebtReportSummary($rows): array
    {
        return [
            'open_count' => (int) $rows->count(),
            'supplier_count' => (int) $rows->pluck('supplier_id')->unique()->count(),
            'total_remaining' => (float) $rows->sum('remaining_amount'),
            'total_overdue' => (float) $rows
                ->filter(fn (SupplierPurchase $purchase) => $purchase->due_date && $purchase->due_date->lt(now()->startOfDay()))
                ->sum('remaining_amount'),
        ];
    }

    private function supplierDebtAgingSummary($rows): array
    {
        $today = now()->startOfDay();
        $buckets = [
            'not_due' => ['label' => 'Belum jatuh tempo', 'amount' => 0.0, 'count' => 0],
            '1_7' => ['label' => 'Overdue 1-7 hari', 'amount' => 0.0, 'count' => 0],
            '8_14' => ['label' => 'Overdue 8-14 hari', 'amount' => 0.0, 'count' => 0],
            '15_30' => ['label' => 'Overdue 15-30 hari', 'amount' => 0.0, 'count' => 0],
            'over_30' => ['label' => 'Overdue >30 hari', 'amount' => 0.0, 'count' => 0],
        ];

        foreach ($rows as $purchase) {
            $bucket = 'not_due';
            if ($purchase->due_date && $purchase->due_date->lt($today)) {
                $days = $purchase->due_date->diffInDays($today);
                $bucket = $days <= 7 ? '1_7' : ($days <= 14 ? '8_14' : ($days <= 30 ? '15_30' : 'over_30'));
            }

            $buckets[$bucket]['amount'] += (float) $purchase->remaining_amount;
            $buckets[$bucket]['count']++;
        }

        return $buckets;
    }

    private function supplierPurchasesFilteredQuery(Request $request)
    {
        $purchaseQ = trim((string) $request->query('purchase_q', ''));
        $purchaseStatus = (string) $request->query('purchase_status', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $purchaseSupplierId = (int) $request->query('purchase_supplier_id', 0);
        $dueFrom = trim((string) $request->query('due_from', ''));
        $dueTo = trim((string) $request->query('due_to', ''));
        $attachmentFilter = (string) $request->query('attachment_filter', 'all');
        $debtFilter = (string) $request->query('debt_filter', 'all');

        return $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->when($purchaseSupplierId > 0, fn ($builder) => $builder->where('supplier_id', $purchaseSupplierId))
            ->when($purchaseQ !== '', function ($builder) use ($purchaseQ) {
                $builder->where(function ($sub) use ($purchaseQ) {
                    $sub->where('number', 'like', "%{$purchaseQ}%")
                        ->orWhere('supplier_invoice_number', 'like', "%{$purchaseQ}%")
                        ->orWhere('delivery_note_number', 'like', "%{$purchaseQ}%")
                        ->orWhereHas('supplier', fn ($supplier) => $supplier->where('name', 'like', "%{$purchaseQ}%"));
                });
            })
            ->when(in_array($purchaseStatus, ['draft', 'partial_received', 'received', 'cancelled'], true), fn ($builder) => $builder->where('status', $purchaseStatus))
            ->when(in_array($paymentStatus, ['unpaid', 'partial', 'overdue', 'paid'], true), fn ($builder) => $builder->where('payment_status', $paymentStatus))
            ->when($debtFilter === 'open', fn ($builder) => $builder->where('remaining_amount', '>', 0))
            ->when($debtFilter === 'closed', fn ($builder) => $builder->where('remaining_amount', '<=', 0))
            ->when($dueFrom !== '', fn ($builder) => $builder->whereDate('due_date', '>=', $dueFrom))
            ->when($dueTo !== '', fn ($builder) => $builder->whereDate('due_date', '<=', $dueTo))
            ->when($attachmentFilter === 'with', fn ($builder) => $builder->has('attachments'))
            ->when($attachmentFilter === 'without', fn ($builder) => $builder->doesntHave('attachments'));
    }

    private function supplierDebtDashboard(Request $request): array
    {
        $openPurchases = $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->with('supplier:id,name,code')
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->get();

        $dueAlerts = $openPurchases
            ->filter(fn (SupplierPurchase $purchase) => $purchase->due_date && $purchase->due_date->lte(now()->addDays(7)))
            ->sortBy(fn (SupplierPurchase $purchase) => $purchase->due_date?->timestamp ?? PHP_INT_MAX)
            ->take(6)
            ->map(fn (SupplierPurchase $purchase) => [
                'id' => (int) $purchase->id,
                'number' => (string) $purchase->number,
                'supplier' => (string) ($purchase->supplier?->name ?? '-'),
                'due_date' => $purchase->due_date?->format('d/m/Y') ?? '-',
                'remaining_amount' => (float) $purchase->remaining_amount,
                'is_overdue' => (bool) ($purchase->due_date && $purchase->due_date->lt(now()->startOfDay())),
            ])
            ->values();

        $topSuppliers = $openPurchases
            ->groupBy('supplier_id')
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'supplier' => (string) ($first?->supplier?->name ?? '-'),
                    'count' => (int) $rows->count(),
                    'remaining_amount' => (float) $rows->sum('remaining_amount'),
                ];
            })
            ->sortByDesc('remaining_amount')
            ->take(5)
            ->values();

        $recentPayments = $this->applyBranchScope(SupplierPurchase::query(), $request->user(), 'supplier_purchases')
            ->join('supplier_purchase_payments', 'supplier_purchases.id', '=', 'supplier_purchase_payments.supplier_purchase_id')
            ->join('suppliers', 'supplier_purchases.supplier_id', '=', 'suppliers.id')
            ->orderByDesc('supplier_purchase_payments.paid_at')
            ->limit(5)
            ->get([
                'supplier_purchases.number',
                'suppliers.name as supplier_name',
                'supplier_purchase_payments.amount',
                'supplier_purchase_payments.payment_method',
                'supplier_purchase_payments.paid_at',
            ])
            ->map(fn ($row) => [
                'number' => (string) $row->number,
                'supplier' => (string) $row->supplier_name,
                'amount' => (float) $row->amount,
                'method' => (string) $row->payment_method,
                'paid_at' => Carbon::parse((string) $row->paid_at)->format('d/m/Y H:i'),
            ]);

        $pendingApprovals = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('type', 'supplier.purchase_approval')
            ->where('status', 'pending')
            ->count();

        return [
            'due_alerts' => $dueAlerts,
            'top_suppliers' => $topSuppliers,
            'recent_payments' => $recentPayments,
            'pending_approvals' => (int) $pendingApprovals,
            'open_count' => (int) $openPurchases->count(),
        ];
    }

    private function supplierDebtReport(Request $request): array
    {
        $rows = $this->applyBranchScope(SupplierPurchase::query(), $request->user())
            ->with('supplier:id,name,code')
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->where('remaining_amount', '>', 0)
            ->get();

        $today = now()->startOfDay();
        $bySupplier = $rows
            ->groupBy('supplier_id')
            ->map(function ($supplierRows) use ($today) {
                $first = $supplierRows->first();
                $overdueRows = $supplierRows->filter(fn (SupplierPurchase $purchase) => $purchase->due_date && $purchase->due_date->lt($today));

                return [
                    'supplier_id' => (int) ($first?->supplier_id ?? 0),
                    'supplier' => (string) ($first?->supplier?->name ?? '-'),
                    'code' => (string) ($first?->supplier?->code ?? '-'),
                    'open_count' => (int) $supplierRows->count(),
                    'overdue_count' => (int) $overdueRows->count(),
                    'remaining_amount' => (float) $supplierRows->sum('remaining_amount'),
                    'overdue_amount' => (float) $overdueRows->sum('remaining_amount'),
                    'nearest_due' => $supplierRows
                        ->filter(fn (SupplierPurchase $purchase) => $purchase->due_date !== null)
                        ->sortBy(fn (SupplierPurchase $purchase) => $purchase->due_date?->timestamp ?? PHP_INT_MAX)
                        ->first()?->due_date?->format('d/m/Y') ?? '-',
                ];
            })
            ->sortByDesc('remaining_amount')
            ->take(8)
            ->values();

        return [
            'supplier_count' => (int) $bySupplier->count(),
            'open_count' => (int) $rows->count(),
            'total_remaining' => (float) $rows->sum('remaining_amount'),
            'total_overdue' => (float) $rows->filter(fn (SupplierPurchase $purchase) => $purchase->due_date && $purchase->due_date->lt($today))->sum('remaining_amount'),
            'by_supplier' => $bySupplier,
        ];
    }

    private function purchaseHasApprovalDocument(SupplierPurchase $purchase): bool
    {
        return trim((string) ($purchase->supplier_invoice_number ?? '')) !== ''
            || trim((string) ($purchase->delivery_note_number ?? '')) !== ''
            || (int) ($purchase->attachments_count ?? 0) > 0;
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
            'due_date' => ['nullable', 'date', 'after_or_equal:ordered_at'],
            'supplier_invoice_number' => ['nullable', 'string', 'max:80'],
            'delivery_note_number' => ['nullable', 'string', 'max:80'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_accounting_treatment' => ['nullable', Rule::in(['inventory', 'expense'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:160'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['required', 'numeric', 'gt:0'],
        ];

        if ($allowDownPayment) {
            $rules['down_payment_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['down_payment_method'] = ['nullable', Rule::in(['cash', 'transfer', 'debit', 'qris', 'e_wallet'])];
        }

        $validated = $request->validate($rules);
        $this->validatePurchaseTotals($validated, $allowDownPayment);

        return $validated;
    }

    private function validatePurchaseTotals(array $validated, bool $allowDownPayment): void
    {
        $subtotal = collect((array) ($validated['items'] ?? []))
            ->sum(fn ($item) => (int) $item['quantity'] * (float) $item['unit_cost']);
        $total = max(
            (float) $subtotal
            - (float) ($validated['discount_amount'] ?? 0)
            + (float) ($validated['tax_amount'] ?? 0)
            + (float) ($validated['shipping_amount'] ?? 0),
            0
        );

        if ($total <= 0) {
            throw ValidationException::withMessages([
                'items' => 'Total pembelian harus lebih besar dari Rp 0.',
            ]);
        }

        if ($allowDownPayment && (float) ($validated['down_payment_amount'] ?? 0) > $total) {
            throw ValidationException::withMessages([
                'down_payment_amount' => 'DP tidak boleh lebih besar dari total pembelian.',
            ]);
        }
    }

    private function applyShippingAllocationToItems(array $items, float $shipping, string $treatment): array
    {
        $subtotal = collect($items)->sum(fn ($item) => (float) ($item['line_total'] ?? 0));
        $allocateToInventory = $treatment === 'inventory' && $shipping > 0 && $subtotal > 0;
        $allocatedSoFar = 0.0;
        $lastIndex = count($items) - 1;

        foreach ($items as $index => $item) {
            $qty = max((int) ($item['quantity'] ?? 1), 1);
            $lineTotal = (float) ($item['line_total'] ?? 0);
            $allocation = 0.0;

            if ($allocateToInventory) {
                $allocation = $index === $lastIndex
                    ? round($shipping - $allocatedSoFar, 2)
                    : round($shipping * ($lineTotal / $subtotal), 2);
                $allocatedSoFar += $allocation;
            }

            $landedLineTotal = round($lineTotal + $allocation, 2);
            $items[$index]['shipping_allocation_amount'] = $allocation;
            $items[$index]['landed_unit_cost'] = round($landedLineTotal / $qty, 2);
            $items[$index]['landed_line_total'] = $landedLineTotal;
        }

        return $items;
    }

    private function effectiveInventoryUnitCost(SupplierPurchaseItem $item): float
    {
        $landedUnitCost = (float) ($item->landed_unit_cost ?? 0);

        return $landedUnitCost > 0 ? $landedUnitCost : (float) $item->unit_cost;
    }

    private function purchaseStatusLabels(): array
    {
        return [
            'draft' => 'Draft',
            'partial_received' => 'Diterima Sebagian',
            'received' => 'Barang Diterima',
            'cancelled' => 'Dibatalkan',
        ];
    }

    private function paymentStatusLabels(): array
    {
        return [
            'unpaid' => 'Belum Bayar',
            'partial' => 'Dibayar Sebagian',
            'overdue' => 'Lewat Tempo',
            'paid' => 'Lunas',
        ];
    }

    private function approvalStatusLabels(): array
    {
        return [
            'none' => 'Belum Diajukan',
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'stale' => 'Perlu Ulang',
        ];
    }

    private function supplierAuditActionLabels(): array
    {
        return [
            'supplier_purchase_created' => 'Draft pembelian dibuat',
            'supplier_purchase_approval_requested' => 'Persetujuan diajukan',
            'supplier_purchase_duplicated' => 'Draft diduplikasi',
            'supplier_purchase_draft_updated' => 'Draft diperbarui',
            'supplier_purchase_reconciled' => 'Rekonsiliasi disimpan',
            'supplier_purchase_attachment_uploaded' => 'Lampiran diunggah',
            'supplier_purchase_attachment_deleted' => 'Lampiran dihapus',
            'supplier_purchase_payment_proof_uploaded' => 'Bukti bayar diunggah',
            'supplier_purchase_cancelled' => 'Pembelian dibatalkan',
            'supplier_purchase_partial_received' => 'Barang diterima sebagian',
            'supplier_purchase_received' => 'Barang diterima penuh',
            'supplier_purchase_returned' => 'Retur pembelian dicatat',
            'supplier_purchase_paid' => 'Pembayaran dicatat',
            'approval_request_created' => 'Permintaan approval dibuat',
            'supplier_debt_due_reminder_generated' => 'Reminder jatuh tempo dibuat',
        ];
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
            ->where(function ($query) use ($purchase) {
                $query->where('payload->fingerprint', $this->supplierPurchaseFingerprint($purchase))
                    ->orWhereNull('payload->fingerprint');
            })
            ->exists();
    }

    private function supplierPurchaseIdsMatchingApprovalFilter($purchasesQuery, Request $request, string $approvalFilter): array
    {
        $candidates = (clone $purchasesQuery)
            ->withoutEagerLoads()
            ->with('items')
            ->get();

        if ($candidates->isEmpty()) {
            return [];
        }

        $purchaseIds = $candidates->pluck('id')->map(fn ($id) => (int) $id)->all();
        $purchaseFingerprints = $candidates
            ->mapWithKeys(fn (SupplierPurchase $purchase) => [(int) $purchase->id => $this->supplierPurchaseFingerprint($purchase)])
            ->all();

        $approvalRows = $this->applyBranchScope(ApprovalRequest::query(), $request->user())
            ->where('type', 'supplier.purchase_approval')
            ->latest('id')
            ->get(['id', 'status', 'payload'])
            ->filter(fn (ApprovalRequest $approval) => in_array((int) data_get((array) $approval->payload, 'supplier_purchase_id'), $purchaseIds, true));

        $approvalStatusByPurchase = [];
        foreach ($purchaseIds as $purchaseId) {
            $rows = $approvalRows
                ->filter(fn (ApprovalRequest $approval) => (int) data_get((array) $approval->payload, 'supplier_purchase_id') === (int) $purchaseId)
                ->values();

            $current = $rows->first(function (ApprovalRequest $approval) use ($purchaseFingerprints, $purchaseId) {
                $fingerprint = (string) data_get((array) $approval->payload, 'fingerprint', '');
                return $fingerprint === '' || $fingerprint === (string) ($purchaseFingerprints[(int) $purchaseId] ?? '');
            });

            if ($current) {
                $approvalStatusByPurchase[(int) $purchaseId] = (string) $current->status;
                continue;
            }

            if ($rows->isNotEmpty()) {
                $approvalStatusByPurchase[(int) $purchaseId] = 'stale';
            }
        }

        return $candidates
            ->filter(function (SupplierPurchase $purchase) use ($approvalFilter, $approvalStatusByPurchase) {
                $approvalStatus = (string) ($approvalStatusByPurchase[(int) $purchase->id] ?? '');
                return $approvalFilter === 'none'
                    ? $approvalStatus === ''
                    : $approvalStatus === $approvalFilter;
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function supplierPurchaseFingerprint(SupplierPurchase $purchase): string
    {
        $purchase->loadMissing('items');

        return sha1(json_encode([
            'supplier_id' => (int) $purchase->supplier_id,
            'supplier_invoice_number' => (string) ($purchase->supplier_invoice_number ?? ''),
            'delivery_note_number' => (string) ($purchase->delivery_note_number ?? ''),
            'ordered_at' => optional($purchase->ordered_at)->toDateString(),
            'payment_term_days' => (int) ($purchase->payment_term_days ?? 0),
            'due_date' => optional($purchase->due_date)->toDateString(),
            'discount_amount' => round((float) $purchase->discount_amount, 2),
            'tax_amount' => round((float) $purchase->tax_amount, 2),
            'shipping_amount' => round((float) $purchase->shipping_amount, 2),
            'shipping_accounting_treatment' => (string) ($purchase->shipping_accounting_treatment ?? 'inventory'),
            'total_amount' => round((float) $purchase->total_amount, 2),
            'items' => $purchase->items
                ->sortBy('id')
                ->map(fn (SupplierPurchaseItem $item) => [
                    'name' => (string) $item->product_name,
                    'quantity' => (int) $item->quantity,
                    'unit_cost' => round((float) $item->unit_cost, 2),
                    'line_total' => round((float) $item->line_total, 2),
                    'shipping_allocation_amount' => round((float) ($item->shipping_allocation_amount ?? 0), 2),
                    'landed_unit_cost' => round((float) ($item->landed_unit_cost ?? 0), 2),
                ])
                ->values()
                ->all(),
        ]));
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

    private function recordSupplierPayment(SupplierPurchase $purchase, Request $request, float $amount, string $method, ?string $reference = null, ?string $note = null, ?Carbon $paidAt = null): array
    {
        $payment = SupplierPurchasePayment::query()->create([
            'supplier_purchase_id' => $purchase->id,
            'received_by' => $request->user()?->id,
            'paid_at' => $paidAt ?: now(),
            'amount' => $amount,
            'payment_method' => $method,
            'reference_number' => $reference,
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

        return [$newRemaining, $payment];
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
