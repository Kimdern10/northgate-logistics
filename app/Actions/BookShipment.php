<?php

namespace App\Actions;

use App\Enums\ShipmentStatus;
use App\Mail\NewWebsiteRequest;
use App\Mail\ShipmentBooked;
use App\Models\Setting;
use App\Models\Shipment;
use App\Services\Quote;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Creates a shipment from the website's "Book a pickup" form.
 */
class BookShipment
{
    /**
     * @param  array<string, mixed>  $details  validated form fields named like the shipment columns
     */
    public function handle(array $details, Quote $quote): Shipment
    {
        $shipment = DB::transaction(function () use ($details, $quote): Shipment {
            $pickupDate = CarbonImmutable::parse($details['pickup_date']);

            $shipment = Shipment::query()->create([
                ...$details,
                'status' => ShipmentStatus::Booked,
                'service_level' => $quote->level,
                'source' => 'website',
                'price_cents' => $quote->totalCents(),
                'estimated_delivery_date' => $quote->estimatedDeliveryDate($pickupDate),
            ]);

            $shipment->events()->create([
                'status' => ShipmentStatus::Booked,
                'location' => $shipment->origin(),
                'description' => 'Pickup booked online. A courier will collect the parcel on '.$pickupDate->format('D j M').'.',
                'happened_at' => now(),
            ]);

            return $shipment;
        });

        $this->sendEmails($shipment);

        return $shipment;
    }

    private function sendEmails(Shipment $shipment): void
    {
        $messages = array_filter([
            $shipment->sender_email ? [$shipment->sender_email, new ShipmentBooked($shipment)] : null,
            filled(Setting::get('notify_email')) ? [Setting::get('notify_email'), new NewWebsiteRequest($shipment)] : null,
        ]);

        foreach ($messages as [$email, $mailable]) {
            try {
                Mail::to($email)->send($mailable);
            } catch (\Throwable $exception) {
                // The booking is saved and shown on screen even when mail is down.
                report($exception);
            }
        }
    }
}
