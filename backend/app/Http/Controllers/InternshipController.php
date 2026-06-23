<?php

namespace App\Http\Controllers;

use App\DTOs\SearchInternshipDTO;
use App\Http\Resources\Api\InternshipResource;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InternshipController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    public function welcome()
    {
        view()->share('seo', [
            'title' => 'Cari Lowongan Magang Mahasiswa Terbaik',
            'description' => 'Temukan lowongan magang hebat di InternHub. Peluang karir terbaik untuk mahasiswa Indonesia dengan pencocokan kecerdasan buatan (Gemini AI).',
            'image' => asset('brand/logo-mark.svg'),
            'url' => request()->url(),
            'type' => 'website',
        ]);

        return Inertia::render('Welcome', [
            'featuredInternships' => Internship::published()
                ->with('company')
                ->latest()
                ->limit(6)
                ->get(),
            'companies' => Company::query()
                ->where('is_verified', true)
                ->latest()
                ->limit(12)
                ->get(),
            'stats' => [
                'total_internships' => Internship::published()->count(),
                'total_companies' => Company::count(),
                'total_placements' => Application::whereIn('status', ['accepted', 'hired'])->count(),
                'total_students' => User::role('user')->count() ?: User::where('role', 'user')->count(),
            ],
        ]);
    }

    public function index(Request $request)
    {
        $dto = SearchInternshipDTO::fromRequest($request);
        $internships = $this->searchService->searchInternships($dto);

        return Inertia::render('Internships/Index', [
            'internships' => InternshipResource::collection($internships),
            'filters' => $dto->toArray(),
        ]);
    }

    public function show(Internship $internship)
    {
        if ($internship->status !== 'published') {
            abort(404);
        }

        $companyName = $internship->company->name ?? 'InternHub';
        $title = $internship->title.' di '.$companyName;
        $description = 'Daftar sekarang untuk lowongan magang '.$internship->title.' di '.$companyName.'. Lokasi: '.$internship->location.'. '.strip_tags(substr($internship->description ?? '', 0, 150)).'...';
        $logoUrl = $internship->company->logo_url ?? asset('brand/logo-mark.svg');

        view()->share('seo', [
            'title' => $title,
            'description' => $description,
            'image' => $logoUrl,
            'url' => request()->url(),
            'type' => 'website',
        ]);

        return Inertia::render('Internships/Show', [
            'internship' => $internship->load('company'),
            'relatedInternships' => Internship::published()
                ->where('id', '!=', $internship->id)
                ->where('company_id', $internship->company_id)
                ->limit(3)
                ->get(),
            'hasApplied' => Auth::check()
                ? Application::where('user_id', Auth::id())->where('internship_id', $internship->id)->exists()
                : false,
        ]);
    }
}
