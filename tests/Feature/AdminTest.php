<?php

use App\Enums\ServiceLevel;
use App\Enums\ShipmentStatus;
use App\Enums\Zone;
use App\Mail\ShipmentStatusUpdated;
use App\Models\Branch;
use App\Models\Enquiry;
use App\Models\Rate;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function admin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('guests are sent to the login page', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('logged in users who are not staff are refused', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('staff can open every admin page', function (string $route) {
    $shipment = Shipment::factory()->create();
    $shipment->events()->create(['status' => ShipmentStatus::Booked, 'location' => 'Lagos', 'happened_at' => now()]);
    Enquiry::factory()->create();
    Branch::factory()->create();
    Service::factory()->create();

    $parameters = str_contains($route, 'shipments.show') || str_contains($route, 'shipments.edit') ? [$shipment] : [];

    $this->actingAs(admin())->get(route($route, $parameters))->assertOk();
})->with([
    'admin.dashboard', 'admin.shipments.index', 'admin.shipments.create', 'admin.shipments.show', 'admin.shipments.edit',
    'admin.enquiries', 'admin.rates', 'admin.services', 'admin.branches', 'admin.testimonials', 'admin.faqs', 'admin.settings',
]);

test('logging in takes staff to the admin dashboard', function () {
    $user = admin();

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.dashboard', absolute: false));
});

test('public sign-up is switched off', function () {
    $this->get('/register')->assertNotFound();
});

test('staff can create a shipment with a tracking number', function () {
    Rate::factory()->create(['service_level' => ServiceLevel::Standard, 'zone' => Zone::International, 'base_fee_cents' => 2000, 'per_kg_cents' => 700]);
    Setting::put(['fuel_surcharge_percent' => 0]);

    $component = Livewire::actingAs(admin())->test('pages::admin.shipments.form')
        ->set('sender_name', 'Ibrahim Musa')
        ->set('sender_phone', '+234 803 000 0000')
        ->set('sender_address', '4 Marina')
        ->set('sender_city', 'Lagos')
        ->set('receiver_name', 'Laura Chen')
        ->set('receiver_phone', '+44 7700 900000')
        ->set('receiver_address', '1 Park Lane')
        ->set('receiver_city', 'London')
        ->set('contents', 'Books')
        ->set('weight_kg', '2')
        ->set('service_level', 'standard')
        ->set('initial_status', 'at_hub')
        ->call('calculate')
        ->assertSet('price', '34.00')
        ->call('save')
        ->assertHasNoErrors();

    $shipment = Shipment::query()->sole();

    $component->assertRedirect(route('admin.shipments.show', $shipment));
    expect($shipment->status)->toBe(ShipmentStatus::AtHub)
        ->and($shipment->price_cents)->toBe(3400)
        ->and($shipment->events()->count())->toBe(1);
});

test('staff can add a tracking update and the customer is emailed', function () {
    Mail::fake();
    $shipment = Shipment::factory()->create(['sender_email' => 'sender@example.com', 'receiver_email' => null]);
    $shipment->events()->create(['status' => ShipmentStatus::Booked, 'location' => 'Lagos', 'happened_at' => now()->subHour()]);

    Livewire::actingAs(admin())->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->assertSet('status', 'picked_up')
        ->set('location', 'Ikeja, Lagos')
        ->set('description', 'Collected by Musa.')
        ->call('addUpdate')
        ->assertHasNoErrors();

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::PickedUp);
    Mail::assertSent(ShipmentStatusUpdated::class, fn ($mail) => $mail->hasTo('sender@example.com'));
});

test('removing the newest update puts the shipment back to the previous status', function () {
    $shipment = Shipment::factory()->status(ShipmentStatus::PickedUp)->create();
    $shipment->events()->create(['status' => ShipmentStatus::Booked, 'location' => 'Lagos', 'happened_at' => now()->subHour()]);
    $latest = $shipment->events()->create(['status' => ShipmentStatus::PickedUp, 'location' => 'Lagos', 'happened_at' => now()]);

    Livewire::actingAs(admin())->test('pages::admin.shipments.show', ['shipment' => $shipment])
        ->call('deleteEvent', $latest->id);

    expect($shipment->fresh()->status)->toBe(ShipmentStatus::Booked);
});

test('staff can download any waybill', function () {
    $shipment = Shipment::factory()->create();

    $this->actingAs(admin())
        ->get(route('admin.shipments.waybill', $shipment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('rates can be changed and switched off', function () {
    Rate::factory()->create(['service_level' => ServiceLevel::Express, 'zone' => Zone::Domestic]);

    Livewire::actingAs(admin())->test('pages::admin.rates')
        ->set('rates.standard_international.active', true)
        ->set('rates.standard_international.base', '18.50')
        ->set('rates.standard_international.per_kg', '6')
        ->set('rates.standard_international.min_days', '4')
        ->set('rates.standard_international.max_days', '8')
        ->set('rates.express_domestic.active', false)
        ->set('fuel_surcharge_percent', '5')
        ->call('save')
        ->assertHasNoErrors();

    $rate = Rate::query()->where(['service_level' => 'standard', 'zone' => 'international'])->sole();

    expect($rate->base_fee_cents)->toBe(1850)
        ->and($rate->per_kg_cents)->toBe(600)
        ->and(Rate::query()->where(['service_level' => 'express', 'zone' => 'domestic'])->sole()->is_active)->toBeFalse()
        ->and(Setting::get('fuel_surcharge_percent'))->toBe('5');
});

test('an active rate needs its prices filled in', function () {
    Livewire::actingAs(admin())->test('pages::admin.rates')
        ->set('rates.express_regional.active', true)
        ->set('rates.express_regional.base', '')
        ->call('save')
        ->assertHasErrors('rates.express_regional.base');
});

test('staff can add a service with its own web address', function () {
    Livewire::actingAs(admin())->test('pages::admin.services')
        ->call('create')
        ->set('name', 'Cold Chain')
        ->set('icon', 'truck')
        ->set('summary', 'Temperature controlled.')
        ->set('description', 'For food and medicine.')
        ->set('features', "Reefer trucks\n\nLive temperature logs")
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::query()->sole();
    expect($service->slug)->toBe('cold-chain')
        ->and($service->features)->toBe(['Reefer trucks', 'Live temperature logs']);

    $this->get(route('services.show', $service))->assertOk()->assertSee('Live temperature logs');
});

test('site settings change what the website shows', function () {
    Livewire::actingAs(admin())->test('pages::admin.settings')
        ->set('business_name', 'Swift Cargo')
        ->set('hero_title', 'Cargo without the drama.')
        ->call('save')
        ->assertHasNoErrors();

    $this->get(route('home'))->assertSee('Swift Cargo')->assertSee('Cargo without the drama.');
});

test('enquiries can be marked as handled', function () {
    $enquiry = Enquiry::factory()->create();

    Livewire::actingAs(admin())->test('pages::admin.enquiries')
        ->call('setStatus', $enquiry->id, 'contacted');

    expect($enquiry->fresh()->status->value)->toBe('contacted');
});
