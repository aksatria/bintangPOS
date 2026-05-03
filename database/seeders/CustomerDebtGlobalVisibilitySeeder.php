<?php

namespace Database\Seeders;

use App\Models\CustomerDebt;
use App\Models\Sale;
use Illuminate\Database\Seeder;

class CustomerDebtGlobalVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        $source = Sale::query()
            ->where('status', 'pending')
            ->whereNotNull('customer_id')
            ->latest('id')
            ->limit(10)
            ->get(['id', 'customer_id', 'sold_at', 'total_amount']);

        $created = 0;
        foreach ($source as $sale) {
            $exists = CustomerDebt::query()
                ->whereNull('branch_id')
                ->where('sale_id', $sale->id)
                ->exists();
            if ($exists) {
                continue;
            }

            $amount = max((float) $sale->total_amount, 1);
            CustomerDebt::query()->create([
                'branch_id' => null,
                'customer_id' => (int) $sale->customer_id,
                'sale_id' => (int) $sale->id,
                'created_by' => null,
                'number' => $this->nextNumber(),
                'debt_date' => optional($sale->sold_at)?->toDateString() ?? now()->toDateString(),
                'due_date' => optional($sale->sold_at)?->copy()?->addDays(7)?->toDateString(),
                'principal_amount' => $amount,
                'paid_amount' => 0,
                'remaining_amount' => $amount,
                'status' => 'overdue',
                'note' => 'Global visibility demo (existing-data)',
            ]);
            $created++;
        }

        if ($this->command) {
            $this->command->info("CustomerDebtGlobalVisibilitySeeder selesai. Debt baru: {$created}");
        }
    }

    private function nextNumber(): string
    {
        $prefix = 'AR-GLOBAL-' . now()->format('Ymd');
        $latest = CustomerDebt::query()->where('number', 'like', $prefix . '-%')->latest('id')->value('number');
        $next = 1;
        if (is_string($latest)) {
            $parts = explode('-', $latest);
            $next = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }
}

