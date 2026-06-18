<?php

namespace App\Http\Controllers;

use App\Models\SosAlert;
use App\Services\SosAlertNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PilgrimSosController extends Controller
{
    public function __construct(private readonly SosAlertNotificationService $notifications)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $pilgrim = $request->user()?->pilgrim;

        if (! $pilgrim) {
            return response()->json([
                'message' => 'No pilgrim profile is linked to this account.',
            ], 403);
        }

        $alert = SosAlert::create([
            'pilgrim_id' => $pilgrim->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => 'pending',
            'source' => 'manual',
            'trigger_reason' => 'Manual SOS button pressed.',
        ]);

        $this->notifications->notifyCreated($alert);

        return response()->json([
            'message' => 'SOS alert created successfully.',
            'alert' => [
                'id' => $alert->id,
                'latitude' => (float) $alert->latitude,
                'longitude' => (float) $alert->longitude,
                'status' => $alert->status,
                'created_at' => $alert->created_at->toDateTimeString(),
            ],
        ]);
    }
}
