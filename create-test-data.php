<?php

require 'bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Tenant;
use App\Models\EmailTemplate;
use App\Models\SmsTemplate;
use App\Models\EmailSender;
use App\Models\EmailDomain;

// Get the first tenant (admin@test.com tenant)
$tenant = Tenant::first();

if (!$tenant) {
    echo "❌ No tenant found\n";
    exit;
}

// Create sample email templates
$emailTemplate = EmailTemplate::create([
    'tenant_id' => $tenant->id,
    'name' => 'Welcome Email',
    'subject' => 'Welcome to Our Service',
    'body' => 'Hello {{name}},\n\nWelcome to our amazing service...',
]);

echo "✅ Email template created: {$emailTemplate->name}\n";

// Create sample SMS template
$smsTemplate = SmsTemplate::create([
    'tenant_id' => $tenant->id,
    'name' => 'Verification Code',
    'message' => 'Your verification code is: {{code}}. Valid for 10 minutes.',
]);

echo "✅ SMS template created: {$smsTemplate->name}\n";

// Create sample email sender
$emailSender = EmailSender::create([
    'tenant_id' => $tenant->id,
    'name' => 'Notifications',
    'email' => 'notifications@example.com',
    'verified' => true,
    'verified_at' => now(),
]);

echo "✅ Email sender created: {$emailSender->email}\n";

// Create sample email domain
$emailDomain = EmailDomain::create([
    'tenant_id' => $tenant->id,
    'subdomain' => 'mail',
    'domain' => 'example.com',
    'verified' => true,
    'verified_at' => now(),
    'dns_record' => json_encode([
        'MX' => 'mail.example.com',
        'SPF' => 'v=spf1 include:sendgrid.net ~all',
    ]),
]);

echo "✅ Email domain created: {$emailDomain->subdomain}.{$emailDomain->domain}\n";

echo "\n✅ All test data created successfully!\n";
