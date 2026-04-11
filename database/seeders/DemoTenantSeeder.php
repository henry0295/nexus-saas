<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantCredit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        // Crear tenant demo
        $tenant = Tenant::create([
            'uuid' => Str::uuid(),
            'name' => 'ACME Corporation Demo',
            'email' => 'admin@acme-demo.com',
            'status' => 'active',
            'plan' => 'pro',
        ]);

        echo "\n✅ Tenant created: {$tenant->name} (ID: {$tenant->id})\n";

        // Crear usuario admin del tenant
        $adminUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin ACME',
            'email' => 'admin@acme-demo.com',
            'password' => Hash::make('AdminACME123!'),
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        echo "✅ Admin user created: {$adminUser->email}\n";

        // Crear usuario regular del tenant
        $regularUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Usuario Demo',
            'email' => 'user@acme-demo.com',
            'password' => Hash::make('UserDemo123!'),
            'role' => 'user',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        echo "✅ Regular user created: {$regularUser->email}\n";

        // Crear créditos iniciales
        $credits = TenantCredit::create([
            'tenant_id' => $tenant->id,
            'balance' => 500, // 500 créditos para demo
            'total_purchased' => 500,
            'total_used' => 0,
            'last_recharged_at' => now(),
        ]);

        echo "✅ Credits created: {$credits->balance} credits\n\n";

        // Mostrar credenciales
        echo "════════════════════════════════════════════════════════════\n";
        echo "            DEMO TENANT CREDENTIALS\n";
        echo "════════════════════════════════════════════════════════════\n";
        echo "Tenant: {$tenant->name}\n\n";
        echo "ADMIN:\n";
        echo "  📧 Email: {$adminUser->email}\n";
        echo "  🔐 Password: AdminACME123!\n\n";
        echo "USER:\n";
        echo "  📧 Email: {$regularUser->email}\n";
        echo "  🔐 Password: UserDemo123!\n\n";
        echo "  💳 Credits: {$credits->balance}\n";
        echo "════════════════════════════════════════════════════════════\n\n";
    }
}
