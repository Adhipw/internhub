<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->role($role);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $roles = ['admin', 'hr', 'mentor', 'user']; // Roles available for assignment

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => $users,
            'roles' => $roles,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:admin,hr,mentor',
            'phone_number' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // Automatically hashed via model cast if configured, or use Hash::make
            'role' => $request->role,
            'phone_number' => $request->phone_number,
            'is_active' => $request->is_active,
            'email_verified_at' => now(), // Auto-verify for admin-created accounts
        ]);

        $user->syncRoles([$request->role]);

        AuditService::log('user_created_by_admin', $user, "Account created with role: {$request->role}");

        SecurityEvent::create([
            'event_type' => 'user_created_by_admin',
            'severity' => 'medium',
            'user_id' => Auth::id(),
            'description' => "Created new account for {$user->email} with role {$request->role}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode(['target_user_id' => $user->id, 'role' => $request->role]),
        ]);

        return redirect()->back()->with('success', 'Akun berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        // Safety: Cannot edit other Super Admins unless specifically allowed (usually only self or specific rules)
        if ($user->hasRole('super_admin') && $user->id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit Super Admin lain.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:super_admin,admin,hr,mentor,user',
            'phone_number' => 'nullable|string|max:20',
            'is_active' => 'required|boolean',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->is_active = $request->is_active;
        $user->role = $request->role;

        if ($request->filled('password')) {
            $user->password = $request->password;
        }

        $user->save();

        if ($request->role !== $user->role) {
            $oldRole = $user->role;
            $user->syncRoles([$request->role]);

            AuditService::log('user_role_updated', $user, "Role updated from {$oldRole} to {$request->role}");
        }

        return redirect()->back()->with('success', 'Data akun berhasil diperbarui.');
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:super_admin,admin,hr,user,mentor',
        ]);

        $oldRole = $user->role;
        $user->role = $request->role;
        $user->save();

        // Sync Spatie role
        $user->syncRoles([$request->role]);

        // Create Security Event for role change
        SecurityEvent::create([
            'event_type' => 'role_changed',
            'severity' => 'medium',
            'user_id' => Auth::id(),
            'description' => "Role changed for user {$user->email} from {$oldRole} to {$request->role}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode([
                'target_user_id' => $user->id,
                'old_role' => $oldRole,
                'new_role' => $request->role,
            ]),
        ]);

        AuditService::log('super_admin_role_change', $user, "Role changed from {$oldRole} to {$request->role}");

        return redirect()->back()->with('success', 'Role user berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        // Safety: Cannot delete self
        if ($user->id === Auth::id()) {
            abort(403, 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        AuditService::log('super_admin_user_deleted', null, "User deleted globally: {$userName}");

        return redirect()->back()->with('success', 'User berhasil dihapus secara permanen.');
    }

    public function ban(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            abort(403, 'Anda tidak dapat memblokir akun Anda sendiri.');
        }

        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $user->update([
            'banned_at' => now(),
            'banned_reason' => $request->reason,
        ]);

        SecurityEvent::create([
            'event_type' => 'user_banned',
            'severity' => 'high',
            'user_id' => Auth::id(),
            'description' => "Banned user {$user->email} for: {$request->reason}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode(['target_user_id' => $user->id, 'reason' => $request->reason]),
        ]);

        AuditService::log('user_banned', $user, "User banned. Reason: {$request->reason}");

        return redirect()->back()->with('success', 'Akun berhasil diblokir.');
    }

    public function unban(Request $request, User $user)
    {
        $user->update([
            'banned_at' => null,
            'banned_reason' => null,
        ]);

        SecurityEvent::create([
            'event_type' => 'user_unbanned',
            'severity' => 'medium',
            'user_id' => Auth::id(),
            'description' => "Unbanned user {$user->email}",
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => json_encode(['target_user_id' => $user->id]),
        ]);

        AuditService::log('user_unbanned', $user, 'User unbanned.');

        return redirect()->back()->with('success', 'Blokir akun berhasil dibuka.');
    }
}
