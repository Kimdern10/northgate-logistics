<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The commercial invoice customs officers ask for when a parcel crosses a border:
 * who sends it, who receives it, and what is inside with its value.
 */
class CommercialInvoice
{
    public static function isNeeded(Shipment $shipment): bool
    {
        return $shipment->crossesBorder() && $shipment->items()->exists();
    }

    public static function pdf(Shipment $shipment): string
    {
        return self::document($shipment)->output();
    }

    public static function download(Shipment $shipment): Response
    {
        abort_unless(self::isNeeded($shipment), 404);

        return self::document($shipment)->download(self::filename($shipment));
    }

    public static function filename(Shipment $shipment): string
    {
        return 'commercial-invoice-'.$shipment->tracking_number.'.pdf';
    }

    /**
     * A private link for the person who booked, like the waybill link.
     */
    public static function signedUrl(Shipment $shipment): string
    {
        return URL::temporarySignedRoute('commercial-invoice', now()->addDays(60), ['shipment' => $shipment->tracking_number]);
    }

    private static function document(Shipment $shipment): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.commercial-invoice', [
            'shipment' => $shipment->loadMissing('items'),
            'site' => Setting::values(),
        ])->setPaper('a4');
    }
}
