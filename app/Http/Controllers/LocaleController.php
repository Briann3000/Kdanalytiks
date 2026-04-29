<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Switch the application locale.
     *
     * @param string $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch(string $locale)
    {
        $supported = ['en', 'sw', 'fr', 'de', 'es', 'ar', 'zh'];

        if (in_array($locale, $supported)) {
            // Store in session for guests and logged-in users
            Session::put('locale', $locale);
            App::setLocale($locale);

            // If user is authenticated, persist to their profile in the database
            if (auth()->check()) {
                auth()->user()->update(['locale' => $locale]);
            }
        }

        return redirect()->back();
    }
}
