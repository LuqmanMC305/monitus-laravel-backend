<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MobileUser;
use Illuminate\Http\Request;

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
    public function approve(int $userId, int $communityId)
    {
        $user = MobileUser::findOrFail($userId);
        
        // Change status to 'approved' so they can receive Telegram/FCM alerts
        $user->communities()->updateExistingPivot($communityId, [
            'status' => 'approved'
        ]);

        return back()->with('success', 'User approved for the community!');
    }

    public function reject(int $userId, int $communityId)
    {
        $user = MobileUser::findOrFail($userId);
    
        // Remove the request entirely if rejected
        $user->communities()->detach($communityId);

        return back()->with('success', 'Membership request rejected and removed.');
    }


}
