<?php

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public ?int $category = null;

    #[Url(history: true)]
    public ?int $location = null;

    #[Url(history: true)]
    public string $status = '';

    #[Url(history: true)]
    public string $condition = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'location', 'status', 'condition']);
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $asset = Asset::findOrFail($id);

        Gate::authorize('delete', $asset);

        $asset->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $assets = Asset::query()
            ->with(['category', 'location'])
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $term = '%'.$this->search.'%';
                    $query->where('asset_tag', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('serial_number', 'like', $term)
                        ->orWhere('manufacturer', 'like', $term)
                        ->orWhere('model', 'like', $term);
                });
            })
            ->when($this->category, fn ($query) => $query->where('category_id', $this->category))
            ->when($this->location, fn ($query) => $query->where('location_id', $this->location))
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->condition !== '', fn ($query) => $query->where('condition', $this->condition))
            ->latest()
            ->paginate(15);

        return [
            'assets' => $assets,
            'categories' => Category::with('parent')->orderBy('name')->get(),
            'locations' => Location::with('parent')->orderBy('name')->get(),
            'statuses' => AssetStatus::cases(),
            'conditions' => AssetCondition::cases(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Assets')" :subtitle="__('Browse and manage the asset registry.')">
            <x-slot name="actions">
                @can('create', \App\Models\Asset::class)
                    <a href="{{ route('assets.create') }}" wire:navigate class="btn btn-primary btn-sm">
                        {{ __('New asset') }}
                    </a>
                @endcan
            </x-slot>
        </x-page-header>

        <div class="card bg-base-100 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" class="block w-full" placeholder="{{ __('Search tag, name, serial…') }}" />
                </div>

                <select wire:model.live="category" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="location" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                    <option value="">{{ __('All locations') }}</option>
                    @foreach ($locations as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="status" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="condition" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                    <option value="">{{ __('All conditions') }}</option>
                    @foreach ($conditions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <div class="flex items-center">
                    <button type="button" wire:click="resetFilters" class="text-sm text-base-content/70 hover:text-base-content">
                        {{ __('Reset filters') }}
                    </button>
                </div>
            </div>
        </div>

        <x-data-table :paginator="$assets">
            <x-slot name="table">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Tag') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Category') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Location') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Condition') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($assets as $asset)
                            <tr wire:key="asset-{{ $asset->id }}" class="hover:bg-base-200">
                                <td class="px-4 py-3 text-sm font-mono text-base-content">{{ $asset->asset_tag }}</td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    <a href="{{ route('assets.show', $asset) }}" wire:navigate class="hover:text-primary">{{ $asset->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $asset->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $asset->location?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm"><x-status-badge :status="$asset->status" /></td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $asset->condition?->label() ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('assets.show', $asset) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                    @can('update', $asset)
                                        <a href="{{ route('assets.edit', $asset) }}" wire:navigate class="ms-3 text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $asset)
                                        <button wire:click="delete({{ $asset->id }})" wire:confirm="{{ __('Delete this asset?') }}" class="ms-3 text-error hover:opacity-80">{{ __('Delete') }}</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No assets found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @forelse ($assets as $asset)
                        <li class="space-y-2 p-4" wire:key="asset-card-{{ $asset->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('assets.show', $asset) }}" wire:navigate class="font-medium text-base-content hover:text-primary">{{ $asset->name }}</a>
                                    <p class="font-mono text-xs text-base-content/50">{{ $asset->asset_tag }}</p>
                                </div>
                                <x-status-badge :status="$asset->status" />
                            </div>

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-base-content/60">
                                <div><dt class="inline text-base-content/40">{{ __('Category') }}:</dt> {{ $asset->category?->name ?? '—' }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('Location') }}:</dt> {{ $asset->location?->name ?? '—' }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('Condition') }}:</dt> {{ $asset->condition?->label() ?? '—' }}</div>
                            </dl>

                            <div class="flex items-center gap-4 pt-1 text-sm">
                                <a href="{{ route('assets.show', $asset) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View') }}</a>
                                @can('update', $asset)
                                    <a href="{{ route('assets.edit', $asset) }}" wire:navigate class="text-base-content/70 hover:text-base-content">{{ __('Edit') }}</a>
                                @endcan
                                @can('delete', $asset)
                                    <button wire:click="delete({{ $asset->id }})" wire:confirm="{{ __('Delete this asset?') }}" class="text-error hover:opacity-80">{{ __('Delete') }}</button>
                                @endcan
                            </div>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-base-content/60">{{ __('No assets found.') }}</li>
                    @endforelse
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
