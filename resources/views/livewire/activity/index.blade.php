<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $log = '';

    #[Url(history: true)]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('view-reports');
    }

    public function updating(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $activities = Activity::query()
            ->with(['causer', 'subject'])
            ->when($this->log !== '', fn ($query) => $query->where('log_name', $this->log))
            ->when($this->search !== '', function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($query) use ($term): void {
                    $query->where('description', 'like', $term)
                        ->orWhere('subject_type', 'like', $term)
                        ->orWhere('event', 'like', $term);
                });
            })
            ->latest()
            ->paginate(20);

        return [
            'activities' => $activities,
            'logs' => Activity::query()->whereNotNull('log_name')->distinct()->orderBy('log_name')->pluck('log_name'),
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <x-page-header :title="__('Activity')" :subtitle="__('Audit trail of changes across the system.')" />

        <div class="card bg-base-100 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <select wire:model.live="log" class="select select-bordered w-full block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md">
                    <option value="">{{ __('All logs') }}</option>
                    @foreach ($logs as $name)
                        <option value="{{ $name }}">{{ ucfirst($name) }}</option>
                    @endforeach
                </select>
                <div class="sm:col-span-2">
                    <x-text-input wire:model.live.debounce.300ms="search" type="search" class="block w-full" placeholder="{{ __('Search description, subject, event…') }}" />
                </div>
            </div>
        </div>

        <x-data-table :paginator="$activities">
            <x-slot name="table">
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('When') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Log') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Event') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('Description') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-base-content/60 uppercase tracking-wider">{{ __('By') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        @forelse ($activities as $activity)
                            <tr wire:key="activity-{{ $activity->id }}">
                                <td class="px-4 py-3 text-sm text-base-content/60 whitespace-nowrap">{{ $activity->created_at?->format('d M Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $activity->log_name ? ucfirst($activity->log_name) : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $activity->event ? ucfirst($activity->event) : '—' }}</td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    {{ $activity->description }}
                                    @if ($activity->subject)
                                        <span class="block text-xs text-base-content/50">
                                            {{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60">{{ $activity->causer?->name ?? __('System') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-base-content/60">{{ __('No activity recorded.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-slot>

            <x-slot name="cards">
                <ul class="divide-y divide-base-300">
                    @forelse ($activities as $activity)
                        <li class="space-y-1 p-4" wire:key="activity-card-{{ $activity->id }}">
                            <div class="flex items-center justify-between gap-2 text-xs text-base-content/50">
                                <span>{{ $activity->created_at?->format('d M Y H:i') }}</span>
                                <span>{{ $activity->log_name ? ucfirst($activity->log_name) : '—' }} · {{ $activity->event ? ucfirst($activity->event) : '—' }}</span>
                            </div>
                            <p class="text-sm text-base-content">
                                {{ $activity->description }}
                                @if ($activity->subject)
                                    <span class="block text-xs text-base-content/50">{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                                @endif
                            </p>
                            <p class="text-xs text-base-content/50">{{ __('By') }} {{ $activity->causer?->name ?? __('System') }}</p>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-base-content/60">{{ __('No activity recorded.') }}</li>
                    @endforelse
                </ul>
            </x-slot>
        </x-data-table>
    </div>
</div>
