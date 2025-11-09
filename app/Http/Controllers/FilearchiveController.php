<?php

namespace App\Http\Controllers;

use App\Services\ScannerService;
use App\Models\FileIndexing;
use App\Models\PageTyping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class FilearchiveController extends Controller
{ 
    public function index(Request $request) {
        $PageTitle = 'File Digital Archive';
        $PageDescription = 'Access and manage digitally archived files';
        
        // Get completed page typed files with cover type information
        $completedFiles = FileIndexing::whereHas('pagetypings')
            ->with([
                'pagetypings' => function($query) {
                    $query->with(['typedBy', 'pageType', 'pageSubType', 'coverType', 'scanning'])
                          ->orderBy('page_number');
                }, 
                'scannings',
                // Get the cover type from the first page
                'firstPageTyping' => function($query) {
                    $query->with(['coverType', 'pageType', 'pageSubType'])
                          ->where('page_number', 1)
                          ->orWhere(function($q) {
                              $q->whereRaw('page_number = (SELECT MIN(page_number) FROM pagetypings pt2 WHERE pt2.file_indexing_id = pagetypings.file_indexing_id)');
                          });
                }
            ])
            ->withCount(['pagetypings', 'scannings'])
            ->orderBy('updated_at', 'desc');
        
        // Apply search filters if provided
        if ($request->filled('search')) {
            $search = $request->get('search');
            $field = $request->get('field', 'all');
            
            $completedFiles->where(function($query) use ($search, $field) {
                if ($field === 'all' || $field === 'fileName') {
                    $query->orWhere('file_title', 'like', "%{$search}%");
                }
                if ($field === 'all' || $field === 'fileNumber') {
                    $query->orWhere('file_number', 'like', "%{$search}%");
                }
                if ($field === 'all' || $field === 'type') {
                    $query->orWhere('land_use_type', 'like', "%{$search}%");
                }
                if ($field === 'all' || $field === 'page') {
                    $query->orWhereHas('pagetypings', function($q) use ($search) {
                        $q->where('page_type', 'like', "%{$search}%")
                          ->orWhere('page_subtype', 'like', "%{$search}%");
                    });
                }
            });
        }
        
        // Apply category filter
        if ($request->filled('category') && $request->get('category') !== 'all') {
            $category = $request->get('category');
            switch ($category) {
                case 'land':
                    $completedFiles->whereIn('land_use_type', ['Residential', 'Commercial', 'Industrial']);
                    break;
                case 'legal':
                    $completedFiles->whereHas('pagetypings', function($q) {
                        $q->whereIn('page_type', ['Deed', 'Certificate', 'Legal Document']);
                    });
                    break;
                case 'admin':
                    $completedFiles->whereHas('pagetypings', function($q) {
                        $q->whereIn('page_type', ['Application Form', 'Letter', 'Administrative']);
                    });
                    break;
            }
        }

        // Apply cover type filter
        if ($request->filled('cover_type') && $request->get('cover_type') !== 'all') {
            $coverType = $request->get('cover_type');
            $completedFiles->whereHas('firstPageTyping.coverType', function($q) use ($coverType) {
                if ($coverType === 'front') {
                    $q->where('Name', 'like', '%front%');
                } elseif ($coverType === 'back') {
                    $q->where('Name', 'like', '%back%');
                }
            });
        }
        
        $completedFiles = $completedFiles->paginate(12);
        
        // Calculate statistics
        $stats = [
            'total_archived' => FileIndexing::whereHas('pagetypings')->count(),
            'recently_added' => FileIndexing::whereHas('pagetypings')
                ->where('updated_at', '>=', now()->subDays(30))->count(),
            'total_pages' => PageTyping::count(),
            'storage_used' => $this->calculateStorageUsed(),
        ];
        
        // Get popular page types for filters
        $popularPageTypes = PageTyping::select('page_type', DB::raw('count(*) as count'))
            ->groupBy('page_type')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        return view('filearchive.index', compact(
            'PageTitle', 
            'PageDescription', 
            'completedFiles', 
            'stats', 
            'popularPageTypes'
        ));
    }
    
    /**
     * Get file details for modal display
     */
    public function getFileDetails($id)
    {
        $file = FileIndexing::with([
            'pagetypings.typedBy:id,first_name,last_name', 
            'pagetypings.coverType',
            'pagetypings.pageType',
            'pagetypings.pageSubType',
            'scannings'
        ])
        ->withCount(['pagetypings', 'scannings'])
        ->findOrFail($id);
        
        // Transform pagetypings to include proper relationship data
        $transformedPageTypings = [];
        foreach ($file->pagetypings as $pageTyping) {
            $pageSubTypeObj = $pageTyping->pageSubType;
            $transformedSubtype = $pageSubTypeObj ? [
                'id' => $pageSubTypeObj->id,
                'name' => $pageSubTypeObj->PageSubType,
                'PageSubType' => $pageSubTypeObj->PageSubType,
                'code' => $pageSubTypeObj->code
            ] : $pageTyping->page_subtype;
            
            $transformedPageTypings[] = [
                'id' => $pageTyping->id,
                'page_number' => $pageTyping->page_number,
                'page_type' => $pageTyping->pageType ? [
                    'id' => $pageTyping->pageType->id,
                    'name' => $pageTyping->pageType->PageType,
                    'PageType' => $pageTyping->pageType->PageType,
                    'code' => $pageTyping->pageType->code
                ] : $pageTyping->page_type,
                'page_subtype' => $transformedSubtype,
                'serial_number' => $pageTyping->serial_number,
                'page_code' => $pageTyping->page_code,
                'cover_type' => $pageTyping->coverType ? [
                    'id' => $pageTyping->coverType->Id,
                    'name' => $pageTyping->coverType->Name,
                    'code' => $pageTyping->coverType->code
                ] : null,
                'typed_by' => $pageTyping->typedBy ? [
                    'id' => $pageTyping->typedBy->id,
                    'name' => $pageTyping->typedBy->first_name . ' ' . $pageTyping->typedBy->last_name,
                    'first_name' => $pageTyping->typedBy->first_name,
                    'last_name' => $pageTyping->typedBy->last_name
                ] : null,
                'created_at' => $pageTyping->created_at,
                'updated_at' => $pageTyping->updated_at
            ];
        }
        
        // Create response data with transformed pagetypings
        $responseData = $file->toArray();
        $responseData['pagetypings'] = $transformedPageTypings;
        
        return response()->json([
            'success' => true,
            'file' => $responseData
        ]);
    }

    /**
     * Get document pages for viewer
     */
    public function getDocumentPages($id)
    {
        $file = FileIndexing::with([
            'pagetypings' => function($query) {
                $query->orderBy('page_number')
                      ->with([
                          'coverType',
                          'typedBy:id,first_name,last_name',
                          'pageType',
                          'pageSubType',
                          'scanning:id,file_indexing_id,document_path,display_order,original_filename,document_type,created_at'
                      ]);
            }
        ])->findOrFail($id);

        $pathPrefixes = [
            'storage/app/public/',
            'app/public/',
            'public/',
            'storage/'
        ];

        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif'];

        $resolveMedia = function (?string $rawPath) use ($pathPrefixes, $imageExtensions) {
            if (!$rawPath) {
                return null;
            }

            $rawPath = str_replace('\\', '/', $rawPath);
            $trimmedRawPath = ltrim($rawPath, '/');

            $normalizedPath = $trimmedRawPath;
            foreach ($pathPrefixes as $prefix) {
                if (stripos($normalizedPath, $prefix) === 0) {
                    $normalizedPath = substr($normalizedPath, strlen($prefix));
                    break;
                }
            }
            $normalizedPath = ltrim($normalizedPath, '/');

            $extension = null;
            if ($normalizedPath) {
                $extension = strtolower(pathinfo($normalizedPath, PATHINFO_EXTENSION));
            } elseif ($trimmedRawPath) {
                $extension = strtolower(pathinfo($trimmedRawPath, PATHINFO_EXTENSION));
            }

            $viewerUrl = null;
            if ($normalizedPath && Storage::disk('public')->exists($normalizedPath)) {
                $viewerUrl = Storage::url($normalizedPath);
            }

            if (!$viewerUrl) {
                $publicFile = public_path($trimmedRawPath);
                if ($trimmedRawPath && file_exists($publicFile)) {
                    $viewerUrl = asset($trimmedRawPath);
                }
            }

            if (!$viewerUrl) {
                return null;
            }

            $mediaType = 'document';
            $thumbnailUrl = null;
            if ($extension && in_array($extension, $imageExtensions)) {
                $mediaType = 'image';
                $thumbnailUrl = $viewerUrl;
            } elseif ($extension === 'pdf') {
                $mediaType = 'pdf';
            }

            return [
                'viewer_url' => $viewerUrl,
                'thumbnail_url' => $thumbnailUrl,
                'media_type' => $mediaType,
                'extension' => $extension,
                'normalized_path' => $normalizedPath,
            ];
        };

        $pages = $file->pagetypings->map(function($pageTyping) use ($resolveMedia) {
            $mediaSources = [
                ['path' => optional($pageTyping->scanning)->document_path, 'source' => 'scanning'],
                ['path' => $pageTyping->file_path, 'source' => 'pagetypings']
            ];

            $media = null;
            $mediaSource = null;
            foreach ($mediaSources as $candidate) {
                $media = $resolveMedia($candidate['path'] ?? null);
                if ($media) {
                    $mediaSource = $candidate['source'];
                    break;
                }
            }

            if (!$media) {
                $media = [
                    'viewer_url' => null,
                    'thumbnail_url' => null,
                    'media_type' => 'document',
                    'extension' => null,
                    'normalized_path' => null,
                ];
            }

            if ($media['media_type'] === 'document') {
                if (method_exists($pageTyping, 'isImagePage') && $pageTyping->isImagePage()) {
                    $media['media_type'] = 'image';
                    if (!$media['thumbnail_url'] && $media['viewer_url']) {
                        $media['thumbnail_url'] = $media['viewer_url'];
                    }
                } elseif (method_exists($pageTyping, 'isPdfPage') && $pageTyping->isPdfPage()) {
                    $media['media_type'] = 'pdf';
                }
            }

            return [
                'page_number' => $pageTyping->page_number,
                'page_type' => $pageTyping->pageType ? [
                    'id' => $pageTyping->pageType->id,
                    'name' => $pageTyping->pageType->PageType,
                    'code' => $pageTyping->pageType->code
                ] : [
                    'id' => $pageTyping->page_type,
                    'name' => 'Unknown Type',
                    'code' => 'UNK'
                ],
                'page_subtype' => $pageTyping->pageSubType ? [
                    'id' => $pageTyping->pageSubType->id,
                    'name' => $pageTyping->pageSubType->PageSubType,
                    'code' => $pageTyping->pageSubType->code
                ] : ($pageTyping->page_subtype ? [
                    'id' => $pageTyping->page_subtype,
                    'name' => 'Unknown Subtype',
                    'code' => 'UNK'
                ] : null),
                'page_code' => $pageTyping->page_code,
                'serial_number' => $pageTyping->serial_number,
                'cover_type' => $pageTyping->coverType ? [
                    'id' => $pageTyping->coverType->Id,
                    'name' => $pageTyping->coverType->Name,
                    'code' => $pageTyping->coverType->code
                ] : null,
                'typed_by' => $pageTyping->typedBy ? [
                    'name' => $pageTyping->typedBy->first_name . ' ' . $pageTyping->typedBy->last_name
                ] : null,
                'viewer_url' => $media['viewer_url'],
                'thumbnail_url' => $media['thumbnail_url'],
                'media_type' => $media['media_type'],
                'media_source' => $mediaSource,
                'pdf_page_number' => method_exists($pageTyping, 'getPdfPageNumber') ? $pageTyping->getPdfPageNumber() : null,
                'scanning_id' => optional($pageTyping->scanning)->id,
                'scanning_display_order' => optional($pageTyping->scanning)->display_order,
                'scanning_original_filename' => optional($pageTyping->scanning)->original_filename,
                'scanning_document_type' => optional($pageTyping->scanning)->document_type,
                'scanning_document_path' => optional($pageTyping->scanning)->document_path,
                'created_at' => $pageTyping->created_at->format('Y-m-d H:i:s')
            ];
        });

        return response()->json([
            'success' => true,
            'file' => [
                'id' => $file->id,
                'file_number' => $file->file_number,
                'file_title' => $file->file_title,
                'total_pages' => $file->pagetypings->count()
            ],
            'pages' => $pages
        ]);
    }
    
    /**
     * Search files with advanced filters
     */
    public function search(Request $request)
    {
        $query = FileIndexing::whereHas('pagetypings')
            ->with(['pagetypings.typedBy', 'scannings'])
            ->withCount(['pagetypings', 'scannings']);
        
        // Apply filters
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('file_title', 'like', "%{$search}%")
                  ->orWhere('file_number', 'like', "%{$search}%")
                  ->orWhereHas('pagetypings', function($subQ) use ($search) {
                      $subQ->where('page_type', 'like', "%{$search}%");
                  });
            });
        }
        
        $files = $query->paginate(12);
        
        return response()->json([
            'success' => true,
            'files' => $files,
            'html' => view('filearchive.partials.files_grid_content', compact('files'))->render()
        ]);
    }
    
    /**
     * Calculate storage used by archived files
     */
    private function calculateStorageUsed()
    {
        // This is a placeholder - you might want to implement actual file size calculation
        // based on your scanning files or implement a more sophisticated storage tracking
        return '4.2 GB';
    }
}


