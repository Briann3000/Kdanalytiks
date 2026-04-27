<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$surveys = \App\Models\Survey::all(['id', 'export_logo_url']);
foreach ($surveys as $s) {
    echo "ID: {$s->id}, Logo: " . ($s->export_logo_url ?? 'NULL') . "\n";
}
