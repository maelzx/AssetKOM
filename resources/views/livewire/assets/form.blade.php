<?php

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\Currency;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public ?Asset $asset = null;

    public string $asset_tag = '';

    public string $name = '';

    public string $description = '';

    public ?int $category_id = null;

    public ?int $location_id = null;

    public string $status = 'available';

    public string $condition = 'good';

    public string $serial_number = '';

    public string $manufacturer = '';

    public string $model = '';

    public ?string $purchase_date = null;

    public ?string $purchase_cost = null;

    public string $currency = 'MYR';

    public ?string $salvage_value = null;

    public ?int $useful_life_years = null;

    public ?string $warranty_expiry = null;

    public string $supplier = '';

    public mixed $image = null;

    public ?string $existingImage = null;

    /**
     * @var array<int, array{key: string, value: string}>
     */
    public array $customFields = [];

    public function mount(?Asset $asset = null): void
    {
        Gate::authorize('manage-assets');

        $this->asset = $asset;

        if ($asset?->exists) {
            $this->asset_tag = $asset->asset_tag;
            $this->name = $asset->name;
            $this->description = (string) $asset->description;
            $this->category_id = $asset->category_id;
            $this->location_id = $asset->location_id;
            $this->status = $asset->status->value;
            $this->condition = $asset->condition?->value ?? AssetCondition::Good->value;
            $this->serial_number = (string) $asset->serial_number;
            $this->manufacturer = (string) $asset->manufacturer;
            $this->model = (string) $asset->model;
            $this->purchase_date = $asset->purchase_date?->format('Y-m-d');
            $this->purchase_cost = $asset->purchase_cost;
            $this->currency = $asset->currency->value;
            $this->salvage_value = $asset->salvage_value;
            $this->useful_life_years = $asset->useful_life_years;
            $this->warranty_expiry = $asset->warranty_expiry?->format('Y-m-d');
            $this->supplier = (string) $asset->supplier;
            $this->existingImage = $asset->image_path;
            $this->customFields = collect($asset->custom_fields ?? [])
                ->map(fn ($value, $key): array => ['key' => (string) $key, 'value' => (string) $value])
                ->values()
                ->all();

            return;
        }

        $this->currency = (string) Setting::get('default_currency', Currency::MYR->value);
    }

    public function addCustomField(): void
    {
        $this->customFields[] = ['key' => '', 'value' => ''];
    }

    public function removeCustomField(int $index): void
    {
        unset($this->customFields[$index]);
        $this->customFields = array_values($this->customFields);
    }

    public function save(): void
    {
        Gate::authorize('manage-assets');

        $validated = $this->validate([
            'asset_tag' => ['nullable', 'string', 'max:100', Rule::unique('assets', 'asset_tag')->ignore($this->asset?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'status' => ['required', Rule::in(AssetStatus::values())],
            'condition' => ['required', Rule::in(AssetCondition::values())],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(Currency::values())],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warranty_expiry' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'max:2048'],
            'customFields' => ['array'],
            'customFields.*.key' => ['nullable', 'string', 'max:100'],
            'customFields.*.value' => ['nullable', 'string', 'max:1000'],
        ]);

        $custom = [];

        foreach ($this->customFields as $field) {
            $key = trim((string) ($field['key'] ?? ''));

            if ($key !== '') {
                $custom[$key] = (string) ($field['value'] ?? '');
            }
        }

        $validated['custom_fields'] = $custom;

        if ($this->image) {
            $validated['image_path'] = $this->image->store('asset-images', 'public');

            if ($this->existingImage) {
                Storage::disk('public')->delete($this->existingImage);
            }
        }

        unset($validated['image'], $validated['customFields']);

        if ($this->asset?->exists) {
            $this->asset->update($validated);
            $asset = $this->asset;
        } else {
            $validated['created_by'] = auth()->id();
            $asset = Asset::create($validated);
        }

        session()->flash('status', __('Asset saved.'));

        $this->redirectRoute('assets.show', $asset, navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'locations' => Location::orderBy('name')->get(),
            'statuses' => AssetStatus::cases(),
            'conditions' => AssetCondition::cases(),
            'currencies' => Currency::cases(),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header
            :title="$asset?->exists ? __('Edit asset') : __('New asset')"
            :subtitle="$asset?->exists ? $asset->asset_tag : __('Leave the tag blank to auto-generate one.')"
        />

        <form wire:submit="save" class="space-y-6">
            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Identity') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="asset_tag" :value="__('Asset tag')" />
                        <x-text-input wire:model="asset_tag" id="asset_tag" type="text" class="mt-1 block w-full" placeholder="AST-0001" />
                        <x-input-error :messages="$errors->get('asset_tag')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" :value="__('Name')" />
                        <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="category_id" :value="__('Category')" />
                        <select wire:model="category_id" id="category_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach ($categories as $option)
                                <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="location_id" :value="__('Current location')" />
                        <select wire:model="location_id" id="location_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">{{ __('— None —') }}</option>
                            @foreach ($locations as $option)
                                <option value="{{ $option->id }}">{{ $option->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('location_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select wire:model="status" id="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($statuses as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="condition" :value="__('Condition')" />
                        <select wire:model="condition" id="condition" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($conditions as $option)
                                <option value="{{ $option->value }}">{{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('condition')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="description" :value="__('Description')" />
                        <textarea wire:model="description" id="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                        <x-input-error :messages="$errors->get('description')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Hardware details') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="serial_number" :value="__('Serial number')" />
                        <x-text-input wire:model="serial_number" id="serial_number" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="manufacturer" :value="__('Manufacturer')" />
                        <x-text-input wire:model="manufacturer" id="manufacturer" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="model" :value="__('Model')" />
                        <x-text-input wire:model="model" id="model" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('model')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label for="image" :value="__('Photo')" />
                    <input wire:model="image" id="image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-600" />
                    <x-input-error :messages="$errors->get('image')" class="mt-2" />

                    @if ($image)
                        <img src="{{ $image->temporaryUrl() }}" class="mt-3 h-32 w-32 rounded object-cover" alt="">
                    @elseif ($existingImage)
                        <img src="{{ Storage::disk('public')->url($existingImage) }}" class="mt-3 h-32 w-32 rounded object-cover" alt="">
                    @endif
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Financials') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="purchase_date" :value="__('Purchase date')" />
                        <x-text-input wire:model="purchase_date" id="purchase_date" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('purchase_date')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="purchase_cost" :value="__('Purchase cost')" />
                        <x-text-input wire:model="purchase_cost" id="purchase_cost" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('purchase_cost')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="currency" :value="__('Currency')" />
                        <select wire:model="currency" id="currency" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($currencies as $option)
                                <option value="{{ $option->value }}">{{ $option->value }} — {{ $option->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="salvage_value" :value="__('Salvage value')" />
                        <x-text-input wire:model="salvage_value" id="salvage_value" type="number" step="0.01" min="0" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('salvage_value')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="useful_life_years" :value="__('Useful life (years)')" />
                        <x-text-input wire:model="useful_life_years" id="useful_life_years" type="number" min="1" max="100" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('useful_life_years')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="warranty_expiry" :value="__('Warranty expiry')" />
                        <x-text-input wire:model="warranty_expiry" id="warranty_expiry" type="date" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('warranty_expiry')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-3">
                        <x-input-label for="supplier" :value="__('Supplier')" />
                        <x-text-input wire:model="supplier" id="supplier" type="text" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('supplier')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="bg-white shadow sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Custom fields') }}</h3>
                    <button type="button" wire:click="addCustomField" class="text-sm text-indigo-600 hover:text-indigo-900">
                        {{ __('+ Add field') }}
                    </button>
                </div>

                @forelse ($customFields as $index => $field)
                    <div class="flex items-start gap-3" wire:key="custom-field-{{ $index }}">
                        <div class="w-1/3">
                            <x-text-input wire:model="customFields.{{ $index }}.key" type="text" class="block w-full" placeholder="{{ __('Label') }}" />
                        </div>
                        <div class="flex-1">
                            <x-text-input wire:model="customFields.{{ $index }}.value" type="text" class="block w-full" placeholder="{{ __('Value') }}" />
                        </div>
                        <button type="button" wire:click="removeCustomField({{ $index }})" class="mt-2 text-sm text-red-600 hover:text-red-900">
                            {{ __('Remove') }}
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No custom fields. Add any extra attributes you need.') }}</p>
                @endforelse
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button>{{ __('Save asset') }}</x-primary-button>
                <a href="{{ route('assets.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</div>
