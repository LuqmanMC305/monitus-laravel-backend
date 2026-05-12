<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Community;
use App\Models\MobileUser;

class CommunityController extends Controller
{
    public function join(Request $request) {
        $appUser = $request->user(); 
        $communityId = $request->community_id;

        // 1. Hop from AppUser (20) to MobileUser (7)
        $mobileUser = MobileUser::where('app_user_id', $appUser->app_user_id)->first();

        if (!$mobileUser) {
        return response()->json(['message' => 'Mobile profile not found'], 404);
        }

        // Attach the user to the community with a 'pending' status
        // syncWithoutDetaching prevents duplicate entries if they click twice
        $mobileUser->communities()->syncWithoutDetaching([
            $communityId => ['status' => 'pending', 'role' => 'resident']
        ]);

        return response()->json([
            'message' => 'Join request sent to Admin.',
            'status' => 'pending'
        ], 201);
    }

    public function index (Request $request)
    {
        // Display all communities list
        $appUser = $request->user();

        // Again, find the MobileUser ID first
        $mobileUser = MobileUser::where('app_user_id', $appUser->app_user_id)->first();
        $mobileId = $mobileUser ? $mobileUser->mobile_user_id : null;

        // We fetch all communities, but ONLY attach the pivot data for the logged-in user
        // This tells Flutter if the user is already 'pending' or 'approved'
        $communities = Community::with(['mobileUsers' => function ($query) use ($mobileId) {
            $query->where('community_user.mobile_user_id', $mobileId);
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $communities
        ]);


    }
}


