<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ApiAdminUserController extends Controller
{
    /**
     * API 1: index()
     * Fungsi: Mengambil daftar pengguna (User) selain 'super_admin' dengan fitur Pagination & Filtering.
     * LSP/Skripsi Note: Menggunakan 'like' query untuk fitur Search, dan Eloquent ORM 'with(roles)' 
     * untuk menghindari masalah N+1 Query.
     */
    public function index(Request $request)
    {
        // Mengambil semua user beserta data rolenya, kecuali akun tertinggi (super_admin)
        $query = User::with('roles')->where('role', '!=', 'super_admin');

        // Fitur Pencarian berdasarkan nama atau email
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        // Fitur Filter berdasarkan Role (Misal: mentor, student, hr)
        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->status !== null) {
            $query->where('is_active', (bool) $request->status);
        }

        $users = $query->latest()->paginate(10);

        return response()->json([
            'status' => 'success',
            'data' => $users,
        ]);
    }

    /**
     * API 2: toggleStatus()
     * Fungsi: Mengaktifkan atau menonaktifkan (Banned) akun pengguna.
     * LSP/Skripsi Note: Ada validasi keamanan khusus di mana admin biasa tidak boleh
     * memblokir akun super_admin (Proteksi Hak Akses).
     */
    public function toggleStatus(User $user)
    {
        // Proteksi tingkat lanjut: Mencegah error logika / sabotase
        if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
            return response()->json(['status' => 'error', 'message' => 'Cannot modify super admin'], 403);
        }

        // Toggle nilai is_active (jika 1 jadi 0, jika 0 jadi 1)
        $user->is_active = ! $user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated',
            'data' => $user,
        ]);
    }

    /**
     * API 3: destroy()
     * Fungsi: Menghapus data pengguna dari sistem.
     * LSP/Skripsi Note: Mengikuti prinsip CRUD dasar (Delete). Terlindungi dari penghapusan super_admin.
     */
    public function destroy(User $user)
    {
        if ($user->role === 'super_admin' || $user->hasRole('super_admin')) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete super admin'], 403);
        }

        // Mengeksekusi query Delete (Atau SoftDelete jika model mendukung)
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted',
        ]);
    }
}
