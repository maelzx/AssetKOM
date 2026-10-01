<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\MaintenanceType;
use App\Models\Asset;
use App\Models\Maintenance;
use App\Models\User;
use App\Services\AssetAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_log_scheduled_maintenance(): void
    {
        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($staff);

        Volt::test('maintenance.panel', ['asset' => $asset])
            ->set('type', MaintenanceType::Preventive->value)
            ->set('title', 'Annual servicing')
            ->set('scheduled_at', now()->toDateString())
            ->call('create')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('maintenances', [
            'asset_id' => $asset->id,
            'title' => 'Annual servicing',
            'status' => MaintenanceStatus::Scheduled->value,
            'created_by' => $staff->id,
        ]);
    }

    public function test_starting_maintenance_places_asset_under_maintenance(): void
    {
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);
        $record = Maintenance::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('maintenance.panel', ['asset' => $asset])
            ->call('start', $record->id)
            ->assertHasNoErrors();

        $this->assertSame(AssetStatus::Maintenance, $asset->fresh()->status);
        $this->assertSame(MaintenanceStatus::InProgress, $record->fresh()->status);
    }

    public function test_maintenance_cannot_start_while_asset_is_assigned(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        app(AssetAssignmentService::class)->checkout($asset, User::factory()->create(), $actor);

        $record = Maintenance::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs($actor);

        Volt::test('maintenance.panel', ['asset' => $asset->fresh()])
            ->call('start', $record->id)
            ->assertHasErrors(['maintenance']);

        $this->assertSame(AssetStatus::Assigned, $asset->fresh()->status);
        $this->assertSame(MaintenanceStatus::Scheduled, $record->fresh()->status);
    }

    public function test_completing_maintenance_frees_the_asset(): void
    {
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);
        $record = Maintenance::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs(User::factory()->staff()->create());

        $component = Volt::test('maintenance.panel', ['asset' => $asset]);
        $component->call('start', $record->id);
        $component->call('complete', $record->id)->assertHasNoErrors();

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
        $this->assertSame(MaintenanceStatus::Completed, $record->fresh()->status);
        $this->assertNotNull($record->fresh()->completed_at);
    }

    public function test_cancelling_maintenance_frees_the_asset(): void
    {
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);
        $record = Maintenance::factory()->create(['asset_id' => $asset->id]);

        $this->actingAs(User::factory()->staff()->create());

        $component = Volt::test('maintenance.panel', ['asset' => $asset]);
        $component->call('start', $record->id);
        $component->call('cancel', $record->id)->assertHasNoErrors();

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
        $this->assertSame(MaintenanceStatus::Cancelled, $record->fresh()->status);
    }

    public function test_completed_maintenance_cannot_be_restarted(): void
    {
        $asset = Asset::factory()->create();
        $record = Maintenance::factory()->completed()->create(['asset_id' => $asset->id]);

        $this->actingAs(User::factory()->staff()->create());

        Volt::test('maintenance.panel', ['asset' => $asset])
            ->call('start', $record->id)
            ->assertHasErrors(['maintenance']);
    }

    public function test_maintenance_index_lists_open_records(): void
    {
        $staff = User::factory()->staff()->create();
        $record = Maintenance::factory()->create(['title' => 'Filter replacement']);

        $this->actingAs($staff)->get('/maintenance')->assertOk();

        Volt::test('maintenance.index')
            ->set('status', 'open')
            ->assertSee('Filter replacement');
    }

    public function test_only_admin_can_delete_maintenance_records(): void
    {
        $record = Maintenance::factory()->create();
        $staff = User::factory()->staff()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse($staff->can('delete', $record));
        $this->assertTrue($admin->can('delete', $record));
    }
}
