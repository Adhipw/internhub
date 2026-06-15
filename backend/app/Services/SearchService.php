<?php

namespace App\Services;

use App\DTOs\SearchInternshipDTO;
use App\Models\Internship;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SearchService
{
    /**
     * Search and filter published internships.
     */
    public function searchInternships(SearchInternshipDTO $dto): LengthAwarePaginator
    {
        $query = Internship::published()->with('company');

        // Full-Text Search using search_vector column
        if ($dto->search) {
            if (\DB::connection()->getDriverName() === 'sqlite') {
                $query->where(function ($q) use ($dto) {
                    $q->where('title', 'like', "%{$dto->search}%")
                        ->orWhere('description', 'like', "%{$dto->search}%");
                });
            } else {
                $query->whereRaw("search_vector @@ plainto_tsquery('simple', ?)", [$dto->search])
                    ->orderByRaw("ts_rank(search_vector, plainto_tsquery('simple', ?)) DESC", [$dto->search]);
            }
        } else {
            $query->latest();
        }

        // Location Filter
        if ($dto->location) {
            $query->where('location', 'like', "%{$dto->location}%");
        }

        // Type Filter
        if ($dto->type) {
            $query->where('type', $dto->type);
        }

        return $query->paginate($dto->perPage)->withQueryString();
    }
}
