<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CompanyResource;
use App\Http\Resources\Api\InternshipResource;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Services\Search\InternshipSearchService;
use Illuminate\Http\Request;

class ApiPublicInternshipController extends Controller
{
    protected $searchService;

    public function __construct(InternshipSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * List all companies.
     */
    public function companies(Request $request)
    {
        $companies = Company::withCount(['internships' => function ($query) {
            $query->where('status', 'published');
        }])->paginate(12);

        return CompanyResource::collection($companies);
    }

    /**
     * List all published internships with search and filters.
     */
    public function index(Request $request)
    {
        $internships = $this->searchService->search($request->all());

        return InternshipResource::collection($internships);
    }

    /**
     * Get single internship detail.
     */
    public function show($slug)
    {
        $internship = Internship::published()
            ->with(['company'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new InternshipResource($internship);
    }

    /**
     * Get company public profile and its published internships.
     */
    public function companyProfile($slug)
    {
        $company = Company::where('slug', $slug)->firstOrFail();

        $internships = Internship::published()
            ->where('company_id', $company->id)
            ->latest()
            ->paginate(6);

        return response()->json([
            'company' => new CompanyResource($company),
            'internships' => InternshipResource::collection($internships),
        ]);
    }

    /**
     * Get stats for homepage.
     */
    public function stats()
    {
        return response()->json([
            'total_internships' => Internship::published()->count(),
            'total_companies' => Company::count(),
            'total_placements' => Application::whereIn('status', ['accepted', 'hired'])->count(),
            'total_students' => User::role('user')->count() ?: User::where('role', 'user')->count(),
        ]);
    }
}
