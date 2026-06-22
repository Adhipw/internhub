<?php

namespace App\Http\Controllers\Api;

use App\Enums\CompanyRole;
use App\Models\CompanyMember;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class ApiHrMemberController extends ApiBaseController
{
    private function getCompanyId(Request $request)
    {
        $user = $request->user();
        $membership = $user->companyMemberships()->where('is_active', true)->first();

        return $membership ? $membership->company_id : null;
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        if (! $companyId) {
            return $this->sendError('You are not associated with any active company.', [], 403);
        }

        $members = CompanyMember::where('company_id', $companyId)
            ->with('user')
            ->get();

        $roles = collect(CompanyRole::cases())->map(fn ($role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ]);

        return $this->sendResponse([
            'members' => $members,
            'roles' => $roles,
        ], 'Members retrieved successfully.');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        if (! $companyId) {
            return $this->sendError('You are not associated with any active company.', [], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'role' => ['required', new Enum(CompanyRole::class)],
        ]);

        $user = User::where('email', $request->email)->first();

        // Check if already a member
        $exists = CompanyMember::where('company_id', $companyId)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return $this->sendError('Validation Error', ['email' => ['User ini sudah menjadi anggota tim.']], 422);
        }

        $member = CompanyMember::create([
            'company_id' => $companyId,
            'user_id' => $user->id,
            'role' => $request->role,
            'is_active' => true,
        ]);

        AuditService::log('company_member_added', $member, "Member added: {$user->name} as {$request->role}");

        return $this->sendResponse($member->load('user'), 'Anggota tim berhasil ditambahkan.', 201);
    }

    public function update(Request $request, CompanyMember $member): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        if (! $companyId || $member->company_id !== $companyId) {
            return $this->sendError('Access Denied', [], 403);
        }

        $request->validate([
            'role' => ['required', new Enum(CompanyRole::class)],
            'is_active' => 'required|boolean',
        ]);

        $member->update($request->only(['role', 'is_active']));

        AuditService::log('company_member_updated', $member, "Member status/role updated for: {$member->user->name}");

        return $this->sendResponse($member->load('user'), 'Data anggota tim diperbarui.');
    }

    public function destroy(Request $request, CompanyMember $member): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        if (! $companyId || $member->company_id !== $companyId) {
            return $this->sendError('Access Denied', [], 403);
        }

        if ($member->user_id === $request->user()->id) {
            return $this->sendError('Validation Error', ['error' => ['Anda tidak bisa menghapus diri sendiri.']], 422);
        }

        AuditService::log('company_member_removed', $member, "Member removed: {$member->user->name}");

        $member->delete();

        return $this->sendResponse(null, 'Anggota tim berhasil dihapus.');
    }
}
