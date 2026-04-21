<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CampaignLog;
use Illuminate\Http\JsonResponse;

class DashboardStatsController extends Controller
{
    /**
     * Get dashboard statistics for authenticated tenant
     */
    public function index(): JsonResponse
    {
        $tenant = auth()->user()->tenant;

        // Get all-time stats
        $allStats = CampaignLog::where('tenant_id', $tenant->id)
            ->where('status', 'sent')
            ->get();

        // Get this month stats
        $thisMonthStart = now()->startOfMonth();
        $thisMonthStats = CampaignLog::where('tenant_id', $tenant->id)
            ->where('status', 'sent')
            ->where('created_at', '>=', $thisMonthStart)
            ->get();

        // Get this week stats
        $thisWeekStart = now()->startOfWeek();
        $thisWeekStats = CampaignLog::where('tenant_id', $tenant->id)
            ->where('status', 'sent')
            ->where('created_at', '>=', $thisWeekStart)
            ->get();

        // Calculate metrics
        $stats = [
            'all_time' => [
                'total_campaigns' => $allStats->count(),
                'total_recipients' => $allStats->sum('recipients_count'),
                'total_spent' => $allStats->sum('cost'),
                'by_type' => $this->groupByType($allStats),
            ],
            'this_month' => [
                'total_campaigns' => $thisMonthStats->count(),
                'total_recipients' => $thisMonthStats->sum('recipients_count'),
                'total_spent' => $thisMonthStats->sum('cost'),
                'by_type' => $this->groupByType($thisMonthStats),
            ],
            'this_week' => [
                'total_campaigns' => $thisWeekStats->count(),
                'total_recipients' => $thisWeekStats->sum('recipients_count'),
                'total_spent' => $thisWeekStats->sum('cost'),
                'by_type' => $this->groupByType($thisWeekStats),
            ],
            'breakdown_by_type' => $this->getDetailedBreakdown(),
        ];

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Group campaigns by type
     */
    private function groupByType($campaigns)
    {
        $grouped = [];

        foreach ($campaigns as $campaign) {
            if (!isset($grouped[$campaign->type])) {
                $grouped[$campaign->type] = [
                    'count' => 0,
                    'recipients' => 0,
                    'spent' => 0,
                ];
            }

            $grouped[$campaign->type]['count']++;
            $grouped[$campaign->type]['recipients'] += $campaign->recipients_count;
            $grouped[$campaign->type]['spent'] += $campaign->cost;
        }

        return $grouped;
    }

    /**
     * Get detailed breakdown of statistics
     */
    private function getDetailedBreakdown(): array
    {
        $tenant = auth()->user()->tenant;

        return [
            'email' => [
                'total_sent' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'email')
                    ->where('status', 'sent')
                    ->sum('recipients_count'),
                'total_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'email')
                    ->where('status', 'sent')
                    ->sum('cost'),
                'average_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'email')
                    ->where('status', 'sent')
                    ->avg('cost') ?? 0,
            ],
            'sms' => [
                'total_sent' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'sms')
                    ->where('status', 'sent')
                    ->sum('recipients_count'),
                'total_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'sms')
                    ->where('status', 'sent')
                    ->sum('cost'),
                'average_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'sms')
                    ->where('status', 'sent')
                    ->avg('cost') ?? 0,
            ],
            'audio' => [
                'total_sent' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'audio')
                    ->where('status', 'sent')
                    ->sum('recipients_count'),
                'total_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'audio')
                    ->where('status', 'sent')
                    ->sum('cost'),
                'average_cost' => CampaignLog::where('tenant_id', $tenant->id)
                    ->where('type', 'audio')
                    ->where('status', 'sent')
                    ->avg('cost') ?? 0,
            ],
        ];
    }
}
