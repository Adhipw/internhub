<?php

namespace App\Http\Controllers\Api;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\MentorEvaluation;
use App\Models\MentorFeedback;
use App\Models\MentorTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiMentorDashboardController extends ApiBaseController
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        $active_mentees = Application::where('status', 'accepted')
            ->where('mentor_user_id', $user->id)
            ->with(['user.detail', 'internship'])
            ->latest()
            ->get();

        $today = now()->toDateString();
        $attendance_summary = Attendance::whereDate('check_in_at', $today)
            ->whereHas('application', function ($q) use ($user) {
                $q->where('mentor_user_id', $user->id);
            })
            ->with('user')
            ->get();

        $pending_tasks = MentorTask::where('mentor_user_id', $user->id)
            ->whereIn('status', ['todo', 'in_progress'])
            ->count();

        $completed_evaluations = MentorEvaluation::where('mentor_user_id', $user->id)->count();

        $recent_feedbacks = MentorFeedback::where('mentor_user_id', $user->id)
            ->with(['application.user'])
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'total_mentees' => $active_mentees->count(),
            'present_today' => $attendance_summary->count(),
            'pending_tasks' => $pending_tasks,
            'completed_evaluations' => $completed_evaluations,
        ];

        return $this->sendResponse([
            'stats' => $stats,
            'active_mentees' => $active_mentees,
            'attendance_today' => $attendance_summary,
            'recent_feedbacks' => $recent_feedbacks,
        ], 'Mentor dashboard data retrieved');
    }
}
