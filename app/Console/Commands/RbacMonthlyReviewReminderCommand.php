<?php

namespace App\Console\Commands;

use App\Models\RolePermissionGrant;
use App\Support\TelegramNotifier;
use Illuminate\Console\Command;

class RbacMonthlyReviewReminderCommand extends Command
{
    protected $signature = 'rbac:monthly-review-reminder';

    protected $description = 'Kirim pengingat review akses RBAC bulanan via Telegram.';

    public function handle(): int
    {
        if (! TelegramNotifier::enabled()) {
            $this->info('Telegram nonaktif.');
            return self::SUCCESS;
        }

        $activeTemp = (int) RolePermissionGrant::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();

        TelegramNotifier::send(
            implode("\n", [
                'Reminder Review RBAC Bulanan',
                'Checklist:',
                '- Validasi akses owner/admin/kasir sesuai tugas.',
                '- Cabut permission sementara yang tidak diperlukan.',
                '- Pastikan prinsip deny-by-default tetap aktif.',
                "Total temporary grants aktif: {$activeTemp}",
            ]),
            'rbac_monthly_review',
            TelegramNotifier::defaultChatId()
        );

        $this->info('Reminder RBAC terkirim.');
        return self::SUCCESS;
    }
}

