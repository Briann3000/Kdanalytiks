<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Enums\SurveyCategory;

$categories = DB::table('surveys')->distinct()->pluck('category')->toArray();
echo "Found categories: " . implode(', ', $categories) . "\n";

// Automap 'marketing' to 'market_research' if it exists
DB::table('surveys')->where('category', 'marketing')->update(['category' => 'market_research']);
DB::table('surveys')->where('category', 'Marketing')->update(['category' => 'market_research']);
DB::table('surveys')->where('category', 'General')->update(['category' => 'business']);
DB::table('surveys')->where('category', 'Product')->update(['category' => 'market_research']);
DB::table('surveys')->where('category', 'Health')->update(['category' => 'social']);
DB::table('surveys')->where('category', 'Political')->update(['category' => 'polls']);
DB::table('surveys')->where('category', 'Other')->update(['category' => 'business']);
DB::table('surveys')->where('category', 'Academic')->update(['category' => 'academic']);
DB::table('surveys')->where('category', 'Polls')->update(['category' => 'polls']);
DB::table('surveys')->where('category', 'Social')->update(['category' => 'social']);
DB::table('surveys')->where('category', 'Business')->update(['category' => 'business']);
DB::table('surveys')->where('category', 'Feasibility')->update(['category' => 'feasibility']);

echo "Categories updated.\n";
