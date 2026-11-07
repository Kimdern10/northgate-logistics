<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * The shareable page for one tracking number (used in emails and on waybills).
 * The live search at /track is the Livewire page pages::track.
 */
class TrackingController extends Controller
{
    public function show(string $number): Response
    {
        $shipment = Shipment::findByTrackingNumber($number);
        $shipment?->load('events');

        return response()->view('pages.track-show', [
            'number' => Shipment::normaliseTrackingNumber($number),
            'shipment' => $shipment,
        ], $shipment ? 200 : 404);
    }

    /**
     * Add a shipment to the logged-in customer's account so they get its updates.
     */
    public function watch(Request $request, string $number): RedirectResponse
    {
        $shipment = Shipment::findByTrackingNumber($number);

        abort_if($shipment === null, 404);

        $request->user()->watchedShipments()->syncWithoutDetaching([$shipment->id]);

        return redirect()->route('account.shipments.show', $shipment);
    }
}
