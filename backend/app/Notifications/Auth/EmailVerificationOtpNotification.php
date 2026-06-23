<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationOtpNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kode Verifikasi InternHub')
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Terima kasih telah mendaftar di InternHub.')
            ->line('Gunakan kode OTP berikut untuk memverifikasi akun Anda:')
            ->line('**'.$this->otp.'**')
            ->line('Kode ini akan kadaluwarsa dalam 10 menit.')
            ->line('Jika Anda tidak merasa mendaftar, abaikan email ini.');
    }
}
