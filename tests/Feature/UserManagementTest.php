<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_access_user_management(): void
    {
        $this->actingAs(User::factory()->staff()->create())->get('/users')->assertForbidden();
        $this->actingAs(User::factory()->manager()->create())->get('/users')->assertForbidden();
    }

    public function test_admin_can_view_user_management(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/users')
            ->assertOk()
            ->assertSeeLivewire('users.index');
    }

    public function test_admin_can_create_a_verified_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Volt::test('users.index')
            ->set('name', 'New Manager')
            ->set('email', 'new.manager@example.com')
            ->set('role', Role::Manager->value)
            ->set('password', 'password123')
            ->call('create')
            ->assertHasNoErrors();

        $user = User::query()->where('email', 'new.manager@example.com')->firstOrFail();

        $this->assertSame(Role::Manager, $user->role);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_admin_can_change_a_users_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->staff()->create();

        $this->actingAs($admin);

        Volt::test('users.index')->call('updateRole', $user->id, Role::Manager->value);

        $this->assertSame(Role::Manager, $user->fresh()->role);
    }

    public function test_last_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('users.index')
            ->call('updateRole', $admin->id, Role::Staff->value)
            ->assertHasErrors(['users']);

        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('users.index')
            ->call('delete', $admin->id)
            ->assertHasErrors(['users']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->staff()->create();

        $this->actingAs($admin);

        Volt::test('users.index')->call('delete', $user->id);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
