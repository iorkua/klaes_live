<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function index()
    {
        $PageTitle = 'User Profile';
        $PageDescription = 'Manage your profile information';
        // Logic to fetch and display user profile information
        return view('profile.index' , compact('PageTitle', 'PageDescription'));
    }


    public function update(Request $request)
    {
        // Validate the incoming request
        $user = Auth::user();
        
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone_number' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'profile' => 'nullable',
        ]);

        // Handle profile image upload if provided
        if ($request->hasFile('profile')) {
            // Delete old profile image if it exists
            if ($user->profile && Storage::exists('public/' . $user->profile)) {
                Storage::delete('public/' . $user->profile);
            }
            
            // Store the new image
            $profilePath = $request->file('profile')->store('profiles', 'public');
            $user->profile = $profilePath;
        }

        // Update user information
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'];
       
        // Only update password if provided
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
            $user->is_password_change = 1; // Mark password as changed
        }
        
        // Save the updated user
        $user->save();
        
        return redirect()->route('profile.index')
                        ->with('success', 'Profile updated successfully.');
    }
}
