<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;

/**
 * Attaches the logo inside every branded email. The mail header shows it with
 * <img src="cid:mail-logo">, so it appears even before the website is online and in
 * inboxes that block pictures from the web.
 */
class EmbedMailLogo
{
    public const CID = 'mail-logo';

    public function handle(MessageSending $event): void
    {
        $html = $event->message->getHtmlBody();

        if (! is_string($html) || ! str_contains($html, 'cid:'.self::CID)) {
            return;
        }

        $event->message->embedFromPath(public_path('images/mail-logo.png'), self::CID, 'image/png');
    }
}
