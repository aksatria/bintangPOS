<?php

namespace App\Console\Commands;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\CashierAuditLog;
use App\Models\StoreSetting;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoExpireApprovalsCommand extends Command
{
    protected $signature = 'approval:auto-expire {--minutes=0 : Override auto-expire minutes threshold}';

    protected $description = 'Auto reject approval pending yang melewati batas waktu.';

    public function handle(): int
    {
        $override = (int) $this->option('minutes');
        $store = StoreSetting::query()->first();
        $approvalRules = is_array($store?->approval_rules) ? $store->approval_rules : [];
        $ruleMinutes = (int) data_get($approvalRules, 'auto_expire_minutes', 0);
        $setting = (int) ($store?->telegram_approval_sla_minutes ?? 0);
        $minutes = $override > 0
            ? max(10, $override)
            : ($ruleMinutes > 0
                ? max(10, $ruleMinutes)
                : ($setting > 0 ? max(10, $setting * 12) : max(10, (int) env('APPROVAL_AUTO_EXPIRE_MINUTES', 1440))));

        $cutoff = now()->subMinutes($minutes);
        $expired = 0;
        $expiredByBranch = [];
        $branchNames = Branch::query()->pluck('name', 'id');

        DB::transaction(function () use ($cutoff, $minutes, &$expired, &$expiredByBranch): void {
            $rows = ApprovalRequest::query()
                ->where('status', 'pending')
                ->where('created_at', '<=', $cutoff)
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $note = trim((string) ($row->review_note ?? ''));
                $row->status = 'rejected';
                $row->review_note = trim('[AUTO-EXPIRED] Pending melebihi '.$minutes.' menit.'.($note !== '' ? ' '.$note : ''));
                $row->reviewed_by = null;
                $row->reviewed_at = now();

                $payload = is_array($row->payload) ? $row->payload : [];
                $payload['auto_expired_at'] = now()->toIso8601String();
                $payload['auto_expire_threshold_minutes'] = $minutes;
                $row->payload = $payload;
                $row->save();
                $branchId = (int) ($row->branch_id ?? 0);
                $branchKey = $branchId > 0 ? (string) $branchId : 'global';
                $expiredByBranch[$branchKey] = (int) ($expiredByBranch[$branchKey] ?? 0) + 1;

                CashierAuditLog::query()->create([
                    'user_id' => null,
                    'branch_id' => $branchId > 0 ? $branchId : null,
                    'action' => 'approval_request_auto_expired',
                    'context' => [
                        'approval_id' => $row->id,
                        'type' => $row->type,
                        'title' => $row->title,
                        'threshold_minutes' => $minutes,
                    ],
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'scheduler:approval:auto-expire',
                ]);
                $expired++;
            }
        });

        if ($expired > 0 && TelegramNotifier::enabled()) {
            foreach ($expiredByBranch as $branchKey => $count) {
                $branchLabel = $branchKey === 'global'
                    ? 'Global'
                    : ((string) ($branchNames[(int) $branchKey] ?? ('#'.$branchKey)));
                TelegramNotifier::send(
                    implode("\n", [
                        'Auto Expire Approval',
                        'Cabang: '.$branchLabel,
                        'Total auto-reject: '.number_format((int) $count, 0, ',', '.'),
                        'Batas: '.$minutes.' menit',
                    ]),
                    'approval_auto_expired',
                    TelegramNotifier::defaultChatId()
                );
            }
        }

        $this->info("Selesai. {$expired} approval di-auto-expire (batas {$minutes} menit).");

        return self::SUCCESS;
    }
}
