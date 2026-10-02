<?php

use App\Enums\Currency;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Exceptions\MaintenanceException;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\MaintenanceService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public Asset $asset;

    public bool $showForm = false;

    public string $type = 'preventive';

    public string $title = '';

    public string $description = '';

    public string $vendor = '';

    public ?string $cost = null;

    public string $currency = 'MYR';

    public ?string $scheduled_at = null;

    public ?int $performed_by = null;

    public string $notes = '';

    public function mount(Asset $asset): void
    {
        $this->asset = $asset;
        $this->currency = $asset->currency->value;
        $this->scheduled_at = now()->toDateString();
    }

    public function create(): void
    {
        $validated = $this->validate([
            'type' => ['required', Rule::in(MaintenanceType::values())],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(Currency::values())],
            'scheduled_at' => ['nullable', 'date'],
            'performed_by' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['status'] = MaintenanceStatus::Scheduled;
        $validated['created_by'] = auth()->id();

        $this->asset->maintenances()->create($validated);

        $this->reset(['title', 'description', 'vendor', 'cost', 'notes', 'performed_by']);
        $this->type = MaintenanceType::Preventive->value;
        $this->scheduled_at = now()->toDateString();
        $this->showForm = false;
        $this->dispatch('asset-updated');
    }

    public function start(int $id, MaintenanceService $service): void
    {
        $this->runAction(fn () => $service->start($this->record($id)));
    }

    public function complete(int $id, MaintenanceService $service): void
    {
        $this->runAction(fn () => $service->complete($this->record($id)));
    }

    public function cancel(int $id, MaintenanceService $service): void
    {
        $this->runAction(fn () => $service->cancel($this->record($id)));
    }

    public function delete(int $id): void
    {
        $maintenance = $this->record($id);
        Gate::authorize('delete', $maintenance);
        $maintenance->delete();
    }

    protected function runAction(callable $action): void
    {
        try {
            $action();
        } catch (MaintenanceException|\InvalidArgumentException $exception) {
            $this->addError('maintenance', $exception->getMessage());

            return;
        }

        $this->asset->refresh();
        $this->dispatch('asset-updated');
    }

    protected function record(int $id): Maintenance
    {
        return $this->asset->maintenances()->findOrFail($id);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'records' => $this->asset->maintenances()->with('performer')->get(),
            'types' => MaintenanceType::cases(),
            'performers' => User::orderBy('name')->get(),
            'currencies' => Currency::cases(),
        ];
    }
}; ?>

<div class="card bg-base-100 p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h3 class="text-lg font-medium text-base-content">{{ __('Maintenance') }}</h3>
        @unless ($showForm)
            <button wire:click="$set('showForm', true)" class="text-sm text-primary hover:text-primary">
                {{ __('+ Log maintenance') }}
            </button>
        @endunless
    </div>

    @error('maintenance')
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ $message }}</div>
    @enderror

    @if ($showForm)
        <form wire:submit="create" class="space-y-3 border-t border-base-300 pt-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="mt_type" :value="__('Type')" />
                    <select wire:model="type" id="mt_type" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                        @foreach ($types as $option)
                            <option value="{{ $option->value }}">{{ $option->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="mt_scheduled" :value="__('Scheduled')" />
                    <x-text-input wire:model="scheduled_at" id="mt_scheduled" type="date" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('scheduled_at')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="mt_title" :value="__('Title')" />
                <x-text-input wire:model="title" id="mt_title" type="text" class="mt-1 block w-full" placeholder="{{ __('e.g. Annual servicing') }}" />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <x-input-label for="mt_vendor" :value="__('Vendor')" />
                    <x-text-input wire:model="vendor" id="mt_vendor" type="text" class="mt-1 block w-full" />
                </div>
                <div>
                    <x-input-label for="mt_performer" :value="__('Performed by')" />
                    <select wire:model="performed_by" id="mt_performer" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                        <option value="">{{ __('— Unassigned —') }}</option>
                        @foreach ($performers as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <x-input-label for="mt_cost" :value="__('Cost')" />
                    <x-text-input wire:model="cost" id="mt_cost" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('cost')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="mt_currency" :value="__('Currency')" />
                    <select wire:model="currency" id="mt_currency" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                        @foreach ($currencies as $option)
                            <option value="{{ $option->value }}">{{ $option->value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <x-input-label for="mt_description" :value="__('Description')" />
                <textarea wire:model="description" id="mt_description" rows="2" class="textarea textarea-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md"></textarea>
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
                <button type="button" wire:click="$set('showForm', false)" class="text-sm text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</button>
            </div>
        </form>
    @endif

    <div class="space-y-3">
        @forelse ($records as $record)
            <div class="rounded-md border border-base-300 p-3" wire:key="maintenance-{{ $record->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-base-content">{{ $record->title }}</p>
                        <p class="text-xs text-base-content/60">
                            {{ $record->type->label() }}
                            @if ($record->scheduled_at) · {{ $record->scheduled_at->format('d M Y') }} @endif
                            @if ($record->vendor) · {{ $record->vendor }} @endif
                            @if ($record->cost !== null) · {{ \App\Support\Money::format($record->cost, $record->currency) }} @endif
                        </p>
                        @if ($record->notes)
                            <p class="mt-1 text-xs text-base-content/60 whitespace-pre-line">{{ $record->notes }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $record->status->color() }}">
                        {{ $record->status->label() }}
                    </span>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs">
                    @if ($record->status === \App\Enums\MaintenanceStatus::Scheduled)
                        <button wire:click="start({{ $record->id }})" class="text-yellow-700 hover:text-yellow-900">{{ __('Start') }}</button>
                        <button wire:click="complete({{ $record->id }})" class="text-green-700 hover:text-green-900">{{ __('Complete') }}</button>
                        <button wire:click="cancel({{ $record->id }})" class="text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</button>
                    @elseif ($record->status === \App\Enums\MaintenanceStatus::InProgress)
                        <button wire:click="complete({{ $record->id }})" class="text-green-700 hover:text-green-900">{{ __('Complete') }}</button>
                        <button wire:click="cancel({{ $record->id }})" class="text-base-content/70 hover:text-base-content">{{ __('Cancel') }}</button>
                    @endif
                    @can('delete', $record)
                        <button wire:click="delete({{ $record->id }})" wire:confirm="{{ __('Delete this maintenance record?') }}" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                    @endcan
                </div>

                <div class="mt-3 border-t border-base-300 pt-3">
                    <livewire:attachments.panel :attachable="$record" wire:key="attachments-maintenance-{{ $record->id }}" />
                </div>
            </div>
        @empty
            <p class="text-sm text-base-content/60">{{ __('No maintenance records.') }}</p>
        @endforelse
    </div>
</div>
