<?php

use App\Mail\NewWebsiteRequest;
use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    Setting::put(['notify_email' => 'ops@northgate.example']);
});

function contactForm(): Testable
{
    return Livewire::test('pages::contact')
        ->set('type', 'warehousing')
        ->set('name', 'Robert Ade')
        ->set('email', 'robert@example.com')
        ->set('monthly_volume', '20 pallets')
        ->set('message', 'We need 20 pallet spaces near Lagos airport.');
}

test('an enquiry is saved and sent to the office', function () {
    contactForm()->call('send')->assertHasNoErrors()->assertSet('sent', true);

    $enquiry = Enquiry::query()->sole();

    expect($enquiry->type)->toBe('warehousing')
        ->and($enquiry->details)->toBe(['Monthly volume' => '20 pallets'])
        ->and($enquiry->status->value)->toBe('new');

    Mail::assertSent(NewWebsiteRequest::class, fn ($mail) => $mail->hasTo('ops@northgate.example') && $mail->hasReplyTo('robert@example.com'));
});

test('the type can be picked from a link', function () {
    Livewire::withQueryParams(['type' => 'fulfillment'])->test('pages::contact')->assertSet('type', 'fulfillment');
    Livewire::withQueryParams(['type' => 'nonsense'])->test('pages::contact')->assertSet('type', 'general');
});

test('the form needs a name, email and message', function () {
    Livewire::test('pages::contact')
        ->set('message', 'short')
        ->call('send')
        ->assertHasErrors(['name', 'email', 'message']);
});

test('spam bots are thanked but nothing is saved', function () {
    contactForm()->set('website', 'spam')->call('send')->assertSet('sent', true);

    expect(Enquiry::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('one connection can only send a few messages in a row', function () {
    foreach (range(1, 3) as $attempt) {
        contactForm()->call('send');
    }

    contactForm()->call('send')->assertHasErrors('form');

    expect(Enquiry::query()->count())->toBe(3);
});
