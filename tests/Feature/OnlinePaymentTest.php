<?php

use App\Enums\PaymentStatus;
use App\Enums\ServiceLevel;
use App\Enums\Zone;
use App\Models\Rate;
use App\Models\Shipment;
use App\Services\StripeCheckout;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

/**
 * A stand-in for Stripe: no network, and the test decides what Stripe "says".
 */
function fakeStripe(?array $paidSession = null): object
{
    config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => 'whsec_test_fake']);

    $fake = new class extends StripeCheckout
    {
        public ?array $paid = null;

        public array $created = [];

        public function createCheckoutUrl(Shipment $shipment, string $successUrl, string $cancelUrl): string
        {
            $this->created[] = compact('successUrl', 'cancelUrl');

            return 'https://checkout.stripe.test/c/pay/cs_test_123';
        }

        public function paidSession(string $sessionId): ?array
        {
            return $sessionId === 'cs_test_123' ? $this->paid : null;
        }
    };

    $fake->paid = $paidSession;
    app()->instance(StripeCheckout::class, $fake);

    return $fake;
}

beforeEach(fn () => Mail::fake());

test('the pay online choice only shows when Stripe keys are set', function () {
    Livewire::test('pages::ship')->assertDontSee('Pay online now');

    fakeStripe();

    Livewire::test('pages::ship')->assertSee('Pay online now');
});

test('choosing to pay online sends the visitor to Stripe after booking', function () {
    fakeStripe();
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::Domestic]);

    $component = Livewire::test('pages::ship')
        ->set('sender_name', 'Megan Carter')->set('sender_email', 'megan@example.com')->set('sender_phone', '+1 (212) 555-0100')
        ->set('sender_address', '120 W 34th St')->set('sender_city', 'New York')
        ->set('receiver_name', 'Tom Hardy')->set('receiver_phone', '+1 (617) 555-0100')->set('receiver_address', '1 Main St')
        ->set('receiver_city', 'Boston')->set('receiver_country', 'US')
        ->set('contents', 'Books')->set('weight_lb', '3')->set('service_level', 'express')
        ->set('payment_choice', 'card')->set('agree', true)
        ->call('book')
        ->assertHasNoErrors();

    $shipment = Shipment::query()->sole();
    $component->assertRedirectContains('/pay/'.$shipment->tracking_number);

    $this->get(URL::temporarySignedRoute('pay', now()->addHour(), ['shipment' => $shipment->tracking_number]))
        ->assertRedirect('https://checkout.stripe.test/c/pay/cs_test_123');
});

test('a payment confirmed by Stripe marks the shipment paid', function () {
    $shipment = Shipment::factory()->create(['price_cents' => 4428]);
    fakeStripe(['tracking_number' => $shipment->tracking_number, 'reference' => 'pi_123', 'amount_cents' => 4428]);

    $this->get(route('pay.done', [$shipment->tracking_number, 'session_id' => 'cs_test_123']))
        ->assertRedirect()
        ->assertSessionHas('paid', true);

    $shipment->refresh();
    expect($shipment->payment_status)->toBe(PaymentStatus::Paid)
        ->and($shipment->payment_method)->toBe('card')
        ->and($shipment->payment_reference)->toBe('pi_123');
});

test('a made-up or unpaid session does not mark anything paid', function (?array $session, string $sessionId) {
    $shipment = Shipment::factory()->create(['price_cents' => 4428]);
    fakeStripe($session ? [...$session, 'tracking_number' => $shipment->tracking_number] : null);

    $this->get(route('pay.done', [$shipment->tracking_number, 'session_id' => $sessionId]))
        ->assertSessionHas('payment_failed', true);

    expect($shipment->fresh()->isPaid())->toBeFalse();
})->with([
    'unknown session' => [null, 'cs_fake'],
    'not paid' => [null, 'cs_test_123'],
    'paid less than the price' => [['reference' => 'pi_1', 'amount_cents' => 100], 'cs_test_123'],
]);

test('a session paid for another shipment cannot be reused', function () {
    $shipment = Shipment::factory()->create(['price_cents' => 4428]);
    fakeStripe(['tracking_number' => 'NT9999999999', 'reference' => 'pi_1', 'amount_cents' => 4428]);

    $this->get(route('pay.done', [$shipment->tracking_number, 'session_id' => 'cs_test_123']));

    expect($shipment->fresh()->isPaid())->toBeFalse();
});

test('the Stripe webhook records a payment only with a valid signature', function () {
    config(['services.stripe.secret' => 'sk_test_fake', 'services.stripe.webhook_secret' => 'whsec_test_fake']);
    $shipment = Shipment::factory()->create(['price_cents' => 4428]);

    $payload = json_encode([
        'id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_1', 'object' => 'checkout.session', 'payment_status' => 'paid',
            'client_reference_id' => $shipment->tracking_number, 'payment_intent' => 'pi_webhook', 'amount_total' => 4428,
        ]],
    ]);
    $sign = fn (string $secret): string => 't='.time().',v1='.hash_hmac('sha256', time().'.'.$payload, $secret);

    $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sign('wrong_secret'), 'CONTENT_TYPE' => 'application/json'], $payload)
        ->assertStatus(400);
    expect($shipment->fresh()->isPaid())->toBeFalse();

    $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $sign('whsec_test_fake'), 'CONTENT_TYPE' => 'application/json'], $payload)
        ->assertOk();
    expect($shipment->fresh()->payment_reference)->toBe('pi_webhook');
});

test('online payment pages are switched off without Stripe keys', function () {
    $shipment = Shipment::factory()->create();

    $this->get(URL::temporarySignedRoute('pay', now()->addHour(), ['shipment' => $shipment->tracking_number]))->assertNotFound();
    $this->get(route('pay.done', [$shipment->tracking_number, 'session_id' => 'cs_x']))->assertNotFound();
});
