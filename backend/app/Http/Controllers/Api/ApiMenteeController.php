<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\Api\MenteeResource;
use App\Http\Resources\Api\MentorFeedbackResource;
use App\Http\Resources\Api\MentorTaskResource;
use App\Models\Application;
use App\Models\MentoringSession;
use App\Models\MentorTask;
use App\Services\MenteeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiMenteeController extends ApiBaseController
{
    protected MenteeService $menteeService;

    public function __construct(MenteeService $menteeService)
    {
        $this->menteeService = $menteeService;
    }

    /**
     * Get list of assigned mentees
     */
    public function index(): JsonResponse
    {
        $mentees = $this->menteeService->getAssignedMentees(Auth::user());

        return $this->sendResponse(
            MenteeResource::collection($mentees)->response()->getData(true),
            __('Mentees retrieved successfully')
        );
    }

    /**
     * Get mentee detail
     */
    public function show(Application $application): JsonResponse
    {
        if ($application->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized access to this mentee'), [], 403);
        }

        $mentee = $this->menteeService->getMenteeDetail($application);

        return $this->sendResponse(
            new MenteeResource($mentee),
            __('Mentee detail retrieved successfully')
        );
    }

    /**
     * Submit feedback for a mentee
     */
    public function storeFeedback(Request $request, Application $application): JsonResponse
    {
        if ($application->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|min:10',
            'assessment' => 'nullable|array',
            'assessment.technical' => 'nullable|integer|min:1|max:5',
            'assessment.soft_skills' => 'nullable|integer|min:1|max:5',
            'assessment.attitude' => 'nullable|integer|min:1|max:5',
        ]);

        $feedback = $this->menteeService->submitFeedback($application, Auth::user(), $validated);

        return $this->sendResponse(
            new MentorFeedbackResource($feedback),
            __('Feedback submitted successfully')
        );
    }

    /**
     * Create task for mentee
     */
    public function storeTask(Request $request, Application $application): JsonResponse
    {
        if ($application->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|integer|in:1,2,3',
        ]);

        $task = $this->menteeService->createTask($application, Auth::user(), $validated);

        return $this->sendResponse(
            new MentorTaskResource($task),
            __('Task created successfully')
        );
    }

    /**
     * Update task status
     */
    public function updateTaskStatus(Request $request, MentorTask $task): JsonResponse
    {
        if ($task->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:todo,in_progress,completed,overdue',
        ]);

        $task = $this->menteeService->updateTaskStatus($task, $validated['status']);

        return $this->sendResponse(
            new MentorTaskResource($task),
            __('Task status updated successfully')
        );
    }

    /**
     * Delete a task
     */
    public function deleteTask(MentorTask $task): JsonResponse
    {
        if ($task->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $this->menteeService->deleteTask($task);

        return $this->sendResponse(null, __('Task deleted successfully'));
    }

    /**
     * Get all tasks for the mentor globally
     */
    public function allTasks(): JsonResponse
    {
        $tasks = MentorTask::with(['application.user', 'application.internship'])
            ->where('mentor_user_id', Auth::id())
            ->latest()
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->due_date,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'mentee' => $task->application->user->name ?? 'Unknown',
                    'internship' => $task->application->internship->title ?? 'Unknown',
                    'application_id' => $task->application_id,
                    'created_at' => $task->created_at,
                ];
            });

        return $this->sendResponse(
            $tasks,
            __('Tasks retrieved successfully')
        );
    }

    /**
     * Create session for mentee
     */
    public function storeSession(Request $request, Application $application): JsonResponse
    {
        if ($application->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_at' => 'required|date',
            'duration_minutes' => 'required|integer|min:15|max:240',
            'meeting_link' => 'nullable|url',
        ]);

        $session = $this->menteeService->createSession($application, Auth::user(), $validated);

        return $this->sendResponse(
            $session,
            __('Session scheduled successfully')
        );
    }

    /**
     * Update session status
     */
    public function updateSessionStatus(Request $request, MentoringSession $session): JsonResponse
    {
        if ($session->mentor_user_id !== Auth::id()) {
            return $this->sendError(__('Unauthorized'), [], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:planned,completed,cancelled',
        ]);

        $session = $this->menteeService->updateSessionStatus($session, $validated['status']);

        return $this->sendResponse(
            $session,
            __('Session status updated successfully')
        );
    }
}
