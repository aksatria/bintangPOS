<?php

namespace App\Exports;

use App\Models\Customer;
use App\Models\SaleItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerPurchaseHistoryExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected Customer $customer,
        protected int $branchId = 0,
        protected bool $isOwner = false,
    ) {
    }

    public function collection()
    {
        return SaleItem::query()
            ->select('sale_items.*')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->when(! $this->isOwner && $this->branchId > 0, fn ($q) => $q->where('sales.branch_id', $this->branchId))
            ->where('sales.customer_id', $this->customer->id)
            ->with('sale.user:id,name')
            ->orderByDesc('sales.sold_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Tanggal',
            'Invoice',
            'Kasir',
            'Produk',
            'SKU',
            'Qty',
            'Harga Satuan',
            'Diskon Item',
            'Subtotal',
        ];
    }

    public function map($item): array
    {
        return [
            $item->sale?->sold_at?->format('Y-m-d H:i:s'),
            $item->sale?->invoice_number,
            $item->sale?->user?->name,
            $item->product_name,
            $item->sku,
            (int) $item->quantity,
            (float) $item->unit_price,
            (float) $item->discount_amount,
            (float) $item->subtotal,
        ];
    }
}
