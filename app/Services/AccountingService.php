<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\SupplierPurchase;
use App\Models\SupplierPurchasePayment;
use App\Models\SupplierPurchaseReturn;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    private const ACCOUNTS = [
        'cash' => '1101',
        'bank' => '1102',
        'ar_customer' => '1103',
        'inventory' => '1201',
        'vat_in' => '1301',
        'ap_supplier' => '2101',
        'vat_out' => '2102',
        'sales' => '4101',
        'sales_discount' => '4102',
        'cogs' => '5101',
        'freight_expense' => '5201',
        'purchase_discount' => '5301',
    ];

    public function postSupplierReceive(SupplierPurchase $purchase, ?int $userId = null): ?JournalEntry
    {
        $inventory = (float) $purchase->subtotal;
        if (($purchase->shipping_accounting_treatment ?? 'inventory') === 'inventory') {
            $inventory += (float) ($purchase->inventory_shipping_amount ?? 0);
        }
        $freightExpense = (float) ($purchase->expense_shipping_amount ?? 0);
        $tax = (float) ($purchase->tax_amount ?? 0);
        $discount = (float) ($purchase->discount_amount ?? 0);
        $payable = (float) $purchase->total_amount;

        $lines = [];
        if ($inventory > 0) {
            $lines[] = ['account' => 'inventory', 'debit' => $inventory, 'credit' => 0, 'description' => 'Persediaan dari '.$purchase->number];
        }
        if ($freightExpense > 0) {
            $lines[] = ['account' => 'freight_expense', 'debit' => $freightExpense, 'credit' => 0, 'description' => 'Ongkir pembelian '.$purchase->number];
        }
        if ($tax > 0) {
            $lines[] = ['account' => 'vat_in', 'debit' => $tax, 'credit' => 0, 'description' => 'PPN masukan '.$purchase->number];
        }
        if ($discount > 0) {
            $lines[] = ['account' => 'purchase_discount', 'debit' => 0, 'credit' => $discount, 'description' => 'Diskon pembelian '.$purchase->number];
        }
        $lines[] = ['account' => 'ap_supplier', 'debit' => 0, 'credit' => $payable, 'description' => 'Hutang supplier '.$purchase->number];

        return $this->post(
            $purchase->branch_id,
            $userId,
            SupplierPurchase::class,
            (int) $purchase->id,
            'supplier_receive',
            $purchase->received_at ? Carbon::parse($purchase->received_at) : now(),
            'Posting penerimaan pembelian supplier '.$purchase->number,
            $lines
        );
    }

    public function postSupplierPayment(SupplierPurchasePayment $payment, ?int $userId = null): ?JournalEntry
    {
        $payment->loadMissing('purchase');
        $purchase = $payment->purchase;
        if (! $purchase) {
            return null;
        }

        $cashAccount = in_array((string) $payment->payment_method, ['transfer', 'debit', 'qris', 'e_wallet'], true) ? 'bank' : 'cash';

        return $this->post(
            $purchase->branch_id,
            $userId,
            SupplierPurchasePayment::class,
            (int) $payment->id,
            'supplier_payment',
            $payment->paid_at ? Carbon::parse($payment->paid_at) : now(),
            'Posting pembayaran hutang supplier '.$purchase->number,
            [
                ['account' => 'ap_supplier', 'debit' => (float) $payment->amount, 'credit' => 0, 'description' => 'Bayar hutang '.$purchase->number],
                ['account' => $cashAccount, 'debit' => 0, 'credit' => (float) $payment->amount, 'description' => 'Kas/bank keluar '.$purchase->number],
            ]
        );
    }

    public function postSupplierReturn(SupplierPurchaseReturn $return, ?int $userId = null): ?JournalEntry
    {
        $return->loadMissing('purchase');
        $purchase = $return->purchase;
        if (! $purchase) {
            return null;
        }

        return $this->post(
            $purchase->branch_id,
            $userId,
            SupplierPurchaseReturn::class,
            (int) $return->id,
            'supplier_return',
            now(),
            'Posting retur pembelian supplier '.$purchase->number,
            [
                ['account' => 'ap_supplier', 'debit' => (float) $return->amount, 'credit' => 0, 'description' => 'Retur mengurangi hutang '.$purchase->number],
                ['account' => 'inventory', 'debit' => 0, 'credit' => (float) $return->amount, 'description' => 'Persediaan keluar retur '.$purchase->number],
            ]
        );
    }

    public function postSale(Sale $sale, ?int $userId = null): ?JournalEntry
    {
        $sale->loadMissing('items');

        $status = $sale->status instanceof SaleStatus ? $sale->status->value : (string) $sale->status;
        if ($status === SaleStatus::Cancelled->value) {
            return null;
        }

        $grossSales = (float) $sale->subtotal;
        $salesDiscount = (float) $sale->discount_amount;
        $tax = (float) $sale->tax_amount;
        $total = (float) $sale->total_amount;
        $paid = min((float) $sale->paid_amount, $total);
        $receivable = max($total - $paid, 0);
        if ($status !== SaleStatus::Paid->value) {
            $receivable = $total;
            $paid = 0;
        }

        $lines = [];
        foreach ($this->paymentLines($sale, $paid) as $line) {
            $lines[] = $line;
        }
        if ($receivable > 0) {
            $lines[] = ['account' => 'ar_customer', 'debit' => $receivable, 'credit' => 0, 'description' => 'Piutang penjualan '.$sale->invoice_number];
        }
        if ($salesDiscount > 0) {
            $lines[] = ['account' => 'sales_discount', 'debit' => $salesDiscount, 'credit' => 0, 'description' => 'Diskon penjualan '.$sale->invoice_number];
        }
        if ($grossSales > 0) {
            $lines[] = ['account' => 'sales', 'debit' => 0, 'credit' => $grossSales, 'description' => 'Penjualan '.$sale->invoice_number];
        }
        if ($tax > 0) {
            $lines[] = ['account' => 'vat_out', 'debit' => 0, 'credit' => $tax, 'description' => 'PPN keluaran '.$sale->invoice_number];
        }

        $cogs = (float) $sale->items->sum(fn ($item) => (float) $item->purchase_price * (int) $item->quantity);
        if ($cogs > 0) {
            $lines[] = ['account' => 'cogs', 'debit' => $cogs, 'credit' => 0, 'description' => 'HPP '.$sale->invoice_number];
            $lines[] = ['account' => 'inventory', 'debit' => 0, 'credit' => $cogs, 'description' => 'Persediaan keluar '.$sale->invoice_number];
        }

        return $this->post(
            $sale->branch_id,
            $userId,
            Sale::class,
            (int) $sale->id,
            'sale_posted',
            $sale->sold_at ? Carbon::parse($sale->sold_at) : now(),
            'Posting penjualan POS '.$sale->invoice_number,
            $lines
        );
    }

    private function post(?int $branchId, ?int $userId, string $sourceType, int $sourceId, string $event, Carbon $postedAt, string $memo, array $lines): ?JournalEntry
    {
        $lines = collect($lines)
            ->map(fn ($line) => [
                'account' => (string) $line['account'],
                'description' => (string) ($line['description'] ?? ''),
                'debit' => round((float) ($line['debit'] ?? 0), 2),
                'credit' => round((float) ($line['credit'] ?? 0), 2),
            ])
            ->filter(fn ($line) => $line['debit'] > 0 || $line['credit'] > 0)
            ->values();

        $debit = round((float) $lines->sum('debit'), 2);
        $credit = round((float) $lines->sum('credit'), 2);
        if ($debit <= 0 || abs($debit - $credit) > 0.01) {
            return null;
        }

        return DB::transaction(function () use ($branchId, $userId, $sourceType, $sourceId, $event, $postedAt, $memo, $lines): JournalEntry {
            $existing = JournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('event', $event)
                ->first();
            if ($existing) {
                return $existing;
            }

            $entry = JournalEntry::query()->create([
                'branch_id' => $branchId,
                'created_by' => $userId,
                'number' => $this->nextNumber(),
                'posted_at' => $postedAt->toDateString(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'event' => $event,
                'memo' => $memo,
                'status' => 'posted',
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $this->account($line['account'])->id,
                    'description' => $line['description'],
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $entry;
        });
    }

    private function account(string $key): Account
    {
        return Account::query()->firstOrCreate(
            ['code' => self::ACCOUNTS[$key]],
            ['name' => $key, 'type' => 'asset', 'is_active' => true]
        );
    }

    private function paymentLines(Sale $sale, float $paid): array
    {
        if ($paid <= 0) {
            return [];
        }

        if ((string) $sale->payment_method === 'mixed' && is_array($sale->payment_breakdown)) {
            $rows = collect($sale->payment_breakdown)
                ->map(fn ($row) => [
                    'method' => (string) ($row['method'] ?? 'cash'),
                    'amount' => max((float) ($row['amount'] ?? 0), 0),
                ])
                ->filter(fn ($row) => $row['amount'] > 0)
                ->values();

            $sum = (float) $rows->sum('amount');
            if ($sum > 0) {
                $factor = $paid / $sum;

                return $rows->map(fn ($row) => [
                    'account' => $this->cashAccountForMethod($row['method']),
                    'debit' => round($row['amount'] * $factor, 2),
                    'credit' => 0,
                    'description' => 'Pembayaran '.strtoupper(str_replace('_', ' ', $row['method'])).' '.$sale->invoice_number,
                ])->all();
            }
        }

        return [[
            'account' => $this->cashAccountForMethod((string) $sale->payment_method),
            'debit' => $paid,
            'credit' => 0,
            'description' => 'Pembayaran '.$sale->invoice_number,
        ]];
    }

    private function cashAccountForMethod(string $method): string
    {
        return in_array($method, ['transfer', 'debit', 'qris', 'e_wallet'], true) ? 'bank' : 'cash';
    }

    private function nextNumber(): string
    {
        $prefix = 'JE-'.now()->format('Ym').'-';
        $last = JournalEntry::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $next = 1;
        if (is_string($last) && preg_match('/(\d+)$/', $last, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
