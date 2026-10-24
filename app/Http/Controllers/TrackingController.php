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
     * Most tracking numbers looked up in one go, like UPS and FedEx allow.
     */
    public const int MAX_AT_ONCE = 10;

    /**
     * The tracking form posts here (GET). One number goes to its own page; several show a summary.
     */
    public function search(Request $request): View|RedirectResponse
    {
        $input = (string) $request->query('number', '');

        // Pick out every "NG + 10 digits", even typed with spaces or dashes inside ("NG 482-730 1956").
        preg_match_all('/n\s*g(?:[\s\-]*\d){10}/i', $input, $matches);

        $numbers = collect($matches[0] ?: [$input])
            ->map(fn (string $number): string => Shipment::normaliseTrackingNumber($number))
            ->filter()
            ->unique()
            ->values();

        if ($numbers->isEmpty()) {
            return view('pages.track', ['number' => '', 'shipment' => null]);
        }

        if ($numbers->count() === 1) {
            return redirect()->route('track.show', $numbers->first());
        }

        $found = Shipment::query()->whereIn('tracking_number', $numbers->take(self::MAX_AT_ONCE))->get()->keyBy('tracking_number');

        return view('pages.track-many', [
            'numbers' => $numbers->take(self::MAX_AT_ONCE),
            'shipments' => $found,
            'skipped' => max(0, $numbers->count() - self::MAX_AT_ONCE),
        ]);
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

    /**
     * Add a shipment to the logged-in customer's account so they get its updates.
     */
    public function watch(Request $request, string $number): RedirectResponse
    {
        $shipment = Shipment::findByTrackingNumber($number);

        abort_unless($shipment, 404);

        $request->user()->watchedShipments()->syncWithoutDetaching([$shipment->id]);

        return redirect()->route('account.shipments.show', $shipment);
    }
}
