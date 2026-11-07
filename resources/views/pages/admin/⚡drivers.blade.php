<?php

use App\Enums\ShipmentStatus;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Drivers')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    #[Computed]
    public function drivers()
    {
        return User::query()
            ->where('is_driver', true)
            ->withCount(['assignedShipments as open_jobs_count' => fn ($query) => $query->whereIn('status', [ShipmentStatus::Booked, ShipmentStatus::OutForDelivery])])
            ->orderBy('name')
            ->get();
    }

    public function create(): void
    {
        $this->reset(['name', 'email', 'phone', 'password']);
        $this->resetValidation();
        Flux::modal('driver')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', Password::defaults(), 'min:8'],
        ]);

        $driver = new User;
        $driver->forceFill([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'],
            'password' => $validated['password'],
            'is_driver' => true,
            'email_verified_at' => now(),
        ])->save();

        unset($this->drivers);
        Flux::modal('driver')->close();
        Flux::toast(variant: 'success', text: "{$driver->name} can now log in at ".route('login').' and will land on the driver page.');
    }

    /**
     * Take away driver access (the login stays, so their delivery history is kept).
     */
    public function remove(int $id): void
    {
        $driver = User::query()->where('is_driver', true)->findOrFail($id);
        $driver->forceFill(['is_driver' => false])->save();
        $driver->assignedShipments()->whereIn('status', [ShipmentStatus::Booked, ShipmentStatus::OutForDelivery])->update(['driver_id' => null]);

        unset($this->drivers);
        Flux::toast(text: "{$driver->name} is no longer a driver. Their open jobs are unassigned.");
    }
}; ?>

<div class="flex max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Drivers</flux:heading>
            <flux:text>Couriers log in on their phone and see the pickups and deliveries you assign to them at <strong>{{ route('driver') }}</strong>.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add driver</flux:button>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>Name</flux:table.column>
            <flux:table.column>Login email</flux:table.column>
            <flux:table.column>Phone</flux:table.column>
            <flux:table.column>Open jobs</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->drivers as $driver)
                <flux:table.row :key="$driver->id">
                    <flux:table.cell>{{ $driver->name }}</flux:table.cell>
                    <flux:table.cell>{{ $driver->email }}</flux:table.cell>
                    <flux:table.cell>{{ $driver->phone }}</flux:table.cell>
                    <flux:table.cell>{{ $driver->open_jobs_count }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        <flux:button size="xs" variant="ghost" icon="user-minus" wire:click="remove({{ $driver->id }})" wire:confirm="Remove {{ $driver->name }} as a driver? Their open jobs will be unassigned.">Remove</flux:button>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5">No drivers yet. Add one to start assigning pickups and deliveries.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="driver" class="w-full max-w-md">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">Add driver</flux:heading>
            <flux:input wire:model="name" label="Full name" />
            <flux:input wire:model="email" type="email" label="Login email" />
            <flux:input wire:model="phone" label="Phone" />
            <flux:input wire:model="password" type="password" label="Password" description="Give this to the driver. They can change it later in Settings." viewable />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Create login</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
