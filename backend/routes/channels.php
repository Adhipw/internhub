<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('company.{companyId}', function ($user, $companyId) {
    // HR only receives company notification sesuai scope.
    return $user->hasRole(['admin', 'super_admin']) ||
           $user->companyMemberships()->where('company_id', $companyId)->where('is_active', true)->exists();
});

Broadcast::channel('mentor.{mentorId}', function ($user, $mentorId) {
    // Mentor hanya menerima notification assignment-nya.
    return (int) $user->id === (int) $mentorId && $user->hasRole(['mentor', 'admin', 'super_admin']);
});

Broadcast::channel('admins.online', function ($user) {
    // Only admins and super admins can join this presence channel
    if ($user->hasRole(['admin', 'super_admin'])) {
        return ['id' => $user->id, 'name' => $user->name, 'role' => $user->role];
    }

    return false;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
