<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the office about a new pickup booking or enquiry from the website.
 */
class NewWebsiteRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shipment|Enquiry $request) {}

    public function envelope(): Envelope
    {
        $subject = $this->request instanceof Shipment
            ? "New pickup booking {$this->request->tracking_number}"
            : "New enquiry: {$this->request->typeLabel()} from {$this->request->name}";

        return new Envelope(
            subject: $subject,
            replyTo: $this->request instanceof Enquiry ? [$this->request->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-website-request',
            with: [
                'isShipment' => $this->request instanceof Shipment,
                'adminUrl' => $this->request instanceof Shipment
                    ? route('admin.shipments.show', $this->request)
                    : route('admin.enquiries'),
            ],
        );
    }
}
