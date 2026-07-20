(function () {
    // Dynamic loader for Shepherd.js library and styles
    function loadShepherd(callback) {
        if (window.Shepherd) {
            callback();
            return;
        }

        // Inject Stylesheet
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/css/shepherd.css';
        document.head.appendChild(link);

        // Inject Custom Styles to match KDAnalytiks design
        const style = document.createElement('style');
        style.innerHTML = `
            .shepherd-element {
                background: #ffffff !important;
                border: 1px solid #f3f4f6 !important;
                border-radius: 1.5rem !important;
                box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1) !important;
                max-width: 340px !important;
                font-family: inherit !important;
                overflow: hidden !important;
            }
            .shepherd-content {
                padding: 1.25rem !important;
            }
            .shepherd-header {
                padding: 0 0 0.75rem 0 !important;
                margin-bottom: 0.75rem !important;
                background: #f3f4f6 !important;
                border-radius: 1rem !important;
            }
            .shepherd-text {
                margin-bottom: 1rem !important;
            }
            .shepherd-footer {
                display: flex !important;
                justify-content: flex-end !important;
                gap: 0.75rem !important;
                padding-top: 0.75rem !important;
                margin-top: 0.75rem !important;
                border-top: 1px solid #e5e7eb !important;
                flex-wrap: wrap !important;
            }
            .shepherd-title {
                font-size: 0.875rem !important;
                font-weight: 900 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                color: #111827 !important;
                margin-bottom: 0.5rem !important;
            }
            .shepherd-text {
                font-size: 0.75rem !important;
                font-weight: 600 !important;
                color: #4b5563 !important;
                line-height: 1.5 !important;
                margin-bottom: 1rem !important;
            }
            .shepherd-button {
                background-color: #4f46e5 !important;
                color: #ffffff !important;
                font-size: 0.65rem !important;
                font-weight: 800 !important;
                text-transform: uppercase !important;
                letter-spacing: 0.05em !important;
                padding: 0.5rem 1rem !important;
                border-radius: 0.75rem !important;
                transition: all 0.2s !important;
                border: none !important;
                cursor: pointer !important;
            }
            .shepherd-button:hover {
                background-color: #4338ca !important;
            }
            .shepherd-button-secondary {
                background-color: #f3f4f6 !important;
                color: #4b5563 !important;
            }
            .shepherd-button-secondary:hover {
                background-color: #e5e7eb !important;
                color: #1f2937 !important;
            }
            .shepherd-cancel-icon {
                color: #9ca3af !important;
                font-size: 1.25rem !important;
                font-weight: 300 !important;
                cursor: pointer !important;
            }
            .shepherd-cancel-icon:hover {
                color: #ef4444 !important;
            }
        `;
        document.head.appendChild(style);

        // Inject JavaScript
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/js/shepherd.min.js';
        script.onload = function () {
            callback();
        };
        document.head.appendChild(script);
    }

    // Helper: build step with optional element attachment
    function makeStep(tour, options) {
        const step = {
            id: options.id,
            title: options.title,
            text: options.text,
            buttons: options.buttons
        };
        // Only attach to element if it exists in the DOM
        if (options.attachTo) {
            const el = document.querySelector(options.attachTo.element);
            if (el) {
                step.attachTo = options.attachTo;
            }
        }
        return step;
    }

    // ── Dashboard Tour ─────────────────────────────────────────────
    window.startDashboardTour = function () {
        loadShepherd(function () {
            const tour = new Shepherd.Tour({
                useModalOverlay: true,
                defaultStepOptions: {
                    cancelIcon: { enabled: true },
                    scrollTo: { behavior: 'smooth', block: 'center' }
                }
            });

            tour.addStep({
                id: 'welcome',
                title: 'Welcome to your Dashboard!',
                text: 'This is your KDAnalytiks hub where you can view survey status, stats, and account balance at a glance.',
                buttons: [{ text: 'Next', action: tour.next }]
            });

            const createLink = document.querySelector('a[href*="surveys/create"]');
            if (createLink) {
                tour.addStep({
                    id: 'create',
                    attachTo: { element: 'a[href*="surveys/create"]', on: 'bottom' },
                    title: 'Create Surveys',
                    text: 'Click here to start building a new survey or questionnaire.',
                    buttons: [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Next', action: tour.next }
                    ]
                });
            }

            const grid = document.querySelector('.grid');
            if (grid) {
                tour.addStep({
                    id: 'grid',
                    attachTo: { element: '.grid', on: 'top' },
                    title: 'Active Projects',
                    text: 'View and manage all active, draft, and closed surveys along with response counters.',
                    buttons: [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Finish', action: tour.complete }
                    ]
                });
            } else {
                // No grid element — just finish after the welcome step if there was no create link
                const lastStep = tour.steps[tour.steps.length - 1];
                if (lastStep) {
                    lastStep.options.buttons = [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Finish', action: tour.complete }
                    ];
                }
            }

            tour.start();
        });
    };

    // ── Survey Builder Tour ────────────────────────────────────────
    // This tour is designed to run on /surveys/create or /surveys/{id}/edit
    window.startSurveyBuilderTour = function () {
        loadShepherd(function () {
            const tour = new Shepherd.Tour({
                useModalOverlay: true,
                defaultStepOptions: {
                    cancelIcon: { enabled: true },
                    scrollTo: { behavior: 'smooth', block: 'center' }
                }
            });

            function addBuilderStep(options) {
                const element = document.querySelector(options.selector);
                if (!element) {
                    return false;
                }

                tour.addStep({
                    id: options.id,
                    attachTo: { element: options.selector, on: options.on || 'bottom' },
                    title: options.title,
                    text: options.text,
                    buttons: options.buttons || [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Next', action: tour.next }
                    ]
                });

                return true;
            }

            tour.addStep({
                id: 'welcome',
                title: 'Survey Builder Tour',
                text: 'Welcome to the questionnaire builder. Let\'s walk through the key components.',
                buttons: [{ text: 'Next', action: tour.next }]
            });

            addBuilderStep({
                id: 'details',
                selector: '#builderDetailsBtn',
                title: 'Survey Settings',
                text: 'Open Details to set the survey title, description, category, access, branding, and reward settings before you publish.'
            });

            addBuilderStep({
                id: 'visual',
                selector: '#builderVisualBtn',
                title: 'Visual Builder',
                text: 'Visual mode is the easiest starting point for new users. Build the questionnaire with buttons and forms instead of writing schema by hand.'
            });

            addBuilderStep({
                id: 'code',
                selector: '#builderCodeBtn',
                title: 'Code Mode',
                text: 'Code mode exposes the raw survey schema. Use it when you need precise control or want to paste and edit a structured form definition.'
            });

            addBuilderStep({
                id: 'ai-architect',
                selector: '#builderAiArchitectBtn',
                title: 'AI Architect',
                text: 'AI Architect can generate a first draft of your survey from a prompt, which is helpful when you know the goal but not the exact question structure yet.'
            });

            addBuilderStep({
                id: 'preview',
                selector: '#builderPreviewBtn',
                title: 'Preview',
                text: 'Preview opens a respondent-style experience so you can test wording, layout, and overall flow before sharing the survey.'
            });

            addBuilderStep({
                id: 'library',
                selector: '#builderLibraryBtn',
                title: 'Library',
                text: 'Library gives you reusable templates and saved question blocks so you do not have to rebuild common sections from scratch.'
            });

            addBuilderStep({
                id: 'import',
                selector: '#builderImportBtn',
                title: 'Import',
                text: 'Import brings in an existing survey structure or supported document so you can keep editing it here.'
            });

            addBuilderStep({
                id: 'export',
                selector: '#builderExportBtn',
                title: 'Export',
                text: 'Export downloads the current survey schema for backup, collaboration, or moving work between projects.'
            });

            addBuilderStep({
                id: 'canvas',
                selector: '#questions-list, .questions-list, [id*="question"]',
                on: 'top',
                title: 'Question Canvas',
                text: 'This is the main canvas where your questions live. Add, edit, reorder, group, and review the questionnaire flow here.'
            });

            const saveStepAdded = addBuilderStep({
                id: 'save',
                selector: '#headerSaveBtn',
                title: 'Save Your Work',
                text: 'Use this Save button to store your draft changes. This is the main save action in the survey builder.',
                buttons: [
                    { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                    { text: 'Finish', action: tour.complete }
                ]
            });

            if (!saveStepAdded) {
                const last = tour.steps[tour.steps.length - 1];
                if (last && last.id !== 'welcome') {
                    last.options.buttons = [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Finish', action: tour.complete }
                    ];
                } else if (last && last.id === 'welcome') {
                    last.options.buttons = [{ text: 'Close', action: tour.complete }];
                }
            }

            tour.start();
        });
    };

    // ?????? Reports Dashboard Tour ???????????????????????????????????????????????????????????????????????????????????????????????????????????????
    // Designed to run on /surveys/{id}/report
    window.startReportsDashboardTour = function () {
        loadShepherd(function () {
            const tour = new Shepherd.Tour({
                useModalOverlay: true,
                defaultStepOptions: {
                    cancelIcon: { enabled: true },
                    scrollTo: { behavior: 'smooth', block: 'center' }
                }
            });

            tour.addStep({
                id: 'welcome',
                title: 'Analytics & Reports Tour',
                text: 'Let\'s explore the reporting dashboard — your real-time hub for response data and AI analysis.',
                buttons: [{ text: 'Next', action: tour.next }]
            });

            const chart = document.querySelector('canvas');
            if (chart) {
                tour.addStep({
                    id: 'charts',
                    attachTo: { element: 'canvas', on: 'top' },
                    title: 'Interactive Graphs',
                    text: 'Hover over chart elements to view exact response counts, percentages, and distributions.',
                    buttons: [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Next', action: tour.next }
                    ]
                });
            }

            const socius = document.querySelector('#socius-prompt-input, [id*="socius"]');
            if (socius) {
                tour.addStep({
                    id: 'socius',
                    attachTo: { element: socius, on: 'top' },
                    title: 'Socius AI Assistant',
                    text: 'Ask Socius to compile summaries, run crosstabs, or generate executive reports from your data.',
                    buttons: [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Finish', action: tour.complete }
                    ]
                });
            } else {
                const last = tour.steps[tour.steps.length - 1];
                if (last) {
                    last.options.buttons = [
                        { text: 'Back', action: tour.back, classes: 'shepherd-button-secondary' },
                        { text: 'Finish', action: tour.complete }
                    ];
                }
            }

            tour.start();
        });
    };

    // ── Auto-launch on page load via query param ───────────────────
    // tours.js is defer-loaded, so the 'load' event has already fired.
    // Run immediately and add a short delay so Alpine.js / async renders settle.
    (function autoLaunch() {
        const urlParams = new URLSearchParams(window.location.search);
        const tourName = urlParams.get('start_tour');

        if (!tourName) return;

        const path = window.location.pathname;
        let launcher = null;

        if (tourName === 'dashboard') {
            launcher = window.startDashboardTour;
        } else if (tourName === 'builder') {
            if (path.includes('/surveys/create') || path.match(/\/surveys\/\d+\/edit/)) {
                launcher = window.startSurveyBuilderTour;
            }
        } else if (tourName === 'reports') {
            if (path.match(/\/surveys\/\d+\/report/) || path.includes('/report')) {
                launcher = window.startReportsDashboardTour;
            }
        }

        if (launcher) {
            // Small delay lets Alpine.js/Livewire finish rendering DOM elements
            setTimeout(launcher, 600);
        }
    })();
})();
