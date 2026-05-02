<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CustomerFollowUp;
use App\Models\StoreSetting;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;

class SendTelegramFollowUpReminders extends Command
{
    protected $signature = 'telegram:send-followup-reminders';

    protected $description = 'Kirim reminder follow-up jatuh tempo ke Telegram.';

    public function handle(): int
    {
        $setting = StoreSetting::query()->first();
        if (! $setting || ! $setting->telegram_enabled) {
            return self::SUCCESS;
        }

        $branches = Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $branches = $branches->concat([(object) ['id' => null, 'name' => 'Global']]);
        $sent = 0;

        foreach ($branches as $branch) {
            $rows = CustomerFollowUp::query()
                ->with('customer:id,name,phone')
                ->when($branch->id, fn ($q) => $q->where('branch_id', $branch->id), fn ($q) => $q->whereNull('branch_id'))
                ->whereIn('status', ['baru', 'proses'])
                ->whereNotNull('reminder_at')
                ->whereNull('reminded_at')
                ->where('reminder_at', '<=', now())
                ->orderBy('reminder_at')
                ->limit(20)
                ->get();

            foreach ($rows as $row) {
                $text = "Reminder Follow-up Customer\n"
                    ."Cabang: {$branch->name}\n"
                    ."Customer: ".($row->customer?->name ?: '-')."\n"
                    ."Kontak: ".($row->customer?->phone ?: '-')."\n"
                    ."Jatuh tempo: ".$row->reminder_at?->format('d/m/Y H:i')."\n"
                    ."Tindakan: ".strtoupper($row->action_type)."\n"
                    ."Catatan: ".($row->note ?: '-');

                $ok = TelegramNotifier::send($text, 'followup_due');
                if ($ok) {
                    $row->reminded_at = now();
                    $row->save();
                    $sent++;
                }
            }
        }

        $this->info("Reminder follow-up terkirim: {$sent} item.");
        return self::SUCCESS;
    }
}
