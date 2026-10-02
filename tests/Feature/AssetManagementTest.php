<?php

namespace Tests\Feature;

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AssetManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_asset_registry_and_detail(): void
    {
        $staff = User::factory()->staff()->create();
        $asset = Asset::factory()->create(['name' => 'Visible Laptop']);

        $this->actingAs($staff)->get('/assets')->assertOk()->assertSee('Visible Laptop');
        $this->actingAs($staff)->get('/assets/'.$asset->id)->assertOk()->assertSee('Visible Laptop');
    }

    public function test_staff_cannot_create_assets(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get('/assets/create')->assertForbidden();
    }

    public function test_manager_can_create_asset_with_auto_generated_tag(): void
    {
        Setting::set('asset_tag_prefix', 'AST');
        Setting::set('asset_tag_sequence', 0, 'integer');

        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('assets.form')
            ->set('name', 'Dell Latitude')
            ->set('status', 'available')
            ->set('condition', 'good')
            ->set('currency', 'MYR')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', [
            'name' => 'Dell Latitude',
            'asset_tag' => 'AST-0001',
            'created_by' => $manager->id,
        ]);
    }

    public function test_manager_can_update_asset(): void
    {
        $manager = User::factory()->manager()->create();
        $asset = Asset::factory()->create(['name' => 'Old name']);

        $this->actingAs($manager);

        Volt::test('assets.form', ['asset' => $asset])
            ->set('name', 'New name')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('New name', $asset->fresh()->name);
    }

    public function test_asset_index_filters_by_status(): void
    {
        $staff = User::factory()->staff()->create();
        Asset::factory()->create(['name' => 'Available Widget', 'status' => AssetStatus::Available]);
        Asset::factory()->create(['name' => 'Retired Widget', 'status' => AssetStatus::Retired]);

        $this->actingAs($staff);

        Volt::test('assets.index')
            ->set('status', AssetStatus::Retired->value)
            ->assertSee('Retired Widget')
            ->assertDontSee('Available Widget');
    }

    public function test_asset_image_can_be_uploaded(): void
    {
        Storage::fake('public');

        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);

        Volt::test('assets.form')
            ->set('name', 'Camera')
            ->set('image', UploadedFile::fake()->image('camera.jpg'))
            ->call('save')
            ->assertHasNoErrors();

        $asset = Asset::query()->where('name', 'Camera')->firstOrFail();

        $this->assertNotNull($asset->image_path);
        Storage::disk('public')->assertExists($asset->image_path);
    }

    public function test_custom_fields_are_persisted(): void
    {
        $manager = User::factory()->manager()->create();
        $this->actingAs($manager);

        Volt::test('assets.form')
            ->set('name', 'Projector')
            ->set('customFields', [
                ['key' => 'Warranty provider', 'value' => 'Acme'],
                ['key' => 'IP address', 'value' => '10.0.0.5'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $asset = Asset::query()->where('name', 'Projector')->firstOrFail();

        $this->assertSame('Acme', $asset->custom_fields['Warranty provider']);
        $this->assertSame('10.0.0.5', $asset->custom_fields['IP address']);
    }

    public function test_only_admin_can_delete_assets(): void
    {
        $asset = Asset::factory()->create();
        $manager = User::factory()->manager()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse($manager->can('delete', $asset));
        $this->assertTrue($admin->can('delete', $asset));
    }

    public function test_admin_can_delete_asset_from_detail_page(): void
    {
        $admin = User::factory()->admin()->create();
        $asset = Asset::factory()->create();

        $this->actingAs($admin);

        Volt::test('assets.show', ['asset' => $asset])
            ->call('delete')
            ->assertRedirect(route('assets.index'));

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_edit_cannot_set_an_invalid_status_transition(): void
    {
        $manager = User::factory()->manager()->create();
        $asset = Asset::factory()->create(['status' => AssetStatus::Retired]);

        $this->actingAs($manager);

        Volt::test('assets.form', ['asset' => $asset])
            ->set('status', AssetStatus::Assigned->value)
            ->call('save')
            ->assertHasErrors(['status']);

        $this->assertSame(AssetStatus::Retired, $asset->fresh()->status);
    }

    public function test_edit_can_apply_a_valid_status_transition(): void
    {
        $manager = User::factory()->manager()->create();
        $asset = Asset::factory()->create(['status' => AssetStatus::Available]);

        $this->actingAs($manager);

        Volt::test('assets.form', ['asset' => $asset])
            ->set('status', AssetStatus::Retired->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(AssetStatus::Retired, $asset->fresh()->status);
    }

    public function test_new_asset_cannot_start_in_a_lifecycle_status(): void
    {
        $manager = User::factory()->manager()->create();

        $this->actingAs($manager);

        Volt::test('assets.form')
            ->set('name', 'Fresh asset')
            ->set('status', AssetStatus::Assigned->value)
            ->set('condition', 'good')
            ->set('currency', 'MYR')
            ->call('save')
            ->assertHasErrors(['status']);
    }
}
