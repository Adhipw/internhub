<?php

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('super admin can update role permissions without renaming the role', function () {
    foreach (PermissionEnum::cases() as $permission) {
        Permission::firstOrCreate([
            'name' => $permission->value,
            'guard_name' => 'web',
        ]);
    }

    $superAdminRole = Role::firstOrCreate([
        'name' => UserRole::SUPER_ADMIN->value,
        'guard_name' => 'web',
    ]);

    $adminRole = Role::firstOrCreate([
        'name' => UserRole::ADMIN->value,
        'guard_name' => 'web',
    ]);

    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN->value,
        'email_verified_at' => now(),
    ]);
    $superAdmin->assignRole($superAdminRole);

    Sanctum::actingAs($superAdmin);

    $permissions = [
        PermissionEnum::VIEW_COMPANIES->value,
        PermissionEnum::CREATE_COMPANIES->value,
    ];

    $this->putJson("/api/v1/super-admin/roles-data/{$adminRole->id}", [
        'permissions' => $permissions,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', UserRole::ADMIN->value);

    expect($adminRole->fresh()->permissions()->pluck('name')->sort()->values()->all())
        ->toBe(collect($permissions)->sort()->values()->all());
});
