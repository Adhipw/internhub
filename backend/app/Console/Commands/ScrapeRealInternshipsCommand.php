<?php

namespace App\Console\Commands;

use App\Services\InternshipScraperService;
use Illuminate\Console\Command;

class ScrapeRealInternshipsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'internships:scrape {--limit=15 : Number of items to fetch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape real internships from public API (Kalibrr) and sync to database';

    /**
     * Execute the console command.
     */
    public function handle(InternshipScraperService $scraperService)
    {
        $this->info('Starting internship synchronization...');

        $limit = (int) $this->option('limit');
        $this->line("Fetching up to {$limit} real internships...");

        $savedCount = $scraperService->fetchRealInternships($limit);

        if ($savedCount > 0) {
            $this->info("Successfully synced {$savedCount} real internships and companies!");
        } else {
            $this->warn('No internships were synced. Please check logs for details.');
        }

        return self::SUCCESS;
    }
}
