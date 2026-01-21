<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;

$users = [
    ['name' => 'Buyer User', 'email' => 'buyer@example.com', 'role' => 'Buyer'],
    ['name' => 'Seller User', 'email' => 'seller@example.com', 'role' => 'Seller'],
    ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'Admin'],
];

echo "Setting up users...\n";

foreach ($users as $u) {
    $user = User::firstOrNew(['email' => $u['email']]);
    $user->name = $u['name'];
    $user->password = bcrypt('password');
    $user->role = $u['role'];
    $user->verification_status = 'verified';
    $user->email_verified_at = now();
    $user->save();
    echo "User Ready: {$user->email} (Password: password)\n";
}

echo "Done.\n";
