<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Location;
use App\Models\Maintenance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->{$role}()->create();
    }

    public function test_gate_capabilities_match_the_matrix(): void
    {
        $admin = $this->user('admin');
        $manager = $this->user('manager');
        $staff = $this->user('staff');

        $gates = ['manage-settings', 'view-reports', 'manage-assets', 'manage-catalog'];

        foreach ($gates as $gate) {
            $this->assertTrue($admin->can($gate), "admin should pass {$gate}");
            $this->assertTrue($manager->can($gate), "manager should pass {$gate}");
            $this->assertFalse($staff->can($gate), "staff should not pass {$gate}");
        }
    }

    public function test_asset_policy_matrix(): void
    {
        $asset = Asset::factory()->create();
        $admin = $this->user('admin');
        $manager = $this->user('manager');
        $staff = $this->user('staff');

        $this->assertTrue($staff->can('viewAny', Asset::class));
        $this->assertTrue($manager->can('create', Asset::class));
        $this->assertFalse($staff->can('create', Asset::class));
        $this->assertTrue($manager->can('update', $asset));
        $this->assertFalse($staff->can('update', $asset));
        $this->assertTrue($admin->can('delete', $asset));
        $this->assertFalse($manager->can('delete', $asset));
        $this->assertFalse($staff->can('delete', $asset));
    }

    public function test_catalog_policy_matrix(): void
    {
        $category = Category::factory()->create();
        $location = Location::factory()->create();
        $manager = $this->user('manager');
        $staff = $this->user('staff');

        $this->assertTrue($manager->can('create', Category::class));
        $this->assertTrue($manager->can('update', $category));
        $this->assertTrue($manager->can('delete', $category));
        $this->assertFalse($staff->can('create', Category::class));

        $this->assertTrue($manager->can('update', $location));
        $this->assertFalse($staff->can('update', $location));
    }

    public function test_maintenance_policy_matrix(): void
    {
        $record = Maintenance::factory()->create();
        $admin = $this->user('admin');
        $manager = $this->user('manager');
        $staff = $this->user('staff');

        $this->assertTrue($staff->can('create', Maintenance::class));
        $this->assertTrue($staff->can('update', $record));
        $this->assertFalse($manager->can('delete', $record));
        $this->assertTrue($admin->can('delete', $record));
        $this->assertFalse($staff->can('delete', $record));
    }

    public function test_attachment_delete_matrix(): void
    {
        $asset = Asset::factory()->create();
        $uploader = $this->user('staff');
        $other = $this->user('staff');
        $manager = $this->user('manager');

        $attachment = Attachment::factory()->create([
            'attachable_type' => $asset->getMorphClass(),
            'attachable_id' => $asset->id,
            'uploaded_by' => $uploader->id,
        ]);

        $this->assertTrue($uploader->can('delete', $attachment));
        $this->assertTrue($manager->can('delete', $attachment));
        $this->assertFalse($other->can('delete', $attachment));
    }

    public function test_route_access_matrix(): void
    {
        $asset = Asset::factory()->create();

        $expectations = [
            'admin' => [
                '/settings' => 200,
                '/categories' => 200,
                '/locations' => 200,
                '/assets' => 200,
                '/assets/create' => 200,
                '/assets/'.$asset->id => 200,
                '/assignments' => 200,
                '/maintenance' => 200,
                '/labels' => 200,
                '/reports' => 200,
                '/activity' => 200,
                '/imports/assets' => 200,
                '/exports/assets' => 200,
            ],
            'manager' => [
                '/settings' => 200,
                '/categories' => 200,
                '/locations' => 200,
                '/assets' => 200,
                '/assets/create' => 200,
                '/assets/'.$asset->id => 200,
                '/assignments' => 200,
                '/maintenance' => 200,
                '/labels' => 200,
                '/reports' => 200,
                '/activity' => 200,
                '/imports/assets' => 200,
                '/exports/assets' => 200,
            ],
            'staff' => [
                '/settings' => 403,
                '/categories' => 403,
                '/locations' => 403,
                '/assets' => 200,
                '/assets/create' => 403,
                '/assets/'.$asset->id => 200,
                '/assignments' => 200,
                '/maintenance' => 200,
                '/labels' => 200,
                '/reports' => 403,
                '/activity' => 403,
                '/imports/assets' => 403,
                '/exports/assets' => 403,
            ],
        ];

        foreach ($expectations as $role => $paths) {
            $user = $this->user($role);

            foreach ($paths as $path => $status) {
                $this->actingAs($user)
                    ->get($path)
                    ->assertStatus($status, "[{$role}] GET {$path}");
            }
        }
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (['/assets', '/assignments', '/maintenance', '/reports', '/settings'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }
}
