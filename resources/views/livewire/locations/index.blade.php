<?php

use App\Models\Location;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public ?int $parent_id = null;

    public string $address = '';

    public function mount(): void
    {
        Gate::authorize('manage-catalog');
    }

    public function edit(int $id): void
    {
        $location = Location::findOrFail($id);

        $this->editingId = $location->id;
        $this->name = $location->name;
        $this->code = (string) $location->code;
        $this->parent_id = $location->parent_id;
        $this->address = (string) $location->address;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'code', 'parent_id', 'address']);
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('manage-catalog');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('locations', 'code')->ignore($this->editingId)],
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingId && $this->parent_id === $this->editingId) {
            $this->addError('parent_id', __('A location cannot be its own parent.'));

            return;
        }

        if ($this->parent_id && $this->editingId && in_array($this->parent_id, $this->descendantIds($this->editingId), true)) {
            $this->addError('parent_id', __('A location cannot be nested under its own descendant.'));

            return;
        }

        if ($this->editingId) {
            Location::findOrFail($this->editingId)->update($validated);
        } else {
            Location::create($validated);
        }

        $this->cancel();
        $this->dispatch('location-saved');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-catalog');

        Location::findOrFail($id)->delete();

        if ($this->editingId === $id) {
            $this->cancel();
        }
    }

    /**
     * @return array<int, int>
     */
    protected function descendantIds(int $rootId): array
    {
        $ids = [];
        $stack = [$rootId];

        while ($stack) {
            $current = array_pop($stack);

            foreach (Location::where('parent_id', $current)->pluck('id') as $childId) {
                $ids[] = $childId;
                $stack[] = $childId;
            }
        }

        return $ids;
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $excluded = $this->editingId
            ? array_merge([$this->editingId], $this->descendantIds($this->editingId))
            : [];

        return [
            'locations' => Location::with('parent')->withCount('assets')->get()->sortBy('full_name'),
            'parents' => Location::with('parent')->whereNotIn('id', $excluded)->get()->sortBy('full_name'),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Locations')" :subtitle="__('Track where assets physically live.')" />

        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900">
                {{ $editingId ? __('Edit location') : __('New location') }}
            </h3>

            <form wire:submit="save" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="code" :value="__('Code')" />
                    <x-text-input wire:model="code" id="code" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="parent_id" :value="__('Parent')" />
                    <select wire:model="parent_id" id="parent_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}">{{ $parent->full_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="address" :value="__('Address')" />
                    <x-text-input wire:model="address" id="address" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>

                <div class="sm:col-span-2 flex items-center gap-3">
                    <x-primary-button>{{ $editingId ? __('Update') : __('Create') }}</x-primary-button>
                    @if ($editingId)
                        <button type="button" wire:click="cancel" class="text-sm text-gray-600 hover:text-gray-900">
                            {{ __('Cancel') }}
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Code') }}</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Assets') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($locations as $location)
                        <tr wire:key="location-{{ $location->id }}">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $location->full_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $location->code ?: '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $location->assets_count }}</td>
                            <td class="px-4 py-2 text-right text-sm whitespace-nowrap">
                                <button wire:click="edit({{ $location->id }})" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $location->id }})" wire:confirm="{{ __('Delete this location?') }}" class="ms-3 text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No locations yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
