<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('subdomain');
            $table->string('domain');
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('dns_record');
            $table->timestamps();

            $table->unique(['tenant_id', 'subdomain', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_domains');
    }
};
