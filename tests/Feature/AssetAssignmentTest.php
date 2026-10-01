<?php

namespace Tests\Feature;

use App\Enums\AssetCondition;
use App\Enums\AssetStatus;
use App\Enums\AssignmentStatus;
use App\Exceptions\AssetAssignmentException;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Location;
use App\Models\User;
use App\Services\AssetAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AssetAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AssetAssignmentService
    {
        return app(AssetAssignmentService::class);
    }

    public function test_checkout_assigns_asset_and_creates_active_record(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);
        $user = User::factory()->create();

        $assignment = $this->service()->checkout($asset, $user, $actor, now()->addWeek()->toDateString(), AssetCondition::Good, 'Handed over');

        $this->assertTrue($assignment->isActive());
        $this->assertSame(AssetStatus::Assigned, $asset->fresh()->status);
        $this->assertDatabaseHas('asset_assignments', [
            'asset_id' => $asset->id,
            'assignable_id' => $user->id,
            'status' => AssignmentStatus::Active->value,
            'active' => 1,
        ]);
    }

    public function test_asset_cannot_be_checked_out_twice(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        $this->service()->checkout($asset, User::factory()->create(), $actor);

        $this->expectException(AssetAssignmentException::class);

        $this->service()->checkout($asset->fresh(), User::factory()->create(), $actor);
    }

    public function test_retired_asset_cannot_be_checked_out(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create(['status' => AssetStatus::Retired]);

        $this->expectException(AssetAssignmentException::class);

        $this->service()->checkout($asset, User::factory()->create(), $actor);
    }

    public function test_checkout_to_location_updates_asset_location(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create(['location_id' => null]);
        $location = Location::factory()->create();

        $this->service()->checkout($asset, $location, $actor);

        $this->assertSame($location->id, $asset->fresh()->location_id);
        $this->assertSame(AssetStatus::Assigned, $asset->fresh()->status);
    }

    public function test_checkin_frees_the_asset(): void
    {
        $actor = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        $assignment = $this->service()->checkout($asset, User::factory()->create(), $actor);

        $this->service()->checkin($assignment, $actor, AssetCondition::Fair, 'Minor scratch');

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
        $this->assertFalse($assignment->fresh()->isActive());
        $this->assertNull($assignment->fresh()->active);
        $this->assertSame(AssetCondition::Fair, $assignment->fresh()->condition_in);
    }

    public function test_staff_can_checkout_via_the_asset_page(): void
    {
        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($staff);

        Volt::test('assets.show', ['asset' => $asset])
            ->call('openCheckout')
            ->set('assignableType', 'user')
            ->set('assignableId', $user->id)
            ->set('conditionOut', AssetCondition::Good->value)
            ->call('checkout')
            ->assertHasNoErrors();

        $this->assertSame(AssetStatus::Assigned, $asset->fresh()->status);
    }

    public function test_second_checkout_via_the_asset_page_shows_an_error(): void
    {
        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        $this->service()->checkout($asset, User::factory()->create(), $staff);

        $this->actingAs($staff);

        Volt::test('assets.show', ['asset' => $asset->fresh()])
            ->call('openCheckout')
            ->set('assignableType', 'user')
            ->set('assignableId', User::factory()->create()->id)
            ->call('checkout')
            ->assertHasErrors(['assignment']);
    }

    public function test_staff_can_check_in_via_the_asset_page(): void
    {
        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create();
        $this->service()->checkout($asset, User::factory()->create(), $staff);

        $this->actingAs($staff);

        Volt::test('assets.show', ['asset' => $asset->fresh()])
            ->call('openCheckin')
            ->set('conditionIn', AssetCondition::Good->value)
            ->call('checkin')
            ->assertHasNoErrors();

        $this->assertSame(AssetStatus::Available, $asset->fresh()->status);
    }

    public function test_assignments_index_lists_and_filters(): void
    {
        $staff = User::factory()->staff()->create();
        $active = AssetAssignment::factory()->create();
        AssetAssignment::factory()->returned()->create();
        $overdue = AssetAssignment::factory()->overdue()->create();

        $this->actingAs($staff)->get('/assignments')->assertOk();

        Volt::test('assignments.index')
            ->set('filter', 'overdue')
            ->assertSee($overdue->asset->asset_tag)
            ->assertDontSee($active->asset->asset_tag);
    }

    public function test_overdue_scope_only_matches_past_due_active_assignments(): void
    {
        AssetAssignment::factory()->create(['expected_return_at' => now()->addWeek()->toDateString()]);
        AssetAssignment::factory()->overdue()->create();
        AssetAssignment::factory()->returned()->overdue()->create();

        $this->assertSame(1, AssetAssignment::overdue()->count());
    }
}
