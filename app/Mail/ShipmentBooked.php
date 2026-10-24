<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Shipment;
use App\Support\Waybill;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the sender after they book a pickup on the website, with the waybill attached.
 */
class ShipmentBooked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Pickup booked: {$this->shipment->tracking_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.shipment-booked',
            with: [
                'site' => Setting::values(),
                'trackUrl' => route('track.show', $this->shipment->tracking_number),
                'waybillUrl' => Waybill::signedUrl($this->shipment),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => Waybill::pdf($this->shipment), Waybill::filename($this->shipment))
                ->withMime('application/pdf'),
        ];
    }
}
