<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $this->userProp($request),
                'session_id' => $request->session()->getId(),
            ],
            'locale' => app()->getLocale(),
            'translations' => fn () => Cache::remember('translations_'.app()->getLocale(), 3600, function () {
                $path = base_path('lang/'.app()->getLocale().'.json');

                return file_exists($path) ? json_decode(file_get_contents($path), true) : [];
            }),
            'stats' => fn () => $this->publicStats(),
            'feature_flags' => fn () => Cache::remember('global_feature_flags', 60, function () {
                try {
                    return FeatureFlag::pluck('is_enabled', 'key')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            }),
            'public_settings' => fn () => Cache::remember('global_public_settings', 60, function () {
                try {
                    return \App\Models\SystemSetting::where('is_sensitive', false)->pluck('value', 'key')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            }),
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    private function userProp(Request $request): ?User
    {
        if (! $request->user()) {
            return null;
        }

        try {
            return $request->user()->load(['detail', 'roles', 'permissions', 'roles.permissions']);
        } catch (QueryException $e) {
            Log::warning('Failed to load shared authenticated user relations', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
            ]);

            return $request->user();
        }
    }

    /**
     * Keep public pages renderable even when a fresh Railway database has not
     * been migrated yet. The actual failing query is still logged server-side.
     *
     * @return array{applicants_count: int, companies_count: int}
     */
    private function publicStats(): array
    {
        return Cache::remember('public_stats', 60, function () {
            $applicantsCount = 0;
            $companiesCount = 0;

            try {
                $applicantsCount = User::where('role', 'user')->count();
                $companiesCount = Company::where('is_verified', true)->count();
            } catch (QueryException $e) {
                Log::warning('Failed to load shared public stats', [
                    'message' => $e->getMessage(),
                ]);
            }

            return [
                'applicants_count' => $applicantsCount,
                'companies_count' => $companiesCount,
            ];
        });
    }
}
