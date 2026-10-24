<?php

use App\Models\Testimonial;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Testimonials')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $role = '';

    public string $quote = '';

    public string $rating = '5';

    public string $sort_order = '0';

    public bool $is_active = true;

    #[Computed]
    public function testimonials()
    {
        return Testimonial::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'role', 'quote', 'rating', 'sort_order', 'is_active']);
        $this->resetValidation();
        Flux::modal('testimonial')->show();
    }

    public function edit(int $id): void
    {
        $testimonial = Testimonial::query()->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $testimonial->id;
        $this->name = $testimonial->name;
        $this->role = (string) $testimonial->role;
        $this->quote = $testimonial->quote;
        $this->rating = (string) $testimonial->rating;
        $this->sort_order = (string) $testimonial->sort_order;
        $this->is_active = $testimonial->is_active;
        Flux::modal('testimonial')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'role' => ['nullable', 'string', 'max:120'],
            'quote' => ['required', 'string', 'max:600'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);

        Testimonial::query()->updateOrCreate(['id' => $this->editingId], $validated);

        unset($this->testimonials);
        Flux::modal('testimonial')->close();
        Flux::toast(variant: 'success', text: 'Testimonial saved.');
    }

    public function delete(int $id): void
    {
        Testimonial::query()->findOrFail($id)->delete();
        unset($this->testimonials);
        Flux::toast(text: 'Testimonial deleted.');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Testimonials</flux:heading>
            <flux:text>Customer quotes that scroll across the home page.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add testimonial</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->testimonials as $testimonial)
            <flux:card class="flex flex-col gap-2" wire:key="t-{{ $testimonial->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <flux:heading>{{ $testimonial->name }}</flux:heading>
                        <flux:text class="text-xs">{{ $testimonial->role }} · {{ str_repeat('★', $testimonial->rating) }}</flux:text>
                    </div>
                    @unless ($testimonial->is_active)<flux:badge size="sm">Hidden</flux:badge>@endunless
                </div>
                <flux:text>“{{ $testimonial->quote }}”</flux:text>
                <div class="flex gap-2">
                    <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $testimonial->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $testimonial->id }})" wire:confirm="Delete this testimonial?">Delete</flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="testimonial" class="w-full max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit testimonial' : 'Add testimonial' }}</flux:heading>
            <flux:input wire:model="name" label="Customer name" />
            <flux:input wire:model="role" label="Role / company" placeholder="Founder, Ada's Fabrics" />
            <flux:textarea wire:model="quote" rows="4" label="Quote" />
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:select wire:model="rating" label="Stars">
                    @foreach ([5, 4, 3, 2, 1] as $stars)
                        <flux:select.option :value="$stars">{{ $stars }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="sort_order" type="number" label="Order" />
                <div class="pt-7"><flux:switch wire:model="is_active" label="Show" /></div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
