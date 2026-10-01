<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class LocationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_view_locations(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager)
            ->get('/locations')
            ->assertOk()
            ->assertSeeLivewire('locations.index');
    }

    public function test_staff_cannot_view_locations(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/locations')->assertForbidden();
    }

    public function test_manager_can_create_location(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('locations.index')
            ->set('name', 'Server Room')
            ->set('code', 'SR-1')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('locations', ['name' => 'Server Room', 'code' => 'SR-1']);
    }

    public function test_location_code_must_be_unique(): void
    {
        $manager = User::factory()->manager()->create();
        Location::factory()->create(['code' => 'DUP']);

        $this->actingAs($manager);

        Volt::test('locations.index')
            ->set('name', 'New location')
            ->set('code', 'DUP')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    public function test_manager_can_delete_location(): void
    {
        $manager = User::factory()->manager()->create();
        $location = Location::factory()->create();

        $this->actingAs($manager);

        Volt::test('locations.index')->call('delete', $location->id);

        $this->assertSoftDeleted('locations', ['id' => $location->id]);
    }
}
