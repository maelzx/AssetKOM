<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use App\Services\DepreciationCalculator;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly DepreciationCalculator $depreciation) {}

    public function assets(): StreamedResponse
    {
        $headers = [
            'Asset Tag', 'Name', 'Category', 'Location', 'Status', 'Condition', 'Serial Number',
            'Manufacturer', 'Model', 'Purchase Date', 'Purchase Cost', 'Currency',
            'Accumulated Depreciation', 'Book Value', 'Warranty Expiry', 'Supplier', 'Created At',
        ];

        $rows = Asset::query()->with(['category', 'location'])->orderBy('asset_tag')->cursor()
            ->map(fn (Asset $asset): array => [
                $asset->asset_tag,
                $asset->name,
                $asset->category?->full_name,
                $asset->location?->full_name,
                $asset->status->label(),
                $asset->condition?->label(),
                $asset->serial_number,
                $asset->manufacturer,
                $asset->model,
                $asset->purchase_date?->toDateString(),
                $asset->purchase_cost,
                $asset->currency->value,
                $this->depreciation->accumulated($asset),
                $this->depreciation->bookValue($asset),
                $asset->warranty_expiry?->toDateString(),
                $asset->supplier,
                $asset->created_at?->toDateTimeString(),
            ]);

        return $this->stream('assets.csv', $headers, $rows);
    }

    public function assignments(): StreamedResponse
    {
        $headers = [
            'Asset Tag', 'Asset Name', 'Assignee', 'Assignee Type', 'Assigned By',
            'Assigned At', 'Expected Return', 'Returned At', 'Status', 'Condition Out', 'Condition In',
        ];

        $rows = AssetAssignment::query()->with(['asset', 'assignable', 'assigner'])->latest('assigned_at')->cursor()
            ->map(fn (AssetAssignment $assignment): array => [
                $assignment->asset?->asset_tag,
                $assignment->asset?->name,
                $assignment->assignable?->name,
                class_basename((string) $assignment->assignable_type),
                $assignment->assigner?->name,
                $assignment->assigned_at?->toDateTimeString(),
                $assignment->expected_return_at?->toDateString(),
                $assignment->returned_at?->toDateTimeString(),
                $assignment->status->label(),
                $assignment->condition_out?->label(),
                $assignment->condition_in?->label(),
            ]);

        return $this->stream('assignments.csv', $headers, $rows);
    }

    public function maintenance(): StreamedResponse
    {
        $headers = [
            'Asset Tag', 'Type', 'Title', 'Status', 'Scheduled', 'Completed',
            'Vendor', 'Cost', 'Currency', 'Performed By', 'Notes',
        ];

        $rows = Maintenance::query()->with(['asset', 'performer'])->latest('scheduled_at')->cursor()
            ->map(fn (Maintenance $maintenance): array => [
                $maintenance->asset?->asset_tag,
                $maintenance->type->label(),
                $maintenance->title,
                $maintenance->status->label(),
                $maintenance->scheduled_at?->toDateString(),
                $maintenance->completed_at?->toDateTimeString(),
                $maintenance->vendor,
                $maintenance->cost,
                $maintenance->currency->value,
                $maintenance->performer?->name,
                $maintenance->notes,
            ]);

        return $this->stream('maintenance.csv', $headers, $rows);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    protected function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->insertOne($headers);

            foreach ($rows as $row) {
                $csv->insertOne($row);
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
