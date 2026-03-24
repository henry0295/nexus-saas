<?php

namespace App\Http\Controllers\Api;

use App\Models\EmailLog;
use App\Services\EmailService;
use Illuminate\Http\Request;

class EmailController
{
    protected EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipient' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            $result = $this->emailService->send(
                $validated['recipient'],
                $validated['subject'],
                $validated['body'],
                $request->user()->tenant
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
            'recipients.*' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        try {
            $result = $this->emailService->sendBulk(
                $validated['recipients'],
                $validated['subject'],
                $validated['body'],
                $request->user()->tenant
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function logs(Request $request)
    {
        $logs = EmailLog::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }
}
