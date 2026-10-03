<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Setting;
use App\Support\AssetBarcode;
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
            'categories' => Category::with('parent')->orderBy('name')->get(),
            'locations' => Location::with('parent')->get()->sortBy('full_name'),
            'statuses' => AssetStatus::cases(),
            'total' => $total,
            'qrEnabled' => (bool) Setting::get('label_qr_enabled', false),
            'preview' => $query->limit(12)->get()->map(fn (Asset $asset): array => [
                'asset' => $asset,
                'barcode' => AssetBarcode::dataUri($asset->asset_tag, 2, 50),
                'qr' => Setting::get('label_qr_enabled', false) ? AssetQrCode::dataUri($asset, 140) : null,
            ]),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Labels')" :subtitle="__('Printable asset labels: 1D barcode + asset tag, with an optional QR code.')">
            <x-slot name="actions">
                @if ($total > 0)
                    <a
                        href="{{ route('labels.bulk', array_filter(['category' => $category, 'location' => $location, 'status' => $status])) }}"
                        target="_blank"
                        class="btn btn-primary btn-sm"
                    >
                        {{ __('Download PDF (:count)', ['count' => min($total, 300)]) }}
                    </a>
                @endif
            </x-slot>
        </x-page-header>

        <div class="card bg-base-100 p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="badge badge-ghost">{{ $qrEnabled ? __('QR enabled') : __('Barcode + tag') }}</span>
                <a href="{{ route('settings') }}" wire:navigate class="text-xs text-primary hover:opacity-80">{{ __('Label settings') }}</a>
            </div>
        </div>

        <div class="card bg-base-100 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select wire:model.live="category" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach ($categories as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="location" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                    <option value="">{{ __('All locations') }}</option>
                    @foreach ($locations as $option)
                        <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="status" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card bg-base-100 p-6">
            <p class="text-sm text-base-content/60">{{ trans_choice(':count asset matches|:count assets match', $total) }} {{ __('(previewing first 12)') }}</p>

            <div class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @forelse ($preview as $item)
                    <div class="rounded-md border border-dashed border-base-300 p-3 text-center" wire:key="label-{{ $item['asset']->id }}">
                        @if ($item['qr'])
                            <img src="{{ $item['qr'] }}" class="mx-auto mb-1 h-20 w-20" alt="">
                        @endif
                        <img src="{{ $item['barcode'] }}" class="mx-auto h-10" alt="">
                        <p class="mt-2 font-mono text-xs font-semibold text-base-content">{{ $item['asset']->asset_tag }}</p>
                        <p class="truncate text-xs text-base-content/60">{{ $item['asset']->name }}</p>
                    </div>
                @empty
                    <p class="text-sm text-base-content/60">{{ __('No assets match the filters.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
