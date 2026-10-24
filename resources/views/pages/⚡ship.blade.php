<?php

use App\Actions\BookShipment;
use App\Enums\ServiceLevel;
use App\Services\Quote;
use App\Models\Shipment;
use App\Services\RateCalculator;
use App\Support\Countries;
use App\Support\Money;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $sender_name = '';

    public string $sender_company = '';

    public string $sender_email = '';

    public string $sender_phone = '';

    public string $sender_address = '';

    public string $sender_city = '';

    public string $sender_country = 'NG';

    public string $receiver_name = '';

    public string $receiver_company = '';

    public string $receiver_email = '';

    public string $receiver_phone = '';

    public string $receiver_address = '';

    public string $receiver_city = '';

    public string $receiver_country = 'GB';

    public string $contents = '';

    public string $pieces = '1';

    public string $weight_kg = '';

    public string $length_cm = '';

    public string $width_cm = '';

    public string $height_cm = '';

    public string $declared_value = '';

    public string $service_level = 'standard';

    public string $pickup_date = '';

    public string $notes = '';

    public bool $agree = false;

    /** Honeypot: hidden from people, filled in by spam bots. */
    public string $website = '';

    /** Logged-in customers: pick from the address book. */
    public string $senderAddressId = '';

    public string $receiverAddressId = '';

    public bool $saveSender = false;

    public bool $saveReceiver = false;

    public function mount(): void
    {
        $query = request()->query();
        $codes = Countries::codes();

        if ($user = auth()->user()) {
            $this->sender_name = $user->name;
            $this->sender_email = $user->email;

            if ($default = $user->addresses()->where('is_default', true)->first()) {
                $this->senderAddressId = (string) $default->id;
                $this->fillFrom($default->toShipmentFields('sender_'));
            }

            $previous = Shipment::findByTrackingNumber((string) ($query['again'] ?? ''));

            if ($previous && $user->sentShipment($previous)) {
                $this->shipAgain($previous);

                return;
            }
        }

        $this->sender_country = in_array($query['from'] ?? null, $codes, true) ? $query['from'] : $this->sender_country;
        $this->receiver_country = in_array($query['to'] ?? null, $codes, true) ? $query['to'] : $this->receiver_country;
        $this->service_level = ServiceLevel::tryFrom((string) ($query['service'] ?? ''))?->value ?? $this->service_level;

        foreach (['weight' => 'weight_kg', 'l' => 'length_cm', 'w' => 'width_cm', 'h' => 'height_cm'] as $key => $property) {
            if (is_numeric($query[$key] ?? null)) {
                $this->{$property} = (string) $query[$key];
            }
        }

        $this->pickup_date = now()->addWeekday()->toDateString();
    }

    /**
     * @param  array<string, string>  $fields
     */
    private function fillFrom(array $fields): void
    {
        foreach ($fields as $field => $value) {
            if (property_exists($this, $field)) {
                $this->{$field} = $value;
            }
        }
    }

    /**
     * Copy a shipment this customer sent before, like "Ship again" on UPS and FedEx.
     */
    private function shipAgain(Shipment $previous): void
    {
        foreach (['sender', 'receiver'] as $prefix) {
            foreach (['name', 'company', 'email', 'phone', 'address', 'city', 'country'] as $field) {
                $this->{$prefix.'_'.$field} = (string) $previous->{$prefix.'_'.$field};
            }
        }

        $this->contents = $previous->contents;
        $this->pieces = (string) $previous->pieces;
        $this->weight_kg = (string) $previous->weight_kg;
        $this->length_cm = (string) $previous->length_cm;
        $this->width_cm = (string) $previous->width_cm;
        $this->height_cm = (string) $previous->height_cm;
        $this->service_level = $previous->service_level->value;
        $this->pickup_date = now()->addWeekday()->toDateString();
    }

    public function updatedSenderAddressId(string $id): void
    {
        if ($address = auth()->user()?->addresses()->find($id)) {
            $this->fillFrom($address->toShipmentFields('sender_'));
        }
    }

    public function updatedReceiverAddressId(string $id): void
    {
        if ($address = auth()->user()?->addresses()->find($id)) {
            $this->fillFrom($address->toShipmentFields('receiver_'));
        }
    }

    #[Computed]
    public function savedAddresses()
    {
        return auth()->user()?->addresses()->get() ?? collect();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $person = fn (bool $emailRequired): array => [
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'email' => [$emailRequired ? 'required' : 'nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', Rule::in(Countries::codes())],
        ];

        $rules = [];

        foreach ($person(true) as $field => $fieldRules) {
            $rules['sender_'.$field] = $fieldRules;
        }

        foreach ($person(false) as $field => $fieldRules) {
            $rules['receiver_'.$field] = $fieldRules;
        }

        return $rules + [
            'contents' => ['required', 'string', 'max:255'],
            'pieces' => ['required', 'integer', 'min:1', 'max:999'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'length_cm' => ['nullable', 'integer', 'min:1', 'max:600'],
            'width_cm' => ['nullable', 'integer', 'min:1', 'max:600'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:600'],
            'declared_value' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'service_level' => ['required', Rule::enum(ServiceLevel::class)],
            'pickup_date' => ['required', 'date', 'after_or_equal:today', 'before:'.now()->addDays(31)->toDateString()],
            'notes' => ['nullable', 'string', 'max:1000'],
            'agree' => ['accepted'],
            'saveSender' => ['boolean'],
            'saveReceiver' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'sender_name' => 'your name', 'sender_email' => 'your email', 'sender_phone' => 'your phone',
            'sender_address' => 'pickup address', 'sender_city' => 'pickup city', 'sender_country' => 'pickup country',
            'receiver_name' => "receiver's name", 'receiver_email' => "receiver's email", 'receiver_phone' => "receiver's phone",
            'receiver_address' => 'delivery address', 'receiver_city' => 'delivery city', 'receiver_country' => 'delivery country',
            'weight_kg' => 'weight', 'length_cm' => 'length', 'width_cm' => 'width', 'height_cm' => 'height',
            'agree' => 'terms',
        ];
    }

    /**
     * Live prices for the route and parcel typed so far.
     *
     * @return array<string, Quote>
     */
    #[Computed]
    public function quotes(): array
    {
        if (! is_numeric($this->weight_kg) || (float) $this->weight_kg <= 0 || (float) $this->weight_kg > 5000) {
            return [];
        }

        $dimension = fn (string $value): ?int => ctype_digit($value) && (int) $value <= 600 ? (int) $value : null;

        return app(RateCalculator::class)
            ->quotes($this->sender_country, $this->receiver_country, (float) $this->weight_kg, $dimension($this->length_cm), $dimension($this->width_cm), $dimension($this->height_cm))
            ->keyBy(fn (Quote $quote): string => $quote->level->value)
            ->all();
    }

    public function book(BookShipment $bookShipment)
    {
        $validated = $this->validate();

        if ($this->website !== '') {
            // Pretend it worked so bots learn nothing.
            return $this->redirectRoute('home');
        }

        $key = 'book-pickup:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('form', 'Too many bookings from your connection. Please wait a few minutes or call us.');

            return null;
        }

        $quote = $this->quotes[$validated['service_level']] ?? null;

        if (! $quote) {
            $this->addError('service_level', 'This service is not available on this route. Please pick another one.');

            return null;
        }

        RateLimiter::hit($key, 600);

        $details = collect($validated)
            ->except(['declared_value', 'service_level', 'agree', 'saveSender', 'saveReceiver'])
            ->map(fn ($value) => $value === '' ? null : $value)
            ->all();
        $details['declared_value_cents'] = Money::toCents($validated['declared_value'] ?? 0);

        $shipment = $bookShipment->handle($details, $quote, auth()->user());

        if ($user = auth()->user()) {
            foreach (['sender' => $this->saveSender, 'receiver' => $this->saveReceiver] as $prefix => $save) {
                if ($save) {
                    $user->addresses()->create(collect($details)
                        ->filter(fn ($value, string $key): bool => str_starts_with($key, $prefix.'_'))
                        ->mapWithKeys(fn ($value, string $key): array => [substr($key, strlen($prefix) + 1) => $value])
                        ->all());
                }
            }

            session()->flash('booked', true);

            return $this->redirectRoute('account.shipments.show', $shipment);
        }

        return $this->redirect(URL::temporarySignedRoute('ship.booked', now()->addDay(), ['shipment' => $shipment->tracking_number]));
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts::site', ['current' => 'ship', 'description' => 'Book a courier pickup online and get your tracking number and printable waybill straight away.'])
            ->title('Book a pickup');
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>Book a pickup</p>
            <h1 data-reveal>We collect from your door.</h1>
            <p class="lead" data-reveal>Fill in the form and you'll get a tracking number and a printable waybill straight away. You pay the courier at pickup.</p>
        </div>
    </section>

    <section class="section section--tight">
        <form class="container ship-layout" wire:submit="book" novalidate>
            <div class="ship-steps">
                @guest
                    <div class="account-hint" data-reveal>
                        <x-icon name="shield" />
                        <span>Ship often? <a href="{{ route('register') }}">Create a free account</a> or <a href="{{ route('login') }}">log in</a> to use your address book, ship again in one click and see all your shipments in one place.</span>
                    </div>
                @endguest

                <fieldset class="card step-card" data-reveal>
                    <legend><span class="step-num">1</span> Pickup from</legend>
                    @auth
                        <x-address-picker model="senderAddressId" save-model="saveSender" :addresses="$this->savedAddresses" />
                    @endauth
                    <div class="grid-2">
                        <x-field label="Your name" model="sender_name" autocomplete="name" />
                        <x-field label="Company (optional)" model="sender_company" autocomplete="organization" />
                        <x-field label="Email" model="sender_email" type="email" autocomplete="email" hint="We send the waybill and updates here." />
                        <x-field label="Phone" model="sender_phone" type="tel" autocomplete="tel" />
                    </div>
                    <x-field label="Pickup address" model="sender_address" autocomplete="street-address" />
                    <div class="grid-2">
                        <x-field label="City" model="sender_city" autocomplete="address-level2" />
                        <x-country-select label="Country" model="sender_country" live />
                    </div>
                </fieldset>

                <fieldset class="card step-card" data-reveal>
                    <legend><span class="step-num">2</span> Deliver to</legend>
                    @auth
                        <x-address-picker model="receiverAddressId" save-model="saveReceiver" :addresses="$this->savedAddresses" />
                    @endauth
                    <div class="grid-2">
                        <x-field label="Receiver's name" model="receiver_name" />
                        <x-field label="Company (optional)" model="receiver_company" />
                        <x-field label="Receiver's email (optional)" model="receiver_email" type="email" hint="They'll get tracking updates too." />
                        <x-field label="Receiver's phone" model="receiver_phone" type="tel" />
                    </div>
                    <x-field label="Delivery address" model="receiver_address" />
                    <div class="grid-2">
                        <x-field label="City" model="receiver_city" />
                        <x-country-select label="Country" model="receiver_country" live />
                    </div>
                </fieldset>

                <fieldset class="card step-card" data-reveal>
                    <legend><span class="step-num">3</span> Your parcel</legend>
                    <x-field label="What's inside?" model="contents" placeholder="e.g. Clothing samples, documents, phone accessories" />
                    <div class="grid-3">
                        <x-field label="Pieces" model="pieces" type="number" />
                        <x-field label="Total weight (kg)" model="weight_kg" type="number" step="0.1" live />
                        <x-field label="Value (USD, optional)" model="declared_value" type="number" hint="For customs." />
                    </div>
                    <div class="grid-3">
                        <x-field label="Length (cm)" model="length_cm" type="number" live />
                        <x-field label="Width (cm)" model="width_cm" type="number" live />
                        <x-field label="Height (cm)" model="height_cm" type="number" live />
                    </div>
                    <div class="grid-2">
                        <x-field label="Pickup date" model="pickup_date" type="date" :min="now()->toDateString()" />
                    </div>
                    <label class="field">
                        <span>Notes for the courier (optional)</span>
                        <textarea rows="3" wire:model="notes" placeholder="Gate code, best time to call, fragile items…"></textarea>
                    </label>
                </fieldset>
            </div>

            <aside class="ship-summary">
                <div class="card summary-card" data-reveal>
                    <h2>Choose a service</h2>
                    <p class="route-mini">{{ Countries::name($sender_country) }} <x-icon name="arrow-right" /> {{ Countries::name($receiver_country) }}</p>

                    <div class="service-options" role="radiogroup" aria-label="Service">
                        @forelse ($this->quotes as $level => $quote)
                            <label class="service-option" wire:key="option-{{ $level }}">
                                <input type="radio" name="service_level" value="{{ $level }}" wire:model.live="service_level">
                                <span class="service-option__body">
                                    <strong>{{ $quote->level->label() }}</strong>
                                    <small>{{ $quote->transitTime() }}</small>
                                </span>
                                <span class="service-option__price">{{ $quote->total() }}</span>
                            </label>
                        @empty
                            <p class="hint">Type the weight to see prices.</p>
                        @endforelse
                    </div>
                    @error('service_level') <small class="error">{{ $message }}</small> @enderror

                    @if ($chosen = $this->quotes[$service_level] ?? null)
                        <dl class="summary-lines">
                            <div><dt>Base fee</dt><dd>{{ Money::format($chosen->baseFeeCents) }}</dd></div>
                            <div><dt>{{ rtrim(rtrim(number_format($chosen->chargeableWeightKg, 1), '0'), '.') }} kg</dt><dd>{{ Money::format($chosen->weightChargeCents) }}</dd></div>
                            <div><dt>Fuel surcharge</dt><dd>{{ Money::format($chosen->fuelSurchargeCents) }}</dd></div>
                            <div class="summary-total"><dt>Estimated total</dt><dd>{{ $chosen->total() }}</dd></div>
                        </dl>
                    @endif

                    <div class="hp" aria-hidden="true">
                        <label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <label class="check">
                        <input type="checkbox" wire:model="agree">
                        <span>The parcel has no dangerous or banned items, and I accept the <a href="{{ route('privacy') }}" target="_blank">privacy policy</a>.</span>
                    </label>
                    @error('agree') <small class="error">Please tick the box to continue.</small> @enderror
                    @error('form') <p class="alert alert--error">{{ $message }}</p> @enderror
                    @if ($errors->isNotEmpty() && ! $errors->has('form'))
                        <p class="alert alert--error">Please check the highlighted fields.</p>
                    @endif

                    <button type="submit" class="btn btn--primary btn--block" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="book">Book pickup</span>
                        <span wire:loading wire:target="book">Booking…</span>
                    </button>
                    <p class="small-print">Pay the courier by card, transfer or cash at pickup.</p>
                </div>
            </aside>
        </form>
    </section>
</div>
