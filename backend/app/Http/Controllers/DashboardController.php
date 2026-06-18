<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use App\Services\Auth\RoleResolver;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(RoleResolver $roleResolver)
    {
        $user = Auth::user();

        $dashboardPath = $roleResolver->dashboardPath($user);
        if ($dashboardPath !== '/dashboard') {
            return redirect($dashboardPath);
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'total_applications' => $user->applications()->count(),
                'active_applications' => $user->applications()->whereNotIn('status', ['accepted', 'rejected'])->count(),
                'saved_internships' => $user->savedInternships()->count(),
                'profile_completion' => $this->calculateProfileCompletion($user),
            ],
            'recent_applications' => $user->applications()
                ->with('internship.company')
                ->latest()
                ->limit(3)
                ->get(),
            'saved_internships' => $user->savedInternships()
                ->with('internship.company')
                ->latest()
                ->limit(3)
                ->get(),
            'recommended_internships' => $this->getRecommendedInternships($user),
            'notifications' => $user->notifications()
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    private function calculateProfileCompletion($user): int
    {
        $points = 0;
        $total = 6;

        if ($user->detail) {
            if ($user->detail->bio) {
                $points++;
            }
            if ($user->detail->phone_number || $user->phone_number) {
                $points++;
            }
            if ($user->detail->address) {
                $points++;
            }
            if (! empty($user->detail->education)) {
                $points++;
            }
            if (! empty($user->detail->skills)) {
                $points++;
            }
            if ($user->detail->cv_path) {
                $points++;
            }
        }

        return round(($points / $total) * 100);
    }

    private function getRecommendedInternships($user)
    {
        $skills = $user->detail->skills ?? [];

        $query = Internship::with('company')->where('status', 'published');

        if (! empty($skills)) {
            $query->where(function ($q) use ($skills) {
                foreach ($skills as $skill) {
                    $q->orWhereJsonContains('tags', $skill);
                }
            });

            $recommended = $query->latest()->limit(3)->get();
            if ($recommended->isNotEmpty()) {
                return $recommended;
            }
        }

        // Fallback to latest if no matches found or no skills
        return Internship::with('company')->where('status', 'published')->latest()->limit(3)->get();
    }
}
