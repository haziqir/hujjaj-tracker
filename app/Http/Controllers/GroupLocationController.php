<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupLocationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => ['required', 'exists:groups,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'recorded_at' => ['nullable', 'date'],
        ]);

        $group = Group::where('id', $validated['group_id'])
            ->where('leader_id', $request->user()->id)
            ->first();

        if (! $group) {
            return response()->json([
                'message' => 'You are not assigned as the leader for this group.',
            ], 403);
        }

        $group->update([
            'group_latitude' => $validated['latitude'],
            'group_longitude' => $validated['longitude'],
            'group_location_recorded_at' => $validated['recorded_at'] ?? now(),
        ]);

        return response()->json([
            'message' => 'Group location updated successfully.',
            'group' => [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'latitude' => (float) $group->group_latitude,
                'longitude' => (float) $group->group_longitude,
                'recorded_at' => $group->group_location_recorded_at->toDateTimeString(),
            ],
        ]);
    }
}
