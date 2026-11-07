<?php

namespace App\Support;

use App\Models\Setting;
use App\Models\Shipment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The customer's receipt (or invoice, while unpaid) for a shipment's shipping charge.
 */
class Receipt
{
    public static function number(Shipment $shipment): string
    {
        return 'R-'.$shipment->tracking_number;
    }

    public static function download(Shipment $shipment): Response
    {
        return Pdf::loadView('pdf.receipt', [
            'shipment' => $shipment,
            'site' => Setting::values(),
            'number' => self::number($shipment),
        ])->setPaper('a4')->download(self::filename($shipment));
    }

    public static function filename(Shipment $shipment): string
    {
        return ($shipment->isPaid() ? 'receipt-' : 'invoice-').$shipment->tracking_number.'.pdf';
    }

    public static function signedUrl(Shipment $shipment): string
    {
        return URL::temporarySignedRoute('receipt', now()->addDays(60), ['shipment' => $shipment->tracking_number]);
    }
}
