<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MentorEvaluation;
use App\Models\MentorFeedback;
use App\Models\MentorTask;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = \Illuminate\Support\Facades\Auth::user();
        $company = app('current_company');

        $query = Application::where('mentor_user_id', $user->id)
            ->whereHas('internship', function ($query) use ($company) {
                $query->where('company_id', $company->id);
            });

        $totalMentees = $query->count();

        $activeMentees = $query->clone()
            ->whereIn('status', ['accepted', 'reviewing'])
            ->with(['user', 'internship'])
            ->latest()
            ->take(5)
            ->get();

        $pendingTasksCount = MentorTask::where('mentor_user_id', $user->id)
            ->where('status', '!=', 'completed')
            ->count();

        $recentFeedbacks = MentorFeedback::where('mentor_user_id', $user->id)
            ->with(['application.user', 'application.internship'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Mentor/Dashboard', [
            'stats' => [
                'total_mentees' => $totalMentees,
                'pending_tasks' => $pendingTasksCount,
                'completed_evaluations' => MentorEvaluation::where('mentor_user_id', $user->id)->count(),
            ],
            'activeMentees' => $activeMentees,
            'recentFeedbacks' => $recentFeedbacks,
        ]);
    }
}
