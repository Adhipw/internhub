<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterUserAction;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\Auth\EmailVerificationOtpNotification;
use App\Notifications\Auth\SuspiciousLoginNotification;
use App\Services\Auth\EmailVerificationOtpService;
use App\Services\Auth\LoginAttemptLogger;
use App\Services\Auth\SecurityEventLogger;
use App\Services\Auth\SuspiciousLoginService;
use App\Services\Auth\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Get(
        path: '/login',
        summary: 'Show login page',
        responses: [
            new OA\Response(response: 200, description: 'Login page view'),
        ]
    )]
    public function showLogin()
    {
        return Inertia::render('Auth/Login');
    }

    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(RegisterRequest $request, RegisterUserAction $registerAction, EmailVerificationOtpService $otpService)
    {
        $user = $registerAction->execute($request->validated());

        // Roles that bypass OTP/Verification
        $bypassRoles = [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::HR->value,
            UserRole::MENTOR->value,
        ];

        if ($user->hasRole($bypassRoles)) {
            $user->markEmailAsVerified();
            Auth::login($user);

            return redirect()->route('dashboard');
        }

        $otp = $otpService->generate($user->email);
        $otpDeliveryError = null;

        try {
            $user->notify(new EmailVerificationOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Failed to send email verification OTP', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            $otpDeliveryError = 'Kode OTP gagal dikirim ke email. Periksa konfigurasi mailer atau coba kirim ulang.';
        }

        Auth::login($user);
        $request->session()->forget('dev_email_verification_otp');

        if (app()->environment(['local', 'testing'])) {
            $request->session()->put('dev_email_verification_otp', $otp);
        }

        $redirect = redirect()->route('verification.notice');

        if ($otpDeliveryError) {
            $redirect->with('otp_delivery_error', $otpDeliveryError);
        }

        return $redirect;
    }

    public function login(
        LoginRequest $request,
        LoginAttemptLogger $logger,
        SuspiciousLoginService $suspiciousService,
        SecurityEventLogger $eventLogger
    ) {
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->banned_at) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => __('Akun Anda telah diblokir. Alasan: ').$user->banned_reason,
            ]);
        }

        // Check suspicious login
        if ($suspiciousService->isSuspicious($user, $request)) {
            $user->notify(new SuspiciousLoginNotification([
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]));
            $eventLogger->log('suspicious_login', 'Login terdeteksi dari IP/perangkat baru: '.$request->ip());
        }

        $request->session()->regenerate();

        $logger->log($request, $user->id, $request->email, true);
        $eventLogger->log('login', 'User berhasil login');

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('url.intended');

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function sessions(UserSessionService $sessionService)
    {
        return Inertia::render('Profile/Sessions', [
            'sessions' => $sessionService->getActiveSessions(),
        ]);
    }

    public function logoutOtherDevices(Request $request, UserSessionService $sessionService, SecurityEventLogger $eventLogger)
    {
        $sessionService->logoutOtherDevices($request->session()->getId());

        $eventLogger->log('logout_others', 'User mengeluarkan semua perangkat lain');

        return back()->with('status', 'Berhasil mengeluarkan semua perangkat lain.');
    }
}
