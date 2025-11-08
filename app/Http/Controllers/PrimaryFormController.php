<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Helpers\SectionalTitleHelper;
use App\Models\FileIndexing;
use App\Models\Scanning;
use App\Services\FileNumberReservationService;

class PrimaryFormController extends Controller
{
    /**
     * File number reservation service
     */
    protected $reservationService;

    /**
     * Constructor
     */
    public function __construct(FileNumberReservationService $reservationService)
    {
        $this->reservationService = $reservationService;
    }
    public function index()
    {
        $PageTitle = 'Application for Sectional Titling';
        $PageDescription = 'Main Application';

        // Generate NP FileNo for the main application
        $rawLandUse = request()->query('landuse', 'COMMERCIAL');
        
        // Normalize land use to match database expected values
        $landUse = match(strtoupper(trim($rawLandUse))) {
            'COMMERCIAL', 'COMMERCIAL USE' => 'COMMERCIAL',
            'INDUSTRIAL', 'INDUSTRIAL USE' => 'INDUSTRIAL', 
            'RESIDENTIAL', 'RESIDENTIAL USE' => 'RESIDENTIAL',
            'MIXED', 'MIXED USE' => 'MIXED',
            default => 'COMMERCIAL'
        };
        
        // Determine the land use code
        $landUseCode = match($landUse) {
            'COMMERCIAL' => 'COM',
            'INDUSTRIAL' => 'IND', 
            'RESIDENTIAL' => 'RES',
            'MIXED' => 'MIXED',
            default => 'COM'
        };
        
        // Get the current year
        $currentYear = date('Y');
        
        // Get the latest primary file number from SUA applications
        $latestSUAPrimaryFileNo = $this->getLatestSUAPrimaryFileNo($landUse, $currentYear);
        
        // Use SUA's primary file number sequence for NPFN
        $npFileNo = $latestSUAPrimaryFileNo;
        
        // Extract serial number from the SUA primary file number for display
        $serialNo = $this->extractSerialFromFileNo($latestSUAPrimaryFileNo);

        return view('primaryform.index', compact(
            'PageTitle', 
            'PageDescription',
            'npFileNo',
            'landUse',
            'currentYear',
            'serialNo'
        )); 
    }
    
    public function store(Request $request)
    {
        try {
            // ⚠️ VALIDATION TEMPORARILY DISABLED FOR AJAX TESTING
            // See VALIDATION_TODO.md for complete validation rules to re-implement
            $rules = [
                'applicantType' => 'required',
                'land_use' => 'required|string|max:100',
                'np_fileno' => 'required|string|max:255',
                'fileno' => 'nullable|string|max:255',
                'applicant_title' => 'nullable',
                'first_name' => 'nullable',
                'middle_name' => 'nullable',
                'surname' => 'nullable',
                'corporate_name' => 'nullable',
                'rc_number' => 'nullable',
                'multiple_owners_names' => 'nullable|array',
                'multiple_owners_address' => 'nullable|array',
                'multiple_owners_passport' => 'nullable|array',
                'multiple_owners_passport.*' => 'nullable|image|max:5120',
                'multiple_owners_email' => 'nullable|array',
                'multiple_owners_email.*' => 'nullable|email',
                'multiple_owners_phone' => 'nullable|array',
                'multiple_owners_phone.*' => 'nullable|string',
                'multiple_owners_identification_type' => 'nullable|array',
                'multiple_owners_identification_type.*' => 'nullable|string',
                'multiple_owners_identification_image' => 'nullable|array',
                'multiple_owners_identification_image.*' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'address_house_no' => 'nullable',
                'owner_street_name' => 'nullable',
                'owner_district' => 'nullable',
                'owner_lga' => 'nullable',
                'owner_state' => 'nullable',
                'phone_number' => 'nullable',
                'owner_email' => 'nullable|email',
                'idType' => 'nullable',
                'id_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'residenceType' => 'nullable',
                'units_count' => 'nullable|integer|min:1',
                'blocks_count' => 'nullable|integer|min:1',
                'sections_count' => 'nullable|integer|min:1',
                'plot_size' => 'nullable|string|max:255',
                'scheme_no' => 'nullable|string|max:255', // Optional - comes from API
                'property_house_no' => 'nullable|string|max:255',
                'property_plot_no' => 'nullable|string|max:255',
                'property_street_name' => 'nullable|string|max:255', // Optional - comes from API
                'property_district' => 'nullable|string|max:255',
                'property_lga' => 'nullable|string|max:255', // Optional - comes from API
                'property_state' => 'nullable|string|max:255', // Optional - comes from API
                'applied_file_number' => 'nullable|string|max:255',
                'selected_file_id' => 'nullable|string|max:255',
                'selected_file_type' => 'nullable|string|max:255',
                'selected_file_data' => 'nullable|string',
                'application_fee' => 'nullable',
                'processing_fee' => 'nullable',
                'site_plan_fee' => 'nullable',
                'payment_date' => 'nullable',
                'receipt_number' => 'nullable',
                'comments' => 'nullable',
                'commercial_type' => 'nullable',
                'passportInput' => 'nullable',
                'application_date' => 'nullable|date',
                'application_letter' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'building_plan' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'architectural_design' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'ownership_document' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'survey_plan' => 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png',
                'csv_file' => 'nullable|file|max:5120|mimes:csv,txt',
                'shared_areas' => 'nullable|array',
                'shared_areas.*' => 'nullable|string',
                'other_areas_detail' => 'nullable|string|max:500',
                'selected_file_id' => 'nullable|string|max:255',
                'selected_file_type' => 'nullable|string|max:255',
                'selected_file_data' => 'nullable|string',
                // Buyer records validation (CSV import and manual entry)
                'records' => 'nullable|array',
                'records.*.buyerTitle' => 'required_with:records|string|max:255',
                'records.*.firstName' => 'required_with:records|string|max:255',
                'records.*.middleName' => 'nullable|string|max:255',
                'records.*.surname' => 'required_with:records|string|max:255',
                'records.*.unit_no' => 'required_with:records|string|max:50',
                'records.*.sectionNumber' => 'required_with:records|string|max:50',
                'records.*.landUse' => 'required_with:records|string|in:Residential,Commercial,Industrial,Mixed Use',
                'records.*.unitMeasurement' => 'nullable|string|max:100',
            ];

            // Conditional validation
            if ($request->input('applicantType') === 'multiple') {
                $rules['multiple_owners_names'] = 'required|array|min:1';
                $rules['multiple_owners_names.*'] = 'required|string';
                $rules['multiple_owners_address'] = 'required|array|min:1';
                $rules['multiple_owners_address.*'] = 'required|string';
                $rules['multiple_owners_email'] = 'required|array|min:1';
                $rules['multiple_owners_email.*'] = 'required|email';
                $rules['multiple_owners_phone'] = 'required|array|min:1';
                $rules['multiple_owners_phone.*'] = 'required|string';
                $rules['multiple_owners_identification_type'] = 'required|array|min:1';
                $rules['multiple_owners_identification_type.*'] = 'required|string';
                $rules['multiple_owners_identification_image'] = 'required|array|min:1';
                $rules['multiple_owners_identification_image.*'] = 'required|file|max:5120|mimes:pdf,jpg,jpeg,png';

                // Main owner fields must be nullable (not required)
                $rules['address_house_no'] = 'nullable';
                $rules['owner_street_name'] = 'nullable';
                $rules['owner_district'] = 'nullable';
                $rules['owner_lga'] = 'nullable';
                $rules['owner_state'] = 'nullable';
                $rules['phone_number'] = 'nullable';
                $rules['owner_email'] = 'nullable|email';
                $rules['idType'] = 'nullable';
                $rules['id_document'] = 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png';
            } else {
                // Main owner nullable fields (not required)
                $rules['address_house_no'] = 'nullable';
                $rules['owner_street_name'] = 'nullable';
                $rules['owner_district'] = 'nullable';
                $rules['owner_lga'] = 'nullable';
                $rules['owner_state'] = 'nullable';
                $rules['phone_number'] = 'nullable';
                $rules['owner_email'] = 'nullable|email';
                $rules['idType'] = 'nullable';
                $rules['id_document'] = 'nullable|file|max:5120|mimes:pdf,jpg,jpeg,png';
            }

            // Re-enable validation for proper form submission
            $validated = $request->validate($rules);

            $selectedFileData = null;
            if ($request->filled('selected_file_data')) {
                $decodedSelectedFileData = json_decode($request->input('selected_file_data'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedSelectedFileData)) {
                    $selectedFileData = $decodedSelectedFileData;
                } else {
                    Log::warning('selected_file_data payload could not be decoded', [
                        'selected_file_data' => $request->input('selected_file_data'),
                        'json_error' => json_last_error_msg(),
                    ]);
                }
            }

            // Debug log to check what's being received
            Log::info('Form data received', [
                'owner_fullname' => $request->input('fullname'),
                'all_data' => $request->all()
            ]);

            // Process the file number based on active tab and selected_file_data
            $fileNo = null;
            $mlsFileNo = null;
            $kangisFileNo = null;
            $newKangisFileNo = null;
            
            // First check if selected_file_data contains the file number
            if ($selectedFileData) {
                $fileNumberFromSelection = $selectedFileData['fileNumber'] ?? $selectedFileData['fileno'] ?? null;
                if ($fileNumberFromSelection) {
                    $fileNo = $fileNumberFromSelection;
                    Log::info('File number extracted from selected_file_data', [
                        'fileNumber' => $fileNo,
                        'system' => $selectedFileData['system'] ?? ($selectedFileData['source'] ?? 'Unknown'),
                        'tab' => $selectedFileData['tab'] ?? 'Unknown'
                    ]);
                }

                $mlsFileNo = $selectedFileData['mls_fileno'] ?? $mlsFileNo;
                $kangisFileNo = $selectedFileData['kangis_fileno'] ?? $kangisFileNo;
                $newKangisFileNo = $selectedFileData['new_kangis_fileno'] ?? $newKangisFileNo;
            }
            
            if (!$fileNo && $request->filled('fileno')) {
                $fileNo = $request->input('fileno');
            }

            // Fallback to individual file number inputs if selected_file_data is not available
            if (!$fileNo) {
                if ($request->filled('mlsPreviewFileNumber')) {
                    $fileNo = $request->input('mlsPreviewFileNumber');
                    $mlsFileNo = $request->input('mlsPreviewFileNumber');
                } elseif ($request->filled('kangisPreviewFileNumber')) {
                    $fileNo = $request->input('kangisPreviewFileNumber');
                    $kangisFileNo = $request->input('kangisPreviewFileNumber');
                } elseif ($request->filled('newKangisPreviewFileNumber')) {
                    $fileNo = $request->input('newKangisPreviewFileNumber');
                    $newKangisFileNo = $request->input('newKangisPreviewFileNumber');
                }
            }
            
            // Additional fallback: check applied_file_number field
            if (!$fileNo && $request->filled('applied_file_number')) {
                $fileNo = $request->input('applied_file_number');
                Log::info('Using applied_file_number as fileNo', ['fileNo' => $fileNo]);
            }
            
            Log::info('Final file number processing result', [
                'fileNo' => $fileNo,
                'mlsFileNo' => $mlsFileNo,
                'kangisFileNo' => $kangisFileNo,
                'newKangisFileNo' => $newKangisFileNo,
                'applied_file_number' => $request->input('applied_file_number')
            ]);

            // Handle passport upload
            $passportPath = null;
            if ($request->hasFile('passport')) {
                $passport = $request->file('passport');
                Log::info('Passport file received', [
                    'type' => gettype($passport), 
                    'class' => get_class($passport),
                    'original_name' => $passport->getClientOriginalName(),
                    'size' => $passport->getSize()
                ]);
                $passportPath = $passport->store('passports', 'public');
                Log::info('Passport file stored', ['path' => $passportPath]);
            } else {
                Log::info('No passport file received', ['has_passport' => $request->has('passport')]);
            }

            // Handle ID document upload
            $idDocumentPath = null;
            if ($request->hasFile('id_document')) {
                $idDocument = $request->file('id_document');
                $originalName = $idDocument->getClientOriginalName();
                $extension = $idDocument->getClientOriginalExtension();
                Log::info('ID Document file received', [
                    'original_name' => $originalName,
                    'size' => $idDocument->getSize(),
                    'type' => $extension
                ]);
                $idDocumentPath = $idDocument->store('id_documents', 'public');
                
                Log::info('ID Document uploaded', [
                    'path' => $idDocumentPath,
                    'original_name' => $originalName,
                    'type' => $extension
                ]);
            } else {
                Log::info('No ID document file received', ['has_id_document' => $request->has('id_document')]);
            }

            // Handle multiple owners passports upload
            $multipleOwnersPassportPaths = [];
            if ($request->hasFile('multiple_owners_passport')) {
                foreach ($request->file('multiple_owners_passport') as $passport) {
                    if ($passport && $passport->isValid()) {
                        $path = $passport->store('multiple_owners_passports', 'public');
                        $multipleOwnersPassportPaths[] = $path;
                    } else {
                        $multipleOwnersPassportPaths[] = null;
                    }
                }
            }

            // Handle multiple owners identification images upload
            $multipleOwnersIdImagePaths = [];
            if ($request->hasFile('multiple_owners_identification_image')) {
                foreach ($request->file('multiple_owners_identification_image') as $idimg) {
                    if ($idimg && $idimg->isValid()) {
                        $path = $idimg->store('multiple_owners_id_images', 'public');
                        $multipleOwnersIdImagePaths[] = $path;
                    } else {
                        $multipleOwnersIdImagePaths[] = null;
                    }
                }
            }

            // Process document uploads - using direct file access
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

            // Process scan upload files captured via drag & drop interface
            $scanUploadPayload = [];
            $scanUploadFiles = Arr::wrap($request->file('scan_upload_files'));

            foreach ($scanUploadFiles as $index => $scanFile) {
                if ($scanFile && $scanFile->isValid()) {
                    $originalName = $scanFile->getClientOriginalName();
                    $extension = $scanFile->getClientOriginalExtension();
                    $path = $scanFile->store('scan_uploads', 'public');

                    $scanMeta = [
                        'path' => $path,
                        'original_name' => $originalName,
                        'type' => $extension,
                        'uploaded_at' => now()->toDateTimeString()
                    ];

                    $scanUploadPayload[] = $scanMeta;
                    $edmsDocuments[] = array_merge($scanMeta, [
                        'doc_type' => 'scan_upload_' . ($index + 1),
                        'source' => 'scan_upload'
                    ]);

                    Log::info('Scan upload file captured', [
                        'index' => $index,
                        'path' => $path,
                        'original_name' => $originalName
                    ]);
                }
            }

            if (!empty($scanUploadPayload)) {
                $documentPayload['scan_uploads'] = $scanUploadPayload;
            }

            // Process shared areas
            $sharedAreas = null;
            if ($request->has('shared_areas') && is_array($request->input('shared_areas'))) {
                $sharedAreasArray = $request->input('shared_areas');

                if (in_array('other', $sharedAreasArray) && $request->filled('other_areas_detail')) {
                    $sharedAreasArray['other_details'] = $request->input('other_areas_detail');
                }

                $sharedAreas = json_encode($sharedAreasArray);
            }

            // Format phone numbers
            $phoneNumber = null;
            if ($request->has('phone_number') && is_array($request->input('phone_number'))) {
                $phoneNumber = implode(', ', array_filter(array_map('trim', $request->input('phone_number'))));
            } elseif ($request->has('phone_number')) {
                $phoneNumber = $request->input('phone_number');
            }

            // Prepare multiple owners' structured values
            $multipleOwnersNames = $request->input('multiple_owners_names');
            if (is_array($multipleOwnersNames)) {
                $multipleOwnersNames = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $multipleOwnersNames);
            }

            $multipleOwnersAddresses = $request->input('multiple_owners_address');
            if (is_array($multipleOwnersAddresses)) {
                $multipleOwnersAddresses = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $multipleOwnersAddresses);
            }

            $multipleOwnersEmails = $request->input('multiple_owners_email');
            if (is_array($multipleOwnersEmails)) {
                $multipleOwnersEmails = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $multipleOwnersEmails);
            }

            $multipleOwnersPhones = $request->input('multiple_owners_phone');
            if (is_array($multipleOwnersPhones)) {
                $multipleOwnersPhones = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $multipleOwnersPhones);
            }

            $multipleOwnersIdentificationTypes = $request->input('multiple_owners_identification_type');
            if (is_array($multipleOwnersIdentificationTypes)) {
                $multipleOwnersIdentificationTypes = array_map(function ($value) {
                    return is_string($value) ? trim($value) : $value;
                }, $multipleOwnersIdentificationTypes);
            }

            $multipleOwnersNamesHasValue = is_array($multipleOwnersNames) && count(array_filter($multipleOwnersNames, function ($value) {
                return $value !== null && trim((string) $value) !== '';
            })) > 0;

            $multipleOwnersAddressesHasValue = is_array($multipleOwnersAddresses) && count(array_filter($multipleOwnersAddresses, function ($value) {
                return $value !== null && trim((string) $value) !== '';
            })) > 0;

            $multipleOwnersEmailsHasValue = is_array($multipleOwnersEmails) && count(array_filter($multipleOwnersEmails, function ($value) {
                return $value !== null && trim((string) $value) !== '';
            })) > 0;

            $multipleOwnersPhonesHasValue = is_array($multipleOwnersPhones) && count(array_filter($multipleOwnersPhones, function ($value) {
                return $value !== null && trim((string) $value) !== '';
            })) > 0;

            $multipleOwnersIdentificationTypesHasValue = is_array($multipleOwnersIdentificationTypes) && count(array_filter($multipleOwnersIdentificationTypes, function ($value) {
                return $value !== null && trim((string) $value) !== '';
            })) > 0;

            $multipleOwnersPassportHasValue = !empty(array_filter($multipleOwnersPassportPaths));
            $multipleOwnersIdImagesHasValue = !empty(array_filter($multipleOwnersIdImagePaths));

            // Resolve owner full name
            $applicantType = strtolower((string) $request->input('applicantType'));
            $ownerFullName = null;
            if ($applicantType === 'individual') {
                $nameParts = array_filter([
                    $request->input('applicant_title'),
                    $request->input('first_name'),
                    $request->input('middle_name'),
                    $request->input('surname')
                ], function ($value) {
                    return $value !== null && trim((string) $value) !== '';
                });

                if (!empty($nameParts)) {
                    $ownerFullName = implode(' ', $nameParts);
                }
            } elseif ($applicantType === 'corporate') {
                $ownerFullName = $request->input('corporate_name');
            } elseif ($applicantType === 'multiple' && $multipleOwnersNamesHasValue) {
                $nonEmptyNames = array_values(array_filter($multipleOwnersNames, function ($value) {
                    return $value !== null && trim((string) $value) !== '';
                }));

                if (!empty($nonEmptyNames)) {
                    $ownerFullName = $nonEmptyNames[0];
                    if (count($nonEmptyNames) > 1) {
                        $ownerFullName .= ' et al.';
                    }
                }
            }

            // Resolve property sub-types
            $residenceTypeInput = $request->input('residenceType');
            $otherResidenceType = $request->input('otherResidenceType');
            $resolvedResidenceType = ($residenceTypeInput === 'others' && $otherResidenceType)
                ? $otherResidenceType
                : $residenceTypeInput;

            $commercialTypeInput = $request->input('commercial_type');
            $otherCommercialType = $request->input('commercial_type_others');
            $resolvedCommercialType = ($commercialTypeInput === 'Others' && $otherCommercialType)
                ? $otherCommercialType
                : $commercialTypeInput;

            $industrialTypeInput = $request->input('industrial_type');
            $otherIndustrialType = $request->input('industrial_type_others');
            $resolvedIndustrialType = ($industrialTypeInput === 'Others' && $otherIndustrialType)
                ? $otherIndustrialType
                : $industrialTypeInput;

            $ownershipType = $request->input('ownershipType');
            $ownershipTypeOther = $request->input('otherOwnership');

            // Determine land use
            $rawLandUse = $selectedFileData['land_use'] ?? $request->input('land_use', 'Residential');
            if (!is_string($rawLandUse) || trim($rawLandUse) === '') {
                $rawLandUse = 'Residential';
            }

            $landUse = match(strtoupper(trim($rawLandUse))) {
                'COMMERCIAL', 'COMMERCIAL USE' => 'COMMERCIAL',
                'INDUSTRIAL', 'INDUSTRIAL USE' => 'INDUSTRIAL',
                'RESIDENTIAL', 'RESIDENTIAL USE' => 'RESIDENTIAL',
                'MIXED', 'MIXED USE' => 'MIXED',
                default => 'RESIDENTIAL'
            };

            $landUseCode = match($landUse) {
                'COMMERCIAL' => 'COM',
                'INDUSTRIAL' => 'IND',
                'RESIDENTIAL' => 'RES',
                'MIXED' => 'MIXED',
                default => 'RES'
            };

            $landUseForStorage = $selectedFileData['land_use'] ?? $request->input('land_use', $landUse);
            if (!is_string($landUseForStorage) || trim($landUseForStorage) === '') {
                $landUseForStorage = $landUse;
            }
            $landUseForStorage = ucwords(strtolower($landUseForStorage));

            Log::info('Land use normalization', [
                'raw_input' => $rawLandUse,
                'normalized' => $landUse,
                'code' => $landUseCode,
                'stored' => $landUseForStorage,
            ]);

            $currentYear = date('Y');

            $mixedTypeDetails = null;
            if ($landUse === 'MIXED') {
                $mixedTypeDetails = [
                    'residential' => $resolvedResidenceType,
                    'commercial' => $resolvedCommercialType,
                ];
            }

            // Capture NP File number supplied from UI or API
            $npFileNoFromRequest = trim((string) $request->input('np_fileno', ''));
            if ($selectedFileData && !empty($selectedFileData['np_fileno'])) {
                $npFileNoFromRequest = $selectedFileData['np_fileno'];
            }

            if ($npFileNoFromRequest === '' && $selectedFileData && !empty($selectedFileData['fileno'])) {
                $npFileNoFromRequest = $selectedFileData['fileno'];
            }

            Log::info('NP File number sources', [
                'from_request' => $npFileNoFromRequest,
                'from_selected_data' => $selectedFileData['np_fileno'] ?? null,
            ]);

            // Insert basic data first to get the application ID
            $tempData = [
                'applicant_type' => $request->input('applicantType'),
                'land_use' => $landUseForStorage,
                'application_status' => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $applicationId = DB::connection('sqlsrv')->table('mother_applications')->insertGetId($tempData);

            $this->saveSharedUtilitiesToTable($request, $applicationId);

            $generatedNpFileNo = null;
            $landUseSerialNo = null;
            if ($npFileNoFromRequest === '') {
                $landUseSerialNo = $this->getNextSerialNumber($landUse, $currentYear);
                $generatedNpFileNo = $this->generateFileNumber($landUse, $landUseSerialNo, $currentYear);
            }

            $npFileNo = $npFileNoFromRequest !== '' ? $npFileNoFromRequest : $generatedNpFileNo;

            if (!$npFileNo) {
                throw new Exception('Unable to determine NP file number for this application.');
            }

            if ($npFileNoFromRequest !== '' && $generatedNpFileNo && $npFileNoFromRequest !== $generatedNpFileNo) {
                Log::warning('NP File number overridden by UI input', [
                    'generated' => $generatedNpFileNo,
                    'from_request' => $npFileNoFromRequest,
                    'used' => $npFileNo,
                ]);
            } elseif ($npFileNoFromRequest === '' && $generatedNpFileNo) {
                Log::info('Generated NP file number because none was supplied from ST API selection', [
                    'generated' => $generatedNpFileNo,
                    'land_use' => $landUse,
                    'year' => $currentYear,
                ]);
            }

            if (!$fileNo) {
                $fileNo = $npFileNo;
            }

            if (is_string($ownerFullName)) {
                $ownerFullName = trim($ownerFullName);
            }
            if ($ownerFullName === '') {
                $ownerFullName = null;
            }

            $selectedFileId = $request->input('selected_file_id') ?: ($selectedFileData['id'] ?? $request->input('primary_file_id'));
            if (is_string($selectedFileId)) {
                $selectedFileId = trim($selectedFileId);
                if ($selectedFileId === '') {
                    $selectedFileId = null;
                }
            }
            $selectedFileType = $request->input('selected_file_type') ?: ($selectedFileData['system'] ?? ($selectedFileData['file_no_type'] ?? null));
            if (is_string($selectedFileType)) {
                $selectedFileType = trim($selectedFileType);
            }
            if ($selectedFileType === '') {
                $selectedFileType = null;
            }
            if (!$selectedFileType && $selectedFileData) {
                $selectedFileType = $selectedFileData['file_no_type'] ?? 'st-file-number';
            }
            if (is_string($selectedFileType)) {
                $selectedFileType = strtoupper($selectedFileType);
            }

            $selectedFileDataJson = $selectedFileData
                ? json_encode($selectedFileData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : ($request->filled('selected_file_data') ? $request->input('selected_file_data') : null);
            if (is_string($selectedFileDataJson) && trim($selectedFileDataJson) === '') {
                $selectedFileDataJson = null;
            }

            $appliedFileNumber = $request->input('applied_file_number') ?: ($selectedFileData['fileno'] ?? $fileNo);
            $schemeNumber = $request->input('scheme_no');
            $mixedTypeJson = $mixedTypeDetails ? json_encode($mixedTypeDetails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
            $surveyPlanPath = $documentPayload['survey_plan']['path'] ?? null;

            $paymentDate = $request->input('payment_date')
                ?? $request->input('application_fee_payment_date')
                ?? $request->input('processing_fee_payment_date')
                ?? $request->input('site_plan_fee_payment_date');

            $receiptNumber = $request->input('receipt_number')
                ?? $request->input('application_fee_receipt_number')
                ?? $request->input('processing_fee_receipt_number')
                ?? $request->input('site_plan_fee_receipt_number');

            $receiptDate = $request->input('application_fee_payment_date')
                ?? $request->input('processing_fee_payment_date')
                ?? $request->input('site_plan_fee_payment_date');

            // Create complete data array for update
            $data = [
                'applicant_type' => $request->input('applicantType'),
                'applicant_title' => $request->input('applicant_title'),
                'first_name' => $request->input('first_name'),
                'middle_name' => $request->input('middle_name'),
                'surname' => $request->input('surname'),
                'corporate_name' => $request->input('corporate_name'),
                'rc_number' => $request->input('rc_number'),
                'multiple_owners_names' => $multipleOwnersNamesHasValue ? json_encode($multipleOwnersNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_address' => $multipleOwnersAddressesHasValue ? json_encode($multipleOwnersAddresses, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_passport' => $multipleOwnersPassportHasValue ? json_encode($multipleOwnersPassportPaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_email' => $multipleOwnersEmailsHasValue ? json_encode($multipleOwnersEmails, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_phone' => $multipleOwnersPhonesHasValue ? json_encode($multipleOwnersPhones, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_identification_type' => $multipleOwnersIdentificationTypesHasValue ? json_encode($multipleOwnersIdentificationTypes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'multiple_owners_identification_image' => $multipleOwnersIdImagesHasValue ? json_encode($multipleOwnersIdImagePaths, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'passport' => $passportPath,
                'id_document' => $idDocumentPath,
                'fileno' => $fileNo,
                'np_fileno' => $npFileNo,
                'address' => $request->input('address'),
                'address_house_no' => $request->input('address_house_no'),
                'address_street_name' => $request->input('owner_street_name'),
                'address_district' => $request->input('owner_district'),
                'address_lga' => $request->input('owner_lga'),
                'address_state' => $request->input('owner_state'),
                'phone_number' => $phoneNumber,
                'email' => $request->input('owner_email'),
                'identification_type' => $request->input('idType'),
                'identification_others' => $request->input('idType') === 'others' ? $request->input('identification_others') : null,
                'owner_fullname' => $ownerFullName,
                'property_house_no' => $request->input('property_house_no'),
                'property_plot_no' => $request->input('property_plot_no'),
                'scheme_no' => $schemeNumber,
                'scheme_number' => $schemeNumber,
                'property_street_name' => $request->input('property_street_name'),
                'property_district' => $request->input('property_district'),
                'property_lga' => $request->input('property_lga'),
                'property_state' => $request->input('property_state'),
                'applied_file_number' => $appliedFileNumber,
                'selected_file_id' => $selectedFileId,
                'selected_file_type' => $selectedFileType,
                'selected_file_data' => $selectedFileDataJson,
                'plot_size' => $request->input('plot_size'),
                'NoOfUnits' => $request->filled('units_count') ? (int) $request->input('units_count') : null,
                'NoOfBlocks' => $request->filled('blocks_count') ? (int) $request->input('blocks_count') : null,
                'NoOfSections' => $request->filled('sections_count') ? (int) $request->input('sections_count') : null,
                'application_fee' => $request->input('application_fee'),
                'processing_fee' => $request->input('processing_fee'),
                'site_plan_fee' => $request->input('site_plan_fee'),
                'payment_date' => $paymentDate,
                'receipt_number' => $receiptNumber,
                'receipt_date' => $receiptDate,
                'comments' => $request->input('comments'),
                'commercial_type' => $resolvedCommercialType,
                'residential_type' => $resolvedResidenceType,
                'industrial_type' => $resolvedIndustrialType,
                'land_use' => $landUseForStorage,
                'ownershipType' => $ownershipType,
                'ownership_type' => $ownershipType,
                'ownership' => $ownershipType,
                'ownership_type_others_text' => $ownershipTypeOther,
                'mixed_type' => $mixedTypeJson,
                'survey_plan' => $surveyPlanPath,
                'application_date' => $request->input('application_date') ?: now()->toDateString(),
                'application_status' => 'Pending',
                'applicationID' => date('Y').'-'.str_pad($applicationId, 2, '0', STR_PAD_LEFT),
                'created_at' => now(),
                'updated_at' => now(),
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'sys_date' => now(),
                'Payment_Status' => $paymentDate ? 'Paid' : 'Pending',
                'documents' => !empty($documentPayload) ? json_encode($documentPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'shared_areas' => $sharedAreas,
            ];

            // Log the data being inserted
            Log::info('Data being inserted into DB', [
                'document_sources' => array_keys($documentPayload),
                'scan_upload_count' => count($scanUploadPayload),
                'edms_document_total' => count($edmsDocuments),
                'np_fileno' => $npFileNo,
                'fileno' => $fileNo,
                'owner_fullname' => $ownerFullName,
                'selected_file_id' => $selectedFileId,
                'selected_file_type' => $selectedFileType,
                'land_use' => $landUseForStorage,
                'payment_date' => $paymentDate,
            ]);

            // Update the application with complete data
            DB::connection('sqlsrv')->table('mother_applications')->where('id', $applicationId)->update($data);

            // Mark file number reservation as used if it exists
            if ($npFileNo) {
                $this->reservationService->markAsUsed($npFileNo, $applicationId);
                Log::info('File number reservation marked as used', [
                    'np_fileno' => $npFileNo,
                    'application_id' => $applicationId
                ]);
            }

            // Get authenticated user information
            $createdBy = Auth::user() ? Auth::user()->first_name : null;
            
            // Use helper class to generate and insert file numbers
            $fileData = [
                'mlsFileNo' => $mlsFileNo,
                'kangisFileNo' => $kangisFileNo,
                'newKangisFileNo' => $newKangisFileNo
            ];
            
            $sectionalTitleFileNo = SectionalTitleHelper::generateAndInsertFileNumber(
                $applicationId, 
                $createdBy, 
                $fileData
            );
            
            // Insert additional data into eRegistry table after successful application creation
            try {
                $eRegistryUpdateData = [
                    'application_id' => $applicationId,
                    'sub_application_id' => null, // This is for main application only
                    'MLSFileNo' => $mlsFileNo,
                    'KANGISFileNo' => $kangisFileNo,
                    'NEWKangisFileNo' => $newKangisFileNo,
                    'npFileno' => $npFileNo,
                    'ST_fileNO' => $fileNo,
                    'Commissioning_Date' => now(), // Current date
                    'Current_Office' => 'ST Registry',
                    'updated_at' => now()
                ];
                
                // Update the existing eRegistry record with additional fields
                DB::connection('sqlsrv')->table('eRegistry')
                    ->where('application_id', $applicationId)
                    ->update($eRegistryUpdateData);
                    
                Log::info('eRegistry updated with additional fields for primary application', [
                    'application_id' => $applicationId,
                    'npFileno' => $npFileNo
                ]);
            } catch (Exception $eRegistryError) {
                Log::error('Error updating eRegistry with additional fields', [
                    'application_id' => $applicationId,
                    'error' => $eRegistryError->getMessage()
                ]);
            }
            
            // Process records/buyers if they exist in the request
            \Log::info('Checking for buyers records in request', [
                'has_records' => $request->has('records'),
                'records_data' => $request->input('records'),
                'has_csv_file' => $request->hasFile('csv_file'),
                'all_request_data' => $request->all()
            ]);
            
            if ($request->has('records') && is_array($request->input('records'))) {
                $buyersData = $request->input('records');
                
                \Log::info('About to process buyers data', [
                    'application_id' => $applicationId,
                    'records_count' => count($buyersData),
                    'records_data' => $buyersData
                ]);
                
                // 1. Store buyers as JSON in final_conveyance column
                $finalConveyanceJson = json_encode($buyersData);
                DB::connection('sqlsrv')->table('mother_applications')
                    ->where('id', $applicationId)
                    ->update(['final_conveyance' => $finalConveyanceJson]);
                
                // 2. Process each buyer for database insertion using helper
                SectionalTitleHelper::insertBuyers($applicationId, $buyersData);
                
                Log::info('Buyers data processed successfully', [
                    'application_id' => $applicationId,
                    'buyers_count' => count($buyersData),
                    'json_stored' => !empty($finalConveyanceJson)
                ]);
            } elseif ($request->hasFile('csv_file')) {
                // Process CSV file if uploaded during form submission
                \Log::info('Processing CSV file during form submission', [
                    'application_id' => $applicationId
                ]);
                
                try {
                    $csvFile = $request->file('csv_file');
                    $csvData = [];
                    
                    if (($handle = fopen($csvFile->getRealPath(), 'r')) !== FALSE) {
                        $headers = fgetcsv($handle, 1000, ',');
                        
                        if ($headers) {
                            // Normalize headers to lowercase for easier matching
                            $headers = array_map('strtolower', array_map('trim', $headers));
                            
                            while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                                if (count($data) >= count($headers)) {
                                    $buyer = [];
                                    
                                    foreach ($headers as $index => $header) {
                                        $value = isset($data[$index]) ? trim($data[$index]) : '';
                                        
                                        switch($header) {
                                            case 'title':
                                                $buyer['buyerTitle'] = $value;
                                                break;
                                            case 'first name':
                                            case 'first_name':
                                                $buyer['firstName'] = $value;
                                                break;
                                            case 'middle name':
                                            case 'middle_name':
                                                $buyer['middleName'] = $value;
                                                break;
                                            case 'surname':
                                            case 'last name':
                                            case 'last_name':
                                                $buyer['surname'] = $value;
                                                break;
                                            case 'address':
                                                $buyer['buyerAddress'] = $value;
                                                break;
                                            case 'email':
                                                $buyer['buyerEmail'] = $value;
                                                break;
                                            case 'phone':
                                                $buyer['buyerPhone'] = $value;
                                                break;
                                            case 'unit number':
                                            case 'unit_number':
                                            case 'unit no':
                                            case 'unit_no':
                                                $buyer['unitNumber'] = $value;
                                                $buyer['unit_no'] = $value; // For form compatibility
                                                break;
                                            case 'unit type':
                                            case 'unit_type':
                                                $buyer['unitType'] = $value;
                                                break;
                                            case 'unit measurement':
                                            case 'unit_measurement':
                                            case 'measurement':
                                                $buyer['unitMeasurement'] = $value;
                                                break;
                                            case 'section number':
                                            case 'section_number':
                                            case 'section':
                                            case 'sectionno':
                                                $buyer['sectionNumber'] = $value;
                                                break;
                                        }
                                    }
                                    
                                    // Create full buyer name for database storage
                                    $fullName = trim(implode(' ', array_filter([
                                        $buyer['buyerTitle'] ?? '',
                                        $buyer['firstName'] ?? '',
                                        $buyer['middleName'] ?? '',
                                        $buyer['surname'] ?? ''
                                    ])));
                                    
                                    if (!empty($fullName)) {
                                        $buyer['buyerName'] = $fullName;
                                    }
                                    
                                    // Only add buyer if essential fields are present
                                    if (!empty($buyer['buyerTitle']) && !empty($buyer['firstName']) && !empty($buyer['surname'])) {
                                        $csvData[] = $buyer;
                                    }
                                }
                            }
                        }
                        
                        fclose($handle);
                    }
                    
                    if (!empty($csvData)) {
                        \Log::info('CSV data parsed successfully', [
                            'application_id' => $applicationId,
                            'records_count' => count($csvData),
                            'records_data' => $csvData
                        ]);
                        
                        // 1. Store CSV data as JSON in final_conveyance column
                        $finalConveyanceJson = json_encode($csvData);
                        DB::connection('sqlsrv')->table('mother_applications')
                            ->where('id', $applicationId)
                            ->update(['final_conveyance' => $finalConveyanceJson]);
                        
                        // 2. Process each buyer for database insertion using helper
                        SectionalTitleHelper::insertBuyers($applicationId, $csvData);
                        
                        Log::info('CSV buyers data processed successfully', [
                            'application_id' => $applicationId,
                            'buyers_count' => count($csvData),
                            'json_stored' => !empty($finalConveyanceJson)
                        ]);
                    } else {
                        \Log::warning('No valid buyer data found in CSV file', [
                            'application_id' => $applicationId
                        ]);
                    }
                } catch (\Exception $csvError) {
                    \Log::error('Error processing CSV file during form submission', [
                        'application_id' => $applicationId,
                        'error' => $csvError->getMessage()
                    ]);
                }
            } else {
                \Log::warning('No buyer records or CSV file found in request', [
                    'application_id' => $applicationId,
                    'request_keys' => array_keys($request->all())
                ]);
            }
            
            // Insert billing record for primary application
            $applicationRefId = date('Y').'-'.str_pad($applicationId, 2, '0', STR_PAD_LEFT);
            $billingData = [
                'Sectional_Title_File_No' => $sectionalTitleFileNo,
                'ref_id' => $applicationRefId,
                'application_id' => $applicationId,
                'sub_application_id' => null,
                'Scheme_Application_Fee' => $request->input('application_fee'),
                'Site_Plan_Fee' => $request->input('site_plan_fee'),
                'Processing_Fee' => $request->input('processing_fee'),
                'survey_fee' => null,
                'Betterment_Charges' => null,
                'Unit_Application_Fees' => null,
                'Land_Use_Charge' => null,
                'property_value' => null,
                'Penalty_Fees' => null,
                'Payment_Status' => 'Paid',
                'created_at' => now(),
                'updated_at' => now(),
                'betterment_rate' => null
            ];

            // Insert billing record
            DB::connection('sqlsrv')->table('billing')->insert($billingData);

            // Log successful submission
            Log::info('Application submitted successfully', [
                'application_id' => $applicationId,
                'sectional_title_file_no' => $sectionalTitleFileNo,
                'billing_inserted' => true
            ]);

            // Auto-create file indexing record for EDMS workflow
            Log::info('About to create file indexing record', [
                'application_id' => $applicationId,
                'np_fileno' => $npFileNo
            ]);
            
            $fileIndexing = $this->createFileIndexingRecord($applicationId, $data, $npFileNo);
            
            Log::info('File indexing record creation result', [
                'application_id' => $applicationId,
                'file_indexing_created' => $fileIndexing ? true : false,
                'file_indexing_id' => $fileIndexing ? $fileIndexing->id : null
            ]);
            
            // Debug EDMS processing conditions
            Log::info('EDMS Processing Debug', [
                'application_id' => $applicationId,
                'file_indexing_exists' => $fileIndexing ? true : false,
                'file_indexing_id' => $fileIndexing ? $fileIndexing->id : null,
                'edms_documents_count' => count($edmsDocuments),
                'edms_documents_empty' => empty($edmsDocuments),
                'edms_documents_sample' => !empty($edmsDocuments) ? array_slice($edmsDocuments, 0, 2) : []
            ]);
            
            // Process uploaded documents and send to scanning table
            if ($fileIndexing && !empty($edmsDocuments)) {
                Log::info('Starting EDMS document processing', [
                    'file_indexing_id' => $fileIndexing->id,
                    'file_number' => $fileIndexing->file_number,
                    'document_count' => count($edmsDocuments)
                ]);
                
                // Use the file number from the created file indexing record to ensure consistency
                $this->processDocumentsForEDMS($fileIndexing->id, $edmsDocuments, $fileIndexing->file_number);
                
                Log::info('EDMS document processing completed', [
                    'file_indexing_id' => $fileIndexing->id,
                    'application_id' => $applicationId
                ]);
            } else {
                Log::warning('EDMS document processing skipped', [
                    'application_id' => $applicationId,
                    'file_indexing_exists' => $fileIndexing ? true : false,
                    'edms_documents_count' => count($edmsDocuments),
                    'reason' => !$fileIndexing ? 'No file indexing' : 'No EDMS documents'
                ]);
            }

            // Debug request type detection
            Log::info('Request type detection', [
                'is_ajax' => $request->ajax(),
                'expects_json' => $request->expectsJson(),
                'has_ajax_header' => $request->header('X-Requested-With') === 'XMLHttpRequest',
                'content_type' => $request->header('Content-Type'),
                'accept_header' => $request->header('Accept'),
                'all_headers' => $request->headers->all()
            ]);

            // Return appropriate response based on request type
            $isAjaxRequest = $request->ajax() || 
                           $request->expectsJson() || 
                           $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                           $request->wantsJson();
                           
            if ($isAjaxRequest) {
                // AJAX request - return JSON response
                Log::info('Returning JSON response for AJAX request');
                
                // Get the correct pagetyping redirect URL using edms.pagetyping route
                $pagetypingUrl = null;
                if ($fileIndexing) {
                    $pagetypingUrl = route('edms.pagetyping', $fileIndexing->id);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Application submitted successfully! Please proceed with page typing.',
                    'application_id' => $applicationId,
                    'sectional_title_file_no' => $sectionalTitleFileNo,
                    'file_indexing_id' => $fileIndexing ? $fileIndexing->id : null,
                    'redirect_url' => $pagetypingUrl ?: route('edms.index', $applicationId)
                ]);
            } else {
                // Regular form submission - return redirect
                Log::info('Returning redirect response for regular form submission');
                
                $redirectRoute = $fileIndexing ? 
                    route('edms.pagetyping', $fileIndexing->id) : 
                    route('edms.index', $applicationId);
                
                return redirect()->to($redirectRoute)
                    ->with('success', 'Application submitted successfully! Please proceed with page typing.')
                    ->with('application_id', $applicationId);
            }
        } catch (Exception $e) {
            // Enhanced error logging for debugging
            Log::error('Error submitting application form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'form_data' => $request->all()
            ]);

            // Return appropriate error response based on request type
            $isAjaxRequest = $request->ajax() || 
                           $request->expectsJson() || 
                           $request->header('X-Requested-With') === 'XMLHttpRequest' ||
                           $request->wantsJson();
                           
            if ($isAjaxRequest) {
                // AJAX request - return JSON error response
                Log::info('Returning JSON error response for AJAX request');
                return response()->json([
                    'success' => false,
                    'message' => 'Error submitting application: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            } else {
                // Regular form submission - return redirect with error
                Log::info('Returning redirect error response for regular form submission');
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Error submitting application: ' . $e->getMessage());
            }
        }
    }

    /**
     * Generate tracking ID for file indexing
     */
    private function generateTrackingId()
    {
        $segment1 = '';
        $segment2 = '';
        $characters = 'ABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
        
        // Generate first segment (8 characters)
        for ($i = 0; $i < 8; $i++) {
            $segment1 .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Generate second segment (5 characters)
        for ($i = 0; $i < 5; $i++) {
            $segment2 .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return "TRK-{$segment1}-{$segment2}";
    }

    /**
     * Create file indexing record for EDMS workflow
     */
    private function createFileIndexingRecord($applicationId, $applicationData, $npFileNo = null)
    {
        try {
            // Verify the application exists before creating file indexing
            $applicationExists = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->where('id', $applicationId)
                ->exists();
                
            if (!$applicationExists) {
                Log::warning('Cannot create file indexing - application does not exist', [
                    'application_id' => $applicationId
                ]);
                return null;
            }

            // Check if file indexing already exists
            $existingFileIndexing = FileIndexing::on('sqlsrv')
                ->where('main_application_id', $applicationId)
                ->first();
                
            if ($existingFileIndexing) {
                // Update existing record with correct npFileNo if provided
                if ($npFileNo && $existingFileIndexing->file_number !== $npFileNo) {
                    $oldFileNumber = $existingFileIndexing->file_number;
                    $existingFileIndexing->file_number = $npFileNo;
                    $existingFileIndexing->save();
                    
                    Log::info('Updated existing file indexing with correct file number', [
                        'application_id' => $applicationId,
                        'file_indexing_id' => $existingFileIndexing->id,
                        'old_file_number' => $oldFileNumber,
                        'new_file_number' => $npFileNo,
                        'passed_npFileNo' => $npFileNo
                    ]);
                } else {
                    Log::info('File indexing already exists for application', [
                        'application_id' => $applicationId,
                        'file_indexing_id' => $existingFileIndexing->id,
                        'current_file_number' => $existingFileIndexing->file_number,
                        'passed_npFileNo' => $npFileNo
                    ]);
                }
                return $existingFileIndexing;
            }

            // Generate file title from application data
            $name = '';
            if ($applicationData['applicant_type'] === 'individual') {
                $name = trim(($applicationData['first_name'] ?? '') . ' ' . ($applicationData['middle_name'] ?? '') . ' ' . ($applicationData['surname'] ?? ''));
            } elseif ($applicationData['applicant_type'] === 'corporate') {
                $name = $applicationData['corporate_name'] ?? '';
            } elseif ($applicationData['applicant_type'] === 'multiple') {
                $names = json_decode($applicationData['multiple_owners_names'] ?? '[]', true);
                if (is_array($names) && count($names) > 0) {
                    $name = $names[0] . ' et al.';
                }
            }
            
            $landUse = $applicationData['land_use'] ?? 'Property';
            $fileTitle = $name ? "{$name}" : "Application {$applicationId}";

            // Generate tracking ID
            $trackingId = $this->generateTrackingId();
            
            // Create file indexing record with proper database connection and correct field mappings
            $fileIndexing = FileIndexing::on('sqlsrv')->create([
                'main_application_id' => $applicationId,
                'subapplication_id' => null,
                'recertification_application_id' => null,
                'st_fillno' => null,
                'file_number_id' => null,
                'file_number' => $npFileNo ?? $applicationData['np_fileno'] ?? 'ST-TEMP-' . $applicationId,
                'file_title' => $fileTitle, // Applicant name as requested
                'land_use_type' => $applicationData['land_use'] ?? 'Residential',
                'plot_number' => $applicationData['property_plot_no'] ?? null,
                'district' => $applicationData['property_district'] ?? null,
                'lga' => $applicationData['property_lga'] ?? null,
                'registry' => 'ST Registry',
                'location' => $applicationData['property_lga'] ?? null,
                'status' => 'active',
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
                'tracking_id' => $trackingId,
                'has_cofo' => false,
                'is_merged' => false,
                'has_transaction' => false,
                'is_problematic' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Verify the file indexing record was created successfully
            if (!$fileIndexing || !$fileIndexing->id) {
                Log::error('Failed to create file indexing record', [
                    'application_id' => $applicationId,
                    'tracking_id' => $trackingId,
                    'file_title' => $fileTitle
                ]);
                return null;
            }

            Log::info('File indexing auto-created successfully', [
                'application_id' => $applicationId,
                'file_indexing_id' => $fileIndexing->id,
                'tracking_id' => $trackingId,
                'file_title' => $fileTitle,
                'file_number_used' => $fileIndexing->file_number,
                'passed_npFileNo' => $npFileNo,
                'data_npFileNo' => $applicationData['np_fileno'] ?? null,
                'data_fileno' => $applicationData['fileno'] ?? null,
                'created_by' => Auth::id(),
                'status' => 'active'
            ]);

            return $fileIndexing;

        } catch (Exception $e) {
            Log::error('Error creating file indexing record', [
                'application_id' => $applicationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Don't throw exception to avoid breaking the main flow
            return null;
        }
    }

    /**
     * Process uploaded documents for EDMS workflow
     */
    private function processDocumentsForEDMS($fileIndexingId, $documents, $fileNumber)
    {
        try {
            Log::info('Processing documents for EDMS workflow', [
                'file_indexing_id' => $fileIndexingId,
                'file_number' => $fileNumber,
                'documents_count' => count($documents)
            ]);

            // Create EDMS directory structure: EDMS/SCAN_UPLOAD/{fileNumber}/
            $edmsBasePath = "EDMS/SCAN_UPLOAD/{$fileNumber}";
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

                    // Generate sequential filename like: ST-COM-2025-01-002_0001.pdf
                    $sequenceStr = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
                    $newFilename = "{$fileNumber}_{$sequenceStr}.{$extension}";
                    $destinationPath = "{$edmsBasePath}/{$newFilename}";

                    // Copy file to EDMS structure with collision handling
                    try {
                        if (!Storage::disk('public')->exists($destinationPath)) {
                            Storage::disk('public')->copy($relativeSourcePath, $destinationPath);
                        } else {
                            // Handle filename collision by adding unique identifier
                            $newFilename = "{$fileNumber}_{$sequenceStr}_" . uniqid() . ".{$extension}";
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
                'file_number' => $fileNumber,
                'total_documents' => count($documents),
                'processed_documents' => $sequenceNumber - 1,
                'final_display_order' => $displayOrder - 1
            ]);

        } catch (Exception $e) {
            Log::error('Error processing documents for EDMS', [
                'file_indexing_id' => $fileIndexingId,
                'file_number' => $fileNumber,
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

    /**
     * Get survey plan for an application
     */
    public function getSurveyPlan($applicationId)
    {
        try {
            // Extract the actual application ID if it starts with 'mother_'
            $actualId = $applicationId;
            if (strpos($applicationId, 'mother_') === 0) {
                $actualId = str_replace('mother_', '', $applicationId);
            }
            
            // Fetch the application from mother_applications table
            $application = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->where('id', $actualId)
                ->first();
            
            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found.'
                ], 404);
            }
            
            // Check if documents exist and parse them
            if (!$application->documents) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found for this application.'
                ]);
            }
            
            // Parse the documents JSON
            $documents = json_decode($application->documents, true);
            
            if (!$documents || !isset($documents['survey_plan'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No survey plan found for this application.'
                ]);
            }
            
            $surveyPlan = $documents['survey_plan'];
            
            // Verify the file exists
            $filePath = storage_path('app/public/' . $surveyPlan['path']);
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Survey plan file not found on server.'
                ]);
            }
            
            return response()->json([
                'success' => true,
                'survey_plan' => $surveyPlan,
                'application' => [
                    'id' => $application->id,
                    'np_fileno' => $application->np_fileno,
                    'fileno' => $application->fileno,
                    'applicant_name' => $this->getApplicantName($application)
                ]
            ]);
            
        } catch (Exception $e) {
            Log::error('Error fetching survey plan', [
                'application_id' => $applicationId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error fetching survey plan: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Helper method to get applicant name
     */
    private function getApplicantName($application)
    {
        if ($application->applicant_type === 'individual') {
            return trim(($application->first_name ?? '') . ' ' . ($application->middle_name ?? '') . ' ' . ($application->surname ?? ''));
        } elseif ($application->applicant_type === 'corporate') {
            return $application->corporate_name ?? 'Corporate Applicant';
        } elseif ($application->applicant_type === 'multiple') {
            $names = json_decode($application->multiple_owners_names ?? '[]', true);
            if (is_array($names) && count($names) > 0) {
                return $names[0] . ' et al.';
            }
            return 'Multiple Owners';
        }
        
        return 'Unknown Applicant';
    }
    
    /**
     * Process CSV file for bulk buyer import
     */
    public function processCsv(Request $request)
    {
        try {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
                'application_id' => 'nullable|integer' // Optional application ID for immediate save
            ]);
            
            $file = $request->file('csv_file');
            $applicationId = $request->input('application_id');
            $csvData = [];
            
            if (($handle = fopen($file->getRealPath(), 'r')) !== FALSE) {
                $headers = fgetcsv($handle, 1000, ',');
                
                if (!$headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid CSV file format.'
                    ]);
                }
                
                // Normalize headers to lowercase for easier matching
                $headers = array_map('strtolower', array_map('trim', $headers));
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if (count($data) >= count($headers)) {
                        $buyer = [];
                        
                        foreach ($headers as $index => $header) {
                            $value = isset($data[$index]) ? trim($data[$index]) : '';
                            
                            switch($header) {
                                case 'title':
                                    $buyer['buyerTitle'] = $value;
                                    break;
                                case 'first name':
                                case 'first_name':
                                    $buyer['firstName'] = $value;
                                    break;
                                case 'middle name':
                                case 'middle_name':
                                    $buyer['middleName'] = $value;
                                    break;
                                case 'surname':
                                case 'last name':
                                case 'last_name':
                                    $buyer['surname'] = $value;
                                    break;
                                case 'unit number':
                                case 'unit_number':
                                case 'unit no':
                                case 'unit_no':
                                    $buyer['unitNumber'] = $value;
                                    $buyer['unit_no'] = $value; // For form compatibility
                                    break;
                                case 'land use':
                                case 'land_use':
                                case 'landuse':
                                    $buyer['landUse'] = $value;
                                    break;
                                case 'unit measurement':
                                case 'unit_measurement':
                                case 'measurement':
                                    $buyer['unitMeasurement'] = $value;
                                    break;
                            }
                        }
                        
                        // Create full buyer name for database storage
                        $fullName = trim(implode(' ', array_filter([
                            $buyer['buyerTitle'] ?? '',
                            $buyer['firstName'] ?? '',
                            $buyer['middleName'] ?? '',
                            $buyer['surname'] ?? ''
                        ])));
                        
                        if (!empty($fullName)) {
                            $buyer['buyerName'] = $fullName;
                        }
                        
                        // Only add buyer if essential fields are present
                        if (!empty($buyer['buyerTitle']) && !empty($buyer['firstName']) && !empty($buyer['surname'])) {
                            $csvData[] = $buyer;
                        }
                    }
                }
                
                fclose($handle);
            }
            
            if (empty($csvData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid buyer data found in CSV file.'
                ]);
            }
            
            // If application_id is provided, save directly to database
            if ($applicationId) {
                try {
                    // Verify application exists
                    $application = DB::connection('sqlsrv')->table('mother_applications')
                        ->where('id', $applicationId)
                        ->first();
                        
                    if (!$application) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Application not found.'
                        ]);
                    }
                    
                    // Store CSV data as JSON in final_conveyance column
                    $finalConveyanceJson = json_encode($csvData);
                    DB::connection('sqlsrv')->table('mother_applications')
                        ->where('id', $applicationId)
                        ->update(['final_conveyance' => $finalConveyanceJson]);
                    
                    Log::info('CSV data stored in final_conveyance column', [
                        'application_id' => $applicationId,
                        'buyer_count' => count($csvData)
                    ]);
                    
                    // Clear existing buyers for this application (optional - uncomment if needed)
                    // DB::connection('sqlsrv')->table('buyer_list')->where('application_id', $applicationId)->delete();
                    
                    $insertedCount = 0;
                    $skippedCount = 0;
                    
                    foreach ($csvData as $buyerData) {
                        // Check if buyer already exists to avoid duplicates
                        $existingBuyer = DB::connection('sqlsrv')->table('buyer_list')
                            ->where('application_id', $applicationId)
                            ->where('buyer_name', $buyerData['buyerName'])
                            ->where('unit_no', $buyerData['unit_no'] ?? $buyerData['unitNumber'] ?? '')
                            ->first();
                            
                        if ($existingBuyer) {
                            $skippedCount++;
                            continue;
                        }
                        
                        // Insert buyer using the helper function
                        try {
                            $records = [
                                [
                                    'buyerTitle' => $buyerData['buyerTitle'] ?? '',
                                    'firstName' => $buyerData['firstName'] ?? '',
                                    'middleName' => $buyerData['middleName'] ?? '',
                                    'surname' => $buyerData['surname'] ?? '',
                                    'buyerName' => $buyerData['buyerName'],
                                    'unit_no' => $buyerData['unit_no'] ?? $buyerData['unitNumber'] ?? '',
                                    'unitMeasurement' => $buyerData['unitMeasurement'] ?? ''
                                ]
                            ];
                            
                            \App\Helpers\SectionalTitleHelper::insertBuyers($applicationId, $records);
                            $insertedCount++;
                            
                        } catch (\Exception $e) {
                            \Log::error('Failed to insert buyer: ' . $e->getMessage(), [
                                'buyer_data' => $buyerData,
                                'application_id' => $applicationId
                            ]);
                            $skippedCount++;
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'message' => "Successfully processed CSV file. Inserted: {$insertedCount}, Skipped: {$skippedCount}",
                        'data' => $csvData,
                        'stats' => [
                            'total' => count($csvData),
                            'inserted' => $insertedCount,
                            'skipped' => $skippedCount
                        ]
                    ]);
                    
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Database error: ' . $e->getMessage()
                    ]);
                }
            }
            
            // If no application_id, return data for form population (existing behavior)
            return response()->json([
                'success' => true,
                'message' => 'CSV processed successfully. Ready to submit form.',
                'data' => $csvData,
                'count' => count($csvData)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing CSV: ' . $e->getMessage()
            ]);
        }
        Log::error('CSV Processing Error: ' . $e->getMessage());
        
        $html = '<div class="p-2 bg-red-100 border border-red-400 text-red-700 rounded">';
        $html .= '<strong>❌ Import Failed!</strong><br>';
        $html .= 'Error: ' . $e->getMessage();
        $html .= '</div>';
        
        return response($html);
    }
    
    /**
     * Download CSV template for buyers
     */
    public function downloadTemplate()
    {
        $csvContent = "title,first name,middle name,surname,unit number,land use,unit measurement\r\n";
        $csvContent .= "Mr.,John,Michael,Doe,A001,Residential,50sqm\r\n";
        $csvContent .= "Mrs.,Jane,Elizabeth,Smith,B002,Commercial,75sqm\r\n";
        $csvContent .= "Dr.,Robert,James,Brown,C003,Mixed Use,100sqm\r\n";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="buyers_template.csv"',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
        ];
        
        return response($csvContent, 200, $headers);
    }
    
    /**
     * Test HTMX connection
     */
    public function testHtmx()
    {
        $html = '<div class="p-2 bg-green-100 border border-green-400 text-green-700 rounded">';
        $html .= '<strong>✅ HTMX Connection Successful!</strong><br>';
        $html .= 'Server time: ' . now()->format('Y-m-d H:i:s') . '<br>';
        $html .= 'User: ' . Auth::user()->name . '<br>';
        $html .= 'HTMX is working properly.';
        $html .= '</div>';
        
        return response($html);
    }
    
    /**
     * Test CSV processing with sample data
     */
    public function testCsv()
    {
        try {
            // Sample CSV data
            $sampleData = [
                [
                    'buyerTitle' => 'Mr.',
                    'firstName' => 'John',
                    'middleName' => 'Michael',
                    'surname' => 'Doe',
                    'buyerAddress' => '123 Test St',
                    'buyerEmail' => 'john.doe@test.com',
                    'buyerPhone' => '1234567890',
                    'unitNumber' => 'A001',
                    'unitType' => 'Apartment',
                    'unitMeasurement' => '50sqm'
                ],
                [
                    'buyerTitle' => 'Mrs.',
                    'firstName' => 'Jane',
                    'middleName' => 'Elizabeth',
                    'surname' => 'Smith',
                    'buyerAddress' => '456 Test Ave',
                    'buyerEmail' => 'jane.smith@test.com',
                    'buyerPhone' => '0987654321',
                    'unitNumber' => 'B002',
                    'unitType' => 'Condo',
                    'unitMeasurement' => '75sqm'
                ]
            ];
            
            $html = '<div class="p-2 bg-blue-100 border border-blue-400 text-blue-700 rounded">';
            $html .= '<strong>✅ CSV Test Successful!</strong><br>';
            $html .= 'Generated ' . count($sampleData) . ' sample buyers:<br>';
            
            foreach ($sampleData as $index => $buyer) {
                $html .= '<div class="mt-1 text-xs">';
                $html .= ($index + 1) . '. ' . $buyer['buyerTitle'] . ' ' . $buyer['firstName'] . ' ' . $buyer['surname'];
                $html .= ' - ' . $buyer['unitNumber'] . ' (' . $buyer['unitType'] . ')';
                $html .= '</div>';
            }
            
            $html .= '<div class="mt-2 text-xs"><em>This simulates CSV processing without actual file upload.</em></div>';
            $html .= '</div>';
            
            // Also update buyers data via JavaScript
            $html .= '<script>';
            $html .= 'if (typeof updateBuyersData === "function") {';
            $html .= '  updateBuyersData(' . json_encode($sampleData) . ');';
            $html .= '  console.log("Test data sent to Alpine.js");';
            $html .= '} else {';
            $html .= '  console.log("updateBuyersData function not found");';
            $html .= '}';
            $html .= '</script>';
            
            return response($html);
            
        } catch (Exception $e) {
            $html = '<div class="p-2 bg-red-100 border border-red-400 text-red-700 rounded">';
            $html .= '<strong>❌ CSV Test Failed!</strong><br>';
            $html .= 'Error: ' . $e->getMessage();
            $html .= '</div>';
            
            return response($html);
        }
    }

    /**
     * Display the print page for an application
     */
    public function printPage(Request $request)
    {
        // Get all the application data from the request
        $data = [
            'applicationId' => $request->get('applicationId', 'APP-' . rand(10000, 99999)),
            'landUse' => $request->get('landUse', 'Residential'),
            'applicantType' => $request->get('applicantType'),
            'applicantName' => $request->get('applicantName'),
            'applicantEmail' => $request->get('applicantEmail'),
            'applicantPhone' => $request->get('applicantPhone'),
            'applicantAddress' => $request->get('applicantAddress'),
            'residenceType' => $request->get('residenceType'),
            'units' => $request->get('units'),
            'blocks' => $request->get('blocks'),
            'sections' => $request->get('sections'),
            'fileNumber' => $request->get('fileNumber'),
            'propertyHouseNo' => $request->get('propertyHouseNo'),
            'propertyPlotNo' => $request->get('propertyPlotNo'),
            'propertyStreet' => $request->get('propertyStreet'),
            'propertyDistrict' => $request->get('propertyDistrict'),
            'propertyLGA' => $request->get('propertyLGA'),
            'propertyState' => $request->get('propertyState'),
            'propertyFullAddress' => $request->get('propertyFullAddress'),
            'applicationFee' => $request->get('applicationFee'),
            'processingFee' => $request->get('processingFee'),
            'sitePlanFee' => $request->get('sitePlanFee'),
            'totalFee' => $request->get('totalFee'),
            'receiptNumber' => $request->get('receiptNumber'),
            'paymentDate' => $request->get('paymentDate'),
            'documents' => $request->get('documents', [])
        ];

        return view('primaryform.print-page', $data);
    }

    /**
     * Get preview serial number for display (read-only, doesn't update database)
     * 
     * @param string $landUse - COMMERCIAL, RESIDENTIAL, INDUSTRIAL, MIXED
     * @param int $year - Year for the file number
     * @return int - Next available serial number for preview
     */
    private function getPreviewSerialNumber($landUse, $year)
    {
        try {
            // Get current serial from database without updating
            $currentSerial = DB::connection('sqlsrv')
                ->table('land_use_serials')
                ->where('land_use_type', $landUse)
                ->where('year', $year)
                ->value('current_serial');
            
            // Return next serial (current + 1) for preview
            return ($currentSerial ?? 0) + 1;
            
        } catch (\Exception $e) {
            Log::warning('Error getting preview serial number', [
                'error' => $e->getMessage(),
                'land_use' => $landUse,
                'year' => $year
            ]);
            
            // Fallback: assume serial 1
            return 1;
        }
    }

    /**
     * Get the next primary file number based on SUA applications for the specified land use and year
     * 
     * @param string $landUse - COMMERCIAL, RESIDENTIAL, INDUSTRIAL, MIXED
     * @param int $year - Year for the file number
     * @return string - Next primary file number (incremented from latest SUA)
     */
    private function getLatestSUAPrimaryFileNo($landUse, $year)
    {
        try {
            // Get the latest sequence number from sua_file_numbers table
            $latestEntry = DB::connection('sqlsrv')
                ->table('sua_file_numbers')
                ->where('land_use_full', $landUse)
                ->where('year', $year)
                ->orderBy('sequence_number', 'desc')
                ->first();
            
            $landUseCode = match($landUse) {
                'COMMERCIAL' => 'COM',
                'INDUSTRIAL' => 'IND', 
                'RESIDENTIAL' => 'RES',
                'MIXED' => 'MIXED',
                default => 'COM'
            };
            
            if ($latestEntry) {
                // Increment the sequence number for the next file number
                $nextSequence = $latestEntry->sequence_number + 1;
                return "ST-{$landUseCode}-{$year}-{$nextSequence}";
            }
            
            // If no SUA entries found, start with sequence 1
            return "ST-{$landUseCode}-{$year}-1";
            
        } catch (\Exception $e) {
            Log::warning('Error getting next SUA primary file number', [
                'error' => $e->getMessage(),
                'land_use' => $landUse,
                'year' => $year
            ]);
            
            // Fallback: generate basic file number
            $landUseCode = match($landUse) {
                'COMMERCIAL' => 'COM',
                'INDUSTRIAL' => 'IND', 
                'RESIDENTIAL' => 'RES',
                'MIXED' => 'MIXED',
                default => 'COM'
            };
            
            return "ST-{$landUseCode}-{$year}-1";
        }
    }

    /**
     * Extract serial number from file number (e.g., "ST-COM-2025-5" returns 5)
     * 
     * @param string $fileNo - File number to extract from
     * @return int - Serial number
     */
    private function extractSerialFromFileNo($fileNo)
    {
        try {
            // Split by dash and get the last part
            $parts = explode('-', $fileNo);
            if (count($parts) >= 4) {
                return (int)$parts[3]; // ST-COM-2025-5 -> 5
            }
            
            return 1; // Fallback
            
        } catch (\Exception $e) {
            Log::warning('Error extracting serial from file number', [
                'error' => $e->getMessage(),
                'file_no' => $fileNo
            ]);
            
            return 1; // Fallback
        }
    }

    /**
     * Get next serial number for file number generation (atomic method - updates database)
     * 
     * @param string $landUse - COMMERCIAL, RESIDENTIAL, INDUSTRIAL, MIXED
     * @param int $year - Year for the file number
     * @return int - Next available serial number
     */
    private function getNextSerialNumber($landUse, $year)
    {
        try {
            // Use stored procedure for atomic serial generation
            $result = DB::connection('sqlsrv')->select(
                'EXEC GetNextFileSerial ?, ?', 
                [$landUse, $year]
            );
            
            // If stored procedure exists and works, use it
            if (!empty($result) && isset($result[0]->NextSerial)) {
                return $result[0]->NextSerial;
            }
        } catch (\Exception $e) {
            Log::warning('Stored procedure GetNextFileSerial not available, using fallback method', [
                'error' => $e->getMessage(),
                'land_use' => $landUse,
                'year' => $year
            ]);
        }
        
        // Fallback method: Use land_use_serials table directly
        return $this->getNextSerialFallback($landUse, $year);
    }

    /**
     * Fallback method for serial number generation using direct table operations
     * 
     * @param string $landUse
     * @param int $year
     * @return int
     */
    private function getNextSerialFallback($landUse, $year)
    {
        DB::connection('sqlsrv')->beginTransaction();
        
        try {
            // Check if record exists for this land use and year
            $serialRecord = DB::connection('sqlsrv')
                ->table('land_use_serials')
                ->where('land_use_type', $landUse)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();
            
            if ($serialRecord) {
                // Update existing record
                $nextSerial = $serialRecord->current_serial + 1;
                
                DB::connection('sqlsrv')
                    ->table('land_use_serials')
                    ->where('land_use_type', $landUse)
                    ->where('year', $year)
                    ->update([
                        'current_serial' => $nextSerial,
                        'updated_at' => now()
                    ]);
            } else {
                // Create new record for this land use and year
                // Normalize land use first
                $normalizedLandUse = match(strtoupper(trim($landUse))) {
                    'COMMERCIAL', 'COMMERCIAL USE' => 'COMMERCIAL',
                    'INDUSTRIAL', 'INDUSTRIAL USE' => 'INDUSTRIAL', 
                    'RESIDENTIAL', 'RESIDENTIAL USE' => 'RESIDENTIAL',
                    'MIXED', 'MIXED USE' => 'MIXED',
                    default => 'COMMERCIAL'
                };
                
                $prefix = match($normalizedLandUse) {
                    'COMMERCIAL' => 'ST-COM',
                    'INDUSTRIAL' => 'ST-IND', 
                    'RESIDENTIAL' => 'ST-RES',
                    'MIXED' => 'ST-MIXED',
                    default => 'ST-COM'
                };
                
                Log::info('Creating new land use serial record', [
                    'input_land_use' => $landUse,
                    'normalized' => $normalizedLandUse,
                    'prefix' => $prefix
                ]);
                
                DB::connection('sqlsrv')
                    ->table('land_use_serials')
                    ->insert([
                        'land_use_type' => $normalizedLandUse, // Use normalized value
                        'prefix' => $prefix,
                        'year' => $year,
                        'current_serial' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                
                $nextSerial = 1;
            }
            
            DB::connection('sqlsrv')->commit();
            return $nextSerial;
            
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollback();
            Log::error('Error generating serial number', [
                'error' => $e->getMessage(),
                'land_use' => $landUse,
                'year' => $year
            ]);
            
            // Ultimate fallback: count existing records (not atomic but works)
            return DB::connection('sqlsrv')
                ->table('mother_applications')
                ->where('land_use', $landUse)
                ->whereYear('created_at', $year)
                ->whereNotNull('np_fileno')
                ->count() + 1;
        }
    }

    /**
     * Generate complete file number for given land use and serial
     * 
     * @param string $landUse
     * @param int $serial
     * @param int $year
     * @return string
     */
    private function generateFileNumber($landUse, $serial, $year)
    {
        // Normalize land use first
        $normalizedLandUse = match(strtoupper(trim($landUse))) {
            'COMMERCIAL', 'COMMERCIAL USE' => 'COMMERCIAL',
            'INDUSTRIAL', 'INDUSTRIAL USE' => 'INDUSTRIAL', 
            'RESIDENTIAL', 'RESIDENTIAL USE' => 'RESIDENTIAL',
            'MIXED', 'MIXED USE' => 'MIXED',
            default => 'COMMERCIAL'
        };
        
        $landUseCode = match($normalizedLandUse) {
            'COMMERCIAL' => 'COM',
            'INDUSTRIAL' => 'IND', 
            'RESIDENTIAL' => 'RES',
            'MIXED' => 'MIXED',
            default => 'COM'
        };
        
        Log::info('Generating file number', [
            'input_land_use' => $landUse,
            'normalized' => $normalizedLandUse,
            'code' => $landUseCode,
            'serial' => $serial,
            'year' => $year,
            'generated' => "ST-{$landUseCode}-{$year}-{$serial}"
        ]);
        
        return "ST-{$landUseCode}-{$year}-{$serial}";
    }

    /**
     * Save shared areas to the shared_utilities table
     */
    private function saveSharedUtilitiesToTable(Request $request, $applicationId): void
    {
        if (!$request->has('shared_areas') || !is_array($request->input('shared_areas'))) {
            return;
        }

        $sharedAreasArray = $request->input('shared_areas');

        // Remove existing utilities for this application
        DB::connection('sqlsrv')->table('shared_utilities')
            ->where('application_id', $applicationId)
            ->whereNull('sub_application_id')
            ->delete();

        // Insert new utilities
        foreach (array_values($sharedAreasArray) as $index => $utility) {
            if (!empty(trim($utility))) {
                DB::connection('sqlsrv')->table('shared_utilities')->insert([
                    'application_id' => $applicationId,
                    'sub_application_id' => null,
                    'utility_type' => trim($utility),
                    'dimension' => null,
                    'count' => null,
                    'order' => $index + 1,
                    'created_by' => auth()->id() ?? 1,
                    'updated_by' => auth()->id() ?? 1,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Get application details by file number for autofill
     */
    public function getApplicationDetails($fileno)
    {
        try {
            $application = DB::connection('sqlsrv')
                ->table('mother_applications')
                ->where('fileno', $fileno)
                ->orWhere('np_fileno', $fileno)
                ->first();

            if (!$application) {
                return response()->json([
                    'success' => false,
                    'message' => 'Application not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $application->id,
                    'fileno' => $application->fileno,
                    'np_fileno' => $application->np_fileno,
                    'scheme_no' => $application->scheme_no,
                    'property_street_name' => $application->property_street_name,
                    'property_lga' => $application->property_lga,
                    'property_state' => $application->property_state,
                    'property_house_no' => $application->property_house_no,
                    'property_plot_no' => $application->property_plot_no,
                    'property_district' => $application->property_district,
                    'applicant_type' => $application->applicant_type,
                    'applicant_title' => $application->applicant_title,
                    'first_name' => $application->first_name,
                    'middle_name' => $application->middle_name,
                    'surname' => $application->surname,
                    'corporate_name' => $application->corporate_name,
                    'rc_number' => $application->rc_number,
                    'land_use' => $application->land_use
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching application details: ' . $e->getMessage()
            ], 500);
        }
    }
}
