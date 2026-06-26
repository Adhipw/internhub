<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$response = Illuminate\Support\Facades\Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
])->withOptions(['verify' => false])->get('https://nominatim.openstreetmap.org/search', [
    'q' => 'Jakarta',
    'format' => 'json',
    'limit' => 1,
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
