<?php

use App\Models\Shipment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    /** What the visitor typed: one or more tracking numbers. Kept in the address bar so results can be shared. */
    #[Url(as: 'number', except: '')]
    public string $number = '';

    public bool $limited = false;

    /**
     * The tidied tracking numbers to look up.
     *
     * @return list<string>
     */
    #[Computed]
    public function numbers(): array
    {
        return Shipment::parseTrackingNumbers($this->number);
    }

    /**
     * Shipments found, keyed by tracking number. Lookups are limited per visitor so numbers can't be guessed in bulk.
     *
     * @return Collection<string, Shipment>
     */
    #[Computed]
    public function shipments(): Collection
    {
        $this->limited = false;
        $numbers = array_slice($this->numbers, 0, Shipment::MAX_TRACKED_AT_ONCE);

        if ($numbers === [] || ! $this->looksComplete()) {
            return collect();
        }

        $key = 'track-live:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 60)) {
            $this->limited = true;

            return collect();
        }

        RateLimiter::hit($key, 60);

        return Shipment::query()
            ->whereIn('tracking_number', $numbers)
            ->with('events')
            ->get()
            ->keyBy('tracking_number');
    }

    /**
     * Wait until at least one full tracking number (12 characters) is typed before saying "not found".
     */
    public function looksComplete(): bool
    {
        return collect($this->numbers)->contains(fn (string $number): bool => strlen($number) >= 12);
    }

    public function render()
    {
        $single = count($this->numbers) === 1 ? $this->shipments->first() : null;

        return $this->view()
            ->layout('layouts::site', ['current' => 'track', 'description' => 'Track your parcel by tracking number and see every update from pickup to delivery.'])
            ->title($single ? 'Tracking '.$single->tracking_number : 'Track a shipment');
    }
}; ?>

<div>
    <section class="page-hero page-hero--track">
        <div class="container">
            <p class="eyebrow">Tracking</p>
            <h1>Where's my shipment?</h1>
            <form class="track-inline" wire:submit.prevent>
                <label class="sr-only" for="track-number">Tracking numbers</label>
                <input id="track-number" wire:model.live.debounce.400ms="number" placeholder="Tracking number, e.g. NT4827301956" autocomplete="off" autofocus>
                <span class="btn btn--primary track-inline__icon" aria-hidden="true">
                    <span wire:loading.remove wire:target="number"><x-icon name="search" /></span>
                    <span wire:loading wire:target="number" class="spin-dot"></span>
                    Track
                </span>
            </form>
            <p class="hint">Results appear as you type. Track up to {{ Shipment::MAX_TRACKED_AT_ONCE }} at once: separate the numbers with commas or spaces.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container live-results" aria-live="polite">
            @php($numbers = $this->numbers)
            @php($found = $this->shipments)

            @if ($this->limited)
                <p class="alert alert--error">Too many lookups. Please wait a minute and try again.</p>
            @elseif ($numbers === [])
                <div class="track-help">
                    <div class="card"><x-icon name="search" /><h2>Your tracking number</h2><p>It's on your waybill and in your booking email. It starts with NT and has 12 characters, e.g. NT4827301956.</p></div>
                    <div class="card"><x-icon name="mail" /><h2>Email updates</h2><p>We email the sender (and the receiver, if we have their email) every time the status changes.</p></div>
                    <div class="card"><x-icon name="phone" /><h2>Need help?</h2><p>Call {{ \App\Models\Setting::get('phone') }} or <a href="{{ route('contact') }}" wire:navigate.hover>send us a message</a>.</p></div>
                </div>
            @elseif (! $this->looksComplete())
                <p class="keep-typing"><x-icon name="search" /> Keep typing… tracking numbers have 12 characters, like NT4827301956.</p>
            @elseif (count($numbers) === 1)
                @if ($shipment = $found->first())
                    @include('partials.tracking-result', ['shipment' => $shipment])
                @else
                    <div class="card empty-state" wire:key="missing-{{ $numbers[0] }}">
                        <x-icon name="alert" />
                        <h2>We couldn't find {{ $numbers[0] }}</h2>
                        <p>Check the number on your waybill or booking email and try again. New bookings can take a few minutes to appear.</p>
                        <a class="btn btn--ghost" href="{{ route('contact') }}" wire:navigate.hover>Ask our team</a>
                    </div>
                @endif
            @else
                <div class="multi-track">
                    <h2 class="multi-title">{{ count($numbers) }} shipments</h2>
                    @if (count($numbers) > Shipment::MAX_TRACKED_AT_ONCE)
                        <p class="alert alert--error">You can track up to {{ Shipment::MAX_TRACKED_AT_ONCE }} at once. {{ count($numbers) - Shipment::MAX_TRACKED_AT_ONCE }} more {{ str('number')->plural(count($numbers) - Shipment::MAX_TRACKED_AT_ONCE) }} were left out.</p>
                    @endif

                    @foreach (array_slice($numbers, 0, Shipment::MAX_TRACKED_AT_ONCE) as $trackingNumber)
                        @php($shipment = $found[$trackingNumber] ?? null)
                        <a @class(['card', 'multi-row', 'multi-row--missing' => ! $shipment]) href="{{ route('track.show', $trackingNumber) }}" wire:key="row-{{ $trackingNumber }}">
                            <div>
                                <strong class="multi-number">{{ $trackingNumber }}</strong>
                                <span class="muted">{{ $shipment ? $shipment->origin().' → '.$shipment->destination() : 'Not found. Check the number.' }}</span>
                            </div>
                            @if ($shipment)
                                <div class="multi-meta">
                                    <span class="status-pill status-pill--{{ $shipment->status->value }}">{{ $shipment->status->label() }}</span>
                                    <small class="muted">
                                        @if ($shipment->delivered_at)
                                            Delivered {{ $shipment->delivered_at->format('j M') }}
                                        @elseif ($shipment->estimated_delivery_date)
                                            Expected {{ $shipment->estimated_delivery_date->format('j M') }}
                                        @endif
                                    </small>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</div>
