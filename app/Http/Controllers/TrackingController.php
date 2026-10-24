<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TrackingController extends Controller
{
    /**
     * The tracking form posts here (GET) and lands on the shipment's own page.
     */
    public function search(Request $request): View|RedirectResponse
    {
        $number = Shipment::normaliseTrackingNumber((string) $request->query('number', ''));

        if ($number === '') {
            return view('pages.track', ['number' => '', 'shipment' => null]);
        }

        return redirect()->route('track.show', $number);
    }

    public function show(string $number): Response
    {
        $shipment = Shipment::findByTrackingNumber($number);
        $shipment?->load('events');

        return response()->view('pages.track', [
            'number' => Shipment::normaliseTrackingNumber($number),
            'shipment' => $shipment,
        ], $shipment ? 200 : 404);
    }
}
