<?php

use App\Services\AssetCsvImporter;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public mixed $file = null;

    /**
     * @var array<int, string>
     */
    public array $headers = [];

    /**
     * @var array<int, array<string, string|null>>
     */
    public array $preview = [];

    /**
     * @var array<string, string>
     */
    public array $mapping = [];

    /**
     * @var array{rows: array<int, array<string, mixed>>, summary: array<string, int>}|null
     */
    public ?array $report = null;

    /**
     * @var array{created: int, updated: int, skipped: int}|null
     */
    public ?array $result = null;

    public function mount(): void
    {
        Gate::authorize('manage-assets');
    }

    public function parse(AssetCsvImporter $importer): void
    {
        Gate::authorize('manage-assets');

        $this->validate([
            'file' => ['required', 'file', 'max:5120', 'mimes:csv,txt'],
        ]);

        $data = $importer->preview($this->file->getRealPath());

        $this->headers = $data['headers'];
        $this->preview = $data['preview'];
        $this->mapping = $this->guessMapping($this->headers);
        $this->report = null;
        $this->result = null;
    }

    public function dryRun(AssetCsvImporter $importer): void
    {
        Gate::authorize('manage-assets');

        if (! $this->ensureParsed()) {
            return;
        }

        $this->report = $importer->dryRun($this->file->getRealPath(), $this->mapping);
        $this->result = null;
    }

    public function import(AssetCsvImporter $importer): void
    {
        Gate::authorize('manage-assets');

        if (! $this->ensureParsed()) {
            return;
        }

        $this->result = $importer->import($this->file->getRealPath(), $this->mapping);
        $this->report = null;
    }

    protected function ensureParsed(): bool
    {
        if ($this->headers === []) {
            $this->addError('file', __('Parse a CSV file first.'));

            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<string, string>
     */
    protected function guessMapping(array $headers): array
    {
        $byNormalised = [];

        foreach ($headers as $header) {
            $byNormalised[$this->normalise($header)] = $header;
        }

        $mapping = [];

        foreach (array_keys(AssetCsvImporter::FIELDS) as $field) {
            $mapping[$field] = $byNormalised[$this->normalise($field)] ?? '';
        }

        return $mapping;
    }

    protected function normalise(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($value)) ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'fields' => AssetCsvImporter::FIELDS,
        ];
    }
}; ?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <x-page-header :title="__('Import assets')" :subtitle="__('Upload a CSV, map the columns, dry-run, then import. Existing tags are updated.')" />

        <div class="card bg-base-100 p-6 space-y-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <x-input-label for="csv" :value="__('CSV file')" />
                    <input wire:model="file" id="csv" type="file" accept=".csv,text/csv" class="mt-1 block text-sm text-base-content/70" />
                    <x-input-error :messages="$errors->get('file')" class="mt-1" />
                </div>
                <x-primary-button wire:click="parse">{{ __('Parse') }}</x-primary-button>
            </div>

            @if ($headers !== [])
                <p class="text-sm text-base-content/60">{{ trans_choice(':count column detected|:count columns detected', count($headers)) }}</p>
            @endif
        </div>

        @if ($headers !== [])
            <div class="card bg-base-100 p-6 space-y-4">
                <h3 class="text-lg font-medium text-base-content">{{ __('Column mapping') }}</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($fields as $field => $label)
                        <div>
                            <x-input-label :for="'map_'.$field" :value="$label" />
                            <select wire:model="mapping.{{ $field }}" id="map_{{ $field }}" class="select select-bordered w-full mt-1 block w-full border-base-300 focus:border-primary focus:ring-primary rounded-md text-sm">
                                <option value="">{{ __('— none —') }}</option>
                                @foreach ($headers as $header)
                                    <option value="{{ $header }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-base-300 pt-4">
                    <h4 class="text-sm font-medium text-base-content/80">{{ __('Preview') }}</h4>
                    <div class="mt-2 overflow-x-auto">
                        <table class="table table-sm text-xs">
                            <thead class="bg-base-200">
                                <tr>
                                    @foreach ($headers as $header)
                                        <th class="px-2 py-1 text-left font-medium text-base-content/60">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-base-300">
                                @foreach ($preview as $row)
                                    <tr>
                                        @foreach ($headers as $header)
                                            <td class="px-2 py-1 text-base-content/70">{{ $row[$header] ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <x-secondary-button wire:click="dryRun">{{ __('Dry run') }}</x-secondary-button>
                    <x-primary-button wire:click="import" wire:confirm="{{ __('Import these rows now?') }}">{{ __('Import') }}</x-primary-button>
                </div>
            </div>
        @endif

        @if ($report)
            <div class="card bg-base-100 p-6 space-y-4">
                <h3 class="text-lg font-medium text-base-content">{{ __('Dry-run report') }}</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    <span class="text-green-700">{{ __('Create') }}: <strong>{{ $report['summary']['create'] }}</strong></span>
                    <span class="text-blue-700">{{ __('Update') }}: <strong>{{ $report['summary']['update'] }}</strong></span>
                    <span class="text-amber-700">{{ __('Restore') }}: <strong>{{ $report['summary']['restore'] }}</strong></span>
                    <span class="text-red-700">{{ __('Errors') }}: <strong>{{ $report['summary']['errors'] }}</strong></span>
                    <span class="text-base-content/60">{{ __('Total') }}: <strong>{{ $report['summary']['total'] }}</strong></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead class="bg-base-200">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Line') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Tag') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Name') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Action') }}</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-base-content/60 uppercase">{{ __('Issues') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-base-300">
                            @foreach ($report['rows'] as $row)
                                <tr class="{{ $row['errors'] ? 'bg-red-50' : '' }}">
                                    <td class="px-3 py-1.5 text-base-content/60">{{ $row['line'] }}</td>
                                    <td class="px-3 py-1.5 font-mono text-base-content/80">{{ $row['asset_tag'] ?: '—' }}</td>
                                    <td class="px-3 py-1.5 text-base-content/80">{{ $row['name'] ?: '—' }}</td>
                                    <td class="px-3 py-1.5">
                                        <span class="text-xs {{ $row['action'] === 'update' ? 'text-blue-700' : 'text-green-700' }}">{{ ucfirst($row['action']) }}</span>
                                    </td>
                                    <td class="px-3 py-1.5 text-xs text-red-600">{{ implode('; ', $row['errors']) ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($result)
            <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">
                {{ __('Import complete — :created created, :updated updated, :restored restored, :skipped skipped.', $result) }}
                <a href="{{ route('assets.index') }}" wire:navigate class="ms-2 font-medium underline">{{ __('View assets') }}</a>
            </div>
        @endif
    </div>
</div>
