<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The printable PDF waybill (shipping label and receipt) for a shipment.
 */
class Waybill
{
    public static function pdf(Shipment $shipment): string
    {
        return self::document($shipment)->output();
    }

    public static function download(Shipment $shipment): Response
    {
        return self::document($shipment)->download(self::filename($shipment));
    }

    public static function filename(Shipment $shipment): string
    {
        return 'waybill-'.$shipment->tracking_number.'.pdf';
    }

    /**
     * A private link for the person who booked; the waybill shows full names, phones and addresses.
     */
    public static function signedUrl(Shipment $shipment): string
    {
        return URL::temporarySignedRoute('waybill', now()->addDays(60), ['shipment' => $shipment->tracking_number]);
    }

    private static function document(Shipment $shipment): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView('pdf.waybill', [
            'shipment' => $shipment,
            'site' => Setting::values(),
            'barcode' => Barcode::dataUri($shipment->tracking_number),
        ])->setPaper('a4');
    }
}
