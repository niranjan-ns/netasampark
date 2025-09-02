<?php

namespace App\Http\Controllers;

use App\Models\Voter;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\VotersExport;
use App\Imports\VotersImport;

class VoterController extends Controller
{
    public function __construct()
    {
        $this->middleware('organization.access');
        $this->middleware('subscription.active');
        $this->middleware('feature.enabled:voter_crm');
    }

    public function index(Request $request)
    {
        $organization = Auth::user()->organization;
        
        $query = $organization->voters();

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('voter_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply constituency filter
        if ($request->filled('constituency')) {
            $query->where('constituency', $request->constituency);
        }

        // Apply district filter
        if ($request->filled('district')) {
            $query->where('district', $request->district);
        }

        // Apply state filter
        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }

        // Apply tags filter
        if ($request->filled('tags')) {
            $tags = explode(',', $request->tags);
            foreach ($tags as $tag) {
                $query->whereJsonContains('tags', trim($tag));
            }
        }

        // Apply consent filter
        if ($request->filled('consent')) {
            $query->where('consent', $request->consent === 'true');
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $voters = $query->paginate($request->get('per_page', 25));

        // Get filter options
        $constituencies = $organization->voters()->distinct()->pluck('constituency')->filter();
        $districts = $organization->voters()->distinct()->pluck('district')->filter();
        $states = $organization->voters()->distinct()->pluck('state')->filter();
        $tags = $this->getAllTags($organization);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'voters' => $voters,
                'filters' => [
                    'constituencies' => $constituencies,
                    'districts' => $districts,
                    'states' => $states,
                    'tags' => $tags,
                ],
            ]);
        }

        return view('voters.index', compact('voters', 'constituencies', 'districts', 'states', 'tags'));
    }

    public function store(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'voter_id' => 'required|string|max:50|unique:voters,voter_id,NULL,id,organization_id,' . $organization->id,
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:1000',
            'constituency' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'booth_number' => 'nullable|string|max:50',
            'part_number' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50',
            'demographics' => 'nullable|array',
            'tags' => 'nullable|array',
            'consent' => 'boolean',
            'status' => 'string|in:active,inactive,deceased,duplicate',
        ]);

        try {
            $voter = $organization->voters()->create($validated);

            Log::info('Voter created', [
                'voter_id' => $voter->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voter created successfully',
                'voter' => $voter,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Voter creation failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating voter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Voter $voter): JsonResponse
    {
        $this->authorize('view', $voter);

        $voter->load(['organization']);

        return response()->json([
            'success' => true,
            'voter' => $voter,
        ]);
    }

    public function update(Request $request, Voter $voter): JsonResponse
    {
        $this->authorize('update', $voter);
        
        $organization = Auth::user()->organization;
        
        $validated = $request->validate([
            'voter_id' => 'sometimes|required|string|max:50|unique:voters,voter_id,' . $voter->id . ',id,organization_id,' . $organization->id,
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:1000',
            'constituency' => 'sometimes|required|string|max:255',
            'district' => 'sometimes|required|string|max:255',
            'state' => 'sometimes|required|string|max:255',
            'booth_number' => 'nullable|string|max:50',
            'part_number' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50',
            'demographics' => 'nullable|array',
            'tags' => 'nullable|array',
            'consent' => 'boolean',
            'status' => 'string|in:active,inactive,deceased,duplicate',
        ]);

        try {
            $voter->update($validated);

            Log::info('Voter updated', [
                'voter_id' => $voter->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voter updated successfully',
                'voter' => $voter->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Voter update failed', [
                'voter_id' => $voter->id,
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating voter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Voter $voter): JsonResponse
    {
        $this->authorize('delete', $voter);

        try {
            $voter->delete();

            Log::info('Voter deleted', [
                'voter_id' => $voter->id,
                'organization_id' => $voter->organization_id,
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voter deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Voter deletion failed', [
                'voter_id' => $voter->id,
                'organization_id' => $voter->organization_id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting voter',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function import(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240',
            'update_existing' => 'boolean',
            'skip_duplicates' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $import = new VotersImport($organization, [
                'update_existing' => $request->boolean('update_existing', false),
                'skip_duplicates' => $request->boolean('skip_duplicates', true),
            ]);

            Excel::import($import, $request->file('file'));

            DB::commit();

            Log::info('Voters imported', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'total_rows' => $import->getRowCount(),
                'imported' => $import->getImportedCount(),
                'updated' => $import->getUpdatedCount(),
                'skipped' => $import->getSkippedCount(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Voters imported successfully',
                'import_summary' => [
                    'total_rows' => $import->getRowCount(),
                    'imported' => $import->getImportedCount(),
                    'updated' => $import->getUpdatedCount(),
                    'skipped' => $import->getSkippedCount(),
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Voters import failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while importing voters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $request->validate([
            'format' => 'required|string|in:csv,xlsx,json',
            'filters' => 'nullable|array',
        ]);

        try {
            $filters = $request->get('filters', []);
            
            $query = $organization->voters();

            // Apply filters
            if (isset($filters['constituency'])) {
                $query->where('constituency', $filters['constituency']);
            }

            if (isset($filters['district'])) {
                $query->where('district', $filters['district']);
            }

            if (isset($filters['state'])) {
                $query->where('state', $filters['state']);
            }

            if (isset($filters['tags'])) {
                foreach ($filters['tags'] as $tag) {
                    $query->whereJsonContains('tags', $tag);
                }
            }

            $voters = $query->get();

            $export = new VotersExport($voters);

            $filename = 'voters_' . $organization->slug . '_' . now()->format('Y-m-d_H-i-s') . '.' . $request->format;

            if ($request->format === 'json') {
                return response()->json([
                    'success' => true,
                    'voters' => $voters,
                ]);
            }

            // For CSV and Excel, return download URL
            $path = $export->store($filename, 'exports');

            Log::info('Voters export requested', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'format' => $request->format,
                'count' => $voters->count(),
                'filename' => $filename,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Export completed successfully',
                'download_url' => Storage::url($path),
                'filename' => $filename,
                'count' => $voters->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Voters export failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while exporting voters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id,organization_id,' . $organization->id,
            'updates' => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            $voters = $organization->voters()->whereIn('id', $request->voter_ids)->get();
            $updatedCount = 0;

            foreach ($voters as $voter) {
                $voter->update($request->updates);
                $updatedCount++;
            }

            DB::commit();

            Log::info('Voters bulk updated', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'count' => $updatedCount,
                'updates' => $request->updates,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$updatedCount} voters updated successfully",
                'updated_count' => $updatedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Voters bulk update failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while bulk updating voters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        $organization = Auth::user()->organization;
        
        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id,organization_id,' . $organization->id,
        ]);

        try {
            DB::beginTransaction();

            $voters = $organization->voters()->whereIn('id', $request->voter_ids)->get();
            $deletedCount = 0;

            foreach ($voters as $voter) {
                $voter->delete();
                $deletedCount++;
            }

            DB::commit();

            Log::info('Voters bulk deleted', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'count' => $deletedCount,
            ]);

            return response()->json([
                'success' => true,
                'message' => "{$deletedCount} voters deleted successfully",
                'deleted_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Voters bulk delete failed', [
                'organization_id' => $organization->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while bulk deleting voters',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected function getAllTags(Organization $organization): array
    {
        $tags = $organization->voters()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        return $tags;
    }
}
