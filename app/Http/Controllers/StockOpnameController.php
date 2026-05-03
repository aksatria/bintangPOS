<?php

namespace App\Http\Controllers;

use App\Models\CashierAuditLog;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\User;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use App\Support\TelegramNotifier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class StockOpnameController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $userId = (int) $request->query('user_id', 0);
        $from = $request->date('from');
        $to = $request->date('to');
        $sort = trim((string) $request->query('sort', 'latest'));
        $quick = trim((string) $request->query('quick', ''));

        $query = $this->applyBranchScope(StockOpname::query(), $request->user())
            ->with('user:id,name')
            ->withCount([
                'items as filled_items_count' => fn ($q) => $q->whereNotNull('counted_stock'),
            ])
            ->when(in_array($status, ['open', 'posted'], true), fn ($q) => $q->where('status', $status))
            ->when($userId > 0, fn ($q) => $q->where('user_id', $userId))
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->when($quick === 'open', fn ($q) => $q->where('status', 'open'))
            ->when($quick === 'posted', fn ($q) => $q->where('status', 'posted'))
            ->when($quick === 'incomplete', fn ($q) => $q
                ->where('status', 'open')
                ->whereRaw('(select count(*) from stock_opname_items soi where soi.stock_opname_id = stock_opnames.id and soi.counted_stock is not null) < stock_opnames.total_items'));

        if ($sort === 'progress_desc') {
            $query->orderByRaw('CASE WHEN total_items > 0 THEN (filled_items_count * 1.0 / total_items) ELSE 0 END DESC');
        } elseif ($sort === 'diff_desc') {
            $query->orderByRaw('ABS(total_difference) DESC');
        } else {
            $query->latest('id');
        }

        $sessions = $query->paginate(20)->withQueryString();

        $openSession = $this->applyBranchScope(StockOpname::query(), $request->user())->where('status', 'open')->latest('id')->first();

        return view('stock-opnames.index', [
            'sessions' => $sessions,
            'openSession' => $openSession,
            'users' => $this->applyBranchScope(\App\Models\User::query(), $request->user())->whereIn('role', ['owner', 'admin', 'kasir'])->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'status' => $status,
                'user_id' => $userId,
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
                'sort' => $sort,
                'quick' => $quick,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $openExists = $this->applyBranchScope(StockOpname::query(), $request->user())->where('status', 'open')->exists();
        if ($openExists) {
            throw ValidationException::withMessages([
                'note' => 'Masih ada sesi opname OPEN. Selesaikan dulu sebelum membuat sesi baru.',
            ]);
        }

        $session = DB::transaction(function () use ($request, $payload) {
            $code = 'OPN-'.now()->format('Ymd-His').'-'.strtoupper(substr((string) str()->uuid(), 0, 5));
            $session = StockOpname::query()->create([
                'user_id' => $request->user()->id,
                'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
                'code' => $code,
                'status' => 'open',
                'note' => trim((string) ($payload['note'] ?? '')),
                'started_at' => now(),
            ]);

            $products = $this->applyBranchScope(Product::query(), $request->user())
                ->active()
                ->orderBy('name')
                ->get(['id', 'stock']);

            $rows = $products->map(fn (Product $product) => [
                'stock_opname_id' => $session->id,
                'product_id' => $product->id,
                'system_stock' => (int) $product->stock,
                'counted_stock' => null,
                'difference' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if (! empty($rows)) {
                StockOpnameItem::query()->insert($rows);
            }

            $session->update(['total_items' => count($rows)]);
            $this->logAudit($request, 'stock_opname_started', [
                'stock_opname_id' => $session->id,
                'code' => $session->code,
                'total_items' => count($rows),
            ]);

            return $session;
        });

        return redirect()->route('stock-opnames.show', $session)->with('success', 'Sesi stock opname berhasil dibuat.');
    }

    public function show(Request $request, StockOpname $stockOpname)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('fill', 'all')); // all|filled|empty|diff
        $items = $stockOpname->items()
            ->with('product:id,name,sku,unit,is_active')
            ->when($q !== '', fn ($query) => $query->whereHas('product', fn ($p) => $p
                ->where('name', 'like', "%{$q}%")
                ->orWhere('sku', 'like', "%{$q}%")))
            ->when($status === 'filled', fn ($query) => $query->whereNotNull('counted_stock'))
            ->when($status === 'empty', fn ($query) => $query->whereNull('counted_stock'))
            ->when($status === 'diff', fn ($query) => $query->whereNotNull('difference')->where('difference', '!=', 0))
            ->join('products', 'products.id', '=', 'stock_opname_items.product_id')
            ->orderBy('products.name')
            ->select('stock_opname_items.*')
            ->get();

        $outlierThreshold = max(1, (int) (StoreSetting::query()->value('stock_opname_outlier_threshold') ?? 10));

        return view('stock-opnames.show', [
            'session' => $stockOpname,
            'items' => $items,
            'q' => $q,
            'fill' => $status,
            'requireManagerApproval' => (bool) (StoreSetting::query()->value('stock_opname_require_manager_approval') ?? false),
            'outlierThreshold' => $outlierThreshold,
            'adjustLogs' => $this->applyBranchScope(CashierAuditLog::query(), $request->user())
                ->with('user:id,name')
                ->whereIn('action', ['stock_opname_posted', 'stock_opname_duplicated'])
                ->where(function ($q) use ($stockOpname) {
                    $q->where('context->stock_opname_id', $stockOpname->id)
                        ->orWhere('context->new_stock_opname_id', $stockOpname->id)
                        ->orWhere('context->source_stock_opname_id', $stockOpname->id);
                })
                ->latest('id')
                ->limit(20)
                ->get(),
        ]);
    }

    public function post(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'open') {
            return back()->withErrors(['items' => 'Sesi sudah diposting/tidak aktif.']);
        }

        $payload = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.id' => ['required', 'integer'],
            'items.*.counted_stock' => ['required', 'integer', 'min:0'],
            'posted_note' => ['nullable', 'string', 'max:1000'],
            'manager_approval_email' => ['nullable', 'email'],
            'manager_approval_password' => ['nullable', 'string'],
        ]);

        $storeSetting = StoreSetting::query()->first();
        if ((bool) ($storeSetting?->stock_opname_require_manager_approval)) {
            $email = trim((string) ($payload['manager_approval_email'] ?? ''));
            $password = (string) ($payload['manager_approval_password'] ?? '');
            $manager = User::query()
                ->where('email', $email)
                ->whereIn('role', ['owner', 'admin'])
                ->first();
            if (! $manager || ! Hash::check($password, (string) $manager->password)) {
                throw ValidationException::withMessages([
                    'manager_approval_email' => 'Approval manager tidak valid untuk posting stock opname.',
                ]);
            }
        }

        $postLockKey = 'stock_opname_post_lock_'.$stockOpname->id;
        if (! cache()->add($postLockKey, 1, now()->addSeconds(20))) {
            return back()->withErrors(['items' => 'Posting sedang diproses. Tunggu beberapa detik lalu coba lagi.']);
        }

        try {
            DB::transaction(function () use ($request, $payload, $stockOpname) {
                $lockedSession = $this->applyBranchScope(StockOpname::query(), $request->user())->lockForUpdate()->findOrFail($stockOpname->id);
                if ($lockedSession->status !== 'open') {
                    throw ValidationException::withMessages([
                        'items' => 'Sesi sudah diposting/tidak aktif.',
                    ]);
                }

            $rows = collect($payload['items'] ?? [])->map(fn ($x) => [
                'id' => (int) data_get($x, 'id', 0),
                'counted_stock' => (int) data_get($x, 'counted_stock', 0),
            ])->filter(fn ($x) => $x['id'] > 0)->values();

            $itemIds = $rows->pluck('id')->unique()->values();
            $itemsToUpdate = StockOpnameItem::query()
                ->where('stock_opname_id', $stockOpname->id)
                ->whereIn('id', $itemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($rows as $row) {
                if (! $itemsToUpdate->has($row['id'])) {
                    continue;
                }
                $opnameItem = $itemsToUpdate->get($row['id']);
                $countedInt = (int) $row['counted_stock'];
                $diff = $countedInt - (int) $opnameItem->system_stock;
                $opnameItem->counted_stock = $countedInt;
                $opnameItem->difference = $diff;
                $opnameItem->save();
            }

            $emptyCount = StockOpnameItem::query()
                ->where('stock_opname_id', $stockOpname->id)
                ->whereNull('counted_stock')
                ->count();
            if ($emptyCount > 0) {
                throw ValidationException::withMessages([
                    'items' => "Masih ada {$emptyCount} item yang belum diisi. Lengkapi dulu sebelum posting.",
                ]);
            }

            $allItems = StockOpnameItem::query()
                ->where('stock_opname_id', $stockOpname->id)
                ->with('product:id,name,sku,stock')
                ->lockForUpdate()
                ->get();

            $adjusted = 0;
            $totalDiff = 0;
            $adjustedRows = [];
            foreach ($allItems as $opnameItem) {
                $diff = (int) ($opnameItem->difference ?? 0);
                if ($diff !== 0 && $opnameItem->product) {
                    $this->applyBranchScope(Product::query(), $request->user())->whereKey($opnameItem->product_id)->update(['stock' => (int) $opnameItem->counted_stock]);
                    $adjusted++;
                    $totalDiff += $diff;
                    $adjustedRows[] = [
                        'item_id' => $opnameItem->id,
                        'product_id' => $opnameItem->product_id,
                        'product_name' => (string) ($opnameItem->product->name ?? '-'),
                        'sku' => (string) ($opnameItem->product->sku ?? '-'),
                        'system_stock' => (int) $opnameItem->system_stock,
                        'counted_stock' => (int) $opnameItem->counted_stock,
                        'difference' => $diff,
                    ];
                }
            }

            $lockedSession->status = 'posted';
            $lockedSession->posted_at = now();
            $lockedSession->posted_note = trim((string) ($payload['posted_note'] ?? ''));
            $lockedSession->adjusted_items = $adjusted;
            $lockedSession->total_difference = $totalDiff;
            $lockedSession->save();

            $this->logAudit($request, 'stock_opname_posted', [
                'stock_opname_id' => $lockedSession->id,
                'code' => $lockedSession->code,
                'adjusted_items' => $adjusted,
                'total_difference' => $totalDiff,
                'adjustments' => $adjustedRows,
            ]);

                if (TelegramNotifier::enabled()) {
                    $text = implode("\n", [
                        'Stock Opname Posted',
                        'Kode: '.$lockedSession->code,
                        'Petugas: '.($request->user()->name ?? '-'),
                        'Adjusted: '.number_format($adjusted, 0, ',', '.'),
                        'Total Selisih: '.number_format($totalDiff, 0, ',', '.'),
                        'Waktu: '.now()->format('d/m/Y H:i:s'),
                    ]);
                    TelegramNotifier::send($text, 'stock_opname_posted');
                }
            });
        } finally {
            cache()->forget($postLockKey);
        }

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', 'Stock opname berhasil diposting.');
    }

    public function duplicate(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'posted') {
            throw ValidationException::withMessages([
                'duplicate' => 'Hanya sesi POSTED yang bisa diduplikasi.',
            ]);
        }

        $openExists = $this->applyBranchScope(StockOpname::query(), $request->user())->where('status', 'open')->exists();
        if ($openExists) {
            throw ValidationException::withMessages([
                'duplicate' => 'Masih ada sesi opname OPEN. Selesaikan dulu sebelum duplikasi.',
            ]);
        }

        $newSession = DB::transaction(function () use ($request, $stockOpname) {
            $sourceItems = $stockOpname->items()
                ->with('product:id,stock,is_active')
                ->get();

            if ($sourceItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'duplicate' => 'Sesi sumber tidak memiliki item untuk diduplikasi.',
                ]);
            }

            $code = 'OPN-'.now()->format('Ymd-His').'-'.strtoupper(substr((string) str()->uuid(), 0, 5));
            $session = StockOpname::query()->create([
                'user_id' => $request->user()->id,
                'branch_id' => ActiveBranchContext::resolveBranchId($request->user()),
                'code' => $code,
                'status' => 'open',
                'note' => trim('Duplikasi dari '.$stockOpname->code),
                'started_at' => now(),
            ]);

            $rows = $sourceItems
                ->filter(fn (StockOpnameItem $item) => (bool) $item->product)
                ->map(fn (StockOpnameItem $item) => [
                    'stock_opname_id' => $session->id,
                    'product_id' => $item->product_id,
                    'system_stock' => (int) $item->product->stock,
                    'counted_stock' => null,
                    'difference' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
                ->values()
                ->all();

            if (! empty($rows)) {
                StockOpnameItem::query()->insert($rows);
            }

            $session->update(['total_items' => count($rows)]);

            $this->logAudit($request, 'stock_opname_duplicated', [
                'source_stock_opname_id' => $stockOpname->id,
                'source_code' => $stockOpname->code,
                'new_stock_opname_id' => $session->id,
                'new_code' => $session->code,
                'total_items' => count($rows),
            ]);

            return $session;
        });

        return redirect()->route('stock-opnames.show', $newSession)->with('success', "Sesi {$stockOpname->code} berhasil diduplikasi ke {$newSession->code}.");
    }

    public function exportCsv(Request $request, StockOpname $stockOpname)
    {
        $onlyDiff = (bool) $request->boolean('only_diff');
        $items = $stockOpname->items()
            ->with('product:id,name,sku')
            ->when($onlyDiff, fn ($q) => $q->whereNotNull('difference')->where('difference', '!=', 0))
            ->orderBy('id')
            ->get();
        $filename = $onlyDiff
            ? 'stock-opname-diff-'.$stockOpname->code.'.csv'
            : 'stock-opname-'.$stockOpname->code.'.csv';

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Produk', 'SKU', 'Stok Sistem', 'Stok Fisik', 'Selisih']);
            foreach ($items as $item) {
                fputcsv($out, [
                    (string) ($item->product?->name ?? '-'),
                    (string) ($item->product?->sku ?? '-'),
                    (int) $item->system_stock,
                    $item->counted_stock !== null ? (int) $item->counted_stock : '',
                    $item->difference !== null ? (int) $item->difference : '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(Request $request, StockOpname $stockOpname)
    {
        $onlyDiff = (bool) $request->boolean('only_diff');
        $stockOpname->load(['items.product:id,name,sku', 'user:id,name']);
        $items = $onlyDiff
            ? $stockOpname->items->filter(fn ($x) => (int) ($x->difference ?? 0) !== 0)->values()
            : $stockOpname->items;
        $increasedCount = $items->filter(fn ($x) => (int) ($x->difference ?? 0) > 0)->count();
        $decreasedCount = $items->filter(fn ($x) => (int) ($x->difference ?? 0) < 0)->count();
        $equalCount = $items->filter(fn ($x) => (int) ($x->difference ?? 0) === 0)->count();
        $netDifference = (int) $items->sum(fn ($x) => (int) ($x->difference ?? 0));

        $topDiffItems = $items
            ->map(function ($x) {
                return [
                    'name' => (string) ($x->product?->name ?? '-'),
                    'sku' => (string) ($x->product?->sku ?? '-'),
                    'difference' => (int) ($x->difference ?? 0),
                ];
            })
            ->sortByDesc(fn ($x) => abs((int) $x['difference']))
            ->take(5)
            ->values();

        $pdf = Pdf::loadView('pdf.stock-opname', [
            'session' => $stockOpname,
            'items' => $items,
            'summary' => [
                'increased_count' => $increasedCount,
                'decreased_count' => $decreasedCount,
                'equal_count' => $equalCount,
                'net_difference' => $netDifference,
                'top_diff_items' => $topDiffItems,
                'only_diff' => $onlyDiff,
            ],
        ])->setPaper('a4', 'portrait');

        $filename = $onlyDiff
            ? 'stock-opname-diff-'.$stockOpname->code.'.pdf'
            : 'stock-opname-'.$stockOpname->code.'.pdf';
        return $pdf->stream($filename);
    }

    public function templateCsv(StockOpname $stockOpname)
    {
        $items = $stockOpname->items()->with('product:id,name,sku')->orderBy('id')->get();
        $filename = 'template-stock-opname-'.$stockOpname->code.'.csv';

        return response()->streamDownload(function () use ($items): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['item_id', 'sku', 'product_name', 'system_stock', 'counted_stock']);
            foreach ($items as $item) {
                fputcsv($out, [
                    (int) $item->id,
                    (string) ($item->product?->sku ?? ''),
                    (string) ($item->product?->name ?? ''),
                    (int) $item->system_stock,
                    '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function importCsv(Request $request, StockOpname $stockOpname)
    {
        if ($stockOpname->status !== 'open') {
            throw ValidationException::withMessages([
                'csv_file' => 'Import CSV hanya bisa untuk sesi OPEN.',
            ]);
        }

        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'preview_only' => ['nullable', 'boolean'],
        ]);
        $previewOnly = (bool) ($validated['preview_only'] ?? false);

        $path = $validated['csv_file']->getRealPath();
        if (! $path) {
            throw ValidationException::withMessages(['csv_file' => 'File CSV tidak valid.']);
        }

        $handle = fopen($path, 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['csv_file' => 'Gagal membaca file CSV.']);
        }

        $header = fgetcsv($handle);
        if (! is_array($header)) {
            fclose($handle);
            throw ValidationException::withMessages(['csv_file' => 'Header CSV tidak ditemukan.']);
        }

        $normalized = array_map(fn ($x) => strtolower(trim((string) $x)), $header);
        $idxItemId = array_search('item_id', $normalized, true);
        $idxSku = array_search('sku', $normalized, true);
        $idxCounted = array_search('counted_stock', $normalized, true);
        if ($idxCounted === false || ($idxItemId === false && $idxSku === false)) {
            fclose($handle);
            throw ValidationException::withMessages(['csv_file' => 'Header wajib: counted_stock + (item_id atau sku).']);
        }

        $result = DB::transaction(function () use ($request, $stockOpname, $handle, $idxItemId, $idxSku, $idxCounted, $previewOnly): array {
            $items = $stockOpname->items()->with('product:id,sku')->get();
            $byId = $items->keyBy('id');
            $bySku = $items->filter(fn ($x) => filled($x->product?->sku))->keyBy(fn ($x) => strtolower((string) $x->product?->sku));
            $updated = 0;
            $rowsRead = 0;
            $invalidCounted = 0;
            $unmatched = 0;
            $previewChanges = [];

            while (($row = fgetcsv($handle)) !== false) {
                if (! is_array($row) || count($row) === 0) {
                    continue;
                }
                $rowsRead++;
                $countedRaw = trim((string) ($row[$idxCounted] ?? ''));
                if ($countedRaw === '' || ! is_numeric($countedRaw)) {
                    $invalidCounted++;
                    continue;
                }
                $counted = max((int) $countedRaw, 0);

                $target = null;
                if ($idxItemId !== false) {
                    $idRaw = trim((string) ($row[$idxItemId] ?? ''));
                    if ($idRaw !== '' && ctype_digit($idRaw)) {
                        $target = $byId->get((int) $idRaw);
                    }
                }
                if (! $target && $idxSku !== false) {
                    $sku = strtolower(trim((string) ($row[$idxSku] ?? '')));
                    if ($sku !== '') {
                        $target = $bySku->get($sku);
                    }
                }
                if (! $target) {
                    $unmatched++;
                    continue;
                }

                $updated++;
                if (count($previewChanges) < 15) {
                    $previewChanges[] = [
                        'item_id' => (int) $target->id,
                        'sku' => (string) ($target->product?->sku ?? ''),
                        'system_stock' => (int) $target->system_stock,
                        'counted_stock' => $counted,
                        'difference' => $counted - (int) $target->system_stock,
                    ];
                }
                if (! $previewOnly) {
                    $target->counted_stock = $counted;
                    $target->difference = $counted - (int) $target->system_stock;
                    $target->save();
                }
            }

            fclose($handle);

            if (! $previewOnly) {
                $this->logAudit($request, 'stock_opname_csv_imported', [
                    'stock_opname_id' => $stockOpname->id,
                    'code' => $stockOpname->code,
                    'updated_rows' => $updated,
                ]);
            }

            return [
                'rows_read' => $rowsRead,
                'valid_rows' => $updated,
                'invalid_counted' => $invalidCounted,
                'unmatched_rows' => $unmatched,
                'preview_changes' => $previewChanges,
            ];
        });

        if ($previewOnly) {
            return redirect()
                ->route('stock-opnames.show', $stockOpname)
                ->with('stock_opname_import_preview', $result)
                ->with('success', 'Preview CSV selesai. Periksa hasil lalu terapkan import.');
        }

        return redirect()->route('stock-opnames.show', $stockOpname)->with('success', 'Import CSV opname berhasil diproses.');
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
}
