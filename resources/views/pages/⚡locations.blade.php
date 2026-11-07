<?php

use App\Models\Branch;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component {
    #[Url(except: '')]
    public string $search = '';

    /** all, hubs or warehouses */
    #[Url(except: 'all')]
    public string $type = 'all';

    /**
     * @return Collection<int, Branch>
     */
    #[Computed]
    public function allBranches(): Collection
    {
        return Branch::query()->published()->get();
    }

    /**
     * Branches matching the search box and type filter. The list is short, so it is filtered in PHP,
     * which also lets visitors search by country name ("United Kingdom") rather than code.
     *
     * @return Collection<int, Branch>
     */
    #[Computed]
    public function branches(): Collection
    {
        $words = array_filter(preg_split('/\s+/', mb_strtolower(trim($this->search))) ?: []);

        return $this->allBranches
            ->filter(fn (Branch $branch): bool => match ($this->type) {
                'hubs' => $branch->is_hub,
                'warehouses' => $branch->is_warehouse,
                default => true,
            })
            ->filter(function (Branch $branch) use ($words): bool {
                $haystack = mb_strtolower(implode(' ', [$branch->name, $branch->city, $branch->address, $branch->countryName(), $branch->country, $branch->kind()]));

                return collect($words)->every(fn (string $word): bool => str_contains($haystack, $word));
            })
            ->values();
    }

    public function render()
    {
        return $this->view()
            ->layout('layouts::site', ['current' => 'locations', 'description' => 'Find a branch, hub or warehouse near you. Drop off parcels or book a pickup.'])
            ->title('Branches & hubs');
    }
}; ?>

<div>
    <section class="page-hero">
        <div class="container">
            <p class="eyebrow">Locations</p>
            <h1>Branches, hubs and warehouses.</h1>
            <p class="lead">Drop off at any branch, or book a pickup and we'll come to you.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="map-wrap map-wrap--wide" wire:ignore>
                <div class="map" data-map='@json($this->allBranches->map->toMapPin()->values())' role="img" aria-label="Map of our branches and hubs"></div>
            </div>

            <div class="locations-toolbar">
                <label class="live-search">
                    <span class="sr-only">Search locations</span>
                    <x-icon name="search" />
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search by city, state, country or name" autocomplete="off">
                    <span wire:loading wire:target="search" class="spin-dot"></span>
                </label>
                <div class="chip-group" role="group" aria-label="Type of location">
                    @foreach (['all' => 'All', 'hubs' => 'Hubs', 'warehouses' => 'Warehouses'] as $value => $label)
                        <button type="button" wire:click="$set('type', '{{ $value }}')" @class(['chip', 'chip--active' => $type === $value]) aria-pressed="{{ $type === $value ? 'true' : 'false' }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <p class="muted results-count">{{ $this->branches->count() }} of {{ $this->allBranches->count() }} {{ str('location')->plural($this->allBranches->count()) }}</p>

            <div class="branch-grid live-results">
                @forelse ($this->branches as $branch)
                    <article class="card branch-card" wire:key="branch-{{ $branch->id }}">
                        <span class="badge @if ($branch->is_hub) badge--orange @endif">{{ $branch->kind() }}</span>
                        <h2>{{ $branch->name }}</h2>
                        <p><x-icon name="pin" /> {{ $branch->address }}, {{ $branch->city }}, {{ $branch->countryName() }}</p>
                        @if ($branch->phone)
                            <p><x-icon name="phone" /> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $branch->phone) }}">{{ $branch->phone }}</a></p>
                        @endif
                        @if ($branch->email)
                            <p><x-icon name="mail" /> <a href="mailto:{{ $branch->email }}">{{ $branch->email }}</a></p>
                        @endif
                        @if ($branch->opening_hours)
                            <p><x-icon name="clock" /> {{ $branch->opening_hours }}</p>
                        @endif
                    </article>
                @empty
                    <div class="card empty-state">
                        <x-icon name="pin" />
                        <h2>No locations match "{{ $search }}"</h2>
                        <p>We can still collect from your door. <a href="{{ route('ship') }}" wire:navigate.hover>Book a pickup</a> or <a href="{{ route('contact') }}" wire:navigate.hover>ask us</a>.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</div>
