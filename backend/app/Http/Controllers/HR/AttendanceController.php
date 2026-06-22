<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Services\Logging\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $company = app('current_company');

        $query = Attendance::with(['user', 'application.internship'])
            ->whereHas('application.internship', function ($q) use ($company) {
                $q->where('company_id', $company->id);
            });

        if ($request->search) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            });
        }

        $attendances = $query->latest()->paginate(15)->withQueryString();

        // Get live locations from Redis
        $liveLocations = [];
        $activeUsers = Attendance::whereHas('application.internship', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })
            ->whereNull('check_out_at')
            ->pluck('user_id');

        foreach ($activeUsers as $userId) {
            $loc = Cache::get("user_location:{$userId}");
            if ($loc) {
                $liveLocations[$userId] = $loc;
            }
        }

        $date = clone now();
        $totalPresent = Attendance::whereHas('application.internship', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->whereDate('check_in_at', $date->toDateString())->count();
        $currentlyActive = count($activeUsers);

        return Inertia::render('HR/Attendance/Index', [
            'attendances' => $attendances,
            'liveLocations' => $liveLocations,
            'stats' => [
                'total_present' => $totalPresent,
                'currently_active' => $currentlyActive,
            ],
            'filters' => $request->only(['search']),
        ]);
    }

    public function approveCorrection(AttendanceCorrection $correction, Request $request)
    {
        $company = app('current_company');
        if ($correction->attendance->application->internship->company_id !== $company->id) {
            abort(403);
        }

        $correction->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewer_notes' => $request->notes,
        ]);

        $correction->attendance->update([
            'check_in_at' => $correction->new_check_in_at ?? $correction->attendance->check_in_at,
            'check_out_at' => $correction->new_check_out_at ?? $correction->attendance->check_out_at,
        ]);

        AuditLogger::log('attendance_correction_approved', $correction, null, null, 'HR approved attendance correction.');

        return back()->with('success', 'Koreksi disetujui.');
    }
}
