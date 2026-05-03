<?php

namespace Database\Seeders;

use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CustomerDebtFromExistingDataSeeder extends Seeder
{
    public function run(): void
    {
        $created = 0;
        $paymentCreated = 0;

        $collectorByBranch = User::query()
            ->select(['id', 'branch_id'])
            ->whereIn('role', ['owner', 'admin', 'kasir'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (User $u) => (int) ($u->branch_id ?? 0));

        $pendingSales = Sale::query()
            ->with(['customer:id,name', 'branch:id'])
            ->where('status', 'pending')
            ->whereNotNull('customer_id')
            ->orderByDesc('sold_at')
            ->limit(40)
            ->get();

        foreach ($pendingSales as $sale) {
            if (! $sale->customer_id) {
                continue;
            }

            $existing = CustomerDebt::query()->where('sale_id', $sale->id)->first();
            if ($existing) {
                continue;
            }

            $principal = max((float) $sale->total_amount, 1);
            $dueDate = optional($sale->sold_at)->copy()?->addDays(14) ?? now()->addDays(14);
            $status = $dueDate->isPast() ? 'overdue' : 'active';
            $branchId = (int) ($sale->branch_id ?? 0) ?: null;
            $collector = $collectorByBranch->get((int) ($branchId ?? 0))?->first();

            $debt = CustomerDebt::query()->create([
                'branch_id' => $branchId,
                'customer_id' => (int) $sale->customer_id,
                'sale_id' => (int) $sale->id,
                'created_by' => $collector?->id,
                'number' => $this->nextSeedNumber(),
                'debt_date' => optional($sale->sold_at)?->toDateString() ?? now()->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'principal_amount' => $principal,
                'paid_amount' => 0,
                'remaining_amount' => $principal,
                'status' => $status,
                'note' => 'Seeder existing-data: generate dari transaksi pending ' . ($sale->invoice_number ?? ('#' . $sale->id)),
            ]);
            $created++;

            // Buat sebagian data jadi contoh cicilan parsial (realistis)
            if (((int) $debt->id % 3) === 0) {
                $paid = round($principal * 0.35, 2);
                CustomerDebtPayment::query()->create([
                    'customer_debt_id' => $debt->id,
                    'received_by' => $collector?->id,
                    'paid_at' => Carbon::parse($debt->debt_date)->addDays(3),
                    'amount' => $paid,
                    'payment_method' => 'transfer',
                    'note' => 'Seeder existing-data: cicilan awal',
                ]);
                $paymentCreated++;

                $remaining = max($principal - $paid, 0);
                $debt->update([
                    'paid_amount' => $paid,
                    'remaining_amount' => $remaining,
                    'status' => $remaining <= 0 ? 'paid' : ($debt->due_date && $debt->due_date->isPast() ? 'overdue' : 'active'),
                ]);
            }
        }

        if ($this->command) {
            $this->command->info("CustomerDebtFromExistingDataSeeder selesai. Debt baru: {$created}, Payment baru: {$paymentCreated}");
        }
    }

    private function nextSeedNumber(): string
    {
        $prefix = 'AR-SEED-' . now()->format('Ymd');
        $latest = CustomerDebt::query()
            ->where('number', 'like', $prefix . '-%')
            ->latest('id')
            ->value('number');

        $next = 1;
        if (is_string($latest)) {
            $parts = explode('-', $latest);
            $next = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }
}

