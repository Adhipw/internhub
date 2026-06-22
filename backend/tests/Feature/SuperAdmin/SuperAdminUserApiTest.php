<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function actingAsSuperAdminForUserApi(): User
{
    $role = Role::firstOrCreate([
        'name' => 'super_admin',
        'guard_name' => 'web',
    ]);

    $user = User::factory()->create([
        'role' => 'super_admin',
        'email_verified_at' => now(),
    ]);

    $user->assignRole($role);
    Sanctum::actingAs($user);

    return $user;
}

test('super admin can update user profile fields from the edit modal payload', function () {
    actingAsSuperAdminForUserApi();

    $targetRole = Role::firstOrCreate([
        'name' => 'user',
        'guard_name' => 'web',
    ]);

    $target = User::factory()->create([
        'name' => 'Old Student',
        'email' => 'old-student@example.test',
        'phone_number' => '081111111111',
        'role' => 'hr',
        'is_active' => true,
    ]);
    $target->assignRole($targetRole);

    $response = $this->post('/api/v1/super-admin/users/'.$target->id, [
        '_method' => 'PUT',
        'name' => 'Updated Student',
        'email' => 'updated-student@example.test',
        'phone_number' => '082211139537',
        'role' => 'user',
        'is_active' => '0',
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ], [
        'Accept' => 'application/json',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'updated-student@example.test')
        ->assertJsonPath('data.phone_number', '082211139537')
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.role', 'user');

    $target->refresh();

    expect($target->name)->toBe('Updated Student')
        ->and($target->phone_number)->toBe('082211139537')
        ->and($target->is_active)->toBeFalse()
        ->and(Hash::check('new-password-123', $target->password))->toBeTrue();
});

test('super admin cannot deactivate own account through user management api', function () {
    $superAdmin = actingAsSuperAdminForUserApi();

    $this->post('/api/v1/super-admin/users/'.$superAdmin->id, [
        '_method' => 'PUT',
        'is_active' => '0',
    ], [
        'Accept' => 'application/json',
    ])
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect($superAdmin->fresh()->is_active)->toBeTrue();
});
