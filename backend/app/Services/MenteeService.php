<?php

namespace App\Services;

use App\Models\Application;
use App\Models\MentorFeedback;
use App\Models\MentorTask;
use App\Models\MentoringSession;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class MenteeService
{
    /**
     * Get list of mentees assigned to a specific mentor.
     */
    public function getAssignedMentees(User $mentor, int $perPage = 15): LengthAwarePaginator
    {
        return Application::where('mentor_user_id', $mentor->id)
            ->with(['user.detail', 'internship'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get full detail of a mentee application.
     */
    public function getMenteeDetail(Application $application): Application
    {
        return $application->load([
            'user.detail',
            'internship',
            'mentorFeedbacks.mentor',
            'tasks',
            'evaluations',
            'mentoringSessions',
        ]);
    }

    /**
     * Submit feedback for a mentee.
     */
    public function submitFeedback(Application $application, User $mentor, array $data): MentorFeedback
    {
        return DB::transaction(function () use ($application, $mentor, $data) {
            $feedback = MentorFeedback::create([
                'application_id' => $application->id,
                'mentor_user_id' => $mentor->id,
                'content' => $data['content'],
                'assessment' => $data['assessment'] ?? [],
                'status' => 'submitted',
            ]);

            AuditService::log(
                'mentor_feedback_submitted',
                $feedback,
                'Feedback submitted for mentee: '.$application->user->name
            );

            return $feedback;
        });
    }

    /**
     * Create a new task for a mentee.
     */
    public function createTask(Application $application, User $mentor, array $data): MentorTask
    {
        return DB::transaction(function () use ($application, $mentor, $data) {
            $task = MentorTask::create([
                'application_id' => $application->id,
                'mentor_user_id' => $mentor->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'priority' => $data['priority'] ?? 2,
                'status' => 'todo',
            ]);

            AuditService::log(
                'mentor_task_created',
                $task,
                'Task created for mentee: '.$application->user->name
            );

            return $task;
        });
    }

    /**
     * Update task status.
     */
    public function updateTaskStatus(MentorTask $task, string $status): MentorTask
    {
        $task->update(['status' => $status]);

        return $task;
    }

    /**
     * Delete a task.
     */
    public function deleteTask(MentorTask $task): bool
    {
        return $task->delete();
    }

    /**
     * Create a new mentoring session.
     */
    public function createSession(Application $application, User $mentor, array $data): MentoringSession
    {
        return DB::transaction(function () use ($application, $mentor, $data) {
            $session = MentoringSession::create([
                'application_id' => $application->id,
                'mentor_user_id' => $mentor->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'scheduled_at' => $data['scheduled_at'],
                'duration_minutes' => $data['duration_minutes'] ?? 60,
                'meeting_link' => $data['meeting_link'] ?? null,
                'status' => 'planned',
            ]);

            AuditService::log(
                'mentoring_session_created',
                $session,
                'Mentoring session scheduled for mentee: '.$application->user->name
            );

            return $session;
        });
    }

    /**
     * Update session status.
     */
    public function updateSessionStatus(MentoringSession $session, string $status): MentoringSession
    {
        $session->update(['status' => $status]);

        return $session;
    }
}
