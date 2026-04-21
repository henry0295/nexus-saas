<?php
// Temporal file to create test user
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\User;

try {
    // Create tenant
    $tenant = Tenant::create([
        'name' => 'Test Company',
        'email' => 'admin@test.com',
        'status' => 'active',
        'plan' => 'starter',
    ]);

    // Create user
    $user = User::create([
        'tenant_id' => $tenant->id,
        'name' => 'Admin Test',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
        'role' => 'superadmin',
        'status' => 'active',
    ]);

    // Create credits
    $tenant->credits()->create([
        'balance' => 1000,
        'total_purchased' => 1000,
        'total_used' => 0,
    ]);

    echo "✓ Usuario creado exitosamente\n";
    echo "Email: admin@test.com\n";
    echo "Password: password123\n";
    echo "Role: superadmin\n";
    echo "Credits: 1000\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
?>
