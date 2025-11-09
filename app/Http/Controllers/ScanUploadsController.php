<?php

namespace App\Http\Controllers;

use App\Models\FileIndexing;
use App\Models\PageTyping;
use App\Models\Scanning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ScanUploadsController extends Controller
{
    /**
     * Display the scan uploads dashboard.
     */
    public function index(Request $request)
    {
        $PageTitle = 'Scan Uploads';
        $PageDescription = 'Manage scan uploads and document processing.';

        $stats = $this->getDashboardStats();
        $recentUploads = $this->getRecentUploads();

        $payload = [
            'stats' => $stats,
            'uploads' => $recentUploads,
        ];

        return view('scan_uploads.index', compact(
            'PageTitle',
            'PageDescription',
            'payload'
        ));
    }

    /**
     * Fetch grouped upload logs with optional filtering.
     */
    public function log(Request $request)
    {
        try {
            $query = Scanning::on('sqlsrv')
                ->with(['fileIndexing', 'uploader'])
                ->latest('created_at');

            // Apply optional filters
            if ($request->filled('file_number')) {
                $query->whereHas('fileIndexing', function ($q) use ($request) {
                    $q->where('file_number', $request->input('file_number'));
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            if ($request->filled('document_type')) {
                $query->where('document_type', $request->input('document_type'));
            }

            $limit = (int) $request->input('limit', 100);
            $scans = $query->limit($limit)->get();

            // Normalize and group by file_number
            $grouped = [];
            foreach ($scans as $scan) {
                $fileNumber = $scan->fileIndexing?->file_number ?? 'UNKNOWN';
                if (!isset($grouped[$fileNumber])) {
                    $grouped[$fileNumber] = [];
                }
                $grouped[$fileNumber][] = $this->formatDocumentPayload($scan);
            }

            return response()->json([
                'success' => true,
                'data' => $grouped,
                'count' => $scans->count(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Scan log fetch failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch scan logs.',
            ], 500);
        }
    }

    /**
     * Handle single file upload.
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_indexing_id' => 'nullable|integer|exists:sqlsrv.file_indexings,id',
            'file_number' => 'nullable|string|max:255',
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,bmp,tiff,webp,pdf|max:51200',
            'paper_size' => 'nullable|string|in:A4,A5,A3,Letter,Legal,Custom',
            'document_type' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'display_order' => 'nullable|integer|min:0',
            'parent_scan_id' => 'nullable|integer|exists:sqlsrv.scannings,id',
            'is_pdf_converted' => 'sometimes|boolean',
            'original_filename' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $payload = $validator->validated();
            $file = $request->file('file');

            // Resolve the FileIndexing record
            $fileIndexing = $this->resolveFileIndexing($payload);
            if (!$fileIndexing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to find the specified indexed file.',
                ], 404);
            }

            // Extract file metadata
            $originalName = $payload['original_filename'] ?? $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $fileSize = $file->getSize();
            $paperSize = $payload['paper_size'] ?? $this->detectPaperSize($file);
            $documentType = $payload['document_type'] ?? $this->detectDocumentType($originalName);

            // Generate storage directory and filename
            $directory = 'EDMS/SCAN_UPLOAD/' . $fileIndexing->file_number;
            $filename = $this->generateFilename($fileIndexing, $extension);

            // Store file using Laravel's Storage facade
            $storedPath = $file->storeAs($directory, $filename, 'public');

            if (!$storedPath) {
                throw new \Exception('Failed to store file on disk');
            }

            Log::info('File stored successfully', [
                'path' => $storedPath,
                'size' => $fileSize,
                'file_indexing_id' => $fileIndexing->id,
            ]);

            // Create Scanning record with all metadata
            $scanning = Scanning::on('sqlsrv')->create([
                'file_indexing_id' => $fileIndexing->id,
                'document_path' => $storedPath,
                'uploaded_by' => Auth::id(),
                'status' => 'pending',
                'original_filename' => $originalName,
                'paper_size' => $paperSize,
                'document_type' => $documentType,
                'notes' => $payload['notes'] ?? null,
                'display_order' => $payload['display_order'] ?? null,
                'file_size' => $fileSize,
                'is_pdf_converted' => $payload['is_pdf_converted'] ?? false,
                'parent_scan_id' => $payload['parent_scan_id'] ?? null,
            ]);

            // Mark file_indexing as updated
            try {
                $fileIndexing->update(['is_updated' => 1]);
            } catch (Throwable $e) {
                Log::warning('Could not update file_indexing.is_updated', [
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Scan upload completed', [
                'file_indexing_id' => $fileIndexing->id,
                'scanning_id' => $scanning->id,
                'uploaded_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'data' => $this->formatDocumentPayload($scanning->fresh(['fileIndexing', 'uploader'])),
            ]);
        } catch (Throwable $exception) {
            Log::error('Scan upload failed', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to upload document: ' . $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a scan record and associated file.
     */
    public function destroy(Scanning $scan)
    {
        try {
            // Check if scan has associated page typing (constraint)
            $pageTypingCount = PageTyping::on('sqlsrv')
                ->where('scanning_id', $scan->id)
                ->count();

            if ($pageTypingCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete scan: page typing is already in progress.',
                ], 409);
            }

            // Get document path before deletion
            $documentPath = $scan->document_path;
            $fileNumber = $scan->fileIndexing?->file_number;

            // Delete the file from storage
            if ($documentPath) {
                Storage::disk('public')->delete($documentPath);
                Log::info('File deleted from storage', ['path' => $documentPath]);
            }

            // Delete the database record
            $scan->delete();

            // Attempt to clean up empty directories
            if ($fileNumber) {
                try {
                    $directory = storage_path('app/public/EDMS/SCAN_UPLOAD/' . $fileNumber);
                    if (is_dir($directory) && count(scandir($directory)) <= 2) {
                        rmdir($directory);
                    }
                } catch (Throwable $e) {
                    Log::warning('Could not clean up empty directory', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Scan deleted', ['scan_id' => $scan->id]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (Throwable $exception) {
            Log::error('Scan deletion failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete document.',
            ], 500);
        }
    }

    /**
     * Debug endpoint: return filesystem diagnostics.
     */
    public function debug()
    {
        try {
            $basePath = storage_path('app/public/EDMS/SCAN_UPLOAD');
            $writable = is_writable($basePath);

            // Count total files recursively
            $fileCount = 0;
            $dirCount = 0;
            if (is_dir($basePath)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    if ($file->isFile()) {
                        $fileCount++;
                    } elseif ($file->isDir()) {
                        $dirCount++;
                    }
                }
            }

            // Build directory tree (limited depth)
            $tree = $this->buildDirectoryTree($basePath, 0, 3);

            return response()->json([
                'success' => true,
                'data' => [
                    'base_path' => $basePath,
                    'exists' => is_dir($basePath),
                    'writable' => $writable,
                    'file_count' => $fileCount,
                    'directory_count' => $dirCount,
                    'tree' => $tree,
                    'storage_disk' => 'public',
                    'disk_free_space' => disk_free_space($basePath) ?: 'N/A',
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Debug endpoint failed', [
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve debug information.',
            ], 500);
        }
    }

    /**
     * Get dashboard statistics.
     */
    protected function getDashboardStats()
    {
        try {
            $today = now()->startOfDay();

            $todayCount = Scanning::on('sqlsrv')
                ->where('created_at', '>=', $today)
                ->count();

            $pendingPageTyping = PageTyping::on('sqlsrv')
                ->where('status', 'pending')
                ->count();

            $totalScanned = Scanning::on('sqlsrv')->count();

            return [
                'today_uploads' => $todayCount,
                'pending_page_typing' => $pendingPageTyping,
                'total_scanned' => $totalScanned,
            ];
        } catch (Throwable $e) {
            Log::warning('Dashboard stats computation failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'today_uploads' => 0,
                'pending_page_typing' => 0,
                'total_scanned' => 0,
            ];
        }
    }

    /**
     * Get recent uploads with related data.
     */
    protected function getRecentUploads()
    {
        try {
            $scans = Scanning::on('sqlsrv')
                ->with(['fileIndexing', 'uploader'])
                ->latest('created_at')
                ->limit(10)
                ->get();

            return $scans->map(fn ($scan) => $this->formatDocumentPayload($scan))->all();
        } catch (Throwable $e) {
            Log::warning('Recent uploads fetch failed', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Format a Scanning record into the normalized API response.
     */
    protected function formatDocumentPayload(Scanning $scan)
    {
        $fileIndexing = $scan->fileIndexing;
        $uploader = $scan->uploader;

        return [
            'id' => $scan->id,
            'fileNumber' => $fileIndexing?->file_number ?? 'N/A',
            'originalName' => $scan->original_filename,
            'paperSize' => $scan->paper_size,
            'documentType' => $scan->document_type,
            'fileSize' => $scan->file_size,
            'isPdfConverted' => (bool) $scan->is_pdf_converted,
            'parentScanId' => $scan->parent_scan_id,
            'status' => $scan->status,
            'uploadedAt' => $scan->created_at?->toIso8601String(),
            'uploadedBy' => $uploader?->name ?? 'System',
            'downloadUrl' => $scan->document_path ? asset('storage/' . $scan->document_path) : null,
            'displayOrder' => $scan->display_order,
            'notes' => $scan->notes,
            'documentPath' => $scan->document_path,
        ];
    }

    /**
     * Resolve a FileIndexing record from payload parameters.
     */
    protected function resolveFileIndexing(array $payload)
    {
        if (!empty($payload['file_indexing_id'])) {
            return FileIndexing::on('sqlsrv')->find($payload['file_indexing_id']);
        }

        if (!empty($payload['file_number'])) {
            return FileIndexing::on('sqlsrv')
                ->where('file_number', $payload['file_number'])
                ->first();
        }

        return null;
    }

    /**
     * Generate a unique filename with timestamp and random suffix.
     */
    protected function generateFilename(FileIndexing $fileIndexing, string $extension)
    {
        $slug = Str::slug($fileIndexing->file_number);
        $timestamp = now()->timestamp;
        $random = Str::random(6);

        return "{$slug}_{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Detect paper size from file dimensions (stub for image processing).
     */
    protected function detectPaperSize($file)
    {
        // In real implementation, use getimagesize() or similar
        return 'A4';
    }

    /**
     * Detect document type from filename or MIME.
     */
    protected function detectDocumentType(string $filename)
    {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'PDF Document',
            'jpg', 'jpeg' => 'JPEG Image',
            'png' => 'PNG Image',
            'gif' => 'GIF Image',
            'bmp' => 'BMP Image',
            'tiff' => 'TIFF Image',
            'webp' => 'WebP Image',
            default => 'Document',
        };
    }

    /**
     * Build a directory tree structure up to a depth limit.
     */
    protected function buildDirectoryTree(string $dir, int $depth = 0, int $maxDepth = 3)
    {
        $tree = [];

        if ($depth > $maxDepth || !is_dir($dir)) {
            return $tree;
        }

        try {
            $files = @scandir($dir);
            if (!$files) {
                return $tree;
            }

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($filePath)) {
                    $tree[$file] = $this->buildDirectoryTree($filePath, $depth + 1, $maxDepth);
                } else {
                    $tree[$file] = filesize($filePath) . ' bytes';
                }
            }
        } catch (Throwable $e) {
            Log::warning('Directory tree scan failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $tree;
    }
}
