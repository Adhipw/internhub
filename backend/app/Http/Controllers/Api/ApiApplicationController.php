<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Models\Internship;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiApplicationController extends ApiBaseController
{
    public function index(): JsonResponse
    {
        $applications = Auth::user()->applications()
            ->with(['internship.company'])
            ->latest()
            ->paginate(10);

        return $this->sendResponse($applications, 'Applications retrieved successfully');
    }

    public function show(Application $application): JsonResponse
    {
        if ($application->user_id !== Auth::id()) {
            return $this->sendError('Unauthorized', [], 403);
        }

        /**
         * FUNGSI: show()
         * Menampilkan detail lengkap aplikasi mahasiswa.
         * LSP/Skripsi Note: Di sini kita tambahkan 'tasks' dan 'mentoringSessions' 
         * ke dalam fungsi load() agar mahasiswa bisa melihat tugas dan jadwal dari mentor.
         */
        return $this->sendResponse(
            $application->load([
                'internship.company', 
                'evaluations.mentor', 
                'mentor',
                'tasks', // Menarik data tugas mahasiswa
                'mentoringSessions' // Menarik data sesi bimbingan
            ]),
            'Application details retrieved'
        );
    }

    public function store(Request $request, Internship $internship): JsonResponse
    {
        $user = Auth::user();

        if ($user->applications()->where('internship_id', $internship->id)->exists()) {
            return $this->sendError('Anda sudah melamar posisi ini.', [], 422);
        }

        if (! $user->detail?->cv_path) {
            return $this->sendError('Silakan lengkapi profil dan unggah CV sebelum melamar.', [], 422);
        }

        $application = DB::transaction(function () use ($user, $internship, $request) {
            $application = Application::create([
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'status' => 'pending',
                'cover_letter' => $request->cover_letter,
                'cv_snapshot' => $user->detail->cv_path,
                'portfolio_snapshot' => $user->detail->portfolio_path,
                'timeline' => [
                    [
                        'status' => 'pending',
                        'label' => 'Lamaran Dikirim',
                        'description' => 'Lamaran Anda telah berhasil dikirim dan sedang menunggu review.',
                        'date' => now()->toDateTimeString(),
                    ],
                ],
            ]);

            AuditService::log('application_submitted', $application);

            return $application;
        });

        return $this->sendResponse($application, 'Application submitted successfully', 201);
    }

    public function withdraw(Application $application): JsonResponse
    {
        if ($application->user_id !== Auth::id()) {
            return $this->sendError('Unauthorized', [], 403);
        }

        if ($application->status !== 'pending') {
            return $this->sendError('Hanya lamaran berstatus "Menunggu" yang dapat ditarik.', [], 422);
        }

        DB::transaction(function () use ($application) {
            $application->update([
                'status' => 'withdrawn',
                'timeline' => array_merge($application->timeline, [[
                    'status' => 'withdrawn',
                    'label' => 'Lamaran Ditarik',
                    'description' => 'Anda telah menarik lamaran ini.',
                    'date' => now()->toDateTimeString(),
                ]]),
            ]);

            AuditService::log('application_withdrawn', $application);
        });

        return $this->sendResponse(null, 'Application withdrawn successfully');
    }
}
