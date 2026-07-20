@extends('layouts.app')

@section('content')
    <div class="docs-page-wrap">
        <!-- Sidebar -->
        <aside class="docs-sidebar">
            <nav>
                <div class="docs-sidebar-label">{{ __('Documentation') }}</div>
                @foreach($articles as $slug => $title)
                    <a href="{{ route('docs.show', $slug) }}"
                        class="docs-nav-link {{ $article === $slug ? 'docs-nav-link--active' : '' }}">
                        {{ $title }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="docs-content">
            <div class="docs-content-inner">
                <article class="prose-custom">
                    {!! $htmlContent !!}
                </article>
            </div>
        </main>
    </div>

    <style>
        /* ── Docs Page Layout ─────────────────────────────────── */
        .docs-page-wrap {
            display: flex;
            align-items: flex-start;
            gap: 1.75rem;
            max-width: 1280px;
            margin: 0 auto;
            padding: 2.5rem 1.5rem;
            min-height: calc(100vh - 64px);
        }

        /* ── Sidebar ──────────────────────────────────────────── */
        .docs-sidebar {
            flex-shrink: 0;
            width: 240px;
            position: sticky;
            top: 80px;
            align-self: flex-start;
        }

        .docs-sidebar nav {
            background: #fff;
            border: 1px solid #f3f4f6;
            border-radius: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .docs-sidebar-label {
            font-size: 0.65rem;
            font-weight: 900;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #9ca3af;
            padding: 0.25rem 0.75rem 0.75rem;
            border-bottom: 1px solid #f3f4f6;
            margin-bottom: 0.5rem;
        }

        .docs-nav-link {
            display: block;
            padding: 0.65rem 0.875rem;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #6b7280;
            border-radius: 0.875rem;
            transition: background 0.15s, color 0.15s;
            text-decoration: none;
        }

        .docs-nav-link:hover {
            background: #f9fafb;
            color: #111827;
        }

        .docs-nav-link--active {
            background: #eef2ff;
            color: #4338ca;
            font-weight: 900;
            border: 1px solid rgba(99, 102, 241, .15);
        }

        /* ── Article Content ──────────────────────────────────── */
        .docs-content {
            flex: 1;
            min-width: 0;
        }

        .docs-content-inner {
            background: #fff;
            border: 1px solid #f3f4f6;
            border-radius: 2rem;
            box-shadow: 0 4px 24px rgba(0, 0, 0, .06);
            padding: 2.5rem 3rem;
        }

        /* ── Responsive: collapse sidebar on small screens ────── */
        @media (max-width: 768px) {
            .docs-page-wrap {
                flex-direction: column;
                padding: 1rem;
            }

            .docs-sidebar {
                width: 100%;
                position: static;
            }

            .docs-sidebar nav {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 6px;
            }

            .docs-sidebar-label {
                width: 100%;
                border-bottom: none;
                padding-bottom: 0.25rem;
            }

            .docs-content-inner {
                padding: 1.5rem;
            }
        }

        /* ── Prose Typography ─────────────────────────────────── */
        .prose-custom h1 {
            font-size: 2rem;
            font-weight: 900;
            color: #111827;
            margin-bottom: 1.5rem;
            letter-spacing: -0.025em;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 0.75rem;
        }

        .prose-custom h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1f2937;
            margin-top: 2rem;
            margin-bottom: 1rem;
            letter-spacing: -0.015em;
        }

        .prose-custom h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #374151;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .prose-custom p {
            font-size: 0.925rem;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 1.25rem;
            font-weight: 500;
        }

        .prose-custom ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #4b5563;
        }

        .prose-custom ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #4b5563;
        }

        .prose-custom li {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.6;
            font-weight: 500;
        }

        .prose-custom strong {
            font-weight: 700;
            color: #111827;
        }

        .prose-custom blockquote {
            border-left: 4px solid #6366f1;
            padding-left: 1.25rem;
            font-style: italic;
            color: #4b5563;
            margin: 1.5rem 0;
        }

        .prose-custom code {
            background-color: #f3f4f6;
            padding: 0.2rem 0.4rem;
            border-radius: 0.375rem;
            font-size: 0.85em;
            font-family: monospace;
            color: #e11d48;
        }

        .prose-custom pre {
            background: #1e1e2e;
            color: #cdd6f4;
            padding: 1.25rem 1.5rem;
            border-radius: 1rem;
            overflow-x: auto;
            font-size: 0.82rem;
            margin: 1.25rem 0;
        }

        .prose-custom pre code {
            background: none;
            color: inherit;
            padding: 0;
            font-size: inherit;
        }
    </style>
@endsection