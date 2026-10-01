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
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $filter === $key ? 'bg-primary text-white' : 'bg-base-100 text-base-content/80 shadow-sm hover:bg-base-200' }}"
                >
                    {{ $label }}
                    @if (isset($counts[$key]))
                        <span class="ms-1 text-xs opacity-75">{{ $counts[$key] }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="card bg-base-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Asset') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Assignee') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Assigned') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Due') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($assignments as $assignment)
                            <tr wire:key="assignment-{{ $assignment->id }}" class="{{ $assignment->isOverdue() ? 'bg-red-50' : 'hover:bg-base-200' }}">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-mono text-base-content">{{ $assignment->asset?->asset_tag }}</span>
                                    <span class="block text-xs text-base-content/60">{{ $assignment->asset?->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    {{ $assignment->assignable?->name ?? __('Unknown') }}
                                    <span class="block text-xs text-base-content/50">
                                        {{ $assignment->assignable_type === \App\Models\User::class ? __('User') : __('Location') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $assignment->assigned_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-sm {{ $assignment->isOverdue() ? 'font-semibold text-red-600' : 'text-base-content/60' }}">
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
                                        <a href="{{ route('assets.show', $assignment->asset) }}" wire:navigate class="text-primary hover:text-primary">{{ __('View asset') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No assignments found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assignments->hasPages())
                <div class="border-t border-base-300 px-4 py-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
