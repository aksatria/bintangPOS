<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Enums\SaleStatus;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected string $q = '',
        protected string $segment = 'all',
        protected int $branchId = 0,
        protected bool $isOwner = false,
    ) {
    }

    public function collection()
    {
        return Customer::query()
            ->when(! $this->isOwner && $this->branchId > 0, fn ($q) => $q->where('branch_id', $this->branchId))
            ->withCount(['sales', 'sales as paid_sales_count' => fn (Builder $query) => $query->paid()])
            ->withSum(['sales as paid_sales_sum_total_amount' => fn (Builder $query) => $query->paid()], 'total_amount')
            ->withMax(['sales as last_purchase_at' => fn (Builder $query) => $query->paid()], 'sold_at')
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->q}%")
                        ->orWhere('phone', 'like', "%{$this->q}%")
                        ->orWhere('email', 'like', "%{$this->q}%")
                        ->orWhere('address', 'like', "%{$this->q}%");
                });
            })
            ->tap(fn ($query) => $this->applySegment($query))
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No HP',
            'Email',
            'Alamat',
            'Status',
            'Segmentasi',
            'Total Transaksi Paid',
            'Total Belanja Paid',
            'Poin Estimasi',
            'Belanja Terakhir',
            'Catatan Internal',
        ];
    }

    public function map($customer): array
    {
        $totalSpending = (float) ($customer->paid_sales_sum_total_amount ?? 0);
        $lastPurchase = $customer->last_purchase_at ? Carbon::parse($customer->last_purchase_at) : null;

        return [
            $customer->name,
            $customer->phone,
            $customer->email,
            $customer->address,
            $customer->is_active ? 'Aktif' : 'Nonaktif',
            $this->segmentLabel($customer, $totalSpending, $lastPurchase),
            (int) ($customer->paid_sales_count ?? 0),
            $totalSpending,
            (int) floor($totalSpending / 10000),
            $lastPurchase?->format('Y-m-d H:i:s') ?: '-',
            $customer->internal_note,
        ];
    }

    private function applySegment($query): void
    {
        $sleepingCutoff = now()->subDays(30)->toDateTimeString();
        match ($this->segment) {
            'active' => $query->where('is_active', true),
            'inactive' => $query->where('is_active', false),
            'vip' => $query->whereRaw(
                '(SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sales.customer_id = customers.id AND status = ?) >= ?',
                [SaleStatus::Paid->value, 1000000]
            ),
            'new' => $query->where('created_at', '>=', now()->subDays(30)),
            'sleeping' => $query->where(function ($sub) use ($sleepingCutoff) {
                $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                    ->orWhereRaw(
                        '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                        [SaleStatus::Paid->value, $sleepingCutoff]
                    );
            }),
            default => null,
        };
    }

    private function segmentLabel(Customer $customer, float $totalSpending, ?Carbon $lastPurchase): string
    {
        if (! $customer->is_active) {
            return 'Nonaktif';
        }

        if ($totalSpending >= 1000000) {
            return 'VIP';
        }

        if ($customer->created_at?->gte(now()->subDays(30))) {
            return 'Baru';
        }

        if (! $lastPurchase || $lastPurchase->lt(now()->subDays(30))) {
            return 'Tidur';
        }

        return 'Aktif';
    }
}
