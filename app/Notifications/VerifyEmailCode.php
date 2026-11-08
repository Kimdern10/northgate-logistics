<?php

namespace App\Notifications;

use App\Models\Setting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the six-digit code a new customer types in to verify their email address.
 */
class VerifyEmailCode extends Notification
{
    public function __construct(public string $code, public int $minutesValid) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $site = Setting::values();

        return (new MailMessage)
            ->subject(__(':code is your :site verification code', ['code' => $this->code, 'site' => $site['business_name']]))
            ->markdown('mail.verification-code', [
                'site' => $site,
                'name' => $notifiable->name ?? __('there'),
                'code' => trim(chunk_split($this->code, 3, ' ')),
                'minutes' => $this->minutesValid,
            ]);
    }
}
