<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierPurchase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SuppliersSummaryCommand extends Command
{
    protected $signature = 'suppliers:summary
        {--branch= : Filter branch by code (contoh: PUSAT)}
        {--limit=10 : Jumlah baris purchase terbaru yang ditampilkan}';

    protected $description = 'Ringkasan supplier, hutang supplier, dan purchase terbaru.';

    public function handle(): int
    {
        $branchCode = strtoupper(trim((string) $this->option('branch')));
        $limit = max(1, (int) $this->option('limit'));

        $branch = null;
        if ($branchCode !== '') {
            $branch = Branch::query()->where('code', $branchCode)->first();
            if (! $branch) {
                $this->error("Branch dengan code '{$branchCode}' tidak ditemukan.");
                return self::FAILURE;
            }
        }

        $suppliersQuery = Supplier::query();
        $purchaseQuery = SupplierPurchase::query();
        if ($branch) {
            $suppliersQuery->where('branch_id', $branch->id);
            $purchaseQuery->where('branch_id', $branch->id);
        }

        $supplierCount = (int) (clone $suppliersQuery)->count();
        $activeSupplierCount = (int) (clone $suppliersQuery)->where('is_active', true)->count();

        $outstanding = (float) (clone $purchaseQuery)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->sum('remaining_amount');
        $dueThisWeek = (float) (clone $purchaseQuery)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->sum('remaining_amount');
        $overdue = (float) (clone $purchaseQuery)
            ->whereIn('payment_status', ['unpaid', 'partial', 'overdue'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->sum('remaining_amount');

        $this->info('=== Supplier Summary ===');
        $this->line('Scope: '.($branch ? "{$branch->name} ({$branch->code})" : 'ALL BRANCH'));
        $this->newLine();
        $this->table(['Metric', 'Value'], [
            ['Suppliers Total', number_format($supplierCount, 0, ',', '.')],
            ['Suppliers Aktif', number_format($activeSupplierCount, 0, ',', '.')],
            ['Outstanding Hutang', $this->idr($outstanding)],
            ['Jatuh Tempo 7 Hari', $this->idr($dueThisWeek)],
            ['Overdue', $this->idr($overdue)],
        ]);

        $statusRows = (clone $purchaseQuery)
            ->select('payment_status', DB::raw('COUNT(*) as total'), DB::raw('COALESCE(SUM(remaining_amount),0) as sisa'))
            ->groupBy('payment_status')
            ->orderBy('payment_status')
            ->get();

        $this->newLine();
        $this->info('Status Hutang Purchase');
        $this->table(
            ['Status', 'Jumlah Purchase', 'Total Sisa'],
            $statusRows->map(fn ($row) => [
                strtoupper((string) $row->payment_status),
                number_format((int) $row->total, 0, ',', '.'),
                $this->idr((float) $row->sisa),
            ])->all()
        );

        $latestPurchases = (clone $purchaseQuery)
            ->with('supplier:id,name,code')
            ->latest('id')
            ->limit($limit)
            ->get();

        $this->newLine();
        $this->info("Purchase Terbaru (limit {$limit})");
        $this->table(
            ['Nomor', 'Supplier', 'Status Barang', 'Status Bayar', 'Total', 'Terbayar', 'Sisa', 'Due Date'],
            $latestPurchases->map(fn ($p) => [
                (string) $p->number,
                (string) ($p->supplier?->code ?? '-'),
                strtoupper((string) $p->status),
                strtoupper((string) $p->payment_status),
                $this->idr((float) $p->total_amount),
                $this->idr((float) $p->paid_amount),
                $this->idr((float) $p->remaining_amount),
                optional($p->due_date)->format('Y-m-d') ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }

    private function idr(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
