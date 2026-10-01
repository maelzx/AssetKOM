<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_is_cast_to_enum(): void
    {
        $user = User::factory()->create(['role' => Role::Manager]);

        $this->assertSame(Role::Manager, $user->fresh()->role);
    }

    public function test_role_helpers_report_correct_role(): void
    {
        $this->assertTrue(User::factory()->admin()->create()->isAdmin());
        $this->assertTrue(User::factory()->manager()->create()->isManager());
        $this->assertTrue(User::factory()->staff()->create()->isStaff());
    }

    public function test_has_role_accepts_multiple_roles(): void
    {
        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->hasRole(Role::Admin, Role::Manager));
        $this->assertFalse($manager->hasRole(Role::Admin, Role::Staff));
    }

    public function test_admin_implicitly_passes_every_gate(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($admin->can('manage-settings'));
        $this->assertTrue($admin->can('view-reports'));
        $this->assertTrue($admin->can('manage-assets'));
        $this->assertTrue($admin->can('viewAny', User::class));
    }

    public function test_manager_can_manage_settings_and_assets_but_not_users(): void
    {
        $manager = User::factory()->manager()->create();

        $this->assertTrue($manager->can('manage-settings'));
        $this->assertTrue($manager->can('manage-assets'));
        $this->assertFalse($manager->can('viewAny', User::class));
    }

    public function test_staff_has_no_management_abilities(): void
    {
        $staff = User::factory()->staff()->create();

        $this->assertFalse($staff->can('manage-settings'));
        $this->assertFalse($staff->can('view-reports'));
        $this->assertFalse($staff->can('manage-assets'));
    }
}
