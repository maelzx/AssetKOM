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

<div class="py-7 sm:py-9">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Dashboard')" :subtitle="__('Overview of your asset estate.')">
            <x-slot name="actions">
                <a href="{{ route('assets.index') }}" wire:navigate class="btn btn-outline btn-sm rounded-xl">{{ __('Browse assets') }}</a>
                @can('create', \App\Models\Asset::class)
                    <a href="{{ route('assets.create') }}" wire:navigate class="btn btn-primary btn-sm rounded-xl">{{ __('New asset') }}</a>
                @endcan
            </x-slot>
        </x-page-header>

        <section aria-label="{{ __('Asset summary') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('assets.index') }}" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Total assets') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-base-content">{{ number_format($totalAssets) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-primary/10 text-primary" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.2V21"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('In the asset register') }} <span class="ms-1 text-primary">→</span></p>
            </a>

            <a href="{{ route('assets.index', ['status' => AssetStatus::Available->value]) }}" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-success/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Available') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-success">{{ number_format($available) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-success/10 text-success" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7.5"/><circle cx="12" cy="12" r="9"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('Ready to be assigned') }} <span class="ms-1 text-success">→</span></p>
            </a>

            <a href="{{ route('assignments.index') }}" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-info/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Assigned') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-info">{{ number_format($assigned) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-info/10 text-info" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path stroke-linecap="round" stroke-linejoin="round" d="M3 20a6 6 0 0 1 12 0m2-8.5a3.5 3.5 0 1 0-1-6.9M18 14a5 5 0 0 1 3 4.6V20"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs {{ $overdueAssignments > 0 ? 'font-medium text-error' : 'text-base-content/50' }}">
                    @if ($overdueAssignments > 0)
                        {{ trans_choice(':count overdue|:count overdue', $overdueAssignments) }}
                    @else
                        {{ __('Currently checked out') }}
                    @endif
                    <span class="ms-1 text-info">→</span>
                </p>
            </a>

            <a href="{{ route('maintenance.index') }}" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-warning/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60">{{ __('Under maintenance') }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-warning">{{ number_format($underMaintenance) }}</p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-warning/10 text-warning" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a5 5 0 0 0-6.4 6.4L3 18l3 3 5.3-5.3a5 5 0 0 0 6.4-6.4L14 12l-2-2 2.7-3.7Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50">{{ __('Open maintenance records') }} <span class="ms-1 text-warning">→</span></p>
            </a>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __('Assets by status') }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ __('A quick view of your inventory distribution.') }}</p>
                    </div>
                    <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-semibold text-base-content/60">{{ number_format($totalAssets) }}</span>
                </div>
                <ul class="mt-5 space-y-4">
                    @foreach (AssetStatus::cases() as $status)
                        @php($count = (int) ($statusCounts[$status->value] ?? 0))
                        <li>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium {{ $status->color() }}">{{ $status->label() }}</span>
                                <span class="font-semibold tabular-nums text-base-content">{{ number_format($count) }}</span>
                            </div>
                            <progress class="progress progress-primary h-1.5 w-full" value="{{ $count }}" max="{{ max(1, $totalAssets) }}" aria-label="{{ $status->label() }}: {{ $count }}"></progress>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card bg-base-100 p-5 sm:p-6">
                <div>
                    <h2 class="text-base font-semibold text-base-content">{{ __('Alerts') }}</h2>
                    <p class="mt-1 text-sm text-base-content/55">{{ __('Items that may need your attention.') }}</p>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    <li>
                        <div class="flex items-center justify-between gap-3 py-3">
                            <span class="text-sm text-base-content/80">{{ __('Warranties expiring (30 days)') }}</span>
                            <span class="min-w-9 rounded-full px-2 py-1 text-center text-xs font-semibold {{ $warrantyExpiring > 0 ? 'bg-warning/15 text-warning' : 'bg-base-200 text-base-content/60' }}">{{ $warrantyExpiring }}</span>
                        </div>
                    </li>
                    <li>
                        <a href="{{ route('maintenance.index') }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                            <span class="text-sm text-base-content/80">{{ __('Maintenance due / overdue') }}</span>
                            <span class="min-w-9 rounded-full px-2 py-1 text-center text-xs font-semibold {{ $maintenanceDue > 0 ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/60' }}">{{ $maintenanceDue }}</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('assignments.index') }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                            <span class="text-sm text-base-content/80">{{ __('Overdue assignments') }}</span>
                            <span class="min-w-9 rounded-full px-2 py-1 text-center text-xs font-semibold {{ $overdueAssignments > 0 ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/60' }}">{{ $overdueAssignments }}</span>
                        </a>
                    </li>
                </ul>
                <div class="mt-auto grid grid-cols-2 gap-3 border-t border-base-200 pt-4 text-sm">
                    <div>
                        <p class="text-xs text-base-content/50">{{ __('Open maintenance') }}</p>
                        <p class="mt-1 font-semibold tabular-nums text-base-content">{{ number_format($maintenanceOpen) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-base-content/50">{{ __('Maintenance due') }}</p>
                        <p class="mt-1 font-semibold tabular-nums {{ $maintenanceDue > 0 ? 'text-error' : 'text-base-content' }}">{{ number_format($maintenanceDue) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __('Top categories') }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ __('Categories with the most assets.') }}</p>
                    </div>
                    @can('manage-catalog')
                        <a href="{{ route('categories.index') }}" wire:navigate class="link link-hover text-xs font-medium text-primary">{{ __('Manage') }}</a>
                    @endcan
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($byCategory as $category)
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            <span class="min-w-0 truncate text-base-content/80">{{ $category->full_name }}</span>
                            <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-semibold tabular-nums text-base-content">{{ number_format($category->assets_count) }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No categories.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __('Top locations') }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ __('Locations with the most assets.') }}</p>
                    </div>
                    @can('manage-catalog')
                        <a href="{{ route('locations.index') }}" wire:navigate class="link link-hover text-xs font-medium text-primary">{{ __('Manage') }}</a>
                    @endcan
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($byLocation as $location)
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            <span class="min-w-0 truncate text-base-content/80">{{ $location->full_name }}</span>
                            <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-semibold tabular-nums text-base-content">{{ number_format($location->assets_count) }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('No locations.') }}</li>
                    @endforelse
                </ul>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __('Warranties expiring soon') }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ __('Next 30 days') }}</p>
                    </div>
                    <span class="rounded-full bg-warning/15 px-2.5 py-1 text-xs font-semibold text-warning">{{ $warrantyExpiring }}</span>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($warrantyList as $asset)
                        <li>
                            <a href="{{ route('assets.show', $asset) }}" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <span class="min-w-0 truncate text-sm font-medium text-primary">{{ $asset->name }}</span>
                                <span class="shrink-0 text-xs tabular-nums text-base-content/60">{{ $asset->warranty_expiry?->format('d M Y') }}</span>
                            </a>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('Nothing expiring soon.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content">{{ __('Maintenance due') }}</h2>
                        <p class="mt-1 text-sm text-base-content/55">{{ __('Scheduled for today or earlier') }}</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $maintenanceDue > 0 ? 'bg-error/10 text-error' : 'bg-base-200 text-base-content/60' }}">{{ $maintenanceDue }}</span>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    @forelse ($maintenanceDueList as $record)
                        <li class="flex items-center justify-between gap-3 py-3">
                            @if ($record->asset)
                                <a href="{{ route('assets.show', $record->asset) }}" wire:navigate class="min-w-0 truncate text-sm font-medium text-primary">{{ $record->title }}</a>
                            @else
                                <span class="min-w-0 truncate text-sm font-medium text-base-content">{{ $record->title }}</span>
                            @endif
                            <span class="shrink-0 text-xs tabular-nums text-base-content/60">{{ $record->scheduled_at?->format('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60">{{ __('Nothing due.') }}</li>
                    @endforelse
                </ul>
            </div>
        </section>
    </div>
</div>
