<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Active Alerts') }}
        </h2>
    </x-slot>

    <div class="py-12"
        x-data="{ 
        showSuccess: false, 
        showError: false, 
        errorMessage: '', 
        successMessage: '' 
        }"
        @alert-resolved.window="showSuccess = true; successMessage = $event.detail.message"
        @resolve-failed.window="showError = true; errorMessage = $event.detail.message">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Active Alerts Table -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold">Currently Active Incidents</h3>
                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                        {{ $activeAlerts->count() }} Live Alerts
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Incident</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location/Radius</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($activeAlerts as $alert)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-gray-900">{{ $alert->title }}</div>
                                    <div class="text-xs text-gray-500 truncate w-48">{{ $alert->instruction }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-white text-xs font-bold uppercase" 
                                          style="background-color: {{ 
                                          strtoupper($alert->severity) == 'HIGH' ? '#ef4444' : 
                                          (strtoupper($alert->severity) == 'MEDIUM' ? '#f59e0b' : '#facc15') 
                                          }}">
                                        {{ $alert->severity }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if($alert->area_type === 'radius')
                                        <span class="font-medium text-gray-700">📍 {{ round($alert->latitude, 4) }}, {{ round($alert->longitude, 4) }}</span>
                                        <div class="text-xs text-blue-600 font-semibold italic">Radius: {{ $alert->radius }}m</div>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 mb-1">
                                            📐 Polygon Shape
                                        </span>
                                        <div class="text-xs text-gray-400 italic">
                                            @if(!empty($alert->danger_zone_coordinates))
                                                {{ count($alert->danger_zone_coordinates) }} boundary vertices recorded
                                            @else
                                                No boundary coordinates defined
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $alert->created_at->diffForHumans() }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button onclick="resolveAlert({{ $alert->alert_id }})" 
                                            class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold rounded transition">
                                        Mark Resolved
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-gray-500 italic">
                                    No active alerts found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resolved Alerts Table -->
            <div div class="mt-12 bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg p-6 border border-gray-200">
                <div class="flex items-center mb-4 text-gray-600">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-semibold uppercase tracking-wider">Resolved Incident History</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 opacity-75">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Incident</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase">Resolved Time</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($resolvedAlerts as $history)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-600">
                                    <span class="font-medium">{{ $history->title }}</span>
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-400">
                                    {{ $history->updated_at->format('d M Y, H:i') }} 
                                    ({{ $history->updated_at->diffForHumans() }})
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-[10px] font-bold text-gray-400 border border-gray-300 px-2 py-0.5 rounded uppercase">
                                        {{ $history->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-gray-400 text-sm italic">
                                    No resolved history found yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Toast Layout for Delete Alert Success/Failure -->
        <div x-show="showSuccess" 
            x-transition 
            x-init="$watch('showSuccess', value => { if(value) setTimeout(() => { showSuccess = false; window.location.reload(); }, 2000) })"
            class="fixed bottom-5 right-5 z-[10000] bg-green-600 text-white p-4 rounded-lg shadow-2xl flex items-center space-x-3"
            style="display: none;">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <div>
                <p class="font-bold">Status Updated</p>
                <p class="text-sm" x-text="successMessage"></p>
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
                <p class="font-bold">Update Failed</p>
                <p class="text-sm" x-text="errorMessage"></p>
            </div>
        </div>
    </div>

    <!-- Axios logic to resolve alerts -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        function resolveAlert(id) {
            if (!confirm('Are you sure the incident is resolved? This will remove it from the public map.')) return;

            // Sends PATCH request
            axios.patch(`/api/alerts/${id}/resolve`)
                .then(response => {
                    // Dispatch event to show the Toast
                    window.dispatchEvent(new CustomEvent('alert-resolved', { 
                        detail: { message: response.data.message || 'Alert marked as resolved.' } 

                        // Note: The toast's x-init will handle the page reload after 3 seconds
                    }));
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.dispatchEvent(new CustomEvent('resolve-failed', { 
                        detail: { message: error.response?.data?.message || 'Failed to update alert status.' } 
                    }));
                });
        }
    </script>
</x-app-layout>