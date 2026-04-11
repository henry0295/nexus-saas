<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Tenants (Clientes SaaS)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'suspended', 'trial'])->default('trial');
            $table->enum('plan', ['starter', 'pro', 'enterprise'])->default('starter');
            $table->timestamps();
            $table->softDeletes();
        });

        // Alterar tabla de Usuarios - agregar campos SaaS
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->onDelete('cascade');
            $table->enum('role', ['superadmin', 'admin', 'user'])->default('user')->after('password');
            $table->enum('status', ['active', 'suspended'])->default('active')->after('role');
            $table->softDeletes()->after('updated_at');
            
            $table->index(['tenant_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropColumn(['tenant_id', 'role', 'status', 'deleted_at']);
        });

        Schema::dropIfExists('tenants');
    }
};
