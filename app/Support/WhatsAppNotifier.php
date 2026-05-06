<?php

namespace App\Support;

use App\Models\CashierAuditLog;
use Illuminate\Support\Facades\Http;

class WhatsAppNotifier
{
    public static function enabled(): bool
    {
        return (bool) config('services.whatsapp.enabled', false);
    }

    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }
        if (! str_starts_with($digits, '62')) {
            return '62'.$digits;
        }

        return $digits;
    }

    public static function send(string $targetPhone, string $message, string $purpose = 'general'): bool
    {
        $enabled = self::enabled();
        $apiUrl = trim((string) config('services.whatsapp.api_url', ''));
        $apiToken = trim((string) config('services.whatsapp.api_token', ''));
        $authHeader = trim((string) config('services.whatsapp.auth_header', 'Authorization'));
        $target = self::normalizePhone($targetPhone);

        if (! $enabled || $apiUrl === '' || $apiToken === '' || $target === '' || trim($message) === '') {
            self::log(false, $purpose, $target, $message, 'WA disabled/config kosong/target kosong');
            return false;
        }

        try {
            $res = Http::retry(2, 250, throw: false)
                ->timeout(12)
                ->withHeaders([$authHeader => $apiToken])
                ->asForm()
                ->post($apiUrl, [
                    'target' => $target,
                    'message' => $message,
                ]);

            $ok = $res->successful();
            self::log($ok, $purpose, $target, $message, $ok ? null : ('HTTP '.$res->status()));

            return $ok;
        } catch (\Throwable $e) {
            self::log(false, $purpose, $target, $message, $e->getMessage());
            return false;
        }
    }

    private static function log(bool $ok, string $purpose, string $targetPhone, string $message, ?string $error = null): void
    {
        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $ok ? 'whatsapp_send_ok' : 'whatsapp_send_failed',
            'context' => [
                'purpose' => $purpose,
                'target_phone' => $targetPhone,
                'message' => mb_substr($message, 0, 350),
                'error' => $error,
            ],
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
        ]);
    }
}

