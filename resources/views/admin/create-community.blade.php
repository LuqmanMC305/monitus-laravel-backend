<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create New Community Sector') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900">Community Registration</h3>
                    <p class="text-sm text-gray-500 mt-1">Register a new zone area, define its location coordinates, and establish its official emergency communication link.</p>
                </div>

                <form action="#" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <label for="community_name" class="block text-sm font-medium text-gray-700">Community Name</label>
                        <input type="text" name="community_name" id="community_name" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="e.g., Bayan Lepas Smart Sector">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description / Area Boundary Details</label>
                        <textarea name="description" id="description" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Describe coverage parameters, boundaries, or general response instructions..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700">Latitude Location Anchor</label>
                            <input type="text" name="latitude" id="latitude" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="e.g., 5.2951">
                        </div>

                        <div>
                            <label for="longitude" class="block text-sm font-medium text-gray-700">Longitude Location Anchor</label>
                            <input type="text" name="longitude" id="longitude" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="e.g., 100.2659">
                        </div>
                    </div>

                    <div>
                        <label for="telegram_link" class="block text-sm font-medium text-gray-700 flex items-center">
                            <span>Telegram Channel Link</span>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">New Sync Feature</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">🔗</span>
                            </div>
                            <input type="url" name="telegram_link" id="telegram_link" required
                                class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="https://t.me/your_community_channel_slug">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">This link will automatically transform the user button in the Flutter application upon their official entry approval.</p>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                        <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </button>
                        <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Save Community Zone
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</x-app-layout>