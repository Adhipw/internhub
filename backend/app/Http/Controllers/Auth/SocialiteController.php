<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;

class SocialiteController extends Controller
{
    public function redirect()
    {
        $flag = FeatureFlag::where('key', 'social_login')->first();
        if ($flag && ! $flag->is_enabled) {
            abort(403, 'Login dengan sosial media saat ini sedang dinonaktifkan.');
        }

        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        return $driver->stateless()->redirect();
    }

    public function callback()
    {
        $flag = FeatureFlag::where('key', 'social_login')->first();
        if ($flag && ! $flag->is_enabled) {
            abort(403, 'Login dengan sosial media saat ini sedang dinonaktifkan.');
        }

        try {
            /** @var GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $socialUser = $driver->stateless()->user();
        } catch (\Exception $e) {
            Log::error('Google OAuth Error: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return redirect()->route('login')->with('error', 'Gagal masuk dengan Google. '.$e->getMessage());
        }

        try {
            $user = DB::transaction(function () use ($socialUser) {
                $account = SocialAccount::where('provider', 'google')
                    ->where('provider_id', $socialUser->getId())
                    ->first();

                if ($account) {
                    return $account->user;
                }

                $user = User::where('email', $socialUser->getEmail())->first();

                if (! $user) {
                    $user = User::create([
                        'name' => $socialUser->getName(),
                        'email' => $socialUser->getEmail(),
                        'avatar_url' => $socialUser->getAvatar(),
                        'password' => bcrypt(Str::random(24)),
                        'email_verified_at' => now(), // Google emails are already verified
                    ]);

                    // Assign default role
                    $user->assignRole(UserRole::USER->value);
                }

                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => 'google',
                    'provider_id' => $socialUser->getId(),
                    'avatar' => $socialUser->getAvatar(),
                ]);

                return $user;
            });

            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            Log::error('Google OAuth Transaction Error: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());

            return redirect()->route('login')->with('error', 'Terjadi kesalahan saat memproses pendaftaran dengan Google. Silakan coba lagi.');
        }
    }
}
