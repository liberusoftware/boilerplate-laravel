<?php
// storage/installer/create_users.php
// Usage: php create_users.php <base64_json_of_users>
// JSON: { "users": [ { "name":"", "email":"", "password":"", "role":"" }, ... ] }

$arg = $argv[1] ?? null;
if (!$arg) {
    echo "No data provided\n";
    exit(1);
}
$data = json_decode(base64_decode($arg), true);
if (!$data || !isset($data['users'])) {
    echo "Invalid payload\n";
    exit(1);
}

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';

$app = require $projectRoot . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Hash;

foreach ($data['users'] as $u) {
    if (!isset($u['name'],$u['email'],$u['password'])) {
        echo "Skipping incomplete user\n";
        continue;
    }
    try {
        // Create user using Eloquent (App\Models\User)
        $userClass = '\App\Models\User';
        if (!class_exists($userClass)) {
            echo "User model not found\n";
            continue;
        }
        if (call_user_func([$userClass, 'where'], 'email', $u['email'])->exists()) {
            echo "User {$u['email']} already exists, skipping\n";
            continue;
        }
        $user = $userClass::create([
            'name' => $u['name'],
            'email' => $u['email'],
            'password' => Hash::make($u['password']),
        ]);
        // Assign role if Spatie exists and user has assignRole
        if (class_exists('\Spatie\Permission\Models\Role') && method_exists($user, 'assignRole') && !empty($u['role'])) {
            if (!\Spatie\Permission\Models\Role::where('name', $u['role'])->exists()) {
                \Spatie\Permission\Models\Role::create(['name' => $u['role']]);
            }
            $user->assignRole($u['role']);
            echo "Created user {$u['email']} with role {$u['role']}\n";
        } else {
            // fallback attempt to set role attribute
            if (array_key_exists('role', $user->getAttributes()) && isset($u['role'])) {
                $user->role = $u['role'];
                $user->save();
                echo "Created user {$u['email']} (role attribute set)\n";
            } else {
                echo "Created user {$u['email']} (no role assigned)\n";
            }
        }
    } catch (Exception $e) {
        echo "Failed to create {$u['email']}: " . $e->getMessage() . "\n";
    }
}
