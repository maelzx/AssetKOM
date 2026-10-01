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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Csv\Reader;

/**
 * CSV asset import with column mapping, dry-run validation and
 * idempotent upserts keyed by asset tag.
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
     * @return array{rows: array<int, array<string, mixed>>, summary: array{create: int, update: int, errors: int, total: int}}
     */
    public function dryRun(string $path, array $mapping): array
    {
        $existingTags = Asset::query()->pluck('asset_tag')->flip();
        $rows = [];
        $summary = ['create' => 0, 'update' => 0, 'errors' => 0, 'total' => 0];
        $line = 1;

        foreach ($this->records($path) as $record) {
            $line++;
            $data = $this->mapped($record, $mapping);
            $validator = Validator::make($data, $this->rules());
            $errors = $validator->errors()->all();

            $tag = $data['asset_tag'] ?? null;
            $action = $tag && $existingTags->has($tag) ? 'update' : 'create';

            if ($errors !== []) {
                $summary['errors']++;
            } else {
                $summary[$action]++;
            }

            $rows[] = [
                'line' => $line,
                'asset_tag' => $tag,
                'name' => $data['name'] ?? null,
                'action' => $action,
                'errors' => $errors,
            ];
        }

        $summary['total'] = count($rows);

        return ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * Import valid rows, creating or updating by asset tag.
     *
     * @param  array<string, string|null>  $mapping
     * @return array{created: int, updated: int, skipped: int}
     */
    public function import(string $path, array $mapping): array
    {
        $categories = $this->nameMap(Category::class);
        $locations = $this->nameMap(Location::class);

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        DB::transaction(function () use ($path, $mapping, $categories, $locations, &$result): void {
            foreach ($this->records($path) as $record) {
                $data = $this->mapped($record, $mapping);

                if (Validator::make($data, $this->rules())->fails()) {
                    $result['skipped']++;

                    continue;
                }

                $attributes = $this->attributes($data, $categories, $locations);
                $tag = $data['asset_tag'] ?? null;

                $asset = $tag ? Asset::firstOrNew(['asset_tag' => $tag]) : new Asset;

                if (! $asset->exists) {
                    $asset->created_by = auth()->id();
                }

                $asset->fill($attributes);
                $asset->save();

                $asset->wasRecentlyCreated ? $result['created']++ : $result['updated']++;
            }
        });

        return $result;
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
            if (! empty($data[$field])) {
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
