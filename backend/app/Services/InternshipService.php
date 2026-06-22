<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Internship;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class InternshipService
{
    /**
     * Get internships for a specific company with pagination.
     */
    public function getCompanyInternships(Company $company, int $perPage = 15): LengthAwarePaginator
    {
        return Internship::where('company_id', $company->id)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Create a new internship.
     */
    public function createInternship(Company $company, array $data): Internship
    {
        $data['company_id'] = $company->id;
        $data['slug'] = $this->generateSlug($data['title']);

        return Internship::create($data);
    }

    /**
     * Update an existing internship.
     */
    public function updateInternship(Internship $internship, array $data): Internship
    {
        if (isset($data['title']) && $data['title'] !== $internship->title) {
            $data['slug'] = $this->generateSlug($data['title']);
        }

        $internship->update($data);

        return $internship;
    }

    /**
     * Delete an internship.
     */
    public function deleteInternship(Internship $internship): bool
    {
        return $internship->delete();
    }

    /**
     * Generate a unique slug for the internship.
     */
    protected function generateSlug(string $title): string
    {
        return Str::slug($title).'-'.Str::random(5);
    }
}
