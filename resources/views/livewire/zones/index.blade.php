<?php

use App\Models\Pilgrim;
use App\Models\Zone;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|numeric|between:-90,90')]
    public string $latitude = '';

    #[Validate('required|numeric|between:-180,180')]
    public string $longitude = '';

    #[Validate('required|integer|min:1|max:50000')]
    public string $radius = '500';

    public ?Zone $editingZone = null;

    private function distanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('zones', 'name')->ignore($this->editingZone?->id)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:1', 'max:50000'],
        ]);

        if ($this->editingZone) {
            $this->editingZone->update($validated);
            $message = 'Zone updated successfully!';
        } else {
            Zone::create($validated);
            $message = 'Zone created successfully!';
        }

        $this->resetForm();
        $this->resetPage();

        $this->dispatch('zones-map-refresh', zones: $this->mapZones());
        $this->dispatch('swal', [
            'title' => 'Success!',
            'text' => $message,
            'icon' => 'success',
        ]);
    }

    public function edit(Zone $zone): void
    {
        $this->editingZone = $zone;
        $this->name = $zone->name;
        $this->latitude = (string) $zone->latitude;
        $this->longitude = (string) $zone->longitude;
        $this->radius = (string) $zone->radius;

        $this->dispatch('zone-map-focus', zone: $this->zonePayload($zone));
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(Zone $zone): void
    {
        $zone->delete();
        $this->resetForm();
        $this->resetPage();

        $this->dispatch('zones-map-refresh', zones: $this->mapZones());
        $this->dispatch('swal', [
            'title' => 'Deleted!',
            'text' => 'Zone deleted successfully!',
            'icon' => 'success',
        ]);
    }

    public function viewOnMap(Zone $zone): void
    {
        $this->dispatch('zone-map-focus', zone: $this->zonePayload($zone));
    }

    private function resetForm(): void
    {
        $this->editingZone = null;
        $this->reset('name', 'latitude', 'longitude');
        $this->radius = '500';
        $this->resetValidation();
    }

    private function zonePayload(Zone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'latitude' => (float) $zone->latitude,
            'longitude' => (float) $zone->longitude,
            'radius' => $zone->radius,
        ];
    }

    private function mapZones(): array
    {
        return Zone::orderBy('id')
            ->get()
            ->map(fn (Zone $zone) => $this->zonePayload($zone))
            ->values()
            ->all();
    }

    private function geofenceRows(): array
    {
        $zones = Zone::orderBy('name')->get();

        return Pilgrim::with(['group', 'hotel', 'latestLocation'])
            ->whereHas('latestLocation')
            ->orderBy('name')
            ->get()
            ->map(function (Pilgrim $pilgrim) use ($zones) {
                $location = $pilgrim->latestLocation;
                $matchedZone = null;
                $nearestZone = null;
                $nearestDistance = null;

                foreach ($zones as $zone) {
                    $distance = $this->distanceMeters(
                        (float) $location->latitude,
                        (float) $location->longitude,
                        (float) $zone->latitude,
                        (float) $zone->longitude
                    );

                    if ($nearestDistance === null || $distance < $nearestDistance) {
                        $nearestDistance = $distance;
                        $nearestZone = $zone;
                    }

                    if ($distance <= $zone->radius) {
                        $matchedZone = $zone;
                        break;
                    }
                }

                return [
                    'name' => $pilgrim->name,
                    'group' => $pilgrim->group?->group_name ?? '-',
                    'hotel' => $pilgrim->hotel?->hotel_name ?? '-',
                    'latitude' => (float) $location->latitude,
                    'longitude' => (float) $location->longitude,
                    'recorded_at' => $location->recorded_at->format('Y-m-d H:i:s'),
                    'status' => $matchedZone ? 'inside' : 'outside',
                    'zone' => $matchedZone?->name ?? $nearestZone?->name ?? '-',
                    'distance' => $nearestDistance !== null ? round($nearestDistance) : null,
                ];
            })
            ->values()
            ->all();
    }

    public function with(): array
    {
        $geofenceRows = $this->geofenceRows();

        return [
            'zones' => Zone::orderBy('id')->paginate(10),
            'mapZones' => $this->mapZones(),
            'geofenceRows' => $geofenceRows,
            'insideCount' => collect($geofenceRows)->where('status', 'inside')->count(),
            'outsideCount' => collect($geofenceRows)->where('status', 'outside')->count(),
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Geofencing Zones</h2>
        <p class="mt-1 text-sm text-gray-500">Create location zones, show radius circles, and detect pilgrims outside allowed areas.</p>
    </header>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <div class="rounded border border-emerald-100 bg-emerald-50 p-4">
            <div class="text-sm font-medium text-emerald-700">Pilgrims Inside Zone</div>
            <div class="mt-2 text-2xl font-semibold text-emerald-900">{{ $insideCount }}</div>
        </div>
        <div class="rounded border border-red-100 bg-red-50 p-4">
            <div class="text-sm font-medium text-red-700">Pilgrims Outside Zone</div>
            <div class="mt-2 text-2xl font-semibold text-red-900">{{ $outsideCount }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[420px_1fr]">
        <form wire:submit="save" class="space-y-4 rounded border border-gray-200 bg-white p-4">
            <h3 class="text-base font-semibold text-gray-900">{{ $editingZone ? 'Update Zone' : 'Create Zone' }}</h3>

            <div>
                <x-input-label for="name" value="Zone Name" />
                <x-text-input wire:model="name" id="name" class="mt-1 w-full" placeholder="Example: Masjidil Haram" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="latitude" value="Latitude" />
                    <x-text-input wire:model="latitude" id="latitude" class="mt-1 w-full" placeholder="21.4225" />
                    <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="longitude" value="Longitude" />
                    <x-text-input wire:model="longitude" id="longitude" class="mt-1 w-full" placeholder="39.8262" />
                    <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="radius" value="Radius (meters)" />
                <x-text-input wire:model="radius" id="radius" type="number" min="1" class="mt-1 w-full" />
                <x-input-error :messages="$errors->get('radius')" class="mt-2" />
            </div>

            <p class="text-sm text-gray-500">Tip: click the map to fill latitude and longitude.</p>

            <div class="flex justify-end gap-3">
                @if ($editingZone)
                    <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
                @endif
                <x-primary-button>{{ $editingZone ? 'Update' : 'Save' }}</x-primary-button>
            </div>
        </form>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Zone Map</h3>
                <span class="text-sm text-gray-500">{{ count($mapZones) }} zones</span>
            </div>

            <div wire:ignore id="zones-map" class="h-[420px] w-full rounded border border-gray-200"></div>
        </div>
    </div>

    <div class="mt-8 overflow-x-auto">
        <h3 class="text-base font-semibold text-gray-900">Zone List</h3>
        <table class="mt-4 w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Name</th>
                    <th class="p-2 border border-gray-300">Latitude</th>
                    <th class="p-2 border border-gray-300">Longitude</th>
                    <th class="p-2 border border-gray-300">Radius</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($zones as $zone)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $zones->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300">{{ $zone->name }}</td>
                        <td class="p-2 border border-gray-300">{{ $zone->latitude }}</td>
                        <td class="p-2 border border-gray-300">{{ $zone->longitude }}</td>
                        <td class="p-2 border border-gray-300">{{ $zone->radius }}m</td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex gap-2">
                                <button type="button" wire:click="viewOnMap({{ $zone->id }})" class="text-emerald-600 hover:text-emerald-800">Map</button>
                                <button type="button" wire:click="edit({{ $zone->id }})" class="text-blue-600 hover:text-blue-800">Edit</button>
                                <button type="button" wire:click="delete({{ $zone->id }})" wire:confirm="Delete this zone?" class="text-red-600 hover:text-red-800">Delete</button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-4 text-center text-gray-500 border border-gray-300">
                            No zones found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4 flex justify-end">
            {{ $zones->links('vendor.pagination.custom') }}
        </div>
    </div>

    <div class="mt-8 overflow-x-auto">
        <h3 class="text-base font-semibold text-gray-900">Pilgrim Geofence Detection</h3>
        <table class="mt-4 w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Pilgrim</th>
                    <th class="p-2 border border-gray-300">Group</th>
                    <th class="p-2 border border-gray-300">Hotel</th>
                    <th class="p-2 border border-gray-300">Latest Location</th>
                    <th class="p-2 border border-gray-300">Zone</th>
                    <th class="p-2 border border-gray-300">Distance</th>
                    <th class="p-2 border border-gray-300">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($geofenceRows as $row)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $loop->iteration }}</td>
                        <td class="p-2 border border-gray-300">{{ $row['name'] }}</td>
                        <td class="p-2 border border-gray-300">{{ $row['group'] }}</td>
                        <td class="p-2 border border-gray-300">{{ $row['hotel'] }}</td>
                        <td class="p-2 border border-gray-300">
                            {{ $row['latitude'] }}, {{ $row['longitude'] }}
                            <div class="text-xs text-gray-500">{{ $row['recorded_at'] }}</div>
                        </td>
                        <td class="p-2 border border-gray-300">{{ $row['zone'] }}</td>
                        <td class="p-2 border border-gray-300">{{ $row['distance'] !== null ? $row['distance'] . 'm' : '-' }}</td>
                        <td class="p-2 border border-gray-300">
                            @if ($row['status'] === 'inside')
                                <span class="rounded bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800">Inside</span>
                            @else
                                <span class="rounded bg-red-100 px-2 py-1 text-xs font-medium text-red-800">Outside</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-4 text-center text-gray-500 border border-gray-300">
                            No pilgrim locations recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @script
    <script>
        const initialZones = @js($mapZones);
        let zonesMap = null;
        let zonesLayer = null;
        let draftCircle = null;
        const defaultCenter = [22.95, 39.72];

        function initZonesMap() {
            const mapElement = document.getElementById('zones-map');

            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            if (!zonesMap) {
                zonesMap = L.map(mapElement).setView(defaultCenter, 8);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(zonesMap);

                zonesLayer = L.layerGroup().addTo(zonesMap);

                zonesMap.on('click', (event) => {
                    @this.set('latitude', event.latlng.lat.toFixed(8));
                    @this.set('longitude', event.latlng.lng.toFixed(8));
                    showDraftZone(event.latlng.lat, event.latlng.lng, Number(document.getElementById('radius')?.value) || 500);
                });
            }

            renderZones(initialZones);
            setTimeout(() => zonesMap.invalidateSize(), 100);
        }

        function renderZones(zones) {
            if (!zonesMap || !zonesLayer) {
                return;
            }

            zonesLayer.clearLayers();

            if (!zones.length) {
                zonesMap.setView(defaultCenter, 8);
                return;
            }

            const bounds = [];

            zones.forEach((zone) => {
                const position = [zone.latitude, zone.longitude];
                bounds.push(position);

                L.circle(position, {
                    radius: zone.radius,
                    color: '#f59e0b',
                    weight: 2,
                    fillColor: '#f59e0b',
                    fillOpacity: 0.12,
                })
                    .bindPopup(`<strong>${zone.name}</strong><br>Radius: ${zone.radius}m`)
                    .addTo(zonesLayer);
            });

            zonesMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 13 });
        }

        function showDraftZone(latitude, longitude, radius) {
            if (!zonesMap) {
                return;
            }

            if (draftCircle) {
                draftCircle.remove();
            }

            draftCircle = L.circle([latitude, longitude], {
                radius,
                color: '#2563eb',
                weight: 2,
                fillColor: '#2563eb',
                fillOpacity: 0.12,
            })
                .bindPopup('Draft zone location')
                .addTo(zonesMap)
                .openPopup();

            zonesMap.setView([latitude, longitude], 15);
        }

        document.addEventListener('DOMContentLoaded', initZonesMap);
        document.addEventListener('livewire:navigated', initZonesMap);

        Livewire.on('zones-map-refresh', (event) => {
            const payload = Array.isArray(event) ? event[0] : event;
            renderZones(payload.zones ?? []);
        });

        Livewire.on('zone-map-focus', (event) => {
            const payload = Array.isArray(event) ? event[0] : event;
            const zone = payload.zone;

            if (!zonesMap || !zone) {
                return;
            }

            zonesMap.setView([zone.latitude, zone.longitude], 15);
            L.popup()
                .setLatLng([zone.latitude, zone.longitude])
                .setContent(`<strong>${zone.name}</strong><br>Radius: ${zone.radius}m`)
                .openOn(zonesMap);
        });

        initZonesMap();
    </script>
    @endscript
</section>
