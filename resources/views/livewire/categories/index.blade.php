<?php

use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public ?int $parent_id = null;

    public string $description = '';

    public function mount(): void
    {
        Gate::authorize('manage-catalog');
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);

        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->parent_id = $category->parent_id;
        $this->description = (string) $category->description;
    }

    public function cancel(): void
    {
        $this->reset(['editingId', 'name', 'parent_id', 'description']);
        $this->resetValidation();
    }

    public function save(): void
    {
        Gate::authorize('manage-catalog');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->editingId && $this->parent_id === $this->editingId) {
            $this->addError('parent_id', __('A category cannot be its own parent.'));

            return;
        }

        if ($this->parent_id && $this->editingId && in_array($this->parent_id, $this->descendantIds($this->editingId), true)) {
            $this->addError('parent_id', __('A category cannot be nested under its own descendant.'));

            return;
        }

        if ($this->editingId) {
            Category::findOrFail($this->editingId)->update($validated);
        } else {
            Category::create($validated);
        }

        $this->cancel();
        $this->dispatch('category-saved');
    }

    public function delete(int $id): void
    {
        Gate::authorize('manage-catalog');

        Category::findOrFail($id)->delete();

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

            foreach (Category::where('parent_id', $current)->pluck('id') as $childId) {
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
            'categories' => Category::with('parent')->withCount('assets')->get()->sortBy('full_name'),
            'parents' => Category::with('parent')->whereNotIn('id', $excluded)->get()->sortBy('full_name'),
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Categories')" :subtitle="__('Organise assets into a hierarchy.')" />

        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900">
                {{ $editingId ? __('Edit category') : __('New category') }}
            </h3>

            <form wire:submit="save" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input wire:model="name" id="name" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
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

                <div class="sm:col-span-2">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea wire:model="description" id="description" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
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
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Assets') }}</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($categories as $category)
                        <tr wire:key="category-{{ $category->id }}">
                            <td class="px-4 py-2 text-sm text-gray-900">{{ $category->full_name }}</td>
                            <td class="px-4 py-2 text-sm text-gray-500">{{ $category->assets_count }}</td>
                            <td class="px-4 py-2 text-right text-sm whitespace-nowrap">
                                <button wire:click="edit({{ $category->id }})" class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</button>
                                <button wire:click="delete({{ $category->id }})" wire:confirm="{{ __('Delete this category?') }}" class="ms-3 text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No categories yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
