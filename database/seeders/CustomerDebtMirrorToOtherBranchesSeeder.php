<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CustomerDebt;
use App\Models\CustomerDebtPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerDebtMirrorToOtherBranchesSeeder extends Seeder
{
    public function run(): void
    {
        $sourceBranchId = (int) (Branch::query()->where('code', 'PUSAT')->value('id') ?? 1);
        $targetBranches = Branch::query()
            ->where('is_active', true)
            ->where('id', '!=', $sourceBranchId)
            ->get(['id', 'name', 'code']);

        $sourceDebts = CustomerDebt::query()
            ->where('branch_id', $sourceBranchId)
            ->latest('id')
            ->limit(20)
            ->get();

        if ($sourceDebts->isEmpty() || $targetBranches->isEmpty()) {
            if ($this->command) {
                $this->command->warn('Tidak ada source debt atau target branch aktif.');
            }
            return;
        }

        $usersByBranch = User::query()
            ->whereIn('role', ['owner', 'admin', 'kasir'])
            ->orderBy('id')
            ->get(['id', 'branch_id'])
            ->groupBy(fn (User $u) => (int) ($u->branch_id ?? 0));

        $created = 0;
        $paymentCreated = 0;

        foreach ($targetBranches as $branch) {
            foreach ($sourceDebts as $debt) {
                $exists = CustomerDebt::query()
                    ->where('branch_id', $branch->id)
                    ->where('customer_id', $debt->customer_id)
                    ->whereDate('debt_date', optional($debt->debt_date)?->toDateString())
                    ->where('principal_amount', (float) $debt->principal_amount)
                    ->exists();
                if ($exists) {
                    continue;
                }

                $collector = $usersByBranch->get((int) $branch->id)?->first();
                $new = CustomerDebt::query()->create([
                    'branch_id' => (int) $branch->id,
                    'customer_id' => (int) $debt->customer_id,
                    'sale_id' => null,
                    'created_by' => $collector?->id,
                    'number' => $this->nextMirrorNumber((string) ($branch->code ?? ('B' . $branch->id))),
                    'debt_date' => optional($debt->debt_date)?->toDateString() ?? now()->toDateString(),
                    'due_date' => optional($debt->due_date)?->toDateString(),
                    'principal_amount' => (float) $debt->principal_amount,
                    'paid_amount' => (float) $debt->paid_amount,
                    'remaining_amount' => (float) $debt->remaining_amount,
                    'status' => (string) $debt->status,
                    'note' => 'Mirror existing-data untuk cabang ' . $branch->name,
                ]);
                $created++;

                $firstPayment = $debt->payments()->oldest('id')->first();
                if ($firstPayment && (float) $firstPayment->amount > 0) {
                    CustomerDebtPayment::query()->create([
                        'customer_debt_id' => $new->id,
                        'received_by' => $collector?->id,
                        'paid_at' => $firstPayment->paid_at ?? now(),
                        'amount' => (float) $firstPayment->amount,
                        'payment_method' => (string) $firstPayment->payment_method,
                        'note' => 'Mirror existing-data payment',
                    ]);
                    $paymentCreated++;
                }
            }
        }

        if ($this->command) {
            $this->command->info("CustomerDebtMirrorToOtherBranchesSeeder selesai. Debt baru: {$created}, Payment baru: {$paymentCreated}");
        }
    }

    private function nextMirrorNumber(string $branchCode): string
    {
        $prefix = 'AR-MIRROR-' . strtoupper($branchCode) . '-' . now()->format('Ymd');
        $latest = CustomerDebt::query()->where('number', 'like', $prefix . '-%')->latest('id')->value('number');
        $next = 1;
        if (is_string($latest)) {
            $parts = explode('-', $latest);
            $next = ((int) end($parts)) + 1;
        }

        return sprintf('%s-%04d', $prefix, $next);
    }
}

