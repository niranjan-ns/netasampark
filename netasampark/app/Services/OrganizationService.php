<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrganizationService
{
    public function create(array $data): Organization
    {
        return DB::transaction(function () use ($data) {
            $organization = Organization::create([
                'name' => $data['name'],
                'slug' => $this->generateSlug($data['name']),
                'domain' => $data['domain'] ?? null,
                'plan' => $data['plan'] ?? 'starter',
                'modules' => $this->getDefaultModules($data['plan'] ?? 'starter'),
                'wallet_balance' => 0,
                'status' => 'active',
                'trial_ends_at' => Carbon::now()->addDays(config('netasampark.organization.trial_days')),
                'subscription_ends_at' => null,
            ]);

            if (isset($data['owner'])) {
                $this->createOwnerUser($organization, $data['owner']);
            }

            $this->initializeSettings($organization);
            Log::info('Organization created', ['id' => $organization->id, 'name' => $organization->name]);

            return $organization;
        });
    }

    public function hasFeature(Organization $organization, string $feature): bool
    {
        $cacheKey = "org_{$organization->id}_feature_{$feature}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organization, $feature) {
            if (!config("netasampark.features.{$feature}", false)) {
                return false;
            }

            $requiredPlan = $this->getRequiredPlanForFeature($feature);
            if ($requiredPlan && !$this->hasPlan($organization, $requiredPlan)) {
                return false;
            }

            return in_array($feature, $organization->modules ?? []);
        });
    }

    public function hasPlan(Organization $organization, string $plan): bool
    {
        $planHierarchy = ['starter' => 1, 'pro' => 2, 'enterprise' => 3];
        $currentPlanLevel = $planHierarchy[$organization->plan] ?? 0;
        $requiredPlanLevel = $planHierarchy[$plan] ?? 0;
        
        return $currentPlanLevel >= $requiredPlanLevel;
    }

    protected function generateSlug(string $name): string
    {
        $baseSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $slug = $baseSlug;
        $counter = 1;

        while (Organization::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function getDefaultModules(string $plan): array
    {
        $modules = [
            'starter' => ['voter_crm', 'basic_communication', 'basic_analytics'],
            'pro' => ['voter_crm', 'communication_hub', 'campaign_management', 'analytics', 'ticketing'],
            'enterprise' => ['voter_crm', 'communication_hub', 'campaign_management', 'analytics', 'ticketing', 'finance', 'ai_insights', 'voice_dialer'],
        ];

        return $modules[$plan] ?? $modules['starter'];
    }

    protected function createOwnerUser(Organization $organization, array $userData): User
    {
        return $organization->users()->create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'] ?? null,
            'password' => bcrypt($userData['password']),
            'role' => 'owner',
            'permissions' => ['*'],
            'email_verified_at' => Carbon::now(),
        ]);
    }

    protected function initializeSettings(Organization $organization): void
    {
        $defaultSettings = [
            'branding' => [
                'logo' => null,
                'primary_color' => '#3b82f6',
                'secondary_color' => '#64748b',
            ],
            'communication' => [
                'default_sms_gateway' => 'msg91',
                'default_email_provider' => 'ses',
                'dlt_compliance' => true,
            ],
            'compliance' => [
                'election_commission' => true,
                'trai_dlt' => true,
                'data_protection' => true,
            ],
        ];

        $organization->update(['settings' => $defaultSettings]);
    }

    protected function getRequiredPlanForFeature(string $feature): ?string
    {
        $featurePlans = [
            'ai_insights' => 'enterprise',
            'voice_dialer' => 'enterprise',
            'finance_management' => 'enterprise',
            'advanced_analytics' => 'pro',
            'campaign_management' => 'pro',
        ];

        return $featurePlans[$feature] ?? null;
    }
}
