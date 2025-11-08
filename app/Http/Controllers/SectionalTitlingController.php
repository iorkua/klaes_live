<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class  SectionalTitlingController extends Controller
{

 

    

    public function index()
    {
        $PageTitle = 'Sectional Titling Module (STM)';
        $PageDescription = 'Process CofO for individually owned sections of multi-unit developments.';
        $Primary = DB::connection('sqlsrv')->table('dbo.mother_applications')
                ->orderBy('sys_date', 'desc')
                ->take(5)
                ->get();
        
        $Secondary = DB::connection('sqlsrv')->table('dbo.subapplications')
                ->orderBy('sys_date', 'desc')
                ->take(5)
                ->get();


        return view('sectionaltitling.index', compact(
            'Primary', 
            'Secondary',
            'PageTitle',
            'PageDescription'
        ));
    }

  public function Primary(Request $request)

    {
        if ($request->has('survey')) {
            $PageTitle = 'Sectional Titling Survey';
            $PageDescription = 'Process Survey Applications';
        } else {
            $PageTitle = $request->get('url') === 'phy_planning' ? 'Planning Recommendation Approval' : 
                    ($request->get('url') === 'recommendation' ? 'Planning Recommendation' : 'Primary Applications');
            $PageDescription = $request->get('url') === 'phy_planning' ? '' : 
                    ($request->get('url') === 'recommendation' ? 'Review and process planning recommendation for sectional titles' : 'Process CofO for individually owned sections of multi-unit developments.');
        }

        $PrimaryApplications = DB::connection('sqlsrv')->table('dbo.mother_applications')->orderBy('sys_date', 'desc')->get();
         

        return view('sectionaltitling.primary', compact('PrimaryApplications', 'PageTitle', 'PageDescription'));
    }

  public function Secondary(Request $request)
    {
        if ($request->has('survey')) {
            $PageTitle = 'Sectional Titling Survey';
            $PageDescription = 'Process Survey Applications';
        } else {
            $PageTitle = $request->get('url') === 'phy_planning' ? 'Planning Recommendation Approval' : 
                         ($request->get('url') === 'recommendation' ? 'Planning Recommendation' : 'Secondary  Applications');
            $PageDescription = $request->get('url') === 'phy_planning' ? '' : 
                               ($request->get('url') === 'recommendation' ? 'Review and process planning recommendation for sectional titles' : 'Process CofO for individually owned sections of multi-unit developments.');
        }

        $SecondaryApplications = DB::connection('sqlsrv')->table('dbo.subapplications')
            ->leftJoin('dbo.mother_applications', 'dbo.subapplications.main_application_id', '=', 'dbo.mother_applications.id')
            ->select(
            'dbo.subapplications.fileno', 
            'dbo.subapplications.applicant_type',
            'dbo.subapplications.scheme_no',
            'dbo.subapplications.id',
            'dbo.subapplications.unit_type',
            'dbo.subapplications.main_application_id',
            'dbo.subapplications.applicant_title',
            'dbo.subapplications.first_name',
            'dbo.subapplications.surname',
            'dbo.subapplications.corporate_name',
            'dbo.subapplications.multiple_owners_names',
            'dbo.subapplications.phone_number',
            'dbo.subapplications.planning_recommendation_status',
            'dbo.subapplications.application_status',
            'dbo.subapplications.created_at',
            'dbo.subapplications.unit_number',
            'dbo.subapplications.main_id',
            'dbo.subapplications.is_sua_unit',
            'dbo.subapplications.land_use as sub_land_use',

            'dbo.subapplications.passport',
            'dbo.subapplications.multiple_owners_passport',
            'dbo.mother_applications.fileno as mother_fileno',
           'dbo.mother_applications.passport as mother_passport',
            'dbo.mother_applications.multiple_owners_passport as mother_multiple_owners_passport',
            'dbo.mother_applications.applicant_title as mother_applicant_title',
            'dbo.mother_applications.first_name as mother_first_name',
            'dbo.mother_applications.surname as mother_surname',
            'dbo.mother_applications.corporate_name as mother_corporate_name',
            'dbo.mother_applications.multiple_owners_names as mother_multiple_owners_names',
            'dbo.mother_applications.land_use',
            'dbo.mother_applications.property_house_no',
            'dbo.mother_applications.property_plot_no',
            'dbo.mother_applications.property_street_name',
            'dbo.mother_applications.property_district',
            'dbo.mother_applications.property_lga',
             'dbo.mother_applications.np_fileno'
            )
            ->orderBy('dbo.subapplications.sys_date', 'desc')->get();
         

        return view('sectionaltitling.secondary', compact('SecondaryApplications', 'PageTitle', 'PageDescription')); 
    }

  public function units(Request $request)
    {
        $PageTitle = 'Parented Units';
        $PageDescription = 'Process CofO for individually owned sections of multi-unit developments.';

        $query = DB::connection('sqlsrv')->table('dbo.subapplications')
            ->leftJoin('dbo.mother_applications', 'dbo.subapplications.main_application_id', '=', 'dbo.mother_applications.id')
            ->select(
            'dbo.subapplications.fileno', 
            'dbo.subapplications.scheme_no',
            'dbo.subapplications.id',
            'dbo.subapplications.main_application_id',
            'dbo.subapplications.applicant_title',
            'dbo.subapplications.first_name',
            'dbo.subapplications.surname',
            'dbo.subapplications.corporate_name',
            'dbo.subapplications.multiple_owners_names',
            'dbo.subapplications.phone_number',
            'dbo.subapplications.planning_recommendation_status',
            'dbo.subapplications.application_status',
            'dbo.subapplications.created_at',
            'dbo.subapplications.sys_date',
            'dbo.subapplications.application_date',
            'dbo.subapplications.unit_number',
            'dbo.subapplications.main_id',
            'dbo.subapplications.passport',
            'dbo.subapplications.multiple_owners_passport',
            'dbo.mother_applications.fileno as mother_fileno',
              'mother_applications.id as mother_id',
           'dbo.mother_applications.passport as mother_passport',
            'dbo.mother_applications.multiple_owners_passport as mother_multiple_owners_passport',
            'dbo.mother_applications.applicant_title as mother_applicant_title',
            'dbo.mother_applications.first_name as mother_first_name',
            'dbo.mother_applications.surname as mother_surname',
            'dbo.mother_applications.corporate_name as mother_corporate_name',
            'dbo.mother_applications.multiple_owners_names as mother_multiple_owners_names',
            'dbo.mother_applications.land_use',
            'dbo.mother_applications.property_house_no',
            'dbo.mother_applications.property_plot_no',
            'dbo.mother_applications.property_street_name',
            'dbo.mother_applications.property_district',
            'dbo.mother_applications.property_lga' ,  
            'dbo.mother_applications.np_fileno'
             
            )
            // Only fetch records that are not soft-deleted
            ->where(function($q) {
                $q->whereNull('dbo.subapplications.is_deleted')
                  ->orWhere('dbo.subapplications.is_deleted', 0);
            });

        // Check if main_application_id parameter exists in URL
        if ($request->has('main_application_id')) {
            $mainApplicationId = $request->get('main_application_id');
            $query->where('dbo.subapplications.main_application_id', $mainApplicationId);
        }

        $SecondaryApplications = $query
            ->where(function($q) {
            $q->where('dbo.subapplications.unit_type', '!=', 'SUA')
              ->orWhereNull('dbo.subapplications.unit_type');
            })
            ->orderBy('dbo.subapplications.sys_date', 'desc')
            ->get();
         

        return view('sectionaltitling.units', compact('SecondaryApplications', 'PageTitle', 'PageDescription')); 
    }



    public function Map()
    {
        $PageTitle = 'GIS Mapping - Sectional Titling';
        $PageDescription = 'Geospatial visualization of sectional title properties in Kano State.';
  
        return view('map.index', compact('PageTitle', 'PageDescription'));
    }

    public function mother(Request $request)
    {
        if ($request->has('survey')) {
            $PageTitle = 'Sectional Titling Survey - Mother Applications';
            $PageDescription = 'Process Survey Applications for Mother Applications';
        } else {
            $PageTitle = $request->get('url') === 'phy_planning' ? 'Planning Recommendation Approval - Mother Applications' : 
                    ($request->get('url') === 'recommendation' ? 'Planning Recommendation - Mother Applications' : 'Mother Applications');
            $PageDescription = $request->get('url') === 'phy_planning' ? 'Physical planning approval for mother applications' : 
                    ($request->get('url') === 'recommendation' ? 'Review and process planning recommendation for mother applications' : 'Manage and process mother applications for sectional titling.');
        }

        $PrimaryApplications = DB::connection('sqlsrv')->table('dbo.mother_applications')
        ->orderBy('sys_date', 'desc')
            ->get();
       

        return view('sectionaltitling.mother', compact('PrimaryApplications', 'PageTitle', 'PageDescription'));
    }

    public function getBuyerList($applicationId)
    {
        try {
            // Query to get buyer list with unit measurements
            $buyers = DB::connection('sqlsrv')
                ->table('dbo.buyer_list as bl')
                ->leftJoin('dbo.st_unit_measurements as sum', function($join) {
                    $join->on('bl.application_id', '=', 'sum.application_id')
                         ->on('bl.unit_no', '=', 'sum.unit_no');
                })
                ->select(
                    'bl.application_id',
                    'bl.unit_measurement_id',
                    'bl.buyer_title',
                    'bl.buyer_name',
                    'bl.unit_no',
                    'sum.buyer_id',
                    'sum.measurement'
                )
                ->where('bl.application_id', $applicationId)
                ->get();

            return response()->json([
                'success' => true,
                'buyers' => $buyers,
                'message' => 'Buyer list retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving buyer list: ' . $e->getMessage(),
                'buyers' => []
            ]);
        }
    }

    public function getCofoDetails(Request $request)
    {
        $fileNumber = trim((string) $request->query('file_number'));

        if ($fileNumber === '') {
            return response()->json([
                'success' => false,
                'message' => 'File number is required.'
            ], 422);
        }

        $variants = $this->buildFileNumberVariants($fileNumber);

        $record = DB::connection('sqlsrv')
            ->table('CofO')
            ->select([
                'serialNo',
                'pageNo',
                'volumeNo',
                'reg_serial',
                'reg_page',
                'reg_volume',
                'regNo',
                'cofo_no',
                'transaction_date',
                'transactionDate',
                'transaction_time',
                'certificate_date',
                'certificateDate',
                'land_use',
                'cofo_type',
                'property_description',
            ])
            ->where(function ($query) use ($variants) {
                foreach (['mlsFNo', 'kangisFileNo', 'NewKANGISFileno', 'file_no', 'cofo_no'] as $column) {
                    $query->orWhereIn($column, $variants);
                }
            })
            ->orderByDesc(DB::raw('ISNULL(transaction_date, transactionDate)'))
            ->first();

        if (! $record) {
            return response()->json([
                'success' => false,
                'message' => 'No CofO record found for this file number.'
            ]);
        }

        $serial = $record->serialNo ?? $record->reg_serial ?? null;
        $page = $record->pageNo ?? $record->reg_page ?? $serial;
        $volume = $record->volumeNo ?? $record->reg_volume ?? null;

        $transactionDateRaw = $record->transaction_date ?? $record->transactionDate ?? null;
        $transactionDate = null;
        $transactionTime = null;

        if ($transactionDateRaw) {
            try {
                $parsed = Carbon::parse($transactionDateRaw);
                $transactionDate = $parsed->format('Y-m-d');
                $transactionTime = $parsed->format('H:i');
            } catch (\Throwable $e) {
                $transactionDate = $transactionDateRaw;
            }
        }

        if (! $transactionTime && ! empty($record->transaction_time)) {
            $transactionTime = substr($record->transaction_time, 0, 5);
        }

        $certificateDateRaw = $record->certificate_date ?? $record->certificateDate ?? null;
        $certificateDate = null;

        if ($certificateDateRaw) {
            try {
                $certificateDate = Carbon::parse($certificateDateRaw)->format('Y-m-d');
            } catch (\Throwable $e) {
                $certificateDate = $certificateDateRaw;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'serial_no' => $serial,
                'page_no' => $page,
                'volume_no' => $volume,
                'transaction_date' => $transactionDate,
                'transaction_time' => $transactionTime,
                'cofo_no' => $record->cofo_no,
                'certificate_date' => $certificateDate,
                'reg_no' => $record->regNo ?? ($serial && $page && $volume ? sprintf('%s/%s/%s', $serial, $page, $volume) : null),
                'land_use' => $record->land_use,
                'cofo_type' => $record->cofo_type,
                'property_description' => $record->property_description,
            ],
        ]);
    }

    protected function buildFileNumberVariants(string $fileNumber): array
    {
        $normalized = strtoupper(trim($fileNumber));
        $variants = [$normalized];

        // Remove spaces variant
        $variants[] = str_replace(' ', '', $normalized);

        // Legacy KANGIS variants e.g. CON-RES-2024-0001 vs CONRES-2024-1
        if (preg_match('/^(CO?N?-?)([A-Z]{3})-(\d{4})-(\d+)$/', $normalized, $matches)) {
            $prefix = $matches[1];
            $landUse = $matches[2];
            $year = $matches[3];
            $serial = ltrim($matches[4], '0');
            if ($serial !== '') {
                $variants[] = sprintf('%s%s-%s-%s', $prefix, $landUse, $year, $serial);
                $variants[] = sprintf('%s%s-%s-%04d', $prefix, $landUse, $year, (int) $serial);
            }
        }

        // Modern ST variants without prefix e.g. RES-2024-0001 vs RES-2024-1
        if (preg_match('/^([A-Z]{3})-(\d{4})-(\d+)$/', $normalized, $matches)) {
            $landUse = $matches[1];
            $year = $matches[2];
            $serial = ltrim($matches[3], '0');
            if ($serial !== '') {
                $variants[] = sprintf('%s-%s-%s', $landUse, $year, $serial);
                $variants[] = sprintf('%s-%s-%04d', $landUse, $year, (int) $serial);
            }
        }

        // Remove hyphen variant
        $variants[] = str_replace('-', '', $normalized);

        return array_values(array_unique(array_filter($variants)));
    }

    /**
     * Render A4 landscape Acknowledgement Slip for a primary (mother) application.
     */
    public function printAcknowledgement($id)
    {
        // Permissions: allow super admin or users with view permission; otherwise, block
        // if (Auth::user()->type != 'super admin' && !Auth::user()->can('View Application')) {
        //     return redirect()->back()->with('error', __('Permission Denied.'));
        // }

        // Fetch mother application from SQL Server
        $app = DB::connection('sqlsrv')->table('dbo.mother_applications')->where('id', $id)->first();
        if (!$app) {
            abort(404, 'Application not found');
        }

        // Map applicant name based on applicant_type
        $applicantType = $app->applicant_type ?? null;
        $applicantName = '-';
        if ($applicantType === 'individual') {
            $applicantName = trim(($app->applicant_title ?? '') . ' ' . ($app->first_name ?? '') . ' ' . ($app->middle_name ?? '') . ' ' . ($app->surname ?? ''));
        } elseif ($applicantType === 'corporate') {
            $applicantName = $app->corporate_name ?? '-';
        } elseif ($applicantType === 'multiple') {
            $applicantName = $app->multiple_owners_names ?? '-';
        }

        // Payment fields may or may not exist on mother_applications; read defensively
        $applicationFee = $app->application_fee ?? null;
        $processingFee = $app->processing_fee ?? null;
        $sitePlanFee   = $app->site_plan_fee ?? null;
        $receiptNumber = $app->receipt_number ?? null;
        $paymentDate   = $app->payment_date ?? null;

        // Enhanced payment status logic
        $paymentStatus = [
            'application_fee_paid' => !empty($applicationFee) && $applicationFee > 0,
            'processing_fee_paid' => !empty($processingFee) && $processingFee > 0,
            'site_plan_fee_paid' => !empty($sitePlanFee) && $sitePlanFee > 0,
        ];

        // Compute total if possible
        $normalize = function ($val) {
            if (is_null($val) || $val === '') return 0.0;
            // remove commas and non-numeric except dot
            $val = preg_replace('/[^0-9.]/', '', (string)$val);
            return is_numeric($val) ? (float)$val : 0.0;
        };
        $totalFee = null;
        if (!is_null($applicationFee) || !is_null($processingFee) || !is_null($sitePlanFee)) {
            $sum = $normalize($applicationFee) + $normalize($processingFee) + $normalize($sitePlanFee);
            $totalFee = number_format($sum, 2);
        }

        // Build address string
        $propertyFullAddress = trim(implode(', ', array_filter([
            $app->property_house_no ?? null,
            $app->property_plot_no ?? null,
            $app->property_street_name ?? null,
            $app->property_district ?? null,
            $app->property_lga ?? null,
            $app->property_state ?? 'Kano',
        ])));

        // Determine tracking info for watermark and display
        $track = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('application_id', $app->id)
            ->first();
        $printedCount = $track ? ((int)($track->printed_count ?? 0)) : 0;
        $watermarkText = $printedCount > 0 ? '' : '';

        // Parse documents JSON if available
        $documentsData = [];
        if (!empty($app->documents)) {
            $documentsData = json_decode($app->documents, true) ?? [];
        }

        // Define documents list with status based on actual document JSON structure
        $documentsWithStatus = [
            'Application Letter' => isset($documentsData['application_letter']) && !empty($documentsData['application_letter']['path']),
            'Building Plan' => isset($documentsData['building_plan']) && !empty($documentsData['building_plan']['path']),
            'Architectural Design' => isset($documentsData['architectural_design']) && !empty($documentsData['architectural_design']['path']),
            'Ownership Document' => isset($documentsData['ownership_document']) && !empty($documentsData['ownership_document']['path']),
            'Site Plan (Survey)' => isset($documentsData['survey_plan']) && !empty($documentsData['survey_plan']['path']),
        ];

        // Render the print view
        return view('sectionaltitling.print.acknowledgement', [
            'applicationId' => $app->id,
            'landUse' => $app->land_use ?? '-',
            'applicantType' => $applicantType ?? '-',
            'applicantName' => $applicantName,
            'applicantEmail' => $app->owner_email ?? '-',
            'applicantPhone' => $app->phone_number ?? '-',
            'applicantAddress' => ($app->address ?? null) ?: ($app->owner_address ?? '-'),

            'residenceType' => $app->residenceType ?? ($app->residence_type ?? '-'),
            // As requested: Units = NoOfUnits, Blocks = NoOfSections, Sections = NoOfBlocks
            'units' => $app->NoOfUnits ?? ($app->units_count ?? '-'),
            'blocks' => $app->NoOfSections ?? ($app->sections_count ?? '-'),
            'sections' => $app->NoOfBlocks ?? ($app->blocks_count ?? '-'),
            // Both file numbers
            'fileNumber' => $app->fileno ?? '-',
            'npFileNumber' => $app->np_fileno ?? '-',

            'propertyHouseNo' => $app->property_house_no ?? '-',
            'propertyPlotNo' => $app->property_plot_no ?? '-',
            'propertyStreet' => $app->property_street_name ?? '-',
            'propertyDistrict' => $app->property_district ?? '-',
            'propertyLGA' => $app->property_lga ?? '-',
            'propertyState' => $app->property_state ?? 'Kano',
            'propertyFullAddress' => $propertyFullAddress,

            'applicationFee' => $applicationFee,
            'processingFee' => $processingFee,
            'sitePlanFee' => $sitePlanFee,
            'totalFee' => $totalFee,
            'receiptNumber' => $receiptNumber,
            'paymentDate' => $paymentDate,

            // Payment and document status
            'paymentStatus' => $paymentStatus,
            'documentsWithStatus' => $documentsWithStatus,
            
            // Tracking for watermark and UI
            'printedCount' => $printedCount,
            'watermarkText' => $watermarkText,
        ]);
    }

    /**
     * Generate/Create the Acknowledgement tracking record.
     */
    public function generateAcknowledgement($id)
    {
       

        $app = DB::connection('sqlsrv')->table('dbo.mother_applications')->where('id', $id)->first();
        if (!$app) {
            return response()->json(['success' => false, 'message' => 'Application not found'], 404);
        }

        // Upsert-like behavior: create if not exists
        $exists = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('application_id', $id)
            ->exists();

        if (!$exists) {
            DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')->insert([
                'application_id' => $id,
                'generated_at' => now(),
                'generated_by_user_id' => Auth::id(),
                'generated_by_user_name' => optional(Auth::user())->name,
                'printed_count' => 0,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Acknowledgement generated']);
    }

    /**
     * Mark an acknowledgement as printed (increment count and timestamps).
     */
    public function markAcknowledgementPrinted($id)
    {
        $track = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('application_id', $id)
            ->first();

        if (!$track) {
            // Auto-create tracking if missing
            DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')->insert([
                'application_id' => $id,
                'generated_at' => now(),
                'generated_by_user_id' => Auth::id(),
                'generated_by_user_name' => optional(Auth::user())->name,
                'printed_count' => 0,
            ]);
            $track = (object) ['printed_count' => 0];
        }

        $now = now();
        $firstPrintedAt = $track->printed_count > 0 ? $track->first_printed_at : $now;

        DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')
            ->where('application_id', $id)
            ->update([
                'printed_count' => DB::raw('ISNULL(printed_count,0) + 1'),
                'first_printed_at' => $firstPrintedAt,
                'last_printed_at' => $now,
                'last_printed_by_user_id' => Auth::id(),
                'last_printed_by_user_name' => optional(Auth::user())->name,
            ]);

        return response()->json(['success' => true]);
    }

    /**
     * Generate/Create the Sub-application Acknowledgement tracking record.
     */
    public function generateSubAcknowledgement($id)
    {
        // Sub-application exists?
        $sub = DB::connection('sqlsrv')->table('dbo.subapplications')->where('id', $id)->first();
        if (!$sub) {
            return response()->json(['success' => false, 'message' => 'Sub-application not found'], 404);
        }

        $exists = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('sub_application_id', $id)
            ->exists();

        if (!$exists) {
            DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')->insert([
                'application_id' => $sub->main_application_id ?? null,
                'sub_application_id' => $id,
                'generated_at' => now(),
                'generated_by_user_id' => Auth::id(),
                'generated_by_user_name' => optional(Auth::user())->name,
                'printed_count' => 0,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Sub Acknowledgement generated']);
    }

    /**
     * Render Acknowledgement Slip for a sub-application (Unit or SUA) with watermark.
     */
    public function printSubAcknowledgement($id)
    {
        $sub = DB::connection('sqlsrv')->table('dbo.subapplications')->where('id', $id)->first();
        if (!$sub) abort(404, 'Sub-application not found');

        // Resolve mother application using either main_application_id or legacy main_id
        $mother = null;
        $motherId = null;
        if (!empty($sub->main_application_id)) {
            $motherId = $sub->main_application_id;
        } elseif (!empty($sub->main_id)) {
            $motherId = $sub->main_id;
        }
        if (!empty($motherId)) {
            $mother = DB::connection('sqlsrv')->table('dbo.mother_applications')->where('id', $motherId)->first();
        }

        // Applicant name logic (sub-level)
        $applicantType = $sub->applicant_type ?? '-';
        $applicantName = '-';
        if ($applicantType === 'individual') {
            $applicantName = trim(($sub->applicant_title ?? '') . ' ' . ($sub->first_name ?? '') . ' ' . ($sub->middle_name ?? '') . ' ' . ($sub->surname ?? ''));
        } elseif ($applicantType === 'corporate') {
            $applicantName = $sub->corporate_name ?? '-';
        } elseif ($applicantType === 'multiple') {
            $applicantName = $sub->multiple_owners_names ?? '-';
        }

        // Address from sub (fallback to mother if missing)
        $propertyFullAddress = trim(implode(', ', array_filter([
            $sub->property_house_no ?? ($mother->property_house_no ?? null),
            $sub->property_plot_no ?? ($mother->property_plot_no ?? null),
            $sub->property_street_name ?? ($mother->property_street_name ?? null),
            $sub->property_district ?? ($mother->property_district ?? null),
            $sub->property_lga ?? ($mother->property_lga ?? null),
            $sub->property_state ?? ($mother->property_state ?? 'Kano'),
        ])));

        // Tracking/watermark
        $track = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('sub_application_id', $id)
            ->first();
        $printedCount = $track ? ((int)($track->printed_count ?? 0)) : 0;
        $watermarkText = $printedCount > 0 ? 'COPY OF ORIGINAL' : 'ORIGINAL';

        // Units vs SUA identification
        $isSUA = isset($sub->unit_type) && strtoupper($sub->unit_type) === 'SUA';

        // Enhanced payment status logic for sub-applications
        $subPaymentStatus = [
            'application_fee_paid' => !empty($sub->application_fee) && $sub->application_fee > 0,
            'processing_fee_paid' => !empty($sub->processing_fee) && $sub->processing_fee > 0,
            'site_plan_fee_paid' => !empty($sub->site_plan_fee) && $sub->site_plan_fee > 0,
        ];

        // Parse documents JSON from sub-application or fall back to mother application
        $subDocumentsData = [];
        if (!empty($sub->documents)) {
            $subDocumentsData = json_decode($sub->documents, true) ?? [];
        } elseif (!empty($mother->documents ?? null)) {
            $subDocumentsData = json_decode($mother->documents, true) ?? [];
        }

        // Define documents list for sub-applications with status - using same structure as mother application
        $subDocumentsWithStatus = [
            'Application Letter *' => isset($subDocumentsData['application_letter']) && !empty($subDocumentsData['application_letter']['path']),
            'Building Plan *' => isset($subDocumentsData['building_plan']) && !empty($subDocumentsData['building_plan']['path']),
            'Architectural Design' => isset($subDocumentsData['architectural_design']) && !empty($subDocumentsData['architectural_design']['path']),
            'Ownership Document' => isset($subDocumentsData['ownership_document']) && !empty($subDocumentsData['ownership_document']['path']),
            'Site Plan (Survey)' => isset($subDocumentsData['survey_plan']) && !empty($subDocumentsData['survey_plan']['path']),
        ];

    return view('sectionaltitling.print.acknowledgement_sub', [
            // IDs, type, and labels
            'applicationId' => $sub->id,
            'applicantType' => $applicantType,
            'applicantName' => $applicantName,
            'applicantEmail' => $sub->owner_email ?? ($mother->owner_email ?? '-'),
            'applicantPhone' => $sub->phone_number ?? ($mother->phone_number ?? '-'),
            'applicantAddress' => ($sub->address ?? null) ?: ($mother->address ?? ($mother->owner_address ?? '-')),

            'residenceType' => $sub->residenceType ?? ($sub->residence_type ?? ($mother->residenceType ?? ($mother->residence_type ?? '-'))),
            // Sub specifics (Unit Detail mapping per request)
            'unitType' => $sub->unit_type ?? 'Parented Unit',
            'unitNumber' => $sub->unit_number ?? ($sub->unit_no ?? '-'),
            'blockNumber' => $sub->block_number ?? ($sub->block_no ?? '-'),
            'unitSize' => $sub->unit_size ?? '-',
            'unitFileNumber' => $sub->fileno ?? '-',
            // Prefer subapplications.np_fileno if present, otherwise fallback to mother_applications.np_fileno
            'npFileNumber' => ($sub->np_fileno ?? null) ?: ($mother->np_fileno ?? '-'),

            // Property Address mapping (Unit/SUA fields)
            'unitLGA' => $sub->unit_lga ?? ($sub->property_lga ?? ($mother->property_lga ?? '-')),
            'unitDistrict' => $sub->unit_district ?? ($sub->property_district ?? ($mother->property_district ?? '-')),
            'propertyLocation' => $sub->property_location ?? ($mother->property_location ?? $propertyFullAddress ?? '-'),

            'applicationFee' => $sub->application_fee ?? null,
            'processingFee' => $sub->processing_fee ?? null,
            'sitePlanFee' => $sub->site_plan_fee ?? null,
            'totalFee' => null, // optional compute if needed later
            'receiptNumber' => $sub->receipt_number ?? null,
            'paymentDate' => $sub->payment_date ?? null,
            
            // Enhanced status for sub-applications
            'paymentStatus' => $subPaymentStatus,
            'documentsWithStatus' => $subDocumentsWithStatus,
            
            'printedCount' => $printedCount,
            'watermarkText' => $watermarkText,
        ]);
    }

    /**
     * Mark sub-application acknowledgement as printed.
     */
    public function markSubAcknowledgementPrinted($id)
    {
        $track = DB::connection('sqlsrv')
            ->table('dbo.st_acknowledgement_tracking')
            ->where('sub_application_id', $id)
            ->first();

        if (!$track) {
            // Best-effort create if missing
            $sub = DB::connection('sqlsrv')->table('dbo.subapplications')->where('id', $id)->first();
            DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')->insert([
                'application_id' => $sub->main_application_id ?? null,
                'sub_application_id' => $id,
                'generated_at' => now(),
                'generated_by_user_id' => Auth::id(),
                'generated_by_user_name' => optional(Auth::user())->name,
                'printed_count' => 0,
            ]);
            $track = (object) ['printed_count' => 0];
        }

        $now = now();
        $firstPrintedAt = $track->printed_count > 0 ? $track->first_printed_at : $now;

        DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')
            ->where('sub_application_id', $id)
            ->update([
                'printed_count' => DB::raw('ISNULL(printed_count,0) + 1'),
                'first_printed_at' => $firstPrintedAt,
                'last_printed_at' => $now,
                'last_printed_by_user_id' => Auth::id(),
                'last_printed_by_user_name' => optional(Auth::user())->name,
            ]);

        return response()->json(['success' => true]);
    }

    public function saveCofoDetails(Request $request)
    {
        try {
            // Log the incoming request for debugging
            \Log::info('CofO Details Save Request', [
                'method' => $request->method(),
                'url' => $request->url(),
                'data' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            // Validate the request
            $validatedData = $request->validate([
                'application_id' => 'required|integer',
                'transaction_type' => 'required|string',
                'cofo_no' => 'nullable|string',
                'certificate_date' => 'nullable|string',
                'serial_no' =>'nullable|string',
                'page_no' => 'nullable|string',
                'volume_no' => 'nullable|string',
                'transaction_date' => 'nullable|string',
                'transaction_time' => 'nullable|string',
                'land_use' => 'nullable|string',
                'period' => 'nullable|integer',
                'period_unit' => 'nullable|string|in:years,months,weeks',
                'grantor' => 'required|string',
                'grantee' => 'required|string',
                'property_description' => 'nullable|string',
                'reg_no' => 'nullable|string'
            ]);

            \Log::info('CofO Details Validation Passed', ['validated_data' => $validatedData]);

            // Get the mother application data
            $motherApplication = DB::connection('sqlsrv')
                ->table('dbo.mother_applications')
                ->where('id', $request->application_id)
                ->first();

            if (!$motherApplication) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ]);
            }

            // Check for duplicates based on application ID
            $existingRecord = DB::connection('sqlsrv')
                ->table('dbo.CofO')
                ->where('mlsFNo', $motherApplication->fileno)
                ->first();

            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'CofO details already exist for this application'
                ]);
            }

            // Prepare data for insertion
            $cofoData = [
                'mlsFNo' => $motherApplication->fileno,
                'kangisFileNo' => $motherApplication->fileno, // Using fileno as kangisFileNo
                'NewKANGISFileno' => $motherApplication->np_fileno ?? $motherApplication->fileno,
                'title_type' => 'Certificate of Occupancy', // Default title type
                'transaction_type' => $request->transaction_type,
                'cofo_no' => $request->cofo_no,
                'transaction_date' => $request->transaction_date,
                'transaction_time' => $request->transaction_time,
                'serialNo' => $request->serial_no,
                'pageNo' => $request->page_no,
                'volumeNo' => $request->volume_no,
                'regNo' => $request->reg_no, // This comes from the preview field
                'instrument_type' => 'Certificate of Occupancy', // Fixed as Certificate of Occupancy
                'period' => $request->period,
                'period_unit' => $request->period_unit,
                'land_use' => $request->land_use ?: $motherApplication->land_use, // Use form data or fallback to mother application
                'Assignor' => null, // Will be set based on transaction type
                'Assignee' => null,
                'Mortgagor' => null,
                'Mortgagee' => null,
                'Surrenderor' => null,
                'Surrenderee' => null,
                'Lessor' => null,
                'Lessee' => null,
                'Grantor' => $request->grantor,
                'Grantee' => $request->grantee,
                'property_description' => $request->property_description ?: (isset($motherApplication->property_description) ? $motherApplication->property_description : ''),
                'location' => '', // Removed location field
                'plot_no' => $motherApplication->property_house_no ?: $motherApplication->property_plot_no,
                'lgsaOrCity' => $motherApplication->property_lga,
                'layout' => '', // Removed layout field
                'schedule' => '', // Removed schedule field
                'application_id' => $request->application_id,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Set transaction-specific parties based on transaction type
            switch (strtolower($request->transaction_type)) {
                case 'assignment':
                    $cofoData['Assignor'] = $request->grantor;
                    $cofoData['Assignee'] = $request->grantee;
                    break;
                case 'mortgage':
                    $cofoData['Mortgagor'] = $request->grantee; // The one giving the mortgage
                    $cofoData['Mortgagee'] = $request->grantor; // The one receiving the mortgage
                    break;
                case 'lease':
                    $cofoData['Lessor'] = $request->grantor;
                    $cofoData['Lessee'] = $request->grantee;
                    break;
                case 'surrender':
                    $cofoData['Surrenderor'] = $request->grantee;
                    $cofoData['Surrenderee'] = $request->grantor;
                    break;
                case 'certificate of occupancy':
                    // For Certificate of Occupancy, use Grantor and Grantee
                    $cofoData['Grantor'] = $request->grantor;
                    $cofoData['Grantee'] = $request->grantee;
                    break;
            }

            // Log the data being inserted
            \Log::info('CofO Data to be inserted', ['cofo_data' => $cofoData]);

            // Insert into CofO table
            $insertResult = DB::connection('sqlsrv')->table('dbo.CofO')->insert($cofoData);
            
            \Log::info('CofO Insert Result', ['result' => $insertResult]);

            return response()->json([
                'success' => true,
                'message' => 'CofO details saved successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('CofO Validation Error', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('CofO Save Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error saving CofO details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteSubapplication(Request $request, $id)
    {
        try {
            // Soft delete: mark the sub-application as deleted
            $affected = DB::connection('sqlsrv')
                ->table('dbo.subapplications')
                ->where('id', $id)
                ->update([
                    'is_deleted' => 1,
                    'updated_at' => now(),
                ]);

            if ($affected === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sub-application not found or already deleted.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sub-application deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting sub-application: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function conveyance(Request $request)
    {
        $PageTitle = 'Final Conveyance';
        $PageDescription = 'Generate Final Conveyance for Sectional Titling Applications';
        
        $finalConveyanceLatest = DB::connection('sqlsrv')->table('dbo.final_conveyance')
            ->select('application_id', DB::raw('MAX(id) as latest_id'))
            ->groupBy('application_id');

        $PrimaryApplications = DB::connection('sqlsrv')->table('dbo.mother_applications as ma')
            ->leftJoin('dbo.memos as m', function($join) {
                $join->on('ma.id', '=', 'm.application_id')
                     ->where('m.memo_type', '=', 'primary');
            })
            ->leftJoinSub($finalConveyanceLatest, 'fc_latest', function($join) {
                $join->on('ma.id', '=', 'fc_latest.application_id');
            })
            ->leftJoin('dbo.final_conveyance as fc', function($join) {
                $join->on('fc.id', '=', 'fc_latest.latest_id');
            })
            ->select('ma.*', 
                DB::raw('CASE WHEN m.application_id IS NOT NULL THEN \'Generated\' ELSE \'Not Generated\' END as st_memo_status'),
                'm.planner_recommendation as st_memo_comments',
                'fc.id as final_conveyance_id',
                'fc.status as final_conveyance_status',
                'fc.generated_date as fc_generated_date'
            )
            ->orderBy('ma.id', 'desc')
            ->get();
        
        return view('sectionaltitling.conveyance', compact('PrimaryApplications', 'PageTitle', 'PageDescription'));
    }
  
}


// sort all  record by the latest created record first

