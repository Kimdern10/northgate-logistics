<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Support\Waybill;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WaybillController extends Controller
{
    /**
     * The "your pickup is booked" page, reached by a signed link right after booking.
     */
    public function booked(Shipment $shipment): View
    {
        return view('pages.booked', [
            'shipment' => $shipment,
            'waybillUrl' => Waybill::signedUrl($shipment),
        ]);
    }

    /**
     * Signed link from the booking page or confirmation email.
     */
    public function download(Shipment $shipment): Response
    {
        return Waybill::download($shipment);
    }

    public function admin(Shipment $shipment): Response
    {
        return Waybill::download($shipment);
    }
}
