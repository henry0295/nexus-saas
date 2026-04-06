<?php

namespace App\Http\Controllers\Api;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController
{
    /**
     * Get all invoices for current tenant (or superadmin can see all)
     */
    public function index(Request $request)
    {
        if ($request->user()->role === 'superadmin') {
            $invoices = Invoice::latest()->paginate(20);
        } else {
            $invoices = Invoice::where('tenant_id', $request->user()->tenant_id)
                ->latest()
                ->paginate(20);
        }

        return response()->json($invoices);
    }

    /**
     * Get a specific invoice
     */
    public function show(Request $request, Invoice $invoice)
    {
        // Verify authorization
        if ($invoice->tenant_id !== $request->user()->tenant_id && $request->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($invoice);
    }

    /**
     * Create a new invoice
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'period_month' => 'required|integer|min:1|max:12',
            'period_year' => 'required|integer|min:2026',
            'due_date' => 'required|date|after:today',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $request->user()->tenant_id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount' => $validated['amount'],
            'period_month' => $validated['period_month'],
            'period_year' => $validated['period_year'],
            'status' => 'draft',
            'due_date' => $validated['due_date'],
        ]);

        return response()->json([
            'message' => 'Invoice created',
            'invoice' => $invoice,
        ], 201);
    }

    /**
     * Mark invoice as paid
     */
    public function markPaid(Request $request, Invoice $invoice)
    {
        // Verify authorization (tenant admin or superadmin)
        if ($invoice->tenant_id !== $request->user()->tenant_id && $request->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Only admins can mark invoices as paid'], 403);
        }

        $validated = $request->validate([
            'payment_method' => 'nullable|string',
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        return response()->json([
            'message' => 'Invoice marked as paid',
            'invoice' => $invoice,
        ]);
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, Invoice $invoice)
    {
        // Verify authorization
        if ($invoice->tenant_id !== $request->user()->tenant_id && $request->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'recipient_email' => 'required|email',
        ]);

        // In real implementation, this would:
        // 1. Generate PDF from invoice
        // 2. Send via AWS SES
        // 3. Update invoice status

        $invoice->update(['status' => 'sent']);

        return response()->json([
            'message' => 'Invoice sent successfully',
            'recipient' => $validated['recipient_email'],
        ]);
    }

    /**
     * Download invoice as PDF
     * Note: Requires barryvdh/laravel-dompdf package
     * composer require barryvdh/laravel-dompdf
     */
    public function downloadPdf(Request $request, Invoice $invoice)
    {
        // Verify authorization
        if ($invoice->tenant_id !== $request->user()->tenant_id && $request->user()->role !== 'superadmin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // In real implementation with dompdf:
        // $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
        // return $pdf->download("invoice-{$invoice->invoice_number}.pdf");

        return response()->json([
            'message' => 'Invoice PDF download',
            'invoice_number' => $invoice->invoice_number,
            'note' => 'PDF generation requires barryvdh/laravel-dompdf package',
        ]);
    }

    /**
     * Generate unique invoice number
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $count = Invoice::whereDate('created_at', now())->count() + 1;
        
        return "{$prefix}-{$date}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
