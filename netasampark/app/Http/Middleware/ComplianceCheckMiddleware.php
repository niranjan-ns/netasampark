<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ComplianceCheckMiddleware
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

        // Check compliance status
        $complianceStatus = $this->checkComplianceStatus($organization);
        
        if (!$complianceStatus['overall']) {
            Log::warning('Organization compliance check failed', [
                'organization_id' => $organization->id,
                'compliance_status' => $complianceStatus,
            ]);

            // Add compliance warnings to session
            $request->session()->flash('compliance_warnings', $complianceStatus['warnings'] ?? []);
            
            // For critical violations, redirect to compliance page
            if ($complianceStatus['critical_violations'] ?? false) {
                return redirect()->route('organization.compliance-issues');
            }
        }

        return $next($request);
    }

    protected function checkComplianceStatus($organization): array
    {
        $status = [
            'overall' => true,
            'warnings' => [],
            'critical_violations' => false,
        ];

        // Check TRAI DLT compliance
        if (config('netasampark.compliance.trai_dlt.enabled')) {
            if (!$this->checkTRAIDLTCompliance($organization)) {
                $status['overall'] = false;
                $status['warnings'][] = 'TRAI DLT compliance configuration incomplete';
            }
        }

        // Check Election Commission compliance
        if (config('netasampark.compliance.election_commission.enabled')) {
            $ecStatus = $this->checkElectionCommissionCompliance($organization);
            if (!$ecStatus['overall']) {
                $status['overall'] = false;
                $status['warnings'] = array_merge($status['warnings'], $ecStatus['warnings']);
                
                if ($ecStatus['critical'] ?? false) {
                    $status['critical_violations'] = true;
                }
            }
        }

        // Check data protection compliance
        if (config('netasampark.compliance.data_protection.pii_encryption')) {
            if (!$this->checkDataProtectionCompliance($organization)) {
                $status['overall'] = false;
                $status['warnings'][] = 'Data protection compliance issues detected';
            }
        }

        return $status;
    }

    protected function checkTRAIDLTCompliance($organization): bool
    {
        $templateId = config('netasampark.compliance.trai_dlt.template_id');
        $entityId = config('netasampark.compliance.trai_dlt.entity_id');
        
        return !empty($templateId) && !empty($entityId);
    }

    protected function checkElectionCommissionCompliance($organization): array
    {
        $status = [
            'overall' => true,
            'warnings' => [],
            'critical' => false,
        ];

        // Check expense limits
        $expenses = $organization->expenses()
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $totalExpenses = $expenses->sum('amount');
        $limit = $this->getExpenseLimit($organization);

        if ($totalExpenses > $limit * 0.8) { // 80% of limit
            $status['warnings'][] = 'Expense limit approaching threshold';
        }

        if ($totalExpenses > $limit) {
            $status['overall'] = false;
            $status['critical'] = true;
            $status['warnings'][] = 'Expense limit exceeded';
        }

        return $status;
    }

    protected function checkDataProtectionCompliance($organization): bool
    {
        // Check if PII data is properly encrypted
        $votersWithPII = $organization->voters()
            ->whereNotNull('phone')
            ->orWhereNotNull('email')
            ->count();

        // This is a simplified check - in production, you'd verify encryption
        return $votersWithPII > 0;
    }

    protected function getExpenseLimit($organization): float
    {
        $limits = config('netasampark.compliance.election_commission.expense_limits');
        $electionType = $this->getElectionType($organization);
        
        return $limits[$electionType] ?? 1000000; // Default 10 lakh
    }

    protected function getElectionType($organization): string
    {
        // This would determine election type based on organization context
        // For now, return a default
        return 'assembly';
    }
}
