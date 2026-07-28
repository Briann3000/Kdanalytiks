<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    /**
     * Define the order and list of articles for sidebar navigation.
     * Pinned: 'getting-started' is first, followed by alphabetical order by title.
     */
    protected array $articles = [
        'getting-started' => 'Getting Started & Overview',
        'publications' => 'Academic Publications',
        'ai-architect-and-templates' => 'AI Architect & Template Library',
        'humanizer' => 'AI Research Humanizer',
        'analytics' => 'Analytics & Custom Dashboards',
        'billing' => 'Billing & Subscriptions',
        'customization-branding' => 'Branding & Themes',
        'collaboration' => 'Collaboration & Analysis Groups',
        'export-options' => 'Data Export Packages (Excel, Word, SPSS)',
        'deployment-distribution' => 'Deployment & Invite Campaigns',
        'survey-import-export' => 'Importing & Version Control',
        'logic-and-branching' => 'Logic & Skip Rules',
        'paid-surveys-rewards' => 'Paid Surveys & Wallet Incentives',
        'research-proposals' => 'Research Proposal Builder',
        'socius-ai' => 'Socius AI Assistant',
        
        'survey-builder' => 'Survey Builder & Question Types',
        'survey-code-and-preview' => 'Survey Code & Live Preview',
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
