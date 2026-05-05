<?php

namespace App\Exports;

use App\Models\SupplierPurchase;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierPurchasesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $rows,
    ) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Nomor PO',
            'Supplier',
            'Invoice Supplier',
            'Surat Jalan',
            'Tanggal Order',
            'Jatuh Tempo',
            'Termin Hari',
            'Status Barang',
            'Status Bayar',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Ongkir',
            'Total',
            'Sudah Bayar',
            'Sisa Hutang',
            'Jumlah Item',
            'Jumlah Lampiran',
            'Barang',
            'Catatan',
        ];
    }

    public function map($row): array
    {
        /** @var SupplierPurchase $row */
        return [
            (string) $row->number,
            (string) ($row->supplier?->name ?? '-'),
            (string) ($row->supplier_invoice_number ?? ''),
            (string) ($row->delivery_note_number ?? ''),
            $row->ordered_at?->format('d/m/Y') ?? '',
            $row->due_date?->format('d/m/Y') ?? '',
            (int) ($row->payment_term_days ?? 0),
            $this->purchaseStatusLabel((string) $row->status),
            $this->paymentStatusLabel((string) $row->payment_status),
            (float) $row->subtotal,
            (float) $row->discount_amount,
            (float) $row->tax_amount,
            (float) $row->shipping_amount,
            (float) $row->total_amount,
            (float) $row->paid_amount,
            (float) $row->remaining_amount,
            (int) ($row->items_count ?? $row->items->count()),
            (int) ($row->attachments_count ?? $row->attachments->count()),
            $row->items
                ->map(fn ($item) => $item->product_name.' x '.(int) $item->quantity.' @ '.number_format((float) $item->unit_cost, 0, ',', '.'))
                ->implode('; '),
            (string) ($row->note ?? ''),
        ];
    }

    private function purchaseStatusLabel(string $status): string
    {
        return match($status) {
            'draft' => 'Draft',
            'received' => 'Barang Diterima',
            'cancelled' => 'Dibatalkan',
            default => $status !== '' ? ucfirst($status) : '-',
        };
    }

    private function paymentStatusLabel(string $status): string
    {
        return match($status) {
            'unpaid' => 'Belum Bayar',
            'partial' => 'Dibayar Sebagian',
            'overdue' => 'Lewat Tempo',
            'paid' => 'Lunas',
            default => $status !== '' ? ucfirst($status) : '-',
        };
    }
}
