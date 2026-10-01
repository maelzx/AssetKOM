<?php

use App\Enums\AssignmentStatus;
use App\Models\AssetAssignment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $filter = 'active';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $assignments = AssetAssignment::query()
            ->with(['asset', 'assignable', 'assigner'])
            ->when($this->filter === 'active', fn ($query) => $query->active())
            ->when($this->filter === 'overdue', fn ($query) => $query->overdue())
            ->when($this->filter === 'returned', fn ($query) => $query->where('status', AssignmentStatus::Returned->value))
            ->orderByDesc('assigned_at')
            ->paginate(15);

        return [
            'assignments' => $assignments,
            'counts' => [
                'active' => AssetAssignment::active()->count(),
                'overdue' => AssetAssignment::overdue()->count(),
                'returned' => AssetAssignment::where('status', AssignmentStatus::Returned->value)->count(),
            ],
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Assignments')" :subtitle="__('Active check-outs and their history.')" />

        <div class="flex flex-wrap gap-2">
            @foreach (['active' => __('Active'), 'overdue' => __('Overdue'), 'returned' => __('Returned'), 'all' => __('All')] as $key => $label)
                <button
                    wire:click="setFilter('{{ $key }}')"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $filter === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 shadow-sm hover:bg-gray-50' }}"
                >
                    {{ $label }}
                    @if (isset($counts[$key]))
                        <span class="ms-1 text-xs opacity-75">{{ $counts[$key] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="bg-white shadow sm:rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Asset') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Assignee') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Assigned') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Due') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($assignments as $assignment)
                            <tr wire:key="assignment-{{ $assignment->id }}" class="{{ $assignment->isOverdue() ? 'bg-red-50' : 'hover:bg-gray-50' }}">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-mono text-gray-900">{{ $assignment->asset?->asset_tag }}</span>
                                    <span class="block text-xs text-gray-500">{{ $assignment->asset?->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ $assignment->assignable?->name ?? __('Unknown') }}
                                    <span class="block text-xs text-gray-400">
                                        {{ $assignment->assignable_type === \App\Models\User::class ? __('User') : __('Location') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $assignment->assigned_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm {{ $assignment->isOverdue() ? 'font-semibold text-red-600' : 'text-gray-500' }}">
                                    {{ $assignment->expected_return_at?->format('d M Y') ?? '—' }}
                                    @if ($assignment->isOverdue())
                                        <span class="block text-xs">{{ __('overdue') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $assignment->status->color() }}">
                                        {{ $assignment->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    @if ($assignment->asset)
                                        <a href="{{ route('assets.show', $assignment->asset) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">{{ __('View asset') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">{{ __('No assignments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assignments->hasPages())
                <div class="border-t border-gray-100 px-4 py-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
