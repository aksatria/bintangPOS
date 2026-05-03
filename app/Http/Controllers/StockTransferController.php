<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Support\ActiveBranchContext;
use App\Support\TelegramNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        $isOwner = $user?->hasAnyRole(['owner']) ?? false;
        $status = trim((string) $request->query('status', 'all'));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));
        $sourceBranchId = (int) $request->query('source_branch_id', 0);
        $destinationBranchId = (int) $request->query('destination_branch_id', 0);
        $quick = trim((string) $request->query('quick', ''));

        if ($quick === 'today') {
            $dateFrom = now()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($quick === '7d') {
            $dateFrom = now()->subDays(6)->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($quick === 'month') {
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->toDateString();
        } elseif ($quick === 'pending') {
            $status = StockTransfer::STATUS_REQUESTED;
        } elseif ($quick === 'overdue') {
            // overdue: masih requested/approved lebih dari 24 jam
        }

        $query = StockTransfer::query()
            ->with([
                'sourceBranch:id,name,code',
                'destinationBranch:id,name,code',
                'requester:id,name',
                'approver:id,name',
                'receiver:id,name',
                'items:id,stock_transfer_id,sku,product_name,unit,requested_qty,received_qty',
            ])
            ->withCount('items')
            ->latest('id');

        if (!$isOwner && $activeBranchId > 0) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('source_branch_id', $activeBranchId)
                    ->orWhere('destination_branch_id', $activeBranchId);
            });
        }

        if ($status !== 'all' && in_array($status, [
            StockTransfer::STATUS_REQUESTED,
            StockTransfer::STATUS_APPROVED,
            StockTransfer::STATUS_REJECTED,
            StockTransfer::STATUS_RECEIVED,
            StockTransfer::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $status);
        }
        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        if ($sourceBranchId > 0) {
            $query->where('source_branch_id', $sourceBranchId);
        }
        if ($destinationBranchId > 0) {
            $query->where('destination_branch_id', $destinationBranchId);
        }
        if ($quick === 'overdue') {
            $query->whereIn('status', [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED])
                ->where('created_at', '<=', now()->subHours(24));
        }

        $transfers = $query->paginate(15)->withQueryString();

        $baseCountQuery = StockTransfer::query();
        if (!$isOwner && $activeBranchId > 0) {
            $baseCountQuery->where(function ($q) use ($activeBranchId) {
                $q->where('source_branch_id', $activeBranchId)
                    ->orWhere('destination_branch_id', $activeBranchId);
            });
        }
        if ($sourceBranchId > 0) {
            $baseCountQuery->where('source_branch_id', $sourceBranchId);
        }
        if ($destinationBranchId > 0) {
            $baseCountQuery->where('destination_branch_id', $destinationBranchId);
        }

        $presetCounts = [
            'today' => (clone $baseCountQuery)
                ->whereDate('created_at', now()->toDateString())
                ->count(),
            '7d' => (clone $baseCountQuery)
                ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
                ->whereDate('created_at', '<=', now()->toDateString())
                ->count(),
            'month' => (clone $baseCountQuery)
                ->whereDate('created_at', '>=', now()->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', now()->toDateString())
                ->count(),
            'pending' => (clone $baseCountQuery)
                ->where('status', StockTransfer::STATUS_REQUESTED)
                ->count(),
            'overdue' => (clone $baseCountQuery)
                ->whereIn('status', [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED])
                ->where('created_at', '<=', now()->subHours(24))
                ->count(),
        ];

        $branchOptions = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $destinationBranches = Branch::query()
            ->where('is_active', true)
            ->when($activeBranchId > 0, fn ($q) => $q->where('id', '!=', $activeBranchId))
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $sourceProducts = $activeBranchId > 0
            ? Product::query()
                ->where('branch_id', $activeBranchId)
                ->active()
                ->where('stock', '>', 0)
                ->orderBy('name')
                ->limit(200)
                ->get(['id', 'name', 'sku', 'stock', 'unit'])
            : collect();

        return view('stock-transfers.index', [
            'transfers' => $transfers,
            'destinationBranches' => $destinationBranches,
            'branchOptions' => $branchOptions,
            'sourceProducts' => $sourceProducts,
            'activeBranchId' => $activeBranchId,
            'status' => $status,
            'isOwner' => $isOwner,
            'canRequest' => $user?->hasPermission('stock-transfer.request') ?? false,
            'canApprove' => $user?->hasPermission('stock-transfer.approve') ?? false,
            'canReceive' => $user?->hasPermission('stock-transfer.receive') ?? false,
            'canExport' => $user?->hasPermission('stock-transfer.export') ?? false,
            'presetCounts' => $presetCounts,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'source_branch_id' => $sourceBranchId,
                'destination_branch_id' => $destinationBranchId,
                'quick' => $quick,
            ],
        ]);
    }

    public function show(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()
            ->with([
                'sourceBranch:id,name,code,address',
                'destinationBranch:id,name,code,address',
                'requester:id,name,email',
                'approver:id,name,email',
                'receiver:id,name,email',
                'items:id,stock_transfer_id,sku,product_name,unit,requested_qty,received_qty,discrepancy_reason,created_at',
            ])
            ->findOrFail($transferId);

        $this->authorizeTransferVisibility($request, $transfer);

        $timeline = [
            [
                'label' => 'Requested',
                'at' => $transfer->created_at,
                'actor' => $transfer->requester?->name ?: '-',
                'note' => $transfer->note ?: null,
                'done' => true,
            ],
            [
                'label' => 'Approved',
                'at' => $transfer->approved_at,
                'actor' => $transfer->approver?->name ?: '-',
                'note' => null,
                'done' => in_array((string) $transfer->status, [
                    StockTransfer::STATUS_APPROVED,
                    StockTransfer::STATUS_RECEIVED,
                ], true),
            ],
            [
                'label' => 'Rejected',
                'at' => $transfer->status === StockTransfer::STATUS_REJECTED ? $transfer->approved_at : null,
                'actor' => $transfer->status === StockTransfer::STATUS_REJECTED ? ($transfer->approver?->name ?: '-') : '-',
                'note' => $transfer->status === StockTransfer::STATUS_REJECTED ? ($transfer->reject_reason ?: null) : null,
                'done' => $transfer->status === StockTransfer::STATUS_REJECTED,
            ],
            [
                'label' => 'Received',
                'at' => $transfer->received_at,
                'actor' => $transfer->receiver?->name ?: '-',
                'note' => null,
                'done' => $transfer->status === StockTransfer::STATUS_RECEIVED,
            ],
            [
                'label' => 'Cancelled',
                'at' => $transfer->status === StockTransfer::STATUS_CANCELLED ? $transfer->updated_at : null,
                'actor' => $transfer->status === StockTransfer::STATUS_CANCELLED ? ($transfer->requester?->name ?: '-') : '-',
                'note' => $transfer->status === StockTransfer::STATUS_CANCELLED ? 'Dibatalkan sebelum diproses.' : null,
                'done' => $transfer->status === StockTransfer::STATUS_CANCELLED,
            ],
        ];

        return view('stock-transfers.show', [
            'transfer' => $transfer,
            'timeline' => $timeline,
            'isOverdue' => in_array((string) $transfer->status, [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED], true)
                && optional($transfer->created_at)->lte(now()->subHours(24)),
            'overdueHours' => $transfer->created_at ? $transfer->created_at->diffInHours(now()) : null,
            'canReceive' => $request->user()?->hasPermission('stock-transfer.receive') ?? false,
            'canExport' => $request->user()?->hasPermission('stock-transfer.export') ?? false,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $sourceBranchId = (int) (ActiveBranchContext::resolveBranchId($user) ?? 0);
        if ($sourceBranchId <= 0) {
            return back()->withErrors(['branch_id' => 'Cabang aktif tidak ditemukan.']);
        }

        $payload = $request->validate([
            'destination_branch_id' => ['required', 'integer', 'exists:branches,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'delivery_ref' => ['nullable', 'string', 'max:100'],
            'courier_name' => ['nullable', 'string', 'max:120'],
            'dispatch_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $destinationBranchId = (int) $payload['destination_branch_id'];
        if ($destinationBranchId === $sourceBranchId) {
            return back()->withErrors(['destination_branch_id' => 'Cabang tujuan tidak boleh sama dengan cabang aktif.']);
        }

        $items = collect($payload['items'])
            ->map(fn ($row) => [
                'product_id' => (int) ($row['product_id'] ?? 0),
                'qty' => (int) ($row['qty'] ?? 0),
            ])
            ->filter(fn ($row) => $row['product_id'] > 0 && $row['qty'] > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withErrors(['items' => 'Tambahkan minimal 1 item dengan qty valid.']);
        }

        DB::transaction(function () use ($request, $payload, $sourceBranchId, $destinationBranchId, $items) {
            $productIds = $items->pluck('product_id')->unique()->values();
            $sourceProducts = Product::query()
                ->where('branch_id', $sourceBranchId)
                ->whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($items as $row) {
                /** @var Product|null $product */
                $product = $sourceProducts->get($row['product_id']);
                if (!$product) {
                    throw ValidationException::withMessages([
                        'items' => "Produk ID {$row['product_id']} tidak valid untuk cabang aktif.",
                    ]);
                }
                if ((int) $product->stock < (int) $row['qty']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok produk {$product->name} tidak cukup.",
                    ]);
                }
            }

            $dispatchProofPath = null;
            if ($request->hasFile('dispatch_proof')) {
                $dispatchProofPath = $request->file('dispatch_proof')->store('stock-transfers/dispatch', 'public');
            }

            $transfer = StockTransfer::query()->create([
                'source_branch_id' => $sourceBranchId,
                'destination_branch_id' => $destinationBranchId,
                'requested_by' => $request->user()->id,
                'code' => $this->generateCode(),
                'status' => StockTransfer::STATUS_REQUESTED,
                'note' => trim((string) ($payload['note'] ?? '')) ?: null,
                'delivery_ref' => trim((string) ($payload['delivery_ref'] ?? '')) ?: null,
                'courier_name' => trim((string) ($payload['courier_name'] ?? '')) ?: null,
                'dispatch_proof_path' => $dispatchProofPath,
            ]);

            $rows = [];
            foreach ($items as $row) {
                /** @var Product $product */
                $product = $sourceProducts->get($row['product_id']);
                $rows[] = [
                    'stock_transfer_id' => $transfer->id,
                    'source_product_id' => $product->id,
                    'sku' => (string) $product->sku,
                    'product_name' => (string) $product->name,
                    'unit' => (string) $product->unit,
                    'requested_qty' => (int) $row['qty'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            StockTransferItem::query()->insert($rows);

            $this->logAudit($request, 'stock_transfer_requested', [
                'stock_transfer_id' => $transfer->id,
                'code' => $transfer->code,
                'source_branch_id' => $sourceBranchId,
                'destination_branch_id' => $destinationBranchId,
                'total_items' => count($rows),
            ]);

            $this->notifyWorkflow("Mutasi REQUESTED\nCode: {$transfer->code}\nSource Branch ID: {$sourceBranchId}\nDestination Branch ID: {$destinationBranchId}\nItem: " . count($rows));
        });

        return back()->with('status', 'Permintaan mutasi stok berhasil dibuat.');
    }

    public function approve(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()->with('items')->findOrFail($transferId);
        $this->authorizeDestinationAction($request, $transfer);

        if ($transfer->status !== StockTransfer::STATUS_REQUESTED) {
            return back()->withErrors(['transfer' => 'Hanya transfer berstatus REQUESTED yang bisa disetujui.']);
        }

        DB::transaction(function () use ($transfer, $request) {
            $locked = StockTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($locked->status !== StockTransfer::STATUS_REQUESTED) {
                throw ValidationException::withMessages(['transfer' => 'Status transfer sudah berubah.']);
            }

            $items = StockTransferItem::query()
                ->where('stock_transfer_id', $locked->id)
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $source = Product::query()
                    ->where('branch_id', $locked->source_branch_id)
                    ->whereKey($item->source_product_id)
                    ->lockForUpdate()
                    ->first();

                if (! $source) {
                    throw ValidationException::withMessages(['transfer' => "Produk sumber {$item->product_name} tidak ditemukan."]);
                }
                if ((int) $source->stock < (int) $item->requested_qty) {
                    throw ValidationException::withMessages(['transfer' => "Stok {$item->product_name} tidak cukup untuk reserve."]);
                }

                // reserve: stok sumber dikurangi agar tidak terpakai transaksi lain
                $source->decrement('stock', (int) $item->requested_qty);
                $item->update(['reserved_qty' => (int) $item->requested_qty]);
            }

            $locked->update([
                'status' => StockTransfer::STATUS_APPROVED,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
        });

        $this->logAudit($request, 'stock_transfer_approved', [
            'stock_transfer_id' => $transfer->id,
            'code' => $transfer->code,
        ]);
        $this->notifyWorkflow("Mutasi APPROVED\nCode: {$transfer->code}\nTujuan: {$transfer->destinationBranch?->name}");

        return back()->with('status', 'Transfer berhasil disetujui.');
    }

    public function reject(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);
        $this->authorizeDestinationAction($request, $transfer);

        if ($transfer->status !== StockTransfer::STATUS_REQUESTED) {
            return back()->withErrors(['transfer' => 'Hanya transfer berstatus REQUESTED yang bisa ditolak.']);
        }

        $data = $request->validate([
            'reject_reason' => ['required', 'string', 'max:1000'],
        ]);

        $transfer->update([
            'status' => StockTransfer::STATUS_REJECTED,
            'approved_by' => $request->user()->id,
            'reject_reason' => trim((string) $data['reject_reason']),
            'approved_at' => now(),
        ]);

        $this->logAudit($request, 'stock_transfer_rejected', [
            'stock_transfer_id' => $transfer->id,
            'code' => $transfer->code,
        ]);
        $this->notifyWorkflow("Mutasi REJECTED\nCode: {$transfer->code}\nAlasan: " . trim((string) $data['reject_reason']));

        return back()->with('status', 'Transfer ditolak.');
    }

    public function receive(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()->with('items')->findOrFail($transferId);
        $this->authorizeDestinationAction($request, $transfer);

        if ($transfer->status !== StockTransfer::STATUS_APPROVED) {
            return back()->withErrors(['transfer' => 'Hanya transfer berstatus APPROVED yang bisa diterima.']);
        }

        $payload = $request->validate([
            'receive_note' => ['nullable', 'string', 'max:1000'],
            'receive_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.received_qty' => ['required_with:items', 'integer', 'min:0'],
            'items.*.discrepancy_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $receiveProofPath = null;
        if ($request->hasFile('receive_proof')) {
            $receiveProofPath = $request->file('receive_proof')->store('stock-transfers/receive', 'public');
        }

        DB::transaction(function () use ($request, $transfer, $payload, $receiveProofPath) {
            $lockedTransfer = StockTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            if ($lockedTransfer->status !== StockTransfer::STATUS_APPROVED) {
                throw ValidationException::withMessages([
                    'transfer' => 'Status transfer sudah berubah, silakan refresh halaman.',
                ]);
            }

            $items = StockTransferItem::query()
                ->where('stock_transfer_id', $lockedTransfer->id)
                ->lockForUpdate()
                ->get();

            $receivedMap = collect($payload['items'] ?? [])
                ->mapWithKeys(fn ($row) => [
                    (int) ($row['id'] ?? 0) => [
                        'received_qty' => (int) ($row['received_qty'] ?? 0),
                        'discrepancy_reason' => trim((string) ($row['discrepancy_reason'] ?? '')),
                    ],
                ]);

            foreach ($items as $item) {
                $requestedQty = (int) $item->requested_qty;
                $receivedRow = $receivedMap->get((int) $item->id, null);
                $receivedQty = $receivedRow !== null ? (int) ($receivedRow['received_qty'] ?? 0) : $requestedQty;
                $discrepancyReason = $receivedRow !== null ? (string) ($receivedRow['discrepancy_reason'] ?? '') : '';

                if ($receivedQty < 0 || $receivedQty > $requestedQty) {
                    throw ValidationException::withMessages([
                        'items' => "Qty receive {$item->product_name} harus 0 sampai {$requestedQty}.",
                    ]);
                }
                if ($receivedQty !== $requestedQty && $discrepancyReason === '') {
                    throw ValidationException::withMessages([
                        'items' => "Alasan selisih wajib diisi untuk {$item->product_name}.",
                    ]);
                }

                $source = Product::query()
                    ->where('branch_id', $lockedTransfer->source_branch_id)
                    ->whereKey($item->source_product_id)
                    ->lockForUpdate()
                    ->first();

                if (!$source) {
                    throw ValidationException::withMessages([
                        'transfer' => "Produk sumber {$item->product_name} tidak ditemukan.",
                    ]);
                }
                $destination = Product::query()
                    ->where('branch_id', $lockedTransfer->destination_branch_id)
                    ->where('sku', $item->sku)
                    ->lockForUpdate()
                    ->first();

                if (!$destination) {
                    $branchSku = $source->sku . '-B' . $lockedTransfer->destination_branch_id;
                    $destination = Product::query()->create([
                        'branch_id' => $lockedTransfer->destination_branch_id,
                        'category_id' => $source->category_id,
                        'name' => $source->name,
                        'sku' => mb_substr($branchSku, 0, 100),
                        'barcode' => null,
                        'purchase_price' => $source->purchase_price,
                        'selling_price' => $source->selling_price,
                        'stock' => 0,
                        'low_stock_threshold' => $source->low_stock_threshold,
                        'unit' => $source->unit,
                        'image' => $source->image,
                        'is_active' => $source->is_active,
                    ]);
                }

                // stok sudah di-reserve saat approve; receive hanya memindahkan ke tujuan
                if ($receivedQty > 0) {
                    $destination->increment('stock', $receivedQty);
                }
                // selisih (reserved - received) dikembalikan ke cabang sumber
                $returnQty = max(0, (int) $item->reserved_qty - $receivedQty);
                if ($returnQty > 0) {
                    $source->increment('stock', $returnQty);
                }
                $item->update([
                    'reserved_qty' => 0,
                    'received_qty' => $receivedQty,
                    'discrepancy_reason' => $receivedQty !== $requestedQty ? $discrepancyReason : null,
                ]);
            }

            $lockedTransfer->update([
                'status' => StockTransfer::STATUS_RECEIVED,
                'received_by' => $request->user()->id,
                'received_at' => now(),
                'receive_note' => trim((string) ($payload['receive_note'] ?? '')) ?: null,
                'receive_proof_path' => $receiveProofPath ?: $lockedTransfer->receive_proof_path,
            ]);
        });

        $this->logAudit($request, 'stock_transfer_received', [
            'stock_transfer_id' => $transfer->id,
            'code' => $transfer->code,
        ]);
        $this->notifyWorkflow("Mutasi RECEIVED\nCode: {$transfer->code}\nPenerima: " . ($request->user()?->name ?? '-'));

        return back()->with('status', 'Transfer diterima dan stok sudah dipindahkan.');
    }

    public function cancel(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()->findOrFail($transferId);
        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0);
        $isOwner = $request->user()?->hasAnyRole(['owner']) ?? false;

        if (!$isOwner && $activeBranchId !== (int) $transfer->source_branch_id) {
            abort(403);
        }
        if (! in_array($transfer->status, [StockTransfer::STATUS_REQUESTED, StockTransfer::STATUS_APPROVED], true)) {
            return back()->withErrors(['transfer' => 'Hanya transfer REQUESTED/APPROVED yang bisa dibatalkan.']);
        }

        DB::transaction(function () use ($transfer) {
            $locked = StockTransfer::query()->lockForUpdate()->findOrFail($transfer->id);
            $items = StockTransferItem::query()
                ->where('stock_transfer_id', $locked->id)
                ->lockForUpdate()
                ->get();

            if ($locked->status === StockTransfer::STATUS_APPROVED) {
                foreach ($items as $item) {
                    if ((int) $item->reserved_qty > 0) {
                        Product::query()
                            ->where('branch_id', $locked->source_branch_id)
                            ->whereKey($item->source_product_id)
                            ->lockForUpdate()
                            ->first()?->increment('stock', (int) $item->reserved_qty);
                        $item->update(['reserved_qty' => 0]);
                    }
                }
            }

            $locked->update(['status' => StockTransfer::STATUS_CANCELLED]);
        });
        $this->logAudit($request, 'stock_transfer_cancelled', [
            'stock_transfer_id' => $transfer->id,
            'code' => $transfer->code,
        ]);
        $this->notifyWorkflow("Mutasi CANCELLED\nCode: {$transfer->code}");

        return back()->with('status', 'Transfer dibatalkan.');
    }

    public function exportCsv(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()
            ->with(['sourceBranch:id,name,code', 'destinationBranch:id,name,code', 'items'])
            ->findOrFail($transferId);
        $this->authorizeTransferVisibility($request, $transfer);

        $filename = 'stock-transfer-' . strtolower($transfer->code) . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($transfer) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Code', $transfer->code]);
            fputcsv($out, ['Status', $transfer->status]);
            fputcsv($out, ['Source', ($transfer->sourceBranch?->name ?? '-') . ' (' . ($transfer->sourceBranch?->code ?? '-') . ')']);
            fputcsv($out, ['Destination', ($transfer->destinationBranch?->name ?? '-') . ' (' . ($transfer->destinationBranch?->code ?? '-') . ')']);
            fputcsv($out, ['Requested At', optional($transfer->created_at)->format('Y-m-d H:i:s')]);
            fputcsv($out, ['Delivery Ref', $transfer->delivery_ref ?: '-']);
            fputcsv($out, ['Courier', $transfer->courier_name ?: '-']);
            fputcsv($out, []);
            fputcsv($out, ['SKU', 'Product', 'Unit', 'Requested Qty', 'Received Qty', 'Discrepancy Reason']);
            foreach ($transfer->items as $item) {
                fputcsv($out, [
                    $item->sku,
                    $item->product_name,
                    $item->unit,
                    $item->requested_qty,
                    $item->received_qty ?? '',
                    $item->discrepancy_reason ?? '',
                ]);
            }
            fclose($out);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request, int $transferId)
    {
        $transfer = StockTransfer::query()
            ->with(['sourceBranch:id,name,code', 'destinationBranch:id,name,code', 'requester:id,name', 'approver:id,name', 'receiver:id,name', 'items'])
            ->findOrFail($transferId);
        $this->authorizeTransferVisibility($request, $transfer);

        $pdf = Pdf::loadView('pdf.stock-transfer', [
            'transfer' => $transfer,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('stock-transfer-' . strtolower($transfer->code) . '.pdf');
    }

    private function authorizeDestinationAction(Request $request, StockTransfer $transfer): void
    {
        $isOwner = $request->user()?->hasAnyRole(['owner']) ?? false;
        if ($isOwner) {
            return;
        }

        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0);
        if ($activeBranchId <= 0 || $activeBranchId !== (int) $transfer->destination_branch_id) {
            abort(403);
        }
    }

    private function authorizeTransferVisibility(Request $request, StockTransfer $transfer): void
    {
        $isOwner = $request->user()?->hasAnyRole(['owner']) ?? false;
        if ($isOwner) {
            return;
        }

        $activeBranchId = (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0);
        $isRelatedBranch = $activeBranchId > 0 && (
            $activeBranchId === (int) $transfer->source_branch_id
            || $activeBranchId === (int) $transfer->destination_branch_id
        );

        if (!$isRelatedBranch) {
            abort(403);
        }
    }

    private function generateCode(): string
    {
        return 'TRF-' . now()->format('Ymd-His') . '-' . strtoupper(substr((string) str()->uuid(), 0, 5));
    }

    private function logAudit(Request $request, string $action, array $context): void
    {
        CashierAuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'branch_id' => (int) (ActiveBranchContext::resolveBranchId($request->user()) ?? 0) ?: null,
            'action' => $action,
            'context' => $context,
            'ip_address' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);
    }

    private function notifyWorkflow(string $message): void
    {
        if (!TelegramNotifier::enabled()) {
            return;
        }

        TelegramNotifier::send($message, 'stock_transfer_workflow', TelegramNotifier::defaultChatId());
    }
}
