<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Community Membership Approvals') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                <!-- Feedback to user after completed action (clicking approve button) -->
                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded shadow-sm">
                        {{ session('success') }}
                    </div>
                @endif
                
                <h3 class="text-lg font-medium text-gray-900 mb-4">Pending Requests</h3>

                <!-- Add community section will be on create-community.blade -->
                <a href="{{ route('admin.create-community') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring focus:ring-indigo-300 disabled:opacity-25 transition">
                    ➕ Create New Community
                </a>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Community</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested At</th>
                            <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingRequests as $request)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $request->appUser->app_user_name ?? 'Unknown User' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $request->communities->first()?->community_name ?? 'Unknown Community' }}</td> 
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $request->communities->first()?->pivot->created_at?->format('d M Y, H:i') ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                                    <form action="{{ route('admin.community-approvals.approve', [
                                        'user' => $request->mobile_user_id, 
                                        'community' => $request->communities->first()->community_id]) 
                                    }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md transition ease-in-out duration-150 mr-2">
                                            Approve
                                        </button>
                                    </form>

                                    <button class="bg-gray-100 hover:bg-red-100 text-red-600 px-3 py-1 rounded-md transition duration-150">
                                        Reject
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($pendingRequests->isEmpty())
                    <p class="text-gray-500 text-sm mt-4">No pending requests at the moment.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
