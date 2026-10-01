<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\Location;
use App\Models\Maintenance;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $statusCounts = Asset::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'totalAssets' => Asset::count(),
            'statusCounts' => $statusCounts,
            'available' => (int) ($statusCounts[AssetStatus::Available->value] ?? 0),
            'assigned' => (int) ($statusCounts[AssetStatus::Assigned->value] ?? 0),
            'underMaintenance' => (int) ($statusCounts[AssetStatus::Maintenance->value] ?? 0),
            'byCategory' => Category::with('parent')->withCount('assets')->orderByDesc('assets_count')->take(6)->get(),
            'byLocation' => Location::with('parent')->withCount('assets')->orderByDesc('assets_count')->take(6)->get(),
            'warrantyExpiring' => Asset::whereNotNull('warranty_expiry')
                ->whereBetween('warranty_expiry', [today(), today()->addDays(30)])
                ->count(),
            'warrantyList' => Asset::whereNotNull('warranty_expiry')
                ->whereBetween('warranty_expiry', [today(), today()->addDays(30)])
                ->orderBy('warranty_expiry')
                ->take(5)
                ->get(),
            'maintenanceOpen' => Maintenance::open()->count(),
            'maintenanceDue' => Maintenance::open()
                ->whereDate('scheduled_at', '<=', today())
                ->count(),
            'maintenanceDueList' => Maintenance::open()
                ->whereDate('scheduled_at', '<=', today())
                ->with('asset')
                ->orderBy('scheduled_at')
                ->take(5)
                ->get(),
            'activeAssignments' => AssetAssignment::active()->count(),
            'overdueAssignments' => AssetAssignment::overdue()->count(),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Dashboard')" :subtitle="__('Overview of your asset estate.')" />

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="card bg-base-100 shadow-sm p-5">
                <p class="text-sm text-base-content/60">{{ __('Total assets') }}</p>
                <p class="mt-1 text-3xl font-semibold text-base-content">{{ $totalAssets }}</p>
            </div>
            <div class="card bg-base-100 shadow-sm p-5">
                <p class="text-sm text-base-content/60">{{ __('Available') }}</p>
                <p class="mt-1 text-3xl font-semibold text-green-600">{{ $available }}</p>
            </div>
            <div class="card bg-base-100 shadow-sm p-5">
                <p class="text-sm text-base-content/60">{{ __('Assigned') }}</p>
                <p class="mt-1 text-3xl font-semibold text-blue-600">{{ $assigned }}</p>
                @if ($overdueAssignments > 0)
                    <p class="mt-1 text-xs text-red-600">{{ trans_choice(':count overdue|:count overdue', $overdueAssignments) }}</p>
                @endif
            </div>
            <div class="card bg-base-100 shadow-sm p-5">
                <p class="text-sm text-base-content/60">{{ __('Under maintenance') }}</p>
                <p class="mt-1 text-3xl font-semibold text-yellow-600">{{ $underMaintenance }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Assets by status') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach (AssetStatus::cases() as $status)
                        @php($count = (int) ($statusCounts[$status->value] ?? 0))
                        <li class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">
                                {{ $status->label() }}
                            </span>
                            <span class="font-medium text-base-content">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Top categories') }}</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($byCategory as $category)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-base-content/80">{{ $category->full_name }}</span>
                            <span class="font-medium text-base-content">{{ $category->assets_count }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/60">{{ __('No categories.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Top locations') }}</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($byLocation as $location)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-base-content/80">{{ $location->full_name }}</span>
                            <span class="font-medium text-base-content">{{ $location->assets_count }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/60">{{ __('No locations.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Alerts') }}</h3>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex items-center justify-between">
                        <span class="text-base-content/80">{{ __('Warranties expiring (30 days)') }}</span>
                        <span class="font-medium {{ $warrantyExpiring > 0 ? 'text-yellow-600' : 'text-base-content' }}">{{ $warrantyExpiring }}</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-base-content/80">{{ __('Maintenance due / overdue') }}</span>
                        <span class="font-medium {{ $maintenanceDue > 0 ? 'text-red-600' : 'text-base-content' }}">{{ $maintenanceDue }}</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-base-content/80">{{ __('Overdue assignments') }}</span>
                        <span class="font-medium {{ $overdueAssignments > 0 ? 'text-red-600' : 'text-base-content' }}">{{ $overdueAssignments }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Warranties expiring soon') }}</h3>
                <ul class="mt-4 divide-y divide-base-300">
                    @forelse ($warrantyList as $asset)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <a href="{{ route('assets.show', $asset) }}" wire:navigate class="text-primary hover:text-primary">{{ $asset->name }}</a>
                            <span class="text-base-content/60">{{ $asset->warranty_expiry?->format('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-base-content/60">{{ __('Nothing expiring soon.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 shadow-sm p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Maintenance due') }}</h3>
                <ul class="mt-4 divide-y divide-base-300">
                    @forelse ($maintenanceDueList as $record)
                        <li class="flex items-center justify-between py-2 text-sm">
                            <a href="{{ $record->asset ? route('assets.show', $record->asset) : '#' }}" wire:navigate class="text-primary hover:text-primary">{{ $record->title }}</a>
                            <span class="text-base-content/60">{{ $record->scheduled_at?->format('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-base-content/60">{{ __('Nothing due.') }}</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
