<?php

namespace App\Http\Controllers\Mentor;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\MentorTask;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = MentorTask::with(['application.user', 'application.internship'])
            ->where('mentor_user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return Inertia::render('Mentor/Tasks/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function store(Request $request, Application $application)
    {
        if ($application->mentor_user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'required|integer|min:1|max:3',
        ]);

        $task = MentorTask::create([
            'application_id' => $application->id,
            'mentor_user_id' => auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'priority' => $validated['priority'],
            'status' => 'todo',
        ]);

        AuditService::log('mentor_task_created', $task, 'Task created for mentee: '.$application->user->name);

        return redirect()->back()->with('success', 'Tugas berhasil dibuat.');
    }

    public function updateStatus(Request $request, MentorTask $task)
    {
        if ($task->mentor_user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,completed,overdue',
        ]);

        $task->update(['status' => $validated['status']]);

        AuditService::log('mentor_task_status_updated', $task, 'Task status updated to: '.$validated['status']);

        return redirect()->back()->with('success', 'Status tugas diperbarui.');
    }

    public function destroy(MentorTask $task)
    {
        if ($task->mentor_user_id !== auth()->id()) {
            abort(403);
        }

        $task->delete();

        return redirect()->back()->with('success', 'Tugas berhasil dihapus.');
    }
}
