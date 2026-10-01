<?php

namespace App\Notifications;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class AssetAlertsDigest extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Asset>  $warranties
     * @param  Collection<int, Maintenance>  $maintenance
     * @param  Collection<int, AssetAssignment>  $overdueAssignments
     */
    public function __construct(
        public Collection $warranties,
        public Collection $maintenance,
        public Collection $overdueAssignments,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(__('AssetKOM daily alerts'))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? '']))
            ->line(__('Here is your daily asset summary.'));

        if ($this->warranties->isNotEmpty()) {
            $mail->line('**'.__('Warranties expiring soon').'**');

            foreach ($this->warranties as $asset) {
                $mail->line('• '.$asset->asset_tag.' — '.$asset->name.' ('.$asset->warranty_expiry?->format('d M Y').')');
            }
        }

        if ($this->maintenance->isNotEmpty()) {
            $mail->line('**'.__('Maintenance due').'**');

            foreach ($this->maintenance as $record) {
                $mail->line('• '.$record->asset?->asset_tag.' — '.$record->title.' ('.$record->scheduled_at?->format('d M Y').')');
            }
        }

        if ($this->overdueAssignments->isNotEmpty()) {
            $mail->line('**'.__('Overdue assignments').'**');

            foreach ($this->overdueAssignments as $assignment) {
                $mail->line('• '.$assignment->asset?->asset_tag.' — '.($assignment->assignable?->name ?? __('Unknown')).' ('.$assignment->expected_return_at?->format('d M Y').')');
            }
        }

        return $mail->action(__('Open AssetKOM'), url('/dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'warranties' => $this->warranties->pluck('asset_tag')->all(),
            'maintenance' => $this->maintenance->pluck('title')->all(),
            'overdue_assignments' => $this->overdueAssignments->pluck('id')->all(),
        ];
    }
}
