<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GoogleController extends Controller
{
    public function redirectToGoogle()
    {
        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/spreadsheets https://www.googleapis.com/auth/drive.file',
            'access_type' => 'offline',
            'prompt' => 'select_account consent',
        ]);

        return redirect()->away("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }

    public function handleGoogleCallback(Request $request)
    {
        if (!$request->has('code')) {
            return redirect()->route('surveys.index')->with('error', 'Google authentication failed.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $request->get('code'),
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed()) {
            return redirect()->route('surveys.index')->with('error', 'Google authentication failed: ' . ($response->json()['error_description'] ?? 'Unknown error'));
        }

        $token = $response->json();
        // Add expiration time
        $token['created'] = time();

        session(['google_token' => $token]);

        $returnTo = session('google_export_url', route('surveys.index'));
        return redirect($returnTo)->with('success', 'Google account connected! You can now export to Sheets.');
    }
}
