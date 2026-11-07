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
     * The delivery photo can show a doorstep or a person, so only staff, the sender and the receiver see it,
     * not someone who only follows the tracking number.
     */
    public function viewProofOfDelivery(User $user, Shipment $shipment): bool
    {
        return $user->is_admin
            || $user->sentShipment($shipment)
            || ($user->hasVerifiedEmail() && $shipment->receiver_email === strtolower($user->email));
    }

    /**
     * The waybill shows both full addresses, so only staff and the sender get it.
     */
    public function downloadWaybill(User $user, Shipment $shipment): bool
    {
        return $user->is_admin || $user->sentShipment($shipment);
    }
}
