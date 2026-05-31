<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center space-x-2">
                <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                <span>{{ __('Monitus Emergency Command Console') }}</span>
            </h2>
            <span class="text-xs font-mono bg-gray-100 text-gray-600 px-3 py-1 rounded-full border">
                System Status: Operational
            </span>
        </div>
    </x-slot>

    <div class="py-6 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 border-l-4 border-blue-500">
                    <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">Active Alerts</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ $activeCount }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 border-l-4 border-red-500">
                    <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">High Severity (Live)</div>
                    <div class="text-2xl font-bold text-red-600 mt-1">{{ $highSeverity }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 border-l-4 border-green-500">
                    <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">Resolved Total</div>
                    <div class="text-2xl font-bold text-gray-800 mt-1">{{ $resolvedCount }}</div>
                </div>
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 border-l-4 border-purple-500">
                    <div class="text-xs text-gray-400 uppercase font-bold tracking-wider">AI Spam Blocked</div>
                    <div class="text-2xl font-bold text-purple-600 mt-1">{{ $spamBlockedCount ?? 0 }}</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center justify-between">
                            <span>Severity Breakdown</span>
                            <span class="text-xs font-normal normal-case text-gray-400">Current Distribution</span>
                        </h3>
                        <div class="relative w-full mx-auto" style="max-width: 200px; height: 200px;">
                            <canvas id="severityChart"></canvas>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-2 pt-4 mt-4 border-t border-gray-100 text-center text-xs">
                        <div>
                            <span class="block w-2 h-2 rounded-full bg-red-500 mx-auto mb-1"></span>
                            <div class="text-gray-400 font-medium">High</div>
                            <div class="font-bold text-gray-700">{{ $highAlerts }}</div>
                        </div>
                        <div>
                            <span class="block w-2 h-2 rounded-full bg-amber-500 mx-auto mb-1"></span>
                            <div class="text-gray-400 font-medium">Med</div>
                            <div class="font-bold text-gray-700">{{ $medAlerts }}</div>
                        </div>
                        <div>
                            <span class="block w-2 h-2 rounded-full bg-yellow-400 mx-auto mb-1"></span>
                            <div class="text-gray-400 font-medium">Low</div>
                            <div class="font-bold text-gray-700">{{ $lowAlerts }}</div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 bg-white p-5 rounded-xl shadow-sm border border-gray-200 flex flex-col">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center justify-between">
                        <span>Live AI Triage & Broadcast Stream</span>
                        <span class="flex items-center space-x-1 text-xs text-green-500 font-semibold bg-green-50 px-2 py-0.5 rounded">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                            <span>Listening to API</span>
                        </span>
                    </h3>
                    
                    <div class="flex-1 overflow-y-auto max-h-[260px] pr-2 space-y-3 font-sans text-sm">
                        <div class="flex items-start space-x-3 p-3 bg-purple-50/50 rounded-lg border border-purple-100">
                            <div class="bg-purple-500 text-white p-1.5 rounded-md text-xs font-mono font-bold">AI</div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-purple-900">Spam Report Terminated</span>
                                    <span class="text-xs text-gray-400 font-mono">Just Now</span>
                                </div>
                                <p class="text-xs text-purple-700 mt-0.5">Report #38 labeled as keyboard smash gibberish ("test 123"). Confidence: 0.99. Auto-rejected safely.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 p-3 bg-blue-50/50 rounded-lg border border-blue-100">
                            <div class="bg-blue-500 text-white p-1.5 rounded-md text-xs font-mono font-bold">AI</div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-blue-900">Valid Incident Captured</span>
                                    <span class="text-xs text-gray-400 font-mono">3 mins ago</span>
                                </div>
                                <p class="text-xs text-blue-700 mt-0.5">"Fire near Sekolah Kebangsaan" analyzed successfully. Assurance score: 0.98. Sent to Alert Approvals.</p>
                            </div>
                        </div>

                        <div class="flex items-start space-x-3 p-3 bg-green-50/50 rounded-lg border border-green-100">
                            <div class="bg-green-500 text-white p-1.5 rounded-md text-xs"><i class="fa-solid fa-paper-plane"></i>📢</div>
                            <div class="flex-1">
                                <div class="flex justify-between items-center">
                                    <span class="font-semibold text-green-900">Multi-Channel Telegram Broadcast Sent</span>
                                    <span class="text-xs text-gray-400 font-mono">12 mins ago</span>
                                </div>
                                <p class="text-xs text-green-700 mt-0.5">Alert ID #14 mirrored cleanly into local community Telegram chat rooms targeting 2 matching local sectors.</p>
                            </div>
                        </div>                       
                    </div>
                </div>
            </div>

             <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-200 mt-6">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wide mb-4 flex items-center justify-between">
                    <span>Alert Despatch Volume (Last 14 Days)</span>
                    <span class="text-xs font-normal normal-case text-gray-400">Chronological Trend Monitoring</span>
                </h3>
                <div class="relative w-full h-[220px]">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('severityChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['High', 'Medium', 'Low'],
                    datasets: [{
                        data: [{{ $highAlerts }}, {{ $medAlerts }}, {{ $lowAlerts }}],
                        backgroundColor: ['#ef4444', '#f59e0b', '#facc15'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allows exact custom bounding layout styling
                    plugins: {
                        legend: {
                            display: false // Turned off because we designed our own custom responsive tracking rows below the canvas!
                        }
                    }
                }
            });

            // 14-Day Timeline Trend Line Chart Configuration
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    // Injected securely from your Laravel array controller compilation wrapper
                    labels: {!! json_encode($labels) !!}, 
                    datasets: [{
                        label: 'Alerts Broadcasted',
                        data: {!! json_encode($data) !!},
                        borderColor: '#3b82f6',         // Premium Tailwind Blue line vector color
                        backgroundColor: 'rgba(59, 130, 246, 0.04)', // Elegant transparent area underfill tint
                        borderWidth: 3,
                        tension: 0.3,                   // Gives a premium curved smooth line instead of sharp angles
                        pointBackgroundColor: '#3b82f6',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true                      // Enables the gradient area overlay underfill
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // Hidden because the header title card handles identification clarity
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0 // Enforces whole integer increments (e.g. 1, 2 alerts, never 1.5)
                            },
                            grid: {
                                color: '#f3f4f6' // Subtle light grey horizontal horizontal separator rules
                            }
                        },
                        x: {
                            grid: {
                                display: false // Hides vertical mesh gridlines for a minimalist structural appearance
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
