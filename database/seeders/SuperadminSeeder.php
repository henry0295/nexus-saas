<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'tenant_id' => null,
            'name' => 'Superadmin',
            'email' => 'superadmin@nexus-saas.com',
            'password' => Hash::make('SuperAdmin123!'),
            'role' => 'superadmin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        echo "\n✅ Superadmin created!\n";
        echo "📧 Email: superadmin@nexus-saas.com\n";
        echo "🔐 Password: SuperAdmin123!\n";
        echo "⚠️  CHANGE IMMEDIATELY IN PRODUCTION!\n\n";
    }
}
