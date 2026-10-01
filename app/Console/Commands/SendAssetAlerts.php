<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use App\Models\User;
use App\Notifications\AssetAlertsDigest;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('alerts:send')]
#[Description('Send daily asset alert digests (warranty expiry, maintenance due, overdue assignments).')]
class SendAssetAlerts extends Command
{
    public function handle(): int
    {
        $today = today();
        $horizon = $today->copy()->addDays(30);

        $warranties = Asset::query()
            ->whereNotNull('warranty_expiry')
            ->whereBetween('warranty_expiry', [$today->toDateString(), $horizon->toDateString()])
            ->get();

        $maintenance = Maintenance::query()
            ->open()
            ->with('asset')
            ->whereDate('scheduled_at', '<=', $today->toDateString())
            ->get();

        $overdueAssignments = AssetAssignment::query()
            ->overdue()
            ->with(['asset', 'assignable'])
            ->get();

        $recipients = User::query()
            ->where(function ($query): void {
                $query->where('notify_warranty_expiry', true)
                    ->orWhere('notify_maintenance_due', true)
                    ->orWhere('notify_overdue_assignments', true);
            })
            ->get();

        $queued = 0;

        foreach ($recipients as $user) {
            $userWarranties = $user->notify_warranty_expiry ? $warranties : collect();
            $userMaintenance = $user->notify_maintenance_due ? $maintenance : collect();
            $userOverdue = $user->notify_overdue_assignments ? $overdueAssignments : collect();

            if ($userWarranties->isEmpty() && $userMaintenance->isEmpty() && $userOverdue->isEmpty()) {
                continue;
            }

            $user->notify(new AssetAlertsDigest($userWarranties, $userMaintenance, $userOverdue));
            $queued++;
        }

        $this->info("Queued {$queued} alert digest(s).");

        return self::SUCCESS;
    }
}
