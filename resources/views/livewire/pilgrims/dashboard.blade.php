<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        $pilgrim = auth()->user()?->pilgrim()
            ->with(['hotel', 'group.leader', 'group.pilgrims'])
            ->first();

        $latestLocation = $pilgrim?->locations()->latest('recorded_at')->first();
        $activeSos = $pilgrim?->sosAlerts()->whereIn('status', ['pending', 'assigned'])->latest()->first();

        $groupLocation = $pilgrim?->group?->group_latitude && $pilgrim?->group?->group_longitude
            ? [
                'name' => $pilgrim->group->group_name,
                'code' => $pilgrim->group->group_code,
                'leader' => $pilgrim->group->leader?->name ?? '-',
                'latitude' => (float) $pilgrim->group->group_latitude,
                'longitude' => (float) $pilgrim->group->group_longitude,
                'recorded_at' => $pilgrim->group->group_location_recorded_at?->format('Y-m-d H:i:s'),
            ]
            : null;

        return [
            'pilgrim' => $pilgrim,
            'latestLocation' => $latestLocation,
            'activeSos' => $activeSos,
            'hotelLocation' => $pilgrim?->hotel ? [
                'name' => $pilgrim->hotel->hotel_name,
                'address' => $pilgrim->hotel->address,
                'latitude' => (float) $pilgrim->hotel->latitude,
                'longitude' => (float) $pilgrim->hotel->longitude,
            ] : null,
            'groupLocation' => $groupLocation,
        ];
    }
}; ?>

<div>
    @if ($pilgrim)
        <section class="mx-auto max-w-5xl">
            <div class="rounded-lg bg-gray-900 px-5 py-6 text-white sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-300">Assalamualaikum</p>
                        <h2 class="mt-1 text-2xl font-semibold">{{ $pilgrim->name }}</h2>
                        <p class="mt-1 text-sm text-gray-300">{{ $pilgrim->group?->group_name ?? 'No group assigned' }}</p>
                    </div>

                    <div class="rounded-md bg-white/10 px-4 py-3 text-sm">
                        <div class="text-gray-300">SOS Status</div>
                        <div id="sos-status" class="mt-1 font-semibold {{ $activeSos ? 'text-red-300' : 'text-emerald-300' }}">
                            {{ $activeSos ? ucfirst($activeSos->status) : 'No active SOS' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="text-sm font-medium text-gray-500">Location Status</div>
                    <div id="location-status" class="mt-2 text-base font-semibold text-gray-900">
                        {{ $latestLocation ? 'Last updated' : 'Not shared yet' }}
                    </div>
                    <div id="location-time" class="mt-1 text-sm text-gray-500">
                        {{ $latestLocation?->recorded_at?->format('Y-m-d H:i:s') ?? '-' }}
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="text-sm font-medium text-gray-500">Hotel</div>
                    <div class="mt-2 text-base font-semibold text-gray-900">{{ $pilgrim->hotel?->hotel_name ?? 'Not assigned' }}</div>
                    <div class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $pilgrim->hotel?->address ?? '-' }}</div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="text-sm font-medium text-gray-500">Emergency Contact</div>
                    <div class="mt-2 text-base font-semibold text-gray-900">{{ $pilgrim->emergency_contact }}</div>
                    <div class="mt-1 text-sm text-gray-500">{{ $pilgrim->passport_no }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <button type="button" id="save-location-button" class="rounded-lg bg-gray-800 px-4 py-4 text-center text-sm font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-gray-700">
                    Update My Location
                </button>

                <button type="button" id="sos-button" class="rounded-lg bg-red-600 px-4 py-4 text-center text-sm font-semibold uppercase tracking-wide text-white shadow-sm hover:bg-red-500">
                    I Am Lost
                </button>

                <button type="button" id="hotel-location-button" class="rounded-lg border border-gray-300 bg-white px-4 py-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-800 shadow-sm hover:bg-gray-50">
                    View Hotel Location
                </button>

                <button type="button" id="group-location-button" class="rounded-lg border border-gray-300 bg-white px-4 py-4 text-center text-sm font-semibold uppercase tracking-wide text-gray-800 shadow-sm hover:bg-gray-50">
                    View Group Location
                </button>
            </div>

            <div class="mt-5 rounded-lg border border-gray-200 bg-white p-4">
                <div class="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 id="map-title" class="text-base font-semibold text-gray-900">Current Location</h3>
                        <p id="map-subtitle" class="text-sm text-gray-500">Click around Makkah or Madinah to pin your location.</p>
                    </div>
                    <span class="text-sm text-gray-500">{{ $pilgrim->group?->pilgrims_count ?? $pilgrim->group?->pilgrims->count() ?? 0 }} group members</span>
                </div>

                <div wire:ignore id="pilgrim-dashboard-map" class="h-[360px] w-full rounded border border-gray-200 sm:h-[430px]"></div>
            </div>
        </section>

        <script>
            (() => {
                const csrfToken = @js(csrf_token());
                const locationUrl = @js(route('locations.store'));
                const sosUrl = @js(route('pilgrims.sos.store'));
                const latestLocation = @js($latestLocation ? [
                    'latitude' => (float) $latestLocation->latitude,
                    'longitude' => (float) $latestLocation->longitude,
                    'recorded_at' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                ] : null);
                const hotelLocation = @js($hotelLocation);
                const groupLocation = @js($groupLocation);

                let map = null;
                let markerLayer = null;
                let pinnedLocation = null;

                const defaultCenter = [22.95, 39.72];
                const makkahMadinahBounds = L.latLngBounds(
                    [20.75, 38.85],
                    [25.25, 40.75]
                );

                function isInsideAllowedArea(latitude, longitude) {
                    return makkahMadinahBounds.contains([Number.parseFloat(latitude), Number.parseFloat(longitude)]);
                }

                function setText(id, value) {
                    const element = document.getElementById(id);

                    if (element) {
                        element.textContent = value;
                    }
                }

                function initMap() {
                    const mapElement = document.getElementById('pilgrim-dashboard-map');

                    if (!mapElement || typeof L === 'undefined') {
                        return;
                    }

                    if (!map) {
                        const center = latestLocation && isInsideAllowedArea(latestLocation.latitude, latestLocation.longitude)
                            ? [latestLocation.latitude, latestLocation.longitude]
                            : defaultCenter;

                        map = L.map(mapElement, {
                            maxBounds: makkahMadinahBounds,
                            maxBoundsViscosity: 1.0,
                            minZoom: 7,
                        }).setView(center, latestLocation ? 16 : 8);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19,
                            attribution: '&copy; OpenStreetMap contributors',
                        }).addTo(map);

                        markerLayer = L.layerGroup().addTo(map);

                        L.rectangle(makkahMadinahBounds, {
                            color: '#2563eb',
                            weight: 1,
                            fillOpacity: 0.03,
                        }).addTo(map);

                        map.on('click', (event) => {
                            setPinnedLocation(event.latlng.lat, event.latlng.lng);
                        });
                    }

                    if (latestLocation && isInsideAllowedArea(latestLocation.latitude, latestLocation.longitude)) {
                        showSingleMarker(latestLocation.latitude, latestLocation.longitude, 'Your latest location', 'Current Location');
                    }

                    setTimeout(() => map.invalidateSize(), 100);
                }

                function clearMarkers() {
                    if (markerLayer) {
                        markerLayer.clearLayers();
                    }
                }

                function showSingleMarker(latitude, longitude, title, mapTitle, subtitle = '') {
                    if (!map || !markerLayer) {
                        return;
                    }

                    if (!isInsideAllowedArea(latitude, longitude)) {
                        clearMarkers();
                        map.setView(defaultCenter, 8);
                        setText('map-title', 'Current Location');
                        setText('map-subtitle', 'Latest saved location is outside Makkah/Madinah. Click the map to pin a new location.');
                        return;
                    }

                    clearMarkers();

                    const position = [Number.parseFloat(latitude), Number.parseFloat(longitude)];
                    L.marker(position).bindPopup(title).addTo(markerLayer).openPopup();
                    map.setView(position, 17);

                    setText('map-title', mapTitle);
                    setText('map-subtitle', subtitle);
                }

                function setPinnedLocation(latitude, longitude) {
                    if (!isInsideAllowedArea(latitude, longitude)) {
                        Swal.fire({
                            title: 'Outside allowed area',
                            text: 'Please select a location around Makkah or Madinah, Saudi Arabia.',
                            icon: 'warning',
                        });
                        return;
                    }

                    pinnedLocation = {
                        latitude,
                        longitude,
                        recorded_at: new Date().toISOString(),
                    };

                    showSingleMarker(
                        latitude,
                        longitude,
                        'Pinned location',
                        'Pinned Location',
                        `${latitude.toFixed(8)}, ${longitude.toFixed(8)}`
                    );
                    setText('location-status', 'Pinned location selected');
                    setText('location-time', 'Click Update My Location or I Am Lost to use it.');
                }

                function getCurrentPosition() {
                    return new Promise((resolve, reject) => {
                        if (!window.isSecureContext) {
                            reject(new Error('GPS is blocked because this page is not secure. Open the app using HTTPS, for example https://hujjaj-tracker.test, or run it on localhost.'));
                            return;
                        }

                        if (!navigator.geolocation) {
                            reject(new Error('Geolocation is not supported by this browser.'));
                            return;
                        }

                        navigator.geolocation.getCurrentPosition(resolve, (error) => {
                            if (error.code === error.PERMISSION_DENIED) {
                                reject(new Error('Location permission was denied. Please allow location access in your browser.'));
                                return;
                            }

                            if (error.code === error.POSITION_UNAVAILABLE) {
                                reject(new Error('Your current GPS location is unavailable. Please check device location settings.'));
                                return;
                            }

                            if (error.code === error.TIMEOUT) {
                                reject(new Error('GPS request timed out. Please try again.'));
                                return;
                            }

                            reject(new Error(error.message || 'Unable to get your current GPS location.'));
                        }, {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        });
                    });
                }

                async function postJson(url, payload) {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify(payload),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.message || 'Request failed.');
                    }

                    return data;
                }

                async function updateMyLocation(showPopup = true) {
                    try {
                        let payload = pinnedLocation;

                        if (payload) {
                            setText('location-status', 'Saving pinned location...');
                        } else {
                            setText('location-status', 'Getting GPS location...');
                            const position = await getCurrentPosition();

                            payload = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                recorded_at: new Date(position.timestamp).toISOString(),
                            };
                        }

                        const data = await postJson(locationUrl, payload);

                        setText('location-status', 'Location updated');
                        setText('location-time', data.location.recorded_at);
                        pinnedLocation = null;
                        showSingleMarker(data.location.latitude, data.location.longitude, 'Your latest location', 'Current Location', data.location.recorded_at);

                        if (showPopup) {
                            Swal.fire({
                                title: 'Location updated',
                                text: 'Your current location has been shared.',
                                icon: 'success',
                                timer: 1800,
                                showConfirmButton: false,
                            });
                        }

                        return payload;
                    } catch (error) {
                        setText('location-status', error.message);
                        Swal.fire({
                            title: 'Unable to update location',
                            text: error.message,
                            icon: 'error',
                        });
                        throw error;
                    }
                }

                async function sendSosAlert() {
                    const confirmation = await Swal.fire({
                        title: 'Send SOS alert?',
                        text: 'Your current GPS location will be sent to staff.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, I am lost',
                        cancelButtonText: 'Cancel',
                        confirmButtonColor: '#dc2626',
                    });

                    if (!confirmation.isConfirmed) {
                        return;
                    }

                    try {
                        let payload = pinnedLocation
                            ? {
                                latitude: pinnedLocation.latitude,
                                longitude: pinnedLocation.longitude,
                            }
                            : null;

                        if (!payload) {
                            const position = await getCurrentPosition();
                            payload = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                            };
                        }

                        const data = await postJson(sosUrl, payload);

                        setText('sos-status', data.alert.status.charAt(0).toUpperCase() + data.alert.status.slice(1));
                        pinnedLocation = null;
                        showSingleMarker(data.alert.latitude, data.alert.longitude, 'SOS location', 'SOS Location', data.alert.created_at);

                        Swal.fire({
                            title: 'SOS sent',
                            text: 'Staff can now see your lost pilgrim alert.',
                            icon: 'success',
                        });
                    } catch (error) {
                        Swal.fire({
                            title: 'Unable to send SOS',
                            text: error.message,
                            icon: 'error',
                        });
                    }
                }

                function viewHotelLocation() {
                    if (!hotelLocation) {
                        Swal.fire({
                            title: 'No hotel assigned',
                            text: 'Your pilgrim profile does not have a hotel yet.',
                            icon: 'info',
                        });
                        return;
                    }

                    showSingleMarker(
                        hotelLocation.latitude,
                        hotelLocation.longitude,
                        `<strong>${hotelLocation.name}</strong><br>${hotelLocation.address}`,
                        'Hotel Location',
                        hotelLocation.address
                    );
                }

                function viewGroupLocation() {
                    if (!groupLocation) {
                        Swal.fire({
                            title: 'No group location yet',
                            text: 'Your group leader needs to update the group location first.',
                            icon: 'info',
                        });
                        return;
                    }

                    showSingleMarker(
                        groupLocation.latitude,
                        groupLocation.longitude,
                        `<strong>${groupLocation.name}</strong><br>Code: ${groupLocation.code}<br>Leader: ${groupLocation.leader}<br>Updated: ${groupLocation.recorded_at ?? '-'}`,
                        'Group Location',
                        `Updated by group leader: ${groupLocation.recorded_at ?? '-'}`
                    );
                }

                function bindDashboardButtons() {
                    const saveButton = document.getElementById('save-location-button');
                    const sosButton = document.getElementById('sos-button');
                    const hotelButton = document.getElementById('hotel-location-button');
                    const groupButton = document.getElementById('group-location-button');

                    if (saveButton) saveButton.onclick = () => updateMyLocation(true);
                    if (sosButton) sosButton.onclick = sendSosAlert;
                    if (hotelButton) hotelButton.onclick = viewHotelLocation;
                    if (groupButton) groupButton.onclick = viewGroupLocation;
                }

                document.addEventListener('DOMContentLoaded', () => {
                    initMap();
                    bindDashboardButtons();
                });

                document.addEventListener('livewire:navigated', () => {
                    initMap();
                    bindDashboardButtons();
                });

                initMap();
                bindDashboardButtons();
            })();
        </script>
    @else
        <section class="rounded border border-gray-200 bg-gray-50 p-6 text-gray-600">
            No pilgrim profile is linked to your account yet.
        </section>
    @endif
</div>
