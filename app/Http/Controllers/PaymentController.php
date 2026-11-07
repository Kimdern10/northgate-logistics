<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\StripeCheckout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;

/**
 * Paying a shipment online with Stripe Checkout, from the booking page or the customer's account.
 */
class PaymentController extends Controller
{
    public function __construct(private StripeCheckout $stripe) {}

    /**
     * Signed link from the "Pickup booked" page, for visitors without an account.
     */
    public function start(Shipment $shipment): RedirectResponse
    {
        return $this->sendToStripe($shipment, $this->bookedPage($shipment));
    }

    public function startFromAccount(Shipment $shipment): RedirectResponse
    {
        Gate::authorize('downloadWaybill', $shipment);

        return $this->sendToStripe($shipment, route('account.shipments.show', $shipment));
    }

    /**
     * Stripe sends the customer back here. The payment is checked with Stripe itself, never trusted from the address.
     */
    public function done(Request $request, Shipment $shipment): RedirectResponse
    {
        abort_unless($this->stripe->isEnabled(), 404);

        $payment = $this->stripe->paidSession((string) $request->query('session_id', ''));
        $paid = $shipment->isPaid() || ($payment && StripeCheckout::recordPayment($shipment, $payment));

        $back = $request->user()?->can('downloadWaybill', $shipment)
            ? redirect()->route('account.shipments.show', $shipment)
            : redirect()->to($this->bookedPage($shipment));

        return $paid
            ? $back->with('paid', true)
            : $back->with('payment_failed', true);
    }

    private function sendToStripe(Shipment $shipment, string $cancelUrl): RedirectResponse
    {
        abort_unless($this->stripe->isEnabled(), 404);

        if ($shipment->isPaid()) {
            return redirect()->to($cancelUrl)->with('paid', true);
        }

        try {
            $url = $this->stripe->createCheckoutUrl($shipment, route('pay.done', $shipment->tracking_number), $cancelUrl);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->to($cancelUrl)->with('payment_failed', true);
        }

        return redirect()->away($url);
    }

    private function bookedPage(Shipment $shipment): string
    {
        return URL::temporarySignedRoute('ship.booked', now()->addDay(), ['shipment' => $shipment->tracking_number]);
    }
}
