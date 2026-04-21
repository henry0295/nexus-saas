<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSender;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;

class EmailSenderController extends Controller
{
    /**
     * Get all email senders for authenticated tenant
     */
    public function index(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        $senders = EmailSender::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $senders,
        ]);
    }

    /**
     * Create new email sender
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $tenant = auth()->user()->tenant;

        // Check if email already exists for this tenant
        $exists = EmailSender::where('tenant_id', $tenant->id)
            ->where('email', $validated['email'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Este email ya está registrado',
            ], 422);
        }

        $verificationToken = Str::random(32);

        $sender = EmailSender::create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'verification_token' => $verificationToken,
        ]);

        // Send verification email
        try {
            Mail::send('emails.verify-sender', [
                'sender_name' => $validated['name'],
                'sender_email' => $validated['email'],
                'verification_url' => url("/api/auth/verify-sender/{$verificationToken}"),
            ], function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('Verifica tu dirección de correo en NexusSaaS');
            });
        } catch (\Exception $e) {
            // Log error but continue - email service might not be configured
            \Log::warning('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Remitente agregado. Se enviará un correo de verificación a ' . $validated['email'],
            'data' => $sender,
        ], 201);
    }

    /**
     * Verify email sender via token
     */
    public function verifySender(string $token): JsonResponse
    {
        $sender = EmailSender::where('verification_token', $token)->first();

        if (!$sender) {
            return response()->json([
                'error' => 'Token de verificación inválido',
            ], 404);
        }

        $sender->update([
            'verified' => true,
            'verified_at' => now(),
            'verification_token' => null,
        ]);

        return response()->json([
            'message' => 'Remitente verificado exitosamente',
            'data' => $sender,
        ]);
    }

    /**
     * Resend verification email
     */
    public function resendVerification(EmailSender $sender): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($sender->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        if ($sender->verified) {
            return response()->json([
                'error' => 'Este remitente ya está verificado',
            ], 422);
        }

        // Generate new token
        $verificationToken = Str::random(32);
        $sender->update(['verification_token' => $verificationToken]);

        // Send verification email
        try {
            Mail::send('emails.verify-sender', [
                'sender_name' => $sender->name,
                'sender_email' => $sender->email,
                'verification_url' => url("/api/auth/verify-sender/{$verificationToken}"),
            ], function ($message) use ($sender) {
                $message->to($sender->email)
                    ->subject('Verifica tu dirección de correo en NexusSaaS');
            });
        } catch (\Exception $e) {
            \Log::warning('Failed to send verification email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Correo de verificación enviado nuevamente',
        ]);
    }

    /**
     * Delete email sender
     */
    public function destroy(EmailSender $sender): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($sender->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        $sender->delete();

        return response()->json([
            'message' => 'Remitente eliminado exitosamente',
        ]);
    }
}
