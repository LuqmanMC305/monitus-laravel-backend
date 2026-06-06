<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileUser;
use App\Services\FCMService;
use Illuminate\Http\Request;
use App\Models\AppUser;
use App\Models\CommunityUser;

/*

WEB DASHBOARD

*/
class CommunityApprovalController extends Controller
{
    // This function shows the "Community Approvals" page on your dashboard
    public function index()
    {
        // Fetch users who have a 'pending' status in the community_user pivot table
        $pendingRequests = MobileUser::whereHas('communities', function ($query) {
            // This part filters the list to only show users with pending requests
            $query->where('community_user.status', 'pending');
        })->with(['appUser','communities' => function ($query) {
            // This part ensures that only the 'pending' relationship data is 
            // attached to the pivot object for the Blade file to read
            $query->wherePivot('status', 'pending');
        }])->get();

        // Returns the Blade file we discussed earlier
        return view('admin.community-approvals', compact('pendingRequests'));
    }

    // This function runs when you click the "Approve" button on the website
    public function approve(int $userId, int $communityId, FCMService $fcmService)
    {
        $user = MobileUser::findOrFail($userId);
        
        // Change status to 'approved' so they can receive Telegram/FCM alerts
        $user->communities()->updateExistingPivot($communityId, [
            'status' => 'approved'
        ]);

        // Fire real-time FCM to specific user
        try
        {

            if (!empty($user->fcm_token)) {
                $fcmService->sendEmergencyAlert(
                    [$user->fcm_token],
                    "Community Update",
                    "Your request to join the community has been approved!",
                    [
                        'type'         => 'COMMUNITY_STATUS_UPDATE',
                        'community_id' => (string)$communityId,
                        'status'       => 'approved'
                    ]
                );
            }
        } 
        catch (\Exception $e)
        {
            info("FCM Community Approval broadcast failed safely: " . $e->getMessage());
        }

        $userName = $user->appUser->app_user_name;
        return back()->with('success', "$userName approved for the community!");
    }

    public function reject(int $userId, int $communityId)
    {
        $user = MobileUser::findOrFail($userId);
    
        // Remove the request entirely if rejected
        $user->communities()->detach($communityId);

        return back()->with('success', 'Membership request rejected and removed.');
    }

    // displays form view created
    public function create()
    {
        return view('admin.create-community');
    }

    // This will handle saving the data when the admin clicks submit later
    public function store(Request $request)
    {
        // Later on, you will add validation and Community::create([...]) here
        return redirect()->route('admin.community-approvals')
            ->with('success', 'Community created successfully!');
    }

    // FOR DEMO PURPOSES ONLY TO DELETE USER 'LUQMAN' (mobile_user_id of 7) FROM COMMUNITY
    public function delete()
    {
       $exists = CommunityUser::where('mobile_user_id', 7)->exists();
            
       if (!$exists) return back()->with('error','Record not found.');

       CommunityUser::where('mobile_user_id', 7)->delete();
            
      return back()->with('success', 'Community user deleted.');
    }
}
