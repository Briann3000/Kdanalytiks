<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicationController extends Controller
{
    public function index()
    {
        $publications = session('user_publications', []);

        $wpConfig = session('wp_config', [
            'site_url' => '',
            'username' => '',
            'status' => 'not_connected'
        ]);

        return view('publications.index', compact('publications', 'wpConfig'));
    }

    public function show($id)
    {
        $publications = session('user_publications', []);
        $publication = collect($publications)->firstWhere('id', (int) $id);

        if (!$publication) {
            return redirect()->route('publications')->with('error', __('Publication not found.'));
        }

        return view('publications.show', compact('publication'));
    }

    public function testWordPress(Request $request)
    {
        $validated = $request->validate([
            'site_url' => 'required|url',
            'username' => 'required|string',
            'app_password' => 'required|string',
        ]);

        $siteUrl = rtrim($validated['site_url'], '/');
        $endpoint = $siteUrl . '/wp-json/wp/v2/users/me';

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $validated['username'],
                $validated['app_password']
            )->timeout(10)->get($endpoint);

            if ($response->successful()) {
                $wpUserData = $response->json();
                $wpName = $wpUserData['name'] ?? $validated['username'];

                session([
                    'wp_config' => [
                        'site_url' => $siteUrl,
                        'username' => $validated['username'],
                        'app_password' => $validated['app_password'],
                        'wp_name' => $wpName,
                        'status' => 'connected'
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'message' => __('Connected to WordPress! Logged in as: ') . $wpName,
                    'wp_name' => $wpName
                ]);
            }

            $status = $response->status();
            $laymanMessage = match ($status) {
                401, 403 => __('Authentication failed: Please check your WordPress username and Application Password.'),
                404 => __('WordPress REST API not found: Please ensure the website URL is a valid WordPress site.'),
                default => __('Could not connect to WordPress site (HTTP Status ') . $status . ').'
            };

            return response()->json([
                'success' => false,
                'message' => $laymanMessage
            ], 422);

        } catch (\Throwable $e) {
            $errText = $e->getMessage();
            $laymanMessage = __('Connection failed: ');

            if (str_contains($errText, 'cURL error 6') || str_contains($errText, 'Could not resolve host')) {
                $laymanMessage .= __('Unable to reach the website address. Please double-check that your WordPress URL is spelled correctly and online.');
            } elseif (str_contains($errText, 'cURL error 28') || str_contains($errText, 'timed out')) {
                $laymanMessage .= __('The connection to your WordPress website timed out. Please verify that your website is active.');
            } else {
                $laymanMessage .= __('Please check your website URL and internet connection.');
            }

            return response()->json([
                'success' => false,
                'message' => $laymanMessage
            ], 422);
        }
    }

    public function publishToWordPress(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'nullable|string'
        ]);

        $wpConfig = session('wp_config');
        if (!$wpConfig || ($wpConfig['status'] ?? '') !== 'connected') {
            return response()->json([
                'success' => false,
                'message' => __('Please connect your WordPress site credentials first before publishing.')
            ], 422);
        }

        $endpoint = rtrim($wpConfig['site_url'], '/') . '/wp-json/wp/v2/posts';

        try {
            $response = \Illuminate\Support\Facades\Http::withBasicAuth(
                $wpConfig['username'],
                $wpConfig['app_password']
            )->timeout(12)->post($endpoint, [
                        'title' => $validated['title'],
                        'content' => $validated['content'],
                        'status' => 'publish',
                    ]);

            if ($response->successful()) {
                $postData = $response->json();
                $link = $postData['link'] ?? '#';

                return response()->json([
                    'success' => true,
                    'message' => __('Successfully published research finding to WordPress blog!'),
                    'post_link' => $link
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('WordPress API publish failed: ') . ($response->json('message') ?? __('HTTP Error ') . $response->status())
                ], 422);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('WordPress Publish Error: ') . $e->getMessage()
            ], 500);
        }
    }

    public function storePublication(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'summary' => 'required|string',
            'author' => 'nullable|string|max:255',
            'pdf_url' => 'nullable|string|max:1000',
            'sync_wp' => 'nullable|boolean'
        ]);

        $pdfPath = null;
        if ($request->hasFile('pdf_file') && $request->file('pdf_file')->isValid()) {
            $path = $request->file('pdf_file')->store('publications_pdf', 'public');
            $pdfPath = '/storage/' . $path;
        } elseif (!empty($validated['pdf_url'])) {
            $pdfPath = $validated['pdf_url'];
        }

        $publications = session('user_publications', []);
        $newPub = [
            'id' => time(),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'date' => date('M d, Y'),
            'author' => $validated['author'] ?: (auth()->user()->name ?? 'Research Author'),
            'summary' => $validated['summary'],
            'pdf_url' => $pdfPath,
            'wp_synced' => false
        ];

        // Auto post to WP if enabled
        if (!empty($validated['sync_wp'])) {
            $wpConfig = session('wp_config');
            if ($wpConfig && ($wpConfig['status'] ?? '') === 'connected') {
                try {
                    $endpoint = rtrim($wpConfig['site_url'], '/') . '/wp-json/wp/v2/posts';
                    $wpRes = \Illuminate\Support\Facades\Http::withBasicAuth(
                        $wpConfig['username'],
                        $wpConfig['app_password']
                    )->post($endpoint, [
                                'title' => $validated['title'],
                                'content' => '<p>' . e($validated['summary']) . '</p><hr/><p><em>Published via KDAnalytiks Research Ecosystem</em></p>',
                                'status' => 'publish'
                            ]);
                    if ($wpRes->successful()) {
                        $newPub['wp_synced'] = true;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('WP auto post failed: ' . $e->getMessage());
                }
            }
        }

        array_unshift($publications, $newPub);
        session(['user_publications' => $publications]);

        return redirect()->back()->with('pub_success', __('Research publication submitted successfully!'));
    }

    public function destroy($id)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', __('You must be signed in to manage publications.'));
        }

        $user = auth()->user();
        $role = is_object($user->role) ? $user->role->value : $user->role;

        $publications = session('user_publications', []);
        $target = collect($publications)->firstWhere('id', (int) $id);

        if ($target && $role !== 'admin' && ($target['author'] ?? '') !== $user->name) {
            return redirect()->back()->with('error', __('Unauthorized: Only the author or an admin can delete this publication.'));
        }

        $filtered = array_values(array_filter($publications, function ($pub) use ($id) {
            return (int) ($pub['id'] ?? 0) !== (int) $id;
        }));

        session(['user_publications' => $filtered]);

        return redirect()->route('publications')->with('pub_success', __('Publication deleted successfully.'));
    }
}
