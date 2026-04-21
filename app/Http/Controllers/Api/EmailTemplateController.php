<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailTemplateController extends Controller
{
    /**
     * Get all email templates for authenticated tenant
     */
    public function index(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        $templates = EmailTemplate::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $templates,
        ]);
    }

    /**
     * Create new email template
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $tenant = auth()->user()->tenant;

        // Check if template name already exists for this tenant
        $exists = EmailTemplate::where('tenant_id', $tenant->id)
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Ya existe una plantilla con este nombre',
            ], 422);
        }

        $template = EmailTemplate::create([
            'tenant_id' => $tenant->id,
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Plantilla creada exitosamente',
            'data' => $template,
        ], 201);
    }

    /**
     * Update email template
     */
    public function update(Request $request, EmailTemplate $template): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($template->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'subject' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
        ]);

        $template->update($validated);

        return response()->json([
            'message' => 'Plantilla actualizada exitosamente',
            'data' => $template,
        ]);
    }

    /**
     * Delete email template
     */
    public function destroy(EmailTemplate $template): JsonResponse
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
