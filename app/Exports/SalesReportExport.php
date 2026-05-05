<?php

namespace App\Exports;

use App\Models\Sale;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected Carbon $startDate,
        protected Carbon $endDate,
        protected int $customerId = 0,
        protected string $paymentMethod = 'all',
        protected string $qrisReference = '',
        protected array $selectedIds = [],
        protected array $exportMeta = [],
        protected int $branchId = 0,
        protected bool $isOwner = false,
    ) {
    }

    public function collection()
    {
        return Sale::query()
            ->with(['user:id,name', 'customer:id,name'])
            ->when(! $this->isOwner && $this->branchId > 0, fn ($q) => $q->where('branch_id', $this->branchId))
            ->whereBetween('sold_at', [$this->startDate->copy()->startOfDay(), $this->endDate->copy()->endOfDay()])
            ->when($this->customerId > 0, fn ($q) => $q->where('customer_id', $this->customerId))
            ->when($this->paymentMethod !== 'all', fn ($q) => $q->where('payment_method', $this->paymentMethod))
            ->when($this->qrisReference !== '', function ($q) {
                $q->where('note', 'like', '%[QRIS] Ref:%')
                    ->where('note', 'like', '%'.$this->qrisReference.'%');
            })
            ->when(! empty($this->selectedIds), fn ($q) => $q->whereIn('id', $this->selectedIds))
            ->latest('sold_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Diekspor Pada',
            'Diekspor Oleh',
            'Alasan Export',
            'Approval Oleh',
            'MoM Omzet (%)',
            'MoM Laba (%)',
            'Tanggal',
            'Invoice',
            'Kasir',
            'Pelanggan',
            'Status',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Total',
            'Dibayar',
            'Kembalian',
            'Metode Bayar',
            'Ref QRIS',
            'Issuer QRIS',
        ];
    }

    public function map($sale): array
    {
        [$qrisRef, $qrisIssuer] = $this->extractQrisFromNote((string) ($sale->note ?? ''));

        return [
            (string) ($this->exportMeta['exported_at'] ?? ''),
            (string) ($this->exportMeta['exported_by'] ?? ''),
            (string) ($this->exportMeta['export_reason'] ?? ''),
            (string) ($this->exportMeta['approved_by'] ?? ''),
            is_null($this->exportMeta['mom_omzet_pct'] ?? null) ? '' : round((float) $this->exportMeta['mom_omzet_pct'], 2),
            is_null($this->exportMeta['mom_profit_pct'] ?? null) ? '' : round((float) $this->exportMeta['mom_profit_pct'], 2),
            $sale->sold_at?->format('Y-m-d H:i:s'),
            $sale->invoice_number,
            $sale->user?->name,
            $sale->customer?->name ?: ($sale->customer_name ?: '-'),
            strtoupper($sale->status->value),
            (float) $sale->subtotal,
            (float) $sale->discount_amount,
            (float) $sale->tax_amount,
            (float) $sale->total_amount,
            (float) $sale->paid_amount,
            (float) $sale->change_amount,
            strtoupper(str_replace('_', ' ', (string) ($sale->payment_method ?? 'cash'))),
            $qrisRef,
            $qrisIssuer,
        ];
    }

    private function extractQrisFromNote(string $note): array
    {
        if (preg_match_all('/\[QRIS\]\s*Ref:\s*(.*?)\s*\|\s*Issuer:\s*(.*)/', $note, $matches, PREG_SET_ORDER) && count($matches) > 0) {
            $last = $matches[count($matches) - 1];
            $ref = trim((string) ($last[1] ?? ''));
            $issuer = trim((string) ($last[2] ?? ''));

            return [$ref, $issuer];
        }

        return ['', ''];
    }
}
