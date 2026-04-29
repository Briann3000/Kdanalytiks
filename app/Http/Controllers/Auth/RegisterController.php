<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;

use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request, $role = 'respondent')
    {
        return view('auth.register', compact('role'));
    }

    public function register(Request $request, $role = 'respondent')
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'institution' => 'nullable|string|max:255',
            'research_area' => 'nullable|string|max:255',
            'organization_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'active',
            'locale' => app()->getLocale(),
        ]);

        event(new Registered($user));

        if ($role == 'organization') {
            Organization::create([
                'user_id' => $user->id,
                'name' => $request->organization_name ?? $request->name,
            ]);
        } elseif ($role == 'independent') {
            Independent::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'institution' => $request->institution,
                'research_area' => $request->research_area,
            ]);
        }

        return redirect()->route($role . '.login')->with('success', 'Registration successful. A verification link has been sent to your email.');
    }
}