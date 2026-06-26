<?php

namespace App\Console\Commands;

use App\Models\Internship;
use App\Services\GeocodingService;
use Illuminate\Console\Command;

class GeocodeInternships extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'internships:geocode';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode existing internships that have a location but no latitude/longitude.';

    /**
     * Execute the console command.
     */
    public function handle(GeocodingService $geocodingService)
    {
        $internships = Internship::whereNotNull('location')
            ->where(function ($query) {
                $query->whereNull('latitude')
                      ->orWhereNull('longitude');
            })
            ->get();

        if ($internships->isEmpty()) {
            $this->info('No internships need geocoding.');
            return;
        }

        $this->info("Found {$internships->count()} internships to geocode.");

        $bar = $this->output->createProgressBar($internships->count());
        $bar->start();

        foreach ($internships as $internship) {
            $coords = $geocodingService->geocode($internship->location);
            
            // Fallback: If failed and location has commas, try the last part (usually city/province)
            if (!$coords && str_contains($internship->location, ',')) {
                $parts = explode(',', $internship->location);
                $fallbackLocation = trim(end($parts));
                $coords = $geocodingService->geocode($fallbackLocation);
            }
            
            if ($coords) {
                Internship::withoutEvents(function () use ($internship, $coords) {
                    $internship->update([
                        'latitude' => $coords['latitude'],
                        'longitude' => $coords['longitude'],
                    ]);
                });
            } else {
                $this->error("\nFailed to geocode: {$internship->title} at {$internship->location}");
            }
            
            $bar->advance();
            
            // Respect Nominatim's usage policy (1 request per second)
            sleep(1);
        }

        $bar->finish();
        $this->info("\nGeocoding completed!");
    }
}
