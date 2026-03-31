<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * MIGRACIÓN DEPRECADA - Ver migraciones:
     * - 2026_03_24_000001_create_saas_tenants_and_users.php
     * - 2026_03_24_000002_create_saas_credits_and_pricing.php
     * - 2026_03_24_000003_create_saas_integrations_and_logs.php
     * - 2026_03_24_000004_create_saas_invoices_and_audit.php
     * 
     * Esta migración fue dividida en 4 migraciones más pequeñas
     * para evitar timeouts y problemas de ejecución.
     */
    public function up(): void
    {
        // No hacer nada - ver migraciones nuevas más arriba
    }

    public function down(): void
    {
        // No hacer nada - ver migraciones nuevas más arriba
    }
};

