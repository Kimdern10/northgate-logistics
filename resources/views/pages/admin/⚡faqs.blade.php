<?php

use App\Models\Faq;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('FAQs')] class extends Component {
    public ?int $editingId = null;

    public string $question = '';

    public string $answer = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    #[Computed]
    public function faqs()
    {
        return Faq::query()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'question', 'answer', 'sort_order', 'is_active']);
        $this->resetValidation();
        Flux::modal('faq')->show();
    }

    public function edit(int $id): void
    {
        $faq = Faq::query()->findOrFail($id);
        $this->resetValidation();
        $this->editingId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->sort_order = (string) $faq->sort_order;
        $this->is_active = $faq->is_active;
        Flux::modal('faq')->show();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:2000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ]);

        Faq::query()->updateOrCreate(['id' => $this->editingId], $validated);

        unset($this->faqs);
        Flux::modal('faq')->close();
        Flux::toast(variant: 'success', text: 'Question saved.');
    }

    public function delete(int $id): void
    {
        Faq::query()->findOrFail($id)->delete();
        unset($this->faqs);
        Flux::toast(text: 'Question deleted.');
    }
}; ?>

<div class="flex max-w-4xl flex-col gap-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <flux:heading size="xl">FAQs</flux:heading>
            <flux:text>Questions shown at the bottom of the home page (and to Google as an FAQ).</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">Add question</flux:button>
    </div>

    <div class="flex flex-col gap-3">
        @foreach ($this->faqs as $faq)
            <flux:card class="flex items-start justify-between gap-4" wire:key="faq-{{ $faq->id }}">
                <div>
                    <flux:heading>{{ $faq->question }} @unless ($faq->is_active)<flux:badge size="sm">Hidden</flux:badge>@endunless</flux:heading>
                    <flux:text class="mt-1">{{ $faq->answer }}</flux:text>
                </div>
                <div class="flex shrink-0 gap-1">
                    <flux:button size="xs" icon="pencil-square" wire:click="edit({{ $faq->id }})" aria-label="Edit" />
                    <flux:button size="xs" variant="ghost" icon="trash" wire:click="delete({{ $faq->id }})" wire:confirm="Delete this question?" aria-label="Delete" />
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="faq" class="w-full max-w-lg">
        <form wire:submit="save" class="flex flex-col gap-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit question' : 'Add question' }}</flux:heading>
            <flux:input wire:model="question" label="Question" />
            <flux:textarea wire:model="answer" rows="5" label="Answer" />
            <div class="grid gap-4 sm:grid-cols-2">
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
