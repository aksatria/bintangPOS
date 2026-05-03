<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Enums\SaleStatus;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;
use App\Models\Branch;
use App\Models\User;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('data:ensure-real', function () {
    $this->info('Memeriksa data utama...');

    if (StoreSetting::query()->count() === 0) {
        StoreSetting::query()->create([
            'name' => config('app.name', 'BINTANG'),
            'address' => 'Alamat toko belum diatur',
            'whatsapp' => null,
            'receipt_footer' => 'Terima kasih sudah berbelanja.',
        ]);
        $this->line('- Store setting dibuat.');
    } else {
        $this->line('- Store setting sudah ada, dilewati.');
    }

    $categoryPayload = [
        ['name' => 'Makanan Ringan', 'description' => 'Snack, biskuit, keripik.'],
        ['name' => 'Minuman', 'description' => 'Air mineral, teh, kopi, minuman kemasan.'],
        ['name' => 'Sembako', 'description' => 'Kebutuhan pokok harian.'],
        ['name' => 'Bumbu & Dapur', 'description' => 'Bumbu masak dan pelengkap dapur.'],
        ['name' => 'Perawatan Rumah', 'description' => 'Sabun cuci, pembersih, pewangi.'],
    ];

    if (Category::query()->count() === 0) {
        foreach ($categoryPayload as $category) {
            Category::query()->create([
                'name' => $category['name'],
                'description' => $category['description'],
                'is_active' => true,
            ]);
        }
        $this->line('- Kategori dibuat: '.count($categoryPayload));
    } else {
        $this->line('- Kategori sudah ada, dilewati.');
    }

    if (Product::query()->count() === 0) {
        $categoryByName = Category::query()->pluck('id', 'name');
        $productPayload = [
            ['Makanan Ringan', 'Biskuit Roma Kelapa 300g', 'MRK-0001', '8991001000001', 9500, 12000, 45, 8, 'pack'],
            ['Makanan Ringan', 'Keripik Singkong Balado 150g', 'MRK-0002', '8991001000002', 7200, 9500, 38, 7, 'pack'],
            ['Makanan Ringan', 'Wafer Cokelat 120g', 'MRK-0003', '8991001000003', 6800, 9000, 52, 10, 'pack'],
            ['Makanan Ringan', 'Kacang Panggang Asin 200g', 'MRK-0004', '8991001000004', 8700, 11000, 34, 6, 'pack'],
            ['Makanan Ringan', 'Permen Mint 125g', 'MRK-0005', '8991001000005', 4200, 6000, 60, 12, 'pack'],
            ['Minuman', 'Air Mineral 600ml', 'MNM-0001', '8992002000001', 2500, 3500, 120, 20, 'botol'],
            ['Minuman', 'Teh Botol 450ml', 'MNM-0002', '8992002000002', 3800, 5000, 90, 15, 'botol'],
            ['Minuman', 'Kopi Susu RTD 220ml', 'MNM-0003', '8992002000003', 5200, 7000, 65, 10, 'botol'],
            ['Minuman', 'Susu UHT Cokelat 200ml', 'MNM-0004', '8992002000004', 4600, 6500, 70, 12, 'kotak'],
            ['Minuman', 'Jus Jeruk 250ml', 'MNM-0005', '8992002000005', 5000, 7000, 55, 10, 'kotak'],
            ['Sembako', 'Beras Premium 5kg', 'SMB-0001', '8993003000001', 68000, 76000, 22, 5, 'karung'],
            ['Sembako', 'Gula Pasir 1kg', 'SMB-0002', '8993003000002', 14500, 17000, 50, 10, 'pack'],
            ['Sembako', 'Minyak Goreng 1L', 'SMB-0003', '8993003000003', 16200, 19000, 48, 10, 'pouch'],
            ['Sembako', 'Tepung Terigu 1kg', 'SMB-0004', '8993003000004', 9800, 12000, 44, 8, 'pack'],
            ['Sembako', 'Garam Halus 500g', 'SMB-0005', '8993003000005', 3800, 5000, 64, 12, 'pack'],
            ['Bumbu & Dapur', 'Kecap Manis 600ml', 'BMD-0001', '8994004000001', 14500, 18000, 36, 7, 'botol'],
            ['Bumbu & Dapur', 'Saus Sambal 340ml', 'BMD-0002', '8994004000002', 9800, 12500, 40, 8, 'botol'],
            ['Bumbu & Dapur', 'Kaldu Bubuk 100g', 'BMD-0003', '8994004000003', 5200, 7000, 58, 10, 'pack'],
            ['Bumbu & Dapur', 'Merica Bubuk 45g', 'BMD-0004', '8994004000004', 6700, 9000, 32, 6, 'sachet'],
            ['Bumbu & Dapur', 'Bawang Goreng 100g', 'BMD-0005', '8994004000005', 11800, 14500, 26, 5, 'pack'],
            ['Perawatan Rumah', 'Sabun Cuci Piring 800ml', 'PRM-0001', '8995005000001', 11800, 15000, 28, 6, 'botol'],
            ['Perawatan Rumah', 'Deterjen Bubuk 900g', 'PRM-0002', '8995005000002', 15500, 19000, 30, 6, 'pack'],
            ['Perawatan Rumah', 'Pembersih Lantai 780ml', 'PRM-0003', '8995005000003', 12800, 16000, 24, 5, 'botol'],
            ['Perawatan Rumah', 'Pemutih Pakaian 1L', 'PRM-0004', '8995005000004', 8900, 11500, 25, 5, 'botol'],
            ['Perawatan Rumah', 'Tisu Gulung 10s', 'PRM-0005', '8995005000005', 18500, 22500, 20, 4, 'pack'],
        ];

        $created = 0;
        foreach ($productPayload as [$categoryName, $name, $sku, $barcode, $hpp, $sell, $stock, $threshold, $unit]) {
            $categoryId = $categoryByName[$categoryName] ?? null;
            if (! $categoryId) {
                continue;
            }

            Product::query()->create([
                'category_id' => $categoryId,
                'name' => $name,
                'sku' => $sku,
                'barcode' => $barcode,
                'purchase_price' => $hpp,
                'selling_price' => $sell,
                'stock' => $stock,
                'low_stock_threshold' => $threshold,
                'unit' => $unit,
                'is_active' => true,
            ]);

            $created++;
        }

        $this->line('- Produk dibuat: '.$created);
    } else {
        $this->line('- Produk sudah ada, dilewati.');
    }

    $this->newLine();
    $this->info('Selesai. Ringkasan data saat ini:');
    $this->line('  * Store setting: '.StoreSetting::query()->count());
    $this->line('  * Kategori: '.Category::query()->count());
    $this->line('  * Produk: '.Product::query()->count());
})->purpose('Pastikan data operasional nyata tersedia jika tabel masih kosong (tanpa dummy random).');

Artisan::command('products:export-seeder {name=ProductsFromCurrentDataSeeder}', function (string $name) {
    $className = preg_replace('/[^A-Za-z0-9_]/', '', $name) ?: 'ProductsFromCurrentDataSeeder';
    $targetPath = database_path('seeders/'.$className.'.php');

    $categories = Category::query()
        ->orderBy('name')
        ->get(['name', 'description', 'is_active'])
        ->map(fn ($c) => [
            'name' => $c->name,
            'description' => $c->description,
            'is_active' => (bool) $c->is_active,
        ])
        ->values()
        ->all();

    $products = Product::query()
        ->with('category:id,name')
        ->orderBy('sku')
        ->get()
        ->map(fn ($p) => [
            'category_name' => $p->category?->name,
            'name' => $p->name,
            'sku' => $p->sku,
            'barcode' => $p->barcode,
            'purchase_price' => (float) $p->purchase_price,
            'selling_price' => (float) $p->selling_price,
            'stock' => (int) $p->stock,
            'low_stock_threshold' => (int) $p->low_stock_threshold,
            'unit' => $p->unit,
            'is_active' => (bool) $p->is_active,
        ])
        ->filter(fn ($p) => ! empty($p['category_name']))
        ->values()
        ->all();

    $categoriesExport = var_export($categories, true);
    $productsExport = var_export($products, true);

    $content = <<<PHP
<?php

namespace Database\\Seeders;

use App\\Models\\Category;
use App\\Models\\Product;
use Illuminate\\Database\\Seeder;

class {$className} extends Seeder
{
    public function run(): void
    {
        \$categories = {$categoriesExport};

        foreach (\$categories as \$category) {
            Category::query()->updateOrCreate(
                ['name' => \$category['name']],
                [
                    'description' => \$category['description'],
                    'is_active' => \$category['is_active'],
                ]
            );
        }

        \$categoryByName = Category::query()->pluck('id', 'name');
        \$products = {$productsExport};

        foreach (\$products as \$product) {
            \$categoryId = \$categoryByName[\$product['category_name']] ?? null;
            if (! \$categoryId) {
                continue;
            }

            Product::query()->updateOrCreate(
                ['sku' => \$product['sku']],
                [
                    'category_id' => \$categoryId,
                    'name' => \$product['name'],
                    'barcode' => \$product['barcode'],
                    'purchase_price' => \$product['purchase_price'],
                    'selling_price' => \$product['selling_price'],
                    'stock' => \$product['stock'],
                    'low_stock_threshold' => \$product['low_stock_threshold'],
                    'unit' => \$product['unit'],
                    'is_active' => \$product['is_active'],
                ]
            );
        }
    }
}
PHP;

    File::put($targetPath, $content);

    $this->info("Seeder berhasil dibuat: {$targetPath}");
    $this->line('Jumlah kategori: '.count($categories));
    $this->line('Jumlah produk: '.count($products));
})->purpose('Generate seeder dari data produk yang saat ini ada di database.');

Schedule::command('db:backup-safe --prune-days=14')->dailyAt('01:40');
Schedule::command('logs:prune-cashier-audit --days=180')->dailyAt('02:30');
Schedule::command('audit:seal --limit=1000')->dailyAt('02:40');
Schedule::command('telegram:send-daily-summary')->everyMinute();
Schedule::command('telegram:send-pending-overdue-reminder')->dailyAt('09:00');
Schedule::command('telegram:send-followup-reminders')->everyMinute();
Schedule::command('rbac:notify-unhealthy')->dailyAt('03:00');
Schedule::command('rbac:expire-temp-grants')->everyThirtyMinutes();
Schedule::command('rbac:monthly-review-reminder')->monthlyOn(1, '07:30');
Schedule::command('approval:send-sla-escalation')->everyTenMinutes();
Schedule::command('approval:send-sla-anomaly-alert')->everyTenMinutes();
Schedule::command('approval:auto-expire')->everyFifteenMinutes();
Schedule::command('telegram:send-weekly-sla-report')->weeklyOn(1, '08:00');
Schedule::command('ops:health-check --telegram')->everyTenMinutes();
Schedule::command('ops:scheduler-heartbeat')->everyMinute();
Schedule::command('ops:drill-disaster-recovery --staging')->monthlyOn(1, '04:30');
Schedule::command('ops:recovery-pack --staging --telegram')->monthlyOn(1, '05:00');
Schedule::command('stock-transfer:send-aging-alert')->hourly();

Artisan::command('data:diversify-payment-methods {--limit=0}', function () {
    $limit = max(0, (int) $this->option('limit'));
    $methods = ['cash', 'qris', 'debit', 'transfer', 'e_wallet', 'mixed'];

    $query = Sale::query()
        ->where('status', SaleStatus::Paid->value)
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $sales = $query->get();

    if ($sales->isEmpty()) {
        $this->warn('Tidak ada transaksi paid yang bisa diperbarui.');
        return;
    }

    $updated = 0;
    foreach ($sales as $idx => $sale) {
        $method = $methods[$idx % count($methods)];
        $paidAmount = (float) ($sale->paid_amount ?: $sale->total_amount);
        $breakdown = null;

        if ($method === 'mixed') {
            $cashPart = (int) round($paidAmount * 0.4);
            $qrisPart = (int) round($paidAmount - $cashPart);
            $breakdown = [
                ['method' => 'cash', 'amount' => max(0, $cashPart)],
                ['method' => 'qris', 'amount' => max(0, $qrisPart)],
            ];
        }

        $sale->payment_method = $method;
        $sale->payment_breakdown = $breakdown;
        $sale->save();
        $updated++;
    }

    $this->info("Selesai. {$updated} transaksi paid diperbarui.");
    $summary = Sale::query()
        ->where('status', SaleStatus::Paid->value)
        ->selectRaw("COALESCE(payment_method,'cash') as method, COUNT(*) as total")
        ->groupBy('method')
        ->orderBy('total', 'desc')
        ->get();

    foreach ($summary as $row) {
        $this->line('- '.strtoupper((string) $row->method).': '.(int) $row->total);
    }
})->purpose('Membuat variasi metode pembayaran transaksi paid agar laporan tidak hanya cash.');

Artisan::command('demo:seed-stock-transfers
    {--count=10 : Jumlah transfer yang dibuat}
    {--source=PUSAT : Kode cabang sumber}
    {--destination=SBY : Kode cabang tujuan}
    {--reset : Hapus transfer demo lama untuk rute yang sama sebelum generate}', function () {
    $count = max(1, (int) $this->option('count'));
    $sourceCode = strtoupper(trim((string) $this->option('source')));
    $destinationCode = strtoupper(trim((string) $this->option('destination')));

    if ($sourceCode === $destinationCode) {
        $this->error('Source dan destination tidak boleh sama.');
        return;
    }

    $source = Branch::query()->firstOrCreate(
        ['code' => $sourceCode],
        ['name' => ucfirst(strtolower($sourceCode)), 'is_active' => true]
    );
    $destination = Branch::query()->firstOrCreate(
        ['code' => $destinationCode],
        ['name' => ucfirst(strtolower($destinationCode)), 'is_active' => true]
    );

    if ((bool) $this->option('reset')) {
        $prefix = "TRF-DEMO-{$sourceCode}-{$destinationCode}-%";
        $transferIds = StockTransfer::query()
            ->where('code', 'like', $prefix)
            ->pluck('id');

        if ($transferIds->isNotEmpty()) {
            StockTransferItem::query()->whereIn('stock_transfer_id', $transferIds)->delete();
            StockTransfer::query()->whereIn('id', $transferIds)->delete();
            $this->warn("Data lama dibersihkan: {$transferIds->count()} transfer.");
        } else {
            $this->line('Tidak ada data demo lama yang perlu dibersihkan.');
        }
    }

    $requester = User::query()->updateOrCreate(
        ['email' => 'admin.' . strtolower($sourceCode) . '.demo@local.test'],
        [
            'name' => 'Admin ' . $sourceCode . ' Demo',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => $source->id,
        ]
    );
    $receiver = User::query()->updateOrCreate(
        ['email' => 'admin.' . strtolower($destinationCode) . '.demo@local.test'],
        [
            'name' => 'Admin ' . $destinationCode . ' Demo',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'branch_id' => $destination->id,
        ]
    );

    $products = Product::query()
        ->where('branch_id', $source->id)
        ->where('is_active', true)
        ->orderBy('name')
        ->limit(30)
        ->get(['id', 'sku', 'name', 'unit']);

    if ($products->count() < 3) {
        $this->error('Produk cabang sumber kurang dari 3. Tambah produk dulu di cabang sumber.');
        return;
    }

    $statuses = [
        StockTransfer::STATUS_RECEIVED,
        StockTransfer::STATUS_RECEIVED,
        StockTransfer::STATUS_APPROVED,
        StockTransfer::STATUS_REQUESTED,
        StockTransfer::STATUS_REJECTED,
        StockTransfer::STATUS_CANCELLED,
    ];

    $created = 0;
    for ($i = 1; $i <= $count; $i++) {
        $status = $statuses[($i - 1) % count($statuses)];
        $seq = str_pad((string) $i, 3, '0', STR_PAD_LEFT);
        $code = "TRF-DEMO-{$sourceCode}-{$destinationCode}-{$seq}";

        $transfer = StockTransfer::query()->updateOrCreate(
            ['code' => $code],
            [
                'source_branch_id' => $source->id,
                'destination_branch_id' => $destination->id,
                'requested_by' => $requester->id,
                'approved_by' => in_array($status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_RECEIVED, StockTransfer::STATUS_REJECTED], true) ? $receiver->id : null,
                'received_by' => $status === StockTransfer::STATUS_RECEIVED ? $receiver->id : null,
                'status' => $status,
                'note' => "Demo transfer {$seq}",
                'delivery_ref' => "SJ-{$sourceCode}-{$destinationCode}-{$seq}",
                'courier_name' => 'Kurir Internal',
                'reject_reason' => $status === StockTransfer::STATUS_REJECTED ? 'Stok tujuan masih aman.' : null,
                'approved_at' => in_array($status, [StockTransfer::STATUS_APPROVED, StockTransfer::STATUS_RECEIVED, StockTransfer::STATUS_REJECTED], true) ? now()->subDays($i) : null,
                'received_at' => $status === StockTransfer::STATUS_RECEIVED ? now()->subDays($i)->addHours(2) : null,
            ]
        );

        StockTransferItem::query()->where('stock_transfer_id', $transfer->id)->delete();
        foreach ($products->shuffle()->take(3) as $p) {
            $qty = random_int(5, 50);
            StockTransferItem::query()->create([
                'stock_transfer_id' => $transfer->id,
                'source_product_id' => $p->id,
                'sku' => (string) $p->sku,
                'product_name' => (string) $p->name,
                'unit' => (string) $p->unit,
                'requested_qty' => $qty,
                'received_qty' => $status === StockTransfer::STATUS_RECEIVED ? $qty : null,
            ]);
        }
        $created++;
    }

    $this->info("Selesai generate {$created} transfer demo {$sourceCode} -> {$destinationCode}.");
    $summary = StockTransfer::query()
        ->where('code', 'like', "TRF-DEMO-{$sourceCode}-{$destinationCode}-%")
        ->selectRaw('status, COUNT(*) as total')
        ->groupBy('status')
        ->orderByDesc('total')
        ->get();
    foreach ($summary as $row) {
        $this->line('- ' . strtoupper((string) $row->status) . ': ' . (int) $row->total);
    }
})->purpose('Generate batch transfer demo antar cabang dengan parameter dinamis.');
