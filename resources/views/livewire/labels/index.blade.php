<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Support\AssetQrCode;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Url(history: true)]
    public ?int $category = null;

    #[Url(history: true)]
    public ?int $location = null;

    #[Url(history: true)]
    public string $status = '';

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $query = Asset::query()
            ->when($this->category, fn ($q) => $q->where('category_id', $this->category))
            ->when($this->location, fn ($q) => $q->where('location_id', $this->location))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->orderBy('asset_tag');

        $total = (clone $query)->count();

        return [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('full_name')->get(),
            'statuses' => AssetStatus::cases(),
            'total' => $total,
            'preview' => $query->limit(12)->get()->map(fn (Asset $asset): array => [
                'asset' => $asset,
                'qr' => AssetQrCode::dataUri($asset, 160),
            ]),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('QR Labels')" :subtitle="__('Generate scannable labels for your assets.')">
            <x-slot name="actions">
                @if ($total > 0)
                    <a
                        href="{{ route('labels.bulk', array_filter(['category' => $category, 'location' => $location, 'status' => $status])) }}"
                        target="_blank"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                    >
                        {{ __('Download PDF (:count)', ['count' => min($total, 300)]) }}
                    </a>
                @endif
            </x-slot>
        </x-page-header>

        <div class="bg-white shadow sm:rounded-lg p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
            </div>
        </div>

        <div class="bg-white shadow sm:rounded-lg p-6">
            <p class="text-sm text-gray-500">{{ trans_choice(':count asset matches|:count assets match', $total) }} {{ __('(previewing first 12)') }}</p>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse ($preview as $item)
                    <div class="rounded-md border border-dashed border-gray-300 p-3 text-center" wire:key="label-{{ $item['asset']->id }}">
                        <img src="{{ $item['qr'] }}" class="mx-auto h-28 w-28" alt="">
                        <p class="mt-2 font-mono text-xs font-semibold text-gray-900">{{ $item['asset']->asset_tag }}</p>
                        <p class="truncate text-xs text-gray-500">{{ $item['asset']->name }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No assets match the filters.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
