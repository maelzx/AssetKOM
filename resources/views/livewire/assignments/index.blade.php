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

<div class="py-8 sm:py-10">
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

        <x-data-table :paginator="$assignments">
            <x-slot name="table">
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
                            <tr wire:key="assignment-{{ $assignment->id }}" class="{{ $assignment->isOverdue() ? 'bg-error/10' : 'hover:bg-base-200' }}">
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
                                <td class="px-4 py-3 text-sm {{ $assignment->isOverdue() ? 'font-semibold text-error' : 'text-base-content/60' }}">
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
                                        <a href="{{ route('assets.show', $assignment->asset) }}" wire:navigate class="text-primary hover:opacity-80">{{ __('View asset') }}</a>
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
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @forelse ($assignments as $assignment)
                        <li class="space-y-2 p-4 {{ $assignment->isOverdue() ? 'bg-error/10' : '' }}" wire:key="assignment-card-{{ $assignment->id }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-medium text-base-content">{{ $assignment->asset?->name }}</p>
                                    <p class="font-mono text-xs text-base-content/50">{{ $assignment->asset?->asset_tag }}</p>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $assignment->status->color() }}">
                                    {{ $assignment->status->label() }}
                                </span>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-base-content/60">
                                <div><dt class="inline text-base-content/40">{{ __('Assignee') }}:</dt> {{ $assignment->assignable?->name ?? __('Unknown') }}</div>
                                <div><dt class="inline text-base-content/40">{{ __('Assigned') }}:</dt> {{ $assignment->assigned_at?->format('d M Y') }}</div>
                                <div class="{{ $assignment->isOverdue() ? 'font-semibold text-error' : '' }}">
                                    <dt class="inline font-normal text-base-content/40">{{ __('Due') }}:</dt> {{ $assignment->expected_return_at?->format('d M Y') ?? '—' }}
                                    @if ($assignment->isOverdue()) ({{ __('overdue') }}) @endif
                                </div>
                            </dl>
                            @if ($assignment->asset)
                                <a href="{{ route('assets.show', $assignment->asset) }}" wire:navigate class="inline-block text-sm text-primary hover:opacity-80">{{ __('View asset') }}</a>
                            @endif
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-base-content/60">{{ __('No assignments found.') }}</li>
                    @endforelse
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
