<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $mentorId = Auth::id();

        // Only mentees assigned to this mentor
        $query = Attendance::with(['user', 'application.internship'])
            ->whereHas('application', function ($q) use ($mentorId) {
                $q->where('mentor_user_id', $mentorId);
            });

        $attendances = $query->latest()->paginate(15);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                ],
            ]);
        }

        return Inertia::render('Mentor/Attendance/Index', [
            'attendances' => $attendances,
        ]);
    }
}
