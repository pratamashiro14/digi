<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check t_design table
echo "=== T_DESIGN TABLE DATA ===\n";
$designs = \DB::table('t_design')->get();
echo "Total records: " . count($designs) . "\n";

if(count($designs) > 0) {
    echo "\nFirst record:\n";
    echo json_encode($designs[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

// Check using Model
echo "\n\n=== USING DESIGN MODEL ===\n";
$designsModel = \App\Models\Design::all();
echo "Total records via model: " . count($designsModel) . "\n";

if(count($designsModel) > 0) {
    echo "\nFirst design via model:\n";
    echo json_encode($designsModel[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
