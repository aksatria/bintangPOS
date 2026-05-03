<?php

namespace App\Exports;

use App\Models\CustomerDebt;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerDebtsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected Builder $query
    ) {
    }

    public function collection()
    {
        return (clone $this->query)
            ->with(['customer:id,name,phone', 'sale:id,invoice_number'])
            ->orderByRaw("CASE WHEN status='active' THEN 1 WHEN status='overdue' THEN 2 ELSE 3 END")
            ->latest('id')
            ->get();
    }

    public function headings(): array
    {
        return ['Nomor', 'Pelanggan', 'No HP', 'Invoice', 'Tanggal Hutang', 'Jatuh Tempo', 'Aging (Hari)', 'Pokok', 'Terbayar', 'Sisa', 'Status', 'Catatan'];
    }

    public function map($debt): array
    {
        $agingDays = '';
        if ($debt->due_date) {
            $agingDays = max(now()->startOfDay()->diffInDays($debt->due_date->copy()->startOfDay(), false) * -1, 0);
        }

        return [
            (string) $debt->number,
            (string) ($debt->customer?->name ?? '-'),
            (string) ($debt->customer?->phone ?? '-'),
            (string) ($debt->sale?->invoice_number ?? '-'),
            (string) optional($debt->debt_date)->format('Y-m-d'),
            (string) optional($debt->due_date)->format('Y-m-d'),
            $agingDays,
            (float) $debt->principal_amount,
            (float) $debt->paid_amount,
            (float) $debt->remaining_amount,
            (string) $debt->status,
            (string) ($debt->note ?? ''),
        ];
    }
}
