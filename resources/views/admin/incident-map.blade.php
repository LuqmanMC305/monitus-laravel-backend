<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Incident Command Centre (Select Draw Mode to Compose Alerts)') }}
        </h2>
    </x-slot>

    <div class="py-12" 
        x-data="{ 
        open: false, 
        area_type: 'radius',
        lat: '', 
        lng: '', 
        radius: 1000,
        polygon_coords: [],
        alert_category: 'flood',
        category_icon: '🌊',
        showSuccess: false,
        showError: false,
        errorMessage: '',
        notifiedCount: 0 
        }"
        @open-modal.window="
            open = true; 
            area_type = $event.detail.area_type;
            lat = $event.detail.lat || ''; 
            lng = $event.detail.lng || ''; 
            radius = $event.detail.radius || 1000;
            polygon_coords = $event.detail.polygon_coords || [];
        "
        @alert-sent.window="showSuccess = true; notifiedCount = $event.detail.count; open = false"
        @alert-failed.window="showError = true; errorMessage = $event.detail.message">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div id="map" style="height: 600px; width: 100%; border-radius: 8px; z-index: 1;"></div> 
                </div>

                <div>
                    <h3 class="text-center text-lg font-bold mt-5">Recent Alerts</h3>
                    <table class="min-w-full divide-y divide-gray-200 mt-6">
                        <thead>
                            <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="px-6 py-3">Title</th>
                                <th class="px-6 py-3">Type / Category</th>
                                <th class="px-6 py-3">Spatial Area Boundary</th>
                                <th class="px-6 py-3">Severity</th>
                                <th class="px-6 py-3">Time</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody id="alert-history-table" class="bg-white divide-y divide-gray-200">
                            @foreach($alerts as $alert)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $alert->title }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <span class="inline-flex items-center space-x-1">
                                        <span>{{ $alert->category_icon ?? '📢' }}</span>
                                        <span class="capitalize font-medium">{{ $alert->alert_category ?? 'General' }}</span>
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if(($alert->area_type ?? 'radius') === 'radius')
                                        <span class="font-medium text-gray-700">📍 {{ round($alert->latitude, 4) }}, {{ round($alert->longitude, 4) }}</span>
                                        <div class="text-xs text-blue-600 font-semibold italic">Radius: {{ $alert->radius }}m</div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mb-1">
                                            📐 Custom Polygon
                                        </span>
                                        <div class="text-xs text-gray-400 italic">
                                            @if(!empty($alert->danger_zone_coordinates))
                                                {{ count($alert->danger_zone_coordinates) }} vertices recorded
                                            @else
                                                No coordinates defined
                                            @endif
                                        </div>
                                    @endif
                                </td>

                                <td class="px-6 py-4 text-sm">
                                    @php
                                        $badgeColour = match(strtolower($alert->severity))
                                        {
                                            'high' => 'bg-red-500',
                                            'medium' => 'bg-amber-500',
                                            'low' => 'bg-yellow-400',
                                            'default' => 'bg-blue-500',
                                        }
                                    @endphp
                                    <span class="{{ $badgeColour }} px-2.5 py-0.5 rounded-full text-white text-xs font-bold uppercase tracking-wider">
                                        {{ $alert->severity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $alert->created_at->diffForHumans()}}</td> 
                                <td class="px-6 py-4 text-sm text-right">
                                    @if(($alert->area_type ?? 'radius') === 'radius')
                                        <button
                                            onclick="focusMap({{ $alert->latitude }}, {{ $alert->longitude }}, 'radius', null, '{{ addslashes($alert->title) }}')"
                                            class="bg-blue-600 hover:bg-blue-800 text-white text-xs py-1 px-3 rounded shadow-sm transition">
                                            Locate
                                        </button>
                                    @else
                                        <button
                                            onclick="focusMap(null, null, 'polygon', {{ json_encode($alert->danger_zone_coordinates) }}, '{{ addslashes($alert->title) }}')"
                                            class="bg-purple-600 hover:bg-purple-800 text-white text-xs py-1 px-3 rounded shadow-sm transition">
                                            Locate
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> 
                
                <div class="mt-10 bg-white p-6 rounded-lg shadow-md border-t-4 border-blue-600">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <span class="mr-2">📢</span> Official Community Announcement
                    </h3>
                    <p class="text-sm text-gray-600 mb-4 italic">
                        Use this to send non-emergency updates to specific Telegram groups.
                    </p>
                    
                    <form action="{{ route('admin.community.broadcast') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Target Community</label>
                                <select name="community_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @foreach($communities as $community)
                                        <option value="{{ $community->community_id }}">{{ $community->community_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Message Content</label>
                                <textarea name="message" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your announcement here..."></textarea>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md font-bold hover:bg-blue-700 transition shadow-lg">
                                Broadcast to Telegram
                            </button>
                        </div>
                    </form>
                </div>

            <div x-show="open" 
                class="fixed inset-0 z-[9999] overflow-y-auto" 
                style="display: none;"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">
                
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 opacity-75"></div>

                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6 relative z-10">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center justify-between">
                            <span>Broadcast New Alert</span>
                            <span class="text-xs font-bold uppercase px-2 py-0.5 rounded" :class="area_type === 'radius' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'" x-text="area_type"></span>
                        </h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Alert Title</label>
                                <input type="text" id="modal_title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., Severe Flash Flooding">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Instructions</label>
                                <textarea id="modal_instruction" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., Evacuate to higher ground immediately..."></textarea>
                            </div>

                            <!-- Alert Category & Emoji section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Alert Classification Category</label>
                                <select id="modal_category" x-model="alert_category" @change="
                                    const emojiMap = { flood: '🌊', weather: '⚡', fire: '🔥', health: '🚨', landslide: '⛰️', general: '📢' };
                                    category_icon = emojiMap[alert_category] || '📢';
                                " class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="flood">Flood Hazard (🌊)</option>
                                    <option value="weather">Extreme Weather (⚡)</option>
                                    <option value="fire">Infrastructure / Fire (🔥)</option>
                                    <option value="health">Medical / Emergency (🚨)</option>
                                    <option value="landslide">Landslide / Geospatial (⛰️)</option>
                                    <option value="general">General Broadcast (📢)</option>
                                </select>
                            </div>

                            <!-- Conditional Visibility for the Radius Slider -->
                            <div x-show="area_type === 'radius'">
                                <label class="block text-sm font-medium text-gray-700">
                                    Impact Radius: <span x-text="radius"></span>m
                                </label>
                                <input type="range" 
                                    x-model="radius" 
                                    min="100" max="5000" step="100"
                                    @input="if(window.pendingCircle) window.pendingCircle.setRadius(radius)"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-red-600">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Severity Level</label>
                                <select id="modal_severity" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                    <option value="LOW">Low</option>
                                    <option value="MEDIUM" selected>Medium</option>
                                    <option value="HIGH">High</option>
                                </select>
                            </div>

                            <div class="text-xs text-gray-500 italic">
                                <template x-if="area_type === 'radius'">
                                    <span>Target Coordinates: <span x-text="lat"></span>, <span x-text="lng"></span></span>
                                </template>
                                <template x-if="area_type === 'polygon'">
                                    <span>Captured Vector Boundary: <span x-text="polygon_coords.length"></span> vertices recorded</span>
                                </template>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button @click="clearPendingDrawings(); open = false" type="button" class="bg-gray-200 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-300">Cancel</button>

                            <button @click="sendAlert(area_type, lat, lng, radius, polygon_coords, alert_category, category_icon)"
                                type="button" 
                                class="bg-red-600 px-4 py-2 rounded-md text-white hover:bg-red-700">
                                Confirm & Broadcast
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-show="showSuccess" 
                 x-transition 
                 x-init="$watch('showSuccess', value => { if(value) setTimeout(() => showSuccess = false, 5000) })"
                 class="fixed bottom-5 right-5 z-[10000] bg-green-600 text-white p-4 rounded-lg shadow-2xl flex items-center space-x-3"
                 style="display: none;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <div>
                    <p class="font-bold">Broadcast Successful!</p>
                    <p class="text-sm">Notified target spatial network vectors successfully.</p>
                </div>
            </div>
            
            <div x-show="showError" 
                x-transition 
                x-init="$watch('showError', value => { if(value) setTimeout(() => showError = false, 5000) })"
                class="fixed bottom-5 right-5 z-[10000] bg-red-600 text-white p-4 rounded-lg shadow-2xl flex items-center space-x-3"
                style="display: none;">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <p class="font-bold">Broadcast Failed</p>
                    <p class="text-sm" x-text="errorMessage"></p>
                </div>
            </div>
        </div>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <!-- Leaflet Draw Plugin -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

    <script>
        // Initialize Core Map View Coordinates (Penang Island Base Cluster)
        const baseLat = 5.4164;
        const baseLng = 100.3301;
        const zoomVal = 13; 

        var map = L.map('map').setView([baseLat, baseLng], zoomVal);
        
        // Drawing Feature State Elements
        window.drawnItems = new L.FeatureGroup();
        map.addLayer(window.drawnItems);
        
        window.pendingLayer = null;

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // 5. UPDATED: Historical Map Layer Rendering (Handles Circle and Polygons)
        @foreach($alerts as $alert)
            (function() {
                const title = "{{ addslashes($alert->title) }}";
                const severity = "{{ $alert->severity }}";
                const color = getSeverityColour(severity);
                const icon = "{{ $alert->category_icon ?? '📢' }}";
                const cat = "{{ $alert->alert_category ?? 'General' }}";
                const areaType = "{{ $alert->area_type ?? 'radius' }}";


                let centralLatLng;
                let layer;

                if (areaType === 'radius') {
                    const lat = {{ $alert->latitude ?? 0.0 }};
                    const lng = {{ $alert->longitude ?? 0.0 }};
                    centerLatLng = L.latLng(lat, lng);

                    layer = L.circle(centerLatLng, {
                          color: color,
                          fillColor: color, 
                          fillOpacity: 0.35, 
                          radius: {{ $alert->radius ?? 1000 }}
                    });
                } else {
                    const rawCoords = {!! json_encode($alert->danger_zone_coordinates) !!};
                    if (rawCoords && rawCoords.length > 0) {
                        layer = L.polygon(rawCoords, {
                            color: color, fillColor: color, fillOpacity: 0.35
                        });
                        // 🟢 Automatically extracts the perfect centre bound vector of your polygon shape layout
                        centerLatLng = layer.getBounds().getCenter();
                    }
                }

                // Render shapes and icons to map layers safely
                if (layer) {
                    const popupContent = `
                        <div style="font-family: sans-serif; min-width:140px;">
                            <b style="font-size: 14px;">${icon} ${title}</b><br>
                            <span style="display:inline-block; margin-top:5px; padding:2px 8px; border-radius:12px; background-color:${color}; color:white; font-size:10px; font-weight:bold; text-transform:uppercase;">
                                ${cat} - ${severity}
                            </span>
                        </div>
                    `;

                    // A. Draw the primary underlying danger boundaries shape zone layer
                    layer.addTo(map).bindPopup(popupContent);

                    // NEW: Draw the transparent floating text emoji marker right over the center
                    if (centerLatLng) {
                        const textIconMarker = L.divIcon({
                            // Custom CSS styling framework sets the emoji center bounding alignments perfectly
                            html: `<div style="font-size: 24px; text-shadow: 0 2px 4px rgba(0,0,0,0.25); transform: translate(-2px, -4px);">${icon}</div>`,
                            className: 'custom-map-emoji-icon', // Clears Leaflet's default white pin background
                            iconSize: [30, 30],
                            iconAnchor: [15, 15] // Anchors precise geometric icon center coordinates points
                        });
                    

                    // Pin the standalone floating emoji right to the screen coordinate system map workspace
                    L.marker(centerLatLng, { icon: textIconMarker })
                    .addTo(map)
                    .bindPopup(popupContent); // Tapping directly on the emoji fires up the popup too!
                }
            }
        })();
        @endforeach

        // 6. NEW: Leaflet.draw Control Panel Interface Integration Configuration
        var drawControl = new L.Control.Draw({
            edit: { featureGroup: window.drawnItems, remove: false, edit: false },
            draw: {
                polygon: { allowIntersection: false, showArea: true, shapeOptions: { color: '#4b5563' } },
                circle: { shapeOptions: { color: '#4b5563' } },
                marker: false, polyline: false, rectangle: false, circlemarker: false
            }
        });
        map.addControl(drawControl);

        // Capture Completed Vectors Event Stream
        map.on(L.Draw.Event.CREATED, function (e) {
            clearPendingDrawings();

            var type = e.layerType;
            window.pendingLayer = e.layer;
            window.drawnItems.addLayer(window.pendingLayer);

            // 🖥️ DEBUG LINE 1: See what Leaflet natively identifies the shape as
            console.log("=== 1. Leaflet Draw Event Triggered ===");
            console.log("Native Leaflet Layer Type:", type);

            let mappedAreaType = (type === 'circle') ? 'radius' : 'polygon';

            console.log("Mapped Area Type for Laravel:", mappedAreaType);

            let modalDetails = { area_type: type };

            if (type === 'circle') {
                var center = window.pendingLayer.getLatLng();
                modalDetails.lat = center.lat;
                modalDetails.lng = center.lng;
                modalDetails.radius = Math.round(window.pendingLayer.getRadius());

                // 🖥️ DEBUG LINE 2: Verify the extracted circle values are real numbers
                console.log("Extracted Circle Values:", { lat: modalDetails.lat, lng: modalDetails.lng, radius: modalDetails.radius });

            } else if (type === 'polygon') {
                var latlngs = window.pendingLayer.getLatLngs()[0];
                // Map array parameters down to simple structure matches for Laravel controller
                modalDetails.polygon_coords = latlngs.map(ll => [ll.lat, ll.lng]);

                // 🖥️ DEBUG LINE 3: Verify the extracted polygon vertices array matrix
                console.log("Extracted Polygon Vertices:", modalDetails.polygon_coords);
            }

            // 🖥️ DEBUG LINE 4: Inspect the final data structure bundle forwarded to Alpine.js
            console.log("Dispatched Modal Details Payload Object:", modalDetails);
            console.log("=======================================");

            // Fire structural event dispatch update loop to open Alpine pop-up form UI
            window.dispatchEvent(new CustomEvent('open-modal', { detail: modalDetails }));
        });

        function clearPendingDrawings() {
            if (window.pendingLayer) {
                window.drawnItems.removeLayer(window.pendingLayer);
                window.pendingLayer = null;
            }
        }

        // Search Bar Setup
        L.Control.geocoder({ defaultMarkGeocode: false })
            .on('markgeocode', function(e) {
                map.fitBounds(L.polygon([
                    e.geocode.bbox.getSouthEast(), e.geocode.bbox.getNorthEast(),
                    e.geocode.bbox.getNorthWest(), e.geocode.bbox.getSouthWest()
                ]).getBounds());
            }).addTo(map);

        // 7. UPDATED: Multi-Format Dynamic Axios Broadcast Packet Payload Sender
        function sendAlert(areaType, lat, lng, radius, polygonCoords, category, icon) {
            const freshTitle = document.getElementById('modal_title').value;
            const freshInstruction = document.getElementById('modal_instruction').value;
            const freshSeverity = document.getElementById('modal_severity').value;

            // 🖥️ DEBUG LINE 5: Look at the arguments arriving from the Alpine modal action context
            console.log("=== 2. sendAlert Action Triggered ===");
            console.log("Arguments received from Alpine form context:", { areaType, lat, lng, radius, polygonCoords, category, icon });
            console.log(`lat: ${lat}`);

            // 🟢 REFACTOR: Accept both 'radius' or 'circle' to protect the data parsing matrix
            const isCircular = (areaType === 'radius' || areaType === 'circle');

            // Build the payload mapping configuration object explicit reference container
            const payload = {
                title: freshTitle,
                instruction: freshInstruction,
                severity: freshSeverity,

                area_type: isCircular ? 'radius' : 'polygon',

                alert_category: category,
                category_icon: icon,

                latitude: isCircular ? parseFloat(lat) : null,
                longitude:  isCircular ? parseFloat(lng) : null,
                radius: isCircular ? parseInt(radius) : null,

                danger_zone_coordinates: areaType === 'polygon' ? polygonCoords : null
            };


            // 🖥️ DEBUG LINE 6: This is the ultimate truth. Check if latitude/longitude are STILL null here!
            console.log("Final Sent Axios Packet Payload Data Array Matrix:", payload);
            console.log("=========================================");

            axios.post('/api/send-alert', payload)
                .then(response => {
                    if (window.pendingLayer) {
                        const finalColor = getSeverityColour(freshSeverity);
                        window.pendingLayer.setStyle({ color: finalColor, fillColor: finalColor });
                        window.pendingLayer.bindPopup(`<b>${icon} ${freshTitle}</b><br><span style="background-color:${finalColor}; color:white; padding:2px 8px; border-radius:12px; font-size:10px; font-weight:bold;">${freshSeverity}</span>`);
                        window.pendingLayer = null; // Detach pointer safeties
                    }

                    // Push new history row trace dynamically into list table view object
                    const tableBody = document.getElementById('alert-history-table');
                    const rowColor = getSeverityColour(freshSeverity);
                    
                    let areaString = areaType === 'radius' 
                        ? `📍 ${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)}<div class="text-xs text-blue-600 font-semibold italic">Radius: ${radius}m</div>`
                        : `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mb-1">📐 Custom Polygon</span><div class="text-xs text-gray-400 italic">${polygonCoords.length} vertices recorded</div>`;

                    const newRow = `
                        <tr class="border-b">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">${freshTitle}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">${icon} <span class="capitalize font-medium">${category}</span></td>
                            <td class="px-6 py-4 text-sm text-gray-500">${areaString}</td>
                            <td class="px-6 py-4 text-sm"><span class="px-2.5 py-0.5 rounded-full text-white text-xs font-bold uppercase tracking-wider" style="background-color:${rowColor}">${freshSeverity}</span></td>
                            <td class="px-6 py-4 text-sm text-gray-500">Just now</td>
                            <td class="px-6 py-4 text-sm text-right">
                                <button onclick="location.reload();" class="bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs py-1 px-3 rounded shadow-sm transition">🔄 Refresh</button>
                            </td>
                        </tr>`;

                    tableBody.insertAdjacentHTML('afterbegin', newRow);
                    if (tableBody.children.length > 10) tableBody.lastElementChild.remove();

                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                    alpineData.notifiedCount = response.data.notified_count || 0;
                    alpineData.showSuccess = true;

                    // Delay the page reload for 3 seconds so the user can read the success message
                    setTimeout(() => {
                        location.reload();
                    }, 3000); // 3000 milliseconds = 3 seconds

                    window.dispatchEvent(new CustomEvent('alert-sent', { detail: { count: response.data.notified_count } }));
                    document.getElementById('modal_title').value = '';
                    document.getElementById('modal_instruction').value = '';
                })
                .catch(error => {
                    console.error("The alert could not be saved:", error);
                    const msg = error.response?.data?.message || "Internal Server Error. Please try again.";
                    window.dispatchEvent(new CustomEvent('alert-failed', { detail: { message: msg } }));
                    
                    const alpineData = Alpine.$data(document.querySelector('[x-data]'));
                    alpineData.errorMessage = msg;
                    alpineData.showError = true;
                    alpineData.open = true;
                });
        }
        
        // Focuses and Flies smoothly into Map Vectors when Triggered via table lookups
        window.focusMap = function(lat, lng, mode, polyCoords, titleVal){
            if (!map) return;
            
            if (mode === 'radius') {
                map.flyTo([lat, lng], 15, { animate: true, duration: 1.2 });
                L.popup().setLatLng([lat, lng]).setContent(`<b>Incident Area:</b> ${titleVal}`).openOn(map);
            } else if (mode === 'polygon' && polyCoords) {
                const bounds = L.polygon(polyCoords).getBounds();
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15, animate: true, duration: 1.2 });
                L.popup().setLatLng(bounds.getCenter()).setContent(`<b>Polygon Zone:</b> ${titleVal}`).openOn(map);
            }
        }

        function getSeverityColour(severity){
            switch(severity.toLowerCase()){
                case 'high': return '#ff0000';
                case 'medium': return '#ff8000';
                case 'low': return '#facc15';
                default: return '#3b82f6';
            }
        }
    </script>
</x-app-layout>