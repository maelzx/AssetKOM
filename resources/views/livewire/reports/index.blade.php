<?php

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\Location;
use App\Models\Maintenance;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function mount(): void
    {
        Gate::authorize('view-reports');
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'statusCounts' => Asset::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'byCategory' => Category::withCount('assets')->orderByDesc('assets_count')->get(),
            'byLocation' => Location::withCount('assets')->orderByDesc('assets_count')->get(),
            'maintenanceCounts' => Maintenance::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'purchaseTotals' => Asset::query()
                ->selectRaw('currency, sum(purchase_cost) as total')
                ->whereNotNull('purchase_cost')
                ->groupBy('currency')
                ->pluck('total', 'currency'),
            'activeAssignments' => AssetAssignment::active()->count(),
            'overdueAssignments' => AssetAssignment::overdue()->count(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Reports')" :subtitle="__('Summary figures and data exports.')">
            <x-slot name="actions">
                <a href="{{ route('exports.assets') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{{ __('Export assets') }}</a>
                <a href="{{ route('exports.assignments') }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Export assignments') }}</a>
                <a href="{{ route('exports.maintenance') }}" class="inline-flex items-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">{{ __('Export maintenance') }}</a>
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Assets by status') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach (AssetStatus::cases() as $status)
                        <li class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">{{ $status->label() }}</span>
                            <span class="font-medium text-gray-900">{{ (int) ($statusCounts[$status->value] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Maintenance by status') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach (MaintenanceStatus::cases() as $status)
                        <li class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">{{ $status->label() }}</span>
                            <span class="font-medium text-gray-900">{{ (int) ($maintenanceCounts[$status->value] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Purchase value') }}</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($purchaseTotals as $currency => $total)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-gray-700">{{ $currency }}</span>
                            <span class="font-medium text-gray-900">{{ Money::format($total, $currency) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-gray-500">{{ __('No purchase costs recorded.') }}</li>
                    @endforelse
                </ul>
                <div class="mt-4 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700">{{ __('Active assignments') }}</span>
                        <span class="font-medium text-gray-900">{{ $activeAssignments }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-gray-700">{{ __('Overdue assignments') }}</span>
                        <span class="font-medium {{ $overdueAssignments > 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $overdueAssignments }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Assets by category') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($byCategory as $category)
                            <tr>
                                <td class="px-6 py-2 text-sm text-gray-700">{{ $category->full_name }}</td>
                                <td class="px-6 py-2 text-sm text-right font-medium text-gray-900">{{ $category->assets_count }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-4 text-sm text-gray-500">{{ __('No categories.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Assets by location') }}</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-100">
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($byLocation as $location)
                            <tr>
                                <td class="px-6 py-2 text-sm text-gray-700">{{ $location->full_name }}</td>
                                <td class="px-6 py-2 text-sm text-right font-medium text-gray-900">{{ $location->assets_count }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-4 text-sm text-gray-500">{{ __('No locations.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
