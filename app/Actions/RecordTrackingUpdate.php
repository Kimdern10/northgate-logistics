<?php

namespace App\Actions;

use App\Enums\ShipmentStatus;
use App\Mail\ShipmentStatusUpdated;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Support\DeliveryPhoto;
use App\Support\SendAfterResponse;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Adds a line to a shipment's timeline, moves the shipment to that status when it is the
 * newest update, and emails the sender and receiver when the status changed.
 */
class RecordTrackingUpdate
{
    /**
     * How many people are being emailed about the last update.
     */
    public int $emailed = 0;

    public function handle(
        Shipment $shipment,
        ShipmentStatus $status,
        string $location,
        ?string $description = null,
        ?CarbonInterface $happenedAt = null,
        bool $notify = true,
        ?string $receivedBy = null,
        ?UploadedFile $deliveryPhoto = null,
    ): TrackingEvent {
        $this->emailed = 0;
        $previousStatus = $shipment->status;

        $event = DB::transaction(function () use ($shipment, $status, $location, $description, $happenedAt, $receivedBy): TrackingEvent {
            $event = $shipment->events()->create([
                'status' => $status,
                'location' => $location,
                'description' => $description,
                'happened_at' => $happenedAt ?? now(),
            ]);

            // An update typed in late for an earlier date stays in the timeline but does not change the status.
            if ($shipment->events()->first()?->is($event)) {
                $shipment->status = $status;

                if ($status === ShipmentStatus::Delivered) {
                    $shipment->delivered_at = $event->happened_at;
                    $shipment->received_by = $receivedBy ?: $shipment->received_by;
                }

                $shipment->save();
            }

            return $event;
        });

        if ($deliveryPhoto && $status === ShipmentStatus::Delivered) {
            DeliveryPhoto::store($shipment, $deliveryPhoto);
        }

        if ($notify && $shipment->status === $status && $previousStatus !== $status) {
            $this->notify($shipment, $event);
        }

        return $event;
    }

    private function notify(Shipment $shipment, TrackingEvent $event): void
    {
        // Sent after the page responds; a mail server problem never loses the tracking update itself.
        foreach ($shipment->notifiableEmails() as $email) {
            SendAfterResponse::mail($email, new ShipmentStatusUpdated($shipment, $event));
            $this->emailed++;
        }
    }
}
