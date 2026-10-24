<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Site settings')] class extends Component {
    public string $business_name = '';

    public string $tagline = '';

    public string $email = '';

    public string $phone = '';

    public string $whatsapp = '';

    public string $address = '';

    public string $notify_email = '';

    public string $hero_title = '';

    public string $hero_text = '';

    public string $stat_years = '';

    public string $stat_countries = '';

    public string $stat_deliveries = '';

    public string $stat_warehouse_sqm = '';

    /**
     * @var list<string>
     */
    private const array KEYS = [
        'business_name', 'tagline', 'email', 'phone', 'whatsapp', 'address', 'notify_email',
        'hero_title', 'hero_text', 'stat_years', 'stat_countries', 'stat_deliveries', 'stat_warehouse_sqm',
    ];

    public function mount(): void
    {
        $values = Setting::values();

        foreach (self::KEYS as $key) {
            $this->{$key} = $values[$key];
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'business_name' => ['required', 'string', 'max:60'],
            'tagline' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'notify_email' => ['nullable', 'email', 'max:190'],
            'hero_title' => ['required', 'string', 'max:80'],
            'hero_text' => ['required', 'string', 'max:300'],
            'stat_years' => ['required', 'integer', 'min:0'],
            'stat_countries' => ['required', 'integer', 'min:0'],
            'stat_deliveries' => ['required', 'integer', 'min:0'],
            'stat_warehouse_sqm' => ['required', 'integer', 'min:0'],
        ]);

        Setting::put($validated);

        Flux::toast(variant: 'success', text: 'Settings saved.');
    }
}; ?>

<div class="flex max-w-3xl flex-col gap-6">
    <div>
        <flux:heading size="xl">Site settings</flux:heading>
        <flux:text>Business details, home page text and numbers. Rates are under Rates.</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-6">
        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Business details</flux:heading>
            <flux:input wire:model="business_name" label="Business name" />
            <flux:input wire:model="tagline" label="Tagline" description="Shown in the footer and to search engines." />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="email" type="email" label="Public email" />
                <flux:input wire:model="phone" label="Phone" />
                <flux:input wire:model="whatsapp" label="WhatsApp number (optional)" />
                <flux:input wire:model="notify_email" type="email" label="Send new bookings & enquiries to" />
            </div>
            <flux:input wire:model="address" label="Head office address" />
        </flux:card>

        <flux:card class="flex flex-col gap-4">
            <flux:heading size="lg">Home page</flux:heading>
            <flux:input wire:model="hero_title" label="Big headline" />
            <flux:textarea wire:model="hero_text" rows="3" label="Text under the headline" />
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:input wire:model="stat_years" type="number" label="Years" />
                <flux:input wire:model="stat_countries" type="number" label="Countries" />
                <flux:input wire:model="stat_deliveries" type="number" label="Parcels delivered" />
                <flux:input wire:model="stat_warehouse_sqm" type="number" label="Warehouse sq ft" />
            </div>
        </flux:card>

        <div><flux:button type="submit" variant="primary">Save settings</flux:button></div>
    </form>
</div>
