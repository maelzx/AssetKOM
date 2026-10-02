<?php

use App\Enums\AssetStatus;
use App\Enums\Currency;
use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Category;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\Setting;
use App\Services\CurrencyConverter;
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
        $depreciationTotals = $this->depreciationTotals();
        $baseCurrency = Currency::tryFrom((string) Setting::get('base_currency', Currency::MYR->value)) ?? Currency::MYR;
        $converter = app(CurrencyConverter::class);

        $bookValueBase = 0.0;

        foreach ($depreciationTotals as $currency => $totals) {
            $bookValueBase += $converter->convert($totals['book'], Currency::from($currency), $baseCurrency);
        }

        return [
            'statusCounts' => Asset::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'byCategory' => Category::with('parent')->withCount('assets')->orderByDesc('assets_count')->get(),
            'byLocation' => Location::with('parent')->withCount('assets')->orderByDesc('assets_count')->get(),
            'maintenanceCounts' => Maintenance::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'purchaseTotals' => Asset::query()
                ->selectRaw('currency, sum(purchase_cost) as total')
                ->whereNotNull('purchase_cost')
                ->groupBy('currency')
                ->pluck('total', 'currency'),
            'activeAssignments' => AssetAssignment::active()->count(),
            'overdueAssignments' => AssetAssignment::overdue()->count(),
            'depreciationTotals' => $depreciationTotals,
            'baseCurrency' => $baseCurrency,
            'bookValueBase' => round($bookValueBase, 2),
            'fxRate' => (float) Setting::get('usd_to_myr_rate', 4.70),
        ];
    }

    /**
     * @return array<string, array{purchase: float, accumulated: float, book: float}>
     */
    protected function depreciationTotals(): array
    {
        $calculator = app(\App\Services\DepreciationCalculator::class);
        $totals = [];

        Asset::query()
            ->whereNotNull('purchase_cost')
            ->whereNotNull('purchase_date')
            ->whereNotNull('useful_life_years')
            ->cursor()
            ->each(function (Asset $asset) use ($calculator, &$totals): void {
                $currency = $asset->currency->value;
                $totals[$currency] ??= ['purchase' => 0.0, 'accumulated' => 0.0, 'book' => 0.0];
                $totals[$currency]['purchase'] += (float) $asset->purchase_cost;
                $totals[$currency]['accumulated'] += $calculator->accumulated($asset) ?? 0.0;
                $totals[$currency]['book'] += $calculator->bookValue($asset) ?? 0.0;
            });

        return $totals;
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Reports')" :subtitle="__('Summary figures and data exports.')">
            <x-slot name="actions">
                <a href="{{ route('exports.assets') }}" class="btn btn-primary btn-sm">{{ __('Export assets') }}</a>
                <a href="{{ route('exports.assignments') }}" class="btn btn-outline btn-sm">{{ __('Export assignments') }}</a>
                <a href="{{ route('exports.maintenance') }}" class="btn btn-outline btn-sm">{{ __('Export maintenance') }}</a>
            </x-slot>
        </x-page-header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="card bg-base-100 p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Assets by status') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach (AssetStatus::cases() as $status)
                        <li class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">{{ $status->label() }}</span>
                            <span class="font-medium text-base-content">{{ (int) ($statusCounts[$status->value] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card bg-base-100 p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Maintenance by status') }}</h3>
                <ul class="mt-4 space-y-2">
                    @foreach (MaintenanceStatus::cases() as $status)
                        <li class="flex items-center justify-between text-sm">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $status->color() }}">{{ $status->label() }}</span>
                            <span class="font-medium text-base-content">{{ (int) ($maintenanceCounts[$status->value] ?? 0) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="card bg-base-100 p-6">
                <h3 class="text-lg font-medium text-base-content">{{ __('Purchase value') }}</h3>
                <ul class="mt-4 space-y-2">
                    @forelse ($purchaseTotals as $currency => $total)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-base-content/80">{{ $currency }}</span>
                            <span class="font-medium text-base-content">{{ Money::format($total, $currency) }}</span>
                        </li>
                    @empty
                        <li class="text-sm text-base-content/60">{{ __('No purchase costs recorded.') }}</li>
                    @endforelse
                </ul>
                <div class="mt-4 border-t border-base-300 pt-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/80">{{ __('Active assignments') }}</span>
                        <span class="font-medium text-base-content">{{ $activeAssignments }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-base-content/80">{{ __('Overdue assignments') }}</span>
                        <span class="font-medium {{ $overdueAssignments > 0 ? 'text-red-600' : 'text-base-content' }}">{{ $overdueAssignments }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 overflow-hidden">
            <div class="border-b border-base-300 px-6 py-4">
                <h3 class="text-lg font-medium text-base-content">{{ __('Depreciation (straight-line)') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-6 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Currency') }}</th>
                            <th class="px-6 py-2 text-right text-xs font-medium text-base-content/60 uppercase">{{ __('Purchase cost') }}</th>
                            <th class="px-6 py-2 text-right text-xs font-medium text-base-content/60 uppercase">{{ __('Accumulated') }}</th>
                            <th class="px-6 py-2 text-right text-xs font-medium text-base-content/60 uppercase">{{ __('Book value') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($depreciationTotals as $currency => $totals)
                            <tr>
                                <td class="px-6 py-2 text-base-content/80">{{ $currency }}</td>
                                <td class="px-6 py-2 text-right text-base-content/70">{{ Money::format($totals['purchase'], $currency) }}</td>
                                <td class="px-6 py-2 text-right text-base-content/70">{{ Money::format($totals['accumulated'], $currency) }}</td>
                                <td class="px-6 py-2 text-right font-medium text-base-content">{{ Money::format($totals['book'], $currency) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-4 text-sm text-base-content/60">{{ __('No depreciable assets.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (count($depreciationTotals) > 1)
                <div class="border-t border-base-300 px-6 py-3 text-xs text-base-content/60">
                    {{ __('Indicative total book value') }}:
                    <span class="font-medium text-base-content">{{ Money::format($bookValueBase, $baseCurrency) }}</span>
                    ({{ __('converted to :base at USD→MYR :rate; no historical rates)', ['base' => $baseCurrency->value, 'rate' => $fxRate]) }})
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="card bg-base-100 overflow-hidden">
                <div class="border-b border-base-300 px-6 py-4">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Assets by category') }}</h3>
                </div>
                <table class="table table-sm">
                    <tbody class="divide-y divide-base-300">
                        @forelse ($byCategory as $category)
                            <tr>
                                <td class="px-6 py-2 text-sm text-base-content/80">{{ $category->full_name }}</td>
                                <td class="px-6 py-2 text-sm text-right font-medium text-base-content">{{ $category->assets_count }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-4 text-sm text-base-content/60">{{ __('No categories.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card bg-base-100 overflow-hidden">
                <div class="border-b border-base-300 px-6 py-4">
                    <h3 class="text-lg font-medium text-base-content">{{ __('Assets by location') }}</h3>
                </div>
                <table class="table table-sm">
                    <tbody class="divide-y divide-base-300">
                        @forelse ($byLocation as $location)
                            <tr>
                                <td class="px-6 py-2 text-sm text-base-content/80">{{ $location->full_name }}</td>
                                <td class="px-6 py-2 text-sm text-right font-medium text-base-content">{{ $location->assets_count }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-6 py-4 text-sm text-base-content/60">{{ __('No locations.') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
