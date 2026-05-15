<?php

return [
    'max_attachment_mb' => (int) env('SOCIUS_MAX_ATTACHMENT_MB', 10),
    'attachment_ttl_days' => (int) env('SOCIUS_ATTACHMENT_TTL_DAYS', 7),
    'context_sample_limit' => (int) env('SOCIUS_CONTEXT_SAMPLE_LIMIT', 5),
    'crosstab_pair_limit' => (int) env('SOCIUS_CROSSTAB_PAIR_LIMIT', 3),
    'storage_prefix' => env('SOCIUS_STORAGE_PREFIX', 'socius'),
    'supported_extensions' => ['pdf', 'csv', 'txt', 'docx'],
];
