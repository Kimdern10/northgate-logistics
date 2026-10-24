<?php

namespace App\Actions;

use App\Enums\ShipmentStatus;
use App\Mail\ShipmentStatusUpdated;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Adds a line to a shipment's timeline, moves the shipment to that status when it is the
 * newest update, and emails the sender and receiver when the status changed.
 */
class RecordTrackingUpdate
{
    /**
     * How many people were emailed on the last call.
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

        if ($notify && $shipment->status === $status && $previousStatus !== $status) {
            $this->notify($shipment, $event);
        }

        return $event;
    }

    private function notify(Shipment $shipment, TrackingEvent $event): void
    {
        foreach ($shipment->notifiableEmails() as $email) {
            try {
                Mail::to($email)->send(new ShipmentStatusUpdated($shipment, $event));
                $this->emailed++;
            } catch (\Throwable $exception) {
                // A mail server problem must not lose the tracking update itself.
                report($exception);
            }
        }
    }
}
