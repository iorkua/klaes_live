<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileTracker;
use App\Services\QuickActionsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class FileTrackerApiController extends Controller
{
    /**
     * Get all file trackers with optional filtering
     * GET /api/file-trackers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = FileTracker::query();

            // Apply filters
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority') && $request->priority) {
                $query->byPriority($request->priority);
            }

            if ($request->has('department') && $request->department) {
                $query->byDepartment($request->department);
            }

            if ($request->has('created_by') && $request->created_by) {
                $query->byUser($request->created_by);
            }

            if ($request->has('overdue') && $request->overdue === 'true') {
                $query->overdue();
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('tracking_id', 'LIKE', "%{$search}%")
                      ->orWhere('file_number', 'LIKE', "%{$search}%")
                      ->orWhere('file_title', 'LIKE', "%{$search}%")
                      ->orWhere('created_by_name', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $trackers = $query->paginate($perPage);

            // Add computed attributes
            $trackers->getCollection()->transform(function ($tracker) {
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
     * Create a new file tracker
     * POST /api/file-trackers
     */
    public function store(Request $request): JsonResponse
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
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
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

            // Return created tracker with computed attributes
            $tracker->is_overdue = $tracker->is_overdue;
            $tracker->days_until_deadline = $tracker->days_until_deadline;
            $tracker->completion_percentage = $tracker->completion_percentage;
            $tracker->current_movement = $tracker->getCurrentMovement();

            return response()->json([
                'success' => true,
                'data' => $tracker,
                'message' => 'File tracker created successfully'
            ], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating file tracker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific file tracker
     * GET /api/file-trackers/{id}
     */
    public function show($id): JsonResponse
    {
        try {
            $tracker = FileTracker::find($id);

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            // Add computed attributes
            $tracker->is_overdue = $tracker->is_overdue;
            $tracker->days_until_deadline = $tracker->days_until_deadline;
            $tracker->completion_percentage = $tracker->completion_percentage;
            $tracker->current_movement = $tracker->getCurrentMovement();

            return response()->json([
                'success' => true,
                'data' => $tracker,
                'message' => 'File tracker retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving file tracker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a file tracker
     * PUT /api/file-trackers/{id}
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $tracker = FileTracker::find($id);

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'file_number' => 'nullable|string|max:255',
                'file_title' => 'nullable|string|max:255',
                'file_type' => 'nullable|string|max:100',
                'priority' => 'nullable|in:LOW,MEDIUM,HIGH',
                'department' => 'nullable|string|max:100',
                'description' => 'nullable|string',
                'deadline' => 'nullable|date',
                'status' => 'nullable|in:ACTIVE,COMPLETED,CANCELLED',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tracker->update($request->only([
                'file_number', 'file_title', 'file_type', 'priority', 
                'department', 'description', 'deadline', 'status', 'notes'
            ]));

            // Add computed attributes
            $tracker->is_overdue = $tracker->is_overdue;
            $tracker->days_until_deadline = $tracker->days_until_deadline;
            $tracker->completion_percentage = $tracker->completion_percentage;
            $tracker->current_movement = $tracker->getCurrentMovement();

            return response()->json([
                'success' => true,
                'data' => $tracker,
                'message' => 'File tracker updated successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating file tracker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a file tracker
     * DELETE /api/file-trackers/{id}
     */
    public function destroy($id): JsonResponse
    {
        try {
            $tracker = FileTracker::find($id);

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            $tracker->delete();

            return response()->json([
                'success' => true,
                'message' => 'File tracker deleted successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file tracker: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add movement to tracker
     * POST /api/file-trackers/{id}/movements
     */
    public function addMovement(Request $request, $id): JsonResponse
    {
        try {
            $tracker = FileTracker::find($id);

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'office_code' => 'required|string',
                'office_name' => 'required|string',
                'log_in_time' => 'required|string',
                'log_in_date' => 'required|date',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $tracker->addMovementLog(
                $request->office_code,
                $request->office_name,
                $request->log_in_time,
                $request->log_in_date,
                $request->notes
            );

            // Add computed attributes
            $tracker->is_overdue = $tracker->is_overdue;
            $tracker->days_until_deadline = $tracker->days_until_deadline;
            $tracker->completion_percentage = $tracker->completion_percentage;
            $tracker->current_movement = $tracker->getCurrentMovement();

            return response()->json([
                'success' => true,
                'data' => $tracker,
                'message' => 'Movement added successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error adding movement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete current movement
     * POST /api/file-trackers/{id}/complete-movement
     */
    public function completeMovement(Request $request, $id): JsonResponse
    {
        try {
            $tracker = FileTracker::find($id);

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'log_out_time' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $result = $tracker->completeCurrentMovement(
                $request->log_out_time,
                $request->notes
            );

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active movement to complete'
                ], 400);
            }

            // Add computed attributes
            $tracker->is_overdue = $tracker->is_overdue;
            $tracker->days_until_deadline = $tracker->days_until_deadline;
            $tracker->completion_percentage = $tracker->completion_percentage;
            $tracker->current_movement = $tracker->getCurrentMovement();

            return response()->json([
                'success' => true,
                'data' => $tracker,
                'message' => 'Movement completed successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error completing movement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get dashboard statistics
     * GET /api/file-trackers/dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = [
                'total_trackers' => FileTracker::count(),
                'active_trackers' => FileTracker::active()->count(),
                'completed_trackers' => FileTracker::completed()->count(),
                'overdue_trackers' => FileTracker::overdue()->count(),
                'priority_breakdown' => [
                    'high' => FileTracker::byPriority('HIGH')->active()->count(),
                    'medium' => FileTracker::byPriority('MEDIUM')->active()->count(),
                    'low' => FileTracker::byPriority('LOW')->active()->count(),
                ],
                'recent_activity' => FileTracker::orderBy('updated_at', 'desc')
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

    /**
     * Search file trackers - Quick Action
     * GET /api/file-trackers/search
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'query' => 'nullable|string|max:255',
                'file_number' => 'nullable|string|max:255',
                'file_title' => 'nullable|string|max:255',
                'priority' => 'nullable|in:Low,Medium,High,Urgent',
                'status' => 'nullable|in:Active,Completed,On Hold,Cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = FileTracker::query();

            // General search query
            if ($request->has('query') && $request->query) {
                $searchTerm = $request->query;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('tracking_id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('file_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('file_title', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('notes', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Specific field searches
            if ($request->has('file_number') && $request->file_number) {
                $query->where('file_number', 'LIKE', "%{$request->file_number}%");
            }

            if ($request->has('file_title') && $request->file_title) {
                $query->where('file_title', 'LIKE', "%{$request->file_title}%");
            }

            if ($request->has('priority') && $request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            $results = $query->orderBy('updated_at', 'desc')
                           ->limit(50)
                           ->get()
                           ->map(function ($tracker) {
                               return [
                                   'id' => $tracker->id,
                                   'tracking_id' => $tracker->tracking_id,
                                   'file_number' => $tracker->file_number,
                                   'file_title' => $tracker->file_title,
                                   'priority' => $tracker->priority,
                                   'status' => $tracker->status,
                                   'current_office' => $tracker->current_office_name,
                                   'created_at' => $tracker->created_at,
                                   'updated_at' => $tracker->updated_at,
                                   'is_overdue' => $tracker->is_overdue,
                                   'days_until_deadline' => $tracker->days_until_deadline
                               ];
                           });

            return response()->json([
                'success' => true,
                'data' => $results,
                'total' => $results->count(),
                'message' => 'Search completed successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error performing search: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Track file status by file number or tracking ID - Quick Action
     * GET /api/file-trackers/track/{identifier}
     */
    public function track(Request $request, $identifier): JsonResponse
    {
        try {
            // Try to find by tracking ID first, then by file number
            $tracker = FileTracker::where('tracking_id', $identifier)
                                 ->orWhere('file_number', $identifier)
                                 ->first();

            if (!$tracker) {
                return response()->json([
                    'success' => false,
                    'message' => 'File tracker not found'
                ], 404);
            }

            $movementLog = json_decode($tracker->movement_log, true) ?? [];

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $tracker->id,
                    'tracking_id' => $tracker->tracking_id,
                    'file_number' => $tracker->file_number,
                    'file_title' => $tracker->file_title,
                    'file_type' => $tracker->file_type,
                    'priority' => $tracker->priority,
                    'status' => $tracker->status,
                    'current_office' => $tracker->current_office_name,
                    'current_office_code' => $tracker->current_office_code,
                    'department' => $tracker->department,
                    'created_at' => $tracker->created_at,
                    'updated_at' => $tracker->updated_at,
                    'deadline' => $tracker->deadline,
                    'is_overdue' => $tracker->is_overdue,
                    'days_until_deadline' => $tracker->days_until_deadline,
                    'completion_percentage' => $tracker->completion_percentage,
                    'movement_history' => $movementLog,
                    'notes' => $tracker->notes
                ],
                'message' => 'File tracker retrieved successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error tracking file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk operations - Quick Action
     * POST /api/file-trackers/bulk
     */
    public function bulk(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'operation' => 'required|in:move,priority,archive,delete',
                'tracker_ids' => 'required|array|min:1',
                'tracker_ids.*' => 'integer|exists:file_tracker,id',
                'data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $operation = $request->operation;
            $trackerIds = $request->tracker_ids;
            $data = $request->data;

            DB::beginTransaction();

            $affectedCount = 0;

            switch ($operation) {
                case 'move':
                    // Bulk move to office
                    if (!isset($data['office_code']) || !isset($data['office_name'])) {
                        throw new Exception('Office information required for move operation');
                    }

                    foreach ($trackerIds as $trackerId) {
                        $tracker = FileTracker::find($trackerId);
                        if ($tracker) {
                            $tracker->addMovementLog([
                                'from_office' => $tracker->current_office_code,
                                'to_office' => $data['office_code'],
                                'moved_by' => Auth::user()->name ?? 'System',
                                'notes' => $data['notes'] ?? 'Bulk move operation'
                            ]);
                            $affectedCount++;
                        }
                    }
                    break;

                case 'priority':
                    // Bulk priority update
                    if (!isset($data['priority']) || !in_array($data['priority'], ['Low', 'Medium', 'High', 'Urgent'])) {
                        throw new Exception('Valid priority required for priority operation');
                    }

                    $affectedCount = FileTracker::whereIn('id', $trackerIds)
                                               ->update(['priority' => $data['priority']]);
                    break;

                case 'archive':
                    // Bulk archive (set status to completed)
                    $affectedCount = FileTracker::whereIn('id', $trackerIds)
                                               ->update(['status' => 'Completed']);
                    break;

                case 'delete':
                    // Bulk delete
                    $affectedCount = FileTracker::whereIn('id', $trackerIds)->delete();
                    break;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'operation' => $operation,
                    'affected_count' => $affectedCount
                ],
                'message' => "Bulk {$operation} operation completed successfully. {$affectedCount} trackers affected."
            ]);

        } catch (Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error performing bulk operation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export file trackers data - Quick Action
     * GET /api/file-trackers/export
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'format' => 'required|in:csv,excel,pdf',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'include_details' => 'boolean',
                'include_movement' => 'boolean',
                'include_office' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $query = FileTracker::query();

            // Apply date filters
            if ($request->from_date) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }

            if ($request->to_date) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            $trackers = $query->orderBy('created_at', 'desc')->get();

            // Prepare export data based on included options
            $exportData = $trackers->map(function ($tracker) use ($request) {
                $data = [
                    'Tracking ID' => $tracker->tracking_id,
                    'File Number' => $tracker->file_number,
                    'File Title' => $tracker->file_title,
                    'Priority' => $tracker->priority,
                    'Status' => $tracker->status,
                    'Created Date' => $tracker->created_at->format('Y-m-d H:i:s')
                ];

                if ($request->include_details) {
                    $data['File Type'] = $tracker->file_type;
                    $data['Notes'] = $tracker->notes;
                    $data['Deadline'] = $tracker->deadline;
                    $data['Is Overdue'] = $tracker->is_overdue ? 'Yes' : 'No';
                }

                if ($request->include_office) {
                    $data['Current Office'] = $tracker->current_office_name;
                    $data['Office Code'] = $tracker->current_office_code;
                    $data['Department'] = $tracker->department;
                }

                if ($request->include_movement) {
                    $movementLog = json_decode($tracker->movement_log, true) ?? [];
                    $data['Movement Count'] = count($movementLog);
                    $data['Last Movement'] = count($movementLog) > 0 ? 
                        end($movementLog)['moved_at'] ?? 'N/A' : 'N/A';
                }

                return $data;
            });

            // In a real implementation, you would generate the actual file here
            // For now, return the data structure

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => $request->format,
                    'total_records' => $exportData->count(),
                    'export_data' => $exportData,
                    'generated_at' => now()->toISOString()
                ],
                'message' => 'Export data prepared successfully'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error preparing export: ' . $e->getMessage()
            ], 500);
        }
    }
}