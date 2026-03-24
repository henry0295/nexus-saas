<?php

namespace App\Http\Controllers\Api;

use App\Models\AuditLog;
use App\Models\PricingRule;
use App\Models\Tenant;
use App\Models\TenantPricingOverride;
use Illuminate\Http\Request;

class AdminController
{
    /**
     * Verify request user is superadmin
     */
    private function verifySuperAdmin(Request $request): bool
    {
        return $request->user()->role === 'superadmin';
    }

    /**
     * List all tenants (with pagination)
     */
    public function tenants(Request $request)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tenants = Tenant::withCount('users')
            ->with('credits')
            ->latest()
            ->paginate(20);

        return response()->json($tenants);
    }

    /**
     * Get detailed information about a specific tenant
     */
    public function tenantDetail(Request $request, Tenant $tenant)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tenant->load(['users', 'credits', 'integrations', 'pricingOverrides']);

        return response()->json([
            'tenant' => $tenant,
            'users_count' => $tenant->users()->count(),
            'total_sms' => $tenant->smsLogs()->count(),
            'total_emails' => $tenant->emailLogs()->count(),
            'total_audio' => $tenant->audioLogs()->count(),
            'total_credits_spent' => $tenant->creditTransactions()
                ->where('type', 'deduction')
                ->sum('amount'),
        ]);
    }

    /**
     * Suspend a tenant (disable service)
     */
    public function suspendTenant(Request $request, Tenant $tenant)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tenant->update(['status' => 'suspended']);

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'tenant_id' => $tenant->id,
            'action' => 'suspend_tenant',
            'description' => "Suspended tenant: {$tenant->name}",
            'metadata' => json_encode(['tenant_name' => $tenant->name, 'tenant_id' => $tenant->id]),
        ]);

        return response()->json([
            'message' => 'Tenant suspended successfully',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Activate a suspended tenant
     */
    public function activateTenant(Request $request, Tenant $tenant)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tenant->update(['status' => 'active']);

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'tenant_id' => $tenant->id,
            'action' => 'activate_tenant',
            'description' => "Activated tenant: {$tenant->name}",
            'metadata' => json_encode(['tenant_name' => $tenant->name, 'tenant_id' => $tenant->id]),
        ]);

        return response()->json([
            'message' => 'Tenant activated successfully',
            'tenant' => $tenant,
        ]);
    }

    /**
     * Create a new pricing rule
     */
    public function createPricingRule(Request $request)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'channel' => 'required|in:sms,email,audio',
            'aws_cost' => 'required|numeric|min:0',
            'margin_percentage' => 'required|numeric|min:0|max:100',
            'selling_price' => 'required|numeric|min:0',
        ]);

        $rule = PricingRule::create([
            'channel' => $validated['channel'],
            'aws_cost' => $validated['aws_cost'],
            'margin_percentage' => $validated['margin_percentage'],
            'selling_price' => $validated['selling_price'],
            'created_by' => $request->user()->id,
        ]);

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'create_pricing_rule',
            'description' => "Created pricing rule for {$validated['channel']}",
            'metadata' => json_encode($validated),
        ]);

        return response()->json([
            'message' => 'Pricing rule created',
            'rule' => $rule,
        ], 201);
    }

    /**
     * Update a pricing rule
     */
    public function updatePricingRule(Request $request, PricingRule $pricingRule)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'aws_cost' => 'nullable|numeric|min:0',
            'margin_percentage' => 'nullable|numeric|min:0|max:100',
            'selling_price' => 'nullable|numeric|min:0',
        ]);

        $pricingRule->update($validated);

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'action' => 'update_pricing_rule',
            'description' => "Updated pricing rule for {$pricingRule->channel}",
            'metadata' => json_encode($validated),
        ]);

        return response()->json([
            'message' => 'Pricing rule updated',
            'rule' => $pricingRule,
        ]);
    }

    /**
     * List all pricing rules
     */
    public function listPricingRules(Request $request)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $rules = PricingRule::all();
        return response()->json(['pricing_rules' => $rules]);
    }

    /**
     * Set VIP pricing override for a tenant
     */
    public function setPricingOverride(Request $request, Tenant $tenant)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'channel' => 'required|in:sms,email,audio',
            'override_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string',
        ]);

        $override = TenantPricingOverride::updateOrCreate(
            ['tenant_id' => $tenant->id, 'channel' => $validated['channel']],
            [
                'override_price' => $validated['override_price'],
                'reason' => $validated['reason'] ?? null,
            ]
        );

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'tenant_id' => $tenant->id,
            'action' => 'set_pricing_override',
            'description' => "Set VIP pricing for {$tenant->name} - {$validated['channel']}",
            'metadata' => json_encode($validated),
        ]);

        return response()->json([
            'message' => 'Pricing override set',
            'override' => $override,
        ]);
    }

    /**
     * Remove pricing override for a tenant
     */
    public function deletePricingOverride(Request $request, Tenant $tenant)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'channel' => 'required|in:sms,email,audio',
        ]);

        TenantPricingOverride::where('tenant_id', $tenant->id)
            ->where('channel', $validated['channel'])
            ->delete();

        // Log the action
        AuditLog::create([
            'admin_id' => $request->user()->id,
            'tenant_id' => $tenant->id,
            'action' => 'delete_pricing_override',
            'description' => "Removed VIP pricing for {$tenant->name} - {$validated['channel']}",
            'metadata' => json_encode($validated),
        ]);

        return response()->json(['message' => 'Pricing override removed']);
    }

    /**
     * Get audit logs (admin actions)
     */
    public function auditLogs(Request $request)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $logs = AuditLog::with(['admin', 'tenant'])
            ->latest()
            ->paginate(50);

        return response()->json($logs);
    }

    /**
     * Get system analytics
     */
    public function analytics(Request $request)
    {
        if (!$this->verifySuperAdmin($request)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $trialTenants = Tenant::where('status', 'trial')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();

        return response()->json([
            'tenants' => [
                'total' => $totalTenants,
                'active' => $activeTenants,
                'trial' => $trialTenants,
                'suspended' => $suspendedTenants,
            ],
            'channels' => [
                'sms' => [
                    'total_sent' => 0,  // Would query SmsLog
                    'total_cost' => 0,  // Would sum from SmsLog
                ],
                'email' => [
                    'total_sent' => 0,  // Would query EmailLog
                    'total_cost' => 0,  // Would sum from EmailLog
                ],
                'audio' => [
                    'total_sent' => 0,  // Would query AudioLog
                    'total_cost' => 0,  // Would sum from AudioLog
                ],
            ],
        ]);
    }
}
