<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        $pilgrim = auth()->user()?->pilgrim()->with('locations')->first();

        return [
            'pilgrim' => $pilgrim,
            'latestLocation' => $pilgrim?->locations()->latest('recorded_at')->first(),
            'recentLocations' => $pilgrim?->locations()->latest('recorded_at')->limit(10)->get() ?? collect(),
        ];
    }
}; ?>

<div>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Location Tracking</h2>
            <p class="mt-1 text-sm text-gray-500">Send your current browser GPS location to the system.</p>
        </header>

        @if ($pilgrim)
            <div class="mt-6 grid gap-6 xl:grid-cols-[360px_1fr]">
                <div class="rounded border border-gray-200 bg-white p-4">
                    <h3 class="text-base font-semibold text-gray-900">{{ $pilgrim->name }}</h3>

                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <div class="font-medium text-gray-700">Tracking Status</div>
                            <div id="tracking-status" class="mt-1 text-gray-600">Not started</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700">Latest Latitude</div>
                            <div id="latest-latitude" class="mt-1 text-gray-600">{{ $latestLocation?->latitude ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700">Latest Longitude</div>
                            <div id="latest-longitude" class="mt-1 text-gray-600">{{ $latestLocation?->longitude ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700">Recorded At</div>
                            <div id="latest-recorded-at" class="mt-1 text-gray-600">{{ $latestLocation?->recorded_at?->format('Y-m-d H:i:s') ?? '-' }}</div>
                        </div>

                        <div>
                            <div class="font-medium text-gray-700">Pinned Location</div>
                            <div id="pinned-location" class="mt-1 text-gray-600">Click the map to pin your location.</div>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-3">
                        <x-primary-button type="button" id="save-current-location">
                            Save Current Location
                        </x-primary-button>

                        <x-secondary-button type="button" id="start-location-tracking">
                            Start Auto Tracking
                        </x-secondary-button>

                        <x-secondary-button type="button" id="stop-location-tracking">
                            Stop Auto Tracking
                        </x-secondary-button>
                    </div>
                </div>

                <div class="rounded border border-gray-200 bg-white p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Current Location Map</h3>
                        <span class="text-sm text-gray-500">Makkah and Madinah region only</span>
                    </div>

                    <div wire:ignore id="my-location-map" class="h-[420px] w-full rounded border border-gray-200"></div>
                    <p class="mt-2 text-sm text-gray-500">Click the map to manually pinpoint your current location, then save.</p>
                </div>
            </div>

            <div class="mt-8 overflow-x-auto">
                <h3 class="text-base font-semibold text-gray-900">Recent Location History</h3>

                <table class="mt-4 w-full text-left border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="p-2 border border-gray-300">No.</th>
                            <th class="p-2 border border-gray-300">Latitude</th>
                            <th class="p-2 border border-gray-300">Longitude</th>
                            <th class="p-2 border border-gray-300">Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLocations as $location)
                            <tr>
                                <td class="p-2 border border-gray-300">{{ $loop->iteration }}</td>
                                <td class="p-2 border border-gray-300">{{ $location->latitude }}</td>
                                <td class="p-2 border border-gray-300">{{ $location->longitude }}</td>
                                <td class="p-2 border border-gray-300">{{ $location->recorded_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500 border border-gray-300">
                                    No location has been recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <div class="mt-6 rounded border border-gray-200 bg-gray-50 p-6 text-gray-600">
                No pilgrim profile is linked to your account yet.
            </div>
        @endif
    </section>

    @if ($pilgrim)
        <script>
            (() => {
                const storeUrl = @js(route('locations.store'));
                const csrfToken = @js(csrf_token());
                const initialLocation = @js($latestLocation ? [
                    'latitude' => (float) $latestLocation->latitude,
                    'longitude' => (float) $latestLocation->longitude,
                    'recorded_at' => $latestLocation->recorded_at->format('Y-m-d H:i:s'),
                ] : null);

                let trackingTimer = null;
                let map = null;
                let marker = null;
                let pinnedLocation = null;

                const defaultCenter = [22.95, 39.72];
                const makkahMadinahBounds = L.latLngBounds(
                    [20.75, 38.85],
                    [25.25, 40.75]
                );

                function isInsideAllowedArea(latitude, longitude) {
                    return makkahMadinahBounds.contains([Number.parseFloat(latitude), Number.parseFloat(longitude)]);
                }

                function setStatus(message) {
                    const status = document.getElementById('tracking-status');

                    if (status) {
                        status.textContent = message;
                    }
                }

                function updateLocationDisplay(location) {
                    document.getElementById('latest-latitude').textContent = location.latitude;
                    document.getElementById('latest-longitude').textContent = location.longitude;
                    document.getElementById('latest-recorded-at').textContent = location.recorded_at;
                    focusMap(location.latitude, location.longitude);
                }

                function initLocationMap() {
                    const mapElement = document.getElementById('my-location-map');

                    if (!mapElement || typeof L === 'undefined') {
                        return;
                    }

                    if (!map) {
                        const center = initialLocation && isInsideAllowedArea(initialLocation.latitude, initialLocation.longitude)
                            ? [initialLocation.latitude, initialLocation.longitude]
                            : defaultCenter;

                        map = L.map(mapElement, {
                            maxBounds: makkahMadinahBounds,
                            maxBoundsViscosity: 1.0,
                            minZoom: 7,
                        }).setView(center, initialLocation ? 16 : 8);

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
                            setPinnedLocation(event.latlng.lat, event.latlng.lng);
                        });
                    }

                    if (initialLocation && isInsideAllowedArea(initialLocation.latitude, initialLocation.longitude)) {
                        focusMap(initialLocation.latitude, initialLocation.longitude);
                    }

                    setTimeout(() => map.invalidateSize(), 100);
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

                    document.getElementById('pinned-location').textContent = `${latitude.toFixed(8)}, ${longitude.toFixed(8)}`;
                    setStatus('Pinned location selected. Click Save Current Location to store it.');
                    focusMap(latitude, longitude, 'Pinned location');
                }

                function focusMap(latitude, longitude) {
                    const popupText = arguments[2] || 'Your latest location';

                    if (!map) {
                        return;
                    }

                    if (!isInsideAllowedArea(latitude, longitude)) {
                        map.setView(defaultCenter, 8);
                        setStatus('Latest saved location is outside Makkah/Madinah. Click the map to pin a new location.');
                        return;
                    }

                    const position = [Number.parseFloat(latitude), Number.parseFloat(longitude)];

                    if (!marker) {
                        marker = L.marker(position).addTo(map);
                    } else {
                        marker.setLatLng(position);
                    }

                    marker.bindPopup(popupText).openPopup();
                    map.setView(position, 17);
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

                async function saveCurrentLocation(showPopup = true) {
                    try {
                        let payload = pinnedLocation;

                        if (payload) {
                            setStatus('Saving pinned location...');
                        } else {
                            setStatus('Getting current location...');

                            const position = await getCurrentPosition();
                            payload = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                recorded_at: new Date(position.timestamp).toISOString(),
                            };
                        }

                        setStatus('Saving location...');

                        const response = await fetch(storeUrl, {
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
                            throw new Error(data.message || 'Location could not be saved.');
                        }

                        updateLocationDisplay(data.location);
                        pinnedLocation = null;
                        document.getElementById('pinned-location').textContent = 'Click the map to pin your location.';
                        setStatus('Location saved successfully.');

                        if (showPopup) {
                            Swal.fire({
                                title: 'Location saved',
                                text: 'Your latest GPS location has been recorded.',
                                icon: 'success',
                                timer: 1800,
                                showConfirmButton: false,
                            });
                        }
                    } catch (error) {
                        setStatus(error.message);

                        if (showPopup) {
                            Swal.fire({
                                title: 'Unable to save location',
                                text: error.message,
                                icon: 'error',
                            });
                        }
                    }
                }

                function startTracking() {
                    saveCurrentLocation(false);

                    if (trackingTimer) {
                        clearInterval(trackingTimer);
                    }

                    trackingTimer = setInterval(() => saveCurrentLocation(false), 30000);
                    setStatus('Auto tracking started. Updating every 30 seconds.');

                    Swal.fire({
                        title: 'Auto tracking started',
                        text: 'Your location will update every 30 seconds.',
                        icon: 'success',
                        timer: 1800,
                        showConfirmButton: false,
                    });
                }

                function stopTracking() {
                    if (trackingTimer) {
                        clearInterval(trackingTimer);
                        trackingTimer = null;
                    }

                    setStatus('Auto tracking stopped.');
                }

                function bindLocationButtons() {
                    document.getElementById('save-current-location')?.addEventListener('click', () => saveCurrentLocation(true));
                    document.getElementById('start-location-tracking')?.addEventListener('click', startTracking);
                    document.getElementById('stop-location-tracking')?.addEventListener('click', stopTracking);
                }

                document.addEventListener('DOMContentLoaded', () => {
                    initLocationMap();
                    bindLocationButtons();
                });

                document.addEventListener('livewire:navigated', () => {
                    initLocationMap();
                    bindLocationButtons();
                });

                initLocationMap();
                bindLocationButtons();
            })();
        </script>
    @endif
</div>
