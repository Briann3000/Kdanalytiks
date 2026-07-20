<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    /**
     * Define the order and list of articles for sidebar navigation.
     */
    protected array $articles = [
        'getting-started' => 'Getting Started',
        'survey-builder' => 'Survey Builder',
        'collaboration' => 'Collaboration & Groups',
        'analytics' => 'Data & Analytics',
        'socius-ai' => 'Socius AI Assistant',
        'billing' => 'Billing & Rewards',
    ];

    public function index()
    {
        return redirect()->route('docs.show', ['article' => 'getting-started']);
    }

    public function show(string $article)
    {
        // Prevent directory traversal
        if (!preg_match('/^[a-z0-9-]+$/', $article)) {
            abort(404);
        }

        $path = resource_path("docs/{$article}.md");

        if (!File::exists($path)) {
            abort(404);
        }

        $markdown = File::get($path);
        $htmlContent = Str::markdown($markdown);
        $articles = $this->articles;

        return view('docs.show', compact('htmlContent', 'article', 'articles'));
    }
}
