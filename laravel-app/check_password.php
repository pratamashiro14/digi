<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Check user password format
echo "=== T_USER PASSWORD FORMAT CHECK ===\n";
$users = \DB::table('t_user')->select('id_user', 'nama', 'email', 'password', 'role')->limit(3)->get();

foreach($users as $user) {
    echo "\nUser: {$user->nama}\n";
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
    echo "Password length: " . strlen($user->password) . "\n";
    echo "Password format: " . (strlen($user->password) === 32 ? 'MD5' : (strlen($user->password) === 60 ? 'Bcrypt' : 'Unknown')) . "\n";
    echo "First 20 chars: " . substr($user->password, 0, 20) . "...\n";
}
