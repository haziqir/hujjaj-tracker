<?php

use Livewire\Volt\Component;

new class extends Component
{
    public function with(): array
    {
        $pilgrim = auth()->user()?->pilgrim()->with('hotel')->first();

        return [
            'pilgrim' => $pilgrim,
            'hotel' => $pilgrim?->hotel,
        ];
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Hotel Location</h2>
        <p class="mt-1 text-sm text-gray-500">View the hotel assigned to your pilgrim profile.</p>
    </header>

    @if ($hotel)
        <div class="mt-6 grid gap-6 lg:grid-cols-[360px_1fr]">
            <div class="rounded border border-gray-200 bg-white p-4">
                <h3 class="text-base font-semibold text-gray-900">{{ $hotel->hotel_name }}</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="font-medium text-gray-700">Address</dt>
                        <dd class="mt-1 text-gray-600">{{ $hotel->address }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700">Latitude</dt>
                        <dd class="mt-1 text-gray-600">{{ $hotel->latitude }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-700">Longitude</dt>
                        <dd class="mt-1 text-gray-600">{{ $hotel->longitude }}</dd>
                    </div>
                </dl>
            </div>

            <div wire:ignore id="my-hotel-map" class="h-[420px] w-full rounded border border-gray-200"></div>
        </div>
    @else
        <div class="mt-6 rounded border border-gray-200 bg-gray-50 p-6 text-gray-600">
            No hotel has been assigned to your pilgrim profile yet.
        </div>
    @endif
</section>

@if ($hotel)
    <script>
        (() => {
            const hotel = @js([
                'hotel_name' => $hotel->hotel_name,
                'address' => $hotel->address,
                'latitude' => (float) $hotel->latitude,
                'longitude' => (float) $hotel->longitude,
            ]);

            function initMyHotelMap() {
                const mapElement = document.getElementById('my-hotel-map');

                if (!mapElement || typeof L === 'undefined' || mapElement.dataset.ready) {
                    return;
                }

                mapElement.dataset.ready = 'true';

                const position = [hotel.latitude, hotel.longitude];
                const map = L.map(mapElement).setView(position, 16);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                L.marker(position)
                    .bindPopup(`<strong>${hotel.hotel_name}</strong><br>${hotel.address}`)
                    .addTo(map)
                    .openPopup();

                setTimeout(() => map.invalidateSize(), 100);
            }

            document.addEventListener('DOMContentLoaded', initMyHotelMap);
            document.addEventListener('livewire:navigated', initMyHotelMap);
            initMyHotelMap();
        })();
    </script>
@endif
