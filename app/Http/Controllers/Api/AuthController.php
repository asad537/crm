<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Sync Firebase User with CRM Users table
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function syncFirebaseUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'firebase_uid' => 'required|string',
                'email' => 'nullable|email',
                'name' => 'nullable|string',
                'phone' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $firebaseUid = $request->input('firebase_uid');
            $email = $request->input('email');
            $name = $request->input('name');
            $phone = $request->input('phone');

            // Find user by firebase_uid or email
            $user = User::where('firebase_uid', $firebaseUid)->first();

            if (!$user && $email) {
                // Check if user exists with this email but without firebase_uid
                $user = User::where('email', $email)->first();
            }

            if ($user) {
                // Update existing user
                $user->firebase_uid = $firebaseUid;
                if ($name) $user->name = $name;
                if ($phone) $user->phone = $phone;
                if ($email && $user->email !== $email) {
                    // Make sure the email isn't already taken by another user
                    $emailExists = User::where('email', $email)->where('id', '!=', $user->id)->exists();
                    if (!$emailExists) {
                        $user->email = $email;
                    }
                }
                $user->save();
            } else {
                // Create new user
                $user = new User([
                    'firebase_uid' => $firebaseUid,
                    'email' => $email ?? $firebaseUid . '@firebase.local', // Requires email
                    'name' => $name ?? 'App User',
                    'phone' => $phone,
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)) // Random password
                ]);
                $user->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'User synced successfully',
                'user' => $user
            ], 200);

        } catch (\Throwable $e) {
            Log::error('AuthController@syncFirebaseUser Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete Firebase User from CRM
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFirebaseUser(Request $request)
    {
        try {
            $firebaseUid = $request->input('firebase_uid');
            if (!$firebaseUid) {
                return response()->json(['success' => false, 'message' => 'Firebase UID required'], 400);
            }

            $user = User::where('firebase_uid', $firebaseUid)->first();

            if ($user) {
                // Note: Decide whether to delete the user or soft delete.
                // Depending on foreign keys in 'production_orders' and 'custom_projects'
                // you might want to either set user_id to null on those, or just empty the user fields.
                // For now, we will empty the PII fields to keep data integrity but "delete" their account logic.
                
                // OR delete fully:
                // \App\CustomProject::where('user_id', $user->id)->update(['user_id' => null]);
                // \App\ProductionOrder::where('user_id', $user->id)->update(['user_id' => null]);
                // $user->delete();

                // Safer alternative: Delete user fully, let cascade or nullification happen if DB prevents it.
                // Assuming no strict foreign key constraints preventing delete if projects exist:
                $user->delete();
                
                return response()->json(['success' => true, 'message' => 'User deleted successfully'], 200);
            }

            return response()->json(['success' => false, 'message' => 'User not found'], 404);
            
        } catch (\Throwable $e) {
            Log::error('AuthController@deleteFirebaseUser Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete user',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
