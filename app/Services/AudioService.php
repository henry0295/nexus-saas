<?php

namespace App\Services;

use App\Models\AudioLog;
use App\Models\Tenant;

class AudioService
{
    private PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function call(
        string $phoneNumber,
        string $message,
        string $gender = 'female',
        string $language = 'es',
        Tenant $tenant = null,
        string $campaign = null
    ): array {
        $phoneNumber = $this->formatPhoneNumber($phoneNumber);
        $cost = $this->pricingService->getSellingPrice('audio', $tenant);

        if (!$tenant->hasCredit($cost)) {
            throw new \Exception("Insufficient credits for audio call");
        }

        try {
            $callId = 'call_' . uniqid();
            $duration = $this->calculateDuration($message);

            $tenant->deductCredit($cost, "AUDIO to {$phoneNumber}", $callId);

            $log = AudioLog::create([
                'tenant_id'         => $tenant->id,
                'phone_number'      => $phoneNumber,
                'message'           => $message,
                'gender'            => $gender,
                'language'          => $language,
                'campaign'          => $campaign,
                'status'            => 'initiated',
                'duration'          => $duration,
                'provider_call_id'  => $callId,
                'cost'              => $cost,
                'response'          => json_encode(['status' => 'initiated', 'call_id' => $callId]),
            ]);

            return [
                'success' => true,
                'log_id' => $log->id,
                'call_id' => $callId,
                'duration' => $duration,
                'cost' => $cost,
            ];
        } catch (\Exception $e) {
            AudioLog::create([
                'tenant_id'    => $tenant->id,
                'phone_number' => $phoneNumber,
                'message'      => $message,
                'gender'       => $gender,
                'language'     => $language,
                'status'       => 'failed',
                'cost'         => 0,
                'response'     => json_encode(['error' => $e->getMessage()]),
            ]);

            throw $e;
        }
    }

    public function callBulk(
        array $phoneNumbers,
        string $message,
        string $gender = 'female',
        string $language = 'es',
        Tenant $tenant = null,
        string $campaign = null
    ): array {
        $cost = $this->pricingService->getSellingPrice('audio', $tenant);
        $totalCost = $cost * count($phoneNumbers);

        if (!$tenant->hasCredit($totalCost)) {
            throw new \Exception("Insufficient credits for bulk audio calls");
        }

        $results = ['sent' => 0, 'failed' => 0, 'total_cost' => $totalCost];

        foreach ($phoneNumbers as $phone) {
            try {
                $this->call($phone, $message, $gender, $language, $tenant, $campaign);
                $results['sent']++;
            } catch (\Exception) {
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Estimate duration for message (rule: ~0.5s per word, minimum 5 seconds)
     */
    private function calculateDuration(string $message): int
    {
        $wordCount = str_word_count($message);
        $estimatedSeconds = max(5, ceil($wordCount * 0.5));
        
        return $estimatedSeconds;
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
}
