<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Academic Referencing Styles
    |--------------------------------------------------------------------------
    |
    | Supported academic referencing styles and their configurations.
    |
    */

    'styles' => [
        'apa7' => [
            'name' => 'APA 7th Edition',
            'font' => 'Times New Roman',
            'font_size' => 12,
            'line_spacing' => 2.0,
            'margin' => 1, // inches
        ],
        'mla9' => [
            'name' => 'MLA 9th Edition',
            'font' => 'Times New Roman',
            'font_size' => 12,
            'line_spacing' => 2.0,
            'margin' => 1,
        ],
        'harvard' => [
            'name' => 'Harvard',
            'font' => 'Arial',
            'font_size' => 11,
            'line_spacing' => 1.5,
            'margin' => 1,
        ],
    ],

    'default_style' => 'apa7',
];
