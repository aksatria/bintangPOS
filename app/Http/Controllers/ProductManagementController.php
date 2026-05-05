<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StoreSetting;
use App\Enums\SaleStatus;
use App\Support\ActiveBranchContext;
use App\Support\AppliesBranchScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductManagementController extends Controller
{
    use AppliesBranchScope;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = (int) $request->query('category_id', 0);
        $status = trim((string) $request->query('status', 'all'));

        $products = $this->applyBranchScope(Product::query(), $request->user())
            ->with('category:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $terms = preg_split('/\s+/', mb_strtolower($q), -1, PREG_SPLIT_NO_EMPTY) ?: [];

                if (count($terms) === 0) {
                    return;
                }

                foreach ($terms as $term) {
                    $query->where(function ($subQuery) use ($term) {
                        $wildcard = "%{$term}%";
                        $subQuery
                            ->whereRaw('LOWER(name) LIKE ?', [$wildcard])
                            ->orWhereRaw('LOWER(sku) LIKE ?', [$wildcard])
                            ->orWhereRaw('LOWER(barcode) LIKE ?', [$wildcard])
                            ->orWhereRaw('LOWER(unit) LIKE ?', [$wildcard])
                            ->orWhereHas('category', fn ($cat) => $cat->whereRaw('LOWER(name) LIKE ?', [$wildcard]));
                    });
                }

                $firstTerm = $terms[0];
                $prefix = $firstTerm . '%';
                $contains = '%' . $firstTerm . '%';
                $query->orderByRaw(
                    "CASE
                        WHEN LOWER(name) LIKE ? THEN 0
                        WHEN LOWER(sku) LIKE ? THEN 1
                        WHEN LOWER(barcode) LIKE ? THEN 2
                        WHEN LOWER(name) LIKE ? THEN 3
                        ELSE 4
                    END",
                    [$prefix, $prefix, $prefix, $contains]
                );
            })
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $setting = StoreSetting::query()->first();
        $rules = is_array($setting?->approval_rules) ? $setting->approval_rules : [];
        $lookbackDays = max(7, (int) data_get($rules, 'reorder_lookback_days', 30));
        $leadDays = max(1, (int) data_get($rules, 'reorder_lead_days', 7));
        $safetyDays = max(0, (int) data_get($rules, 'reorder_safety_days', 3));

        $start = now()->subDays($lookbackDays)->startOfDay();
        $end = now()->endOfDay();

        $salesIds = $this->applyBranchScope(Sale::query(), $request->user())
            ->where('status', SaleStatus::Paid->value)
            ->whereBetween('sold_at', [$start, $end])
            ->pluck('id');

        $dailySoldByProduct = collect();
        if ($salesIds->isNotEmpty()) {
            $dailySoldByProduct = SaleItem::query()
                ->whereIn('sale_id', $salesIds->all())
                ->selectRaw('product_id, COALESCE(SUM(quantity),0) as qty')
                ->whereNotNull('product_id')
                ->groupBy('product_id')
                ->pluck('qty', 'product_id');
        }

        $reorderSuggestions = $this->applyBranchScope(Product::query(), $request->user())
            ->where('is_active', true)
            ->get(['id', 'name', 'sku', 'stock', 'low_stock_threshold'])
            ->map(function (Product $product) use ($dailySoldByProduct, $lookbackDays, $leadDays, $safetyDays) {
                $sold = (float) ($dailySoldByProduct[(int) $product->id] ?? 0);
                $dailyAvg = $lookbackDays > 0 ? ($sold / $lookbackDays) : 0;
                $demandWindow = $leadDays + $safetyDays;
                $targetStock = (int) ceil(max((int) $product->low_stock_threshold, $dailyAvg * $demandWindow));
                $suggestedQty = max($targetStock - (int) $product->stock, 0);

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'sku' => (string) $product->sku,
                    'stock' => (int) $product->stock,
                    'target_stock' => $targetStock,
                    'daily_avg' => round($dailyAvg, 2),
                    'suggested_qty' => $suggestedQty,
                ];
            })
            ->filter(fn ($row) => (int) $row['suggested_qty'] > 0)
            ->sortByDesc('suggested_qty')
            ->take(12)
            ->values();

        return view('admin.products', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'reorderSuggestions' => $reorderSuggestions,
            'reorderWindow' => [
                'lookback_days' => $lookbackDays,
                'lead_days' => $leadDays,
                'safety_days' => $safetyDays,
            ],
            'filters' => [
                'q' => $q,
                'category_id' => $categoryId,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request, false);
        $payload = $this->normalizePayload($data);

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('products', 'public');
        }
        $payload['branch_id'] = ActiveBranchContext::resolveBranchId($request->user());

        $product = Product::query()->create($payload);
        $product->load('category:id,name');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Produk berhasil ditambahkan.',
                'product' => $this->productResponseData($product),
            ]);
        }

        return back()->with('status', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product)
    {
        if ($request->boolean('toggle_only') || $request->header('X-Product-Toggle') === '1') {
            $validated = $request->validate([
                'is_active' => ['required', 'boolean'],
            ]);

            $product->update([
                'is_active' => (bool) $validated['is_active'],
            ]);
            $product->load('category:id,name');

            if (!($request->expectsJson() || $request->ajax())) {
                return back()->with('status', 'Status produk berhasil diperbarui.');
            }

            return response()->json([
                'message' => 'Status produk berhasil diperbarui.',
                'product' => $this->productResponseData($product),
            ]);
        }
        if ($request->boolean('inline_only') || $request->header('X-Product-Inline') === '1') {
            $validated = $request->validate([
                'selling_price' => ['required', 'numeric', 'min:0'],
                'stock' => ['required', 'integer', 'min:0'],
            ]);

            $product->update([
                'selling_price' => (float) $validated['selling_price'],
                'stock' => (int) $validated['stock'],
            ]);
            $product->load('category:id,name');

            if (!($request->expectsJson() || $request->ajax())) {
                return back()->with('status', 'Harga jual dan stok berhasil diperbarui.');
            }

            return response()->json([
                'message' => 'Harga jual dan stok berhasil diperbarui.',
                'product' => $this->productResponseData($product),
            ]);
        }

        $data = $this->validatePayload($request, true);
        $payload = $this->normalizePayload($data);

        if ($request->hasFile('image')) {
            if (! empty($product->image)) {
                Storage::disk('public')->delete((string) $product->image);
            }
            $payload['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($payload);
        $product->load('category:id,name');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Produk berhasil diperbarui.',
                'product' => $this->productResponseData($product),
            ]);
        }

        return back()->with('status', 'Produk berhasil diperbarui.');
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
        ]);

        $productIds = array_values(array_unique(array_map('intval', $validated['product_ids'])));
        $products = $this->applyBranchScope(Product::query(), $request->user())->whereIn('id', $productIds)->get();
        $requestedCount = count($productIds);
        $affectedCount = 0;

        if ($validated['action'] === 'activate') {
            $affectedCount = $this->applyBranchScope(Product::query(), $request->user())->whereIn('id', $productIds)->update(['is_active' => true]);
        } elseif ($validated['action'] === 'deactivate') {
            $affectedCount = $this->applyBranchScope(Product::query(), $request->user())->whereIn('id', $productIds)->update(['is_active' => false]);
        } else {
            foreach ($products as $product) {
                if ($product->saleItems()->exists()) {
                    continue;
                }
                if (! empty($product->image)) {
                    Storage::disk('public')->delete((string) $product->image);
                }
                $product->delete();
                $affectedCount++;
            }
        }

        $message = match ($validated['action']) {
            'activate' => "Berhasil mengaktifkan {$affectedCount} dari {$requestedCount} produk.",
            'deactivate' => "Berhasil menonaktifkan {$affectedCount} dari {$requestedCount} produk.",
            default => "Berhasil menghapus {$affectedCount} dari {$requestedCount} produk.",
        };

        return response()->json([
            'message' => $message,
            'affected' => $affectedCount,
            'requested' => $requestedCount,
        ]);
    }

    public function destroy(Product $product)
    {
        if ($product->saleItems()->exists()) {
            return back()->withErrors(['product' => 'Produk tidak bisa dihapus karena sudah dipakai transaksi.']);
        }

        if (! empty($product->image)) {
            Storage::disk('public')->delete((string) $product->image);
        }
        $product->delete();

        return back()->with('status', 'Produk berhasil dihapus.');
    }

    private function validatePayload(Request $request, bool $isUpdate): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['required', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:40'],
            'image' => [$isUpdate ? 'nullable' : 'nullable', 'image', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function normalizePayload(array $data): array
    {
        return [
            'category_id' => (int) $data['category_id'],
            'name' => trim((string) $data['name']),
            'sku' => trim((string) $data['sku']),
            'barcode' => trim((string) ($data['barcode'] ?? '')),
            'purchase_price' => (float) $data['purchase_price'],
            'selling_price' => (float) $data['selling_price'],
            'stock' => (int) $data['stock'],
            'low_stock_threshold' => (int) $data['low_stock_threshold'],
            'unit' => trim((string) $data['unit']),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function productResponseData(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'name' => (string) $product->name,
            'sku' => (string) $product->sku,
            'barcode' => (string) ($product->barcode ?: '-'),
            'unit' => (string) $product->unit,
            'category_name' => (string) ($product->category?->name ?: '-'),
            'purchase_price' => (float) $product->purchase_price,
            'selling_price' => (float) $product->selling_price,
            'stock' => (int) $product->stock,
            'low_stock_threshold' => (int) $product->low_stock_threshold,
            'is_active' => (bool) $product->is_active,
            'is_low' => (int) $product->stock <= (int) $product->low_stock_threshold,
            'image_url' => ! empty($product->image) ? asset('storage/' . ltrim((string) $product->image, '/')) : null,
        ];
    }
}
