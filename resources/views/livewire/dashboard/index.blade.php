<?php

use App\Models\Hotel;
use App\Models\Pilgrim;
use App\Models\SosAlert;
use App\Models\User;
use App\Models\Zone;
use Livewire\Volt\Component;

new class extends Component
{
    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function mapData(): array
    {
        $activeAlerts = SosAlert::with(['pilgrim.group', 'pilgrim.hotel', 'latestAssignment.staff'])
            ->whereIn('status', ['pending', 'assigned'])
            ->latest()
            ->get();

        $activePilgrimIds = $activeAlerts->pluck('pilgrim_id')->unique();

        $normalPilgrims = Pilgrim::with(['group', 'hotel', 'latestLocation'])
            ->whereNotIn('id', $activePilgrimIds)
            ->whereHas('latestLocation')
            ->get()
            ->map(fn (Pilgrim $pilgrim) => [
                'type' => 'normal',
                'name' => $pilgrim->name,
                'age' => $pilgrim->age,
                'group' => $pilgrim->group?->group_name ?? '-',
                'hotel' => $pilgrim->hotel?->hotel_name ?? '-',
                'status' => 'Normal',
                'latitude' => (float) $pilgrim->latestLocation->latitude,
                'longitude' => (float) $pilgrim->latestLocation->longitude,
                'recorded_at' => $pilgrim->latestLocation->recorded_at->format('Y-m-d H:i:s'),
            ])
            ->values();

        $sosPilgrims = $activeAlerts
            ->map(fn (SosAlert $alert) => [
                'type' => 'sos',
                'name' => $alert->pilgrim->name,
                'age' => $alert->pilgrim->age,
                'group' => $alert->pilgrim->group?->group_name ?? '-',
                'hotel' => $alert->pilgrim->hotel?->hotel_name ?? '-',
                'status' => strtoupper($alert->status),
                'latitude' => (float) $alert->latitude,
                'longitude' => (float) $alert->longitude,
                'recorded_at' => $alert->created_at->format('Y-m-d H:i:s'),
            ])
            ->values();

        $staffMarkers = $activeAlerts
            ->filter(fn (SosAlert $alert) => $alert->latestAssignment?->staff)
            ->map(fn (SosAlert $alert) => [
                'type' => 'staff',
                'name' => $alert->latestAssignment->staff->name,
                'age' => '-',
                'group' => '-',
                'hotel' => '-',
                'status' => 'Assigned to ' . $alert->pilgrim->name,
                'latitude' => (float) $alert->latitude,
                'longitude' => (float) $alert->longitude,
                'recorded_at' => $alert->latestAssignment->assigned_at?->format('Y-m-d H:i:s') ?? '-',
            ])
            ->values();

        $hotelMarkers = Hotel::orderBy('hotel_name')
            ->get()
            ->map(fn (Hotel $hotel) => [
                'type' => 'hotel',
                'name' => $hotel->hotel_name,
                'age' => '-',
                'group' => '-',
                'hotel' => $hotel->hotel_name,
                'status' => 'Hotel',
                'latitude' => (float) $hotel->latitude,
                'longitude' => (float) $hotel->longitude,
                'address' => $hotel->address,
            ])
            ->values();

        $zones = Zone::orderBy('name')
            ->get()
            ->map(fn (Zone $zone) => [
                'type' => 'zone',
                'name' => $zone->name,
                'latitude' => (float) $zone->latitude,
                'longitude' => (float) $zone->longitude,
                'radius' => $zone->radius,
                'status' => 'Zone radius ' . $zone->radius . 'm',
            ])
            ->values();

        return [
            'markers' => $normalPilgrims
                ->concat($sosPilgrims)
                ->concat($staffMarkers)
                ->concat($hotelMarkers)
                ->values()
                ->all(),
            'zones' => $zones->all(),
        ];
    }

    private function outsideZoneCount(): int
    {
        $zones = Zone::all();

        if ($zones->isEmpty()) {
            return 0;
        }

        return Pilgrim::with('latestLocation')
            ->whereHas('latestLocation')
            ->get()
            ->filter(function (Pilgrim $pilgrim) use ($zones) {
                $location = $pilgrim->latestLocation;

                return ! $zones->contains(function (Zone $zone) use ($location) {
                    return $this->distanceMeters(
                        (float) $location->latitude,
                        (float) $location->longitude,
                        (float) $zone->latitude,
                        (float) $zone->longitude
                    ) <= $zone->radius;
                });
            })
            ->count();
    }

    public function with(): array
    {
        return [
            'totalPilgrims' => Pilgrim::count(),
            'totalStaff' => User::whereHas('roles', fn ($query) => $query->where('name', 'staff'))->count(),
            'activeSosCount' => SosAlert::whereIn('status', ['pending', 'assigned'])->count(),
            'resolvedSosCount' => SosAlert::where('status', 'resolved')->count(),
            'outsideZoneCount' => $this->outsideZoneCount(),
            'latestSosAlerts' => SosAlert::with(['pilgrim.group', 'pilgrim.hotel', 'latestAssignment.staff'])
                ->latest()
                ->limit(5)
                ->get(),
            'mapData' => $this->mapData(),
        ];
    }
}; ?>

<section>
    <div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded border border-gray-200 bg-white p-4">
                <div class="text-sm font-medium text-gray-500">Total Pilgrims</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalPilgrims }}</div>
            </div>
            <div class="rounded border border-gray-200 bg-white p-4">
                <div class="text-sm font-medium text-gray-500">Total Staff</div>
                <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $totalStaff }}</div>
            </div>
            <div class="rounded border border-red-100 bg-red-50 p-4">
                <div class="text-sm font-medium text-red-700">Active SOS</div>
                <div class="mt-2 text-2xl font-semibold text-red-900">{{ $activeSosCount }}</div>
            </div>
            <div class="rounded border border-emerald-100 bg-emerald-50 p-4">
                <div class="text-sm font-medium text-emerald-700">Resolved Alerts</div>
                <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ $resolvedSosCount }}</div>
            </div>
            <div class="rounded border border-amber-100 bg-amber-50 p-4">
                <div class="text-sm font-medium text-amber-700">Outside Zone</div>
                <div class="mt-2 text-2xl font-semibold text-amber-900">{{ $outsideZoneCount }}</div>
            </div>
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
            <div class="rounded border border-gray-200 bg-white p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Live Dashboard Map</h3>
                        <p class="mt-1 text-sm text-gray-500">Markers show latest known locations and active alerts.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-map-filter="all" class="map-filter rounded bg-gray-900 px-3 py-2 text-sm font-medium text-white">All</button>
                        <button type="button" data-map-filter="sos" class="map-filter rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">SOS only</button>
                        <button type="button" data-map-filter="staff" class="map-filter rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">Staff</button>
                        <button type="button" data-map-filter="hotel" class="map-filter rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">Hotels</button>
                        <button type="button" data-map-filter="zone" class="map-filter rounded border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">Zones</button>
                    </div>
                </div>

                <div wire:ignore id="dashboard-map" class="mt-4 h-[560px] w-full rounded border border-gray-200"></div>
            </div>

            <div class="rounded border border-gray-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Latest SOS</h3>
                    <a href="{{ route('sos-alerts.index') }}" wire:navigate class="text-sm text-blue-600 hover:text-blue-800">View all</a>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($latestSosAlerts as $alert)
                        <div class="rounded border border-gray-200 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $alert->pilgrim->name }}</div>
                                    <div class="mt-1 text-sm text-gray-500">{{ $alert->pilgrim->group?->group_name ?? 'No group' }} | {{ $alert->pilgrim->hotel?->hotel_name ?? 'No hotel' }}</div>
                                </div>
                                <span class="rounded px-2 py-1 text-xs font-medium {{ $alert->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : ($alert->status === 'assigned' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($alert->status) }}
                                </span>
                            </div>
                            <div class="mt-2 text-sm text-gray-600">Emergency: {{ $alert->pilgrim->emergency_contact }}</div>
                            <div class="mt-1 text-sm text-gray-500">Staff: {{ $alert->latestAssignment?->staff?->name ?? '-' }}</div>
                        </div>
                    @empty
                        <div class="rounded border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500">
                            No SOS alerts yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        (() => {
            const mapData = @js($mapData);
            let map = null;
            let markerLayer = null;
            let zoneLayer = null;

            const defaultCenter = [21.4225, 39.8262];
            const colors = {
                normal: '#16a34a',
                sos: '#dc2626',
                staff: '#2563eb',
                hotel: '#9333ea',
                zone: '#f59e0b',
            };

            function markerIcon(type) {
                return L.divIcon({
                    className: '',
                    html: `<div style="width:18px;height:18px;border-radius:9999px;background:${colors[type] ?? '#111827'};border:3px solid white;box-shadow:0 2px 8px rgba(0,0,0,.35);"></div>`,
                    iconSize: [18, 18],
                    iconAnchor: [9, 9],
                });
            }

            function popupHtml(marker) {
                return `
                    <div>
                        <strong>${marker.name}</strong><br>
                        Age: ${marker.age ?? '-'}<br>
                        Group: ${marker.group ?? '-'}<br>
                        Hotel: ${marker.hotel ?? '-'}<br>
                        Status: ${marker.status ?? '-'}
                    </div>
                `;
            }

            function initDashboardMap() {
                const element = document.getElementById('dashboard-map');

                if (!element || typeof L === 'undefined') {
                    return;
                }

                if (!map) {
                    map = L.map(element).setView(defaultCenter, 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                    }).addTo(map);

                    markerLayer = L.layerGroup().addTo(map);
                    zoneLayer = L.layerGroup().addTo(map);
                }

                renderMap('all');
                bindFilters();
                setTimeout(() => map.invalidateSize(), 100);
            }

            function renderMap(filter) {
                if (!map || !markerLayer || !zoneLayer) {
                    return;
                }

                markerLayer.clearLayers();
                zoneLayer.clearLayers();

                const bounds = [];
                const showZones = filter === 'all' || filter === 'zone';
                const showMarkers = mapData.markers.filter((marker) => filter === 'all' || marker.type === filter);

                showMarkers.forEach((marker) => {
                    const position = [marker.latitude, marker.longitude];
                    bounds.push(position);

                    L.marker(position, { icon: markerIcon(marker.type) })
                        .bindPopup(popupHtml(marker))
                        .addTo(markerLayer);
                });

                if (showZones) {
                    mapData.zones.forEach((zone) => {
                        const position = [zone.latitude, zone.longitude];
                        bounds.push(position);

                        L.circle(position, {
                            radius: zone.radius,
                            color: colors.zone,
                            weight: 2,
                            fillColor: colors.zone,
                            fillOpacity: 0.12,
                        })
                            .bindPopup(`<strong>${zone.name}</strong><br>${zone.status}`)
                            .addTo(zoneLayer);
                    });
                }

                if (bounds.length) {
                    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
                } else {
                    map.setView(defaultCenter, 13);
                }
            }

            function bindFilters() {
                document.querySelectorAll('.map-filter').forEach((button) => {
                    button.onclick = () => {
                        document.querySelectorAll('.map-filter').forEach((item) => {
                            item.classList.remove('bg-gray-900', 'text-white');
                            item.classList.add('border', 'border-gray-300', 'text-gray-700');
                        });

                        button.classList.add('bg-gray-900', 'text-white');
                        button.classList.remove('border', 'border-gray-300', 'text-gray-700');

                        renderMap(button.dataset.mapFilter);
                    };
                });
            }

            document.addEventListener('DOMContentLoaded', initDashboardMap);
            document.addEventListener('livewire:navigated', initDashboardMap);
            initDashboardMap();
        })();
    </script>
    @endscript
</section>
