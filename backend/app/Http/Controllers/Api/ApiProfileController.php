<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\UserDetail;
use App\Services\AuditService;
use App\Services\FileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ApiProfileController extends ApiBaseController
{
    public function __construct(
        protected FileService $fileService
    ) {}

    public function edit(): JsonResponse
    {
        $user = Auth::user();

        return $this->sendResponse([
            'user' => $user,
            'userDetail' => $user->detail ?? new UserDetail,
        ], 'Profile data retrieved');
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        $user = Auth::user();
        $detail = $user->detail ?? new UserDetail(['user_id' => $user->id]);

        $oldData = $detail->toArray();

        // Handle Files (Base64 or Multipart)
        if ($request->hasFile('avatar')) {
            $this->fileService->deletePrivate($user->avatar_url);
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

        if ($request->phone_number) {
            $user->update(['phone_number' => $request->phone_number]);
        }

        AuditService::log('profile_updated', $detail, $oldData, $detail->toArray());

        return $this->sendResponse($detail, 'Profile updated successfully');
    }
}
