<?php

use App\Enums\EnquiryStatus;
use App\Models\Enquiry;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Quote requests')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = 'new';

    public ?int $openId = null;

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function enquiries()
    {
        return Enquiry::query()
            ->when(EnquiryStatus::tryFrom($this->status), fn ($query, EnquiryStatus $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function open(): ?Enquiry
    {
        return $this->openId ? Enquiry::query()->find($this->openId) : null;
    }

    public function show(int $id): void
    {
        $this->openId = $id;
        Flux::modal('enquiry')->show();
    }

    public function setStatus(int $id, string $status): void
    {
        validator(['status' => $status], ['status' => [Rule::enum(EnquiryStatus::class)]])->validate();

        Enquiry::query()->findOrFail($id)->update(['status' => $status]);
        unset($this->open, $this->enquiries);

        Flux::toast(variant: 'success', text: 'Marked as '.EnquiryStatus::from($status)->label().'.');
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <flux:heading size="xl">Quote requests</flux:heading>
        <flux:text>Messages from the contact form: custom quotes, warehousing, fulfilment and business accounts.</flux:text>
    </div>

    <flux:radio.group wire:model.live="status" variant="segmented" class="max-w-xl">
        <flux:radio value="" label="All" />
        @foreach (EnquiryStatus::cases() as $case)
            <flux:radio :value="$case->value" :label="$case->label()" />
        @endforeach
    </flux:radio.group>

    <flux:table :paginate="$this->enquiries">
        <flux:table.columns>
            <flux:table.column>From</flux:table.column>
            <flux:table.column>About</flux:table.column>
            <flux:table.column>Message</flux:table.column>
            <flux:table.column>Status</flux:table.column>
            <flux:table.column>Received</flux:table.column>
        </flux:table.columns>
        <flux:table.rows>
            @forelse ($this->enquiries as $enquiry)
                <flux:table.row :key="$enquiry->id">
                    <flux:table.cell>
                        <button type="button" class="text-start font-medium hover:underline" wire:click="show({{ $enquiry->id }})">{{ $enquiry->name }}</button>
                        <div class="text-xs text-zinc-500">{{ $enquiry->company ?: $enquiry->email }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $enquiry->typeLabel() }}</flux:table.cell>
                    <flux:table.cell class="max-w-xs truncate">{{ \Illuminate\Support\Str::limit($enquiry->message, 70) }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$enquiry->status->color()">{{ $enquiry->status->label() }}</flux:badge></flux:table.cell>
                    <flux:table.cell>{{ $enquiry->created_at->format('j M, g:i a') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row><flux:table.cell colspan="5">Nothing here.</flux:table.cell></flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    <flux:modal name="enquiry" class="w-full max-w-lg">
        @if ($enq = $this->open)
            <div class="flex flex-col gap-4">
                <div>
                    <flux:heading size="lg">{{ $enq->typeLabel() }} from {{ $enq->name }}</flux:heading>
                    <flux:text>{{ $enq->created_at->format('D j M Y, g:i a') }}</flux:text>
                </div>
                <dl class="grid grid-cols-3 gap-2 text-sm">
                    @if ($enq->company)<dt class="text-zinc-500">Company</dt><dd class="col-span-2">{{ $enq->company }}</dd>@endif
                    <dt class="text-zinc-500">Email</dt><dd class="col-span-2"><a class="underline" href="mailto:{{ $enq->email }}">{{ $enq->email }}</a></dd>
                    @if ($enq->phone)<dt class="text-zinc-500">Phone</dt><dd class="col-span-2"><a class="underline" href="tel:{{ $enq->phone }}">{{ $enq->phone }}</a></dd>@endif
                    @foreach ($enq->details ?? [] as $label => $value)
                        <dt class="text-zinc-500">{{ $label }}</dt><dd class="col-span-2">{{ $value }}</dd>
                    @endforeach
                </dl>
                <div class="whitespace-pre-line rounded-lg bg-zinc-50 p-3 text-sm dark:bg-zinc-700/50">{{ $enq->message }}</div>
                <div class="flex flex-wrap gap-2">
                    @foreach (EnquiryStatus::cases() as $case)
                        <flux:button size="sm" :variant="$enq->status === $case ? 'primary' : 'filled'" wire:click="setStatus({{ $enq->id }}, '{{ $case->value }}')">{{ $case->label() }}</flux:button>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:modal>
</div>
