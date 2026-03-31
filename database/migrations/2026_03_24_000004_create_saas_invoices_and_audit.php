<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Invoices
        if (!Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('invoice_number')->unique();
                $table->decimal('amount', 12, 2);
                $table->integer('period_month');
                $table->integer('period_year');
                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
                $table->date('due_date')->nullable();
                $table->date('paid_at')->nullable();
                $table->json('line_items')->nullable();
                $table->timestamps();
                
                // Foreign key y índices al final
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->index(['tenant_id', 'period_year', 'period_month']);
            });
        }

        // Audit Logs
        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('admin_id')->nullable();
                $table->string('action');
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->json('old_data')->nullable();
                $table->json('new_data')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('admin_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
                
                // Indices
                $table->index(['admin_id', 'created_at']);
                $table->index(['tenant_id', 'action']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('invoices');
    }
};
