<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_asset_is_logged(): void
    {
        $asset = Asset::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'asset',
            'event' => 'created',
            'subject_type' => $asset->getMorphClass(),
            'subject_id' => $asset->id,
        ]);
    }

    public function test_updating_an_asset_is_logged(): void
    {
        $asset = Asset::factory()->create(['name' => 'Original']);
        $asset->update(['name' => 'Renamed']);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'asset',
            'event' => 'updated',
            'subject_id' => $asset->id,
        ]);
    }

    public function test_staff_cannot_view_the_activity_page(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/activity')->assertForbidden();
    }

    public function test_manager_can_view_the_activity_page(): void
    {
        Asset::factory()->create(['name' => 'Logged Asset']);

        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/activity')
            ->assertOk()
            ->assertSeeLivewire('activity.index');
    }

    public function test_asset_detail_shows_its_activity(): void
    {
        $asset = Asset::factory()->create();
        $asset->update(['name' => 'Updated Name']);

        $this->actingAs(User::factory()->staff()->create())
            ->get('/assets/'.$asset->id)
            ->assertOk()
            ->assertSee('Updated');
    }
}
