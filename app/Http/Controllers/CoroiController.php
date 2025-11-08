<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoroiController extends Controller
{
    protected $validTables = [
        'registered_instruments' => ['id' => 'Grantor', 'type' => null],
        'SectionalCofOReg' => ['id' => 'Applicant_Name', 'type' => 'Sectional Title Certificates of Occupancy'],
        'Sectional_title_transfer' => ['id' => 'Applicant_Name', 'type' => 'Transfer of Title']
    ];

    public function index(Request $request)
    {
        // Parse URL parameters
        $url = $request->query('url', '');
        $stmRef = $request->query('STM_Ref', '');
        $fileno = $request->query('fileno', '');
        
        // Log the incoming parameters for debugging
        Log::info('COROI Index called with parameters', [
            'url' => $url,
            'STM_Ref' => $stmRef,
            'fileno' => $fileno,
            'full_url' => $request->fullUrl()
        ]);
        
        // Handle special URL format for STM_Ref
        if (empty($stmRef) && !empty($url) && strpos($url, '?STM_Ref=') !== false) {
            $parts = explode('?STM_Ref=', $url);
            if (count($parts) == 2) {
                $tableName = $parts[0];
                $stmRef = $parts[1];
                Log::info('Extracted STM_Ref from URL', ['table' => $tableName, 'stm_ref' => $stmRef]);
            }
        }
        // Handle special URL format for fileno
        elseif (empty($fileno) && !empty($url) && strpos($url, '?fileno=') !== false) {
            $parts = explode('?fileno=', $url);
            if (count($parts) == 2) {
                $tableName = $parts[0];
                $fileno = $parts[1];
                Log::info('Extracted fileno from URL', ['table' => $tableName, 'fileno' => $fileno]);
            }
        } else {
            $tableName = $url;
        }
        
        // Search by fileno if provided
        if (!empty($fileno)) {
            Log::info('Searching by fileno', ['fileno' => $fileno]);
            $data = $this->findRecordByFileno($fileno);
        }
        // Search by STM_Ref if provided
        elseif (!empty($stmRef)) {
            Log::info('Searching by STM_Ref', ['stm_ref' => $stmRef, 'table' => $tableName]);
            $data = $this->findRecord($tableName, $stmRef);
        }
        // Return mock data if neither parameter is provided
        else {
            Log::info('No search parameters provided, using mock data');
            $data = $this->formatDateTimeData($this->createMockData());
        }
        
        return view('coroi.index', ['data' => $data]);
    }
    
    public function searchByFileno(Request $request)
    {
        $fileno = $request->query('fileno');
        
        if (empty($fileno)) {
            return redirect()->route('coroi.search')->with('error', 'File number is required');
        }
        
        $data = $this->findRecordByFileno($fileno);
        return view('coroi.index', ['data' => $data]);
    }
    
    public function debug(Request $request)
    {
        // Get the same parameters as the main index method
        $url = $request->query('url', '');
        $stmRef = $request->query('STM_Ref', '');
        $fileno = $request->query('fileno', 'ST-COM-2025-01'); // Default for testing
        
        // Handle special URL format for fileno
        if (empty($fileno) && !empty($url) && strpos($url, '?fileno=') !== false) {
            $parts = explode('?fileno=', $url);
            if (count($parts) == 2) {
                $tableName = $parts[0];
                $fileno = $parts[1];
            }
        }
        
        // Search by fileno if provided
        if (!empty($fileno)) {
            $data = $this->findRecordByFileno($fileno);
        }
        // Search by STM_Ref if provided
        elseif (!empty($stmRef)) {
            $data = $this->findRecord($url, $stmRef);
        }
        // Return mock data if neither parameter is provided
        else {
            $data = $this->formatDateTimeData($this->createMockData());
        }
        
        return view('coroi.debug', ['data' => $data]);
    }
    
    public function testDatabase(Request $request)
    {
        try {
            // Test database connection
            $connection = DB::connection('sqlsrv');
            Log::info('Database connection successful');
            
            // Get total count
            $totalCount = $connection->table('registered_instruments')->count();
            Log::info('Total records in registered_instruments: ' . $totalCount);
            
            // Get sample records
            $sampleRecords = $connection->table('registered_instruments')
                ->select('id', 'MLSFileNo', 'KAGISFileNO', 'NewKANGISFileNo', 'StFileNo', 'Grantor', 'instrument_type', 'volume_no', 'page_no', 'serial_no')
                ->whereNotNull('Grantor')
                ->take(10)
                ->get();
            
            Log::info('Sample records:', $sampleRecords->toArray());
            
            // Test specific search for ST-COM-2025-01
            $specificSearch = $connection->table('registered_instruments')
                ->where('MLSFileNo', 'ST-COM-2025-01')
                ->orWhere('KAGISFileNO', 'ST-COM-2025-01')
                ->orWhere('NewKANGISFileNo', 'ST-COM-2025-01')
                ->orWhere('StFileNo', 'ST-COM-2025-01')
                ->first();
            
            Log::info('Specific search for ST-COM-2025-01:', $specificSearch ? (array)$specificSearch : ['result' => 'not found']);
            
            return response()->json([
                'success' => true,
                'total_records' => $totalCount,
                'sample_records' => $sampleRecords,
                'specific_search' => $specificSearch,
                'message' => 'Database test completed. Check logs for details.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Database test failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Database test failed. Check logs for details.'
            ]);
        }
    }
    
    private function findRecordByFileno($fileno)
    {
        try {
            // Search in registered_instruments table using multiple file number columns
            $data = $this->queryRegisteredInstrumentsByFileno($fileno);
            if ($data) {
                return $this->formatDateTimeData($data);
            }
            
            // Return mock data if no record found
            return $this->formatDateTimeData($this->createMockData($fileno));
        } catch (\Exception $e) {
            Log::error('Error querying registered_instruments by fileno: ' . $e->getMessage());
            return $this->formatDateTimeData($this->createMockData($fileno));
        }
    }
    
    private function queryRegisteredInstrumentsByFileno($fileno)
    {
        try {
            // First, let's check if the table exists and has data
            $countStmt = DB::connection('sqlsrv')->getPdo()->prepare("
                SELECT COUNT(*) as total FROM registered_instruments
            ");
            $countStmt->execute();
            $count = $countStmt->fetch(\PDO::FETCH_OBJ);
            Log::info('Total records in registered_instruments table: ' . $count->total);
            
            // Query registered_instruments table with multiple file number columns
            $stmt = DB::connection('sqlsrv')->getPdo()->prepare("
                SELECT TOP 1
                    Grantor as Applicant_Name,
                    instrument_type,
                    volume_no,
                    page_no,
                    serial_no,
                    deeds_time,
                    deeds_date,
                    instrumentDate,
                    STM_Ref,
                    MLSFileNo,
                    KAGISFileNO,
                    NewKANGISFileNo,
                    StFileNo,
                    status,
                    created_at
                FROM registered_instruments
                WHERE MLSFileNo = ? 
                   OR KAGISFileNO = ? 
                   OR NewKANGISFileNo = ? 
                   OR StFileNo = ?
                ORDER BY created_at DESC
            ");
            
            Log::info('Executing exact match query for fileno: ' . $fileno);
            $stmt->execute([$fileno, $fileno, $fileno, $fileno]);
            $data = $stmt->fetch(\PDO::FETCH_OBJ);
            
            if (!$data) {
                Log::info('No exact match found, trying LIKE search');
                // Try with LIKE search for partial matches
                $stmt = DB::connection('sqlsrv')->getPdo()->prepare("
                    SELECT TOP 1
                        Grantor as Applicant_Name,
                        instrument_type,
                        volume_no,
                        page_no,
                        serial_no,
                        deeds_time,
                        deeds_date,
                        instrumentDate,
                        STM_Ref,
                        MLSFileNo,
                        KAGISFileNO,
                        NewKANGISFileNo,
                        StFileNo,
                        status,
                        created_at
                    FROM registered_instruments
                    WHERE MLSFileNo LIKE ? 
                       OR KAGISFileNO LIKE ? 
                       OR NewKANGISFileNo LIKE ? 
                       OR StFileNo LIKE ?
                    ORDER BY created_at DESC
                ");
                
                $likePattern = '%' . $fileno . '%';
                $stmt->execute([$likePattern, $likePattern, $likePattern, $likePattern]);
                $data = $stmt->fetch(\PDO::FETCH_OBJ);
            }
            
            // If still no data, let's check what file numbers actually exist
            if (!$data) {
                Log::info('No LIKE match found, checking existing file numbers');
                $sampleStmt = DB::connection('sqlsrv')->getPdo()->prepare("
                    SELECT TOP 10 
                        MLSFileNo, KAGISFileNO, NewKANGISFileNo, StFileNo, Grantor, instrument_type
                    FROM registered_instruments 
                    WHERE MLSFileNo IS NOT NULL OR KAGISFileNO IS NOT NULL 
                       OR NewKANGISFileNo IS NOT NULL OR StFileNo IS NOT NULL
                    ORDER BY created_at DESC
                ");
                $sampleStmt->execute();
                $samples = $sampleStmt->fetchAll(\PDO::FETCH_OBJ);
                Log::info('Sample file numbers in database:', array_map(function($row) {
                    return [
                        'MLSFileNo' => $row->MLSFileNo,
                        'KAGISFileNO' => $row->KAGISFileNO,
                        'NewKANGISFileNo' => $row->NewKANGISFileNo,
                        'StFileNo' => $row->StFileNo,
                        'Grantor' => $row->Grantor,
                        'instrument_type' => $row->instrument_type
                    ];
                }, $samples));
            }
            
            // Log the data for debugging
            if ($data) {
                Log::info('Found record for fileno: ' . $fileno, (array)$data);
            } else {
                Log::info('No record found for fileno: ' . $fileno);
            }
            
            return $data;
            
        } catch (\Exception $e) {
            Log::error('Error in queryRegisteredInstrumentsByFileno: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return null;
        }
    }
    
    private function findRecord($tableName, $stmRef)
    {
        try {
            // If table is specified and valid, search there first
            if (!empty($tableName) && isset($this->validTables[$tableName])) {
                $data = $this->queryTable($tableName, $stmRef);
                if ($data) return $this->formatDateTimeData($data);
            }
            
            // Search all tables if no data found
            foreach ($this->validTables as $table => $config) {
                $data = $this->queryTable($table, $stmRef);
                if ($data) return $this->formatDateTimeData($data);
            }
            
            // Return mock data as last resort
            return $this->formatDateTimeData($this->createMockData($stmRef));
        } catch (\Exception $e) {
            Log::error('Error querying database: ' . $e->getMessage());
            return $this->formatDateTimeData($this->createMockData($stmRef));
        }
    }
    
    private function queryTable($table, $stmRef)
    {
        $config = $this->validTables[$table];
        $idColumn = $config['id'];
        $nameAlias = $idColumn === 'Grantor' ? "$idColumn as Applicant_Name" : $idColumn;
        $type = $config['type'] !== null ? "'$config[type]' as instrument_type" : 'instrument_type';
        
        $stmt = DB::connection('sqlsrv')->getPdo()->prepare("
            SELECT TOP 1
                $nameAlias,
                $type,
                volume_no,
                page_no,
                serial_no,
                deeds_time,
                deeds_date,
                STM_Ref
            FROM $table
            WHERE STM_Ref = ?
        ");
        
        $stmt->execute([$stmRef]);
        $data = $stmt->fetch(\PDO::FETCH_OBJ);
        
        if (!$data) {
            // Try with LIKE search
            $stmt = DB::connection('sqlsrv')->getPdo()->prepare("
                SELECT TOP 1
                    $nameAlias,
                    $type,
                    volume_no,
                    page_no,
                    serial_no,
                    deeds_time,
                    deeds_date,
                    STM_Ref
                FROM $table
                WHERE STM_Ref LIKE ?
            ");
            
            $stmt->execute(['%' . $stmRef . '%']);
            $data = $stmt->fetch(\PDO::FETCH_OBJ);
        }
        
        return $data;
    }
    
    private function createMockData($reference = null)
    {
        $year = date('Y');
        return (object)[
            'Applicant_Name' => 'DEFAULT APPLICANT',
            'instrument_type' => 'DEFAULT INSTRUMENT',
            'volume_no' => 1,
            'page_no' => 1,
            'serial_no' => 1,
            'deeds_time' => '12:00 PM',
            'deeds_date' => date('Y-m-d'),
            'STM_Ref' => $reference && strpos($reference, 'STM-') === 0 ? $reference : "STM-{$year}-001",
            'MLSFileNo' => $reference && strpos($reference, 'STM-') !== 0 ? $reference : null,
            'KAGISFileNO' => null,
            'NewKANGISFileNo' => null,
            'StFileNo' => null
        ];
    }
    
    private function formatDateTimeData($data)
    {
        if (!$data) return $this->createMockData();
        
        // Format date - try deeds_date first, then instrumentDate, then default
        if (isset($data->deeds_date) && !empty($data->deeds_date)) {
            $data->formatted_date = date('jS F Y', strtotime($data->deeds_date));
        } elseif (isset($data->instrumentDate) && !empty($data->instrumentDate)) {
            $data->formatted_date = date('jS F Y', strtotime($data->instrumentDate));
            // Also set deeds_date for consistency
            $data->deeds_date = $data->instrumentDate;
        } else {
            $data->formatted_date = date('jS F Y');
            $data->deeds_date = date('Y-m-d');
        }
            
        // Handle time formatting - check multiple sources
        $timeString = null;
        
        if (isset($data->deeds_time) && !empty($data->deeds_time)) {
            // If deeds_time is available, use it
            $timeString = $data->deeds_time;
            Log::info('Using deeds_time: ' . $timeString);
        } elseif (isset($data->instrumentDate) && !empty($data->instrumentDate)) {
            // Extract time from instrumentDate if it contains time information
            $timeString = date('H:i:s', strtotime($data->instrumentDate));
            Log::info('Extracted time from instrumentDate: ' . $timeString);
        } elseif (isset($data->deeds_date) && !empty($data->deeds_date)) {
            // Extract time from deeds_date if it contains time information
            $timeString = date('H:i:s', strtotime($data->deeds_date));
            Log::info('Extracted time from deeds_date: ' . $timeString);
        } else {
            // Default time
            $timeString = '12:00:00';
            Log::info('Using default time: ' . $timeString);
        }
        
        // Convert to 12-hour format
        try {
            if ($timeString) {
                $data->formatted_time = date('g:i A', strtotime($timeString));
                $data->time_part = date('A', strtotime($timeString));
                $data->hour_part = date('g', strtotime($timeString));
                if (!isset($data->deeds_time)) {
                    $data->deeds_time = $data->formatted_time;
                }
            } else {
                throw new \Exception('No valid time string');
            }
        } catch (\Exception $e) {
            // Fallback if time parsing fails
            Log::warning('Time parsing failed: ' . $e->getMessage());
            $data->formatted_time = '12:00 PM';
            $data->time_part = 'PM';
            $data->hour_part = '12';
            $data->deeds_time = '12:00 PM';
        }
        
        // Log formatted data for debugging
        Log::info('Formatted data', [
            'Applicant_Name' => $data->Applicant_Name ?? 'N/A',
            'instrument_type' => $data->instrument_type ?? 'N/A',
            'volume_no' => $data->volume_no ?? 'N/A',
            'page_no' => $data->page_no ?? 'N/A',
            'serial_no' => $data->serial_no ?? 'N/A',
            'formatted_date' => $data->formatted_date ?? 'N/A',
            'hour_part' => $data->hour_part ?? 'N/A',
            'time_part' => $data->time_part ?? 'N/A',
            'deeds_time' => $data->deeds_time ?? 'N/A',
            'deeds_date' => $data->deeds_date ?? 'N/A'
        ]);
        
        return $data;
    }
}
