<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginTwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public int $ttlMinutes,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi Login BINTANG POS')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Gunakan kode berikut untuk menyelesaikan login:')
            ->line('Kode: '.$this->code)
            ->line('Kode berlaku '.$this->ttlMinutes.' menit.')
            ->line('Jika Anda tidak merasa login, abaikan email ini.');
    }
}

