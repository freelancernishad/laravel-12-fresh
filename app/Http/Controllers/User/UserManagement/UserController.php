<?php

namespace App\Http\Controllers\User\UserManagement;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\User\UserResource;

class UserController extends Controller
{
    // Show own profile
    public function profile(Request $request)
    {
        $user = $request->user(); // Authenticated user
        return new UserResource($user);
    }

    // Update own profile
    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'notes' => 'nullable|string|max:1000',
            // other fields user can edit
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'isError' => true,
                'status_code' => 422
            ], 422);
        }

        $user->fill($request->only([
            'name', 'email', 'notes'
        ]));

        $user->save();

        return response()->json([
            'data' => new UserResource($user),
            'Message' => 'Profile updated successfully',
            'isError' => false,
            'status_code' => 200
        ]);
    }

    public function updateProfilePicture(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'photo_url' => 'required|url'
        ]);

        // Unset previous primary photo
        $user->photos()->where('is_primary', true)->update(['is_primary' => false]);

        // Create new photo and set as primary
        $photo = $user->photos()->create([
            'path' => $request->photo_url,
            'is_primary' => true,
        ]);

        // Refresh the user to update the appended attribute
        $user->refresh();

        return response()->json([
            'message' => 'Profile picture updated successfully',
            'profile_picture' => $user->profile_picture
        ]);
    }



}
