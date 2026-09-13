<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class StaffResetPasswordNotification extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Reset your Kraftrack admin password')
            ->greeting('Reset your admin password')
            ->line('Someone requested a password reset for your Kraftrack operations account.')
            ->action('Choose a new password', $url)
            ->line('This link expires in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60).' minutes.')
            ->line('If you did not request a reset, you can ignore this email.');
    }
}
