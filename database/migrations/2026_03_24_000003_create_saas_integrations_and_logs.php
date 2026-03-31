<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
            $table->unsignedBigInteger('tenant_id');
            $table->string('recipient');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->enum('status', ['sent', 'failed', 'bounce', 'complaint', 'open', 'click'])->default('sent');
            $table->json('response')->nullable();
            $table->string('aws_message_id')->nullable();
            $table->decimal('cost', 12, 4)->default(0);
            $table->timestamps();
            
            // Foreign key y índices al final
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
        });

        // Logs de SMS
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('recipient', 20);
            $table->string('message');
            $table->string('campaign')->nullable();
            $table->integer('parts')->default(1);
            $table->enum('status', ['sent', 'failed', 'delivered', 'bounced', 'read'])->default('sent');
            $table->json('response')->nullable();
            $table->string('aws_message_id')->nullable();
            $table->decimal('cost', 12, 4)->default(0);
            $table->timestamps();
            
            // Foreign key y índices al final
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
        });

        // Logs de Audio
        Schema::create('audio_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('phone_number', 20);
            $table->string('message');
            $table->string('gender')->default('F');
            $table->string('language')->default('es_ES');
            $table->string('campaign')->nullable();
            $table->enum('status', ['queued', 'initiated', 'completed', 'failed', 'no_answer'])->default('queued');
            $table->integer('duration')->nullable();
            $table->json('response')->nullable();
            $table->string('provider_call_id')->nullable();
            $table->decimal('cost', 12, 4)->default(0);
            $table->timestamps();
            
            // Foreign key y índices al final
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('tenant_integrations');
    }
};
