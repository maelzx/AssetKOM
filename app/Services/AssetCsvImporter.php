<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\Currency;
use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Csv\Reader;

/**
 * CSV asset import with column mapping, dry-run validation and
 * idempotent upserts keyed by asset tag.
 *
 * Dry-run and import share the same row analysis so they always agree.
 */
class AssetCsvImporter
{
    /**
     * Target fields => human labels shown in the mapping UI.
     *
     * @var array<string, string>
     */
    public const FIELDS = [
        'asset_tag' => 'Asset tag',
        'name' => 'Name',
        'description' => 'Description',
        'category' => 'Category',
        'location' => 'Location',
        'status' => 'Status',
        'condition' => 'Condition',
        'serial_number' => 'Serial number',
        'manufacturer' => 'Manufacturer',
        'model' => 'Model',
        'purchase_date' => 'Purchase date',
        'purchase_cost' => 'Purchase cost',
        'currency' => 'Currency',
        'salvage_value' => 'Salvage value',
        'useful_life_years' => 'Useful life (years)',
        'warranty_expiry' => 'Warranty expiry',
        'supplier' => 'Supplier',
    ];

    public function __construct(private readonly AssetStatusTransition $transitions) {}

    /**
     * @return array{headers: array<int, string>, preview: array<int, array<string, string|null>>}
     */
    public function preview(string $path, int $limit = 5): array
    {
        $reader = $this->reader($path);
        $preview = [];

        foreach ($reader->getRecords() as $record) {
            $preview[] = $record;

            if (count($preview) >= $limit) {
                break;
            }
        }

        return [
            'headers' => array_values($reader->getHeader()),
            'preview' => $preview,
        ];
    }

    /**
     * Validate every row without writing anything.
     *
     * @param  array<string, string|null>  $mapping
     * @return array{rows: array<int, array<string, mixed>>, summary: array<string, int>}
     */
    public function dryRun(string $path, array $mapping): array
    {
        $rows = $this->analyze($path, $mapping);
        $summary = ['create' => 0, 'update' => 0, 'restore' => 0, 'errors' => 0, 'total' => count($rows)];

        foreach ($rows as $row) {
            if ($row['errors'] !== []) {
                $summary['errors']++;
            } else {
                $summary[$row['action']]++;
            }
        }

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Import valid rows, creating, updating or restoring by asset tag.
     *
     * @param  array<string, string|null>  $mapping
     * @return array{created: int, updated: int, restored: int, skipped: int}
     */
    public function import(string $path, array $mapping): array
    {
        $rows = $this->analyze($path, $mapping);
        $categories = $this->nameMap(Category::class);
        $locations = $this->nameMap(Location::class);

        $result = ['created' => 0, 'updated' => 0, 'restored' => 0, 'skipped' => 0];

        DB::transaction(function () use ($rows, $categories, $locations, &$result): void {
            foreach ($rows as $row) {
                if ($row['errors'] !== []) {
                    $result['skipped']++;

                    continue;
                }

                $data = $row['data'];
                $tag = $row['asset_tag'];
                $attributes = $this->attributes($data, $categories, $locations);

                $asset = $tag ? Asset::withTrashed()->firstOrNew(['asset_tag' => $tag]) : new Asset;
                $wasTrashed = $asset->exists && $asset->trashed();

                if ($wasTrashed) {
                    $asset->restore();
                }

                if (! $asset->exists) {
                    $asset->created_by = auth()->id();
                }

                $asset->fill($attributes);
                $asset->save();

                if ($wasTrashed) {
                    $result['restored']++;
                } elseif ($asset->wasRecentlyCreated) {
                    $result['created']++;
                } else {
                    $result['updated']++;
                }
            }
        });

        return $result;
    }

    /**
     * Analyse every row (shared by dry-run and import).
     *
     * @param  array<string, string|null>  $mapping
     * @return array<int, array{line: int, asset_tag: ?string, name: ?string, action: string, errors: array<int, string>, data: array<string, mixed>}>
     */
    protected function analyze(string $path, array $mapping): array
    {
        $categories = $this->nameMap(Category::class);
        $locations = $this->nameMap(Location::class);

        /** @var Collection<string, Asset> $existing */
        $existing = Asset::withTrashed()->get()->keyBy('asset_tag');

        $seen = [];
        $rows = [];
        $line = 1;

        foreach ($this->records($path) as $record) {
            $line++;
            $data = $this->mapped($record, $mapping);
            $errors = $this->validateRow($data, $categories, $locations);

            $tag = $data['asset_tag'] ?? null;

            if ($tag !== null) {
                if (isset($seen[$tag])) {
                    $errors[] = __('Duplicate asset tag in this file.');
                }

                $seen[$tag] = true;
            }

            $current = $tag !== null ? $existing->get($tag) : null;

            if ($current && ! $current->trashed()) {
                $errors = array_merge($errors, $this->validateTransition($data, $current));
                $action = 'update';
            } elseif ($current) {
                $action = 'restore';
            } else {
                $errors = array_merge($errors, $this->validateInitialStatus($data));
                $action = 'create';
            }

            $rows[] = [
                'line' => $line,
                'asset_tag' => $tag,
                'name' => $data['name'] ?? null,
                'action' => $action,
                'errors' => array_values(array_unique($errors)),
                'data' => $data,
            ];
        }

        return $rows;
    }

    protected function reader(string $path): Reader
    {
        $reader = Reader::from($path, 'r');
        $reader->setHeaderOffset(0);

        return $reader;
    }

    /**
     * @return iterable<int, array<string, string|null>>
     */
    protected function records(string $path): iterable
    {
        return $this->reader($path)->getRecords();
    }

    /**
     * Turn a raw row into target-field values using the mapping.
     *
     * @param  array<string, string|null>  $record
     * @param  array<string, string|null>  $mapping
     * @return array<string, mixed>
     */
    protected function mapped(array $record, array $mapping): array
    {
        $data = [];

        foreach ($mapping as $field => $column) {
            if ($column === null || $column === '') {
                continue;
            }

            $value = $record[$column] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$field] = $value === '' ? null : $value;
        }

        foreach (['status', 'condition'] as $enumField) {
            if (isset($data[$enumField]) && is_string($data[$enumField])) {
                $data[$enumField] = strtolower($data[$enumField]);
            }
        }

        if (isset($data['currency']) && is_string($data['currency'])) {
            $data['currency'] = strtoupper($data['currency']);
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    protected function validateRow(array $data, array $categories, array $locations): array
    {
        $errors = Validator::make($data, $this->rules())->errors()->all();

        if (! empty($data['category']) && ! isset($categories[$this->normalise($data['category'])])) {
            $errors[] = __('Unknown category: :name', ['name' => $data['category']]);
        }

        if (! empty($data['location']) && ! isset($locations[$this->normalise($data['location'])])) {
            $errors[] = __('Unknown location: :name', ['name' => $data['location']]);
        }

        return $errors;
    }

    /**
     * @return array<int, string>
     */
    protected function validateInitialStatus(array $data): array
    {
        $status = $data['status'] ?? null;

        if ($status === null || in_array($status, AssetStatusTransition::INITIAL_STATUSES, true)) {
            return [];
        }

        return [__('A new asset cannot start with status ":status".', ['status' => $status])];
    }

    /**
     * @return array<int, string>
     */
    protected function validateTransition(array $data, Asset $current): array
    {
        $status = $data['status'] ?? null;
        $to = $status !== null ? AssetStatus::tryFrom($status) : null;

        if ($to === null || $to === $current->status) {
            return [];
        }

        if ($this->transitions->canTransition($current->status, $to)) {
            return [];
        }

        return [__('Cannot change status from ":from" to ":to".', [
            'from' => $current->status->value,
            'to' => $to->value,
        ])];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(AssetStatus::values())],
            'condition' => ['nullable', Rule::in(AssetCondition::values())],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'purchase_date' => ['nullable', 'date'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', Rule::in(Currency::values())],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life_years' => ['nullable', 'integer', 'min:1', 'max:100'],
            'warranty_expiry' => ['nullable', 'date'],
            'supplier' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $categories
     * @param  array<string, int>  $locations
     * @return array<string, mixed>
     */
    protected function attributes(array $data, array $categories, array $locations): array
    {
        $attributes = [];

        foreach ([
            'name', 'description', 'serial_number', 'manufacturer', 'model',
            'purchase_cost', 'salvage_value', 'useful_life_years', 'supplier',
            'status', 'condition', 'currency',
        ] as $field) {
            // Preserve explicit zero values (e.g. purchase_cost = 0).
            if (array_key_exists($field, $data) && $data[$field] !== null && $data[$field] !== '') {
                $attributes[$field] = $data[$field];
            }
        }

        if (! empty($data['purchase_date'])) {
            $attributes['purchase_date'] = Carbon::parse($data['purchase_date'])->toDateString();
        }

        if (! empty($data['warranty_expiry'])) {
            $attributes['warranty_expiry'] = Carbon::parse($data['warranty_expiry'])->toDateString();
        }

        if (! empty($data['category'])) {
            $attributes['category_id'] = $categories[$this->normalise($data['category'])] ?? null;
        }

        if (! empty($data['location'])) {
            $attributes['location_id'] = $locations[$this->normalise($data['location'])] ?? null;
        }

        return array_filter($attributes, fn ($value) => $value !== null);
    }

    /**
     * @param  class-string<Model>  $model
     * @return array<string, int>
     */
    protected function nameMap(string $model): array
    {
        return $model::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (Model $record): array => [$this->normalise($record->name) => $record->id])
            ->all();
    }

    protected function normalise(?string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $value)) ?? '';
    }
}
