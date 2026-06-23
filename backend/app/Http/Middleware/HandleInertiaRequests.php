<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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
            'translations' => file_exists(base_path('lang/' . app()->getLocale() . '.json'))
                ? json_decode(file_get_contents(base_path('lang/' . app()->getLocale() . '.json')), true)
                : [],
            'stats' => fn() => $this->publicStats(),
            'feature_flags' => fn() => \Illuminate\Support\Facades\Cache::remember('global_feature_flags', 60, function () {
                try {
                    return \App\Models\FeatureFlag::pluck('is_enabled', 'key')->toArray();
                } catch (\Exception $e) {
                    return [];
                }
            }),
            'ziggy' => fn() => [
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
            return $request->user()->load(['detail', 'roles']);
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
        try {
            return [
                'applicants_count' => User::where('role', 'user')->count(),
                'companies_count' => Company::where('is_verified', true)->count(),
            ];
        } catch (QueryException $e) {
            Log::warning('Failed to load shared public stats', [
                'message' => $e->getMessage(),
            ]);

            return [
                'applicants_count' => 0,
                'companies_count' => 0,
            ];
        }
    }
}
