<?php

namespace App\Http\Controllers;

use App\Models\FileTracker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

class CreateFileTrackerController extends Controller
{
    /**
     * Display the create file tracker page
     */
    public function index()
    {
        $PageTitle = 'Create File Tracker';
        $PageDescription = 'Create and manage file trackers for document workflow management';
        
        return view('create_file_tracker_page.index', compact('PageTitle', 'PageDescription'));
    }

    /**
     * Store a new file tracker
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'file_number' => 'nullable|string|max:255',
                'file_title' => 'required|string|max:255',
                'file_type' => 'nullable|string|max:100',
                'priority' => 'required|in:LOW,MEDIUM,HIGH',
                'department' => 'required|string|max:100',
                'description' => 'nullable|string',
                'deadline' => 'nullable|date',
                'movement_log' => 'required|array',
                'movement_log.*.office_code' => 'required|string',
                'movement_log.*.office_name' => 'required|string',
                'movement_log.*.log_in_time' => 'required|string',
                'movement_log.*.log_in_date' => 'required|date',
                'movement_log.*.notes' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput()
                    ->with('error', 'Please fix the validation errors and try again.');
            }

            DB::beginTransaction();

            // Generate tracking ID
            $trackingId = FileTracker::generateTrackingId();

            // Create file tracker
            $tracker = FileTracker::create([
                'tracking_id' => $trackingId,
                'file_number' => $request->file_number,
                'file_title' => $request->file_title,
                'file_type' => $request->file_type,
                'priority' => $request->priority,
                'created_by' => Auth::id(),
                'created_by_name' => Auth::user()->name ?? 'System User',
                'department' => $request->department,
                'description' => $request->description,
                'status' => FileTracker::STATUS_ACTIVE,
                'date_created' => now(),
                'deadline' => $request->deadline,
                'total_offices' => count($request->movement_log),
                'notes' => $request->notes
            ]);

            // Process movement log
            $processedLog = [];
            foreach ($request->movement_log as $index => $logEntry) {
                $logId = 'LOG-' . now()->format('YmdHis') . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
                
                $processedEntry = [
                    'log_id' => $logId,
                    'office_code' => $logEntry['office_code'],
                    'office_name' => $logEntry['office_name'],
                    'log_in_time' => $logEntry['log_in_time'],
                    'log_in_date' => $logEntry['log_in_date'],
                    'notes' => $logEntry['notes'] ?? null,
                    'user_id' => Auth::id(),
                    'user_name' => Auth::user()->name ?? 'System User',
                    'timestamp' => now()->toISOString(),
                    'status' => $index === 0 ? 'active' : 'pending'
                ];
                
                $processedLog[] = $processedEntry;
            }

            $tracker->movement_log = $processedLog;
            
            // Set current office from first log entry
            if (!empty($processedLog)) {
                $tracker->current_office_code = $processedLog[0]['office_code'];
                $tracker->current_office_name = $processedLog[0]['office_name'];
                $tracker->completed_offices = 1;
            }
            
            $tracker->save();

            DB::commit();

            if ($request->expectsJson()) {
                // Add computed attributes for API response
                $tracker->is_overdue = $tracker->is_overdue;
                $tracker->days_until_deadline = $tracker->days_until_deadline;
                $tracker->completion_percentage = $tracker->completion_percentage;
                $tracker->current_movement = $tracker->getCurrentMovement();

                return response()->json([
                    'success' => true,
                    'data' => $tracker,
                    'message' => 'File tracker created successfully'
                ], 201);
            }

            return redirect()->route('create-file-tracker.index')
                ->with('success', 'File tracker created successfully with ID: ' . $trackingId);

        } catch (Exception $e) {
            DB::rollBack();
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating file tracker: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating file tracker: ' . $e->getMessage());
        }
    }

    /**
     * Get list of file trackers for the current user
     */
    public function list(Request $request)
    {
        try {
            $query = FileTracker::query();

            // Filter by current user if not admin
            if (!Auth::user()->can('view_all_file_trackers')) {
                $query->where('created_by', Auth::id());
            }

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority') && $request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_id', 'LIKE', "%{$search}%")
                      ->orWhere('file_number', 'LIKE', "%{$search}%")
                      ->orWhere('file_title', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Get results
            $trackers = $query->get();

            // Add computed attributes
            $trackers->transform(function ($tracker) {
                $tracker->is_overdue = $tracker->is_overdue;
                $tracker->days_until_deadline = $tracker->days_until_deadline;
                $tracker->completion_percentage = $tracker->completion_percentage;
                $tracker->current_movement = $tracker->getCurrentMovement();
                return $tracker;
            });

            return response()->json([
                'success' => true,
                'data' => $trackers,
                'message' => 'File trackers retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving file trackers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for file trackers
     */
    public function dashboard()
    {
        try {
            $userId = Auth::id();
            $isAdmin = Auth::user()->can('view_all_file_trackers');

            // Base query - filter by user if not admin
            $baseQuery = $isAdmin ? FileTracker::query() : FileTracker::where('created_by', $userId);

            $stats = [
                'total_trackers' => (clone $baseQuery)->count(),
                'active_trackers' => (clone $baseQuery)->where('status', FileTracker::STATUS_ACTIVE)->count(),
                'completed_trackers' => (clone $baseQuery)->where('status', FileTracker::STATUS_COMPLETED)->count(),
                'overdue_trackers' => (clone $baseQuery)->where('deadline', '<', now())
                    ->where('status', FileTracker::STATUS_ACTIVE)->count(),
                'priority_breakdown' => [
                    'high' => (clone $baseQuery)->where('priority', 'HIGH')
                        ->where('status', FileTracker::STATUS_ACTIVE)->count(),
                    'medium' => (clone $baseQuery)->where('priority', 'MEDIUM')
                        ->where('status', FileTracker::STATUS_ACTIVE)->count(),
                    'low' => (clone $baseQuery)->where('priority', 'LOW')
                        ->where('status', FileTracker::STATUS_ACTIVE)->count(),
                ],
                'recent_activity' => (clone $baseQuery)->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get()
                    ->map(function ($tracker) {
                        return [
                            'id' => $tracker->id,
                            'tracking_id' => $tracker->tracking_id,
                            'file_title' => $tracker->file_title,
                            'status' => $tracker->status,
                            'priority' => $tracker->priority,
                            'updated_at' => $tracker->updated_at
                        ];
                    })
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Dashboard statistics retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving dashboard statistics: ' . $e->getMessage()
            ], 500);
        }
    }
}
