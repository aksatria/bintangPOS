<?php

namespace App\Support;

use App\Models\CashierAuditLog;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\Http;

class TelegramNotifier
{
    public static function send(string $message, string $purpose = 'general', ?string $chatId = null): bool
    {
        $token = (string) env('TELEGRAM_BOT_TOKEN', '');
        $defaultChatId = (string) env('TELEGRAM_CHAT_ID', '');
        $targetChatId = trim((string) ($chatId ?: $defaultChatId));

        if ($token === '' || $targetChatId === '') {
            self::log(false, $purpose, $targetChatId, $message, 'Token/chat id kosong');
            return false;
        }

        try {
            $res = Http::retry(3, 200, throw: false)
                ->timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $targetChatId,
                'text' => $message,
            ]);

            $ok = $res->successful() && (bool) data_get($res->json(), 'ok', false);
            self::log($ok, $purpose, $targetChatId, $message, $ok ? null : ('HTTP '.$res->status()));

            return $ok;
        } catch (\Throwable $e) {
            self::log(false, $purpose, $targetChatId, $message, $e->getMessage());
            return false;
        }
    }

    public static function enabled(): bool
    {
        $setting = StoreSetting::query()->first();
        return (bool) ($setting?->telegram_enabled);
    }

    public static function defaultChatId(): string
    {
        $setting = StoreSetting::query()->first();
        $override = trim((string) ($setting?->telegram_override_chat_id ?? ''));
        if ($override !== '') {
            return $override;
        }

        return (string) env('TELEGRAM_CHAT_ID', '');
    }

    private static function log(bool $ok, string $purpose, string $targetChatId, string $message, ?string $error = null): void
    {
        CashierAuditLog::query()->create([
            'user_id' => auth()->id(),
            'action' => $ok ? 'telegram_send_ok' : 'telegram_send_failed',
            'context' => [
                'purpose' => $purpose,
                'target_chat_id' => $targetChatId,
                'message' => mb_substr($message, 0, 350),
                'error' => $error,
            ],
            'ip_address' => request()?->ip() ?? '127.0.0.1',
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
