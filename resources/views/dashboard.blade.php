<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Overview Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Alert Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                    <div class="text-sm text-gray-500 uppercase font-bold">Active Alerts</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $activeCount }}</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-green-500">
                    <div class="text-sm text-gray-500 uppercase font-bold">Resolved Total</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $resolvedCount }}</div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border-l-4 border-red-500">
                    <div class="text-sm text-gray-500 uppercase font-bold">High Severity (Live)</div>
                    <div class="text-3xl font-bold text-gray-800">{{ $highSeverity }}</div>
                </div>
            
            <!-- Alert Severity Chart -->
            </div>
             <div class="flex justify-center mt-6">
                    <div class="bg-white p-6 rounded-lg shadow text-center">
                        <h3 class="text-lg font-bold mb-4">Severity Breakdown</h3>
                        <canvas id="severityChart"></canvas>
                    </div>
            </div>
        </div>
    </div>

    <!-- Alert Severity Chart using Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('severityChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['High', 'Medium', 'Low'],
                datasets: [{
                    data: [{{ $highAlerts }}, {{ $medAlerts }}, {{ $lowAlerts }}],
                    backgroundColor: ['#ef4444', '#f59e0b', '#facc15'], // Red, Orange, Yellow
                }]
            }
        });
    </script>


</x-app-layout>
