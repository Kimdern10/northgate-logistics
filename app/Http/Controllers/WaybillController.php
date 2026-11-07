<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Support\CommercialInvoice;
use App\Support\Waybill;
use Illuminate\Support\Facades\Gate;
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
            'invoiceUrl' => CommercialInvoice::isNeeded($shipment) ? CommercialInvoice::signedUrl($shipment) : null,
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

    public function account(Shipment $shipment): Response
    {
        Gate::authorize('downloadWaybill', $shipment);

        return Waybill::download($shipment);
    }

    /**
     * Signed link from the booking page or confirmation email.
     */
    public function commercialInvoice(Shipment $shipment): Response
    {
        return CommercialInvoice::download($shipment);
    }

    public function accountCommercialInvoice(Shipment $shipment): Response
    {
        Gate::authorize('downloadWaybill', $shipment);

        return CommercialInvoice::download($shipment);
    }

    public function adminCommercialInvoice(Shipment $shipment): Response
    {
        return CommercialInvoice::download($shipment);
    }
}
