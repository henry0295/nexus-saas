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
     * Create a new invoice (from credit purchases)
     * Note: In real implementation, this would be triggered by successful payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'credit_transaction_id' => 'required|exists:credit_transactions,id',
            'amount_due' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $invoice = Invoice::create([
            'tenant_id' => $request->user()->tenant_id,
            'credit_transaction_id' => $validated['credit_transaction_id'],
            'invoice_number' => $this->generateInvoiceNumber(),
            'amount_due' => $validated['amount_due'],
            'amount_paid' => 0,
            'status' => 'draft',
            'description' => $validated['description'] ?? null,
            'issue_date' => now(),
            'due_date' => now()->addDays(30),
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
            'amount_paid' => 'required|numeric|min:0',
            'payment_method' => 'nullable|string',
        ]);

        $invoice->update([
            'amount_paid' => $validated['amount_paid'],
            'status' => 'paid',
            'paid_date' => now(),
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
        // 3. Update invoice sent_at timestamp

        $invoice->update(['sent_at' => now()]);

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
