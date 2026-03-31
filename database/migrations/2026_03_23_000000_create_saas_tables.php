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

        // Tabla de Créditos por Tenant
        Schema::create('tenant_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            $table->decimal('balance', 12, 4)->default(100); // Trial: 100 créditos gratis
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

        // Tabla de Integración AWS por Tenant
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('tenants')->onDelete('cascade');
            
            // Email (SES)
            $table->string('ses_verified_domain')->nullable();
            $table->json('ses_dkim_tokens')->nullable();
            $table->integer('ses_sending_limit')->default(50000);
            $table->boolean('ses_verified')->default(false);
            
            // SMS (SNS)
            $table->string('sns_alias')->nullable();
            
            // 360nrs Audio
            $table->string('audio_api_key')->nullable();
            
            $table->timestamps();
        });

        // Logs de Email
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('recipient');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->enum('status', ['sent', 'failed', 'bounce', 'complaint', 'open', 'click'])->default('sent');
            $table->json('response')->nullable();
            $table->string('aws_message_id')->nullable();
            $table->decimal('cost', 10, 6);
            $table->timestamps();
            
            $table->index(['tenant_id', 'created_at']);
        });

        // Logs de SMS
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('recipient', 20);
            $table->string('message');
            $table->string('campaign')->nullable();
            $table->integer('parts')->default(1);
            $table->enum('status', ['sent', 'failed', 'delivered', 'bounced', 'read'])->default('sent');
            $table->json('response')->nullable();
            $table->string('aws_message_id')->nullable();
            $table->decimal('cost', 10, 6);
            $table->timestamps();
            
            $table->index(['tenant_id', 'created_at']);
        });

        // Logs de Audio
        Schema::create('audio_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('phone_number', 20);
            $table->string('message');
            $table->string('gender')->default('F');
            $table->string('language')->default('es_ES');
            $table->string('campaign')->nullable();
            $table->enum('status', ['queued', 'initiated', 'completed', 'failed', 'no_answer'])->default('queued');
            $table->integer('duration')->nullable();
            $table->json('response')->nullable();
            $table->string('provider_call_id')->nullable();
            $table->decimal('cost', 10, 6);
            $table->timestamps();
            
            $table->index(['tenant_id', 'created_at']);
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->integer('period_month');
            $table->integer('period_year');
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->date('due_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->json('line_items')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'period_year', 'period_month']);
        });

        // Audit Logs
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action');
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->onDelete('set null');
            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['admin_id', 'created_at']);
            $table->index(['tenant_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('audio_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('tenant_integrations');
        Schema::dropIfExists('tenant_pricing_overrides');
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('tenant_credits');
        Schema::dropIfExists('tenants');
        
        // Revertir cambios a tabla users
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'role']);
            $table->dropColumn(['tenant_id', 'role', 'status', 'deleted_at']);
        });
    }
};
