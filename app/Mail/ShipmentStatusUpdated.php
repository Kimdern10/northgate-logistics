<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the sender and receiver when a shipment's status changes.
 */
class ShipmentStatusUpdated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment, public TrackingEvent $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->event->status->label()}: shipment {$this->shipment->tracking_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.shipment-status-updated',
            with: [
                'site' => Setting::values(),
                'trackUrl' => route('track.show', $this->shipment->tracking_number),
            ],
        );
    }
}
