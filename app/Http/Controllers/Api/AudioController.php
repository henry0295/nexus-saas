<?php

namespace App\Http\Controllers\Api;

use App\Models\AudioLog;
use App\Services\AudioService;
use Illuminate\Http\Request;

class AudioController
{
    protected AudioService $audioService;

    public function __construct(AudioService $audioService)
    {
        $this->audioService = $audioService;
    }

    public function call(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
            'message'      => 'required|string|max:5000',
            'gender'       => 'nullable|in:male,female',
            'language'     => 'nullable|in:es,en,pt',
            'campaign'     => 'nullable|string',
        ]);

        try {
            $result = $this->audioService->call(
                $validated['phone_number'],
                $validated['message'],
                $validated['gender'] ?? 'female',
                $validated['language'] ?? 'es',
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
            'phone_numbers' => 'required|array|min:1|max:1000',
            'phone_numbers.*' => 'required|string',
            'message'       => 'required|string|max:5000',
            'gender'        => 'nullable|in:male,female',
            'language'      => 'nullable|in:es,en,pt',
            'campaign'      => 'nullable|string',
        ]);

        try {
            $result = $this->audioService->callBulk(
                $validated['phone_numbers'],
                $validated['message'],
                $validated['gender'] ?? 'female',
                $validated['language'] ?? 'es',
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
        $logs = AudioLog::where('tenant_id', $request->user()->tenant_id)
            ->latest()
            ->paginate(20);

        return response()->json($logs);
    }

    public function show(Request $request, AudioLog $audioLog)
    {
        // Verificar que el audio log pertenezca al tenant del user
        if ($audioLog->tenant_id !== $request->user()->tenant_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($audioLog);
    }
}
