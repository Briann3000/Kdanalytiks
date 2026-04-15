<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function showLoginForm(Request $request, $role = null)
    {
        if (!$role) {
            $role = explode('.', $request->route()->getName())[0];
        }
        return view('auth.login', compact('role'));
    }

    public function login(Request $request, $role = null)
    {
        if (!$role) {
            $role = explode('.', $request->route()->getName())[0];
        }
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->where('role', $role)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            if ($user->status === \App\Enums\UserStatus::Active) {
                Auth::login($user, $request->has('remember'));
                return redirect()->intended(route($user->role->value . '.dashboard'));
            } else {
                return back()->withErrors(['status' => 'Your account is not active']);
            }
        }

        return back()->withErrors(['credentials' => 'Invalid email or password']);
    }

    public function logout()
    {
        Auth::logout();
        return redirect('/');
    }
}