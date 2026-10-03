<?php

use App\Enums\Currency;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $org_name = '';

    public string $default_currency = 'MYR';

    public string $base_currency = 'MYR';

    public float $usd_to_myr_rate = 4.70;

    public string $asset_tag_prefix = 'AST';

    public string $depreciation_method = 'straight_line';

    public float $depreciation_rate = 20.0;

    public function mount(): void
    {
        Gate::authorize('manage-settings');

        $this->org_name = (string) Setting::get('org_name', 'AssetKOM');
        $this->default_currency = (string) Setting::get('default_currency', Currency::MYR->value);
        $this->base_currency = (string) Setting::get('base_currency', Currency::MYR->value);
        $this->usd_to_myr_rate = (float) Setting::get('usd_to_myr_rate', 4.70);
        $this->asset_tag_prefix = (string) Setting::get('asset_tag_prefix', 'AST');
        $this->depreciation_method = (string) Setting::get('depreciation_method', 'straight_line');
        $this->depreciation_rate = (float) Setting::get('depreciation_rate', 20);
    }

    public function save(): void
    {
        Gate::authorize('manage-settings');

        $validated = $this->validate([
            'org_name' => ['required', 'string', 'max:255'],
            'default_currency' => ['required', Rule::in(Currency::values())],
            'base_currency' => ['required', Rule::in(Currency::values())],
            'usd_to_myr_rate' => ['required', 'numeric', 'min:0.01'],
            'asset_tag_prefix' => ['required', 'string', 'max:10', 'alpha_num'],
            'depreciation_method' => ['required', Rule::in(['straight_line', 'reducing_balance'])],
            'depreciation_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, in_array($key, ['usd_to_myr_rate', 'depreciation_rate'], true) ? 'float' : 'string');
        }

        $this->dispatch('settings-saved');
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
        <form wire:submit="save" class="card bg-base-100 p-6 space-y-6">
            <div>
                <x-input-label for="org_name" :value="__('Organization name')" />
                <x-text-input wire:model="org_name" id="org_name" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="default_currency" :value="__('Default currency')" />
                    <select wire:model="default_currency" id="default_currency" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                        @foreach (\App\Enums\Currency::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->value }} — {{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="base_currency" :value="__('Base currency (reports)')" />
                    <select wire:model="base_currency" id="base_currency" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                        @foreach (\App\Enums\Currency::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->value }} — {{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('base_currency')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="usd_to_myr_rate" :value="__('USD → MYR rate (MYR per 1 USD)')" />
                    <x-text-input wire:model="usd_to_myr_rate" id="usd_to_myr_rate" type="number" step="0.0001" min="0.01" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-base-content/50">{{ __('Used for indicative base-currency totals in reports (no historical rates).') }}</p>
                    <x-input-error :messages="$errors->get('usd_to_myr_rate')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="asset_tag_prefix" :value="__('Asset tag prefix')" />
                    <x-text-input wire:model="asset_tag_prefix" id="asset_tag_prefix" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('asset_tag_prefix')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="depreciation_method" :value="__('Depreciation method')" />
                    <select wire:model.live="depreciation_method" id="depreciation_method" class="select select-bordered mt-1 block w-full">
                        <option value="straight_line">{{ __('Straight-line') }}</option>
                        <option value="reducing_balance">{{ __('Reducing balance') }}</option>
                    </select>
                    <p class="mt-1 text-xs text-base-content/50">{{ __('Applies to all assets (per-asset values still come from the asset).') }}</p>
                    <x-input-error :messages="$errors->get('depreciation_method')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="depreciation_rate" :value="__('Reducing-balance rate (% per year)')" />
                    <x-text-input wire:model="depreciation_rate" id="depreciation_rate" type="number" step="0.1" min="0" max="100" class="mt-1 block w-full" />
                    <p class="mt-1 text-xs text-base-content/50">
                        @if ($depreciation_method === 'reducing_balance')
                            {{ __('Used for the reducing-balance method. 0 uses double-declining (2 ÷ useful life).') }}
                        @else
                            {{ __('Only used when the reducing-balance method is selected.') }}
                        @endif
                    </p>
                    <x-input-error :messages="$errors->get('depreciation_rate')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
                <x-action-message on="settings-saved">{{ __('Saved.') }}</x-action-message>
            </div>
        </form>
        </div>
    </div>
</div>
