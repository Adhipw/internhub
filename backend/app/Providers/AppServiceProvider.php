<?php

namespace App\Providers;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Events\ProfileUpdated;
use App\Listeners\ProcessLog;
use App\Mail\Transport\ResendTransport;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\SecurityEvent;
use App\Policies\LogPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('mail.manager', function ($manager) {
            $manager->extend('resend_custom', function () {
                return new ResendTransport(
                    config('services.resend.key')
                );
            });

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for cURL error 77 on local Windows environment
        if (config('app.env') === 'local' && file_exists(storage_path('cacert.pem'))) {
            ini_set('curl.cainfo', storage_path('cacert.pem'));
            ini_set('openssl.cafile', storage_path('cacert.pem'));
        }

        // Force HTTPS in production (crucial for Railway and Google OAuth)
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // ELITE STANDARDS: N+1 Prevention
        Model::preventLazyLoading(! app()->isProduction());

        // Implicitly grant "Super Admin" role all permissions
        // This works in the gate-checked permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole(UserRole::SUPER_ADMIN->value) ? true : null;
        });

        Gate::policy(ActivityLog::class, LogPolicy::class);
        Gate::policy(AuditLog::class, LogPolicy::class);
        Gate::policy(SecurityEvent::class, LogPolicy::class);

        Event::listen(
            ProfileUpdated::class,
            ProcessLog::class
        );

        // Register Gates based on Permissions Enum
        foreach (PermissionEnum::cases() as $permission) {
            Gate::define($permission->value, function ($user) use ($permission) {
                return $user->hasPermissionTo($permission->value);
            });
        }

        Gate::define('use-ai', function ($user) {
            return $user->email_verified_at !== null && $user->is_active;
        });

        Gate::define('view-notification-channel', function ($user, $id) {
            return (int) $user->id === (int) $id;
        });
    }
}
