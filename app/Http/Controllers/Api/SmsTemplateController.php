<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SmsTemplateController extends Controller
{
    /**
     * Get all SMS templates for authenticated tenant
     */
    public function index(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        $templates = SmsTemplate::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $templates,
        ]);
    }

    /**
     * Create new SMS template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:1000',
        ]);

        $tenant = auth()->user()->tenant;

        // Check if template name already exists for this tenant
        $exists = SmsTemplate::where('tenant_id', $tenant->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Ya existe una plantilla con este nombre',
            ], 422);
        }

        $template = SmsTemplate::create([
            'tenant_id' => $tenant->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Plantilla creada exitosamente',
            'data' => $template,
        ], 201);
    }

    /**
     * Update SMS template
     */
    public function update(Request $request, SmsTemplate $template): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($template->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'message' => 'sometimes|string|max:1000',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Plantilla actualizada exitosamente',
            'data' => $template,
        ]);
    }

    /**
     * Delete SMS template
     */
    public function destroy(SmsTemplate $template): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($template->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        $template->delete();

        return response()->json([
            'message' => 'Plantilla eliminada exitosamente',
        ]);
    }
}
