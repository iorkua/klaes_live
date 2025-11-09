<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InstrumentController extends Controller
{
    public function index()
    {
        $PageTitle = 'Instrument Capture';
        $PageDescription = '';
        
        $instruments = DB::connection('sqlsrv')->table('instrument_registration')
            ->orderBy('created_at', 'desc')
            ->get();
        return view('instruments.index', compact('PageTitle', 'PageDescription', 'instruments'));
    }

    public function create()
    {
      $PageTitle = 'Instrument Capture';
      $PageDescription = 'Capture a new instrument ';

        return view('instruments.create', compact('PageTitle', 'PageDescription'));
    }
 
    public function store(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'instrument_type' => 'required|string',
                'Grantor' => 'required|string',
                'GrantorAddress' => 'required|string',
                'Grantee' => 'required|string',
                'GranteeAddress' => 'required|string',
                'instrumentDate' => 'required|date',
                'propertyDescription' => 'nullable|string',
                'solicitorName' => 'nullable|string',
                'solicitorAddress' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            // Normalize and collect file number inputs (supporting multiple sources)
            $mls = trim((string)($request->input('mlsFNo') ?? $request->input('MLSFileNo')));
            $kangis = trim((string)($request->input('kangisFileNo') ?? $request->input('KAGISFileNo') ?? $request->input('KAGISFileNO')));
            $newKangis = trim((string)($request->input('NewKANGISFileno') ?? $request->input('NewKANGISFileNo')));
            $tempFileno = trim((string)($request->input('temp_fileno') ?? $request->input('temporaryFileNo')));

            // Fallback: if all three official file numbers are empty but a generic fileno exists (smart selector), use it as MLS
            if ($mls === '' && $kangis === '' && $newKangis === '') {
                $generic = trim((string)$request->input('fileno', ''));
                if ($generic !== '') {
                    $mls = $generic;
                }
            }

            // If temp flag present and no explicit temp_fileno provided, use the temporaryFileNo value
            $isTemporary = filter_var($request->input('isTemporary', false), FILTER_VALIDATE_BOOLEAN) || $request->boolean('isTemporaryFileNo');
            if ($isTemporary && $tempFileno === '') {
                $tempFileno = trim((string)$request->input('temporaryFileNo', ''));
            }

            // Log for diagnostics
            Log::info('Instrument store request file numbers', [
                'mlsFNo' => $mls,
                'kangisFileNo' => $kangis,
                'NewKANGISFileno' => $newKangis,
                'temp_fileno' => $tempFileno,
                'fileno_fallback' => $request->input('fileno')
            ]);
            
            // Format date for SQL Server
            $instrumentDate = $request->instrumentDate ? date('Y-m-d', strtotime($request->instrumentDate)) : null;
            
            // Create instrument record using DB facade
            $now = now()->format('Y-m-d H:i:s');
            
            $data = [
                'instrument_type' => $request->instrument_type,
                'MLSFileNo' => $mls !== '' ? $mls : null,
                'KAGISFileNO' => $kangis !== '' ? $kangis : null,
                'NewKANGISFileNo' => $newKangis !== '' ? $newKangis : null,
                'temp_fileno' => $tempFileno !== '' ? $tempFileno : null,
                'Grantor' => $request->Grantor,
                'GrantorAddress' => $request->GrantorAddress,
                'Grantee' => $request->Grantee,
                'GranteeAddress' => $request->GranteeAddress,
                'instrumentDate' => $instrumentDate,
                'propertyDescription' => $request->propertyDescription,
                'solicitorName' => $request->solicitorName,
                'solicitorAddress' => $request->solicitorAddress,
                'lga' => $request->lga,
                'district' => $request->district,
                'size' => $request->size,
                'plotNumber' => $request->plotNumber,
                'rootRegistrationNumber' => $request->rootRegistrationNumber,
                'particularsRegistrationNumber' => $request->particularsRegistrationNumber,
                'duration' => $request->duration,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            // Add Deed of Gift specific fields if the instrument type is Deed of Gift
            if ($request->instrument_type === 'Deed of Gift') {
                $data = array_merge($data, [
                    // Section A - Instrument Metadata
                    'instrumentNo' => $request->instrumentNo,
                    'landUse' => $request->landUse,
                    'dateOfExecution' => $request->dateOfExecution ? date('Y-m-d', strtotime($request->dateOfExecution)) : null,
                    'dateOfRegistration' => $request->dateOfRegistration ? date('Y-m-d', strtotime($request->dateOfRegistration)) : null,
                    
                    // Section B - Donor (Giver) Details
                    'donorPhone' => $request->donorPhone,
                    'donorNationality' => $request->donorNationality,
                    'donorIdDocument' => $request->donorIdDocument,
                    'donorIdNumber' => $request->donorIdNumber,
                    
                    // Section C - Donee (Receiver) Details
                    'doneePhone' => $request->doneePhone,
                    'doneeNationality' => $request->doneeNationality,
                    'doneeIdDocument' => $request->doneeIdDocument,
                    'doneeIdNumber' => $request->doneeIdNumber,
                    
                    // Section D - Gifted Property Information
                    'surveyPlanNo' => $request->surveyPlanNo,
                    'propertySize' => $request->propertySize,
                    'consideration' => $request->consideration,
                    'encumbrances' => $request->encumbrances,
                    'supportingDocs' => $request->supportingDocs,
                    
                    // Section E - Registration
                    'registrarName' => $request->registrarName,
                    'registrarSignature' => $request->registrarSignature,
                    'volumePageNo' => $request->volumePageNo,
                    'blockchainHash' => $request->blockchainHash,
                ]);
            }
            
            // Check if table exists
            $tableExists = DB::connection('sqlsrv')->select("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'instrument_registration'");
            
            if (empty($tableExists)) {
                return redirect()->back()->with('error', 'Database table not found. Please run migrations.')->withInput();
            }
            
            try {
                // Begin transaction to ensure data integrity
                DB::connection('sqlsrv')->beginTransaction();
                
                // Use the direct insert method with explicit commit
                $success = DB::connection('sqlsrv')->table('instrument_registration')->insert($data);
                
                if ($success) {
                    // Explicitly commit the transaction
                    DB::connection('sqlsrv')->commit();
                    
                    // Redirect to index with success message
                    return redirect()->route('instruments.index')->with('success', 'Instrument registered successfully');
                } else {
                    throw new \Exception('Failed to insert record');
                }
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::connection('sqlsrv')->rollBack();
                
                return redirect()->back()->with('error', 'Database error occurred: ' . $e->getMessage())->withInput();
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage())->withInput();
        }
    }

    public function generateParticulars()
    {
        try {
            // Get the last record from the instrument_registration table to determine the next sequence
            $lastRecord = DB::connection('sqlsrv')
                ->table('instrument_registration')
                ->whereNotNull('rootRegistrationNumber')
                ->orderBy('id', 'desc')
                ->first();
            
            if (!$lastRecord || !$lastRecord->rootRegistrationNumber) {
                // Initialize with starting values if no records exist
                $serial_no = 1;
                $page_no = 1;
                $volume_no = 1;
            } else {
                // Parse the last registration number
                $regParts = explode('/', $lastRecord->rootRegistrationNumber);
                $serial_no = (int)($regParts[0] ?? 0) + 1;
                $page_no = (int)($regParts[1] ?? 0) + 1;
                $volume_no = (int)($regParts[2] ?? 1);
                
                // If we've reached the maximum, reset and increment volume
                if ($serial_no > 300) {
                    $serial_no = 1;
                    $page_no = 1;
                    $volume_no++;
                }
            }
            
            // Format the particulars registration number
            $formatted = "{$serial_no}/{$page_no}/{$volume_no}";
            
            return response()->json([
                'success' => true,
                'rootRegistrationNumber' => $formatted,
                'serial_no' => $serial_no,
                'page_no' => $page_no,
                'volume_no' => $volume_no
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate particulars registration number: ' . $e->getMessage()
            ], 500);
        }
    }

    public function Coroi()
    {
        $title = 'Confirmation Of Instrument Registration';
        return view('coroi.index', compact('title'));
    }

    public function update(Request $request, $id)
    {
        // Here you would implement the update logic using DB::connection('sqlsrv')
        return redirect('/instruments')->with('success', 'Instrument updated successfully');
    }

    public function destroy($id)
    {
        // Here you would implement the delete logic using DB::connection('sqlsrv')
        return redirect('/instruments')->with('success', 'Instrument deleted successfully');
    }
}