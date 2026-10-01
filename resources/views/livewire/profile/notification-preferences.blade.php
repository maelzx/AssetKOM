<?php

use Livewire\Volt\Component;

new class extends Component
{
    public bool $notify_warranty_expiry = true;

    public bool $notify_maintenance_due = true;

    public bool $notify_overdue_assignments = true;

    public function mount(): void
    {
        $user = auth()->user();

        $this->notify_warranty_expiry = (bool) $user->notify_warranty_expiry;
        $this->notify_maintenance_due = (bool) $user->notify_maintenance_due;
        $this->notify_overdue_assignments = (bool) $user->notify_overdue_assignments;
    }

    public function update(): void
    {
        $validated = $this->validate([
            'notify_warranty_expiry' => ['boolean'],
            'notify_maintenance_due' => ['boolean'],
            'notify_overdue_assignments' => ['boolean'],
        ]);

        auth()->user()->update($validated);

        $this->dispatch('saved');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-base-content">{{ __('Email alerts') }}</h2>
        <p class="mt-1 text-sm text-base-content/70">{{ __('Choose which parts of the daily alert digest you receive.') }}</p>
    </header>

    <form wire:submit="update" class="mt-6 space-y-3">
        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_warranty_expiry" class="rounded border-base-300 text-primary shadow-sm focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Warranties expiring within 30 days') }}</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_maintenance_due" class="rounded border-base-300 text-primary shadow-sm focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Maintenance due or overdue') }}</span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_overdue_assignments" class="rounded border-base-300 text-primary shadow-sm focus:ring-primary">
            <span class="text-sm text-base-content/80">{{ __('Overdue assignments') }}</span>
        </label>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>
            <x-action-message on="saved">{{ __('Saved.') }}</x-action-message>
        </div>
    </form>
</section>
