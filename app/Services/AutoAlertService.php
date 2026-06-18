<?php

namespace App\Services;

use App\Models\Pilgrim;
use App\Models\SosAlert;
use App\Models\Zone;

class AutoAlertService
{
    private const GROUP_DISTANCE_THRESHOLD_METERS = 1000;

    public function __construct(private readonly SosAlertNotificationService $notifications)
    {
    }

    public function check(?int $leaderId = null): array
    {
        $zones = Zone::orderBy('name')->get();
        $summary = [
            'checked' => 0,
            'created' => 0,
            'duplicates' => 0,
            'safe' => 0,
            'no_location' => 0,
            'no_rules' => 0,
            'alerts' => [],
        ];

        Pilgrim::with(['group', 'latestLocation', 'sosAlerts' => function ($query) {
            $query->whereIn('status', ['pending', 'assigned']);
        }])
            ->when($leaderId, function ($query) use ($leaderId) {
                $query->whereHas('group', function ($query) use ($leaderId) {
                    $query->where('leader_id', $leaderId);
                });
            })
            ->orderBy('name')
            ->get()
            ->each(function (Pilgrim $pilgrim) use ($zones, &$summary) {
                $summary['checked']++;
                $location = $pilgrim->latestLocation;

                if (! $location) {
                    $summary['no_location']++;

                    return;
                }

                $violations = $this->violationsFor($pilgrim, $zones);

                if ($violations === []) {
                    $summary['safe']++;

                    return;
                }

                if ($pilgrim->sosAlerts->isNotEmpty()) {
                    $summary['duplicates']++;

                    return;
                }

                $alert = SosAlert::create([
                    'pilgrim_id' => $pilgrim->id,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'status' => 'pending',
                    'source' => 'auto',
                    'trigger_reason' => implode(' ', $violations),
                ]);

                $this->notifications->notifyCreated($alert);

                $summary['created']++;
                $summary['alerts'][] = [
                    'id' => $alert->id,
                    'pilgrim' => $pilgrim->name,
                    'reason' => $alert->trigger_reason,
                ];
            });

        if ($zones->isEmpty()) {
            $summary['no_rules']++;
        }

        return $summary;
    }

    private function violationsFor(Pilgrim $pilgrim, $zones): array
    {
        $location = $pilgrim->latestLocation;
        $violations = [];

        if ($pilgrim->group?->group_latitude && $pilgrim->group?->group_longitude) {
            $distanceFromGroup = $this->distanceMeters(
                (float) $location->latitude,
                (float) $location->longitude,
                (float) $pilgrim->group->group_latitude,
                (float) $pilgrim->group->group_longitude
            );

            if ($distanceFromGroup > self::GROUP_DISTANCE_THRESHOLD_METERS) {
                $violations[] = 'Too far from group location: ' . round($distanceFromGroup) . 'm away, limit ' . self::GROUP_DISTANCE_THRESHOLD_METERS . 'm.';
            }
        }

        if ($zones->isNotEmpty()) {
            $insideAnyZone = false;
            $nearestZoneName = null;
            $nearestDistance = null;

            foreach ($zones as $zone) {
                $distanceFromZone = $this->distanceMeters(
                    (float) $location->latitude,
                    (float) $location->longitude,
                    (float) $zone->latitude,
                    (float) $zone->longitude
                );

                if ($nearestDistance === null || $distanceFromZone < $nearestDistance) {
                    $nearestDistance = $distanceFromZone;
                    $nearestZoneName = $zone->name;
                }

                if ($distanceFromZone <= $zone->radius) {
                    $insideAnyZone = true;
                    break;
                }
            }

            if (! $insideAnyZone) {
                $violations[] = 'Outside allowed zones. Nearest zone: ' . ($nearestZoneName ?? '-') . ' (' . round($nearestDistance ?? 0) . 'm from center).';
            }
        }

        return $violations;
    }

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
