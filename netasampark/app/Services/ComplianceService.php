<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\Expense;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ComplianceService
{
    public function checkCampaignCompliance(Campaign $campaign): array
    {
        $checks = [
            'trai_dlt' => $this->checkTRAIDLTCompliance($campaign),
            'election_commission' => $this->checkElectionCommissionCompliance($campaign),
            'data_protection' => $this->checkDataProtectionCompliance($campaign),
            'overall' => true,
        ];

        $checks['overall'] = !in_array(false, array_slice($checks, 0, -1));

        if (!$checks['overall']) {
            Log::warning('Campaign compliance check failed', [
                'campaign_id' => $campaign->id,
                'checks' => $checks
            ]);
        }

        return $checks;
    }

    public function checkMessageCompliance(Message $message): array
    {
        $checks = [
            'content_appropriate' => $this->checkContentAppropriateness($message),
            'consent_verified' => $this->checkConsentVerification($message),
            'rate_limit' => $this->checkRateLimit($message),
            'overall' => true,
        ];

        $checks['overall'] = !in_array(false, array_slice($checks, 0, -1));

        return $checks;
    }

    public function checkExpenseCompliance(Expense $expense): array
    {
        $checks = [
            'amount_limit' => $this->checkAmountLimit($expense),
            'category_valid' => $this->checkCategoryValidity($expense),
            'documentation' => $this->checkDocumentation($expense),
            'overall' => true,
        ];

        $checks['overall'] = !in_array(false, array_slice($checks, 0, -1));

        return $checks;
    }

    public function generateComplianceReport(Organization $organization, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "compliance_report_{$organization->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 3600, function () use ($organization, $startDate, $endDate) {
            $campaigns = $organization->campaigns()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $expenses = $organization->expenses()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $messages = $organization->messages()
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            return [
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                ],
                'campaigns' => [
                    'total' => $campaigns->count(),
                    'compliant' => $campaigns->where('compliance_status', 'compliant')->count(),
                    'non_compliant' => $campaigns->where('compliance_status', 'non_compliant')->count(),
                    'pending_review' => $campaigns->where('compliance_status', 'pending_review')->count(),
                ],
                'expenses' => [
                    'total' => $expenses->count(),
                    'total_amount' => $expenses->sum('amount'),
                    'compliant' => $expenses->where('compliance_status', 'compliant')->count(),
                    'non_compliant' => $expenses->where('compliance_status', 'non_compliant')->count(),
                ],
                'messages' => [
                    'total' => $messages->count(),
                    'compliant' => $messages->where('compliance_status', 'compliant')->count(),
                    'non_compliant' => $messages->where('compliance_status', 'non_compliant')->count(),
                ],
                'violations' => $this->getViolations($organization, $startDate, $endDate),
                'recommendations' => $this->getRecommendations($organization, $startDate, $endDate),
            ];
        });
    }

    protected function checkTRAIDLTCompliance(Campaign $campaign): bool
    {
        if (!config('netasampark.compliance.trai_dlt.enabled')) {
            return true;
        }

        if ($campaign->type === 'sms') {
            $templateId = config('netasampark.compliance.trai_dlt.template_id');
            $entityId = config('netasampark.compliance.trai_dlt.entity_id');
            
            if (!$templateId || !$entityId) {
                Log::warning('TRAI DLT configuration missing', [
                    'campaign_id' => $campaign->id,
                    'template_id' => $templateId,
                    'entity_id' => $entityId,
                ]);
                return false;
            }

            // Check if content matches approved template
            return $this->validateDLTTemplate($campaign->content, $templateId);
        }

        return true;
    }

    protected function checkElectionCommissionCompliance(Campaign $campaign): bool
    {
        if (!config('netasampark.compliance.election_commission.enabled')) {
            return true;
        }

        // Check campaign timing restrictions
        if ($this->isElectionPeriod()) {
            $restrictions = $this->getElectionPeriodRestrictions();
            foreach ($restrictions as $restriction) {
                if (!$this->validateRestriction($campaign, $restriction)) {
                    return false;
                }
            }
        }

        // Check content restrictions
        if (!$this->validateElectionContent($campaign->content)) {
            return false;
        }

        return true;
    }

    protected function checkDataProtectionCompliance(Campaign $campaign): bool
    {
        if (!config('netasampark.compliance.data_protection.pii_encryption')) {
            return true;
        }

        // Check if PII data is properly encrypted
        if ($this->containsPII($campaign->content)) {
            return $this->isPIIEncrypted($campaign->content);
        }

        return true;
    }

    protected function checkContentAppropriateness(Message $message): bool
    {
        $inappropriateWords = $this->getInappropriateWords();
        $content = strtolower($message->content);

        foreach ($inappropriateWords as $word) {
            if (str_contains($content, $word)) {
                Log::warning('Inappropriate content detected', [
                    'message_id' => $message->id,
                    'word' => $word,
                ]);
                return false;
            }
        }

        return true;
    }

    protected function checkConsentVerification(Message $message): bool
    {
        $voter = $message->campaign->voters()->where('id', $message->metadata['voter_id'] ?? 0)->first();
        
        if (!$voter) {
            return false;
        }

        return $voter->consent === true;
    }

    protected function checkRateLimit(Message $message): bool
    {
        $type = $message->type;
        $organizationId = $message->organization_id;
        
        $cacheKey = "rate_limit_{$type}_{$organizationId}";
        $currentCount = Cache::get($cacheKey, 0);
        
        $limit = config("netasampark.messaging.{$type}.rate_limit", 100);
        
        if ($currentCount >= $limit) {
            return false;
        }

        Cache::put($cacheKey, $currentCount + 1, 3600); // Reset every hour
        return true;
    }

    protected function checkAmountLimit(Expense $expense): bool
    {
        $limits = config('netasampark.compliance.election_commission.expense_limits');
        $electionType = $this->getElectionType($expense->organization);
        
        if (!isset($limits[$electionType])) {
            return true;
        }

        $limit = $limits[$electionType];
        return $expense->amount <= $limit;
    }

    protected function checkCategoryValidity(Expense $expense): bool
    {
        $validCategories = [
            'publicity', 'travel', 'meetings', 'printing', 'posters',
            'banners', 'vehicles', 'accommodation', 'food', 'other'
        ];

        return in_array($expense->category, $validCategories);
    }

    protected function checkDocumentation(Expense $expense): bool
    {
        // Check if receipts are attached
        if (empty($expense->receipts)) {
            return false;
        }

        // Check if amount matches receipt total
        $receiptTotal = collect($expense->receipts)->sum('amount');
        return abs($receiptTotal - $expense->amount) < 0.01; // Allow small rounding differences
    }

    protected function validateDLTTemplate(string $content, string $templateId): bool
    {
        // This would validate against approved DLT templates
        // For now, return true
        return true;
    }

    protected function isElectionPeriod(): bool
    {
        // This would check against election calendar
        // For now, return false
        return false;
    }

    protected function getElectionPeriodRestrictions(): array
    {
        return [
            'no_campaign_24h_before' => true,
            'no_campaign_on_election_day' => true,
            'no_campaign_48h_after' => true,
        ];
    }

    protected function validateRestriction(Campaign $campaign, string $restriction): bool
    {
        // Implementation for specific restrictions
        return true;
    }

    protected function validateElectionContent(string $content): bool
    {
        $restrictedTerms = [
            'vote for', 'elect', 'choose', 'support',
            'defeat', 'oppose', 'against'
        ];

        $content = strtolower($content);
        foreach ($restrictedTerms as $term) {
            if (str_contains($content, $term)) {
                return false;
            }
        }

        return true;
    }

    protected function containsPII(string $content): bool
    {
        $piiPatterns = [
            '/\b\d{10}\b/', // Phone numbers
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\d{6}\b/', // PIN codes
        ];

        foreach ($piiPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    protected function isPIIEncrypted(string $content): bool
    {
        // This would check if PII is properly encrypted
        // For now, return true
        return true;
    }

    protected function getInappropriateWords(): array
    {
        return [
            'hate', 'discrimination', 'violence', 'illegal',
            'corruption', 'bribe', 'threat'
        ];
    }

    protected function getElectionType(Organization $organization): string
    {
        // This would determine election type based on organization context
        // For now, return a default
        return 'assembly';
    }

    protected function getViolations(Organization $organization, Carbon $startDate, Carbon $endDate): array
    {
        // This would return detailed violation information
        return [];
    }

    protected function getRecommendations(Organization $organization, Carbon $startDate, Carbon $endDate): array
    {
        // This would return compliance improvement recommendations
        return [];
    }
}
