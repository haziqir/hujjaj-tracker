<?php

namespace App\Services;

use App\Models\SosAlert;
use App\Models\User;
use App\Notifications\SosAlertCreatedNotification;
use Illuminate\Support\Collection;

class SosAlertNotificationService
{
    public function notifyCreated(SosAlert $alert): void
    {
        $alert->loadMissing('pilgrim.group.leader');

        $this->adminUsers()->each->notify(new SosAlertCreatedNotification($alert, 'admin'));
        $this->staffUsers()->each->notify(new SosAlertCreatedNotification($alert, 'staff'));

        if (($alert->source ?? 'manual') === 'auto' && $alert->pilgrim?->group?->leader) {
            $alert->pilgrim->group->leader->notify(new SosAlertCreatedNotification($alert, 'group-leader'));
        }
    }

    private function adminUsers(): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'super-admin'))->get();
    }

    private function staffUsers(): Collection
    {
        return User::whereHas('roles', fn ($query) => $query->where('name', 'staff'))->get();
    }
}
