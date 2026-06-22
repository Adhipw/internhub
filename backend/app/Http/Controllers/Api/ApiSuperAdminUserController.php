<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ApiSuperAdminUserController extends ApiBaseController
{
    protected $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    public function index(Request $request)
    {
        $query = User::with('roles'); // Eager load roles for accuracy

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('roles', function ($sub) use ($request) {
                    $sub->where('name', $request->role);
                })->orWhere('role', $request->role);
            });
        }

        if ($request->status) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->latest()->paginate(15);

        return $this->sendResponse([
            'users' => $users,
            'roles' => ['super_admin', 'admin', 'hr', 'mentor', 'user'],
        ], 'Users retrieved successfully');
    }

    public function store(Request $request)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $this->normalizeBooleanInput($request->input('is_active')),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')],
            'password' => 'required|string|min:8',
            'role' => ['required', Rule::in(['admin', 'hr', 'mentor', 'user', 'super_admin'])],
            'is_active' => 'sometimes|boolean',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $user = new User;
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->phone_number = $validated['phone_number'] ?? null;
        $user->password = Hash::make($validated['password']);
        $user->role = $validated['role']; // Column
        $user->is_active = $validated['is_active'] ?? true;
        $user->email_verified_at = now();
        $user->save();

        if ($request->hasFile('avatar')) {
            $path = $this->fileService->uploadPublic($request->file('avatar'), 'avatars');
            $user->avatar = $path;
            $user->save();
        }

        // Sync with Spatie Roles
        $user->syncRoles([$validated['role']]);

        return $this->sendResponse($user->load('roles'), 'User created and role synced successfully');
    }

    public function update(Request $request, User $user)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $this->normalizeBooleanInput($request->input('is_active')),
            ]);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone_number')->ignore($user->id)],
            'role' => ['sometimes', Rule::in(['admin', 'hr', 'mentor', 'user', 'super_admin'])],
            'is_active' => 'sometimes|boolean',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($user->id === auth()->id()) {
            if (array_key_exists('role', $validated) && $validated['role'] !== $user->getAttributeFromArray('role')) {
                return $this->sendError('Cannot change your own role', [], 422);
            }

            if (array_key_exists('is_active', $validated) && ! $validated['is_active']) {
                return $this->sendError('Cannot deactivate your own account', [], 422);
            }
        }

        if (array_key_exists('role', $validated)) {
            $user->role = $validated['role']; // Force update the column
            $user->syncRoles([$validated['role']]); // Sync Spatie
            unset($validated['role']);
        }

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        unset($validated['avatar']);
        $user->fill($validated);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                $this->fileService->delete($user->avatar);
            }
            $path = $this->fileService->uploadPublic($request->file('avatar'), 'avatars');
            $user->avatar = $path;
        }

        $user->save();

        return $this->sendResponse($user->load('roles'), 'User updated successfully');
    }

    private function normalizeBooleanInput(mixed $value): mixed
    {
        if (is_bool($value) || $value === 1 || $value === 0 || $value === '1' || $value === '0') {
            return $value;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $value;
    }

    public function ban(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return $this->sendError('Cannot ban yourself', [], 403);
        }

        $user->update([
            'is_active' => false,
            'banned_at' => now(),
            'banned_reason' => $request->reason ?? 'Pelanggaran kebijakan platform.',
        ]);

        return $this->sendResponse($user, 'User has been banned');
    }

    public function unban(User $user)
    {
        $user->update([
            'is_active' => true,
            'banned_at' => null,
            'banned_reason' => null,
        ]);

        return $this->sendResponse($user, 'User has been unbanned');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Cannot delete yourself'], 403);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted permanently',
        ]);
    }
}
