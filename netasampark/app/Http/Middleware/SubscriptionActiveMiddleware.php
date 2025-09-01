<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $organization = $user->organization;

        if (!$organization) {
            return redirect()->route('organization.setup');
        }

        // Check if subscription is active
        if (!$this->isSubscriptionActive($organization)) {
            Log::warning('User attempting to access with inactive subscription', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'plan' => $organization->plan,
                'trial_ends_at' => $organization->trial_ends_at,
                'subscription_ends_at' => $organization->subscription_ends_at,
            ]);

            // Allow access to basic routes
            if ($this->isBasicRoute($request)) {
                return $next($request);
            }

            return redirect()->route('organization.subscription-expired');
        }

        return $next($request);
    }

    protected function isSubscriptionActive($organization): bool
    {
        // Check if trial is still active
        if ($organization->trial_ends_at && $organization->trial_ends_at->isFuture()) {
            return true;
        }

        // Check if subscription is active
        if ($organization->subscription_ends_at && $organization->subscription_ends_at->isFuture()) {
            return true;
        }

        // Check if organization has wallet balance
        if ($organization->wallet_balance > 0) {
            return true;
        }

        return false;
    }

    protected function isBasicRoute(Request $request): bool
    {
        $basicRoutes = [
            'organization.settings',
            'organization.billing',
            'organization.subscription-expired',
            'organization.trial-expired',
            'logout',
        ];

        return in_array($request->route()->getName(), $basicRoutes);
    }
}
