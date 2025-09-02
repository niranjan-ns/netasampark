<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FeatureEnabledMiddleware
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $organization = $user->organization;

        if (!$organization) {
            return redirect()->route('organization.setup');
        }

        // Check if feature is enabled for the organization
        if (!$this->isFeatureEnabled($organization, $feature)) {
            Log::warning('User attempting to access disabled feature', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'feature' => $feature,
                'plan' => $organization->plan,
            ]);

            return redirect()->route('organization.feature-disabled', ['feature' => $feature]);
        }

        return $next($request);
    }

    protected function isFeatureEnabled($organization, string $feature): bool
    {
        // Check if feature is enabled globally
        if (!config("netasampark.features.{$feature}", false)) {
            return false;
        }

        // Check if organization has the required plan
        $requiredPlan = $this->getRequiredPlanForFeature($feature);
        if ($requiredPlan && !$this->hasPlan($organization, $requiredPlan)) {
            return false;
        }

        // Check if organization has the feature enabled in modules
        return in_array($feature, $organization->modules ?? []);
    }

    protected function hasPlan($organization, string $plan): bool
    {
        $planHierarchy = ['starter' => 1, 'pro' => 2, 'enterprise' => 3];
        
        $currentPlanLevel = $planHierarchy[$organization->plan] ?? 0;
        $requiredPlanLevel = $planHierarchy[$plan] ?? 0;
        
        return $currentPlanLevel >= $requiredPlanLevel;
    }

    protected function getRequiredPlanForFeature(string $feature): ?string
    {
        $featurePlans = [
            'ai_insights' => 'enterprise',
            'voice_dialer' => 'enterprise',
            'finance_management' => 'enterprise',
            'advanced_analytics' => 'pro',
            'campaign_management' => 'pro',
            'ticketing' => 'pro',
            'communication_hub' => 'pro',
        ];

        return $featurePlans[$feature] ?? null;
    }
}
