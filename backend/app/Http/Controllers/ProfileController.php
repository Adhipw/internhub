<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserDetail;
use App\Services\AuditService;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function __construct(
        protected FileService $fileService
    ) {}

    public function edit()
    {
        return Inertia::render('Profile/Edit', [
            'userDetail' => Auth::user()->detail ?? new UserDetail,
        ]);
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = Auth::user();
        $detail = $user->detail ?? new UserDetail(['user_id' => $user->id]);

        $oldData = $detail->toArray();

        // Handle Files
        if ($request->hasFile('avatar')) {
            $this->fileService->deletePrivate($user->avatar_url); // Assume avatar is stored similarly
            $user->avatar_url = $this->fileService->uploadPublic($request->file('avatar'), 'avatars', "avatar_{$user->id}_");
            $user->save();
        }

        if ($request->hasFile('cv')) {
            $this->fileService->deletePrivate($detail->cv_path);
            $detail->cv_path = $this->fileService->uploadPrivate($request->file('cv'), 'cvs', "cv_{$user->id}_");
        }

        if ($request->hasFile('portfolio')) {
            $this->fileService->deletePrivate($detail->portfolio_path);
            $detail->portfolio_path = $this->fileService->uploadPrivate($request->file('portfolio'), 'portfolios', "portfolio_{$user->id}_");
        }

        // Update Biodata
        $detail->bio = $request->bio;
        $detail->address = $request->address;
        $detail->education = $request->education;
        $detail->skills = $request->skills;

        if ($request->has('ai_consent') && $detail->ai_consent !== (bool) $request->ai_consent) {
            $detail->ai_consent = (bool) $request->ai_consent;
            $detail->ai_consent_updated_at = now();
        }

        $detail->save();

        // Update User Level Info
        if ($request->phone_number) {
            $user->update(['phone_number' => $request->phone_number]);
        }

        AuditService::log('profile_updated', $detail, $oldData, $detail->toArray());

        return back()->with('status', 'profile-updated');
    }

    public function updateAiConsent(Request $request)
    {
        $request->validate([
            'consent' => 'required|boolean',
        ]);

        $user = Auth::user();
        $detail = $user->detail ?? new UserDetail(['user_id' => $user->id]);

        $detail->ai_consent = $request->consent;
        $detail->ai_consent_updated_at = now();
        $detail->save();

        return back()->with('status', 'ai-consent-updated');
    }
}
