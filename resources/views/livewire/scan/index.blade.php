<?php

use App\Models\Asset;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $code = '';

    public function find(): void
    {
        $this->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($this->code);

        $asset = Asset::query()
            ->where('asset_tag', $code)
            ->orWhere('serial_number', $code)
            ->orWhereRaw('LOWER(asset_tag) = ?', [strtolower($code)])
            ->orWhereRaw('LOWER(serial_number) = ?', [strtolower($code)])
            ->first();

        if (! $asset) {
            $this->addError('code', __('No asset found for ":code".', ['code' => $code]));

            return;
        }

        $this->redirectRoute('assets.show', $asset, navigate: true);
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Find by code')" :subtitle="__('Scan or type an asset tag or serial number and press Enter.')" />

        <div class="card max-w-2xl bg-base-100 p-6">
            <form wire:submit="find" class="flex flex-wrap items-end gap-3">
                <div class="min-w-56 flex-1">
                    <x-input-label for="code" :value="__('Asset tag or serial number')" />
                    <x-text-input wire:model="code" id="code" type="text" autofocus autocomplete="off" class="mt-1 block w-full text-lg" placeholder="AST-0001" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <x-primary-button>{{ __('Find') }}</x-primary-button>
            </form>

            <p class="mt-4 text-xs text-base-content/50">
                {{ __('Hardware barcode scanners work here too — they type the code and press Enter. Camera scanning can be wired in later.') }}
            </p>
        </div>
    </div>
</div>
