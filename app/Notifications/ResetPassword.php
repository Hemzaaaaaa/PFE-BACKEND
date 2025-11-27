<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordNotification
{
    use Queueable;

    public function toMail($notifiable)
    {
        $url = config('app.frontend_url') .
            '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->line('We received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('If you didn’t request a password reset, no further action is required.');
    }
}
