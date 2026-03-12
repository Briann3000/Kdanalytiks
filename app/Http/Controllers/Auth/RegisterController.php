<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Organization;
use App\Models\Independent;

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
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
            'status' => 'pending', // or active, depending on logic
        ]);

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

        // For respondent, no additional table

        return redirect()->route($role . '.login')->with('success', 'Registration successful. Please wait for activation.');
    }
}