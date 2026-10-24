<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    /** Booked online or at a branch; waiting for the courier to collect it. */
    case Booked = 'booked';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case AtHub = 'at_hub';
    case CustomsClearance = 'customs';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';
    /** Delayed or held, e.g. a wrong address or missing documents. */
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Booked => 'Pickup booked',
            self::PickedUp => 'Picked up',
            self::InTransit => 'In transit',
            self::AtHub => 'At hub',
            self::CustomsClearance => 'Customs clearance',
            self::OutForDelivery => 'Out for delivery',
            self::Delivered => 'Delivered',
            self::OnHold => 'On hold',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Flux badge colour in the admin.
     */
    public function color(): string
    {
        return match ($this) {
            self::Booked => 'zinc',
            self::PickedUp, self::InTransit, self::AtHub, self::CustomsClearance => 'blue',
            self::OutForDelivery => 'amber',
            self::Delivered => 'green',
            self::OnHold, self::Cancelled => 'red',
        };
    }

    /**
     * The step on the public progress bar (0 to 4), or null when the shipment is off the normal path.
     */
    public function step(): ?int
    {
        return match ($this) {
            self::Booked => 0,
            self::PickedUp => 1,
            self::InTransit, self::AtHub, self::CustomsClearance => 2,
            self::OutForDelivery => 3,
            self::Delivered => 4,
            self::OnHold, self::Cancelled => null,
        };
    }

    /**
     * Shipments that are still moving through the network.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::PickedUp, self::InTransit, self::AtHub, self::CustomsClearance, self::OutForDelivery, self::OnHold];
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])->all();
    }
}
