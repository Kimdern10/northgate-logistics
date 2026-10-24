<?php

use App\Services\Quote;
use App\Services\RateCalculator;
use App\Support\Countries;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url(as: 'from')]
    public string $fromCountry = 'US';

    #[Url(as: 'to')]
    public string $toCountry = 'GB';

    #[Url]
    public string $weight = '2';

    #[Url(as: 'l')]
    public string $length = '';

    #[Url(as: 'w')]
    public string $width = '';

    #[Url(as: 'h')]
    public string $height = '';

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'fromCountry' => ['required', Rule::in(Countries::codes())],
            'toCountry' => ['required', Rule::in(Countries::codes())],
            'weight' => ['required', 'numeric', 'min:0.1', 'max:5000'],
            'length' => ['nullable', 'integer', 'min:1', 'max:600'],
            'width' => ['nullable', 'integer', 'min:1', 'max:600'],
            'height' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];
    }

    public function updated(string $property): void
    {
        $this->validateOnly($property);
    }

    /**
     * @return Collection<int, Quote>
     */
    #[Computed]
    public function quotes(): Collection
    {
        if ($this->getErrorBag()->isNotEmpty() || validator($this->all(), $this->rules())->fails()) {
            return collect();
        }

        return app(RateCalculator::class)->quotes(
            $this->fromCountry,
            $this->toCountry,
            (float) $this->weight,
            $this->length !== '' ? (int) $this->length : null,
            $this->width !== '' ? (int) $this->width : null,
            $this->height !== '' ? (int) $this->height : null,
        );
    }

    public function swap(): void
    {
        [$this->fromCountry, $this->toCountry] = [$this->toCountry, $this->fromCountry];
    }

    /**
     * Link to the booking form with this quote filled in.
     */
    public function bookUrl(string $level): string
    {
        return route('ship', array_filter([
            'from' => $this->fromCountry,
            'to' => $this->toCountry,
            'weight' => $this->weight,
            'l' => $this->length,
            'w' => $this->width,
            'h' => $this->height,
            'service' => $level,
        ]));
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts::site', ['current' => 'quote', 'description' => 'Get an instant price for air express, standard air or economy freight to 40+ countries.'])
            ->title('Shipping quote calculator');
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow" data-reveal>Instant quote</p>
            <h1 data-reveal>What will it cost to ship?</h1>
            <p class="lead" data-reveal>Enter where it's going and how heavy it is. Prices include the fuel surcharge and are in US dollars.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container quote-layout">
            <form class="card quote-form" wire:submit.prevent data-reveal>
                <div class="route-fields">
                    <label class="field">
                        <span>From</span>
                        <select wire:model.live="fromCountry">
                            @foreach (Countries::options() as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button type="button" class="swap-btn" wire:click="swap" aria-label="Swap from and to">
                        <x-icon name="swap" />
                    </button>
                    <label class="field">
                        <span>To</span>
                        <select wire:model.live="toCountry">
                            @foreach (Countries::options() as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="field">
                    <span>Weight (kg)</span>
                    <input type="number" inputmode="decimal" step="0.1" min="0.1" wire:model.live.debounce.400ms="weight">
                    @error('weight') <small class="error">{{ $message }}</small> @enderror
                </label>

                <fieldset class="field">
                    <legend>Box size in cm <em>(optional)</em></legend>
                    <div class="dims">
                        <input type="number" inputmode="numeric" placeholder="Length" aria-label="Length in cm" wire:model.live.debounce.400ms="length">
                        <input type="number" inputmode="numeric" placeholder="Width" aria-label="Width in cm" wire:model.live.debounce.400ms="width">
                        <input type="number" inputmode="numeric" placeholder="Height" aria-label="Height in cm" wire:model.live.debounce.400ms="height">
                    </div>
                    @error('length') <small class="error">{{ $message }}</small> @enderror
                    @error('width') <small class="error">{{ $message }}</small> @enderror
                    @error('height') <small class="error">{{ $message }}</small> @enderror
                    <small class="hint">Big, light boxes are charged by size: length × width × height ÷ {{ \App\Models\Setting::int('volumetric_divisor') ?: 5000 }}.</small>
                </fieldset>
            </form>

            <div class="quote-results" aria-live="polite">
                <div class="quote-route" data-reveal>
                    <strong>{{ Countries::name($fromCountry) }}</strong>
                    <span class="route-line"><x-icon name="plane" /></span>
                    <strong>{{ Countries::name($toCountry) }}</strong>
                </div>

                <div wire:loading.delay class="quote-loading">Updating prices…</div>

                @forelse ($this->quotes as $quote)
                    <article class="quote-card @if ($loop->first) quote-card--featured @endif" wire:key="quote-{{ $quote->level->value }}">
                        <div>
                            <h3>{{ $quote->level->label() }}</h3>
                            <p>{{ $quote->level->summary() }}</p>
                            <p class="quote-meta">
                                <span><x-icon name="clock" /> {{ $quote->transitTime() }}</span>
                                <span><x-icon name="box" /> Charged on {{ rtrim(rtrim(number_format($quote->chargeableWeightKg, 1), '0'), '.') }} kg</span>
                            </p>
                        </div>
                        <div class="quote-price">
                            <strong>{{ $quote->total() }}</strong>
                            <a class="btn btn--primary btn--sm" href="{{ $this->bookUrl($quote->level->value) }}" wire:navigate.hover>Book pickup</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state">
                        <x-icon name="box" />
                        <p>Fill in a valid weight to see prices. For cargo over 5 tonnes, <a href="{{ route('contact', ['type' => 'quote']) }}" wire:navigate.hover>ask us for a custom quote</a>.</p>
                    </div>
                @endforelse

                <p class="small-print">Final price is confirmed when the courier weighs your parcel. Duties and taxes at the destination are not included.</p>
            </div>
        </div>
    </section>
</div>
