<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    /**
     * Geocode an address to get latitude and longitude.
     * Uses OpenStreetMap Nominatim API.
     *
     * @param string $address
     * @return array|null Returns ['latitude' => ..., 'longitude' => ...] or null on failure.
     */
    public function geocode(string $address): ?array
    {
        if (empty(trim($address))) {
            return null;
        }

        // Clean up the address a bit (e.g., "Jakarta / Remote" -> "Jakarta")
        if (str_contains($address, '/')) {
            $parts = explode('/', $address);
            $address = trim($parts[0]);
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])
            ->withOptions(['verify' => false])
            ->timeout(10)
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

            if ($response->successful() && !empty($response->json())) {
                $data = $response->json()[0];
                return [
                    'latitude' => (float) $data['lat'],
                    'longitude' => (float) $data['lon'],
                ];
            }
            
            // Log if no results found
            if (empty($response->json())) {
                Log::warning("Geocoding returned no results for address: {$address}");
            }
        } catch (\Exception $e) {
            Log::error("Geocoding failed for address: {$address}. Error: " . $e->getMessage());
        }

        return null;
    }
}
