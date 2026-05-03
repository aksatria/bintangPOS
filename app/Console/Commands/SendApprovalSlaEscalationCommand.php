<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\BusinessHourSla;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendApprovalSlaEscalationCommand extends Command
{
    protected $signature = 'approval:send-sla-escalation {--minutes=0 : Override SLA minutes threshold}';

    protected $description = 'Kirim alert Telegram untuk approval pending yang melewati SLA.';

    public function handle(): int
    {
        $lock = Cache::lock('approval:sla-escalation:lock', 540);
        if (! $lock->get()) {
            $this->info('Proses SLA escalation sedang berjalan, skip untuk hindari duplikasi.');
            return self::SUCCESS;
        }

        try {
            if (! TelegramNotifier::enabled()) {
                $this->info('Telegram nonaktif. SLA escalation dilewati.');
                return self::SUCCESS;
            }

            $threshold = (int) $this->option('minutes');
            $store = StoreSetting::query()->first();
            $approvalRules = is_array($store?->approval_rules) ? $store->approval_rules : [];
            $saleSla = max(5, (int) data_get($approvalRules, 'sla_minutes_sale', 120));
            $exportSla = max(5, (int) data_get($approvalRules, 'sla_minutes_export', 360));
            $hasBusinessHoursConfig = is_array(data_get($approvalRules, 'business_hours'));
            $businessHourRules = [
                'start' => (string) data_get($approvalRules, 'business_hours.start', '08:00'),
                'end' => (string) data_get($approvalRules, 'business_hours.end', '22:00'),
                'workdays' => (array) data_get($approvalRules, 'business_hours.workdays', [1, 2, 3, 4, 5, 6, 7]),
            ];
            if ($threshold <= 0) {
                $settingThreshold = (int) ($store?->telegram_approval_sla_minutes ?? 0);
                if ($settingThreshold > 0) {
                    $threshold = max(5, $settingThreshold);
                } else {
                    $threshold = max(5, (int) env('APPROVAL_SLA_ESCALATION_MINUTES', 120));
                }
            }

            $candidates = ApprovalRequest::query()
                ->with('requester:id,name')
                ->where('status', 'pending')
                ->orderBy('created_at')
                ->limit(100)
                ->get();
            $branchNames = Branch::query()->pluck('name', 'id');

            $sent = 0;
            $checked = 0;
            foreach ($candidates as $approval) {
                $checked++;
                if ($approval->snoozed_until && $approval->snoozed_until->isFuture()) {
                    continue;
                }

                $payload = is_array($approval->payload) ? $approval->payload : [];
                $type = (string) $approval->type;
                $baseThreshold = $threshold;
                if (in_array($type, ['sale.quick_refund', 'sale.quick_void'], true)) {
                    $baseThreshold = $saleSla;
                } elseif (str_starts_with($type, 'report.export.')) {
                    $baseThreshold = $exportSla;
                }
                $baseThreshold = max(5, $baseThreshold);

                $ageMinutes = $hasBusinessHoursConfig
                    ? BusinessHourSla::diffInBusinessMinutes($approval->created_at, now(), $businessHourRules)
                    : (int) $approval->created_at->diffInMinutes(now());
                if ($ageMinutes < $baseThreshold) {
                    continue;
                }

                $levels = [
                    1 => $baseThreshold,
                    2 => $baseThreshold * 2,
                    3 => $baseThreshold * 4,
                ];
                $targetLevel = 0;
                foreach ($levels as $lvl => $limit) {
                    if ($ageMinutes >= $limit) {
                        $targetLevel = $lvl;
                    }
                }
                if ($targetLevel === 0) {
                    continue;
                }

                $notifiedLevels = (array) data_get($payload, 'sla_escalation_levels', []);
                if (! empty($notifiedLevels[(string) $targetLevel])) {
                    continue;
                }
                $cooldownSeconds = max(60, (int) env('APPROVAL_SLA_ESCALATION_COOLDOWN_SECONDS', 600));
                $cooldownKey = "approval:sla-escalation:{$approval->id}:L{$targetLevel}";
                if (Cache::has($cooldownKey)) {
                    continue;
                }

                $autoAssignedName = null;
                $branchId = (int) ($approval->branch_id ?? 0);
                $branchLabel = $branchId > 0 ? (string) ($branchNames[$branchId] ?? ('#'.$branchId)) : 'Global';
                if ($targetLevel >= 2) {
                    $assignee = $this->pickEscalationAssignee($targetLevel, (int) ($approval->assigned_to ?? 0), $branchId > 0 ? $branchId : null);
                    if ($assignee && (int) ($approval->assigned_to ?? 0) !== (int) $assignee->id) {
                        $approval->assigned_to = (int) $assignee->id;
                        $autoAssignedName = (string) $assignee->name;
                    }
                }

                $lines = [
                    'SLA Approval Terlewati',
                    'Level Escalation: '.$targetLevel,
                    'ID: #'.$approval->id,
                    'Cabang: '.$branchLabel,
                    'Tipe: '.$approval->type,
                    'Judul: '.$approval->title,
                    'Peminta: '.($approval->requester?->name ?: '-'),
                    'Aging: '.$ageMinutes.' menit',
                    'Batas SLA Dasar: '.$baseThreshold.' menit',
                ];
                if ($autoAssignedName) {
                    $lines[] = 'Auto-Assign Reviewer: '.$autoAssignedName;
                }
                $message = implode("\n", $lines);

                $ok = TelegramNotifier::send($message, 'approval_sla_escalation', TelegramNotifier::defaultChatId());
                if (! $ok) {
                    continue;
                }
                Cache::put($cooldownKey, 1, now()->addSeconds($cooldownSeconds));

                $payload['sla_escalation_notified_at'] = now()->toIso8601String();
                $payload['sla_escalation_threshold_minutes'] = $baseThreshold;
                $payload['sla_escalation_last_level'] = $targetLevel;
                $payload['sla_escalation_levels'] = array_merge($notifiedLevels, [
                    (string) $targetLevel => now()->toIso8601String(),
                ]);
                if ($autoAssignedName) {
                    $payload['sla_escalation_auto_assigned_at'] = now()->toIso8601String();
                    $payload['sla_escalation_auto_assigned_name'] = $autoAssignedName;
                    $payload['sla_escalation_auto_assigned_level'] = $targetLevel;
                }
                $approval->payload = $payload;
                $approval->save();
                $sent++;
            }

            $this->info("Selesai. Alert escalation terkirim: {$sent}.");
            CashierAuditLog::query()->create([
                'user_id' => null,
                'action' => 'approval_sla_escalation_run',
                'context' => [
                    'checked' => $checked,
                    'sent' => $sent,
                    'cooldown_seconds' => max(60, (int) env('APPROVAL_SLA_ESCALATION_COOLDOWN_SECONDS', 600)),
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'scheduler:approval:send-sla-escalation',
            ]);
            return self::SUCCESS;
        } finally {
            optional($lock)->release();
        }
    }

    private function pickEscalationAssignee(int $level, int $currentAssignedId = 0, ?int $branchId = null): ?User
    {
        $roleCandidates = $level >= 3 ? ['owner'] : ['admin', 'owner'];
        $approvers = collect();
        foreach ($roleCandidates as $role) {
            $batch = User::query()
                ->select('id', 'name', 'role')
                ->where('role', $role)
                ->when($role !== 'owner' && $branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->orderBy('name')
                ->get();
            if ($batch->isNotEmpty()) {
                $approvers = $batch;
                break;
            }
        }

        if ($approvers->isEmpty()) {
            return null;
        }

        $pendingLoads = ApprovalRequest::query()
            ->selectRaw('assigned_to, COUNT(*) as total')
            ->where('status', 'pending')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $best = null;
        $bestScore = PHP_INT_MAX;
        foreach ($approvers as $approver) {
            $score = (int) ($pendingLoads[(int) $approver->id] ?? 0);
            if ($currentAssignedId > 0 && (int) $approver->id === $currentAssignedId) {
                $score += 2;
            }
            if ($score < $bestScore) {
                $bestScore = $score;
                $best = $approver;
            }
        }

        return $best;
    }
}
