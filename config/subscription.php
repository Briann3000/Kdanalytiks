<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Early Access / Launch Mode Toggle
    |--------------------------------------------------------------------------
    | When true:
    | - Hides Enterprise tiers from pricing tables (collapsing to 2 tiers: Free & Pro).
    | - Relaxes free limits across surveys, AI, transcription, and exports.
    | When false:
    | - Restores original 3-tier layout (Free, Pro, Enterprise).
    | - Enforces all original strict limits immediately.
    */
    'early_access_mode' => (bool) env('EARLY_ACCESS_MODE', true),

    'early_access' => [
        'hide_enterprise' => true,

        // Relaxed limits for Free tier
        'max_surveys' => 10,                 // Up from 3
        'max_responses_per_survey' => 1000,   // Up from 100
        'ai_analyses_monthly' => 50,          // Up from 2-5
        'transcription_count' => 5,           // Up from 1
        'proofread_count' => 5,               // Up from 1
        'plagiarism_scans' => 10,             // Up from 3
        'plagiarism_word_limit' => 5000,      // Up from 1,500
        'allow_excel_export' => true,         // Free tier gets Excel (.xlsx)
        'allow_json_export' => true,          // Free tier gets JSON
        'allow_full_report_preview' => true,  // Full DOCX generation (KD branding retained)
        'org_free_seats' => 5,                // Up from 2
    ],
];
