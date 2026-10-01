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

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Assets')" :subtitle="__('Browse and manage the asset registry.')">
            <x-slot name="actions">
                @can('create', \App\Models\Asset::class)
                    <a href="{{ route('assets.create') }}" wire:navigate class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {{ __('New asset') }}
                    </a>
                @endcan
            </x-slot>
        </x-page-header>

        <div class="bg-white shadow sm:rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" class="block w-full" placeholder="{{ __('Search tag, name, serial…') }}" />
                </div>

                <select wire:model.live="category" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="location" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('All locations') }}</option>
                    @foreach ($locations as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="status" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <select wire:model.live="condition" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                    <option value="">{{ __('All conditions') }}</option>
                    @foreach ($conditions as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>

                <div class="flex items-center">
                    <button type="button" wire:click="resetFilters" class="text-sm text-gray-600 hover:text-gray-900">
                        {{ __('Reset filters') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Tag') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Category') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Location') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Condition') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assets as $asset)
                            <tr wire:key="asset-{{ $asset->id }}" class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $asset->asset_tag }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <a href="{{ route('assets.show', $asset) }}" wire:navigate class="hover:text-indigo-600">{{ $asset->name }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $asset->category?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $asset->location?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm"><x-status-badge :status="$asset->status" /></td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $asset->condition?->label() ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="{{ route('assets.show', $asset) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                                    @can('update', $asset)
                                        <a href="{{ route('assets.edit', $asset) }}" wire:navigate class="ms-3 text-gray-600 hover:text-gray-900">{{ __('Edit') }}</a>
                                    @endcan
                                    @can('delete', $asset)
                                        <button wire:click="delete({{ $asset->id }})" wire:confirm="{{ __('Delete this asset?') }}" class="ms-3 text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">{{ __('No assets found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assets->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $assets->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
