<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\Tenant;

class SmsService
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function send(string $phoneNumber, string $message, Tenant $tenant, string $campaign = null): array
    {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $cost = $this->pricingService->getSellingPrice('sms', $tenant);

        if (!$tenant->hasCredit($cost)) {
            throw new \Exception("Insufficient credits");
        }

        try {
            $parts = $this->calculateParts($message);
            $messageId = 'msg_' . uniqid();

            $tenant->deductCredit($cost, "SMS to {$phoneNumber}", $messageId);

            $log = SmsLog::create([
                'tenant_id'      => $tenant->id,
                'recipient'      => $phoneNumber,
                'message'        => $message,
                'campaign'       => $campaign,
                'parts'          => $parts,
                'status'         => 'sent',
                'aws_message_id' => $messageId,
                'cost'           => $cost,
                'response'       => json_encode(['status' => 'sent']),
            ]);

            return ['success' => true, 'log_id' => $log->id, 'parts' => $parts];
        } catch (\Exception $e) {
            SmsLog::create([
                'tenant_id' => $tenant->id,
                'recipient' => $phoneNumber,
                'message'   => $message,
                'status'    => 'failed',
                'cost'      => 0,
                'response'  => json_encode(['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }

    public function sendBulk(array $recipients, string $message, Tenant $tenant, string $campaign = null): array
    {
        $cost = $this->pricingService->getSellingPrice('sms', $tenant);
        $totalCost = $cost * count($recipients);

        if (!$tenant->hasCredit($totalCost)) {
            throw new \Exception("Insufficient credits for bulk send");
        }

        $results = ['sent' => 0, 'failed' => 0, 'total_cost' => $totalCost];

        foreach ($recipients as $recipient) {
            try {
                $this->send($recipient, $message, $tenant, $campaign);
                $results['sent']++;
            } catch (\Exception) {
                $results['failed']++;
            }
        }

        return $results;
    }

    private function formatPhoneNumber(string $phone): string
    {
        $phone = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($phone, '+57') || str_starts_with($phone, '57')) {
            return str_starts_with($phone, '+') ? $phone : '+' . $phone;
        }

        if (strlen($phone) === 10 && is_numeric($phone)) {
            return '+57' . $phone;
        }

        throw new \Exception("Invalid phone format: {$phone}");
    }

    private function calculateParts(string $message): int
    {
        $length = strlen($message);
        $isGsm7 = $this->isGsm7($message);
        $charsPerPart = $isGsm7 ? 160 : 70;
        return (int) ceil($length / $charsPerPart);
    }

    private function isGsm7(string $message): bool
    {
        for ($i = 0; $i < strlen($message); $i++) {
            $ord = ord($message[$i]);
            if ($ord > 127) return false;
        }
        return true;
    }
}
