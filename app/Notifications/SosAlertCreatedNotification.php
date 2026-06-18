<?php

namespace App\Notifications;

use App\Models\SosAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SosAlertCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly SosAlert $alert,
        private readonly string $audience
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $this->alert->loadMissing(['pilgrim.group', 'pilgrim.hotel']);

        return [
            'alert_id' => $this->alert->id,
            'pilgrim_id' => $this->alert->pilgrim_id,
            'pilgrim_name' => $this->alert->pilgrim?->name,
            'group_name' => $this->alert->pilgrim?->group?->group_name,
            'hotel_name' => $this->alert->pilgrim?->hotel?->hotel_name,
            'status' => $this->alert->status,
            'source' => $this->alert->source ?? 'manual',
            'reason' => $this->alert->trigger_reason,
            'audience' => $this->audience,
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => route('sos-alerts.index'),
            'created_at' => $this->alert->created_at?->toDateTimeString(),
        ];
    }

    private function title(): string
    {
        return match ($this->audience) {
            'group-leader' => 'Group member separated',
            'admin' => 'New SOS alert created',
            default => 'New SOS alert',
        };
    }

    private function message(): string
    {
        $this->alert->loadMissing('pilgrim.group');
        $pilgrimName = $this->alert->pilgrim?->name ?? 'A pilgrim';

        if ($this->audience === 'group-leader') {
            return "{$pilgrimName} from your group may be separated.";
        }

        return "{$pilgrimName} needs assistance.";
    }
}
