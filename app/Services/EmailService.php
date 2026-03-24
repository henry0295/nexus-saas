<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\Tenant;

class EmailService
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function send(string $recipient, string $subject, string $body, Tenant $tenant): array
    {
        $cost = $this->pricingService->getSellingPrice('email', $tenant);
        
        if (!$tenant->hasCredit($cost)) {
            throw new \Exception("Insufficient credits. Required: {$cost}, Available: {$tenant->credits->balance}");
        }

        try {
            // Aquí iría la integración real con AWS SES
            // Por ahora, simular envío
            $messageId = 'msg_' . uniqid();

            $tenant->deductCredit($cost, "Email to {$recipient}", $messageId);

            $log = EmailLog::create([
                'tenant_id'      => $tenant->id,
                'recipient'      => $recipient,
                'subject'        => $subject,
                'body'           => $body,
                'status'         => 'sent',
                'aws_message_id' => $messageId,
                'cost'           => $cost,
                'response'       => json_encode(['status' => 'sent']),
            ]);

            return [
                'success'    => true,
                'log_id'     => $log->id,
                'message_id' => $messageId,
            ];
        } catch (\Exception $e) {
            EmailLog::create([
                'tenant_id' => $tenant->id,
                'recipient' => $recipient,
                'subject'   => $subject,
                'status'    => 'failed',
                'cost'      => 0,
                'response'  => json_encode(['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }

    public function sendBulk(array $recipients, string $subject, string $body, Tenant $tenant): array
    {
        $cost = $this->pricingService->getSellingPrice('email', $tenant);
        $totalCost = $cost * count($recipients);

        if (!$tenant->hasCredit($totalCost)) {
            throw new \Exception("Insufficient credits for bulk send");
        }

        $results = ['sent' => 0, 'failed' => 0, 'logs' => []];

        foreach ($recipients as $recipient) {
            try {
                $result = $this->send($recipient, $subject, $body, $tenant);
                $results['sent']++;
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }
}
