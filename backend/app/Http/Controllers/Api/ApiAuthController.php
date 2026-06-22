<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\RegisterUserAction;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Notifications\Auth\EmailVerificationOtpNotification;
use App\Notifications\Auth\PasswordResetOtpNotification;
use App\Services\Auth\EmailVerificationOtpService;
use App\Services\Auth\PasswordResetOtpService;
use App\Services\Auth\SecurityEventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\TransientToken;

class ApiAuthController extends ApiBaseController
{
    /**
     * Handle user login and return token.
     */
    public function login(LoginRequest $request, SecurityEventLogger $eventLogger): JsonResponse
    {
        $request->authenticate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->banned_at) {
            Auth::logout();

            return $this->sendError(__('Your account is currently restricted.'), [
                'email' => __('Account is banned. Reason: :reason', ['reason' => $user->banned_reason]),
            ], 403);
        }

        // Handle 'Remember Me' by setting token expiration (14 days or 1 day)
        $expiresAt = $request->boolean('remember')
            ? now()->addDays(14)
            : now()->addDay();

        $tokenResult = $user->createToken('auth_token', ['*'], $expiresAt);

        $eventLogger->log('api_login', "User {$user->email} logged in via API");

        return $this->sendResponse([
            'access_token' => $tokenResult->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expiresAt->toIso8601String(),
            'user' => new UserResource($user),
        ], __('Welcome back! Login successful.'));
    }

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request, RegisterUserAction $registerAction, EmailVerificationOtpService $otpService): JsonResponse
    {
        $user = $registerAction->execute($request->validated());

        $otp = $otpService->generate($user->email);
        $otpDeliveryFailed = false;

        try {
            $user->notify(new EmailVerificationOtpNotification($otp));
        } catch (\Throwable $e) {
            $otpDeliveryFailed = true;
            Log::error('Failed to send API email verification OTP', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->sendResponse([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'otp_delivery_failed' => $otpDeliveryFailed,
        ], $otpDeliveryFailed
            ? __('Registration successful, but the verification code could not be sent. Please try resending it.')
            : __('Registration successful. We have sent a verification code to your email.')
        );
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user) {
            $token = $user->currentAccessToken();

            // Delete personal access token if it exists and is not a transient token (session-based)
            if ($token && ! ($token instanceof TransientToken)) {
                $token->delete();
            }

            // Always clear the web guard session for security in stateful SPA
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->sendResponse(null, __('Successfully logged out. See you again!'));
    }

    /**
     * Verify email with OTP.
     */
    public function verifyOtp(Request $request, EmailVerificationOtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        if (! $otpService->verify($request->email, $request->otp)) {
            return $this->sendError(__('The verification code is invalid or has expired.'), [], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->markEmailAsVerified();
        }

        return $this->sendResponse(null, __('Email verified successfully. You can now access all features.'));
    }

    /**
     * Resend verification OTP.
     */
    public function resendOtp(Request $request, EmailVerificationOtpService $otpService): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return $this->sendError(__('We couldn\'t find a user with that email address.'), [], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->sendError(__('Your email address is already verified.'), [], 422);
        }

        $otp = $otpService->generate($user->email);

        try {
            $user->notify(new EmailVerificationOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Failed to resend API email verification OTP', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->sendError(__('The verification code could not be sent. Please check mail configuration and try again.'), [], 503);
        }

        return $this->sendResponse(null, __('A new verification code has been sent to your email.'));
    }

    /**
     * Handle forgot password request.
     */
    public function forgotPassword(Request $request, PasswordResetOtpService $otpService): JsonResponse
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp = $otpService->generate($user->email);

        try {
            $user->notify(new PasswordResetOtpNotification($otp));
        } catch (\Throwable $e) {
            Log::error('Failed to send API password reset OTP', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->sendError(__('The password reset code could not be sent. Please check mail configuration and try again.'), [], 503);
        }

        return $this->sendResponse(null, __('Password reset code has been sent to your email.'));
    }

    /**
     * Handle password reset with OTP.
     */
    public function resetPassword(Request $request, PasswordResetOtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if (! $otpService->verify($request->email, $request->otp)) {
            return $this->sendError(__('The password reset code is invalid or has expired.'), [], 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return $this->sendResponse(null, __('Your password has been reset successfully. Please login with your new password.'));
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->sendResponse(
            new UserResource($request->user()->load(['roles', 'detail'])),
            __('Profile retrieved successfully')
        );
    }
}
