<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\SectionalTitleHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Exception;

class PrimaryApplicationController extends Controller
{
    /**
     * Display the primary application form
     */
    public function index(Request $request)
    {
        // Simple controller - no file number generation logic
        // Values will come from ST API through JavaScript
        
        return view('primaryform.index', [
            'PageTitle' => 'Primary Application Form',
            'PageDescription' => 'Submit a new sectional title application',
            'landUse' => $request->query('landuse', 'COMMERCIAL'),
            'currentYear' => date('Y'),
            'serialNo' => '',
            'npFileNo' => '',
            'draftMeta' => [
                'last_saved_at' => null,
                'progress_percent' => 0,
                'collaborators' => [],
                'mode' => 'fresh',
                'drafts' => []
            ]
        ]);
    }

    /**
     * Store a new primary application
     */
    public function store(Request $request)
    {
        try {
            $this->hydrateLegacyFields($request);

            // Debug: Log ALL incoming request data
            Log::info('=== PRIMARY APPLICATION FORM DEBUG ===');
            Log::info('All Request Data:', $request->all());
            Log::info('Files:', array_keys($request->allFiles()));
            Log::info('Form Field Count: ' . count($request->all()));
            Log::info('Buyer payload snapshot:', [
                'records_count' => is_array($request->input('records')) ? count($request->input('records')) : 0,
                'records' => $request->input('records', []),
                'buyers_json_present' => $request->filled('buyers_json')
            ]);
            
            // Debug: Check specific expected fields
            $expectedFields = [
                'np_fileno', 'fileno', 'land_use', 'applicant_type', 'title', 'fname', 'lname',
                'email', 'phone', 'address', 'address_house_no', 'owner_street_name', 'owner_lga', 'owner_state',
                'scheme_no', 'property_house_no', 'property_plot_no', 'property_street_name', 'property_district',
                'property_lga', 'property_state', 'units_count', 'blocks_count', 'sections_count'
            ];
            foreach ($expectedFields as $field) {
                Log::info("Field {$field}:", ['value' => $request->input($field, 'NOT PROVIDED')]);
            }
            Log::info('=== END DEBUG ===');

            // Validate required fields
            $validated = $request->validate([
                // API-provided identifiers (required)
                'np_fileno' => 'required|string|max:50',
                'fileno' => 'required|string|max:1000',
                'land_use' => 'required|string|max:1000',
                'applicant_type' => 'required|string|in:individual,corporate,multiple,Individual,Corporate,Multiple',
                
                // Applicant information (conditional based on type)
                'applicant_title' => 'nullable|string|max:1000',
                'first_name' => 'nullable|string|max:1000',
                'middle_name' => 'nullable|string|max:1000',
                'surname' => 'nullable|string|max:1000',
                'corporate_name' => 'nullable|string|max:1000',
                'rc_number' => 'nullable|string|max:1000',
                'multiple_owners_names' => 'nullable|array',
                'multiple_owners_address' => 'nullable|array',
                'multiple_owners_email' => 'nullable|array',
                'multiple_owners_phone' => 'nullable|array',
                'multiple_owners_identification_type' => 'nullable|array',
                
                // Contact information
                'address_house_no' => 'nullable|string|max:1000',
                'owner_street_name' => 'nullable|string|max:1000',
                'owner_district' => 'nullable|string|max:1000',
                'owner_lga' => 'nullable|string|max:1000',
                'owner_state' => 'nullable|string|max:1000',
                'phone_number' => 'nullable|array',
                'phone' => 'nullable|string|max:255',
                'phone_alternate' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:1000',
                
                // Property information
                'scheme_no' => 'nullable|string|max:1000',
                'property_house_no' => 'nullable|string|max:1000',
                'property_plot_no' => 'nullable|string|max:1000',
                'property_street_name' => 'nullable|string|max:1000',
                'property_district' => 'nullable|string|max:1000',
                'property_lga' => 'nullable|string|max:1000',
                'property_state' => 'nullable|string|max:1000',
                'plot_size' => 'nullable|string|max:1000',
                
                // Property details
                'units_count' => 'nullable|integer',
                'blocks_count' => 'nullable|integer',
                'sections_count' => 'nullable|integer',
                'residential_type' => 'nullable|string|max:1000',
                'commercial_type' => 'nullable|string|max:1000',
                'industrial_type' => 'nullable|string|max:1000',
                'ownership_type' => 'nullable|string|max:1000',
                'ownership_type_others_text' => 'nullable|string|max:1000',
                
                // Payment information
                'application_fee' => 'nullable|string|max:1000',
                'processing_fee' => 'nullable|string|max:1000',
                'site_plan_fee' => 'nullable|string|max:1000',
                
                // Individual payment tracking
                'application_fee_payment_date' => 'nullable|date',
                'application_fee_receipt_number' => 'nullable|string|max:100',
                'processing_fee_payment_date' => 'nullable|date',
                'processing_fee_receipt_number' => 'nullable|string|max:100',
                'site_plan_fee_payment_date' => 'nullable|date',
                'site_plan_fee_receipt_number' => 'nullable|string|max:100',
                
                // Legacy payment fields (for backward compatibility)
                'payment_date' => 'nullable|string|max:1000',
                'receipt_number' => 'nullable|string|max:1000',
                
                // Additional fields
                'comments' => 'nullable|string|max:1000',
                'shared_areas' => 'nullable|array',
                'documents' => 'nullable|array',
                'application_date' => 'nullable|date',
                
                // Files
                'passport' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'id_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'rc_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'multiple_owners_identification_image' => 'nullable|array',
                'multiple_owners_identification_image.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                
                // Document uploads (accompanying documents)
                'application_letter' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'building_plan' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'architectural_design' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'ownership_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'survey_plan' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                
                // ST API metadata
                'selected_file_data' => 'nullable|string',
                'selected_file_id' => 'nullable|string|max:255',
                'selected_file_type' => 'nullable|string|max:255',
                'applied_file_number' => 'nullable|string|max:255',
                'tracking_id' => 'nullable|string',
                'primary_file_id' => 'nullable|string',

                // Buyer records
                'records' => 'nullable|array',
                'records.*.buyerTitle' => 'nullable|string|max:255',
                'records.*.firstName' => 'nullable|string|max:255',
                'records.*.middleName' => 'nullable|string|max:255',
                'records.*.surname' => 'nullable|string|max:255',
                'records.*.buyerName' => 'nullable|string|max:1000',
                'records.*.unit_no' => 'nullable|string|max:255',
                'records.*.unitNumber' => 'nullable|string|max:255',
                'records.*.unitMeasurement' => 'nullable|string|max:255',
                'records.*.sectionNumber' => 'nullable|string|max:255',
                'records.*.section_number' => 'nullable|string|max:255',
                'records.*.landUse' => 'nullable|string|max:255',
                'buyers_json' => 'nullable|string'
            ]);

            // Handle file uploads - handle passport, id_document, and rc_document separately
            $passportPath = null;
            $idDocumentPath = null;
            $rcDocumentPath = null;
            
            if ($request->hasFile('passport')) {
                $passportPath = $request->file('passport')->store('passports', 'public');
                Log::info('Passport file uploaded:', [
                    'path' => $passportPath,
                    'original_name' => $request->file('passport')->getClientOriginalName(),
                    'size' => $request->file('passport')->getSize()
                ]);
            }
            
            if ($request->hasFile('id_document')) {
                $idDocumentPath = $request->file('id_document')->store('id_documents', 'public');
                Log::info('ID document file uploaded:', [
                    'path' => $idDocumentPath,
                    'original_name' => $request->file('id_document')->getClientOriginalName(),
                    'size' => $request->file('id_document')->getSize()
                ]);
            }
            
            if ($request->hasFile('rc_document')) {
                $rcDocumentPath = $request->file('rc_document')->store('rc_documents', 'public');
                Log::info('RC document file uploaded:', [
                    'path' => $rcDocumentPath,
                    'original_name' => $request->file('rc_document')->getClientOriginalName(),
                    'size' => $request->file('rc_document')->getSize()
                ]);
            }

            $multipleOwnersIdImages = [];
            if ($request->hasFile('multiple_owners_identification_image')) {
                foreach ($request->file('multiple_owners_identification_image') as $file) {
                    if ($file && $file->isValid()) {
                        $multipleOwnersIdImages[] = $file->store('multiple_owners_ids', 'public');
                    }
                }
            }

            // Handle document uploads (accompanying documents)
            $documentPayload = [];
            $edmsDocuments = [];
            $documentTypes = ['application_letter', 'building_plan', 'architectural_design', 'ownership_document', 'survey_plan'];
            
            foreach ($documentTypes as $docType) {
                if ($request->hasFile($docType)) {
                    $file = $request->file($docType);
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $extension = $file->getClientOriginalExtension();
                        $path = $file->store('documents', 'public');
                        
                        // Add detailed info about each document
                        $documentMeta = [
                            'path' => $path,
                            'original_name' => $originalName,
                            'type' => $extension,
                            'uploaded_at' => now()->toDateTimeString()
                        ];
                        $documentPayload[$docType] = $documentMeta;
                        
                        // Add to EDMS documents array for processing
                        $edmsDocuments[] = array_merge($documentMeta, [
                            'doc_type' => $docType,
                            'source' => 'accompanying'
                        ]);
                        
                        Log::info('Document uploaded', [
                            'docType' => $docType,
                            'path' => $path,
                            'original_name' => $originalName
                        ]);
                    }
                }
            }

            // Normalize applicant_type to lowercase for consistency
            $applicantType = strtolower($validated['applicant_type']);
            
            // Prepare data for database insertion - exact column mapping
            $applicationData = [
                // Primary identifiers (from ST API)
                'np_fileno' => $validated['np_fileno'],
                'fileno' => $validated['fileno'],
                'land_use' => $validated['land_use'],
                'applicant_type' => $applicantType,
                
                // Applicant details
                'applicant_title' => $validated['applicant_title'] ?? null,
                'first_name' => $validated['first_name'] ?? null,
                'middle_name' => $validated['middle_name'] ?? null,
                'surname' => $validated['surname'] ?? null,
                'corporate_name' => $validated['corporate_name'] ?? null,
                'rc_number' => $validated['rc_number'] ?? null,
                
                // Multiple owners (JSON serialize arrays)
                'multiple_owners_names' => isset($validated['multiple_owners_names']) 
                    ? json_encode($validated['multiple_owners_names']) : null,
                'multiple_owners_address' => isset($validated['multiple_owners_address']) 
                    ? json_encode($validated['multiple_owners_address']) : null,
                'multiple_owners_email' => isset($validated['multiple_owners_email']) 
                    ? json_encode($validated['multiple_owners_email']) : null,
                'multiple_owners_phone' => isset($validated['multiple_owners_phone']) 
                    ? json_encode($validated['multiple_owners_phone']) : null,
                'multiple_owners_identification_type' => isset($validated['multiple_owners_identification_type']) 
                    ? json_encode($validated['multiple_owners_identification_type']) : null,
                'multiple_owners_identification_image' => !empty($multipleOwnersIdImages) 
                    ? json_encode($multipleOwnersIdImages) : null,
                
                // Contact information
                'address_house_no' => $validated['address_house_no'] ?? null,
                'address_street_name' => $validated['owner_street_name'] ?? null,
                'address_district' => $validated['owner_district'] ?? null,
                'address_lga' => $validated['owner_lga'] ?? null,
                'address_state' => $validated['owner_state'] ?? null,
                
                // Consolidated address field (for backward compatibility)
                'address' => $request->input('contact_address') ?? $request->input('address') ?? 
                    implode(', ', array_filter([
                        $validated['address_house_no'] ?? null,
                        $validated['owner_street_name'] ?? null,
                        $validated['owner_district'] ?? null,
                        $validated['owner_lga'] ?? null,
                        $validated['owner_state'] ?? null
                    ])),
                
                'phone_number' => $validated['phone'] ?? $validated['phone_alternate'] ?? (isset($validated['phone_number']) 
                    ? (is_array($validated['phone_number']) ? implode(', ', $validated['phone_number']) : $validated['phone_number'])
                    : null),
                'email' => $validated['email'] ?? null,
                
                // Property information
                'scheme_no' => $validated['scheme_no'] ?? null,
                'property_house_no' => $validated['property_house_no'] ?? null,
                'property_plot_no' => $validated['property_plot_no'] ?? null,
                'property_street_name' => $validated['property_street_name'] ?? null,
                'property_district' => $validated['property_district'] ?? null,
                'property_lga' => $validated['property_lga'] ?? null,
                'property_state' => $validated['property_state'] ?? null,
                'plot_size' => $validated['plot_size'] ?? null,
                
                // Property details
                'NoOfUnits' => $validated['units_count'] ?? null,
                'NoOfBlocks' => $validated['blocks_count'] ?? null,
                'NoOfSections' => $validated['sections_count'] ?? null,
                'residential_type' => $validated['residential_type'] ?? null,
                'commercial_type' => $validated['commercial_type'] ?? null,
                'industrial_type' => $validated['industrial_type'] ?? null,
                'ownership_type' => $validated['ownership_type'] ?? null,
                'ownership_type_others_text' => $validated['ownership_type_others_text'] ?? null,
                
                // Payment information
                'application_fee' => $validated['application_fee'] ?? null,
                'processing_fee' => $validated['processing_fee'] ?? null,
                'site_plan_fee' => $validated['site_plan_fee'] ?? null,
                
                // Individual payment tracking
                'application_fee_payment_date' => $request->input('application_fee_payment_date') ?? null,
                'application_fee_receipt_number' => $request->input('application_fee_receipt_number') ?? null,
                'processing_fee_payment_date' => $request->input('processing_fee_payment_date') ?? null,
                'processing_fee_receipt_number' => $request->input('processing_fee_receipt_number') ?? null,
                'site_plan_fee_payment_date' => $request->input('site_plan_fee_payment_date') ?? null,
                'site_plan_fee_receipt_number' => $request->input('site_plan_fee_receipt_number') ?? null,
                
                // Legacy payment fields (for backward compatibility)
                'payment_date' => $request->input('application_fee_payment_date') ?? $validated['payment_date'] ?? null,
                'receipt_number' => $request->input('application_fee_receipt_number') ?? $validated['receipt_number'] ?? null,
                'Payment_Status' => 'Pending', // Default status
                
                // Owner full name (consolidated from applicant data)
                'owner_fullname' => $this->generateOwnerFullName($validated, $applicantType),
                
                // Additional information
                'comments' => $validated['comments'] ?? null,
                'shared_areas' => isset($validated['shared_areas']) 
                    ? json_encode($validated['shared_areas']) : null,
                'documents' => !empty($documentPayload) 
                    ? json_encode($documentPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'application_date' => $validated['application_date'] ?? now()->format('Y-m-d'),
                
                // Survey plan path (separate column for quick access)
                'survey_plan' => isset($documentPayload['survey_plan']) ? $documentPayload['survey_plan']['path'] : null,
                
                // File uploads
                'id_document' => $idDocumentPath,
                'passport' => $passportPath,
                'rc_document' => $rcDocumentPath,
                
                // Identification and ownership
                'identification_type' => $request->input('identification_type') ?? null,
                'ownership_type' => $request->input('ownership_type') ?? null,
                
                // ST API metadata
                'selected_file_data' => $validated['selected_file_data'] ?? null,
                'selected_file_id' => $validated['selected_file_id'] ?? null,
                'selected_file_type' => $validated['selected_file_type'] ?? null,
                'applied_file_number' => $validated['applied_file_number'] ?? null,
                'tracking_id' => $validated['tracking_id'] ?? null,
                'primary_file_id' => $validated['primary_file_id'] ?? null,
                
                // System fields
                'application_status' => 'Pendin',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
                'sys_date' => now()
            ];

            // Debug log file upload values before database insert
            Log::info('File upload values before database insert:', [
                'passport_path' => $passportPath,
                'id_document_path' => $idDocumentPath,
                'rc_document_path' => $rcDocumentPath,
                'passport_has_file' => $request->hasFile('passport'),
                'id_document_has_file' => $request->hasFile('id_document'),
                'rc_document_has_file' => $request->hasFile('rc_document'),
                'documents_uploaded' => array_keys($documentPayload),
                'document_count' => count($documentPayload)
            ]);

            // Insert into database
            $applicationId = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->insertGetId($applicationData);

            // Update file number status to USED to prevent duplicate usage
            try {
                $fileNumberUpdated = DB::connection('sqlsrv')
                    ->table('st_file_numbers')
                    ->where('np_fileno', $validated['np_fileno'])
                    ->where('fileno', $validated['fileno'])
                    ->update([
                        'status' => 'USED',
                        'used_at' => now(),
                        'mother_application_id' => $applicationId
                    ]);
                
                if ($fileNumberUpdated > 0) {
                    Log::info('File number status updated to USED', [
                        'np_fileno' => $validated['np_fileno'],
                        'fileno' => $validated['fileno'],
                        'application_id' => $applicationId
                    ]);
                } else {
                    Log::warning('File number not found for status update', [
                        'np_fileno' => $validated['np_fileno'],
                        'fileno' => $validated['fileno'],
                        'application_id' => $applicationId
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to update file number status to USED', [
                    'np_fileno' => $validated['np_fileno'],
                    'fileno' => $validated['fileno'],
                    'application_id' => $applicationId,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the entire submission for this - just log the error
            }

            // Persist buyer list records when provided
            try {
                $buyerRecords = $this->prepareBuyerRecords($request);
                if (!empty($buyerRecords)) {
                    SectionalTitleHelper::insertBuyers($applicationId, $buyerRecords);
                    Log::info('Buyer records processed for application', [
                        'application_id' => $applicationId,
                        'buyer_count' => count($buyerRecords)
                    ]);
                } else {
                    Log::info('No buyer records supplied for application', ['application_id' => $applicationId]);
                }
            } catch (Throwable $buyerException) {
                Log::error('Failed to insert buyer records for primary application', [
                    'application_id' => $applicationId,
                    'error' => $buyerException->getMessage()
                ]);
            }

            // Create file indexing entry
            try {
                $fileIndexingData = [
                    'main_application_id' => $applicationId,
                    'st_fillno' => $validated['np_fileno'],
                    'file_number' => $validated['fileno'],
                    'file_title' => $validated['corporate_name'] ?? $validated['first_name'] . ' ' . $validated['surname'],
                    'land_use_type' => $validated['land_use'],
                    'tracking_id' => $validated['tracking_id'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id()
                ];

                $fileIndexingId = DB::connection('sqlsrv')
                    ->table('file_indexings')
                    ->insertGetId($fileIndexingData);

                Log::info('File indexing created successfully', [
                    'file_indexing_id' => $fileIndexingId,
                    'application_id' => $applicationId
                ]);
                
                // Process uploaded documents for EDMS workflow
                if ($fileIndexingId && !empty($edmsDocuments)) {
                    Log::info('Starting EDMS document processing', [
                        'file_indexing_id' => $fileIndexingId,
                        'file_number' => $validated['np_fileno'], // Using NP file number for EDMS
                        'document_count' => count($edmsDocuments)
                    ]);
                    
                    $this->processDocumentsForEDMS($fileIndexingId, $edmsDocuments, $validated['np_fileno']);
                    
                    Log::info('EDMS document processing completed', [
                        'file_indexing_id' => $fileIndexingId,
                        'application_id' => $applicationId
                    ]);
                } else {
                    Log::warning('EDMS document processing skipped', [
                        'application_id' => $applicationId,
                        'file_indexing_created' => $fileIndexingId ? true : false,
                        'edms_documents_count' => count($edmsDocuments),
                        'reason' => !$fileIndexingId ? 'No file indexing created' : 'No EDMS documents'
                    ]);
                }
                
            } catch (Throwable $indexingException) {
                Log::error('Failed to create file indexing for primary application', [
                    'application_id' => $applicationId,
                    'error' => $indexingException->getMessage()
                ]);
            }

            Log::info('Primary application created successfully', [
                'application_id' => $applicationId,
                'np_fileno' => $validated['np_fileno'],
                'fileno' => $validated['fileno'],
                'applicant_type' => $validated['applicant_type']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Primary application submitted successfully.',
                'redirect_url' => '/blind-scanning?url=st_edms&app_id=' . $applicationId,
                'data' => [
                    'application_id' => $applicationId,
                    'np_fileno' => $validated['np_fileno'],
                    'fileno' => $validated['fileno'],
                    'applicant_info' => [
                        'title' => $validated['title'] ?? '',
                        'fname' => $validated['fname'] ?? '',
                        'lname' => $validated['lname'] ?? '',
                        'applicant_type' => $validated['applicant_type'] ?? '',
                        'land_use' => $validated['land_use'] ?? ''
                    ]
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for primary application', [
                'errors' => $e->errors(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error creating primary application', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while submitting the application. Please try again.'
            ], 500);
        }
    }

    /**
     * Normalize legacy field names and ensure critical ST API data is present.
     */
    protected function hydrateLegacyFields(Request $request): void
    {
        $selectedMeta = null;
        $selectedRaw = $request->input('selected_file_data');

        if (!empty($selectedRaw)) {
            if (is_array($selectedRaw)) {
                $selectedMeta = $selectedRaw;
            } elseif (is_string($selectedRaw)) {
                $decoded = json_decode($selectedRaw, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $selectedMeta = $decoded;
                }
            }
        }

        $merge = [];

        $merge['applicant_type'] = $request->input('applicant_type')
            ?? $request->input('applicantType')
            ?? ($selectedMeta['applicant_type'] ?? null);

        $merge['applicant_title'] = $request->input('applicant_title')
            ?? $request->input('title')
            ?? ($selectedMeta['applicant_title'] ?? null);

        $merge['first_name'] = $request->input('first_name')
            ?? $request->input('fname')
            ?? ($selectedMeta['first_name'] ?? null);

        $merge['middle_name'] = $request->input('middle_name')
            ?? $request->input('mname')
            ?? ($selectedMeta['middle_name'] ?? null);

        $merge['surname'] = $request->input('surname')
            ?? $request->input('lname')
            ?? ($selectedMeta['surname'] ?? null);

        $merge['corporate_name'] = $request->input('corporate_name')
            ?? ($selectedMeta['corporate_name'] ?? null);

        $merge['rc_number'] = $request->input('rc_number')
            ?? ($selectedMeta['rc_number'] ?? null);

        $merge['np_fileno'] = $request->input('np_fileno')
            ?? ($selectedMeta['np_fileno'] ?? $selectedMeta['fileno'] ?? null);

        $merge['fileno'] = $request->input('fileno')
            ?? ($selectedMeta['fileno'] ?? $selectedMeta['full_file_number'] ?? null);

        $merge['land_use'] = $request->input('land_use')
            ?? ($selectedMeta['land_use'] ?? $selectedMeta['land_use_code'] ?? null);

        $merge['tracking_id'] = $request->input('tracking_id')
            ?? ($selectedMeta['tra'] ?? $selectedMeta['tracking_id'] ?? null);

        $merge['primary_file_id'] = $request->input('primary_file_id')
            ?? ($selectedMeta['id'] ?? null);

        $merge['selected_file_id'] = $request->input('selected_file_id')
            ?? ($selectedMeta['id'] ?? null);

        $merge['selected_file_type'] = $request->input('selected_file_type')
            ?? ($selectedMeta['file_no_type'] ?? null);

        $merge['applied_file_number'] = $request->input('applied_file_number')
            ?? ($selectedMeta['fileno'] ?? $selectedMeta['full_file_number'] ?? null);

        // Ensure phone_number array is available when only phone field exists
        if (!$request->has('phone_number') && $request->filled('phone')) {
            $merge['phone_number'] = [$request->input('phone')];
        }

        // Ensure contact address fallback
        // Only merge non-null values to avoid overriding intentionally empty fields
        $merge = array_filter($merge, function ($value) {
            if (is_array($value)) {
                return !empty($value);
            }

            return $value !== null && $value !== '';
        });

        if (!empty($merge)) {
            $request->merge($merge);
        }
    }

    /**
     * Generate owner full name based on applicant type
     */
    protected function generateOwnerFullName(array $validated, string $applicantType): ?string
    {
        if ($applicantType === 'corporate') {
            return $validated['corporate_name'] ?? null;
        } elseif ($applicantType === 'multiple') {
            // For multiple owners, join all names
            if (isset($validated['multiple_owners_names']) && is_array($validated['multiple_owners_names'])) {
                return implode(', ', array_filter($validated['multiple_owners_names']));
            }
            return 'Multiple Owners';
        } else {
            // Individual - combine title, first name, middle name, surname
            $nameParts = array_filter([
                $validated['applicant_title'] ?? null,
                $validated['first_name'] ?? null,
                $validated['middle_name'] ?? null,
                $validated['surname'] ?? null
            ]);
            return !empty($nameParts) ? implode(' ', $nameParts) : null;
        }
    }

    /**
     * Prepare buyer records from request payloads (dynamic form + JSON imports).
     */
    protected function prepareBuyerRecords(Request $request): array
    {
        $records = $request->input('records', []);
        if (!is_array($records)) {
            $records = [];
        }

        if ($request->filled('buyers_json')) {
            $decoded = json_decode($request->input('buyers_json'), true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $records = array_merge($records, $decoded);
            }
        }

        if (empty($records)) {
            return [];
        }

        $normalised = [];

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $buyerTitle = $record['buyerTitle'] ?? ($record['buyer_title'] ?? null);
            $firstName = $record['firstName'] ?? ($record['first_name'] ?? null);
            $middleName = $record['middleName'] ?? ($record['middle_name'] ?? null);
            $surname = $record['surname'] ?? ($record['lastName'] ?? $record['last_name'] ?? null);
            $buyerName = $record['buyerName'] ?? ($record['buyer_name'] ?? null);
            $unitNo = $record['unit_no'] ?? ($record['unitNo'] ?? $record['unitNumber'] ?? null);
            $unitMeasurement = $record['unitMeasurement'] ?? ($record['measurement'] ?? null);
            $sectionNumber = $record['sectionNumber'] ?? ($record['section_number'] ?? null);
            if (is_string($sectionNumber)) {
                $sectionNumber = trim($sectionNumber);
                if ($sectionNumber === '') {
                    $sectionNumber = null;
                }
            }
            $landUse = $record['landUse'] ?? ($record['land_use'] ?? null);

            if (!$buyerName && ($buyerTitle || $firstName || $middleName || $surname)) {
                $buyerName = trim(implode(' ', array_filter([
                    $buyerTitle,
                    $firstName,
                    $middleName,
                    $surname
                ])));
            }

            $normalised[] = [
                'buyerTitle' => $buyerTitle,
                'firstName' => $firstName,
                'middleName' => $middleName,
                'surname' => $surname,
                'buyerName' => $buyerName,
                'unit_no' => $unitNo,
                'unitMeasurement' => $unitMeasurement,
                'sectionNumber' => $sectionNumber,
                'landUse' => $landUse
            ];
        }

        return array_values(array_filter($normalised, function (array $record) {
            $hasBuyer = !empty($record['buyerName']) || !empty($record['firstName']) || !empty($record['surname']);
            $hasUnit = !empty($record['unit_no']);
            $hasSection = !empty($record['sectionNumber']);

            return $hasBuyer && $hasUnit && $hasSection;
        }));
    }

    /**
     * Process uploaded documents for EDMS workflow
     * Uses NP File Number for folder creation and file naming
     */
    private function processDocumentsForEDMS($fileIndexingId, $documents, $npFileNumber)
    {
        try {
            Log::info('Processing documents for EDMS workflow', [
                'file_indexing_id' => $fileIndexingId,
                'np_file_number' => $npFileNumber,
                'documents_count' => count($documents)
            ]);

            // Create EDMS directory structure: EDMS/SCAN_UPLOAD/{npFileNumber}/
            $edmsBasePath = "EDMS/SCAN_UPLOAD/{$npFileNumber}";
            if (!Storage::disk('public')->exists($edmsBasePath)) {
                Storage::disk('public')->makeDirectory($edmsBasePath);
                Log::info('Created EDMS directory', [
                    'path' => Storage::disk('public')->path($edmsBasePath)
                ]);
            }

            // Get current maximum display_order for this file_indexing_id
            $maxDisplayOrder = DB::connection('sqlsrv')
                ->table('scannings')
                ->where('file_indexing_id', $fileIndexingId)
                ->max('display_order') ?? 0;

            $displayOrder = $maxDisplayOrder + 1;
            $sequenceNumber = 1;

            foreach ($documents as $document) {
                try {
                    $docType = $document['doc_type'] ?? 'document';
                    $source = $document['source'] ?? 'accompanying';
                    $relativeSourcePath = $document['path'] ?? null;
                    $originalName = $document['original_name'] ?? null;

                    if (!$relativeSourcePath || !$originalName) {
                        Log::warning('Skipping document with incomplete metadata', [
                            'doc_type' => $docType,
                            'source' => $source,
                            'metadata' => $document
                        ]);
                        continue;
                    }

                    // Verify the source file exists
                    if (!Storage::disk('public')->exists($relativeSourcePath)) {
                        Log::warning('Original document file not found', [
                            'doc_type' => $docType,
                            'source' => $source,
                            'path' => $relativeSourcePath,
                            'full_path' => Storage::disk('public')->path($relativeSourcePath)
                        ]);
                        continue;
                    }

                    // Extract file extension
                    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                    $extension = $extension ?: ($document['type'] ?? 'pdf');
                    $extension = strtolower(ltrim((string) $extension, '.'));
                    if ($extension === '') {
                        $extension = 'pdf';
                    }

                    // Generate sequential filename using NP file number like: ST-COM-2025-01-002_0001.pdf
                    $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
                    $newFilename = "{$npFileNumber}_{$sequenceStr}.{$extension}";
                    $destinationPath = "{$edmsBasePath}/{$newFilename}";

                    // Copy file to EDMS structure with collision handling
                    try {
                        if (!Storage::disk('public')->exists($destinationPath)) {
                            Storage::disk('public')->copy($relativeSourcePath, $destinationPath);
                        } else {
                            // Handle filename collision by adding unique identifier
                            $newFilename = "{$npFileNumber}_{$sequenceStr}_" . uniqid() . ".{$extension}";
                            $destinationPath = "{$edmsBasePath}/{$newFilename}";
                            Storage::disk('public')->copy($relativeSourcePath, $destinationPath);
                        }
                    } catch (Exception $copyError) {
                        Log::error('Failed to copy document to EDMS structure', [
                            'doc_type' => $docType,
                            'source' => $source,
                            'error' => $copyError->getMessage(),
                            'original_path' => $relativeSourcePath,
                            'destination_path' => $destinationPath
                        ]);
                        continue;
                    }

                    // Verify the copy was successful
                    if (!Storage::disk('public')->exists($destinationPath)) {
                        Log::error('File copy failed verification', [
                            'original_path' => $relativeSourcePath,
                            'destination_path' => $destinationPath,
                            'doc_type' => $docType,
                            'source' => $source
                        ]);
                        continue;
                    }

                    // Map document type and generate notes
                    $documentTypeLabel = $source === 'scan_upload'
                        ? 'Scan Upload Document'
                        : $this->mapDocumentType($docType);

                    $notes = $source === 'scan_upload'
                        ? 'Uploaded from Scan Upload (File Documents) section during primary application submission.'
                        : "Uploaded from primary application form - Document type: {$docType}. Original filename: {$originalName}";

                    // Prepare scanning record data - mapped to match database structure
                    $scanningData = [
                        'file_indexing_id' => $fileIndexingId,
                        'document_path' => $destinationPath, // Full path within public storage
                        'original_filename' => $originalName,
                        'uploaded_by' => Auth::id(),
                        'status' => 'pending', // Status: pending, scanned, reviewed, approved
                        'paper_size' => $this->detectPaperSize($originalName),
                        'document_type' => $documentTypeLabel,
                        'notes' => $notes,
                        'display_order' => $displayOrder,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    // Insert scanning record using raw DB query for better error handling
                    try {
                        $scanningId = DB::connection('sqlsrv')->table('scannings')->insertGetId($scanningData);

                        if (!$scanningId) {
                            Log::error('Failed to insert scanning record - no ID returned', [
                                'scanning_data' => $scanningData
                            ]);
                            continue;
                        }

                        Log::info('Scanning record created successfully', [
                            'scanning_id' => $scanningId,
                            'file_indexing_id' => $fileIndexingId,
                            'document_type' => $documentTypeLabel,
                            'display_order' => $displayOrder
                        ]);

                    } catch (Exception $scanningError) {
                        Log::error('Database error inserting scanning record', [
                            'error' => $scanningError->getMessage(),
                            'trace' => $scanningError->getTraceAsString(),
                            'scanning_data' => $scanningData
                        ]);
                        continue;
                    }

                    Log::info('Document processed successfully for EDMS', [
                        'doc_type' => $docType,
                        'source' => $source,
                        'original_name' => $originalName,
                        'new_filename' => $newFilename,
                        'edms_path' => $destinationPath,
                        'scanning_id' => $scanningId,
                        'display_order' => $displayOrder,
                        'file_size' => Storage::disk('public')->size($destinationPath)
                    ]);

                    $sequenceNumber++;
                    $displayOrder++;
                    
                } catch (Exception $docError) {
                    Log::error('Error processing individual document', [
                        'error' => $docError->getMessage(),
                        'trace' => $docError->getTraceAsString(),
                        'document' => $document
                    ]);
                    continue;
                }
            }

            Log::info('EDMS document processing completed', [
                'file_indexing_id' => $fileIndexingId,
                'np_file_number' => $npFileNumber,
                'total_documents' => count($documents),
                'processed_documents' => $sequenceNumber - 1,
                'final_display_order' => $displayOrder - 1
            ]);

        } catch (Exception $e) {
            Log::error('Error processing documents for EDMS', [
                'file_indexing_id' => $fileIndexingId,
                'np_file_number' => $npFileNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Map document type to scanning document type
     */
    private function mapDocumentType($docType)
    {
        $mapping = [
            // Primary application form documents
            'application_letter' => 'Application Letter',
            'building_plan' => 'Building Plan',
            'architectural_design' => 'Architectural Design',
            'ownership_document' => 'Ownership Document',
            'survey_plan' => 'Survey Plan',
            
            // Scan upload documents (from drag & drop section)
            'scan_upload_1' => 'Scan Upload Document',
            'scan_upload_2' => 'Scan Upload Document',
            'scan_upload_3' => 'Scan Upload Document',
            'scan_upload_4' => 'Scan Upload Document',
            'scan_upload_5' => 'Scan Upload Document',
            
            // Additional document types
            'passport' => 'Passport Photograph',
            'id_document' => 'Identification Document',
            'certificate' => 'Certificate',
            'receipt' => 'Payment Receipt',
            'memo' => 'Official Memo',
            'correspondence' => 'Correspondence',
            'plan' => 'Plan/Drawing',
            'report' => 'Report',
            'form' => 'Official Form',
            'contract' => 'Contract/Agreement',
            'deed' => 'Deed/Title Document',
            'clearance' => 'Clearance Certificate',
            'approval' => 'Approval Document',
            'permit' => 'Permit/License'
        ];

        // Handle scan upload pattern (scan_upload_N)
        if (strpos($docType, 'scan_upload_') === 0) {
            return 'Scan Upload Document';
        }

        return $mapping[$docType] ?? 'Document';
    }

    /**
     * Detect paper size from filename (basic heuristic)
     */
    private function detectPaperSize($filename)
    {
        // Basic paper size detection - can be enhanced
        $filename = strtolower($filename);
        
        if (strpos($filename, 'a4') !== false) {
            return 'A4';
        } elseif (strpos($filename, 'a3') !== false) {
            return 'A3';
        } elseif (strpos($filename, 'a2') !== false) {
            return 'A2';
        } elseif (strpos($filename, 'a1') !== false) {
            return 'A1';
        } elseif (strpos($filename, 'a0') !== false) {
            return 'A0';
        }
        
        return 'A4'; // Default
    }
}