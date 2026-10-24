<?php

use App\Models\Service;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Services')] class extends Component {
    public ?int $editingId = null;

    public string $name = '';

    public string $icon = 'box';

    public string $summary = '';

    public string $description = '';

    /** One feature per line. */
    public string $features = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    #[Computed]
    public function services()
    {
        return Service::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'icon', 'summary', 'description', 'features', 'sort_order', 'is_active']);
        $this->resetValidation();
        Flux::modal('service')->show();
    }

    public function edit(int $id): void
    {
        $service = Service::query()->findOrFail($id);

        $this->resetValidation();
        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->icon = $service->icon;
        $this->summary = $service->summary;
        $this->description = $service->description;
        $this->features = implode("\n", $service->features ?? []);
        $this->sort_order = (string) $service->sort_order;
        $this->is_active = $service->is_active;

        Flux::modal('service')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'icon' => ['required', Rule::in(array_keys(Service::ICONS))],
            'summary' => ['required', 'string', 'max:300'],
            'description' => ['required', 'string', 'max:5000'],
            'features' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);

        $validated['features'] = collect(preg_split('/\R/', $validated['features'] ?? ''))->map(fn (string $line): string => trim($line))->filter()->values()->all();

        if ($this->editingId) {
            Service::query()->findOrFail($this->editingId)->update($validated);
        } else {
            $slug = Str::slug($validated['name']);
            $validated['slug'] = Service::query()->where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(4)) : $slug;
            Service::query()->create($validated);
        }

        unset($this->services);
        Flux::modal('service')->close();
        Flux::toast(variant: 'success', text: 'Service saved.');
    }

    public function delete(int $id): void
    {
        Service::query()->findOrFail($id)->delete();
        unset($this->services);
        Flux::toast(text: 'Service deleted.');
    }
}; ?>

<div class="flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">Services</flux:heading>
            <flux:text>Shown on the home page and the Services page, lowest order number first.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add service</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($this->services as $service)
            <flux:card class="flex flex-col gap-2" wire:key="service-{{ $service->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400"><x-icon :name="$service->icon" class="size-5" /></span>
                        <div>
                            <flux:heading>{{ $service->name }}</flux:heading>
                            <flux:text class="text-xs">Order {{ $service->sort_order }} · /services/{{ $service->slug }}</flux:text>
                        </div>
                    </div>
                    @unless ($service->is_active)<flux:badge size="sm">Hidden</flux:badge>@endunless
                </div>
                <flux:text>{{ $service->summary }}</flux:text>
                <div class="flex gap-2">
                    <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $service->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $service->id }})" wire:confirm="Delete {{ $service->name }}?">Delete</flux:button>
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="service" class="w-full max-w-2xl">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit service' : 'Add service' }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2"><flux:input wire:model="name" label="Name" /></div>
                <flux:select wire:model="icon" label="Icon">
                    @foreach (Service::ICONS as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:textarea wire:model="summary" rows="2" label="Short summary" description="One or two sentences for the cards." />
            <flux:textarea wire:model="description" rows="6" label="Full description" description="Leave an empty line between paragraphs." />
            <flux:textarea wire:model="features" rows="5" label="What's included" description="One per line." />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="sort_order" type="number" label="Order" />
                <div class="pt-7"><flux:switch wire:model="is_active" label="Show on website" /></div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
