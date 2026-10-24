<?php

use App\Models\Branch;
use App\Support\Countries;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Branches & hubs')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $city = '';

    public string $country = 'NG';

    public string $address = '';

    public string $phone = '';

    public string $email = '';

    public string $opening_hours = '';

    public string $latitude = '';

    public string $longitude = '';

    public bool $is_hub = false;

    public bool $is_warehouse = false;

    public bool $is_active = true;

    #[Computed]
    public function branches()
    {
        return Branch::query()->orderBy('country')->orderBy('city')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'city', 'country', 'address', 'phone', 'email', 'opening_hours', 'latitude', 'longitude', 'is_hub', 'is_warehouse', 'is_active']);
        $this->resetValidation();
        Flux::modal('branch')->show();
    }

    public function edit(int $id): void
    {
        $branch = Branch::query()->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $branch->id;

        foreach (['name', 'city', 'country', 'address', 'phone', 'email', 'opening_hours', 'latitude', 'longitude'] as $field) {
            $this->{$field} = (string) $branch->{$field};
        }

        $this->is_hub = $branch->is_hub;
        $this->is_warehouse = $branch->is_warehouse;
        $this->is_active = $branch->is_active;

        Flux::modal('branch')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', Rule::in(Countries::codes())],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:190'],
            'opening_hours' => ['nullable', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_hub' => ['boolean'],
            'is_warehouse' => ['boolean'],
            'is_active' => ['boolean'],
        ], [], ['latitude' => 'latitude', 'longitude' => 'longitude']);

        $validated = array_map(fn ($value) => $value === '' ? null : $value, $validated);

        Branch::query()->updateOrCreate(['id' => $this->editingId], $validated);

        unset($this->branches);
        Flux::modal('branch')->close();
        Flux::toast(variant: 'success', text: 'Branch saved.');
    }

    public function delete(int $id): void
    {
        Branch::query()->findOrFail($id)->delete();
        unset($this->branches);
        Flux::toast(text: 'Branch deleted.');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Branches & hubs</flux:heading>
            <flux:text>Each one is a pin on the coverage map. Hubs get a bigger orange pin.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add branch</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>City</flux:table.column>
            <flux:table.column>Type</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->branches as $branch)
                <flux:table.row :key="$branch->id">
                    <flux:table.cell>{{ $branch->name }} @unless ($branch->is_active)<flux:badge size="sm">Hidden</flux:badge>@endunless</flux:table.cell>
                    <flux:table.cell>{{ $branch->city }}, {{ $branch->countryName() }}</flux:table.cell>
                    <flux:table.cell>{{ $branch->kind() }}</flux:table.cell>
                    <flux:table.cell>{{ $branch->phone }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        <flux:button size="xs" icon="pencil-square" wire:click="edit({{ $branch->id }})">Edit</flux:button>
                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $branch->id }})" wire:confirm="Delete {{ $branch->name }}?" aria-label="Delete" />
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="branch" class="w-full max-w-2xl">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit branch' : 'Add branch' }}</flux:heading>
            <flux:input wire:model="name" label="Name" placeholder="e.g. Lagos Airport Hub" />
            <flux:input wire:model="address" label="Street address" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="city" label="City" />
                <flux:select wire:model="country" label="Country">
                    @foreach (Countries::options() as $code => $countryName)
                        <flux:select.option :value="$code">{{ $countryName }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="phone" label="Phone" />
                <flux:input wire:model="email" type="email" label="Email" />
            </div>
            <flux:input wire:model="opening_hours" label="Opening hours" placeholder="Mon–Fri 8am–6pm, Sat 9am–2pm" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="latitude" label="Latitude" placeholder="6.5774" />
                <flux:input wire:model="longitude" label="Longitude" placeholder="3.3212" />
            </div>
            <flux:text class="text-xs">Tip: right-click the place in Google Maps or openstreetmap.org and copy the two numbers.</flux:text>
            <div class="flex flex-wrap gap-6">
                <flux:switch wire:model="is_hub" label="Regional hub" />
                <flux:switch wire:model="is_warehouse" label="Warehouse" />
                <flux:switch wire:model="is_active" label="Show on website" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
