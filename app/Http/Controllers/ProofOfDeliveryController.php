<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Support\DeliveryPhoto;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shows the proof of delivery photo to staff, the sender and the receiver.
 */
class ProofOfDeliveryController extends Controller
{
    public function __invoke(Shipment $shipment): StreamedResponse
    {
        Gate::authorize('viewProofOfDelivery', $shipment);

        return DeliveryPhoto::response($shipment);
    }
}
