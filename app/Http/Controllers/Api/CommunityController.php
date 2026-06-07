<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Community;
use App\Models\MobileUser;
use App\Models\AppUser;
use Illuminate\Support\Facades\App;


/*

INTERACTS WITH FLUTTER, RETURNS JSON DATA

*/

class CommunityController extends Controller
{
    public function join(Request $request) 
    {
        // 1. Validate the incoming JSON request packet payload data
        $request->validate([
            'community_id' => 'required|exists:communities,community_id',
            'app_user_id'  => 'sometimes|integer',
            'user_id'      => 'required|integer'
        ]);

        $communityId = $request->community_id;

        // Safely falls back to look for 'app_user_id' or 'user_id' dynamically
        $targetUserId = $request->input('app_user_id', $request->user_id);

        // 1. Hop from AppUser  MobileUser 
        $mobileUser = MobileUser::where('app_user_id', $targetUserId)->first();

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


