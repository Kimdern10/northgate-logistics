<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\StripeCheckout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Stripe tells the site directly when a Checkout payment succeeds, so a payment is recorded even if the
 * customer closes the page before coming back. Each message is checked with the webhook signing secret.
 */
class StripeWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');
        abort_if($secret === '', 404);

        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), $secret);
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response('Invalid signature', 400);
        }

        if ($event->type === 'checkout.session.completed' && $event->data->object instanceof Session) {
            $payment = StripeCheckout::paymentFromSession($event->data->object);
            $shipment = $payment ? Shipment::query()->where('tracking_number', $payment['tracking_number'])->first() : null;

            if ($payment && $shipment) {
                StripeCheckout::recordPayment($shipment, $payment);
            }
        }

        return response('ok');
    }
}
