<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ApplicationMother;
use App\Models\Scanning;
use App\Models\PageTyping;
use App\Models\FileTracking;
use App\Models\PrintLabelBatchItem;
use App\Models\User;
use App\Models\Grouping;

class FileIndexing extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'file_indexings';
    
    protected $fillable = [
        'main_application_id',
        'subapplication_id',
        'recertification_application_id',
        'tracking_id',
        'st_fillno',
        'serial_no',
        'batch_no',
        'shelf_location',
        'shelf_label_id',
        'sys_batch_no',
        'mdc_batch_no',
        'group_no',
        'file_number',
        'file_title',
        'land_use_type',
        'plot_number',
        'tp_no',
        'lpkn_no',
        'location',
        'district',
        'registry',
        'lga',
        'property_description',
        'has_cofo',
        'is_merged',
        'has_transaction',
        'is_problematic',
        'is_co_owned_plot',
        'is_updated',
        'workflow_status',
        'has_qc_issues',
        'created_by',
        'updated_by',
        'batch_generated',
        'last_batch_id',
        'batch_generated_at',
        'batch_generated_by',
        'is_deleted',
        'deleted_at',
    ];

    protected $casts = [
        'has_cofo' => 'boolean',
        'is_merged' => 'boolean',
        'has_transaction' => 'boolean',
        'is_problematic' => 'boolean',
        'is_co_owned_plot' => 'boolean',
        'is_updated' => 'boolean',
        'is_deleted' => 'boolean',
        'has_qc_issues' => 'boolean',
        'batch_generated' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'batch_generated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function mainApplication()
    {
        return $this->belongsTo(ApplicationMother::class, 'main_application_id');
    }

    public function scannings()
    {
        return $this->hasMany(Scanning::class, 'file_indexing_id')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    public function pagetypings()
    {
        return $this->hasMany(PageTyping::class, 'file_indexing_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function grouping()
    {
        return $this->hasOne(Grouping::class, 'awaiting_fileno', 'file_number');
    }

    public function getStatusAttribute()
    {
        $hasScanning = $this->scannings()->exists();
        $hasPageTyping = $this->pagetypings()->exists();
        
        if ($hasPageTyping) {
            return 'Typed';
        } elseif ($hasScanning) {
            return 'Scanned';
        } else {
            return 'Indexed';
        }
    }

    public function recertificationApplication()
    {
        return $this->belongsTo('App\Models\RecertificationApplication', 'recertification_application_id');
    }

    public function fileTracking()
    {
        return $this->hasOne(FileTracking::class, 'file_indexing_id');
    }

    public function printLabelBatchItems()
    {
        return $this->hasMany(PrintLabelBatchItem::class, 'file_indexing_id');
    }

    public function getTrackingStatusAttribute()
    {
        $tracking = $this->fileTracking;
        if (!$tracking) {
            return 'Not Tracked';
        }
        
        return ucfirst(str_replace('_', ' ', $tracking->status));
    }

    public function getIsTrackedAttribute()
    {
        return $this->fileTracking !== null;
    }

    public function getHasLabelPrintedAttribute()
    {
        return $this->printLabelBatchItems()->exists();
    }

    /**
     * Get the first page typing record for cover type display
     */
    public function firstPageTyping()
    {
        return $this->hasOne(PageTyping::class, 'file_indexing_id')
                    ->with(['coverType', 'pageType', 'pageSubType'])
                    ->orderBy('page_number', 'asc');
    }

    /**
     * Get the cover type from the first page
     */
    public function getCoverTypeAttribute()
    {
        $firstPage = $this->firstPageTyping;
        return $firstPage ? $firstPage->coverType : null;
    }

    /**
     * Check if this file has been used in any batch tracking sheet generation
     */
    public function hasBeenInBatch()
    {
        return \App\Models\TrackingSheet::where('selected_file_ids', 'LIKE', '%"' . $this->id . '"%')->exists();
    }

    /**
     * Get the latest batch that included this file
     */
    public function getLatestBatch()
    {
        return \App\Models\TrackingSheet::where('selected_file_ids', 'LIKE', '%"' . $this->id . '"%')
                                       ->orderBy('generated_at', 'desc')
                                       ->first();
    }

    /**
     * Get formatted batch generated at date
     */
    public function getFormattedBatchGeneratedAtAttribute()
    {
        if (!$this->batch_generated_at) {
            return null;
        }

        try {
            // If it's already a Carbon instance, format it
            if ($this->batch_generated_at instanceof \Carbon\Carbon) {
                return $this->batch_generated_at->format('M j, Y H:i');
            }
            
            // If it's a string, parse it with Carbon first
            if (is_string($this->batch_generated_at)) {
                return \Carbon\Carbon::parse($this->batch_generated_at)->format('M j, Y H:i');
            }
            
            return null;
        } catch (\Exception $e) {
            // If parsing fails, return null
            return null;
        }
    }
}

