<?php

use App\Models\Group as HajjGroup;
use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        $groups = HajjGroup::where('leader_id', auth()->id())
            ->withCount('pilgrims')
            ->orderBy('group_name')
            ->get();

        return [
            'groups' => $groups,
            'groupLocations' => $groups->map(fn (HajjGroup $group) => [
                'id' => $group->id,
                'group_name' => $group->group_name,
                'group_code' => $group->group_code,
                'members_count' => $group->pilgrims_count,
                'latitude' => $group->group_latitude ? (float) $group->group_latitude : null,
                'longitude' => $group->group_longitude ? (float) $group->group_longitude : null,
                'recorded_at' => $group->group_location_recorded_at?->format('Y-m-d H:i:s'),
            ])->values()->all(),
        ];
    }
}; ?>

<div>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Group Location Update</h2>
            <p class="mt-1 text-sm text-gray-500">Share your group meeting point so pilgrims can find their group from the Pilgrim Dashboard.</p>
        </header>

        @if ($groups->isNotEmpty())
            <div class="mt-6 grid gap-6 xl:grid-cols-[360px_1fr]">
                <div class="rounded border border-gray-200 bg-white p-4">
                    <div>
                        <x-input-label for="group_id" value="Select Group" />
                        <select id="group_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}">
                                    {{ $group->group_name }} ({{ $group->group_code }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <div class="font-medium text-gray-700">Group Members</div>
                            <div id="group-members-count" class="mt-1 text-gray-600">-</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-700">Selected Latitude</div>
                            <div id="selected-latitude" class="mt-1 text-gray-600">-</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-700">Selected Longitude</div>
                            <div id="selected-longitude" class="mt-1 text-gray-600">-</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-700">Last Updated</div>
                            <div id="group-recorded-at" class="mt-1 text-gray-600">-</div>
                        </div>
                        <div>
                            <div class="font-medium text-gray-700">Status</div>
                            <div id="group-location-status" class="mt-1 text-gray-600">Click map to pin group location.</div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3">
                        <x-secondary-button type="button" id="use-current-location">
                            Use Current GPS
                        </x-secondary-button>

                        <x-primary-button type="button" id="save-group-location">
                            Save Group Location
                        </x-primary-button>
                    </div>
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Group Location Map</h3>
                        <span class="text-sm text-gray-500">Makkah and Madinah region only</span>
                    </div>

                    <div wire:ignore id="group-location-map" class="h-[460px] w-full rounded border border-gray-200"></div>
                    <p class="mt-2 text-sm text-gray-500">Click the map to pin your group location, then save.</p>
                </div>
            </div>
        @else
            <div class="mt-6 rounded border border-gray-200 bg-gray-50 p-6 text-gray-600">
                You are not assigned as leader for any hajj group yet.
            </div>
        @endif
    </section>

    @if ($groups->isNotEmpty())
        <script>
            (() => {
                const csrfToken = @js(csrf_token());
                const storeUrl = @js(route('groups.location.store'));
                const groupLocations = @js($groupLocations);

                let map = null;
                let marker = null;
                let selectedLocation = null;

                const defaultCenter = [22.95, 39.72];
                const makkahMadinahBounds = L.latLngBounds(
                    [20.75, 38.85],
                    [25.25, 40.75]
                );

                function currentGroup() {
                    const groupId = Number(document.getElementById('group_id')?.value);

                    return groupLocations.find((group) => Number(group.id) === groupId);
                }

                function isInsideAllowedArea(latitude, longitude) {
                    return makkahMadinahBounds.contains([Number.parseFloat(latitude), Number.parseFloat(longitude)]);
                }

                function setText(id, value) {
                    const element = document.getElementById(id);

                    if (element) {
                        element.textContent = value;
                    }
                }

                function initGroupLocationMap() {
                    const mapElement = document.getElementById('group-location-map');

                    if (!mapElement || typeof L === 'undefined') {
                        return;
                    }

                    if (!map) {
                        map = L.map(mapElement, {
                            maxBounds: makkahMadinahBounds,
                            maxBoundsViscosity: 1.0,
                            minZoom: 7,
                        }).setView(defaultCenter, 8);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors',
                        }).addTo(map);

                        L.rectangle(makkahMadinahBounds, {
                            color: '#2563eb',
                            weight: 1,
                            fillOpacity: 0.03,
                        }).addTo(map);

                        map.on('click', (event) => {
                            setSelectedLocation(event.latlng.lat, event.latlng.lng, 'Pinned group location');
                        });
                    }

                    loadSelectedGroupLocation();
                    setTimeout(() => map.invalidateSize(), 100);
                }

                function setSelectedLocation(latitude, longitude, popupText = 'Group location') {
                    if (!isInsideAllowedArea(latitude, longitude)) {
                        Swal.fire({
                            title: 'Outside allowed area',
                            text: 'Please select a location around Makkah or Madinah, Saudi Arabia.',
                            icon: 'warning',
                        });
                        return;
                    }

                    selectedLocation = {
                        latitude,
                        longitude,
                        recorded_at: new Date().toISOString(),
                    };

                    const position = [latitude, longitude];

                    if (!marker) {
                        marker = L.marker(position).addTo(map);
                    } else {
                        marker.setLatLng(position);
                    }

                    marker.bindPopup(popupText).openPopup();
                    map.setView(position, 16);

                    setText('selected-latitude', latitude.toFixed(8));
                    setText('selected-longitude', longitude.toFixed(8));
                    setText('group-location-status', 'Location selected. Click Save Group Location.');
                }

                function loadSelectedGroupLocation() {
                    const group = currentGroup();

                    if (!group) {
                        return;
                    }

                    setText('group-members-count', group.members_count);
                    setText('group-recorded-at', group.recorded_at ?? '-');

                    if (group.latitude && group.longitude && isInsideAllowedArea(group.latitude, group.longitude)) {
                        setSelectedLocation(group.latitude, group.longitude, `${group.group_name} location`);
                        setText('group-location-status', 'Current saved group location loaded.');
                    } else {
                        selectedLocation = null;
                        setText('selected-latitude', '-');
                        setText('selected-longitude', '-');
                        setText('group-location-status', 'Click map to pin group location.');
                        map.setView(defaultCenter, 8);

                        if (marker) {
                            marker.remove();
                            marker = null;
                        }
                    }
                }

                function getCurrentPosition() {
                    return new Promise((resolve, reject) => {
                        if (!window.isSecureContext) {
                            reject(new Error('GPS is blocked because this page is not secure. Open the app using HTTPS or run it on localhost.'));
                            return;
                        }

                        if (!navigator.geolocation) {
                            reject(new Error('Geolocation is not supported by this browser.'));
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(resolve, (error) => {
                            reject(new Error(error.message || 'Unable to get your current GPS location.'));
                        }, {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        });
                    });
                }

                async function useCurrentLocation() {
                    try {
                        setText('group-location-status', 'Getting GPS location...');
                        const position = await getCurrentPosition();

                        setSelectedLocation(position.coords.latitude, position.coords.longitude, 'Current GPS location');
                    } catch (error) {
                        Swal.fire({
                            title: 'Unable to get GPS',
                            text: error.message,
                            icon: 'error',
                        });
                    }
                }

                async function saveGroupLocation() {
                    const group = currentGroup();

                    if (!group || !selectedLocation) {
                        Swal.fire({
                            title: 'Location required',
                            text: 'Please click the map or use GPS before saving.',
                            icon: 'warning',
                        });
                        return;
                    }

                    try {
                        setText('group-location-status', 'Saving group location...');

                        const response = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({
                                group_id: group.id,
                                latitude: selectedLocation.latitude,
                                longitude: selectedLocation.longitude,
                                recorded_at: selectedLocation.recorded_at,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Group location could not be saved.');
                        }

                        group.latitude = data.group.latitude;
                        group.longitude = data.group.longitude;
                        group.recorded_at = data.group.recorded_at;
                        setText('group-recorded-at', data.group.recorded_at);
                        setText('group-location-status', 'Group location saved successfully.');

                        Swal.fire({
                            title: 'Location saved',
                            text: 'Pilgrims can now view this group location.',
                            icon: 'success',
                            timer: 1800,
                            showConfirmButton: false,
                        });
                    } catch (error) {
                        setText('group-location-status', error.message);
                        Swal.fire({
                            title: 'Unable to save location',
                            text: error.message,
                            icon: 'error',
                        });
                    }
                }

                function bindGroupLocationControls() {
                    const groupSelect = document.getElementById('group_id');
                    const gpsButton = document.getElementById('use-current-location');
                    const saveButton = document.getElementById('save-group-location');

                    if (groupSelect) groupSelect.onchange = loadSelectedGroupLocation;
                    if (gpsButton) gpsButton.onclick = useCurrentLocation;
                    if (saveButton) saveButton.onclick = saveGroupLocation;
                }

                document.addEventListener('DOMContentLoaded', () => {
                    initGroupLocationMap();
                    bindGroupLocationControls();
                });

                document.addEventListener('livewire:navigated', () => {
                    initGroupLocationMap();
                    bindGroupLocationControls();
                });

                initGroupLocationMap();
                bindGroupLocationControls();
            })();
        </script>
    @endif
</div>
