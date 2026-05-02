<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;

class SendTelegramWeeklySlaReport extends Command
{
    protected $signature = 'telegram:send-weekly-sla-report';

    protected $description = 'Kirim ringkasan mingguan SLA approval ke Telegram.';

    public function handle(): int
    {
        if (! TelegramNotifier::enabled()) {
            $this->info('Telegram nonaktif. Weekly SLA report dilewati.');
            return self::SUCCESS;
        }

        $end = now()->endOfDay();
        $start = now()->subDays(6)->startOfDay();
        $sent = 0;

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = $branches->concat([(object) ['id' => null, 'name' => 'Global']]);
        foreach ($branches as $branch) {
            $reviewed = ApprovalRequest::query()
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->whereIn('status', ['approved', 'rejected'])
                ->whereNotNull('reviewed_at')
                ->whereBetween('reviewed_at', [$start, $end]);

            $reviewedCount = (int) (clone $reviewed)->count();
            $rejectedCount = (int) (clone $reviewed)->where('status', 'rejected')->count();
            $autoExpiredCount = (int) ApprovalRequest::query()
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->where('status', 'rejected')
                ->whereBetween('reviewed_at', [$start, $end])
                ->where('review_note', 'like', '[AUTO-EXPIRED]%')
                ->count();

            $avgReviewMinutes = (int) round((float) ApprovalRequest::query()
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->whereIn('status', ['approved', 'rejected'])
                ->whereNotNull('reviewed_at')
                ->whereBetween('reviewed_at', [$start, $end])
                ->get(['created_at', 'reviewed_at'])
                ->map(fn ($row) => max(0, (int) $row->created_at?->diffInMinutes($row->reviewed_at)))
                ->avg());

            $overduePending = (int) ApprovalRequest::query()
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->where('status', 'pending')
                ->where('created_at', '<=', now()->subMinutes(120))
                ->count();

            $topBottleneck = ApprovalRequest::query()
                ->leftJoin('users', 'users.id', '=', 'approval_requests.assigned_to')
                ->selectRaw('approval_requests.assigned_to, users.name as assignee_name, COUNT(*) as total')
                ->when($branch->id, fn ($q) => $q->where('approval_requests.branch_id', $branch->id), fn ($q) => $q->whereNull('approval_requests.branch_id'))
                ->where('status', 'pending')
                ->whereNotNull('approval_requests.assigned_to')
                ->groupBy('approval_requests.assigned_to', 'users.name')
                ->orderByDesc('total')
                ->limit(3)
                ->get();

            $lines = [
                'Weekly SLA Report',
                'Cabang: '.$branch->name,
                'Periode: '.$start->format('d/m/Y').' - '.$end->format('d/m/Y'),
                'Reviewed: '.number_format($reviewedCount, 0, ',', '.'),
                'Reject Rate: '.($reviewedCount > 0 ? number_format(($rejectedCount / $reviewedCount) * 100, 1, ',', '.') : '0,0').'%',
                'Avg Review: '.number_format($avgReviewMinutes, 0, ',', '.').' menit',
                'Auto Expired: '.number_format($autoExpiredCount, 0, ',', '.'),
                'Pending Overdue Saat Ini: '.number_format($overduePending, 0, ',', '.'),
            ];

            if ($topBottleneck->isNotEmpty()) {
                $lines[] = 'Top Bottleneck Assignee:';
                foreach ($topBottleneck as $idx => $row) {
                    $name = trim((string) ($row->assignee_name ?? ''));
                    if ($name === '') {
                        $name = '#'.(int) $row->assigned_to;
                    }
                    $lines[] = ($idx + 1).'. '.$name.' ('.(int) $row->total.' pending)';
                }
            }

            if (TelegramNotifier::send(implode("\n", $lines), 'weekly_sla_report', TelegramNotifier::defaultChatId())) {
                $sent++;
            }
        }

        $this->info("Weekly SLA report terkirim untuk {$sent} cabang.");
        return self::SUCCESS;
    }
}
