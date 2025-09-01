<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Organization;
use App\Services\MessagingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CampaignController extends Controller
{
    protected $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
        $this->middleware('organization.access');
        $this->middleware('subscription.active');
        $this->middleware('feature.enabled:campaign_management');
    }

    public function index(Request $request)
    {
        $organization = Auth::user()->organization;
        
        $query = $organization->campaigns();

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('constituency')) {
            $query->whereJsonContains('target_audience->constituency', $request->constituency);
        }

        if ($request->filled('date_range')) {
            $query->whereBetween('created_at', [
                $request->date_range['start'],
                $request->date_range['end']
            ]);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate($request->get('per_page', 25));

        // Get campaign statistics
        $stats = $this->getCampaignStats($organization);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'campaigns' => $campaigns,
                'stats' => $stats,
            ]);
        }

        return view('campaigns.index', compact('campaigns', 'stats'));
    }

    public function store(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|string|in:sms,whatsapp,email,voice',
            'content' => 'required|string|max:5000',
            'target_audience' => 'required|array',
            'target_audience.constituency' => 'nullable|string|max:255',
            'target_audience.district' => 'nullable|string|max:255',
            'target_audience.state' => 'nullable|string|max:255',
            'target_audience.age_range' => 'nullable|array',
            'target_audience.age_range.min' => 'nullable|integer|min:18|max:120',
            'target_audience.age_range.max' => 'nullable|integer|min:18|max:120',
            'target_audience.tags' => 'nullable|array',
            'target_audience.tags.*' => 'string|max:100',
            'scheduled_at' => 'nullable|date|after:now',
            'settings' => 'nullable|array',
            'settings.priority' => 'nullable|string|in:low,normal,high,urgent',
            'settings.retry_count' => 'nullable|integer|min:0|max:5',
            'settings.timezone' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Validate target audience
            $this->validateTargetAudience($validated['target_audience'], $organization);

            // Check if campaign name is unique for the organization
            if ($organization->campaigns()->where('name', $validated['name'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign name already exists',
                ], 422);
            }

            $campaign = $organization->campaigns()->create([
                ...$validated,
                'status' => $validated['scheduled_at'] ? 'scheduled' : 'draft',
                'total_recipients' => $this->calculateTargetRecipients($validated['target_audience'], $organization),
            ]);

            DB::commit();

            Log::info('Campaign created', [
                'campaign_id' => $campaign->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign created successfully',
                'campaign' => $campaign,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Campaign creation failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        $campaign->load(['organization', 'messages']);

        // Get campaign performance metrics
        $metrics = $this->getCampaignMetrics($campaign);

        return response()->json([
            'success' => true,
            'campaign' => $campaign,
            'metrics' => $metrics,
        ]);
    }

    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);
        
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|required|string|in:sms,whatsapp,email,voice',
            'content' => 'sometimes|required|string|max:5000',
            'target_audience' => 'sometimes|required|array',
            'target_audience.constituency' => 'nullable|string|max:255',
            'target_audience.district' => 'nullable|string|max:255',
            'target_audience.state' => 'nullable|string|max:255',
            'target_audience.age_range' => 'nullable|array',
            'target_audience.age_range.min' => 'nullable|integer|min:18|max:120',
            'target_audience.age_range.max' => 'nullable|integer|min:18|max:120',
            'target_audience.tags' => 'nullable|array',
            'target_audience.tags.*' => 'string|max:100',
            'scheduled_at' => 'nullable|date|after:now',
            'settings' => 'nullable|array',
            'settings.priority' => 'nullable|string|in:low,normal,high,urgent',
            'settings.retry_count' => 'nullable|integer|min:0|max:5',
            'settings.timezone' => 'nullable|string|max:100',
        ]);

        try {
            DB::beginTransaction();

            // Validate target audience if provided
            if (isset($validated['target_audience'])) {
                $this->validateTargetAudience($validated['target_audience'], $organization);
                $validated['total_recipients'] = $this->calculateTargetRecipients($validated['target_audience'], $organization);
            }

            // Check if campaign name is unique (excluding current campaign)
            if (isset($validated['name']) && 
                $organization->campaigns()->where('name', $validated['name'])->where('id', '!=', $campaign->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign name already exists',
                ], 422);
            }

            // Update status based on scheduled_at
            if (isset($validated['scheduled_at'])) {
                $validated['status'] = $validated['scheduled_at'] ? 'scheduled' : 'draft';
            }

            $campaign->update($validated);

            DB::commit();

            Log::info('Campaign updated', [
                'campaign_id' => $campaign->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign updated successfully',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Campaign update failed', [
                'campaign_id' => $campaign->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Campaign $campaign): JsonResponse
    {
        $this->authorize('delete', $campaign);

        try {
            // Only allow deletion of draft or failed campaigns
            if (!in_array($campaign->status, ['draft', 'failed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft or failed campaigns can be deleted',
                ], 422);
            }

            $campaign->delete();

            Log::info('Campaign deleted', [
                'campaign_id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign deletion failed', [
                'campaign_id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function send(Campaign $campaign): JsonResponse
    {
        $this->authorize('update', $campaign);

        try {
            // Check if campaign can be sent
            if (!in_array($campaign->status, ['draft', 'scheduled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign cannot be sent in current status',
                ], 422);
            }

            // Check if campaign has recipients
            if ($campaign->total_recipients === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campaign has no target recipients',
                ], 422);
            }

            // Update campaign status
            $campaign->update([
                'status' => 'sending',
                'started_at' => now(),
            ]);

            // Send campaign in background
            dispatch(function () use ($campaign) {
                try {
                    $this->messagingService->sendCampaign($campaign);
                    
                    $campaign->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    $campaign->update([
                        'status' => 'failed',
                        'metadata' => array_merge($campaign->metadata ?? [], ['error' => $e->getMessage()]),
                    ]);
                    
                    Log::error('Campaign sending failed', [
                        'campaign_id' => $campaign->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();

            Log::info('Campaign sending initiated', [
                'campaign_id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign sending initiated',
                'campaign' => $campaign->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign send initiation failed', [
                'campaign_id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while initiating campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function analytics(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        try {
            $analytics = $this->getCampaignAnalytics($campaign);

            return response()->json([
                'success' => true,
                'analytics' => $analytics,
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign analytics failed', [
                'campaign_id' => $campaign->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function duplicate(Campaign $campaign): JsonResponse
    {
        $this->authorize('view', $campaign);

        try {
            $duplicate = $campaign->replicate();
            $duplicate->name = $campaign->name . ' (Copy)';
            $duplicate->status = 'draft';
            $duplicate->scheduled_at = null;
            $duplicate->started_at = null;
            $duplicate->completed_at = null;
            $duplicate->sent_count = 0;
            $duplicate->delivered_count = 0;
            $duplicate->opened_count = 0;
            $duplicate->replies_count = 0;
            $duplicate->save();

            Log::info('Campaign duplicated', [
                'original_campaign_id' => $campaign->id,
                'duplicate_campaign_id' => $duplicate->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Campaign duplicated successfully',
                'campaign' => $duplicate,
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign duplication failed', [
                'campaign_id' => $campaign->id,
                'organization_id' => $campaign->organization_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating campaign',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function validateTargetAudience(array $targetAudience, Organization $organization): void
    {
        $validator = Validator::make($targetAudience, [
            'constituency' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'age_range' => 'nullable|array',
            'age_range.min' => 'nullable|integer|min:18|max:120',
            'age_range.max' => 'nullable|integer|min:18|max:120',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:100',
        ]);

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        // Validate that at least one targeting criteria is provided
        if (empty(array_filter($targetAudience))) {
            throw new \InvalidArgumentException('At least one targeting criteria must be specified');
        }
    }

    protected function calculateTargetRecipients(array $targetAudience, Organization $organization): int
    {
        $query = $organization->voters();

        if (isset($targetAudience['constituency'])) {
            $query->where('constituency', $targetAudience['constituency']);
        }

        if (isset($targetAudience['district'])) {
            $query->where('district', $targetAudience['district']);
        }

        if (isset($targetAudience['state'])) {
            $query->where('state', $targetAudience['state']);
        }

        if (isset($targetAudience['age_range'])) {
            $query->whereBetween('date_of_birth', [
                now()->subYears($targetAudience['age_range']['max'] ?? 120),
                now()->subYears($targetAudience['age_range']['min'] ?? 18),
            ]);
        }

        if (isset($targetAudience['tags'])) {
            foreach ($targetAudience['tags'] as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        return $query->count();
    }

    protected function getCampaignStats(Organization $organization): array
    {
        $totalCampaigns = $organization->campaigns()->count();
        $activeCampaigns = $organization->campaigns()->whereIn('status', ['draft', 'scheduled', 'sending'])->count();
        $completedCampaigns = $organization->campaigns()->where('status', 'completed')->count();
        $failedCampaigns = $organization->campaigns()->where('status', 'failed')->count();

        $totalMessages = $organization->messages()->count();
        $deliveredMessages = $organization->messages()->where('status', 'delivered')->count();
        $failedMessages = $organization->messages()->where('status', 'failed')->count();

        return [
            'total_campaigns' => $totalCampaigns,
            'active_campaigns' => $activeCampaigns,
            'completed_campaigns' => $completedCampaigns,
            'failed_campaigns' => $failedCampaigns,
            'total_messages' => $totalMessages,
            'delivered_messages' => $deliveredMessages,
            'failed_messages' => $failedMessages,
            'delivery_rate' => $totalMessages > 0 ? round(($deliveredMessages / $totalMessages) * 100, 2) : 0,
        ];
    }

    protected function getCampaignMetrics(Campaign $campaign): array
    {
        $messages = $campaign->messages;

        $totalMessages = $messages->count();
        $deliveredMessages = $messages->where('status', 'delivered')->count();
        $failedMessages = $messages->where('status', 'failed')->count();
        $pendingMessages = $messages->where('status', 'pending')->count();

        $deliveryRate = $totalMessages > 0 ? round(($deliveredMessages / $totalMessages) * 100, 2) : 0;
        $failureRate = $totalMessages > 0 ? round(($failedMessages / $totalMessages) * 100, 2) : 0;

        return [
            'total_messages' => $totalMessages,
            'delivered_messages' => $deliveredMessages,
            'failed_messages' => $failedMessages,
            'pending_messages' => $pendingMessages,
            'delivery_rate' => $deliveryRate,
            'failure_rate' => $failureRate,
            'progress_percentage' => $totalMessages > 0 ? round((($deliveredMessages + $failedMessages) / $totalMessages) * 100, 2) : 0,
        ];
    }

    protected function getCampaignAnalytics(Campaign $campaign): array
    {
        $messages = $campaign->messages;

        // Hourly delivery pattern
        $hourlyPattern = $messages->groupBy(function ($message) {
            return $message->sent_at ? $message->sent_at->format('H') : 'unknown';
        })->map->count();

        // Status distribution
        $statusDistribution = $messages->groupBy('status')->map->count();

        // Cost analysis
        $totalCost = $messages->sum('cost');
        $averageCost = $messages->avg('cost');

        return [
            'hourly_pattern' => $hourlyPattern,
            'status_distribution' => $statusDistribution,
            'cost_analysis' => [
                'total_cost' => $totalCost,
                'average_cost' => $averageCost,
                'cost_per_message' => $totalCost / max($messages->count(), 1),
            ],
            'performance_metrics' => $this->getCampaignMetrics($campaign),
        ];
    }
}
