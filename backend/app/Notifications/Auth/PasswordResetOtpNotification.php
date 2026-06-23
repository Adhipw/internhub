<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtpNotification extends Notification
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
            ->subject('Reset Password InternHub')
            ->greeting('Halo '.$notifiable->name.'!')
            ->line('Anda meminta pengaturan ulang password untuk akun InternHub Anda.')
            ->line('Gunakan kode OTP berikut untuk melanjutkan:')
            ->line('**'.$this->otp.'**')
            ->line('Kode ini akan kadaluwarsa dalam 15 menit.')
            ->line('Jika Anda tidak meminta reset password, abaikan email ini dan pastikan akun Anda aman.');
    }
}
