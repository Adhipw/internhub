<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Internship;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InternshipScraperService
{
    private const KALIBRR_API_URL = 'https://www.kalibrr.com/api/job_board/search';

    /**
     * Fetch real internships from public API
     */
    public function fetchRealInternships(int $limit = 15): int
    {
        try {
            $response = Http::withoutVerifying()->timeout(30)->get(self::KALIBRR_API_URL, [
                'text' => 'magang',
                'country' => 'Indonesia',
                'limit' => $limit,
            ]);

            if ($response->failed()) {
                Log::error('Failed to fetch from Kalibrr API', ['status' => $response->status()]);

                return 0;
            }

            $data = $response->json();
            $jobs = $data['jobs'] ?? [];

            $savedCount = 0;

            foreach ($jobs as $job) {
                if ($this->processJob($job)) {
                    $savedCount++;
                }
            }

            return $savedCount;

        } catch (\Exception $e) {
            Log::error('Error in InternshipScraperService', ['message' => $e->getMessage()]);

            return 0;
        }
    }

    private function processJob(array $job): bool
    {
        try {
            // Process Company
            $companyData = $job['company_info'] ?? $job['company'] ?? null;
            if (! $companyData || empty($companyData['name'])) {
                return false; // Skip if no company info
            }

            $companyName = $companyData['name'];
            $companySlug = Str::slug($companyName).'-'.substr(md5($companyData['code'] ?? $companyName), 0, 5);

            $company = Company::firstOrCreate(
                ['name' => $companyName],
                [
                    'slug' => $companySlug,
                    'description' => strip_tags($companyData['description'] ?? 'Perusahaan inovatif di Indonesia.'),
                    'logo_url' => $companyData['logo'] ?? null,
                    'industry' => $companyData['industry'] ?? 'Technology',
                    'is_verified' => true,
                ]
            );

            // Process Internship
            $title = $job['name'] ?? 'Internship Program';
            $baseSlug = Str::slug($title.'-'.$companyName);
            $slug = Internship::where('slug', $baseSlug)->exists() ? $baseSlug.'-'.Str::random(4) : $baseSlug;

            // Mapping Data
            $wfh = $job['is_work_from_home'] ?? false;
            $type = $wfh ? 'WFH' : 'Office';

            $city = $job['google_location']['address_components']['city'] ??
                    $job['google_location']['address_components']['region'] ??
                    'Indonesia';

            $fullAddress = $job['google_location']['address_components']['address_line_1'] ?? null;
            $location = $fullAddress ? $fullAddress.', '.$city : $city;

            $deadlineAt = isset($job['application_end_date']) ? Carbon::parse($job['application_end_date']) : now()->addMonths(2);

            $externalUrl = 'https://www.kalibrr.com/c/'.($companyData['code'] ?? $companySlug).'/jobs/'.$job['id'].'/'.$job['slug'];

            $internship = Internship::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'title' => $title,
                ],
                [
                    'slug' => $slug,
                    'description' => $job['description'] ?? 'Kesempatan magang terbaik untuk mahasiswa dan fresh graduate.',
                    'requirements' => $job['qualifications'] ?? '<ul><li>Mahasiswa tingkat akhir atau fresh graduate</li><li>Memiliki kemauan belajar yang tinggi</li></ul>',
                    'benefits' => '<ul><li>Pengalaman kerja nyata</li><li>Sertifikat magang</li><li>Uang saku (tergantung kebijakan perusahaan)</li></ul>',
                    'type' => $type,
                    'location' => $location,
                    'deadline_at' => $deadlineAt,
                    'status' => 'published',
                    'is_external' => true,
                    'external_source' => 'kalibrr',
                    'external_id' => (string) $job['id'],
                    'external_url' => $externalUrl,
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::warning('Failed to process a job from Kalibrr API', ['message' => $e->getMessage()]);

            return false;
        }
    }
}
