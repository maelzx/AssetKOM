<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_command_creates_a_verified_admin(): void
    {
        $this->artisan('admin:create', [
            'email' => 'boss@example.com',
            '--name' => 'Boss',
            '--password' => 'super-secret',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'boss@example.com')->firstOrFail();

        $this->assertSame(Role::Admin, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('super-secret', $user->password));
    }

    public function test_admin_create_rejects_short_passwords(): void
    {
        $this->artisan('admin:create', [
            'email' => 'weak@example.com',
            '--password' => 'short',
        ])->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_demo_users_are_not_seeded_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->app->make(UserSeeder::class)->run();

        $this->assertDatabaseCount('users', 0);

        $this->app->detectEnvironment(fn () => 'testing');
    }
}
