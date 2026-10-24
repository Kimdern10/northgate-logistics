<?php

use App\Mail\NewWebsiteRequest;
use App\Models\Enquiry;
use App\Models\Setting;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public string $type = 'general';

    public string $name = '';

    public string $company = '';

    public string $email = '';

    public string $phone = '';

    public string $monthly_volume = '';

    public string $message = '';

    /** Honeypot: hidden from people, filled in by spam bots. */
    public string $website = '';

    public bool $sent = false;

    public function mount(): void
    {
        $type = (string) request()->query('type', '');
        $this->type = array_key_exists($type, Enquiry::TYPES) ? $type : $this->type;
    }

    public function send(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(array_keys(Enquiry::TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'monthly_volume' => ['nullable', 'string', 'max:60'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $key = 'enquiry:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('form', 'You have sent a few messages already. Please wait a few minutes or call us.');

            return;
        }

        RateLimiter::hit($key, 600);

        $enquiry = Enquiry::query()->create([
            ...collect($validated)->except('monthly_volume')->all(),
            'details' => filled($validated['monthly_volume']) ? ['Monthly volume' => $validated['monthly_volume']] : null,
        ]);

        if (filled(Setting::get('notify_email'))) {
            try {
                Mail::to(Setting::get('notify_email'))->send(new NewWebsiteRequest($enquiry));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->reset(['name', 'company', 'email', 'phone', 'monthly_volume', 'message']);
        $this->sent = true;
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts::site', ['current' => 'contact', 'description' => 'Talk to our team about freight, warehousing, fulfillment or a business account.'])
            ->title('Contact us');
    }
}; ?>

<div>
    @php($site = Setting::values())

    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>Contact</p>
            <h1 data-reveal>Let's move your goods.</h1>
            <p class="lead" data-reveal>Ask for a custom freight quote, warehouse space or a fulfillment plan. We reply within one working day.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container contact-layout">
            <div class="card" data-reveal>
                @if ($sent)
                    <div class="success-state">
                        <span class="success-icon"><x-icon name="check" /></span>
                        <h2>Thank you, we've got your message.</h2>
                        <p>Someone from our team will get back to you within one working day.</p>
                        <button type="button" class="btn btn--ghost" wire:click="$set('sent', false)">Send another message</button>
                    </div>
                @else
                    <form wire:submit="send" class="stack" novalidate>
                        <label class="field">
                            <span>What can we help with?</span>
                            <select wire:model.live="type">
                                @foreach (Enquiry::TYPES as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="grid-2">
                            <x-field label="Your name" model="name" autocomplete="name" />
                            <x-field label="Company (optional)" model="company" autocomplete="organization" />
                            <x-field label="Email" model="email" type="email" autocomplete="email" />
                            <x-field label="Phone (optional)" model="phone" type="tel" autocomplete="tel" />
                        </div>
                        @if (in_array($type, ['warehousing', 'fulfillment', 'business', 'quote'], true))
                            <x-field label="Roughly how much per month? (optional)" model="monthly_volume" placeholder="e.g. 300 orders, 2 pallets, 1 container" />
                        @endif
                        <label class="field @error('message') field--invalid @enderror">
                            <span>Message</span>
                            <textarea rows="5" wire:model.blur="message" placeholder="Tell us what you ship, from where to where, and how often."></textarea>
                            @error('message') <small class="error">{{ $message }}</small> @enderror
                        </label>
                        <div class="hp" aria-hidden="true">
                            <label>Website <input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
                        </div>
                        @error('form') <p class="alert alert--error">{{ $message }}</p> @enderror
                        <div>
                            <button type="submit" class="btn btn--primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="send">Send message</span>
                                <span wire:loading wire:target="send">Sending…</span>
                            </button>
                        </div>
                    </form>
                @endif
            </div>

            <aside class="contact-aside" data-reveal>
                <div class="card contact-card">
                    <h2>Head office</h2>
                    <p><x-icon name="pin" /> {{ $site['address'] }}</p>
                    <p><x-icon name="phone" /> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $site['phone']) }}">{{ $site['phone'] }}</a></p>
                    <p><x-icon name="mail" /> <a href="mailto:{{ $site['email'] }}">{{ $site['email'] }}</a></p>
                    @if (filled($site['whatsapp']))
                        <a class="btn btn--whatsapp btn--block" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $site['whatsapp']) }}" target="_blank" rel="noopener">Chat on WhatsApp</a>
                    @endif
                </div>
                <div class="card contact-card">
                    <h2>Already shipping?</h2>
                    <p>Track a parcel or see our branches.</p>
                    <div class="btn-row">
                        <a class="btn btn--ghost btn--sm" href="{{ route('track') }}" wire:navigate.hover>Track</a>
                        <a class="btn btn--ghost btn--sm" href="{{ route('locations') }}" wire:navigate.hover>Locations</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</div>
