<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

$response = Http::withoutVerifying()->get('https://www.kalibrr.com/api/job_board/search', ['text' => 'magang', 'limit' => 1]);
$loc = $response->json()['jobs'][0]['google_location'] ?? [];

print_r($loc);
