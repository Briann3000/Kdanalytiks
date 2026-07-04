<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    /**
     * Show the unified account settings page.
     */
    public function index()
    {
        $user = auth()->user();
        return view('account.settings', compact('user'));
    }

    /**
     * Update account profile details.
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => ['nullable', 'string', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'locale' => 'required|string|in:en,sw,fr,de,es,ar,zh-CN',
            'current_password' => 'nullable|required_with:new_password',
            'new_password' => ['nullable', 'confirmed', Password::min(8)],
        ], [
            'phone_number.regex' => 'The phone number format is invalid. Please use an international format (e.g., +254712345678).',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user, $validated) {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->phone_number = $validated['phone_number'];
            $user->locale = $validated['locale'];

            if ($request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'current_password' => ['The provided password does not match your current password.']
                    ]);
                }

                if (Hash::check($request->new_password, $user->password)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'new_password' => ['The new password cannot be the same as your current password.']
                    ]);
                }

                $user->password = Hash::make($validated['new_password']);
            }

            $user->save();
            return back()->with('success', 'Profile updated successfully.');
        });
    }

    /**
     * Update branding settings.
     */
    public function updateBranding(Request $request)
    {
        $user = auth()->user();

        if (!$user->hasActiveSubscription()) {
            return back()->with('error', 'You must have an active subscription to modify branding settings.');
        }

        $validated = $request->validate([
            'export_org_name' => 'nullable|string|max:255',
            'export_logo' => 'nullable|image|max:2048',
            'brand_color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/',
            'remove_kd_branding' => 'nullable',
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $user, $validated) {
            if ($request->has('remove_kd_branding_present')) {
                $user->remove_kd_branding = $request->has('remove_kd_branding');
            }

            if ($request->has('export_org_name')) {
                $user->export_org_name = $validated['export_org_name'];
            }

            if ($request->has('brand_color')) {
                $user->brand_color = $validated['brand_color'];
            }

            if ($request->hasFile('export_logo')) {
                if ($user->export_logo_url) {
                    Storage::disk('public')->delete($user->export_logo_url);
                }
                $path = $request->file('export_logo')->store('user_branding', 'public');
                $user->export_logo_url = $path;
                $user->brand_logo = $path; // Keep both in sync if needed, or just use export_logo_url
            }

            $user->save();
            return back()->with('success', 'Branding settings updated successfully.');
        });
    }
}
