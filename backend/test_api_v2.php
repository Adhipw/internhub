<?php

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$user = User::where('email', 'superadmin1@internhub.id')->first();
$app['auth']->guard('web')->login($user);

$request = Request::create('/api/v1/super-admin/roles-data/6', 'PUT', [
    'name' => 'super_admin',
    'permissions' => ['view_companies', 'create_companies', 'delete_companies', 'manage_users'],
]);
$request->headers->set('Accept', 'application/json');

$response = app()->handle($request);
echo 'Status: '.$response->getStatusCode()."\n";
echo 'Body: '.$response->getContent()."\n";
