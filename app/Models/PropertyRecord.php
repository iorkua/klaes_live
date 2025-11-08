<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyRecord extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'property_records';

    protected $fillable = [
        'mlsFNo',
        'kangisFileNo', 
        'NewKANGISFileno',
        'title_type',
        'transaction_type',
        'transaction_date',
        'serialNo',
        'pageNo',
        'volumeNo',
        'regNo',
        'instrument_type',
        'period',
        'period_unit',
        'Assignor',
        'Assignee',
        'Mortgagor',
        'Mortgagee',
        'Surrenderor',
        'Surrenderee',
        'Lessor',
        'Lessee',
        'Grantor',
        'Grantee',
        'property_description',
        'location',
        'plot_no',
        'lgsaOrCity',
        'layout',
        'schedule',
        'created_by',
        'updated_by',
        'tp_no',
        'lpkn_no',
        'approved_plan_no',
        'plot_size',
        'date_recommended',
        'date_approved',
        'lease_begins',
        'lease_expires',
        'metric_sheet',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'date_recommended' => 'date',
        'date_approved' => 'date',
        'lease_begins' => 'date',
        'lease_expires' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function fileIndexing()
    {
        return $this->belongsTo(FileIndexing::class, 'kangisFileNo', 'file_number');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
