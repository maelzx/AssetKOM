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

    public function mount(): void
    {
        Gate::authorize('manage-settings');

        $this->org_name = (string) Setting::get('org_name', 'AssetKOM');
        $this->default_currency = (string) Setting::get('default_currency', Currency::MYR->value);
        $this->base_currency = (string) Setting::get('base_currency', Currency::MYR->value);
        $this->usd_to_myr_rate = (float) Setting::get('usd_to_myr_rate', 4.70);
        $this->asset_tag_prefix = (string) Setting::get('asset_tag_prefix', 'AST');
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
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value, $key === 'usd_to_myr_rate' ? 'float' : 'string');
        }

        $this->dispatch('settings-saved');
    }
}; ?>

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form wire:submit="save" class="card bg-base-100 shadow-sm p-6 space-y-6">
            <div>
                <x-input-label for="org_name" :value="__('Organization name')" />
                <x-text-input wire:model="org_name" id="org_name" type="text" class="mt-1 block w-full" />
                <x-input-error :messages="$errors->get('org_name')" class="mt-2" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="default_currency" :value="__('Default currency')" />
                    <select wire:model="default_currency" id="default_currency" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                        @foreach (\App\Enums\Currency::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->value }} — {{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('default_currency')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="base_currency" :value="__('Base currency (reports)')" />
                    <select wire:model="base_currency" id="base_currency" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm">
                        @foreach (\App\Enums\Currency::cases() as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->value }} — {{ $currency->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('base_currency')" class="mt-2" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <x-input-label for="usd_to_myr_rate" :value="__('USD → MYR rate')" />
                    <x-text-input wire:model="usd_to_myr_rate" id="usd_to_myr_rate" type="number" step="0.0001" min="0.01" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('usd_to_myr_rate')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="asset_tag_prefix" :value="__('Asset tag prefix')" />
                    <x-text-input wire:model="asset_tag_prefix" id="asset_tag_prefix" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('asset_tag_prefix')" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <x-primary-button>{{ __('Save') }}</x-primary-button>
                <x-action-message on="settings-saved">{{ __('Saved.') }}</x-action-message>
            </div>
        </form>
    </div>
</div>
