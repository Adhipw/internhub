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
     * API 1: companies()
     * Fungsi: Mengambil daftar perusahaan untuk ditampilkan di Landing Page umum.
     * LSP/Skripsi Note: Hanya mengambil perusahaan dan menghitung jumlah lowongan magang
     * yang statusnya 'published' (Publik).
     */
    public function companies(Request $request)
    {
        $companies = Company::withCount(['internships' => function ($query) {
            $query->where('status', 'published');
        }])->paginate(12);

        return CompanyResource::collection($companies);
    }

    /**
     * API 2: index()
     * Fungsi: Mencari lowongan magang berdasarkan kata kunci (Pencarian).
     * LSP/Skripsi Note: Logika pencariannya sengaja dipisah ke dalam 'InternshipSearchService'
     * agar Controller tetap rapi (Prinsip SOLID / Single Responsibility).
     */
    public function index(Request $request)
    {
        // Mendelegasikan logika pencarian ke class Service khusus
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
     * API 5: stats()
     * Fungsi: Menghitung total statistik (Lowongan, Perusahaan, Mahasiswa) untuk 
     * ditampilkan di angka-angka di Landing Page.
     * LSP/Skripsi Note: Menghitung count() dari database secara live (Real-time).
     */
    public function stats()
    {
        return response()->json([
            'total_internships' => Internship::published()->count(), // Lowongan aktif
            'total_companies' => Company::where('is_verified', true)->count(), // Perusahaan valid
            'total_placements' => Application::count(), // Total mahasiswa yang melamar
            'total_students' => User::where('role', 'user')->count(), // Pengguna (Mentee)
        ]);
    }
}
