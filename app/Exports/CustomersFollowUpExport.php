<?php

namespace App\Exports;

use App\Enums\SaleStatus;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersFollowUpExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected string $q = '',
        protected string $segment = 'all',
        protected string $pending = 'all',
        protected string $preset = 'all',
        protected int $branchId = 0,
        protected bool $isOwner = false,
    ) {
    }

    public function collection()
    {
        $query = Customer::query()
            ->when(! $this->isOwner && $this->branchId > 0, fn ($q) => $q->where('branch_id', $this->branchId))
            ->withMax(['sales as last_purchase_at' => fn (Builder $q) => $q->paid()], 'sold_at')
            ->withCount(['sales as pending_sales_count' => fn (Builder $q) => $q->where('status', SaleStatus::Pending->value)])
            ->withSum(['sales as paid_sales_sum_total_amount' => fn (Builder $q) => $q->paid()], 'total_amount')
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('name', 'like', "%{$this->q}%")
                        ->orWhere('phone', 'like', "%{$this->q}%")
                        ->orWhere('email', 'like', "%{$this->q}%");
                });
            });

        $this->applySegment($query);
        $this->applyPending($query);
        $this->applyPreset($query);

        return $query->orderBy('name')->get();
    }

    public function headings(): array
    {
        return [
            'Nama',
            'No HP',
            'Email',
            'Pending',
            'Hari Tidak Belanja',
            'Total Belanja Paid',
            'Prioritas Follow-up',
        ];
    }

    public function map($customer): array
    {
        $last = $customer->last_purchase_at ? Carbon::parse($customer->last_purchase_at) : null;
        $inactiveDays = $last ? now()->diffInDays($last) : null;
        $pendingCount = (int) ($customer->pending_sales_count ?? 0);
        $priority = $pendingCount > 0 || ($inactiveDays !== null && $inactiveDays >= 30) ? 'Tinggi' : 'Normal';

        return [
            $customer->name,
            $customer->phone,
            $customer->email,
            $pendingCount,
            $inactiveDays ?? 'Belum pernah belanja',
            (float) ($customer->paid_sales_sum_total_amount ?? 0),
            $priority,
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

    private function applyPending($query): void
    {
        if ($this->pending === 'with') {
            $query->whereHas('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value));
        } elseif ($this->pending === 'without') {
            $query->whereDoesntHave('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value));
        }
    }

    private function applyPreset($query): void
    {
        $sleepingCutoff = now()->subDays(30)->toDateTimeString();
        match ($this->preset) {
            'risky' => $query->where(function ($sub) use ($sleepingCutoff) {
                $sub->whereHas('sales', fn ($sale) => $sale->where('status', SaleStatus::Pending->value))
                    ->orWhere(function ($w) use ($sleepingCutoff) {
                        $w->where('is_active', true)
                            ->whereRaw(
                                '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                                [SaleStatus::Paid->value, $sleepingCutoff]
                            );
                    });
            }),
            'vip_sleeping' => $query
                ->whereRaw(
                    '(SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE sales.customer_id = customers.id AND status = ?) >= ?',
                    [SaleStatus::Paid->value, 1000000]
                )
                ->where(function ($sub) use ($sleepingCutoff) {
                    $sub->whereDoesntHave('sales', fn ($sale) => $sale->paid())
                        ->orWhereRaw(
                            '(SELECT MAX(sold_at) FROM sales WHERE sales.customer_id = customers.id AND status = ?) < ?',
                            [SaleStatus::Paid->value, $sleepingCutoff]
                        );
                }),
            'pending_7' => $query->whereHas('sales', fn ($sale) => $sale
                ->where('status', SaleStatus::Pending->value)
                ->whereDate('sold_at', '<=', now()->subDays(7)->toDateString())),
            'new_14' => $query->where('created_at', '>=', now()->subDays(14)),
            default => null,
        };
    }
}
