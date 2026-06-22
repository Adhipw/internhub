<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class InternshipSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Fetch Real Internships
        Artisan::call('internships:scrape', [
            '--limit' => 30,
        ]);
    }
}
