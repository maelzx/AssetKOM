<?php

use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Maintenance;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $status = 'open';

    #[Url(history: true)]
    public string $type = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $records = Maintenance::query()
            ->with(['asset', 'performer'])
            ->when($this->status === 'open', fn ($query) => $query->open())
            ->when(
                in_array($this->status, array_column(MaintenanceStatus::cases(), 'value'), true),
                fn ($query) => $query->where('status', $this->status)
            )
            ->when($this->type !== '', fn ($query) => $query->where('type', $this->type))
            ->latest('scheduled_at')
            ->paginate(15);

        return [
            'records' => $records,
            'types' => MaintenanceType::cases(),
            'counts' => [
                'open' => Maintenance::open()->count(),
                'scheduled' => Maintenance::where('status', MaintenanceStatus::Scheduled->value)->count(),
                'in_progress' => Maintenance::where('status', MaintenanceStatus::InProgress->value)->count(),
                'completed' => Maintenance::where('status', MaintenanceStatus::Completed->value)->count(),
            ],
        ];
    }
}; ?>

<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Maintenance')" :subtitle="__('Scheduled and completed maintenance across all assets.')" />

        <div class="flex flex-wrap items-center gap-2">
            @foreach (['open' => __('Open'), 'scheduled' => __('Scheduled'), 'in_progress' => __('In progress'), 'completed' => __('Completed'), 'all' => __('All')] as $key => $label)
                <button
                    wire:click="setStatus('{{ $key }}')"
                    class="rounded-md px-3 py-1.5 text-sm font-medium {{ $status === $key ? 'bg-primary text-white' : 'bg-base-100 text-base-content/80 shadow-sm hover:bg-base-200' }}"
                >
                    {{ $label }}
                    @if (isset($counts[$key]))
                        <span class="ms-1 text-xs opacity-75">{{ $counts[$key] }}</span>
                    @endif
                </button>
            @endforeach

            <div class="ms-auto">
                <select wire:model.live="type" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary rounded-md shadow-sm text-sm">
                    <option value="">{{ __('All types') }}</option>
                    @foreach ($types as $option)
                        <option value="{{ $option->value }}">{{ $option->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Asset') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Title') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Scheduled') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Cost') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Status') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($records as $record)
                            <tr wire:key="maintenance-{{ $record->id }}" class="hover:bg-base-200">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-mono text-base-content">{{ $record->asset?->asset_tag }}</span>
                                    <span class="block text-xs text-base-content/60">{{ $record->asset?->name }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $record->type->label() }}</td>
                                <td class="px-4 py-3 text-sm text-base-content">{{ $record->title }}</td>
                                <td class="px-4 py-3 text-sm {{ $record->isOverdue() ? 'font-semibold text-red-600' : 'text-base-content/60' }}">
                                    {{ $record->scheduled_at?->format('d M Y') ?? '—' }}
                                    @if ($record->isOverdue())
                                        <span class="block text-xs">{{ __('overdue') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">
                                    {{ $record->cost !== null ? \App\Support\Money::format($record->cost, $record->currency) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $record->status->color() }}">
                                        {{ $record->status->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    @if ($record->asset)
                                        <a href="{{ route('assets.show', $record->asset) }}" wire:navigate class="text-primary hover:text-primary">{{ __('View asset') }}</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No maintenance records found.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($records->hasPages())
                <div class="border-t border-base-300 px-4 py-3">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
