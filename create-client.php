<?php
require "vendor/autoload.php";
$app = require "bootstrap/app.php";

// Bootstrap Laravel
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tenant;
use App\Models\User;

// Crear nuevo tenant
$tenant = Tenant::create([
    'name' => 'Acme Corporation',
    'email' => 'contact@acmecorp.com',
    'status' => 'active',
    'plan' => 'pro',
]);

// Crear usuario admin para el tenant
$user = User::create([
    'tenant_id' => $tenant->id,
    'name' => 'Carlos Mendez',
    'email' => 'carlos@acmecorp.com',
    'password' => bcrypt('AcmeCorp@2026'),
    'role' => 'admin',
    'status' => 'active',
]);

// Asignar créditos iniciales (plan de prueba)
$tenant->credits()->create([
    'balance' => 5000,
    'total_purchased' => 5000,
    'total_used' => 0,
]);

echo "\n✅ NUEVO CLIENTE CREADO EXITOSAMENTE\n";
echo "════════════════════════════════════════════════\n\n";
echo "📊 INFORMACIÓN DEL TENANT:\n";
echo "  • ID: {$tenant->id}\n";
echo "  • Nombre: {$tenant->name}\n";
echo "  • Email: {$tenant->email}\n";
echo "  • UUID: {$tenant->uuid}\n";
echo "  • Plan: {$tenant->plan}\n";
echo "  • Estado: {$tenant->status}\n";
echo "\n👤 INFORMACIÓN DEL USUARIO:\n";
echo "  • ID: {$user->id}\n";
echo "  • Nombre: {$user->name}\n";
echo "  • Email: {$user->email}\n";
echo "  • Contraseña: AcmeCorp@2026\n";
echo "  • Rol: {$user->role}\n";
echo "  • Estado: {$user->status}\n";
echo "\n💰 CRÉDITOS:\n";
echo "  • Balance: 5,000\n";
echo "  • Total Comprado: 5,000\n";
echo "  • Total Usado: 0\n";
echo "\n════════════════════════════════════════════════\n";
