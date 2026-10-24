<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Full details and timeline in the customer's account.
     */
    public function view(User $user, Shipment $shipment): bool
    {
        return $user->is_admin || $user->canSeeShipment($shipment);
    }

    /**
     * The waybill shows both full addresses, so only staff and the sender get it.
     */
    public function downloadWaybill(User $user, Shipment $shipment): bool
    {
        return $user->is_admin || $user->sentShipment($shipment);
    }
}
