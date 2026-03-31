<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Créditos por Tenant
        Schema::create('tenant_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->decimal('balance', 12, 4)->default(100);
            $table->decimal('total_purchased', 12, 4)->default(0);
            $table->decimal('total_used', 12, 4)->default(0);
            $table->timestamp('last_recharged_at')->nullable();
            $table->timestamps();
        });

        // Tabla de Transacciones de Créditos
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('type', ['purchase', 'refund', 'usage', 'adjustment'])->default('purchase');
            $table->decimal('amount', 12, 4);
            $table->string('description')->nullable();
            $table->string('reference_id')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'created_at']);
        });

        // Tabla de Reglas de Precios (Global)
        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->enum('channel', ['sms', 'email', 'audio']);
            $table->enum('provider', ['aws', 'twilio', '360nrs'])->default('aws');
            $table->decimal('cost_per_unit', 10, 6);
            $table->integer('margin_percent')->default(30);
            $table->decimal('selling_price', 10, 6);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by_admin')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->unique(['channel', 'provider', 'is_active']);
        });

        // Tabla de Overrides de Precio por Tenant (VIP clients)
        Schema::create('tenant_pricing_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->enum('channel', ['sms', 'email', 'audio']);
            $table->decimal('custom_price', 10, 6);
            $table->string('reason')->nullable();
            $table->date('effective_from')->useCurrent();
            $table->date('effective_to')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_pricing_overrides');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('tenant_credits');
    }
};
