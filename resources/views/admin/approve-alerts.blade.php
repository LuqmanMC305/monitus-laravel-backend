<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Community Crowd-Sourced Alert Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if($pendingReports->isEmpty())
                    <div class="text-center py-8 text-gray-500">
                        <span class="text-4xl block mb-2">🎉</span>
                        {{ __('No pending community reports requiring verification.') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Reporter') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Incident Details') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Location') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Evidence') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($pendingReports as $report)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ $report->appUser->app_user_name ?? 'Citizen' }}</div>
                                            <div class="text-xs text-gray-500">{{ $report->created_at->diffForHumans() }}</div>
                                        </td>

                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-md break-words">
                                                {{ $report->incident_description }}
                                            </div>
                                            <div class="mt-1 text-xs text-blue-600 font-semibold">
                                                AI Clean Pass Assurance Score: {{ number_format($report->llm_spam_score, 2) }}
                                            </div>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="bg-gray-100 text-gray-800 text-xs font-semibold px-2.5 py-0.5 rounded border border-gray-300 block w-max">
                                                📍 {{ number_format($report->latitude, 4) }}, {{ number_format($report->longitude, 4) }}
                                            </span>
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($report->image_path)
                                                <a href="{{ asset('storage/' . $report->image_path) }}" target="_blank">
                                                    <img src="{{ asset('storage/' . $report->image_path) }}" alt="Evidence" class="w-16 h-16 object-cover rounded-lg border border-gray-200 hover:scale-105 transition-transform duration-200 shadow-sm">
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">{{ __('No Image Provided') }}</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <form action="{{ route('admin.reports.approve', $report->id) }}" method="POST" onsubmit="return confirm('Promote this community report to an official active warning zone?');">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-sm">
                                                    🚀 {{ __('Approve & Broadcast') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>