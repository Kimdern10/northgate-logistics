<?php

namespace App\Services;

use App\Models\Shipment;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * Online card payment through Stripe Checkout: the customer types their card on Stripe's own page,
 * so card numbers never reach this website.
 */
class StripeCheckout
{
    public function isEnabled(): bool
    {
        return filled(config('services.stripe.secret'));
    }

    /**
     * Start a Checkout session for the shipment's price and return the Stripe page to send the customer to.
     * Stripe swaps {CHECKOUT_SESSION_ID} in the success address for the real session id.
     */
    public function createCheckoutUrl(Shipment $shipment, string $successUrl, string $cancelUrl): string
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'payment',
            'client_reference_id' => $shipment->tracking_number,
            'customer_email' => $shipment->sender_email ?: null,
            'metadata' => ['tracking_number' => $shipment->tracking_number],
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $shipment->price_cents,
                    'product_data' => [
                        'name' => 'Shipping '.$shipment->tracking_number,
                        'description' => $shipment->service_level->label().': '.$shipment->origin().' to '.$shipment->destination(),
                    ],
                ],
            ]],
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ]);

        return (string) $session->url;
    }

    /**
     * Ask Stripe whether a Checkout session was paid. Returns what is needed to record the payment, or null.
     *
     * @return array{tracking_number: string, reference: string, amount_cents: int}|null
     */
    public function paidSession(string $sessionId): ?array
    {
        return self::paymentFromSession($this->client()->checkout->sessions->retrieve($sessionId));
    }

    /**
     * @return array{tracking_number: string, reference: string, amount_cents: int}|null
     */
    public static function paymentFromSession(Session $session): ?array
    {
        if ($session->payment_status !== 'paid' || ! $session->client_reference_id) {
            return null;
        }

        return [
            'tracking_number' => (string) $session->client_reference_id,
            'reference' => (string) ($session->payment_intent ?? $session->id),
            'amount_cents' => (int) $session->amount_total,
        ];
    }

    /**
     * Record a confirmed Stripe payment once, and only when Stripe charged the full price.
     *
     * @param  array{tracking_number: string, reference: string, amount_cents: int}  $payment
     */
    public static function recordPayment(Shipment $shipment, array $payment): bool
    {
        if ($shipment->isPaid() || $payment['tracking_number'] !== $shipment->tracking_number || $payment['amount_cents'] < $shipment->price_cents) {
            return false;
        }

        $shipment->markPaid('card', $payment['reference']);

        return true;
    }

    protected function client(): StripeClient
    {
        return new StripeClient((string) config('services.stripe.secret'));
    }
}
