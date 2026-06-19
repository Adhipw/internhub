<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CompanyController as AdminCompanyController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ExternalIntegrationController;
use App\Http\Controllers\Admin\IntegrationReviewController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SecurityEventController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AiAdminController;
use App\Http\Controllers\AiHrController;
use App\Http\Controllers\AiMentorController;
use App\Http\Controllers\AiNearbyController;
use App\Http\Controllers\AiPublicController;
use App\Http\Controllers\AiSuperAdminController;
use App\Http\Controllers\AiUserController;
use App\Http\Controllers\Api\PartnerWebhookController;
use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\CompanyPublicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExternalIntegration\WebhookController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HR\CompanyController as HrCompanyController;
use App\Http\Controllers\HR\MemberController;
use App\Http\Controllers\InternshipController;
use App\Http\Controllers\Mentor\EvaluationController;
use App\Http\Controllers\Mentor\MenteeController;
use App\Http\Controllers\Mentor\SessionController;
use App\Http\Controllers\Mentor\TaskController;
use App\Http\Controllers\NearbyController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PipelineController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicPageController;
use App\Http\Controllers\SavedInternshipController;
use App\Http\Controllers\ScoringController;
use App\Http\Controllers\SuperAdmin\IntegrationController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\RolePermissionController;
use App\Http\Controllers\SuperAdmin\SettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [InternshipController::class, 'welcome'])->name('welcome');

// Language Switcher (Keep as web route for session-based locale)

Route::match(['get', 'post'], '/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
        \Illuminate\Support\Facades\App::setLocale($locale);
    }

    return back();
})->name('language.switch');

Route::get('/internships', [InternshipController::class, 'index'])->name('internships.index');
Route::get('/internships/{internship:slug}', [InternshipController::class, 'show'])->name('internships.show');
Route::get('/companies', [CompanyPublicController::class, 'index'])->name('companies.index');
// Public Info & Legal Routes
Route::controller(PublicPageController::class)->group(function () {
    Route::get('/help', 'help')->name('help');
    Route::get('/terms', 'terms')->name('terms');
    Route::get('/privacy', 'privacy')->name('privacy');
    Route::get('/contact', 'contact')->name('contact');
    Route::get('/career-tips', 'careerTips')->name('career-tips');
    Route::get('/cv-guide', 'cvGuide')->name('cv-guide');
    Route::get('/campus-program', 'campusProgram')->name('campus-program');
    Route::get('/selection-system', 'selectionSystem')->name('selection-system');
    Route::get('/employer-branding', 'employerBranding')->name('employer-branding');
    Route::get('/enterprise', 'enterprise')->name('enterprise');

    Route::prefix('companies')->name('companies.')->group(function () {
        Route::get('/selection-system', 'selectionSystem')->name('selection-system');
        Route::get('/employer-branding', 'employerBranding')->name('employer-branding');
        Route::get('/enterprise', 'enterprise')->name('enterprise');
    });
});

Route::get('/companies/{company:slug}', [CompanyPublicController::class, 'show'])->name('companies.show');

// Public AI Assistant Routes
Route::post('/ai/public/faq', [AiPublicController::class, 'faq'])->name('ai.public.faq');
Route::post('/ai/public/finder', [AiPublicController::class, 'internshipFinder'])->name('ai.public.finder');

Route::get('/auth/callback', fn () => \Inertia\Inertia::render('Auth/AuthCallback'))->name('auth.callback');


// Partner Webhooks
Route::post('/api/webhooks/partner/{uuid}', [PartnerWebhookController::class, 'handle'])->name('webhooks.partner');

Route::middleware('guest')->group(function () {
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
    Route::get('verify-otp', fn () => \Inertia\Inertia::render('Auth/VerifyOtp', [
        'email' => request('email'),
    ]))->name('verify.otp');

    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);

    Route::get('forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendOtp'])->name('password.email');
    Route::get('reset-password', [ResetPasswordController::class, 'show'])->name('password.reset');
    Route::post('reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

    Route::get('auth/google/redirect', [SocialiteController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [SocialiteController::class, 'callback'])->name('google.callback');
});


Route::middleware('auth')->group(function () {
    Route::get('verify-email', [VerifyEmailController::class, 'show'])->name('verification.notice');
    Route::post('verify-email', [VerifyEmailController::class, 'verify'])->name('verification.verify');
    Route::post('email/verification-notification', [VerifyEmailController::class, 'resend'])->name('verification.send');

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('sessions', [AuthController::class, 'sessions'])->name('sessions.index');
    Route::post('sessions/logout-others', [AuthController::class, 'logoutOtherDevices'])->name('sessions.logout-others');

    // Dashboard & Profile (Verified Users)
    Route::middleware('verified')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/settings', [AccountController::class, 'index'])->name('settings.index');
        Route::patch('/settings/name', [AccountController::class, 'updateName'])->name('settings.name.update');
        Route::put('/settings/password', [AccountController::class, 'updatePassword'])
            ->name('settings.password.update')
            ->middleware('throttle:5,1'); // Limit 5 attempts per minute

        Route::get('/my-applications', [ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/my-applications/{application}', [ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/my-applications/{application}/onboarding', function (\App\Models\Application $application) {
            abort_unless($application->user_id === auth()->id(), 403);

            return \Inertia\Inertia::render('Applications/Onboarding', [
                'application' => $application->load(['internship.company', 'onboardingDocuments.verifier']),
                'documents' => $application->onboardingDocuments()->with('verifier')->get(),
            ]);
        })->name('applications.onboarding');
        Route::redirect('/applications', '/my-applications');
        Route::get('/applications/{application}', fn (\App\Models\Application $application) => redirect()->route('applications.show', $application));
        Route::get('/applications/{application}/onboarding', fn (\App\Models\Application $application) => redirect()->route('applications.onboarding', $application));
        Route::post('/my-applications/{application}/withdraw', [ApplicationController::class, 'withdraw'])->name('applications.withdraw');
        Route::post('/internships/{internship:slug}/apply', [ApplicationController::class, 'store'])->name('internships.apply');

        Route::get('/saved-internships', [SavedInternshipController::class, 'index'])->name('saved-internships.index');
        Route::post('/internships/{internship:slug}/toggle-save', [SavedInternshipController::class, 'toggle'])->name('internships.toggle-save');

        // Private Files (Hardened with Signed URLs)
        Route::get('/storage/private/{type}/{filename}', [FileController::class, 'show'])
            ->name('storage.private')
            ->middleware('signed');

        // Attendance Routes
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('/', [AttendanceController::class, 'index'])->name('index');
            Route::post('/check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
            Route::post('/check-out/{attendance}', [AttendanceController::class, 'checkOut'])->name('check-out');
            Route::post('/location', [AttendanceController::class, 'updateLocation'])->name('update-location');
            Route::post('/correction', [AttendanceController::class, 'requestCorrection'])->name('request-correction');
        });

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
            Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        });

        // AI Assistant Routes
        Route::prefix('ai')->name('ai.')->middleware(['ai_consent'])->group(function () {
            Route::post('/review-profile', [AiUserController::class, 'reviewProfile'])->name('review-profile');
            Route::post('/summarize-cv', [AiUserController::class, 'summarizeCv'])->name('summarize-cv');
            Route::get('/recommendations', [AiUserController::class, 'recommendInternships'])->name('recommendations');
            Route::post('/draft-letter', [AiUserController::class, 'draftApplicationLetter'])->name('draft-letter');
            Route::post('/interview-prep', [AiUserController::class, 'interviewPrep'])->name('interview-prep');
            Route::post('/nearby-recommendation', [AiNearbyController::class, 'recommend'])->name('nearby-recommendation');
        });

        // HR Routes
        Route::prefix('hr')->name('hr.')->group(function () {
            // Company Management (Accessible to any verified user to allow registration)
            Route::middleware(['auth', 'verified'])->group(function () {
                Route::get('/companies/create', [HrCompanyController::class, 'create'])->name('companies.create');
                Route::post('/companies', [HrCompanyController::class, 'store'])->name('companies.store');
            });

            // Protected HR Module
            Route::middleware(['role:hr,admin,super_admin', 'company_scope'])->group(function () {
                Route::get('/dashboard', [App\Http\Controllers\HR\DashboardController::class, 'index'])->name('dashboard');
                Route::get('/companies/select', [HrCompanyController::class, 'select'])->name('companies.select');
                Route::post('/companies/switch/{company}', [HrCompanyController::class, 'switch'])->name('companies.switch');
                Route::get('/company/edit', [HrCompanyController::class, 'edit'])->name('companies.edit');
                Route::put('/company', [HrCompanyController::class, 'update'])->name('companies.update');

                // Internship Management
                Route::resource('internships', App\Http\Controllers\HR\InternshipController::class);

                // Applications
                Route::get('/applications', [App\Http\Controllers\HR\ApplicationController::class, 'index'])->name('applications.index');
                Route::get('/applications/{application}', [App\Http\Controllers\HR\ApplicationController::class, 'show'])->name('applications.show');
                Route::post('/applications/{application}/status', [App\Http\Controllers\HR\ApplicationController::class, 'updateStatus'])->name('applications.update-status');
                Route::post('/applications/{application}/schedule', [App\Http\Controllers\HR\ApplicationController::class, 'scheduleInterview'])->name('applications.schedule-interview');
                Route::post('/applications/{application}/assign-mentor', [App\Http\Controllers\HR\ApplicationController::class, 'assignMentor'])->name('applications.assign-mentor');

                // Team Management
                Route::get('/team', [MemberController::class, 'index'])->name('team.index');
                Route::post('/team', [MemberController::class, 'store'])->name('team.store');
                Route::put('/team/{member}', [MemberController::class, 'update'])->name('team.update');
                Route::delete('/team/{member}', [MemberController::class, 'destroy'])->name('team.destroy');

                // Attendance
                Route::get('/attendance', [App\Http\Controllers\HR\AttendanceController::class, 'index'])->name('attendance.index');
                Route::post('/attendance/corrections/{correction}/approve', [App\Http\Controllers\HR\AttendanceController::class, 'approveCorrection'])->name('attendance.approve-correction');
            });
        });

        // Mentor Routes
        Route::prefix('mentor')->name('mentor.')->middleware(['role:mentor,admin,super_admin', 'company_scope'])->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Mentor\DashboardController::class, 'index'])->name('dashboard');
            Route::get('/mentees', [MenteeController::class, 'index'])->name('mentees.index');
            Route::get('/mentees/{application}', [MenteeController::class, 'show'])->name('mentees.show');
            Route::post('/mentees/{application}/feedback', [MenteeController::class, 'storeFeedback'])->name('mentees.feedback');

            // Task Management
            Route::post('/mentees/{application}/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.update-status');
            Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

            // Evaluation Management
            Route::post('/mentees/{application}/evaluations', [EvaluationController::class, 'store'])->name('evaluations.store');

            // Mentoring Sessions
            Route::post('/mentees/{application}/sessions', [SessionController::class, 'store'])->name('sessions.store');
            Route::patch('/mentoring-sessions/{session}/status', [SessionController::class, 'updateStatus'])->name('sessions.update-status');

            // Attendance
            Route::get('/attendance', [App\Http\Controllers\Mentor\AttendanceController::class, 'index'])->name('attendance.index');
            Route::get('/tasks', fn () => \Inertia\Inertia::render('Mentor/Tasks/Index'))->name('tasks.index');
        });

        // Admin Routes
        Route::prefix('admin')->name('admin.')->middleware(['role:admin'])->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

            // User Moderation
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Company Moderation
            Route::get('/companies', [AdminCompanyController::class, 'index'])->name('companies.index');
            Route::post('/companies/{company}/verify', [App\Http\Controllers\Admin\CompanyController::class, 'verify'])->name('companies.verify');
            Route::post('/companies/{company}/unverify', [App\Http\Controllers\Admin\CompanyController::class, 'unverify'])->name('companies.unverify');

            // Internship Moderation
            Route::get('/internships', [App\Http\Controllers\Admin\InternshipController::class, 'index'])->name('internships.index');
            Route::patch('/internships/{internship}/status', [App\Http\Controllers\Admin\InternshipController::class, 'updateStatus'])->name('internships.update-status');

            // Master Data: Locations
            // Moderation & Reports
            Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
            Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
            Route::post('/locations/{location}/toggle', [LocationController::class, 'toggleStatus'])->name('locations.toggle');
            Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

            // Security & Audit Logs
            Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
            Route::get('/security-events', [SecurityEventController::class, 'index'])->name('security.index');

            // External Integration
            Route::prefix('integrations')->name('integrations.')->group(function () {
                Route::get('/', [ExternalIntegrationController::class, 'index'])->name('index');
                Route::post('/', [ExternalIntegrationController::class, 'store'])->name('store');
                Route::get('/{externalIntegration}', [ExternalIntegrationController::class, 'show'])->name('show');
                Route::patch('/{externalIntegration}', [ExternalIntegrationController::class, 'update'])->name('update');
                Route::delete('/{externalIntegration}', [ExternalIntegrationController::class, 'destroy'])->name('destroy');
                Route::post('/{externalIntegration}/sync', [ExternalIntegrationController::class, 'sync'])->name('sync');

                // Review Workflow
                Route::get('/review', [IntegrationReviewController::class, 'index'])->name('review.index');
                Route::post('/review/{internship}/approve', [IntegrationReviewController::class, 'approve'])->name('review.approve');
                Route::post('/review/{internship}/reject', [IntegrationReviewController::class, 'reject'])->name('review.reject');
                Route::post('/review/bulk-approve', [IntegrationReviewController::class, 'bulkApprove'])->name('review.bulk-approve');
            });
        });

        // Admin AI Routes
        Route::prefix('admin/ai')->name('admin.ai.')->middleware(['role:admin,super_admin'])->group(function () {
            Route::post('/moderate-content', [AiAdminController::class, 'moderateContent'])->name('moderate-content');
            Route::post('/summarize-report', [AiAdminController::class, 'summarizeReport'])->name('summarize-report');
            Route::get('/suggest-master-data', [AiAdminController::class, 'suggestMasterData'])->name('suggest-master-data');
            Route::get('/privacy-report', [AiAdminController::class, 'getPrivacyComplianceReport'])->name('privacy-report');
        });

        // HR AI Routes
        Route::prefix('hr/ai')->name('hr.ai.')->middleware(['role:hr,admin,super_admin', 'company_scope'])->group(function () {
            Route::post('/generate-job-desc', [AiHrController::class, 'generateJobDescription'])->name('generate-job-desc');
            Route::post('/summarize-candidate', [AiHrController::class, 'summarizeCandidate'])->name('summarize-candidate');
            Route::post('/screen-candidate', [AiHrController::class, 'screenCandidate'])->name('screen-candidate');
            Route::post('/generate-interview-questions', [AiHrController::class, 'generateInterviewQuestions'])->name('generate-interview-questions');
            Route::get('/pipeline-insight', [AiHrController::class, 'getPipelineInsight'])->name('pipeline-insight');

            // Advanced Pipeline
            Route::prefix('pipeline')->name('pipeline.')->group(function () {
                Route::get('/{internship}/kanban', [PipelineController::class, 'getKanbanData'])->name('kanban');
                Route::post('/update-stage', [PipelineController::class, 'updateStage'])->name('update-stage');
                Route::get('/{application}/history', [PipelineController::class, 'getHistory'])->name('history');
            });

            // Scoring & Rubric
            Route::prefix('scoring')->name('scoring.')->group(function () {
                Route::post('/rubric', [ScoringController::class, 'setRubric'])->name('set-rubric');
                Route::post('/{application}/ai-score', [ScoringController::class, 'calculateAiScore'])->name('ai-score');
                Route::post('/{score}/review', [ScoringController::class, 'reviewScore'])->name('review');
            });
        });

        // Mentor AI Routes
        Route::prefix('mentor/ai')->name('mentor.ai.')->middleware(['role:mentor,admin,super_admin', 'company_scope'])->group(function () {
            Route::post('/generate-tasks', [AiMentorController::class, 'generateTasks'])->name('generate-tasks');
            Route::post('/draft-feedback', [AiMentorController::class, 'draftFeedback'])->name('draft-feedback');
            Route::get('/evaluation-summary', [AiMentorController::class, 'getEvaluationSummary'])->name('evaluation-summary');
        });

        // Super Admin AI Routes
        Route::prefix('super-admin/ai')->name('super-admin.ai.')->middleware(['role:super_admin'])->group(function () {
            Route::get('/audit-insight', [AiSuperAdminController::class, 'getAuditInsight'])->name('audit-insight');
            Route::get('/security-risk-summary', [AiSuperAdminController::class, 'getSecurityRiskSummary'])->name('security-risk-summary');
            Route::post('/diagnose-integration', [AiSuperAdminController::class, 'diagnoseIntegration'])->name('diagnose-integration');
            Route::get('/system-health', [AiSuperAdminController::class, 'getSystemHealth'])->name('system-health');
        });

        // Super Admin Routes
        Route::prefix('super-admin')->name('super-admin.')->middleware(['role:super_admin'])->group(function () {
            Route::get('/dashboard', [App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

            // Global User Management
            Route::get('/users', [App\Http\Controllers\SuperAdmin\UserController::class, 'index'])->name('users.index');
            Route::post('/users', [App\Http\Controllers\SuperAdmin\UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [App\Http\Controllers\SuperAdmin\UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/role', [App\Http\Controllers\SuperAdmin\UserController::class, 'updateRole'])->name('users.update-role');
            Route::patch('/users/{user}/ban', [App\Http\Controllers\SuperAdmin\UserController::class, 'ban'])->name('users.ban');
            Route::patch('/users/{user}/unban', [App\Http\Controllers\SuperAdmin\UserController::class, 'unban'])->name('users.unban');
            Route::delete('/users/{user}', [App\Http\Controllers\SuperAdmin\UserController::class, 'destroy'])->name('users.destroy');

            // Roles & Permissions
            Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RolePermissionController::class, 'storeRole'])->name('roles.store');
            Route::post('/roles/{role}/permissions', [RolePermissionController::class, 'syncPermissions'])->name('roles.sync-permissions');

            // Integrations
            Route::get('/integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            Route::post('/integrations', [IntegrationController::class, 'store'])->name('integrations.store');
            Route::put('/integrations/{integration}', [IntegrationController::class, 'update'])->name('integrations.update');
            Route::delete('/integrations/{integration}', [IntegrationController::class, 'destroy'])->name('integrations.destroy');

            // Audits & Security (Hardened)
            Route::middleware('throttle:30,1')->group(function () {
                Route::get('/activity-logs', [LogController::class, 'activityLogs'])->name('activity-logs.index');
                Route::get('/audit-logs', [LogController::class, 'auditLogs'])->name('audit-logs.index');
                Route::get('/security-events', [LogController::class, 'securityEvents'])->name('security-events.index');
            });

            // Settings & Feature Flags
            Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
            Route::patch('/settings/{setting}', [SettingController::class, 'updateSetting'])->name('settings.update');
            Route::post('/feature-flags/{flag}/toggle', [SettingController::class, 'toggleFeature'])->name('feature-flags.toggle');
        });
    });
});

// External Webhooks (No CSRF)
Route::post('/webhooks/integration/{provider}', [WebhookController::class, 'handle'])
    ->name('webhooks.integration');

// Web fallback for Laravel + Vue/Inertia monolith deployments.
// Keep this as the final route so browser refreshes on client-side paths load the app shell.
Route::fallback(function (Request $request) {
    if ($request->isMethod('GET') && ! $request->is('api/*', 'storage/*', 'build/*')) {
        return Inertia::render('Welcome');
    }

    return Inertia::render('Error', [
        'status' => 404,
        'request_id' => $request->header('X-Request-ID'),
    ])->toResponse($request)->setStatusCode(404);
});
