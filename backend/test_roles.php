<?php

use App\Http\Controllers\Api\ApiRoleController;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$role = Role::first();
$permissions = Permission::limit(2)->pluck('name')->toArray();
$request = Request::create('/api/v1/super-admin/roles-data/'.$role->id, 'PUT', [
    'name' => $role->name,
    'permissions' => $permissions,
]);
$controller = new ApiRoleController;
$response = $controller->update($request, $role);
echo $response->getContent();
