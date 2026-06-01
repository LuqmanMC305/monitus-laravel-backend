<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\CommunityUser;
use App\Models\IncidentReport;
use App\Models\Alert;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Shares database status counts with your main navigation views seamlessly
        View::composer(
            ['layouts.app', // resources/views/layouts/app.blade.php
            'navigation-menu', // resources/views/navigation-menu.blade.php
            ], function ($view) {
            $view->with([
                // 1. Count pending community membership requests
                'pendingCommunityCount' => CommunityUser::where('status', 'pending')->count(),
                
                // 2. Count incoming reports that passed the spam filter but wait for mobile user broadcast approval
                'pendingAlertCount' => IncidentReport::where('status', 'pending')->count(),

                // 3. Count incoming active that passed for admin resolve
                'activeAlertCount' => Alert::where('status', 'active')->count(),
            ]);
        });
    }
}
