<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ __('Monitus Admin Console') }}</title>

        <link rel="icon" type="image/png" href="{{ asset('images/monitus-logo-round.png') }}" alt="Monitus Logo">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts

         <!-- Dynamic Tab Pending Alerts -->
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // 1. Safely inject three distinct backend metrics as integers
                const pendingCommunity = {{ $pendingCommunityCount ?? 0 }};
                const pendingAlerts    = {{ $pendingAlertCount ?? 0 }};
                const activeAlerts     = {{ $activeAlertCount ?? 0 }};
                
                // 2. Sum them up to calculate the global operational action total
                const totalPendingAlerts = pendingCommunity + pendingAlerts + activeAlerts;
                
                // 3. Keep a pristine reference to your branding title string
                const baseTitle = document.title; 

                // 4. Update the visual tab text layout conditionally
                if (totalPendingAlerts > 0) {
                    document.title = `(${totalPendingAlerts}) ${baseTitle}`;
                }
            });
        </script>
    </body>

</html>
