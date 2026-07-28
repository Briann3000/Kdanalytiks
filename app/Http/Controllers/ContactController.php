<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        return view('contact');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Save local backup copy so zero contact messages are lost
        $backupLine = "[" . date('Y-m-d H:i:s') . "] FROM: {$validated['name']} <{$validated['email']}> | SUBJECT: {$validated['subject']}\nMESSAGE: {$validated['message']}\n" . str_repeat('-', 60) . "\n";
        @file_put_contents(storage_path('logs/contact_submissions.log'), $backupLine, FILE_APPEND);

        try {
            // Send email to official platform inbox
            \Illuminate\Support\Facades\Mail::to('infokdanalytiks@gmail.com')
                ->send(new \App\Mail\ContactSubmission($validated));

            \Illuminate\Support\Facades\Log::info('Contact Form Submission', $validated);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Contact form email failed: ' . $e->getMessage(), $validated);
        }

        return redirect()->back()->with('contact_success', __('Thank you for contacting KDAnalytiks! Your message has been received and logged by our team.'));
    }
}
