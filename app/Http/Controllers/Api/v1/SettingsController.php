<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Traits\ImageUploadTrait;

use Illuminate\Support\Facades\Mail;
use App\Mail\AccountDeletedUserEmail;
use App\Mail\AccountDeletedAdminEmail;

class SettingsController extends Controller
{
    use ImageUploadTrait;
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'bio' => 'sometimes|nullable|string',
            'avatar_file' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        $data = $request->except(['avatar_file']); // Exclude file field

        if ($request->hasFile('avatar_file')) {
            $this->deleteImage($user->avatar_url);
            $path = $this->uploadImage($request->file('avatar_file'), 'avatars', 500, 500);
            $data['avatar_url'] = $path;
        }

        $user->update($data);
        $user = $user->fresh();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password does not match.',
                'data' => []
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.',
            'data' => []
        ]);
    }

    /**
     * Delete user account and all associated data.
     */
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        \DB::beginTransaction();
        try {
            // Delete Tickets
            $user->tickets()->delete();

            // Delete Links
            $user->links()->delete();

            // Delete Bio Pages and their related data
            foreach ($user->bioPages as $bioPage) {
                // Delete Analytics for this bio page
                $bioPage->analytics()->delete();
                // Delete Leads for this bio page
                $bioPage->leads()->delete();
                // Forget cache
                $bioPage->purgeCache();
                // Delete the bio page itself
                $bioPage->delete();
            }

            // Delete Subscriptions and Plan Changes
            foreach ($user->subscriptions as $subscription) {
                \App\Models\PlanChange::where('subscription_id', $subscription->id)->delete();
                $subscription->delete();
            }

            // Capture info for email
            $name = $user->name;
            $email = $user->email;

            // Finally delete the user
            $user->tokens()->delete(); // Revoke all tokens
            $user->delete();

            \DB::commit();

            // Send emails after successful commit
            try {
                // Send to user
                Mail::to($email)->send(new AccountDeletedUserEmail($name));
                
                // Send to admin
                Mail::to(config('mail.from.address'))->send(new AccountDeletedAdminEmail($name, $email));
            } catch (\Exception $e) {
                \Log::error("Failed to send account deletion emails: " . $e->getMessage());
            }


            return response()->json([
                'success' => true,
                'message' => 'Your account and all associated data have been permanently deleted.',
                'data' => []
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete account. Please try again later.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
