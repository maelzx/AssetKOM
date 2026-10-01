<?php

namespace Tests\Feature;

use App\Enums\MaintenanceStatus;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use App\Models\User;
use App\Notifications\AssetAlertsDigest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AssetAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_alerts_are_sent_to_subscribed_users_only(): void
    {
        Notification::fake();

        Asset::factory()->create(['warranty_expiry' => now()->addDays(10)]);

        $farAsset = Asset::factory()->create(['warranty_expiry' => now()->addYears(2)]);
        Maintenance::factory()->overdue()->create(['asset_id' => $farAsset->id]);
        $assignedAsset = Asset::factory()->create(['warranty_expiry' => now()->addYears(2)]);
        AssetAssignment::factory()->overdue()->create(['asset_id' => $assignedAsset->id]);

        $manager = User::factory()->manager()->create();
        $staff = User::factory()->staff()->create([
            'notify_warranty_expiry' => false,
            'notify_maintenance_due' => false,
            'notify_overdue_assignments' => false,
        ]);

        $this->artisan('alerts:send')->assertSuccessful();

        Notification::assertSentTo($manager, AssetAlertsDigest::class);
        Notification::assertNotSentTo($staff, AssetAlertsDigest::class);
    }

    public function test_digest_contains_matching_warranty_items(): void
    {
        Notification::fake();

        $asset = Asset::factory()->create(['warranty_expiry' => now()->addDays(10)]);
        $manager = User::factory()->manager()->create();

        $this->artisan('alerts:send');

        Notification::assertSentTo(
            $manager,
            AssetAlertsDigest::class,
            fn (AssetAlertsDigest $notification) => $notification->warranties->contains('id', $asset->id)
        );
    }

    public function test_preferences_exclude_disabled_sections(): void
    {
        Notification::fake();

        Asset::factory()->create(['warranty_expiry' => now()->addDays(5)]);

        $farAsset = Asset::factory()->create(['warranty_expiry' => now()->addYears(2)]);
        $maintenance = Maintenance::factory()->create([
            'asset_id' => $farAsset->id,
            'status' => MaintenanceStatus::Scheduled,
            'scheduled_at' => now()->subDay()->toDateString(),
        ]);

        $manager = User::factory()->manager()->create([
            'notify_warranty_expiry' => false,
        ]);

        $this->artisan('alerts:send');

        Notification::assertSentTo(
            $manager,
            AssetAlertsDigest::class,
            fn (AssetAlertsDigest $notification) => $notification->warranties->isEmpty()
                && $notification->maintenance->contains('id', $maintenance->id)
        );
    }

    public function test_no_digest_is_sent_when_there_is_nothing_to_report(): void
    {
        Notification::fake();

        User::factory()->manager()->create();

        $this->artisan('alerts:send')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $user = User::factory()->create(['notify_warranty_expiry' => true]);

        $this->actingAs($user);

        Volt::test('profile.notification-preferences')
            ->set('notify_warranty_expiry', false)
            ->call('update')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->notify_warranty_expiry);
    }
}
