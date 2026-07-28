@extends('layouts.app')

@section('content')
    <div class="docs-page-wrap">
        <!-- Docs Sidebar (Sticky Header + Independent Scrollbar) -->
        <aside class="docs-sidebar custom-scrollbar">
            <div class="docs-sidebar-header">
                <i class="fa-solid fa-book-open text-indigo-600 mr-2.5 text-sm"></i>
                <span>{{ __('Documentation') }}</span>
            </div>
            <nav class="docs-sidebar-nav">
                @foreach($articles as $slug => $title)
                    <a href="{{ route('docs.show', $slug) }}"
                        class="docs-nav-link {{ $article === $slug ? 'docs-nav-link--active' : '' }}">
                        <span class="docs-nav-indicator"></span>
                        <span>{{ $title }}</span>
                    </a>
                @endforeach
            </nav>
        </aside>

        <!-- Main Content Area (Independent Scroll Container) -->
        <main class="docs-content custom-scrollbar">
            <div class="docs-content-inner">
                <article class="prose-custom">
                    {!! $htmlContent !!}
                </article>
            </div>
        </main>
    </div>

    <style>
        /* ── Full Height Docs Layout Wrapper (Locks Outer Window) ───── */
        .docs-page-wrap {
            display: flex;
            width: 100%;
            height: calc(100vh - 64px);
            overflow: hidden;
            /* Parent container locked so sidebar and content scroll independently */
            margin: 0;
            padding: 0;
            background: #fcfcfd;
            box-sizing: border-box;
        }

        /* ── Left Sidebar (320px Width with Independent Scroll & Sticky Header) ── */
        .docs-sidebar {
            width: 320px;
            flex-shrink: 0;
            height: 100%;
            overflow-y: auto;
            /* Independent scrollbar for sidebar */
            background: #ffffff;
            border-right: 1px solid #eaecf0;
            box-sizing: border-box;
            z-index: 20;
            position: relative;
        }

        /* Sticky Header inside Docs Sidebar */
        .docs-sidebar-header {
            position: sticky;
            top: 0;
            background: #ffffff;
            z-index: 30;
            display: flex;
            align-items: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #1d2939;
            padding: 1.5rem 1.25rem 0.875rem 1.75rem;
            border-bottom: 1px solid #f2f4f7;
            letter-spacing: -0.01em;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.03);
        }

        .docs-sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            padding: 0.75rem 1.25rem 3rem 1.75rem;
        }

        .docs-nav-link {
            display: flex;
            align-items: center;
            padding: 0.65rem 0.875rem;
            font-size: 0.825rem;
            font-weight: 600;
            color: #475467;
            border-radius: 0.75rem;
            transition: all 0.15s ease;
            text-decoration: none;
            position: relative;
        }

        .docs-nav-link:hover {
            background: #f9fafb;
            color: #101828;
        }

        .docs-nav-link--active {
            background: #eff6ff;
            color: #175cd3;
            font-weight: 700;
        }

        .docs-nav-link--active .docs-nav-indicator {
            position: absolute;
            left: 0;
            top: 20%;
            bottom: 20%;
            width: 3.5px;
            background: #175cd3;
            border-radius: 0 4px 4px 0;
        }

        /* ── Right Content Area (Independent Scroll Container) ── */
        .docs-content {
            flex: 1;
            min-width: 0;
            height: 100%;
            overflow-y: auto;
            /* Independent scrollbar for article content */
            padding: 2.5rem 3.5rem 5rem 3.5rem;
            box-sizing: border-box;
        }

        .docs-content-inner {
            background: #ffffff;
            border: 1px solid #eaecf0;
            border-radius: 1.5rem;
            box-shadow: 0 1px 3px rgba(16, 24, 40, 0.05);
            padding: 3rem 3.5rem;
            max-width: 960px;
        }

        /* Independent Scrollbars for Sidebar and Content */
        .docs-sidebar::-webkit-scrollbar,
        .docs-content::-webkit-scrollbar {
            width: 6px;
        }

        .docs-sidebar::-webkit-scrollbar-track,
        .docs-content::-webkit-scrollbar-track {
            background: transparent;
        }

        .docs-sidebar::-webkit-scrollbar-thumb,
        .docs-content::-webkit-scrollbar-thumb {
            background: #e4e7ec;
            border-radius: 4px;
        }

        .docs-sidebar::-webkit-scrollbar-thumb:hover,
        .docs-content::-webkit-scrollbar-thumb:hover {
            background: #d0d5dd;
        }

        /* Responsive Behavior */
        @media (max-width: 1024px) {
            .docs-page-wrap {
                flex-direction: column;
                height: auto;
                overflow: visible;
            }

            .docs-sidebar {
                width: 100%;
                height: auto;
                max-height: 300px;
                border-right: none;
                border-bottom: 1px solid #eaecf0;
            }

            .docs-content {
                height: auto;
                padding: 1.5rem 1rem 3rem 1rem;
            }

            .docs-content-inner {
                padding: 1.5rem 1.25rem;
                max-width: 100%;
            }
        }

        /* ── Prose Typography ─────────────────────────────────── */
        .prose-custom h1 {
            font-size: 2rem;
            font-weight: 900;
            color: #101828;
            margin-bottom: 1.5rem;
            letter-spacing: -0.025em;
            border-bottom: 2px solid #f2f4f7;
            padding-bottom: 0.75rem;
        }

        .prose-custom h2 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #1d2939;
            margin-top: 2.25rem;
            margin-bottom: 1rem;
            letter-spacing: -0.015em;
        }

        .prose-custom h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #344054;
            margin-top: 1.75rem;
            margin-bottom: 0.75rem;
        }

        .prose-custom p {
            font-size: 0.925rem;
            color: #475467;
            line-height: 1.75;
            margin-bottom: 1.25rem;
            font-weight: 450;
        }

        .prose-custom ul {
            list-style-type: disc;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #475467;
        }

        .prose-custom ol {
            list-style-type: decimal;
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
            color: #475467;
        }

        .prose-custom li {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            line-height: 1.6;
            font-weight: 450;
        }

        .prose-custom strong {
            font-weight: 700;
            color: #101828;
        }

        .prose-custom blockquote {
            border-left: 4px solid #175cd3;
            padding-left: 1.25rem;
            font-style: italic;
            color: #344054;
            margin: 1.5rem 0;
            background: #f8fafc;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        .prose-custom code {
            background-color: #f2f4f7;
            padding: 0.2rem 0.4rem;
            border-radius: 0.375rem;
            font-size: 0.85em;
            font-family: monospace;
            color: #c01048;
        }

        .prose-custom pre {
            background: #0f172a;
            color: #e2e8f0;
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