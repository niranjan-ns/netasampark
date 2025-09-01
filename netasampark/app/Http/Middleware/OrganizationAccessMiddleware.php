<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class OrganizationAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        if (!$user->organization_id) {
            Log::warning('User without organization attempting access', [
                'user_id' => $user->id,
                'email' => $user->email,
                'url' => $request->url(),
            ]);
            
            return redirect()->route('organization.setup');
        }

        $organization = $user->organization;
        
        if (!$organization) {
            Log::error('User organization not found', [
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
            ]);
            
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Organization access error. Please contact support.']);
        }

        if ($organization->status !== 'active') {
            Log::warning('User attempting to access suspended organization', [
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'status' => $organization->status,
            ]);
            
            if ($organization->status === 'suspended') {
                return redirect()->route('organization.suspended');
            }
            
            if ($organization->status === 'trial_expired') {
                return redirect()->route('organization.trial-expired');
            }
        }

        // Add organization to request for easy access
        $request->attributes->set('organization', $organization);
        
        // Check if trial is about to expire
        if ($organization->trial_ends_at && $organization->trial_ends_at->diffInDays(now()) <= 3) {
            $request->session()->flash('trial_warning', true);
        }

        return $next($request);
    }
}
