<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$companies = Company::get();
foreach ($companies as $c) {
    $hrEmail = 'hr.' . Str::slug($c->name) . '@internhub.id';
    
    $hr = User::withoutEvents(function() use ($hrEmail, $c) {
        return User::firstOrCreate(
            ['email' => $hrEmail],
            [
                'name' => 'HR ' . $c->name,
                'password' => Hash::make('password123'),
                'role' => 'hr',
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
    });

    if (method_exists($hr, 'syncRoles')) {
        $hr->syncRoles(['hr']);
    }

    App\Models\CompanyMember::withoutEvents(function() use ($hr, $c) {
        $hr->companyMemberships()->firstOrCreate(
            ['company_id' => $c->id],
            [
                'role' => 'owner',
                'is_active' => true,
            ]
        );
    });

    echo "Created HR for " . $c->name . " -> " . $hrEmail . "\n";
}

