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
     * FUNGSI 1: getAssignedMentees
     * Digunakan untuk mengambil daftar mentee (mahasiswa magang) yang ditugaskan ke mentor tertentu.
     * LSP/Skripsi Note: Menggunakan 'paginate' agar data tidak membebani server jika jumlahnya ribuan (Best Practice).
     */
    public function getAssignedMentees(User $mentor, int $perPage = 15): LengthAwarePaginator
    {
        return Application::where('mentor_user_id', $mentor->id)
            ->with(['user.detail', 'internship']) // Eager loading untuk mencegah masalah N+1 query
            ->latest()
            ->paginate($perPage);
    }

    /**
     * FUNGSI 2: getMenteeDetail
     * Mengambil seluruh detail profil mentee beserta riwayat magangnya (tugas, sesi, evaluasi).
     * LSP/Skripsi Note: Konsep 'load' ini menghubungkan relasi database tanpa query berulang.
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
     * FUNGSI 3: submitFeedback
     * Menyimpan catatan/feedback dari mentor untuk mentee.
     * LSP/Skripsi Note: Memakai 'DB::transaction' agar jika terjadi error di tengah proses,
     * seluruh data akan di-rollback (dibatalkan) sehingga tidak ada data "setengah jadi" di database.
     */
    public function submitFeedback(Application $application, User $mentor, array $data): MentorFeedback
    {
        return DB::transaction(function () use ($application, $mentor, $data) {
            // Menyimpan data ke tabel mentor_feedback
            $feedback = MentorFeedback::create([
                'application_id' => $application->id,
                'mentor_user_id' => $mentor->id,
                'content' => $data['content'],
                'assessment' => $data['assessment'] ?? [], // Array data skor
                'status' => 'submitted',
            ]);

            // Mencatat aktivitas di log (Fitur standar keamanan/audit)
            AuditService::log(
                'mentor_feedback_submitted',
                $feedback,
                'Feedback submitted for mentee: '.$application->user->name
            );

            return $feedback;
        });
    }

    /**
     * FUNGSI 4: createTask
     * Membuat tugas baru untuk mentee. Sama menggunakan fitur DB transaction.
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
     * FUNGSI 5: updateTaskStatus
     * Memperbarui status tugas (contoh: dari todo menjadi completed).
     */
    public function updateTaskStatus(MentorTask $task, string $status): MentorTask
    {
        $task->update(['status' => $status]);

        return $task;
    }

    /**
     * FUNGSI 6: deleteTask
     * Menghapus tugas.
     */
    public function deleteTask(MentorTask $task): bool
    {
        return $task->delete();
    }

    /**
     * FUNGSI 7: createSession
     * Membuat jadwal bimbingan/meeting baru (Mentoring Session) antara mentor dan mahasiswa.
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
     * FUNGSI 8: updateSessionStatus
     * Memperbarui status sesi meeting (contoh: planned -> completed / dibatalkan).
     */
    public function updateSessionStatus(MentoringSession $session, string $status): MentoringSession
    {
        $session->update(['status' => $status]);

        return $session;
    }
}
