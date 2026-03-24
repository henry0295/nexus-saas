<?php

namespace App\Http\Controllers\Api;

use App\Models\SmsLog;
use App\Services\SmsService;
use Illuminate\Http\Request;

class SmsController
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient' => 'required|string',
            'message' => 'required|string|max:1000',
            'campaign' => 'nullable|string',
        ]);

        try {
            $result = $this->smsService->send(
                $validated['recipient'],
                $validated['message'],
                $request->user()->tenant,
                $validated['campaign'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function bulk(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|array|min:1|max:1000',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:1000',
            'campaign' => 'nullable|string',
        ]);

        try {
            $result = $this->smsService->sendBulk(
                $validated['recipients'],
                $validated['message'],
                $request->user()->tenant,
                $validated['campaign'] ?? null
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function logs(Request $request)
    {
        $logs = SmsLog::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
