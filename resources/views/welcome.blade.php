@extends('layouts.app')

@section('title', 'KDAnalytiks | Collect, Analyze Data & Compile Findings')
@section('meta_description', 'KDAnalytiks is an AI-powered research platform that helps users collect data, run statistical analysis and compile automated publication-ready reports.')

@push('styles')
    <style>
        .full-bleed-container {
            width: 100%;
            margin: 0 !important;
            padding: 0 !important;
        }

        :root {
            --kd-primary: #2271b1;
            --kd-primary-dark: #135e96;
            --kd-primary-light: #EFF6FF;
            --kd-accent: #0284C7;
            --kd-navy: #0F172A;
            --kd-slate: #1E293B;
            --kd-muted: #64748B;
            --kd-subtle: #94A3B8;
            --kd-border: #E2E8F0;
            --kd-bg-alt: #F8FAFC;
            --kd-bg-soft: #F1F5F9;
        }

        /* Typography */
        .hero-title-light {
            font-size: clamp(32px, 4.4vw, 54px);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.03em;
            color: var(--kd-navy);
        }

        .hero-title-light .highlight {
            color: var(--kd-primary);
            display: block;
        }

        /* Connected 3-Step Process (Light) */
        .step-track-light {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            position: relative;
        }

        .step-track-light::before {
            content: "";
            position: absolute;
            top: 24px;
            left: 40px;
            right: 40px;
            height: 2px;
            background: repeating-linear-gradient(to right, #CBD5E1, #CBD5E1 6px, transparent 6px, transparent 12px);
            z-index: 1;
        }

        .step-node-light {
            position: relative;
            z-index: 2;
        }

        .step-node-light .node-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #FFFFFF;
            border: 2px solid #E2E8F0;
            color: var(--kd-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-bottom: 12px;
            box-shadow: 0 4px 12px rgba(34, 113, 177, 0.12);
            transition: all .2s ease;
        }

        .step-node-light:hover .node-icon {
            border-color: var(--kd-primary);
            background: var(--kd-primary);
            color: #FFFFFF;
            transform: scale(1.06);
        }

        /* Buttons */
        .btn-kd-primary {
            background: var(--kd-primary);
            color: #FFFFFF !important;
            padding: 13px 26px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 10px 20px -6px rgba(34, 113, 177, 0.4);
            transition: all .15s ease;
        }

        .btn-kd-primary:hover {
            background: var(--kd-primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 14px 24px -6px rgba(34, 113, 177, 0.5);
        }

        .btn-kd-outline {
            background: #FFFFFF;
            color: var(--kd-navy) !important;
            border: 1px solid var(--kd-border);
            padding: 13px 24px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all .15s ease;
        }

        .btn-kd-outline:hover {
            border-color: var(--kd-primary);
            background: var(--kd-primary-light);
            transform: translateY(-2px);
        }

        /* Image Mockup Frames: Realistic Laptop & Mobile */
        .device-showcase-container {
            position: relative;
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
        }

        .laptop-mockup {
            position: relative;
            width: 100%;
            filter: drop-shadow(0 20px 30px rgba(15, 23, 42, 0.15));
        }

        .laptop-screen-bezel {
            background: #0f172a;
            border-radius: 16px 16px 0 0;
            padding: 10px 10px 0 10px;
            border: 2px solid #334155;
            border-bottom: none;
            position: relative;
        }

        .laptop-camera-dot {
            width: 6px;
            height: 6px;
            background: #475569;
            border-radius: 50%;
            margin: 0 auto 6px auto;
        }

        .laptop-screen-glass {
            border-radius: 8px 8px 0 0;
            overflow: hidden;
            background: #020617;
            aspect-ratio: 16 / 10;
            position: relative;
        }

        .laptop-screen-glass img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top left;
        }

        .laptop-base-chassis {
            height: 14px;
            background: linear-gradient(180deg, #64748b 0%, #475569 40%, #334155 100%);
            border-radius: 0 0 16px 16px;
            position: relative;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);
        }

        .laptop-base-notch {
            width: 70px;
            height: 4px;
            background: #1e293b;
            border-radius: 0 0 4px 4px;
            margin: 0 auto;
        }

        /* Overlapping Mobile Phone Mockup */
        .phone-mockup-overlap {
            position: absolute;
            right: -10px;
            bottom: -20px;
            width: 140px;
            z-index: 20;
            filter: drop-shadow(0 15px 25px rgba(0, 0, 0, 0.25));
        }

        @media (min-width: 640px) {
            .phone-mockup-overlap {
                right: -20px;
                bottom: -25px;
                width: 165px;
            }
        }

        .phone-chassis {
            background: #0f172a;
            border: 3px solid #334155;
            border-radius: 28px;
            padding: 6px;
            position: relative;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .phone-dynamic-island {
            width: 36px;
            height: 8px;
            background: #020617;
            border-radius: 10px;
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 30;
        }

        .phone-screen-glass {
            border-radius: 22px;
            overflow: hidden;
            background: #090d16;
            aspect-ratio: 9 / 18;
            position: relative;
        }

        .phone-screen-glass img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: top;
        }

        /* Browser Window Mockup Frame */
        .browser-mockup-frame {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.2);
        }

        .browser-header-bar {
            background: #1e293b;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #334155;
        }

        .browser-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .browser-url-pill {
            background: #0f172a;
            color: #94a3b8;
            font-size: 11px;
            font-family: monospace;
            padding: 3px 14px;
            border-radius: 12px;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid #334155;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .browser-viewport-glass {
            background: #020617;
            overflow: hidden;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .browser-viewport-glass img {
            width: 100%;
            height: auto;
            max-height: 480px;
            object-fit: contain;
            display: block;
        }

        .browser-scrollable-glass {
            max-height: 460px;
            overflow-y: auto;
            scrollbar-width: thin;
            background: #020617;
        }

        .browser-scrollable-glass img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Horizontal Infinite Marquee for Feature Highlights */
        .feature-marquee-container {
            position: relative;
            width: 100%;
            overflow: hidden;
            mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 4%, black 96%, transparent);
            padding: 10px 0;
        }

        .feature-marquee-track {
            display: flex;
            gap: 16px;
            width: max-content;
            animation: featureMarqueeScroll 45s linear infinite;
        }

        .feature-marquee-track:hover {
            animation-play-state: paused;
        }

        @keyframes featureMarqueeScroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .feature-marquee-card {
            background: #FFFFFF;
            border: 1px solid var(--kd-border);
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 4px 16px -4px rgba(15, 23, 42, 0.05);
            transition: all .25s ease;
            text-decoration: none !important;
            flex-shrink: 0;
            width: 295px;
        }

        .feature-marquee-card:hover {
            border-color: var(--kd-primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -8px rgba(34, 113, 177, 0.18);
        }

        .feature-marquee-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        /* AI Highlight Badges */
        .ai-feature-card {
            background: #FFFFFF;
            border: 1px solid var(--kd-border);
            border-radius: 16px;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 16px -4px rgba(15, 23, 42, 0.05);
            transition: all .2s ease;
        }

        .ai-feature-card:hover {
            border-color: var(--kd-primary);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px -8px rgba(34, 113, 177, 0.18);
        }

        .ai-feature-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: var(--kd-primary-light);
            color: var(--kd-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        /* M&E 3 Cards */
        .me-card-light {
            background: #FFFFFF;
            border: 1px solid var(--kd-border);
            border-radius: 20px;
            padding: 32px 28px;
            box-shadow: 0 6px 20px -6px rgba(15, 23, 42, 0.06);
            transition: all .2s ease;
        }

        .me-card-light:hover {
            border-color: var(--kd-primary);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(34, 113, 177, 0.16);
        }

        /* Role Cards */
        .role-card-light {
            background: #FFFFFF;
            border: 1px solid var(--kd-border);
            border-radius: 20px;
            padding: 28px 24px;
            text-decoration: none;
            display: block;
            color: inherit;
            transition: all .2s ease;
            box-shadow: 0 6px 20px -6px rgba(15, 23, 42, 0.05);
        }

        .role-card-light:hover {
            border-color: var(--kd-primary);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px -15px rgba(34, 113, 177, 0.16);
        }

        .role-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--kd-primary-light);
            color: var(--kd-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        /* FAQ Accordion */
        .faq-accordion-card {
            background: #FFFFFF;
            border: 1px solid var(--kd-border);
            border-radius: 16px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .faq-accordion-card:hover {
            border-color: var(--kd-primary);
        }

        .faq-accordion-btn {
            width: 100%;
            padding: 20px 24px;
            text-align: left;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 16px;
            font-weight: 700;
            color: var(--kd-navy);
            background: transparent;
            border: none;
            cursor: pointer;
        }

        .faq-accordion-content {
            padding: 0 24px 20px;
            font-size: 14.5px;
            color: #475569;
            line-height: 1.65;
        }

        /* Comparison Table */
        .comp-light-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--kd-border);
            background: #FFFFFF;
        }

        .comp-light-table th,
        .comp-light-table td {
            padding: 16px 20px;
            text-align: left;
            font-size: 13.5px;
            border-bottom: 1px solid var(--kd-border);
        }

        .comp-light-table th {
            background: #F8FAFC;
            color: var(--kd-navy);
            font-weight: 800;
        }

        .comp-light-table tr:last-child td {
            border-bottom: none;
        }

        /* Scroll Reveal */
        .reveal {
            opacity: 1;
            transform: none;
        }

        body.js-ready .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .6s cubic-bezier(.22, .61, .36, 1), transform .6s cubic-bezier(.22, .61, .36, 1);
        }

        body.js-ready .reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        body.js-ready .reveal-group .reveal-item {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity .5s cubic-bezier(.22, .61, .36, 1), transform .5s cubic-bezier(.22, .61, .36, 1);
        }

        body.js-ready .reveal-group.is-visible .reveal-item {
            opacity: 1;
            transform: none;
        }

        /* Comprehensive Mobile Responsiveness */
        @media (max-width: 768px) {
            .hero-title-light {
                font-size: 2.35rem;
                line-height: 1.15;
                letter-spacing: -0.025em;
            }

            .browser-header-bar {
                padding: 8px 12px;
            }

            .browser-url-pill {
                font-size: 10px;
                padding: 2px 10px;
            }
        }

        @media (max-width: 640px) {
            .hero-title-light {
                font-size: 2rem;
                line-height: 1.18;
            }

            /* Horizontal Step Flow on Mobile */
            .step-track-light {
                display: flex;
                flex-direction: column;
                gap: 14px;
            }

            .step-track-light::before {
                display: none;
            }

            .step-node-light {
                display: flex;
                align-items: flex-start;
                gap: 14px;
            }

            .step-node-light .node-icon {
                margin-bottom: 0;
                width: 40px;
                height: 40px;
                font-size: 15px;
                flex-shrink: 0;
            }

            /* Device Mockups on Mobile */
            .device-showcase-container {
                max-width: 100%;
                padding: 0 0 16px 0;
            }

            .laptop-screen-bezel {
                border-radius: 12px 12px 0 0;
                padding: 6px 6px 0 6px;
                border-width: 1.5px;
            }

            .laptop-camera-dot {
                width: 4px;
                height: 4px;
                margin-bottom: 4px;
            }

            .laptop-screen-glass {
                border-radius: 6px 6px 0 0;
            }

            .laptop-base-chassis {
                height: 9px;
                border-radius: 0 0 12px 12px;
            }

            .laptop-base-notch {
                width: 46px;
                height: 3px;
            }

            .phone-mockup-overlap {
                right: -2px;
                bottom: -12px;
                width: 115px;
            }

            .phone-chassis {
                border-width: 2.5px;
                padding: 4px;
                border-radius: 20px;
            }

            .phone-dynamic-island {
                width: 24px;
                height: 6px;
                top: 7px;
            }

            .phone-screen-glass {
                border-radius: 16px;
            }

            .browser-mockup-frame {
                border-radius: 14px;
            }

            .browser-dot {
                width: 8px;
                height: 8px;
            }

            /* Marquee Ticker Mobile Proportions */
            .feature-marquee-container {
                padding: 6px 0;
            }

            .feature-marquee-track {
                gap: 12px;
                animation-duration: 38s;
            }

            .feature-marquee-card {
                width: 255px;
                padding: 12px 14px;
                gap: 11px;
                border-radius: 14px;
            }

            .feature-marquee-icon {
                width: 38px;
                height: 38px;
                font-size: 1rem;
                border-radius: 10px;
            }

            .feature-marquee-card h4 {
                font-size: 13px;
            }

            .feature-marquee-card p {
                font-size: 11px;
            }

            /* FAQ Accordion Mobile Spacing */
            .faq-accordion-btn {
                padding: 16px 18px;
                font-size: 15px;
            }

            .faq-accordion-content {
                padding: 0 18px 16px;
                font-size: 13.5px;
            }

            /* Table Mobile Spacing */
            .comp-light-table th,
            .comp-light-table td {
                padding: 12px 14px;
                font-size: 12.5px;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Structured Data JSON-LD Schema for Google -->
    <script type="application/ld+json">
            {
              "@context": "https://schema.org",
              "@type": "WebApplication",
              "name": "KDAnalytiks",
              "url": "{{ url('/') }}",
              "applicationCategory": "BusinessApplication",
              "operatingSystem": "All",
              "description": "KDAnalytiks is an AI-powered research platform that helps users collect data, run statistical analysis and compile automated reports.",
              "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "USD"
              }
            }
            </script>

    <!-- Full-Bleed Edge-to-Edge Container -->
    <div class="full-bleed-container">

        <!-- 1. Hero Section (Clean Light Theme with Side-by-Side Layout) -->
        <section class="bg-slate-50 border-b border-slate-200 py-12 lg:py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-center">
                    <!-- Left Column: Copy & Actions -->
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 font-black text-lg text-slate-900 tracking-tight">
                            <i class="fa-solid fa-chart-simple text-[#2271b1]"></i>
                            <span>KDAnalytiks</span>
                        </div>

                        <h1 class="hero-title-light">
                            Data Simplified.
                            <span class="highlight">Insights Amplified.</span>
                        </h1>

                        <p class="text-base sm:text-lg text-slate-600 font-medium leading-relaxed max-w-xl">
                            {{ __('Transforming Complex Data into Actionable Strategies') }}
                        </p>

                        <!-- Connected 3-Step Process -->
                        <div class="pt-2 pb-2">
                            <div class="step-track-light">
                                <div class="step-node-light">
                                    <div class="node-icon">
                                        <i class="fa-solid fa-database"></i>
                                    </div>
                                    <h4 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Collect Data') }}</h4>
                                    <p class="text-xs text-slate-500 leading-snug m-0">
                                        {{ __('Gather accurate and relevant data.') }}
                                    </p>
                                </div>

                                <div class="step-node-light">
                                    <div class="node-icon">
                                        <i class="fa-solid fa-chart-line"></i>
                                    </div>
                                    <h4 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Analyze Data') }}</h4>
                                    <p class="text-xs text-slate-500 leading-snug m-0">
                                        {{ __('Uncover patterns and generate insights.') }}
                                    </p>
                                </div>

                                <div class="step-node-light">
                                    <div class="node-icon">
                                        <i class="fa-solid fa-file-lines"></i>
                                    </div>
                                    <h4 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Compile Report') }}</h4>
                                    <p class="text-xs text-slate-500 leading-snug m-0">
                                        {{ __('Deliver clear, actionable reports.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Hero Actions -->
                        <div class="flex items-center gap-3 flex-wrap pt-2">
                            <a href="#get-started" class="btn-kd-primary flex-1 sm:flex-none justify-center">
                                <span>{{ __('Get started') }}</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                            <a href="#m-and-e" class="btn-kd-outline flex-1 sm:flex-none justify-center">
                                <span>{{ __('See how it works') }}</span>
                            </a>
                        </div>

                        <p class="text-xs font-semibold text-slate-500 flex items-center gap-2 pt-1">
                            <i class="fa-solid fa-circle-check text-[#2271b1]"></i>
                            <span>{{ __('One seamless platform for survey creators, analysts and respondents worldwide.') }}</span>
                        </p>
                    </div>

                    <!-- Right Column: Realistic Laptop & Overlapping Mobile Survey Showcase (Side-by-Side) -->
                    <div class="w-full">
                        <div class="device-showcase-container">
                            <!-- Laptop Device Mockup -->
                            <div class="laptop-mockup">
                                <div class="laptop-screen-bezel">
                                    <div class="laptop-camera-dot"></div>
                                    <div class="laptop-screen-glass">
                                        <img src="{{ asset('images/platform/dashboard-preview.png') }}"
                                            alt="KDAnalytiks Dashboard Workspace"
                                            class="w-full h-full object-cover object-top"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                        <!-- Visual Fallback -->
                                        <div class="placeholder-overlay flex-col items-center justify-center space-y-3 h-full bg-slate-950 text-white"
                                            style="display: none;">
                                            <i class="fa-solid fa-chart-line text-3xl text-[#2271b1]"></i>
                                            <p class="text-xs text-slate-300 font-bold m-0">
                                                {{ __('KDAnalytiks Desktop Dashboard') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="laptop-base-chassis">
                                    <div class="laptop-base-notch"></div>
                                </div>
                            </div>

                            <!-- Overlapping Mobile Phone Mockup (Live Mobile Survey Form) -->
                            <div class="phone-mockup-overlap">
                                <div class="phone-chassis">
                                    <div class="phone-dynamic-island"></div>
                                    <div class="phone-screen-glass">
                                        <img src="{{ asset('images/platform/mobile-preview.png') }}"
                                            alt="KDAnalytiks Mobile Survey Form"
                                            class="w-full h-full object-cover object-top"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                        <!-- Visual Fallback -->
                                        <div class="placeholder-overlay flex-col items-center justify-center space-y-2 h-full bg-slate-950 text-white p-2"
                                            style="display: none;">
                                            <i class="fa-solid fa-clipboard-check text-xl text-[#2271b1]"></i>
                                            <p class="text-[9px] text-slate-300 font-bold m-0">{{ __('Live Survey Form') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horizontal Infinite Scrolling Ticker of Platform Features (Full Viewport Width) -->
            <div class="w-full mt-14 overflow-hidden reveal">
                <div class="feature-marquee-container">
                    <div class="feature-marquee-track">
                        <!-- Loop 1 -->
                        <!-- 1. AI Humanizer -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Humanizer') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">{{ __('Human-like content that connects.') }}
                                </p>
                            </div>
                        </a>

                        <!-- 2. AI Proofread -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-blue-50 text-[#2271b1]">
                                <i class="fa-solid fa-spell-check"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Proofread') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Grammar, style & academic clarity.') }}</p>
                            </div>
                        </a>

                        <!-- 3. AI Transcription -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-microphone-lines"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Voice Transcription') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Accurate transcription in minutes.') }}</p>
                            </div>
                        </a>

                        <!-- 4. Socius AI -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-sky-50 text-[#0284C7]">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Socius AI') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">{{ __('Your AI research assistant.') }}</p>
                            </div>
                        </a>

                        <!-- 5. Proposal Generation -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-purple-50 text-purple-600">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Proposal Generation') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Draft complete research proposals fast.') }}</p>
                            </div>
                        </a>

                        <!-- 6. Report Generation -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-amber-50 text-amber-600">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Report Generation') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Automated APA/IEEE formatted reports.') }}</p>
                            </div>
                        </a>

                        <!-- 7. Plagiarism Checker -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-rose-50 text-rose-600">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Plagiarism Checker') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Verify originality & academic integrity.') }}</p>
                            </div>
                        </a>

                        <!-- 8. Paid Surveys -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-teal-50 text-teal-600">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Paid Surveys') }}
                                </h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Field data collection with rewards.') }}</p>
                            </div>
                        </a>

                        <!-- 9. Quantitative Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-blue-50 text-[#2271b1]">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Quantitative Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Descriptive statistics, charts & tables.') }}</p>
                            </div>
                        </a>

                        <!-- 10. Qualitative Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-orange-50 text-orange-600">
                                <i class="fa-solid fa-tags"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Qualitative Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Structured thematic coding & insights.') }}</p>
                            </div>
                        </a>

                        <!-- 11. Inferential Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card">
                            <div class="feature-marquee-icon bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Inferential Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Hypothesis testing, ANOVA & regression.') }}</p>
                            </div>
                        </a>

                        <!-- Loop 2 (for seamless continuous infinite scroll) -->
                        <!-- 1. AI Humanizer -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-wand-magic-sparkles"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Humanizer') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">{{ __('Human-like content that connects.') }}
                                </p>
                            </div>
                        </a>

                        <!-- 2. AI Proofread -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-blue-50 text-[#2271b1]">
                                <i class="fa-solid fa-spell-check"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Proofread') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Grammar, style & academic clarity.') }}</p>
                            </div>
                        </a>

                        <!-- 3. AI Transcription -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-emerald-50 text-emerald-600">
                                <i class="fa-solid fa-microphone-lines"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Voice Transcription') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Accurate transcription in minutes.') }}</p>
                            </div>
                        </a>

                        <!-- 4. Socius AI -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-sky-50 text-[#0284C7]">
                                <i class="fa-solid fa-brain"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Socius AI') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">{{ __('Your AI research assistant.') }}</p>
                            </div>
                        </a>

                        <!-- 5. Proposal Generation -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-purple-50 text-purple-600">
                                <i class="fa-solid fa-file-signature"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Proposal Generation') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Draft complete research proposals fast.') }}</p>
                            </div>
                        </a>

                        <!-- 6. Report Generation -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-amber-50 text-amber-600">
                                <i class="fa-solid fa-file-lines"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Report Generation') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Automated APA/IEEE formatted reports.') }}</p>
                            </div>
                        </a>

                        <!-- 7. Plagiarism Checker -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-rose-50 text-rose-600">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Plagiarism Checker') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Verify originality & academic integrity.') }}</p>
                            </div>
                        </a>

                        <!-- 8. Paid Surveys -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-teal-50 text-teal-600">
                                <i class="fa-solid fa-wallet"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">{{ __('Paid Surveys') }}
                                </h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Field data collection with rewards.') }}</p>
                            </div>
                        </a>

                        <!-- 9. Quantitative Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-blue-50 text-[#2271b1]">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Quantitative Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Descriptive statistics, charts & tables.') }}</p>
                            </div>
                        </a>

                        <!-- 10. Qualitative Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-orange-50 text-orange-600">
                                <i class="fa-solid fa-tags"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Qualitative Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Structured thematic coding & insights.') }}</p>
                            </div>
                        </a>

                        <!-- 11. Inferential Analysis -->
                        <a href="{{ route('login') }}" class="feature-marquee-card" aria-hidden="true">
                            <div class="feature-marquee-icon bg-indigo-50 text-indigo-600">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                            <div class="overflow-hidden">
                                <h4 class="font-extrabold text-slate-900 text-sm mb-0.5 truncate">
                                    {{ __('Inferential Analysis') }}</h4>
                                <p class="text-xs text-slate-500 m-0 truncate">
                                    {{ __('Hypothesis testing, ANOVA & regression.') }}</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Access Portal Section -->
        <section class="py-16 bg-white border-b border-slate-200 reveal" id="get-started">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span
                        class="text-xs font-extrabold text-[#2271b1] uppercase tracking-wider block mb-2">{{ __('Access Portal') }}</span>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-3">{{ __('Get Started') }}</h2>
                    <p class="text-slate-600 text-sm sm:text-base">
                        {{ __('Select your account type to sign in or register') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 reveal-group">
                    <!-- Organization -->
                    <a href="{{ route('login.role', ['role' => 'organization']) }}" class="role-card-light reveal-item">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-2">{{ __('Organization') }}</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed m-0">
                            {{ __('For companies and institutions managing large-scale research surveys') }}
                        </p>
                    </a>

                    <!-- Researcher -->
                    <a href="{{ route('login.role', ['role' => 'independent']) }}" class="role-card-light reveal-item">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-2">{{ __('Researcher') }}</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed m-0">
                            {{ __('For academicians and practitioners.') }}
                        </p>
                    </a>

                    <!-- Respondent -->
                    <a href="{{ route('login.role', ['role' => 'respondent']) }}" class="role-card-light reveal-item">
                        <div class="role-icon-box">
                            <i class="fa-solid fa-clipboard-check"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-2">{{ __('Respondent') }}</h3>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed m-0">
                            {{ __('Share feedback and earn') }}
                        </p>
                    </a>
                </div>

                <!-- Public Survey Hub Banner -->
                <div
                    class="mt-8 bg-slate-900 rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-xl reveal">
                    <div class="flex items-center gap-5">
                        <div
                            class="w-14 h-14 rounded-2xl bg-blue-500/20 text-[#72aee6] flex items-center justify-center text-2xl flex-shrink-0 border border-blue-400/30">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-white mb-1">{{ __('Public Survey Hub') }}</h3>
                            <p class="text-xs sm:text-sm text-slate-300 m-0">
                                {{ __('Browse publicly hosted research studies, contribute insights and get rewarded instantly.') }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('surveys.public') }}"
                        class="btn-kd-primary flex-shrink-0 w-full sm:w-auto justify-center">
                        <span>{{ __('Browse all surveys') }}</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </a>
                </div>
            </div>
        </section>

        <!-- 3. Research & Field Studies Suite -->
        <section class="py-16 lg:py-20 bg-slate-50 border-b border-slate-200 reveal" id="research-studies">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-14">
                    <span
                        class="text-xs font-extrabold text-[#2271b1] uppercase tracking-wider block mb-2">{{ __('Research & Fieldwork') }}</span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight mb-3">
                        {{ __('KDAnalytiks for Conducting Studies') }}
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base leading-relaxed">
                        {{ __('Streamline study design, multi-modal data collection, and empirical analysis from initial proposal to publication-ready synthesis.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 reveal-group">
                    <!-- Study Card 1 -->
                    <div class="me-card-light reveal-item">
                        <div
                            class="w-12 h-12 rounded-xl bg-blue-50 text-[#2271b1] flex items-center justify-center text-xl mb-5">
                            <i class="fa-solid fa-pen-ruler"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-4">{{ __('Rigorous study & tool design') }}</h3>
                        <ul class="space-y-3 p-0 m-0 list-none">
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Design structured questionnaires with skip logic, Likert matrices, and field validation.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Ensure methodological alignment across research objectives, hypotheses, and variables.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Calculate statistically sound sample sizes using Yamane and Cochran formulas.') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Study Card 2 -->
                    <div class="me-card-light reveal-item">
                        <div
                            class="w-12 h-12 rounded-xl bg-blue-50 text-[#2271b1] flex items-center justify-center text-xl mb-5">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-4">{{ __('Mixed-methods data collection') }}
                        </h3>
                        <ul class="space-y-3 p-0 m-0 list-none">
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Integrate quantitative web surveys, field enumerator mobile forms, and offline data sync.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Conduct and transcribe qualitative in-depth interviews, KIIs, and focus groups.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Track respondent demographics and stratified cluster quotas in real time.') }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Study Card 3 -->
                    <div class="me-card-light reveal-item">
                        <div
                            class="w-12 h-12 rounded-xl bg-blue-50 text-[#2271b1] flex items-center justify-center text-xl mb-5">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-4">
                            {{ __('Empirical analysis & synthesis') }}
                        </h3>
                        <ul class="space-y-3 p-0 m-0 list-none">
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Generate descriptive summaries, cross-tabulations, and inferential regression diagnostics.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Synthesize theoretical frameworks, empirical literature reviews, and gaps matrices.') }}</span>
                            </li>
                            <li class="flex items-start gap-2.5 text-xs sm:text-sm text-slate-600">
                                <i class="fa-solid fa-circle-check text-[#2271b1] text-xs mt-1 flex-shrink-0"></i>
                                <span>{{ __('Export submission-ready academic manuscripts and research reports in APA 7th / IEEE formats.') }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- 4. Dedicated Socius AI Spotlight (Side-by-Side Layout) -->
        <section class="py-16 lg:py-20 bg-white border-b border-slate-200 reveal" id="socius">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12 items-center">
                    <!-- Left: Copy & 4 Capabilities -->
                    <div class="space-y-6">
                        <div class="inline-flex items-center gap-2 font-black text-lg text-slate-900">
                            <i class="fa-solid fa-comments text-[#2271b1]"></i>
                            <span>Socius AI</span>
                        </div>

                        <div>
                            <span
                                class="text-xs font-black text-[#2271b1] uppercase tracking-wider block mb-1">{{ __('AI RESEARCH ASSISTANT') }}</span>
                            <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">
                                Your Guide
                                <span class="text-[#2271b1] block">Your Research</span>
                            </h2>
                        </div>

                        <p class="text-sm sm:text-base text-slate-600 leading-relaxed">
                            {{ __('Socius AI helps you develop literature, refine ideas and run smarter analyses') }}
                        </p>

                        <!-- 4 Capabilities List -->
                        <div class="space-y-4 pt-1">
                            <div class="flex items-start gap-3.5">
                                <div
                                    class="w-9 h-9 rounded-lg bg-blue-50 text-[#2271b1] flex items-center justify-center text-sm flex-shrink-0">
                                    <i class="fa-solid fa-comment-dots"></i>
                                </div>
                                <div>
                                    <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Ask Anything') }}</h5>
                                    <p class="text-xs text-slate-500 m-0">
                                        {{ __('Get clear answers and explanations about your topic.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3.5">
                                <div
                                    class="w-9 h-9 rounded-lg bg-blue-50 text-[#2271b1] flex items-center justify-center text-sm flex-shrink-0">
                                    <i class="fa-solid fa-book-open"></i>
                                </div>
                                <div>
                                    <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Explore Literature') }}
                                    </h5>
                                    <p class="text-xs text-slate-500 m-0">
                                        {{ __('Find, summarize and understand relevant research.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3.5">
                                <div
                                    class="w-9 h-9 rounded-lg bg-blue-50 text-[#2271b1] flex items-center justify-center text-sm flex-shrink-0">
                                    <i class="fa-solid fa-chart-line"></i>
                                </div>
                                <div>
                                    <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Run Analyses') }}</h5>
                                    <p class="text-xs text-slate-500 m-0">
                                        {{ __('Support your work with data-driven analysis.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-start gap-3.5">
                                <div
                                    class="w-9 h-9 rounded-lg bg-blue-50 text-[#2271b1] flex items-center justify-center text-sm flex-shrink-0">
                                    <i class="fa-solid fa-shield-halved"></i>
                                </div>
                                <div>
                                    <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Reliable & Private') }}
                                    </h5>
                                    <p class="text-xs text-slate-500 m-0">
                                        {{ __('Your research stays secure and confidential.') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-4 flex-wrap pt-2">
                            <a href="#get-started" class="btn-kd-primary">
                                <span>{{ __('Start a Conversation') }}</span>
                                <i class="fa-solid fa-arrow-right text-xs"></i>
                            </a>
                            <a href="{{ route('docs') }}"
                                class="text-[#2271b1] text-sm font-bold hover:underline flex items-center gap-1">
                                <span>{{ __('Learn more about Socius AI') }}</span>
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Right: Socius AI Desktop Window & Overlapping Mobile Chat Showcase (Side-by-Side) -->
                    <div class="w-full">
                        <div class="device-showcase-container">
                            <!-- Desktop Browser Window Mockup -->
                            <div class="browser-mockup-frame">
                                <div class="browser-header-bar">
                                    <div class="flex items-center gap-1.5">
                                        <div class="browser-dot bg-rose-500"></div>
                                        <div class="browser-dot bg-amber-400"></div>
                                        <div class="browser-dot bg-emerald-500"></div>
                                    </div>
                                    <div class="browser-url-pill">
                                        <i class="fa-solid fa-lock text-[9px] text-emerald-400"></i>
                                        <span>kdanalytiks.com/socius/chat</span>
                                    </div>
                                    <div class="w-12"></div>
                                </div>
                                <div class="browser-viewport-glass">
                                    <img src="{{ asset('images/platform/socius-preview.png') }}"
                                        alt="Socius AI Research Assistant Studio" class="w-full h-auto object-contain block"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                    <!-- Visual Fallback -->
                                    <div class="placeholder-overlay flex-col items-center justify-center space-y-3 h-full bg-slate-950 text-white"
                                        style="display: none;">
                                        <i class="fa-solid fa-comments text-3xl text-indigo-400"></i>
                                        <p class="text-xs text-slate-300 font-bold m-0">{{ __('Socius AI Chat Workspace') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Overlapping Mobile Phone Mockup (Active Socius Chat) -->
                            <div class="phone-mockup-overlap">
                                <div class="phone-chassis">
                                    <div class="phone-dynamic-island"></div>
                                    <div class="phone-screen-glass">
                                        <img src="{{ asset('images/platform/socius-chat-mobile.png') }}"
                                            alt="Socius AI Live Chat Thread" class="w-full h-full object-cover object-top"
                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">

                                        <!-- Visual Fallback -->
                                        <div class="placeholder-overlay flex-col items-center justify-center space-y-2 h-full bg-slate-950 text-white p-2"
                                            style="display: none;">
                                            <i class="fa-solid fa-brain text-xl text-indigo-400"></i>
                                            <p class="text-[9px] text-slate-300 font-bold m-0">{{ __('Socius AI Chat') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4 Pillars Trust Bar -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 bg-slate-50 border border-slate-200 rounded-2xl p-6 sm:p-8 mt-12 reveal">
                    <div class="flex items-start gap-3.5">
                        <i class="fa-solid fa-comment-dots text-[#2271b1] text-lg mt-0.5"></i>
                        <div>
                            <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Research Assistant') }}</h5>
                            <p class="text-xs text-slate-500 m-0 leading-relaxed">
                                {{ __('Socius AI understands your questions and research context.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <i class="fa-solid fa-book text-[#2271b1] text-lg mt-0.5"></i>
                        <div>
                            <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Knowledge Base') }}</h5>
                            <p class="text-xs text-slate-500 m-0 leading-relaxed">
                                {{ __('Built on trusted academic sources to support your studies.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <i class="fa-solid fa-bolt text-[#2271b1] text-lg mt-0.5"></i>
                        <div>
                            <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Save Time') }}</h5>
                            <p class="text-xs text-slate-500 m-0 leading-relaxed">
                                {{ __('From topic exploration to final analysis in minutes.') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3.5">
                        <i class="fa-solid fa-lock text-[#2271b1] text-lg mt-0.5"></i>
                        <div>
                            <h5 class="font-extrabold text-sm text-slate-900 mb-0.5">{{ __('Private & Secure') }}</h5>
                            <p class="text-xs text-slate-500 m-0 leading-relaxed">
                                {{ __('Your proprietary research data and chats remain private.') }}
                            </p>
                        </div>
                    </div>
                </div>
        </section>

        <!-- 5. Lifecycle Workflow Pillars (01 to 04) -->
        <section class="py-16 bg-slate-50 border-b border-slate-200 reveal" id="pillars">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="max-w-3xl mb-12">
                    <span
                        class="text-xs font-extrabold text-[#2271b1] uppercase tracking-wider block mb-2">{{ __('The Workflow') }}</span>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-3">
                        {{ __('One workspace, the whole research lifecycle') }}
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base">
                        {{ __('Every stage, from questionnaire design to the final publication report, happens seamlessly inside KDAnalytiks without disconnected spreadsheets.') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 reveal-group">
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm reveal-item">
                        <span
                            class="text-xs font-mono font-bold text-[#2271b1] bg-blue-50 px-2.5 py-1 rounded-md mb-4 inline-block">01</span>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-3">{{ __('Design') }}</h3>
                        <ul class="space-y-2 p-0 m-0 list-none text-xs sm:text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('AI Questionnaire Architect drafts surveys from a prompt') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Drag-and-drop builder with 12+ question types') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Conditional branching & dynamic validation rules') }}
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm reveal-item">
                        <span
                            class="text-xs font-mono font-bold text-[#2271b1] bg-blue-50 px-2.5 py-1 rounded-md mb-4 inline-block">02</span>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-3">{{ __('Collect') }}</h3>
                        <ul class="space-y-2 p-0 m-0 list-none text-xs sm:text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Distribute via link, QR code, email, or embedded widget') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Built-in audio transcription for qualitative field interviews') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Integrated incentive wallet with automated fraud detection') }}
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm reveal-item">
                        <span
                            class="text-xs font-mono font-bold text-[#2271b1] bg-blue-50 px-2.5 py-1 rounded-md mb-4 inline-block">03</span>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-3">{{ __('Analyze') }}</h3>
                        <ul class="space-y-2 p-0 m-0 list-none text-xs sm:text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Descriptive statistics, t-tests, ANOVA and linear regression') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Qualitative coding into structured thematic frameworks') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Query live datasets in natural plain language with Socius AI') }}
                            </li>
                        </ul>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm reveal-item">
                        <span
                            class="text-xs font-mono font-bold text-[#2271b1] bg-blue-50 px-2.5 py-1 rounded-md mb-4 inline-block">04</span>
                        <h3 class="font-extrabold text-lg text-slate-900 mb-3">{{ __('Report') }}</h3>
                        <ul class="space-y-2 p-0 m-0 list-none text-xs sm:text-sm text-slate-600">
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Human Voice Guard preserves original intellectual style') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('Auto-formatted APA and IEEE citations and tables') }}
                            </li>
                            <li class="flex items-start gap-2"><span
                                    class="w-1.5 h-1.5 rounded-full bg-[#2271b1] mt-1.5 flex-shrink-0"></span>
                                {{ __('One-click export to Word, PDF, or direct WordPress publish') }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>



        <!-- 7. Platform Comparison Matrix -->
        <section class="py-16 bg-white border-b border-slate-200 reveal">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span
                        class="text-xs font-extrabold text-[#2271b1] uppercase tracking-wider block mb-2">{{ __('The Advantage') }}</span>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-3">
                        {{ __('Unified Platform vs. Fragmented Tools') }}
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base">
                        {{ __('See why research teams choose KDAnalytiks over juggling disconnected software.') }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="comp-light-table">
                        <thead>
                            <tr>
                                <th>{{ __('Capability') }}</th>
                                <th class="text-[#2271b1] font-black">{{ __('KDAnalytiks') }}</th>
                                <th class="text-slate-500">{{ __('Basic Form Builders') }}</th>
                                <th class="text-slate-500">{{ __('Legacy Stats Packages') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-bold text-slate-900">{{ __('All-in-One Lifecycle (Build to Report)') }}</td>
                                <td class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1.5"></i>
                                    {{ __('Yes, Seamless') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i>
                                    {{ __('Collection Only') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i>
                                    {{ __('Analysis Only') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="font-bold text-slate-900">{{ __('AI Dataset Querying & Copilot') }}</td>
                                <td class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1.5"></i>
                                    {{ __('Socius AI Built-in') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i> {{ __('No') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i>
                                    {{ __('Manual Syntax Only') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="font-bold text-slate-900">{{ __('Audio Transcription Studio') }}</td>
                                <td class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1.5"></i>
                                    {{ __('Direct Field Speech-to-Text') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i> {{ __('No') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i> {{ __('No') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="font-bold text-slate-900">{{ __('Automated APA & IEEE Formatted Output') }}</td>
                                <td class="text-emerald-600 font-bold"><i class="fa-solid fa-circle-check mr-1.5"></i>
                                    {{ __('Instant Chapter 4 & 5') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i>
                                    {{ __('Raw CSV Only') }}
                                </td>
                                <td class="text-rose-500"><i class="fa-solid fa-circle-xmark mr-1.5"></i>
                                    {{ __('Manual Formatting Needed') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- 7. Expanded Frequently Asked Questions (FAQ) Section -->
        <section class="py-16 lg:py-20 bg-slate-50 border-b border-slate-200 reveal" id="faq" x-data="{ openFaq: null }">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-2xl mx-auto mb-12">
                    <span
                        class="text-xs font-extrabold text-[#2271b1] uppercase tracking-wider block mb-2">{{ __('Got Questions?') }}</span>
                    <h2 class="text-3xl font-black text-slate-900 tracking-tight mb-3">
                        {{ __('Frequently Asked Questions') }}
                    </h2>
                    <p class="text-slate-600 text-sm sm:text-base">
                        {{ __('Everything you need to know about the KDAnalytiks ecosystem and research workflows.') }}
                    </p>
                </div>

                <div class="space-y-3">
                    <!-- FAQ 1 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 1 ? null : 1">
                            <span>{{ __('How does KDAnalytiks simplify data analysis for non-statisticians?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 1 }"></i>
                        </button>
                        <div x-show="openFaq === 1" x-cloak class="faq-accordion-content">
                            {{ __('KDAnalytiks automates statistical calculation pipelines. Instead of memorizing complex syntax or formulas, our automated analysis engine calculates descriptives, inferential tests, p-values and correlation coefficients automatically, presenting findings in plain language and publication-ready tables.') }}
                        </div>
                    </div>

                    <!-- FAQ 2 -->


                    <!-- FAQ 3 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 3 ? null : 3">
                            <span>{{ __('How does Socius AI differ from general tools like ChatGPT?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 3 }"></i>
                        </button>
                        <div x-show="openFaq === 3" x-cloak class="faq-accordion-content">
                            {{ __('Unlike generic chat models, Socius AI connects directly to your live survey datasets and your private library of uploaded literature PDFs. It runs real-time statistical tests (ANOVA, t-tests, Pearson correlations) on your exact variables and grounds all synthesis in verifiable citations.') }}
                        </div>
                    </div>

                    <!-- FAQ 4 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 4 ? null : 4">
                            <span>{{ __('How does the Humanizer preserve my authentic research voice?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 4 }"></i>
                        </button>
                        <div x-show="openFaq === 4" x-cloak class="faq-accordion-content">
                            {{ __('The Humanizer removes robotic phrasing, repetitive transitions and formulaic AI patterns. It refines syntax to mirror natural human academic prose while rigorously preserving your original data findings, hypotheses and citations.') }}
                        </div>
                    </div>

                    <!-- FAQ 5 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 5 ? null : 5">
                            <span>{{ __('How does the Audio Transcription Studio handle field recordings?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 5 }"></i>
                        </button>
                        <div x-show="openFaq === 5" x-cloak class="faq-accordion-content">
                            {{ __('You can upload recorded audio files (WAV, MP3, M4A) or dictate directly from the field. KDAnalytiks automatically transcribes speech with speaker diarization and integrates the text directly into your qualitative thematic coding studio.') }}
                        </div>
                    </div>

                    <!-- FAQ 6 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 6 ? null : 6">
                            <span>{{ __('Is my proprietary research data secure and private?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 6 }"></i>
                        </button>
                        <div x-show="openFaq === 6" x-cloak class="faq-accordion-content">
                            {{ __('Yes. All workspaces are multi-tenant isolated and encrypted with 256-bit standards. Your raw datasets, uploaded literature documents and AI conversations are private to your account and never used to train public models.') }}
                        </div>
                    </div>

                    <!-- FAQ 7 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 7 ? null : 7">
                            <span>{{ __('Can I export raw datasets and reports to external software?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 7 }"></i>
                        </button>
                        <div x-show="openFaq === 7" x-cloak class="faq-accordion-content">
                            {{ __('Absolutely. You can export clean datasets in CSV and Excel formats, download formatted reports in Word (.docx) and PDF, or publish findings directly to WordPress websites via REST API.') }}
                        </div>
                    </div>

                    <!-- FAQ 8 -->
                    <div class="faq-accordion-card">
                        <button type="button" class="faq-accordion-btn" @click="openFaq = openFaq === 8 ? null : 8">
                            <span>{{ __('What survey distribution channels and fraud protections are supported?') }}</span>
                            <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform"
                                :class="{ 'rotate-180': openFaq === 8 }"></i>
                        </button>
                        <div x-show="openFaq === 8" x-cloak class="faq-accordion-content">
                            {{ __('Surveys can be shared via direct link, QR code, email campaigns, or published onto our Public Survey Hub. Built-in IP validation, completion-time monitoring and respondent wallet reward verification ensure clean, uncompromised sample integrity.') }}
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 8. Bottom Conversion CTA Banner -->
        <section class="bg-slate-900 py-16 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-8">
                    <div class="space-y-2 text-center lg:text-left">
                        <h2 class="text-2xl sm:text-3xl font-black text-white m-0">
                            {{ __('Ready to Streamline Your Research Workflows?') }}
                        </h2>
                        <p class="text-sm sm:text-base text-slate-300 m-0">
                            {{ __('Design, collect, analyze and report — all in one unified workspace.') }}
                        </p>
                    </div>
                    <div
                        class="flex items-center justify-center lg:justify-end gap-3 sm:gap-4 flex-wrap flex-shrink-0 w-full lg:w-auto">
                        <a href="#get-started" class="btn-kd-primary w-full sm:w-auto justify-center">
                            <span>{{ __('Get Started Free') }}</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                        <a href="{{ route('contact') }}"
                            class="px-6 py-3 rounded-xl font-bold text-sm bg-slate-800 text-white border border-slate-700 hover:bg-slate-700 hover:border-slate-500 hover:text-white transition-all shadow-sm flex items-center justify-center gap-2 w-full sm:w-auto">
                            <i class="fa-solid fa-headset text-slate-400"></i>
                            <span>{{ __('Contact Support') }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        // Scroll-reveal: adds .is-visible to .reveal / .reveal-group targets as they enter the viewport
        document.body.classList.add('js-ready');

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -30px 0px' });

            document.querySelectorAll('.reveal, .reveal-group').forEach((el) => io.observe(el));
        } else {
            document.querySelectorAll('.reveal, .reveal-group').forEach((el) => el.classList.add('is-visible'));
        }
    </script>
@endpush