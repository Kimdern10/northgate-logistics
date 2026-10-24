<?php

use App\Models\Address;
use App\Support\Countries;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Address book')] class extends Component {
    public ?int $editingId = null;

    public string $label = '';

    public string $name = '';

    public string $company = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $country = 'NG';

    public bool $is_default = false;

    #[Computed]
    public function addresses()
    {
        return auth()->user()->addresses()->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'label', 'name', 'company', 'email', 'phone', 'address', 'city', 'country', 'is_default']);
        $this->resetValidation();
        Flux::modal('address')->show();
    }

    public function edit(int $id): void
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $address->id;

        foreach (['label', 'name', 'company', 'email', 'phone', 'address', 'city', 'country'] as $field) {
            $this->{$field} = (string) $address->{$field};
        }

        $this->is_default = $address->is_default;
        Flux::modal('address')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'label' => ['nullable', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'company' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', Rule::in(Countries::codes())],
            'is_default' => ['boolean'],
        ]);

        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);
        $user = auth()->user();

        if ($validated['is_default']) {
            $user->addresses()->update(['is_default' => false]);
        }

        $this->editingId
            ? $user->addresses()->findOrFail($this->editingId)->update($validated)
            : $user->addresses()->create($validated);

        unset($this->addresses);
        Flux::modal('address')->close();
        Flux::toast(variant: 'success', text: 'Address saved.');
    }

    public function delete(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->delete();
        unset($this->addresses);
        Flux::toast(text: 'Address deleted.');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Address book</flux:heading>
            <flux:text>Save the places you ship from and the people you ship to. Pick them in one click when you book.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add address</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @forelse ($this->addresses as $saved)
            <flux:card class="flex flex-col gap-1" wire:key="address-{{ $saved->id }}">
                <div class="flex items-start justify-between gap-2">
                    <flux:heading>{{ $saved->label ?: $saved->name }}</flux:heading>
                    @if ($saved->is_default)<flux:badge size="sm" color="orange">My pickup address</flux:badge>@endif
                </div>
                <flux:text>{{ $saved->name }}{{ $saved->company ? ', '.$saved->company : '' }}</flux:text>
                <flux:text>{{ $saved->address }}, {{ $saved->city }}, {{ Countries::name($saved->country) }}</flux:text>
                <flux:text>{{ $saved->phone }}{{ $saved->email ? ' · '.$saved->email : '' }}</flux:text>
                <div class="mt-2 flex gap-2">
                    <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $saved->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $saved->id }})" wire:confirm="Delete this address?">Delete</flux:button>
                </div>
            </flux:card>
        @empty
            <flux:card class="md:col-span-2"><flux:text>No saved addresses yet. You can also tick "Save to my address book" when you book a pickup.</flux:text></flux:card>
        @endforelse
    </div>

    <flux:modal name="address" class="w-full max-w-2xl">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit address' : 'Add address' }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="label" label="Nickname (optional)" placeholder="Home, Office, Mum in London" />
                <flux:input wire:model="name" label="Full name" />
                <flux:input wire:model="company" label="Company (optional)" />
                <flux:input wire:model="phone" label="Phone" />
                <flux:input wire:model="email" type="email" label="Email (optional)" />
            </div>
            <flux:input wire:model="address" label="Street address" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="city" label="City" />
                <flux:select wire:model="country" label="Country">
                    @foreach (Countries::options() as $code => $countryName)
                        <flux:select.option :value="$code">{{ $countryName }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:switch wire:model="is_default" label="Use as my pickup address" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
