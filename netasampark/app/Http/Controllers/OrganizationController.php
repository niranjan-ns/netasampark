<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class OrganizationController extends Controller
{
    protected $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
        $this->middleware('organization.access');
        $this->middleware('subscription.active');
    }

    public function settings()
    {
        $organization = Auth::user()->organization;
        
        return view('organization.settings', compact('organization'));
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255|unique:organizations,domain,' . $organization->id,
            'settings.branding.logo' => 'nullable|image|mimes:jpeg,png,gif|max:2048',
            'settings.branding.primary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.branding.secondary_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'settings.communication.default_sms_gateway' => 'nullable|string|in:msg91,route_mobile,gupshup',
            'settings.communication.default_email_provider' => 'nullable|string|in:ses,sendgrid,mailgun',
            'settings.communication.dlt_compliance' => 'nullable|boolean',
            'settings.compliance.election_commission' => 'nullable|boolean',
            'settings.compliance.trai_dlt' => 'nullable|boolean',
            'settings.compliance.data_protection' => 'nullable|boolean',
        ]);

        try {
            // Handle logo upload
            if ($request->hasFile('settings.branding.logo')) {
                $logoPath = $request->file('settings.branding.logo')->store('logos', 'public');
                $validated['settings']['branding']['logo'] = $logoPath;
            }

            $updated = $this->organizationService->updateSettings($organization, $validated);

            if ($updated) {
                Log::info('Organization settings updated', [
                    'organization_id' => $organization->id,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Settings updated successfully',
                    'organization' => $organization->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Organization settings update failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function billing()
    {
        $organization = Auth::user()->organization;
        $usageStats = $this->organizationService->getUsageStats($organization);
        
        return view('organization.billing', compact('organization', 'usageStats'));
    }

    public function upgradePlan(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'plan' => 'required|string|in:starter,pro,enterprise',
        ]);

        try {
            $upgraded = $this->organizationService->upgradePlan($organization, $validated['plan']);

            if ($upgraded) {
                Log::info('Organization plan upgraded', [
                    'organization_id' => $organization->id,
                    'user_id' => Auth::id(),
                    'new_plan' => $validated['plan'],
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Plan upgraded successfully',
                    'organization' => $organization->fresh(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade plan',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Organization plan upgrade failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while upgrading plan',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function addFunds(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:100|max:1000000',
            'payment_method' => 'required|string|in:razorpay,stripe',
        ]);

        try {
            // This would integrate with payment gateways
            // For now, just update the wallet balance
            $organization->increment('wallet_balance', $validated['amount']);

            Log::info('Organization funds added', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Funds added successfully',
                'wallet_balance' => $organization->wallet_balance,
            ]);

        } catch (\Exception $e) {
            Log::error('Organization funds addition failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding funds',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUsageStats(): JsonResponse
    {
        $organization = Auth::user()->organization;
        $usageStats = $this->organizationService->getUsageStats($organization);
        
        return response()->json([
            'success' => true,
            'usage_stats' => $usageStats,
        ]);
    }

    public function exportData(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'data_type' => 'required|string|in:voters,campaigns,messages,expenses',
            'format' => 'required|string|in:csv,json,xlsx',
            'date_range' => 'nullable|array',
            'date_range.start' => 'nullable|date',
            'date_range.end' => 'nullable|date',
        ]);

        try {
            // This would generate and return the export file
            // For now, return a success response
            Log::info('Organization data export requested', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'data_type' => $validated['data_type'],
                'format' => $validated['format'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Export request submitted successfully',
                'export_id' => uniqid('export_'),
            ]);

        } catch (\Exception $e) {
            Log::error('Organization data export failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing export request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
