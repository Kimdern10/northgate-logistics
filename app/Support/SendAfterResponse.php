<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

use function Illuminate\Support\defer;

/**
 * Sends email after the page has been sent to the visitor, so a slow mail server (such as
 * Gmail SMTP, which can take several seconds per message) never makes a button feel broken.
 *
 * This needs no queue worker: PHP finishes the response first (PHP-FPM on Herd, LiteSpeed on
 * Namecheap) and then runs the deferred code. A failed send is logged and never shown to the visitor.
 */
class SendAfterResponse
{
    public static function mail(string $email, Mailable $mailable): void
    {
        defer(function () use ($email, $mailable): void {
            try {
                Mail::to($email)->send($mailable);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }

    public static function notify(User $user, Notification $notification): void
    {
        defer(function () use ($user, $notification): void {
            try {
                $user->notify($notification);
            } catch (\Throwable $exception) {
                report($exception);
            }
        });
    }
}
