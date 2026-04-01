<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de Integración AWS por Tenant - SIN foreign key inline
        if (!Schema::hasTable('tenant_integrations')) {
            Schema::create('tenant_integrations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unique('tenant_id');
                
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
            
            // Agregar foreign key DESPUÉS de crear la tabla
            Schema::table('tenant_integrations', function (Blueprint $table) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            });
        }

        // Logs de Email - SIN foreign key inline
        if (!Schema::hasTable('email_logs')) {
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
                $table->index(['tenant_id', 'created_at']);
            });
            
            // Agregar foreign key DESPUÉS
            Schema::table('email_logs', function (Blueprint $table) {
                if (!$this->foreignKeyExists('email_logs', 'email_logs_tenant_id_foreign')) {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                }
            });
        }

        // Logs de SMS - SIN foreign key inline
        if (!Schema::hasTable('sms_logs')) {
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
                $table->index(['tenant_id', 'created_at']);
            });
            
            // Agregar foreign key DESPUÉS
            Schema::table('sms_logs', function (Blueprint $table) {
                if (!$this->foreignKeyExists('sms_logs', 'sms_logs_tenant_id_foreign')) {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                }
            });
        }

        // Logs de Audio - SIN foreign key inline
        if (!Schema::hasTable('audio_logs')) {
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
                $table->index(['tenant_id', 'created_at']);
            });
            
            // Agregar foreign key DESPUÉS
            Schema::table('audio_logs', function (Blueprint $table) {
                if (!$this->foreignKeyExists('audio_logs', 'audio_logs_tenant_id_foreign')) {
                    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audio_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('tenant_integrations');
    }
    
    /**
     * Check if foreign key exists
     */
    protected function foreignKeyExists($table, $foreignKey)
    {
        $database = DB::connection()->getDatabaseName();
        $keyColumnUsageTable = 'INFORMATION_SCHEMA.KEY_COLUMN_USAGE';
        
        $result = DB::selectOne(
            "SELECT CONSTRAINT_NAME FROM $keyColumnUsageTable 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_NAME = ?",
            [$database, $table, $foreignKey]
        );
        
        return $result !== null;
    }
};

