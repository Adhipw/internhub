<?php

/* [NEW] [api.php](file:///c:/Users/ASUS/Downloads/rollback_backups/backend/routes/api.php) */

use App\Http\Controllers\AiHrController;
use App\Http\Controllers\AiPublicController;
use App\Http\Controllers\AiUserController;
use App\Http\Controllers\Api\ApiAdminAuditController;
use App\Http\Controllers\Api\ApiAdminCompanyController;
use App\Http\Controllers\Api\ApiAdminDashboardController;
use App\Http\Controllers\Api\ApiAdminInternshipController;
use App\Http\Controllers\Api\ApiAdminLocationController;
use App\Http\Controllers\Api\ApiAdminReportController;
use App\Http\Controllers\Api\ApiAdminUserController;
use App\Http\Controllers\Api\ApiApplicationController;
use App\Http\Controllers\Api\ApiAttendanceController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiCertificateController;
use App\Http\Controllers\Api\ApiExportController;
use App\Http\Controllers\Api\ApiExternalIntegrationController;
use App\Http\Controllers\Api\ApiFileController;
use App\Http\Controllers\Api\ApiHealthCheckController;
use App\Http\Controllers\Api\ApiHrApplicationController;
use App\Http\Controllers\Api\ApiHrAttendanceController;
use App\Http\Controllers\Api\ApiHrDashboardController;
use App\Http\Controllers\Api\ApiHrInternshipController;
use App\Http\Controllers\Api\ApiImportController;
use App\Http\Controllers\Api\ApiMasterDataController;
use App\Http\Controllers\Api\ApiMenteeController;
use App\Http\Controllers\Api\ApiMentorDashboardController;
use App\Http\Controllers\Api\ApiMentorEvaluationController;
use App\Http\Controllers\Api\ApiNotificationController;
use App\Http\Controllers\Api\ApiOnboardingController;
use App\Http\Controllers\Api\ApiProfileController;
use App\Http\Controllers\Api\ApiPublicInternshipController;
use App\Http\Controllers\Api\ApiRoleController;
use App\Http\Controllers\Api\ApiSavedInternshipController;
use App\Http\Controllers\Api\ApiSuperAdminDashboardController;
use App\Http\Controllers\Api\ApiSuperAdminSettingController;
use App\Http\Controllers\Api\ApiSuperAdminUserController;
use App\Http\Controllers\Api\ApiUserDashboardController;
use App\Http\Controllers\Api\ApplicationMessageController;
use App\Http\Controllers\Mentor\AttendanceController;
use App\Http\Controllers\NearbyController;
use App\Http\Middleware\CheckBanned;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public Auth Routes
Route::prefix('v1')->group(function () {
    Route::get('/health', [ApiHealthCheckController::class, 'index']);

    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/auth/email/verify-otp', [ApiAuthController::class, 'verifyOtp'])->middleware('throttle:5,1');
    Route::post('/auth/email/resend-otp', [ApiAuthController::class, 'resendOtp'])->middleware('throttle:3,1');

    Route::post('/auth/password/email', [ApiAuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('/auth/password/reset', [ApiAuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    // Master Data
    Route::get('/industries', [ApiMasterDataController::class, 'industries']);
    Route::get('/locations', [ApiMasterDataController::class, 'locations']);
});

// Protected Routes
Route::middleware(['auth:sanctum', CheckBanned::class])->prefix('v1')->group(function () {
    Route::get('/me', [ApiAuthController::class, 'me']);
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [ApiNotificationController::class, 'index']);
        Route::post('/{id}/read', [ApiNotificationController::class, 'markAsRead']);
        Route::post('/read-all', [ApiNotificationController::class, 'markAllAsRead']);
    });

    // Private Files
    Route::get('/files/private', [ApiFileController::class, 'showPrivate']);

    // User Profile
    Route::get('/profile', [ApiProfileController::class, 'edit']);
    Route::post('/profile', [ApiProfileController::class, 'update']);

    // User Applications
    Route::get('/applications', [ApiApplicationController::class, 'index']);
    Route::get('/applications/{application}', [ApiApplicationController::class, 'show']);
    Route::post('/internships/{internship:slug}/apply', [ApiApplicationController::class, 'store']);
    Route::post('/applications/{application}/withdraw', [ApiApplicationController::class, 'withdraw']);
    Route::get('/applications/{application}/messages', [ApplicationMessageController::class, 'index']);
    Route::post('/applications/{application}/messages', [ApplicationMessageController::class, 'store']);
    Route::get('/saved-internships', [ApiSavedInternshipController::class, 'index']);
    Route::post('/internships/{internship:slug}/toggle-save', [ApiSavedInternshipController::class, 'toggle']);

    // User Attendance
    Route::get('/attendance', [ApiAttendanceController::class, 'index']);
    Route::post('/attendance/check-in', [ApiAttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out/{attendance}', [ApiAttendanceController::class, 'checkOut']);
    Route::post('/attendance/update-location', [ApiAttendanceController::class, 'updateLocation']);
    Route::post('/attendance/correction', [ApiAttendanceController::class, 'requestCorrection']);

    // Candidate dashboard. Keep the old /dashboard/user path as a compatibility alias.
    Route::middleware('verified')->group(function () {
        Route::get('/candidate/dashboard', [ApiUserDashboardController::class, 'index']);
        Route::get('/dashboard/user', [ApiUserDashboardController::class, 'index']);
    });

    // AI Assistant Routes
    Route::prefix('ai')->group(function () {
        Route::post('/review-profile', [AiUserController::class, 'reviewProfile']);
        Route::post('/summarize-cv', [AiUserController::class, 'summarizeCv']);
        Route::get('/recommendations', [AiUserController::class, 'recommendInternships']);
        Route::post('/draft-letter', [AiUserController::class, 'draftApplicationLetter']);
        Route::post('/interview-prep', [AiUserController::class, 'interviewPrep']);
    });

    // Mentor Evaluations & Management
    Route::middleware(['role:mentor|hr|admin'])->prefix('mentor')->group(function () {
        Route::get('/dashboard', [ApiMentorDashboardController::class, 'index']);

        Route::get('/evaluations', [ApiMentorEvaluationController::class, 'index']);
        Route::post('/evaluations', [ApiMentorEvaluationController::class, 'store']);
        Route::get('/evaluations/{mentor_evaluation}', [ApiMentorEvaluationController::class, 'show']);

        // Mentee & Task Management
        Route::get('/mentees', [ApiMenteeController::class, 'index']);
        Route::get('/mentees/{application}', [ApiMenteeController::class, 'show']);
        Route::post('/mentees/{application}/feedback', [ApiMenteeController::class, 'storeFeedback']);
        Route::post('/mentees/{application}/tasks', [ApiMenteeController::class, 'storeTask']);
        Route::patch('/tasks/{task}/status', [ApiMenteeController::class, 'updateTaskStatus']);
        Route::get('/tasks', [ApiMenteeController::class, 'allTasks']);

        // Attendance Monitoring for Mentees
        Route::get('/attendance', [AttendanceController::class, 'index']);
    });

    Route::middleware(['role:mentor|hr|admin'])->get('/dashboard/mentor', [ApiMentorDashboardController::class, 'index']);

    // Super Admin global user management
    Route::middleware(['role:super_admin'])->prefix('super-admin')->group(function () {
        Route::get('/dashboard', [ApiSuperAdminDashboardController::class, 'index']);
        Route::get('/users', [ApiSuperAdminUserController::class, 'index']);
        Route::get('/users/export', [ApiExportController::class, 'exportUsers']);
        Route::get('/audit-logs/export', [ApiExportController::class, 'exportAuditLogs']);
        Route::post('/users', [ApiSuperAdminUserController::class, 'store']);
        Route::put('/users/{user}', [ApiSuperAdminUserController::class, 'update']);
        Route::delete('/users/{user}', [ApiSuperAdminUserController::class, 'destroy']);
        Route::post('/users/{user}/ban', [ApiSuperAdminUserController::class, 'ban']);
        Route::post('/users/{user}/unban', [ApiSuperAdminUserController::class, 'unban']);

        // Role & Permission Management
        Route::get('/roles-data', [ApiRoleController::class, 'index']);
        Route::post('/roles-data', [ApiRoleController::class, 'store']);
        Route::get('/permissions-data', [ApiRoleController::class, 'permissions']);
        Route::put('/roles-data/{role}', [ApiRoleController::class, 'update']);
        Route::delete('/roles-data/{role}', [ApiRoleController::class, 'destroy']);

        // External Integration
        Route::get('/integrations', [ApiExternalIntegrationController::class, 'index']);
        Route::post('/integrations', [ApiExternalIntegrationController::class, 'store']);
        Route::patch('/integrations/{integration}', [ApiExternalIntegrationController::class, 'update']);
        Route::delete('/integrations/{integration}', [ApiExternalIntegrationController::class, 'destroy']);
        Route::post('/integrations/{integration}/sync', [ApiExternalIntegrationController::class, 'sync']);
        Route::get('/integrations/{integration}/logs', [ApiExternalIntegrationController::class, 'logs']);

        // System Settings & Feature Flags
        Route::get('/settings', [ApiSuperAdminSettingController::class, 'index']);
        Route::patch('/settings/{setting}', [ApiSuperAdminSettingController::class, 'updateSetting']);
        Route::post('/feature-flags/{flag}/toggle', [ApiSuperAdminSettingController::class, 'toggleFeature']);

        // Audit & Security (Super Admin)
        Route::get('/audit-logs', [ApiAdminAuditController::class, 'auditLogs']);
        Route::get('/security-events', [ApiAdminAuditController::class, 'securityEvents']);
    });

    // Admin Routes
    Route::middleware(['role:admin|super_admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [ApiAdminDashboardController::class, 'index']);

        // User Moderation
        Route::get('/users', [ApiAdminUserController::class, 'index']);
        Route::post('/users/{user}/toggle-status', [ApiAdminUserController::class, 'toggleStatus']);
        Route::delete('/users/{user}', [ApiAdminUserController::class, 'destroy']);

        // Company Moderation
        Route::get('/companies', [ApiAdminCompanyController::class, 'index']);
        Route::post('/companies/{company}/verify', [ApiAdminCompanyController::class, 'verify']);
        Route::post('/companies/{company}/unverify', [ApiAdminCompanyController::class, 'unverify']);

        // Internship Moderation
        Route::get('/internships', [ApiAdminInternshipController::class, 'index']);
        Route::patch('/internships/{internship}/status', [ApiAdminInternshipController::class, 'updateStatus']);

        // Location Management
        Route::get('/locations', [ApiAdminLocationController::class, 'index']);
        Route::post('/locations', [ApiAdminLocationController::class, 'store']);
        Route::post('/locations/{location}/toggle', [ApiAdminLocationController::class, 'toggle']);
        Route::delete('/locations/{location}', [ApiAdminLocationController::class, 'destroy']);

        // Audit & Security
        Route::get('/audit-logs', [ApiAdminAuditController::class, 'auditLogs']);
        Route::get('/security-events', [ApiAdminAuditController::class, 'securityEvents']);

        // Reports
        Route::get('/reports', [ApiAdminReportController::class, 'index']);
    });

    Route::middleware(['role:admin|super_admin'])->get('/dashboard/admin', [ApiAdminDashboardController::class, 'index']);

    // Batch Imports (Super Admin)
    Route::middleware('role:super_admin')->group(function () {
        Route::post('/import/users', [ApiImportController::class, 'importUsers']);
    });

    // HR Routes
    Route::middleware(['role:hr|admin|super_admin'])->prefix('hr')->group(function () {
        Route::get('/dashboard', [ApiHrDashboardController::class, 'index']);

        // Internship Management
        Route::get('/internships', [ApiHrInternshipController::class, 'index']);
        Route::post('/internships', [ApiHrInternshipController::class, 'store']);
        Route::get('/internships/{internship}', [ApiHrInternshipController::class, 'show']);
        Route::put('/internships/{internship}', [ApiHrInternshipController::class, 'update']);
        Route::delete('/internships/{internship}', [ApiHrInternshipController::class, 'destroy']);

        // Application Management
        Route::get('/applications', [ApiHrApplicationController::class, 'index']);
        Route::get('/applications/{application}', [ApiHrApplicationController::class, 'show']);
        Route::get('/applications/{application}/ai-summary', [ApiHrApplicationController::class, 'getAiSummary']);
        Route::post('/applications/{application}/status', [ApiHrApplicationController::class, 'updateStatus']);
        Route::post('/applications/{application}/schedule-interview', [ApiHrApplicationController::class, 'scheduleInterview']);
        Route::post('/applications/{application}/assign-mentor', [ApiHrApplicationController::class, 'assignMentor']);

        // Attendance Monitoring
        Route::get('/attendance', [ApiHrAttendanceController::class, 'index']);
        Route::get('/attendance/stats', [ApiHrAttendanceController::class, 'stats']);

        // HR AI
        Route::prefix('ai')->group(function () {
            Route::post('/generate-job-desc', [AiHrController::class, 'generateJobDescription']);
            Route::post('/summarize-candidate', [AiHrController::class, 'summarizeCandidate']);
            Route::post('/screen-candidate', [AiHrController::class, 'screenCandidate']);
            Route::post('/generate-interview-questions', [AiHrController::class, 'generateInterviewQuestions']);
            Route::get('/pipeline-insight', [AiHrController::class, 'getPipelineInsight']);
        });

        Route::post('/import/internships', [ApiImportController::class, 'importInternships']);
    });

    Route::middleware(['role:hr|admin|super_admin'])->get('/dashboard/hr', [ApiHrDashboardController::class, 'index']);

    // Common Batch Import Utilities
    Route::get('/import/template/{type}', [ApiImportController::class, 'downloadTemplate']);
    // Onboarding Documents
    Route::get('/applications/{application}/onboarding', [ApiOnboardingController::class, 'index']);
    Route::post('/applications/{application}/onboarding/upload', [ApiOnboardingController::class, 'upload']);
    Route::post('/onboarding-documents/{document}/verify', [ApiOnboardingController::class, 'verify']);
    Route::get('/onboarding-documents/{document}/download', [ApiOnboardingController::class, 'download']);

    // Certificates
    Route::get('/applications/{application}/certificate', [ApiCertificateController::class, 'show']);
});

// Public Internship Routes
Route::prefix('v1')->group(function () {
    Route::get('/stats/public', [ApiPublicInternshipController::class, 'stats']);
    Route::get('/internships', [ApiPublicInternshipController::class, 'index']);
    Route::get('/internships/{slug}', [ApiPublicInternshipController::class, 'show']);
    Route::get('/companies', [ApiPublicInternshipController::class, 'companies']);
    Route::get('/companies/{slug}', [ApiPublicInternshipController::class, 'companyProfile']);

    // Public AI Routes
    Route::prefix('ai/public')->group(function () {
        Route::post('/finder', [AiPublicController::class, 'internshipFinder']);
        Route::post('/faq', [AiPublicController::class, 'faq']);
        Route::post('/nearby', [NearbyController::class, 'publicSearch']);
    });

    // Translations
    Route::get('/translations/{locale}', function ($locale) {
        $path = lang_path("{$locale}.json");
        $source = 'lang_path';
        if (! file_exists($path)) {
            $path = base_path("lang/{$locale}.json");
            $source = 'base_path';
            if (! file_exists($path)) {
                return response()->json([
                    'error' => 'File not found',
                    'attempted' => [lang_path("{$locale}.json"), base_path("lang/{$locale}.json")],
                ], 404);
            }
        }
        $content = file_get_contents($path);
        $decoded = json_decode($content, true);

        return response()->json([
            'debug' => [
                'locale' => $locale,
                'path' => $path,
                'source' => $source,
                'valid' => ! is_null($decoded),
                'size' => strlen($content),
            ],
            'translations' => $decoded ?: [],
        ]);
    });
});
