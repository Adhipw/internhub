<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;

class ApiAdminCompanyController extends Controller
{
    /**
     * API 1: index()
     * Fungsi: Mengambil daftar semua Perusahaan (Company) yang terdaftar.
     * LSP/Skripsi Note: Menggunakan 'withCount' untuk menghitung otomatis 
     * berapa banyak lowongan magang (internships) yang dimiliki tiap perusahaan.
     */
    public function index(Request $request)
    {
        $query = Company::query();

        // Fitur Pencarian Nama Perusahaan
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Fitur Filter Status Verifikasi
        if ($request->status === 'verified') {
            $query->where('is_verified', true);
        } elseif ($request->status === 'unverified') {
            $query->where('is_verified', false);
        }

        // Menambahkan total lowongan (internships_count) menggunakan withCount
        $companies = $query->withCount('internships')->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $companies,
        ]);
    }

    /**
     * API 2: verify()
     * Fungsi: Mengubah status perusahaan menjadi "Terverifikasi".
     * LSP/Skripsi Note: Fitur ini wajib di aplikasi magang agar perusahaan fiktif 
     * tidak bisa sembarangan merekrut mahasiswa.
     */
    public function verify(Company $company)
    {
        $company->is_verified = true;
        $company->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Company verified',
            'data' => $company,
        ]);
    }

    /**
     * API 3: unverify()
     * Fungsi: Mencabut status verifikasi perusahaan.
     */
    public function unverify(Company $company)
    {
        $company->is_verified = false;
        $company->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Company verification revoked',
            'data' => $company,
        ]);
    }
}
