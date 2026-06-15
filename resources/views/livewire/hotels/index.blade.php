<?php

use App\Models\Hotel;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Validate('required|string|max:255')]
    public string $hotel_name = '';

    #[Validate('required|numeric|between:-90,90')]
    public string $latitude = '';

    #[Validate('required|numeric|between:-180,180')]
    public string $longitude = '';

    #[Validate('required|string|max:1000')]
    public string $address = '';

    public ?Hotel $editingHotel = null;

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingHotel) {
            $this->editingHotel->update($validated);
            $message = 'Hotel updated successfully!';
        } else {
            Hotel::create($validated);
            $message = 'Hotel created successfully!';
        }

        $this->resetForm();
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Success!',
            'text' => $message,
            'icon' => 'success',
        ]);

        $this->dispatch('hotels-map-refresh', hotels: $this->mapHotels());
    }

    public function edit(Hotel $hotel): void
    {
        $this->editingHotel = $hotel;
        $this->hotel_name = $hotel->hotel_name;
        $this->latitude = (string) $hotel->latitude;
        $this->longitude = (string) $hotel->longitude;
        $this->address = $hotel->address;
    }

    public function cancelEdit(): void
    {
        $this->resetForm();
    }

    public function delete(Hotel $hotel): void
    {
        $hotel->delete();
        $this->resetForm();
        $this->resetPage();

        $this->dispatch('swal', [
            'title' => 'Deleted!',
            'text' => 'Hotel deleted successfully!',
            'icon' => 'success',
        ]);

        $this->dispatch('hotels-map-refresh', hotels: $this->mapHotels());
    }

    public function viewOnMap(Hotel $hotel): void
    {
        $this->dispatch('hotel-map-focus', hotel: [
            'id' => $hotel->id,
            'hotel_name' => $hotel->hotel_name,
            'address' => $hotel->address,
            'latitude' => (float) $hotel->latitude,
            'longitude' => (float) $hotel->longitude,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingHotel = null;
        $this->reset('hotel_name', 'latitude', 'longitude', 'address');
        $this->resetValidation();
    }

    private function mapHotels(): array
    {
        return Hotel::orderBy('id')
            ->get(['id', 'hotel_name', 'address', 'latitude', 'longitude'])
            ->map(fn (Hotel $hotel) => [
                'id' => $hotel->id,
                'hotel_name' => $hotel->hotel_name,
                'address' => $hotel->address,
                'latitude' => (float) $hotel->latitude,
                'longitude' => (float) $hotel->longitude,
            ])
            ->values()
            ->all();
    }

    public function with(): array
    {
        return [
            'hotels' => Hotel::withCount('pilgrims')->orderBy('id')->paginate(10),
            'mapHotels' => $this->mapHotels(),
        ];
    }
}; ?>

<section>
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-lg font-medium text-gray-900">Hotel Management</h2>
            <p class="mt-1 text-sm text-gray-500">Create hotel records and view their locations on the map.</p>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[420px_1fr]">
        <form wire:submit="save" class="space-y-4 rounded border border-gray-200 bg-white p-4">
            <h3 class="text-base font-semibold text-gray-900">{{ $editingHotel ? 'Update Hotel' : 'Create Hotel' }}</h3>

            <div>
                <x-input-label for="hotel_name" value="Hotel Name" />
                <div class="mt-1 flex gap-2">
                    <x-text-input wire:model="hotel_name" id="hotel_name" class="w-full" placeholder="Example: Al Kiswah Hotel" />
                    <x-secondary-button type="button" x-on:click="lookupHotelLocation($wire)">
                        Find Location
                    </x-secondary-button>
                </div>
                <x-input-error :messages="$errors->get('hotel_name')" class="mt-2" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="latitude" value="Latitude" />
                    <x-text-input wire:model="latitude" id="latitude" class="mt-1 w-full" placeholder="21.4224" />
                    <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="longitude" value="Longitude" />
                    <x-text-input wire:model="longitude" id="longitude" class="mt-1 w-full" placeholder="39.8262" />
                    <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
                </div>
            </div>

            <div>
                <x-input-label for="address" value="Address" />
                <textarea wire:model="address" id="address" rows="4" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                <x-input-error :messages="$errors->get('address')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3">
                @if ($editingHotel)
                    <x-secondary-button type="button" wire:click="cancelEdit">Cancel</x-secondary-button>
                @endif

                <x-primary-button>{{ $editingHotel ? 'Update' : 'Save' }}</x-primary-button>
            </div>
        </form>

        <div class="rounded border border-gray-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Hotel Location Map</h3>
                <span class="text-sm text-gray-500">{{ count($mapHotels) }} hotels</span>
            </div>

            <div wire:ignore id="hotels-map" class="h-[360px] w-full rounded border border-gray-200"></div>
        </div>
    </div>

    <div class="mt-8 overflow-x-auto">
        <table class="w-full text-left border-collapse border border-gray-300">
            <thead>
                <tr class="bg-gray-50">
                    <th class="p-2 border border-gray-300">No.</th>
                    <th class="p-2 border border-gray-300">Hotel Name</th>
                    <th class="p-2 border border-gray-300">Latitude</th>
                    <th class="p-2 border border-gray-300">Longitude</th>
                    <th class="p-2 border border-gray-300">Address</th>
                    <th class="p-2 border border-gray-300">Pilgrims</th>
                    <th class="p-2 border border-gray-300">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hotels as $hotel)
                    <tr>
                        <td class="p-2 border border-gray-300">{{ $hotels->firstItem() + $loop->index }}</td>
                        <td class="p-2 border border-gray-300">{{ $hotel->hotel_name }}</td>
                        <td class="p-2 border border-gray-300">{{ $hotel->latitude }}</td>
                        <td class="p-2 border border-gray-300">{{ $hotel->longitude }}</td>
                        <td class="p-2 border border-gray-300">{{ $hotel->address }}</td>
                        <td class="p-2 border border-gray-300">{{ $hotel->pilgrims_count }}</td>
                        <td class="p-2 border border-gray-300">
                            <div class="flex gap-2">
                                <a wire:click="viewOnMap({{ $hotel->id }})" class="text-emerald-600 hover:text-emerald-800" title="View on map">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                </a>
                                <a wire:click="edit({{ $hotel->id }})" class="text-blue-600 hover:text-blue-800" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                    </svg>
                                </a>
                                <a wire:click="delete({{ $hotel->id }})" wire:confirm="Are you sure you want to delete this hotel?" class="text-red-600 hover:text-red-800" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-4 text-center text-gray-500 border border-gray-300">
                            No hotels found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex justify-end">
        {{ $hotels->links('vendor.pagination.custom') }}
    </div>
</section>

<script>
    (() => {
        const initialHotels = @js($mapHotels);
        let map = null;
        let markerLayer = null;
        let draftMarker = null;

        const defaultCenter = [21.4225, 39.8262];

        function initHotelsMap() {
            const mapElement = document.getElementById('hotels-map');

            if (!mapElement || typeof L === 'undefined') {
                return;
            }

            if (!map) {
                map = L.map(mapElement).setView(defaultCenter, 13);

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                markerLayer = L.layerGroup().addTo(map);
            }

            renderHotelMarkers(initialHotels);
            setTimeout(() => map.invalidateSize(), 100);
        }

        function renderHotelMarkers(hotels) {
            if (!map || !markerLayer) {
                return;
            }

            markerLayer.clearLayers();

            if (!hotels.length) {
                map.setView(defaultCenter, 13);
                return;
            }

            const bounds = [];

            hotels.forEach((hotel) => {
                const position = [hotel.latitude, hotel.longitude];
                bounds.push(position);

                L.marker(position)
                    .bindPopup(`<strong>${hotel.hotel_name}</strong><br>${hotel.address}`)
                    .addTo(markerLayer);
            });

            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
        }

        window.focusHotelDraftLocation = function (hotel) {
            if (!map || !hotel) {
                return;
            }

            const position = [hotel.latitude, hotel.longitude];

            if (draftMarker) {
                draftMarker.remove();
            }

            draftMarker = L.marker(position)
                .bindPopup(`<strong>${hotel.hotel_name}</strong><br>${hotel.address}`)
                .addTo(map)
                .openPopup();

            map.setView(position, 17);
        };

        window.lookupHotelLocation = async function (component) {
            const hotelName = component.get('hotel_name')?.trim();

            if (!hotelName) {
                Swal.fire({
                    title: 'Hotel name required',
                    text: 'Please enter a hotel name before searching.',
                    icon: 'warning',
                });
                return;
            }

            const query = `${hotelName} hotel Makkah Saudi Arabia`;
            const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=1&q=${encodeURIComponent(query)}`;

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    throw new Error('Location search failed.');
                }

                const results = await response.json();
                const location = results[0];

                if (!location) {
                    Swal.fire({
                        title: 'Location not found',
                        text: 'Try a more specific hotel name or include nearby area details.',
                        icon: 'error',
                    });
                    return;
                }

                const hotel = {
                    hotel_name: hotelName,
                    address: location.display_name,
                    latitude: Number.parseFloat(location.lat),
                    longitude: Number.parseFloat(location.lon),
                };

                component.set('latitude', hotel.latitude.toFixed(8));
                component.set('longitude', hotel.longitude.toFixed(8));
                component.set('address', hotel.address);

                window.focusHotelDraftLocation(hotel);

                Swal.fire({
                    title: 'Location found',
                    text: 'Latitude, longitude and address have been filled.',
                    icon: 'success',
                    timer: 1800,
                    showConfirmButton: false,
                });
            } catch (error) {
                Swal.fire({
                    title: 'Unable to search location',
                    text: 'Please check your connection or enter the coordinates manually.',
                    icon: 'error',
                });
            }
        };

        document.addEventListener('livewire:navigated', initHotelsMap);
        document.addEventListener('DOMContentLoaded', initHotelsMap);

        document.addEventListener('livewire:init', () => {
            Livewire.on('hotels-map-refresh', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;
                renderHotelMarkers(payload.hotels ?? []);
            });

            Livewire.on('hotel-map-focus', (event) => {
                const payload = Array.isArray(event) ? event[0] : event;
                const hotel = payload.hotel;

                if (!map || !hotel) {
                    return;
                }

                const position = [hotel.latitude, hotel.longitude];
                map.setView(position, 17);

                L.popup()
                    .setLatLng(position)
                    .setContent(`<strong>${hotel.hotel_name}</strong><br>${hotel.address}`)
                    .openOn(map);
            });
        });
    })();
</script>
