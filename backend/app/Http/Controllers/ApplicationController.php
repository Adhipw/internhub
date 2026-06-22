<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Internship;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    public function index()
    {
        return Inertia::render('Applications/Index', [
            'applications' => Auth::user()->applications()
                ->with(['internship.company'])
                ->latest()
                ->paginate(10),
        ]);
    }

    public function show(Application $application)
    {
        // Security check handled by policy
        $this->authorize('view', $application);

        return Inertia::render('Applications/Show', [
            'application' => $application->load(['internship.company']),
        ]);
    }

    public function store(Request $request, Internship $internship)
    {
        $user = Auth::user();

        if ($user->applications()->where('internship_id', $internship->id)->exists()) {
            return back()->withErrors(['application' => 'Anda sudah melamar posisi ini.']);
        }

        // Verification check
        if (! $user->detail?->cv_path) {
            return back()->withErrors(['application' => 'Silakan lengkapi profil dan unggah CV sebelum melamar.']);
        }

        DB::transaction(function () use ($user, $internship, $request) {
            $cvSnapshot = $user->detail->cv_path;
            if ($cvSnapshot && Storage::exists($cvSnapshot)) {
                $newCvPath = 'snapshots/'.uniqid().'_'.basename($cvSnapshot);
                Storage::copy($cvSnapshot, $newCvPath);
                $cvSnapshot = $newCvPath;
            }

            $portfolioSnapshot = $user->detail->portfolio_path;
            if ($portfolioSnapshot && Storage::exists($portfolioSnapshot)) {
                $newPortfolioPath = 'snapshots/'.uniqid().'_'.basename($portfolioSnapshot);
                Storage::copy($portfolioSnapshot, $newPortfolioPath);
                $portfolioSnapshot = $newPortfolioPath;
            }

            $application = Application::create([
                'user_id' => $user->id,
                'internship_id' => $internship->id,
                'status' => 'pending',
                'cover_letter' => $request->cover_letter,
                'cv_snapshot' => $cvSnapshot,
                'portfolio_snapshot' => $portfolioSnapshot,
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
        });

        return back()->with('status', 'application-submitted');
    }

    public function withdraw(Application $application)
    {
        $this->authorize('update', $application);

        if ($application->status !== 'pending') {
            return back()->withErrors(['application' => 'Hanya lamaran berstatus "Menunggu" yang dapat ditarik.']);
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

        return redirect()->route('applications.index')->with('status', 'unsaved');
    }
}
