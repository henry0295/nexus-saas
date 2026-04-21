<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailDomain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EmailDomainController extends Controller
{
    /**
     * Get all email domains for authenticated tenant
     */
    public function index(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        $domains = EmailDomain::where('tenant_id', $tenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $domains,
        ]);
    }

    /**
     * Create new email domain
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subdomain' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
        ]);

        $tenant = auth()->user()->tenant;

        // Check if domain already exists for this tenant
        $exists = EmailDomain::where('tenant_id', $tenant->id)
            ->where('subdomain', $validated['subdomain'])
            ->where('domain', $validated['domain'])
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => 'Este dominio ya está registrado',
            ], 422);
        }

        // Generate DNS records
        $dnsRecord = $this->generateDnsRecords($validated['subdomain'], $validated['domain']);

        $emailDomain = EmailDomain::create([
            'tenant_id' => $tenant->id,
            'subdomain' => $validated['subdomain'],
            'domain' => $validated['domain'],
            'dns_record' => json_encode($dnsRecord),
        ]);

        return response()->json([
            'message' => 'Dominio agregado. Por favor verifica los registros DNS',
            'data' => $emailDomain->makeHidden('dns_record')->append('dns_records'),
        ], 201);
    }

    /**
     * Verify domain via DNS check
     */
    public function verifyDomain(EmailDomain $domain): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($domain->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        // In a real implementation, you would check DNS records here
        // For now, we'll simulate verification after a delay
        $dnsRecords = json_decode($domain->dns_record, true);
        $isVerified = $this->checkDnsRecords($domain->subdomain, $domain->domain, $dnsRecords);

        if ($isVerified) {
            $domain->update([
                'verified' => true,
                'verified_at' => now(),
            ]);

            return response()->json([
                'message' => 'Dominio verificado exitosamente',
                'data' => $domain,
            ]);
        } else {
            return response()->json([
                'error' => 'No se pudo verificar el dominio. Asegúrate de que los registros DNS están correctos.',
                'records' => $dnsRecords,
            ], 422);
        }
    }

    /**
     * Delete email domain
     */
    public function destroy(EmailDomain $domain): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        if ($domain->tenant_id !== $tenant->id) {
            return response()->json([
                'error' => 'No autorizado',
            ], 403);
        }

        $domain->delete();

        return response()->json([
            'message' => 'Dominio eliminado exitosamente',
        ]);
    }

    /**
     * Generate DNS records for domain verification
     */
    private function generateDnsRecords(string $subdomain, string $domain): array
    {
        $fullDomain = "{$subdomain}.{$domain}";

        return [
            'MX' => [
                'priority' => 10,
                'value' => "mail.{$domain}",
                'instruction' => "Tipo: MX, Prioridad: 10, Valor: mail.{$domain}",
            ],
            'SPF' => [
                'type' => 'TXT',
                'value' => 'v=spf1 include:sendgrid.net ~all',
                'instruction' => "Tipo: TXT, Nombre: @, Valor: v=spf1 include:sendgrid.net ~all",
            ],
            'DKIM' => [
                'type' => 'CNAME',
                'value' => 'sendgrid.net',
                'instruction' => "Tipo: CNAME, Nombre: {$subdomain}._domainkey, Valor: sendgrid.net",
            ],
            'DMARC' => [
                'type' => 'TXT',
                'value' => 'v=DMARC1; p=none; rua=mailto:admin@' . $domain,
                'instruction' => "Tipo: TXT, Nombre: _dmarc, Valor: v=DMARC1; p=none; rua=mailto:admin@{$domain}",
            ],
        ];
    }

    /**
     * Check if DNS records are properly configured
     */
    private function checkDnsRecords(string $subdomain, string $domain, array $expectedRecords): bool
    {
        $fullDomain = "{$subdomain}.{$domain}";

        try {
            // Check MX record
            $mxRecords = dns_get_mx($domain);
            if (empty($mxRecords)) {
                return false;
            }

            // Check TXT records for SPF
            $txtRecords = dns_get_record($domain, DNS_TXT);
            $hasSPF = false;
            
            foreach ($txtRecords as $record) {
                if (isset($record['txt']) && strpos($record['txt'], 'v=spf1') === 0) {
                    $hasSPF = true;
                    break;
                }
            }

            return $hasSPF;
        } catch (\Exception $e) {
            // If DNS check fails, return false
            return false;
        }
    }
}
