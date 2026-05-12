<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Incident Command Centre (Click on the Map to Compose Alerts)') }}
        </h2>
    </x-slot>

    <div class="py-12" 
        x-data="{ 
        open: false, 
        lat: '', 
        lng: '', 
        radius: 1000,
        showSuccess: false,
        showError: false,
        errorMessage: '',
        notifiedCount: 0 
        }"
        @open-modal.window="open = true; lat = $event.detail.lat; lng = $event.detail.lng"
        @alert-sent.window="showSuccess = true; notifiedCount = $event.detail.count; open = false"
        @alert-failed.window="showError = true; errorMessage = $event.detail.message">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div id="map" style="height: 600px; width: 100%; border-radius: 8px; z-index: 1;"></div> <!-- Map Container -->
                </div>
                <div>
                    <!-- Alert Table History -->
                    <h3 class="text-center text-lg font-bold mt-5">Recent Alerts</h3>
                    <table class="min-w-full divide-y divide-gray-200 mt-6">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Severity</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="alert-history-table">
                            @foreach($alerts as $alert)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4">{{ $alert->title }}</td>
                                <td class="px-6 py-4">
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
                                <td class="px-6 py-4">{{ $alert->created_at->diffForHumans()}}</td> 
                                <td class="px-6 py-4 text-right">
                                    <button
                                        onclick="focusMap({{ $alert->latitude }}, {{ $alert->longitude }}, '{{ addslashes($alert->title) }}')"
                                        class="bg-blue-600 hover:bg-blue-800 text-white text-xs py-1 px-3 rounded shadow-sm transition">
                                        Locate
                                    </button>
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

            <!-- Broadcast Model Pop-Up for Alert Fill-in Form -->
            <div x-show="open" 
                class="fixed inset-0 z-[9999] overflow-y-auto" 
                style="display: none;"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100">
                
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-gray-500 opacity-75"></div>

                    <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full p-6 relative z-10">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Broadcast New Alert</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Alert Title</label>
                                <input type="text" id="modal_title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., Road Accident">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Instructions</label>
                                <textarea id="modal_instruction" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500" placeholder="e.g., Use alternative routes..."></textarea>
                            </div>

                            <div>
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
                                Target Coordinates: <span x-text="lat"></span>, <span x-text="lng"></span>
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <button @click="open = false" type="button" class="bg-gray-200 px-4 py-2 rounded-md text-gray-700 hover:bg-gray-300">Cancel</button>

                            <button @click="sendAlert(lat, lng, radius)"
                                type="button" 
                                class="bg-red-600 px-4 py-2 rounded-md text-white hover:bg-red-700">
                                Confirm & Broadcast
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Success Toast (Pop-Up) -->
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
                    <p class="text-sm">Reached <span x-text="notifiedCount"></span> active users in the zone.</p>
                </div>
            </div>
            
            <!-- Failure Toast (Pop-Up) -->
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

    <!-- Leaflet Assets --> 
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Control Geocoder Assets --> 
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

    <script>
        // 1. Initialise the map centered on Penang
        const lat = 5.4164;
        const lng = 100.3301;
        const zoomVal = 13; 

        var map = L.map('map').setView([lat, lng], zoomVal);
        window.pendingMarker = null;
        window.pendingCircle = null;


        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        //  Initialise Historical Alert Rendering from DB onto Map
        @foreach($alerts as $alert)
            (function() {
                const hLat = {{ $alert->latitude }};
                const hLng = {{ $alert->longitude }};
                const hTitle = "{{ addslashes($alert->title) }}";
                const hSeverity = "{{ $alert->severity }}";
                const hColour = getSeverityColour(hSeverity); // Calls your helper function

                L.circle([hLat, hLng], {
                    color: hColour,
                    fillColor: hColour,
                    fillOpacity: 0.4,
                    radius: {{ $alert->radius ?? 1000 }}
                })
                .addTo(map)
                .bindPopup(`
                    <div style="font-family: sans-serif;">
                        <b style="font-size: 14px;">${hTitle}</b><br>
                        <span style="
                            display: inline-block; 
                            margin-top: 5px;
                            padding: 2px 8px; 
                            border-radius: 12px; 
                            background-color: ${hColour}; 
                            color: white; 
                            font-size: 10px; 
                            font-weight: bold;
                            text-transform: uppercase;">
                            Severity:
                            ${hSeverity}
                        </span>
                    </div>
                `);
            })();
        @endforeach

        // Initialise Search Bar 
        var geocoder = L.Control.geocoder({
            defaultMarkGeocode: false
        })
        .on('markgeocode', function(e) {
            var bbox = e.geocode.bbox;
            var poly = L.polygon([
            bbox.getSouthEast(),
            bbox.getNorthEast(),
            bbox.getNorthWest(),
            bbox.getSouthWest()
            ]);
            map.fitBounds(poly.getBounds()); // Zoom into the searched area
        })
        .addTo(map);

        // 2. Click to Alert Logic
        map.on('click', function(e){
        // 1. Manage Map Visuals
        if (window.pendingMarker) map.removeLayer(window.pendingMarker);
        if (window.pendingCircle) map.removeLayer(window.pendingCircle);

        // Grabs current value from Alpine.js slider
        const alpineElement = document.querySelector('[x-data]');

        // Add a default value of 1000, ensure radius is not NaN
        const currentRadius = Alpine.$data(alpineElement).radius || 1000;

        window.pendingMarker = L.marker(e.latlng).addTo(map);
        window.pendingCircle = L.circle(e.latlng, {
            color: '#667b99', // Grey slate colour for "Pending" status
            fillColor: '#94a3b8',
            fillOpacity: 0.4,
            radius: parseFloat(currentRadius)
        }).addTo(map);

        // 2. Open the Modal using the Event Dispatcher 
        window.dispatchEvent(new CustomEvent('open-modal', { 
            detail: { lat: e.latlng.lat, lng: e.latlng.lng } 
            }));
        });

        // Actual Broadcast Function
        function sendAlert(lat, lng, radius) {

            // Capture New Alert Details
            const freshTitle = document.getElementById('modal_title').value;
            const freshInstruction = document.getElementById('modal_instruction').value;
            const freshSeverity = document.getElementById('modal_severity').value;

            axios.post('/api/send-alert', {
                title: freshTitle,
                instruction: freshInstruction,
                severity:freshSeverity,
                latitude: lat,
                longitude: lng,
                radius: radius
            })
            .then(response => {
                // Change colour from "Pending Grey" to severity colour
                if (typeof pendingCircle !== 'undefined' && pendingCircle) {
                    const circleColor = getSeverityColour(freshSeverity);
                    
                    pendingCircle.setStyle({
                        color: circleColor,
                        fillColor: circleColor
                    });

                    pendingCircle.bindPopup(`
                        <b>${freshTitle}</b><br>
                        <span style="background-color: ${getSeverityColour(freshSeverity)}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold;">
                            ${freshSeverity}
                        </span>
                    `);

                    // Release the "pending" status so they aren't deleted on next click
                    window.pendingCircle = null;
                    window.pendingMarker = null;
                }
                // Get the table body by the ID that just created
                const tableBody = document.getElementById('alert-history-table');


                /* Prepare the new row HTML
                   Note: We use "Just now" because the server-side diffForHumans hasn't processed this row yet.
                */
                const colour = getSeverityColour(freshSeverity);

                const newRow = `
                        <tr class="border-b">
                            <td class="px-6 py-4">${freshTitle}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-0.5 rounded-full text-white text-xs font-bold uppercase tracking-wider" style="background-color: ${colour}">
                                    ${freshSeverity}
                                </span>
                            </td>
                            <td class="px-6 py-4">Just now</td>
                            <td class="px-6 py-4">
                                <button 
                                    onclick="focusMap(${lat}, ${lng}, '${freshTitle}')"
                                    class="bg-blue-600 hover:bg-blue-800 text-white text-xs py-1 px-3 rounded shadow-sm transition">
                                    Locate
                                </button>
                            </td>
                        </tr>
                    `;

                // Insert the row at the top (afterbegin)
                tableBody.insertAdjacentHTML('afterbegin', newRow);

                // Ensure it limits to only 10 rows
                if (tableBody.children.length > 10) {
                    tableBody.lastElementChild.remove(); // Removes the absolute last row in the body
                }
                
                // Update Alpine.js state for Toast
                const alpineData = Alpine.$data(document.querySelector('[x-data]')); // Get Alpine.js data object

                // Feed server response into Toast
                alpineData.notifiedCount = response.data.notified_count; 
                alpineData.showSuccess = true;

                // DISPATCH SUCCESS EVENT
                window.dispatchEvent(new CustomEvent('alert-sent', { 
                    detail: { count: response.data.notified_count } 
                }));

                // Clean up: Clear the inputs for the next click
                document.getElementById('modal_title').value = '';
                document.getElementById('modal_instruction').value = '';
                })
            .catch(error => {
                console.error("The alert could not be saved:", error);

                window.dispatchEvent(new CustomEvent('alert-failed', { 
                    detail: { 
                        message: error.response?.data?.message || "Internal Server Error. Please try again." 
                    } 
                }));

                // Get Alpine.Js data object
                const alpineData = Alpine.$data(document.querySelector('[x-data]'));

                // Set Error Details
                alpineData.errorMessage = error.response?.data?.message || "Internal Server Error. Please try again.";
                alpineData.showError = true;

                // Re-open the modal so the admin doesn't lose their text
                alpineData.open = true;
            });
        }
        
        // Focuses on Map When Click the Alert Button Inside Table
        window.focusMap = function(lat, lng, titleVal){
            console.log("Locate button clicked!");

            if (!map) {
                console.error("The map variable is not defined!");
                return;
            }
            // Creates a smooth zooming animation (.flyto)
            map.flyTo([lat, lng], 16,{
                animate:true,
                duration: 1.5 // in seconds
            });

            // Temporary marker or popup to show where it is exactly
            L.popup()
                .setLatLng([lat, lng])
                .setContent('<b style="color: #2563eb;">Incident:</b> ' + titleVal)
                .openOn(map);
                
        }

        function getSeverityColour(severity){
            switch(severity.toLowerCase()){
                case 'high': return '#ff0000'; // Red
                case 'medium': return '#ff8000'; // Orange
                case 'low': return '#facc15'; // Yellow
                default: return '#3b82f6'; // Blue
            }

        }
 
    </script>
</x-app-layout>