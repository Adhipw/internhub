<?php

namespace App\Http\Controllers\Api;

use App\Events\ApplicationStatusChanged;
use App\Models\Application;
use App\Models\CompanyMember;
use App\Models\Internship;
use App\Models\User;
use App\Services\AI\AiService;
use App\Services\AI\DTOs\AiMessage;
use App\Services\AI\Enums\AiRole;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ApiHrApplicationController extends ApiBaseController
{
    /**
     * Display a listing of applications for the HR's company.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company) {
            return $this->sendError('Company not found', [], 404);
        }

        $query = Application::whereHas('internship', function ($q) use ($company) {
            $q->where('company_id', $company->id);
        })->with(['user.detail', 'internship']);

        // Filter by Internship
        if ($request->has('internship_id')) {
            $query->where('internship_id', $request->internship_id);
        }

        // Filter by Status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(15);

        return $this->sendResponse($applications, 'HR Applications retrieved successfully');
    }

    /**
     * Display the specified application.
     */
    public function show(Application $application): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $application->load(['user.detail', 'internship', 'interviewer', 'mentor']);

        // Get available mentors in this company
        $mentors = CompanyMember::where('company_id', $company->id)
            ->whereHas('user', function ($q) {
                $q->whereIn('role', ['mentor', 'hr', 'admin']);
            })
            ->with('user')
            ->get();

        return $this->sendResponse([
            'application' => $application,
            'mentors' => $mentors,
        ], 'Application details retrieved');
    }

    /**
     * Update the status of an application.
     */
    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:pending,reviewing,interview,accepted,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $statusLabels = [
            'pending' => __('Menunggu Review'),
            'reviewing' => __('Sedang Direview'),
            'interview' => __('Tahap Interview'),
            'accepted' => __('Diterima Magang'),
            'rejected' => __('Lamaran Ditolak'),
        ];

        DB::transaction(function () use ($application, $validated, $statusLabels) {
            $oldStatus = $application->status;

            $description = $validated['notes'] ?? "Status lamaran Anda diperbarui menjadi {$statusLabels[$validated['status']]}";

            if ($validated['status'] === 'accepted') {
                $description = __('Selamat! Anda telah diterima magang. Mohon lengkapi dokumen Onboarding dan hubungi Mentor Anda.');
            }

            $application->update([
                'status' => $validated['status'],
                'hr_notes' => $validated['notes'] ?? $application->hr_notes,
                'timeline' => array_merge($application->timeline ?? [], [[
                    'status' => $validated['status'],
                    'label' => $statusLabels[$validated['status']],
                    'description' => $description,
                    'date' => now()->toDateTimeString(),
                    'updated_by' => Auth::user()->name,
                ]]),
            ]);

            AuditService::log('application_status_updated', $application, ['old' => $oldStatus], ['new' => $validated['status']]);

            event(new ApplicationStatusChanged($application, $validated['status'], $validated['notes'] ?? "Status lamaran Anda diperbarui menjadi {$statusLabels[$validated['status']]}"));

            // Logic for acceptance: Automations can be added here (e.g., creating a mentor assignment)
        });

        return $this->sendResponse($application, 'Application status updated successfully');
    }

    /**
     * Schedule an interview for the application.
     */
    public function scheduleInterview(Request $request, Application $application): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'type' => 'required|string|in:online,offline',
            'meeting_link' => 'nullable|string|url',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($application, $validated) {
            // Update application status to interview
            $application->update([
                'status' => 'interview',
                'timeline' => array_merge($application->timeline ?? [], [[
                    'status' => 'interview',
                    'label' => __('Interview Dijadwalkan'),
                    'description' => __('Wawancara dijadwalkan pada :date (:type). :notes', [
                        'date' => $validated['scheduled_at'],
                        'type' => $validated['type'],
                        'notes' => $validated['notes'] ?? '',
                    ]),
                    'date' => now()->toDateTimeString(),
                    'metadata' => [
                        'type' => $validated['type'],
                        'link' => $validated['meeting_link'] ?? null,
                        'location' => $validated['location'] ?? null,
                    ],
                ]]),
            ]);

            // Create interview record if needed (Assuming InterviewSchedule exists)
            // \App\Models\InterviewSchedule::create([...]);

            AuditService::log('interview_scheduled', $application, [], $validated);
        });

        return $this->sendResponse($application, 'Interview scheduled successfully');
    }

    /**
     * Assign a mentor to the application.
     */
    public function assignMentor(Request $request, Application $application): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $validated = $request->validate([
            'mentor_user_id' => 'required|exists:users,id',
        ]);

        $mentor = User::find($validated['mentor_user_id']);

        DB::transaction(function () use ($application, $mentor) {
            $application->update([
                'mentor_user_id' => $mentor->id,
                'timeline' => array_merge($application->timeline ?? [], [[
                    'status' => $application->status,
                    'label' => 'Mentor Ditugaskan',
                    'description' => "Mentor {$mentor->name} telah ditugaskan untuk membimbing Anda.",
                    'date' => now()->toDateTimeString(),
                ]]),
            ]);

            AuditService::log('mentor_assigned', $application, [], ['mentor_id' => $mentor->id]);
        });

        return $this->sendResponse($application, 'Mentor assigned successfully');
    }

    /**
     * Generate an AI resume/CV summary for the candidate.
     */
    public function getAiSummary(Application $application, AiService $aiService): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $company = $user->companies()->first();

        if (! $company || $application->internship->company_id !== $company->id) {
            return $this->sendError('Unauthorized', [], 403);
        }

        $candidate = $application->user;
        $detail = $candidate->detail;
        $internship = $application->internship;

        if (! $detail) {
            return $this->sendResponse(['summary' => "• **Kelebihan Utama:** Profil kandidat belum dilengkapi.\n• **Kesenjangan Keahlian:** N/A\n• **Rekomendasi Tindakan:** Hubungi kandidat untuk melengkapi profil."], 'Profile incomplete');
        }

        // Clean arrays
        $requiredRequirements = $internship->requirements ?? [];
        $candidateSkills = $detail->skills ?? [];

        // Build Gemini prompt
        $prompt = "Anda adalah Asisten Rekrutmen AI profesional untuk InternHub.
Tugas Anda adalah membaca data profil kandidat berikut dan menganalisis kecocokannya dengan posisi magang:

Posisi Magang: {$internship->title}
Persyaratan/Keahlian Yang Dibutuhkan: ".implode(', ', $requiredRequirements)."
Deskripsi Lowongan: {$internship->description}

Nama Kandidat: {$candidate->name}
Bio: {$detail->bio}
Pendidikan: ".json_encode($detail->education).'
Keahlian Kandidat: '.json_encode($candidateSkills)."
Cover Letter: {$application->cover_letter}

Berikan analisis terstruktur dalam tepat 3 poin berbutir (bullet points) pendek dan padat berbahasa Indonesia:
1. Kelebihan Utama: (Analisis keahlian dan riwayat pendidikan terkuat kandidat yang cocok untuk posisi ini)
2. Kesenjangan Keahlian (Skill Gap): (Tuliskan keterampilan penting apa saja dari persyaratan lowongan yang belum dikuasai oleh kandidat)
3. Rekomendasi Tindakan: (Berikan skor kecocokan persentase dan rekomendasi konkret untuk HR, contoh: \"Undang wawancara karena skor kecocokan 92%.\")

Format keluaran harus persis berupa teks dengan 3 baris poin tersebut, tanpa tambahan teks pengantar atau penutup.";

        try {
            $messages = [
                new AiMessage(AiRole::SYSTEM, 'Anda adalah asisten rekrutmen AI profesional yang menganalisis kecocokan pelamar magang dengan lowongan secara taktis.'),
                new AiMessage(AiRole::USER, $prompt),
            ];

            // Chat with AI Service (skip authorization since this endpoint is protected by HR gate)
            $response = $aiService->chat($messages, ['skip_auth' => true]);
            $summary = $response->content;

            return $this->sendResponse(['summary' => $summary], 'AI Resume summary generated');
        } catch (\Exception $e) {
            // Local fallback logic
            $matchCount = 0;
            $requiredSkillsLower = array_map('strtolower', $requiredRequirements);
            $candidateSkillsLower = array_map('strtolower', $candidateSkills);

            foreach ($requiredSkillsLower as $req) {
                if (in_array($req, $candidateSkillsLower)) {
                    $matchCount++;
                }
            }

            $score = count($requiredRequirements) > 0 ? round(($matchCount / count($requiredRequirements)) * 100) : 60;
            $missing = array_diff($requiredSkillsLower, $candidateSkillsLower);
            $missingText = count($missing) > 0 ? implode(', ', array_slice($missing, 0, 3)) : 'Tidak ada kesenjangan keahlian signifikan';

            $fallback = '• **Kelebihan Utama:** Kandidat menguasai keahlian '.implode(', ', array_slice($candidateSkills, 0, 3))." dan memiliki latar belakang pendidikan yang relevan.\n".
                        '• **Kesenjangan Keahlian (Skill Gap):** Kurang mendalam di bidang '.$missingText.".\n".
                        "• **Rekomendasi Tindakan:** Pertimbangkan untuk dipertahankan (Taksiran skor kecocokan: {$score}%).";

            return $this->sendResponse(['summary' => $fallback], 'AI Resume summary generated via fallback');
        }
    }
}
