<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class LegalSearchController extends Controller
{
    public function index()
    { 
        $PageTitle = 'Legal Search - Official (for filing purpose)';
        $PageDescription = '';
        return view('legal_search.index', compact('PageTitle', 'PageDescription'));
    } 

    public function search(Request $request)
    {
        // Get all search parameters
        $fileNo = $request->input('query');
        $guarantorName = $request->input('guarantorName');
        $guaranteeName = $request->input('guaranteeName');
        $lga = $request->input('lga');
        $district = $request->input('district');
        $location = $request->input('location');
        $plotNumber = $request->input('plotNumber');
        $planNumber = $request->input('planNumber');
        $size = $request->input('size');
        $caveat = $request->input('caveat');

        // Check if at least one search parameter is provided
        $hasSearchCriteria = !empty($fileNo) || !empty($guarantorName) || !empty($guaranteeName) || 
                            !empty($lga) || !empty($district) || !empty($location) || 
                            !empty($plotNumber) || !empty($planNumber) || !empty($size) || !empty($caveat);

        if (!$hasSearchCriteria) {
            return response()->json([
                'property_records' => [],
                'registered_instruments' => [],
                'cofo' => [],
            ]);
        }

        // Step 1: Get all parent file numbers (np_fileno) that match the search criteria
        $parentFileNumbers = $this->getParentFileNumbers($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        
        // Step 2: Get all related file numbers from the parent-child relationships - WITH SELECTIVE LOGIC
        $allRelatedFileNumbers = $this->getAllRelatedFileNumbers($parentFileNumbers, $fileNo);

        // Step 3: Search across the three main tables using all related file numbers
        
        // 1. Search property_records table
        $property_records = $this->searchPropertyRecords($allRelatedFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        
        // 2. Search registered_instruments table (including ST instruments)
        $registered_instruments = $this->searchRegisteredInstruments($allRelatedFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        
        // 3. Search CofO table
        $cofo = $this->searchCofoRecords($allRelatedFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);

        return response()->json([
            'property_records' => $property_records,
            'registered_instruments' => $registered_instruments,
            'cofo' => $cofo,
        ]);
    }

    /**
     * Get all parent file numbers (np_fileno) that match the search criteria
     */
    private function getParentFileNumbers($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        $parentFileNumbers = [];

        // Search in mother_applications table
        $motherApps = DB::connection('sqlsrv')->table('mother_applications')
            ->where(function($q) use ($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat) {
                if (!empty($fileNo)) {
                    $q->where(function($subQ) use ($fileNo) {
                        $subQ->whereRaw("UPPER(np_fileno) LIKE UPPER(?)", ["%{$fileNo}%"])
                             ->orWhereRaw("UPPER(fileno) LIKE UPPER(?)", ["%{$fileNo}%"]);
                    });
                }
                
                if (!empty($guarantorName)) {
                    $q->where(function($subQ) use ($guarantorName) {
                        $subQ->whereRaw("UPPER(first_name) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(corporate_name) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(multiple_owners_names) LIKE UPPER(?)", ["%{$guarantorName}%"]);
                    });
                }
                
                if (!empty($lga)) {
                    $q->whereRaw("UPPER(property_lga) LIKE UPPER(?)", ["%{$lga}%"]);
                }
                
                if (!empty($district)) {
                    $q->whereRaw("UPPER(property_district) LIKE UPPER(?)", ["%{$district}%"]);
                }
                
                if (!empty($location)) {
                    $q->where(function($subQ) use ($location) {
                        $subQ->whereRaw("UPPER(property_street_name) LIKE UPPER(?)", ["%{$location}%"])
                             ->orWhereRaw("UPPER(property_house_no) LIKE UPPER(?)", ["%{$location}%"]);
                    });
                }
                
                if (!empty($plotNumber)) {
                    $q->whereRaw("UPPER(property_plot_no) LIKE UPPER(?)", ["%{$plotNumber}%"]);
                }
            })
            ->pluck('np_fileno')
            ->toArray();

        $parentFileNumbers = array_merge($parentFileNumbers, $motherApps);

        // Also search in subapplications to find parent relationships
        if (!empty($fileNo)) {
            $subApps = DB::connection('sqlsrv')->table('subapplications as s')
                ->join('mother_applications as m', 's.main_application_id', '=', 'm.id')
                ->where(function($q) use ($fileNo) {
                    $q->whereRaw("UPPER(s.fileno) LIKE UPPER(?)", ["%{$fileNo}%"]);
                })
                ->pluck('m.np_fileno')
                ->toArray();
            
            $parentFileNumbers = array_merge($parentFileNumbers, $subApps);
        }

        // Search in registered_instruments to find parent relationships
        if (!empty($fileNo)) {
            $instrumentParents = DB::connection('sqlsrv')->table('registered_instruments')
                ->where(function($q) use ($fileNo) {
                    $q->whereRaw("UPPER(StFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                     ->orWhereRaw("UPPER(MLSFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                     ->orWhereRaw("UPPER(parent_fileNo) LIKE UPPER(?)", ["%{$fileNo}%"]);
                })
                ->whereNotNull('parent_fileNo')
                ->pluck('parent_fileNo')
                ->toArray();
            
            $parentFileNumbers = array_merge($parentFileNumbers, $instrumentParents);
        }

        // If searching by file number directly, include it as a potential parent
        if (!empty($fileNo)) {
            $parentFileNumbers[] = $fileNo;
        }

        return array_unique(array_filter($parentFileNumbers));
    }

    /**
     * Get all related file numbers from parent-child relationships - UPDATED FOR SELECTIVE ST FILE SEARCH
     */
    private function getAllRelatedFileNumbers($parentFileNumbers, $originalFileNo)
    {
        $allFileNumbers = [];
        
        // Identify the type of the original search file number
        $originalFileType = $this->identifyFileNumberType($originalFileNo);

        foreach ($parentFileNumbers as $parentFileNo) {
            // Add the parent file number itself
            $allFileNumbers[] = $parentFileNo;

            // Handle child ST files based on search type - SELECTIVE LOGIC
            if ($originalFileType === 'st') {
                // If searching for a specific ST file, only include that specific ST file
                // Don't include sibling ST files
                if (!empty($originalFileNo) && !in_array($originalFileNo, $allFileNumbers)) {
                    $allFileNumbers[] = $originalFileNo;
                }
            } else {
                // If searching for parent, MLS, or other criteria, include all child ST files
                $childSTFiles = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('parent_fileNo', $parentFileNo)
                    ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                    ->pluck('StFileNo')
                    ->toArray();
                
                $allFileNumbers = array_merge($allFileNumbers, array_filter($childSTFiles));
            }

            // Find MLS file numbers associated with this parent
            $mlsFiles = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('parent_fileNo', $parentFileNo)
                ->whereNotNull('MLSFileNo')
                ->pluck('MLSFileNo')
                ->toArray();
            
            $allFileNumbers = array_merge($allFileNumbers, array_filter($mlsFiles));

            // Find MLS files from CofO table
            $cofoMlsFiles = DB::connection('sqlsrv')->table('CofO')
                ->where('np_fileno', $parentFileNo)
                ->whereNotNull('mlsFNo')
                ->pluck('mlsFNo')
                ->toArray();
            
            $allFileNumbers = array_merge($allFileNumbers, array_filter($cofoMlsFiles));

            // Find KANGIS file numbers
            $kangisFiles = DB::connection('sqlsrv')->table('CofO')
                ->where('np_fileno', $parentFileNo)
                ->whereNotNull('kangisFileNo')
                ->pluck('kangisFileNo')
                ->toArray();
            
            $allFileNumbers = array_merge($allFileNumbers, array_filter($kangisFiles));

            // Find New KANGIS file numbers
            $newKangisFiles = DB::connection('sqlsrv')->table('CofO')
                ->where('np_fileno', $parentFileNo)
                ->whereNotNull('NewKANGISFileno')
                ->pluck('NewKANGISFileno')
                ->toArray();
            
            $allFileNumbers = array_merge($allFileNumbers, array_filter($newKangisFiles));
        }

        // Include the original search file number if not already included
        if (!empty($originalFileNo) && !in_array($originalFileNo, $allFileNumbers)) {
            $allFileNumbers[] = $originalFileNo;
        }

        return array_unique(array_filter($allFileNumbers));
    }

    /**
     * Search property_records table
     */
    private function searchPropertyRecords($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        return DB::connection('sqlsrv')->table('property_records')
            ->where(function($q) use ($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat) {
                // File number search
                if (!empty($fileNumbers)) {
                    $q->where(function($subQ) use ($fileNumbers) {
                        foreach ($fileNumbers as $fileNo) {
                            $subQ->orWhereRaw("UPPER(mlsFNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(kangisFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(NewKANGISFileno) LIKE UPPER(?)", ["%{$fileNo}%"]);
                        }
                    });
                }
                
                // Other search criteria
                if (!empty($guarantorName)) {
                    $q->where(function($subQ) use ($guarantorName) {
                        $subQ->whereRaw("UPPER(Assignor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Grantor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Mortgagor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Lessor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Surrenderor) LIKE UPPER(?)", ["%{$guarantorName}%"]);
                    });
                }
                
                if (!empty($guaranteeName)) {
                    $q->where(function($subQ) use ($guaranteeName) {
                        $subQ->whereRaw("UPPER(Assignee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Grantee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Mortgagee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Lessee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Surrenderee) LIKE UPPER(?)", ["%{$guaranteeName}%"]);
                    });
                }
                
                if (!empty($lga)) {
                    $q->whereRaw("UPPER(lgsaOrCity) LIKE UPPER(?)", ["%{$lga}%"]);
                }
                
                if (!empty($district)) {
                    $q->whereRaw("UPPER(location) LIKE UPPER(?)", ["%{$district}%"]);
                }
                
                if (!empty($location)) {
                    $q->where(function($subQ) use ($location) {
                        $subQ->whereRaw("UPPER(location) LIKE UPPER(?)", ["%{$location}%"])
                             ->orWhereRaw("UPPER(property_description) LIKE UPPER(?)", ["%{$location}%"]);
                    });
                }
                
                if (!empty($plotNumber)) {
                    $q->whereRaw("UPPER(plot_no) LIKE UPPER(?)", ["%{$plotNumber}%"]);
                }
                
                if (!empty($size)) {
                    $q->whereRaw("UPPER(CAST(size AS VARCHAR)) LIKE UPPER(?)", ["%{$size}%"]);
                }
                
                if (!empty($caveat)) {
                    $q->whereRaw("UPPER(caveat) = UPPER(?)", [$caveat]);
                }
            })
            ->select('*', DB::raw("'property_records' as record_type"))
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Search registered_instruments table
     */
    private function searchRegisteredInstruments($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        return DB::connection('sqlsrv')->table('registered_instruments as ri')
            ->leftJoin('subapplications as s', 'ri.StFileNo', '=', 's.fileno')
            ->leftJoin('mother_applications as m', 's.main_application_id', '=', 'm.id')
            ->where(function($q) use ($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat) {
                // File number search
                if (!empty($fileNumbers)) {
                    $q->where(function($subQ) use ($fileNumbers) {
                        foreach ($fileNumbers as $fileNo) {
                            $subQ->orWhereRaw("UPPER(ri.StFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(ri.parent_fileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(ri.MLSFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(ri.KAGISFileNO) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(ri.NewKANGISFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(m.np_fileno) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(m.fileno) LIKE UPPER(?)", ["%{$fileNo}%"]);
                        }
                    });
                }
                
                // Other search criteria
                if (!empty($guarantorName)) {
                    $q->where(function($subQ) use ($guarantorName) {
                        $subQ->whereRaw("UPPER(ri.Grantor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(ri.mortgagor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(ri.assignor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(ri.lessor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(ri.surrenderor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(m.first_name) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(m.corporate_name) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(m.multiple_owners_names) LIKE UPPER(?)", ["%{$guarantorName}%"]);
                    });
                }
                
                if (!empty($guaranteeName)) {
                    $q->where(function($subQ) use ($guaranteeName) {
                        $subQ->whereRaw("UPPER(ri.Grantee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(ri.mortgagee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(ri.assignee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(ri.lessee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(ri.surrenderee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(s.first_name) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(s.corporate_name) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(s.multiple_owners_names) LIKE UPPER(?)", ["%{$guaranteeName}%"]);
                    });
                }
                
                if (!empty($lga)) {
                    $q->whereRaw("UPPER(ri.lga) LIKE UPPER(?)", ["%{$lga}%"]);
                }
                
                if (!empty($district)) {
                    $q->whereRaw("UPPER(ri.district) LIKE UPPER(?)", ["%{$district}%"]);
                }
                
                if (!empty($location)) {
                    $q->where(function($subQ) use ($location) {
                        $subQ->whereRaw("UPPER(ri.propertyAddress) LIKE UPPER(?)", ["%{$location}%"])
                             ->orWhereRaw("UPPER(ri.propertyDescription) LIKE UPPER(?)", ["%{$location}%"])
                             ->orWhereRaw("UPPER(m.property_street_name) LIKE UPPER(?)", ["%{$location}%"]);
                    });
                }
                
                if (!empty($plotNumber)) {
                    $q->where(function($subQ) use ($plotNumber) {
                        $subQ->whereRaw("UPPER(ri.plotNumber) LIKE UPPER(?)", ["%{$plotNumber}%"])
                             ->orWhereRaw("UPPER(ri.plotNo) LIKE UPPER(?)", ["%{$plotNumber}%"])
                             ->orWhereRaw("UPPER(m.property_plot_no) LIKE UPPER(?)", ["%{$plotNumber}%"]);
                    });
                }
                
                if (!empty($size)) {
                    $q->whereRaw("UPPER(CAST(ri.size AS VARCHAR)) LIKE UPPER(?)", ["%{$size}%"]);
                }
            })
            ->select(
                'ri.*',
                'm.np_fileno as mother_np_fileno',
                'm.fileno as mother_fileno',
                's.fileno as sub_fileno',
                // Add computed fields for proper file number display
                DB::raw("COALESCE(ri.StFileNo, s.fileno) as STFileNo"),
                DB::raw("COALESCE(ri.parent_fileNo, m.np_fileno) as ParentFileNo"),
                DB::raw("ri.MLSFileNo as MLSFileNo"),
                DB::raw("ri.KAGISFileNO as KANGISFileNo"),
                DB::raw("ri.NewKANGISFileNo as NewKANGISFileNo"),
                DB::raw("'registered_instruments' as record_type")
            )
            ->orderBy('ri.deeds_date', 'desc')
            ->get();
    }

    /**
     * Search CofO table
     */
    private function searchCofoRecords($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        return DB::connection('sqlsrv')->table('CofO')
            ->where(function($q) use ($fileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat) {
                // File number search
                if (!empty($fileNumbers)) {
                    $q->where(function($subQ) use ($fileNumbers) {
                        foreach ($fileNumbers as $fileNo) {
                            $subQ->orWhereRaw("UPPER(np_fileno) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(mlsFNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(kangisFileNo) LIKE UPPER(?)", ["%{$fileNo}%"])
                                 ->orWhereRaw("UPPER(NewKANGISFileno) LIKE UPPER(?)", ["%{$fileNo}%"]);
                        }
                    });
                }
                
                // Other search criteria
                if (!empty($guarantorName)) {
                    $q->where(function($subQ) use ($guarantorName) {
                        $subQ->whereRaw("UPPER(Assignor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Grantor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Mortgagor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Lessor) LIKE UPPER(?)", ["%{$guarantorName}%"])
                             ->orWhereRaw("UPPER(Surrenderor) LIKE UPPER(?)", ["%{$guarantorName}%"]);
                    });
                }
                
                if (!empty($guaranteeName)) {
                    $q->where(function($subQ) use ($guaranteeName) {
                        $subQ->whereRaw("UPPER(Assignee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Grantee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Mortgagee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Lessee) LIKE UPPER(?)", ["%{$guaranteeName}%"])
                             ->orWhereRaw("UPPER(Surrenderee) LIKE UPPER(?)", ["%{$guaranteeName}%"]);
                    });
                }
                
                if (!empty($lga)) {
                    $q->whereRaw("UPPER(lgsaOrCity) LIKE UPPER(?)", ["%{$lga}%"]);
                }
                
                if (!empty($district)) {
                    $q->whereRaw("UPPER(location) LIKE UPPER(?)", ["%{$district}%"]);
                }
                
                if (!empty($location)) {
                    $q->where(function($subQ) use ($location) {
                        $subQ->whereRaw("UPPER(location) LIKE UPPER(?)", ["%{$location}%"])
                             ->orWhereRaw("UPPER(property_description) LIKE UPPER(?)", ["%{$location}%"]);
                    });
                }
                
                if (!empty($plotNumber)) {
                    $q->whereRaw("UPPER(plot_no) LIKE UPPER(?)", ["%{$plotNumber}%"]);
                }
                
                if (!empty($size)) {
                    $q->whereRaw("UPPER(CAST(size AS VARCHAR)) LIKE UPPER(?)", ["%{$size}%"]);
                }
                
                if (!empty($caveat)) {
                    $q->whereRaw("UPPER(caveat) = UPPER(?)", [$caveat]);
                }
            })
            ->select(
                '*',
                DB::raw("NULL as STFileNo"),
                DB::raw("np_fileno as ParentFileNo"),
                DB::raw("mlsFNo as MLSFileNo"),
                DB::raw("kangisFileNo as KANGISFileNo"),
                DB::raw("NewKANGISFileno as NewKANGISFileNo"),
                DB::raw("'CofO' as record_type")
            )
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    public function report()
    {
        return view('legal_search.report');
    }

    public function legal_search_report()
    {
        $PageTitle = 'Legal Search Report';
        return view('legal_search.legal_search_report');
    }
    
    /**
     * Identify the type of file number being searched
     * 
     * @param string $fileNo
     * @return string
     */
    private function identifyFileNumberType($fileNo)
    {
        if (empty($fileNo)) {
            return 'unknown';
        }
        
        $cleanFileNo = trim($fileNo);
        
        // MLS File Number patterns: COM-2022-572, RES-2023-145, CON-COM-2024-089, etc.
        if (preg_match('/^(COM|RES|IND|AG|CON-COM|CON-RES|CON-AG|CON-IND)-\d{4}-\d+$/i', $cleanFileNo)) {
            return 'mls';
        }
        
        // KANGIS File Number patterns: KNML 00001, MNKL 02500, MLKN 00567, KNGP 01234
        if (preg_match('/^[A-Z]{4}\s?\d{5}$/i', $cleanFileNo)) {
            return 'kangis';
        }
        
        // New KANGIS File Number patterns: KN1586, KN0001, KN2345
        if (preg_match('/^KN\d{4}$/i', $cleanFileNo)) {
            return 'new_kangis';
        }
        
        // Parent File Number (NP) patterns: ST-RES-2024-01, ST-COM-2024-02, ST-IND-2024-03
        if (preg_match('/^ST-(RES|COM|IND|AG)-\d{4}-\d+$/i', $cleanFileNo)) {
            return 'parent';
        }
        
        // ST File Number patterns: ST-RES-2024-01-001, ST-COM-2024-02-002, ST-IND-2024-03-009
        if (preg_match('/^ST-(RES|COM|IND|AG)-\d{4}-\d+-\d+$/i', $cleanFileNo)) {
            return 'st';
        }
        
        return 'unknown';
    }
    
    /**
     * Get related file numbers based on the file number type and hierarchical relationships
     * 
     * @param string $fileNo
     * @param string $fileNumberType
     * @return array
     */
    private function getRelatedFileNumbers($fileNo, $fileNumberType)
    {
        if (empty($fileNo)) {
            return [];
        }
        
        $relatedNumbers = [];
        
        switch ($fileNumberType) {
            case 'st':
                // For ST File Number (e.g., ST-COM-2025-01-001)
                // Find its parent (e.g., ST-COM-2025-01) and related MLS file
                $parentFileNo = $this->extractParentFromSTFile($fileNo);
                if ($parentFileNo) {
                    $relatedNumbers[] = $parentFileNo;
                    
                    // Find the MLS file number associated with this parent
                    $mlsFileNo = $this->findMlsFileForParent($parentFileNo);
                    if ($mlsFileNo) {
                        $relatedNumbers[] = $mlsFileNo;
                    }
                }
                break;
                
            case 'parent':
                // For Parent File Number (e.g., ST-COM-2025-01)
                // Find all child ST files and the associated MLS file
                $childSTFiles = $this->findChildSTFiles($fileNo);
                $relatedNumbers = array_merge($relatedNumbers, $childSTFiles);
                
                // Find the MLS file number
                $mlsFileNo = $this->findMlsFileForParent($fileNo);
                if ($mlsFileNo) {
                    $relatedNumbers[] = $mlsFileNo;
                }
                break;
                
            case 'mls':
                // For MLS File Number (e.g., COM-2015-0001)
                // Find the parent file and all child ST files
                $parentFileNo = $this->findParentFileForMls($fileNo);
                if ($parentFileNo) {
                    $relatedNumbers[] = $parentFileNo;
                    
                    // Find all child ST files
                    $childSTFiles = $this->findChildSTFiles($parentFileNo);
                    $relatedNumbers = array_merge($relatedNumbers, $childSTFiles);
                }
                break;
                
            case 'kangis':
            case 'new_kangis':
                // For KANGIS file numbers, search for related MLS and ST files
                $relatedFiles = $this->findRelatedFilesForKangis($fileNo);
                $relatedNumbers = array_merge($relatedNumbers, $relatedFiles);
                break;
        }
        
        return array_unique($relatedNumbers);
    }
    
    /**
     * Extract parent file number from ST file number
     * ST-COM-2025-01-001 -> ST-COM-2025-01
     */
    private function extractParentFromSTFile($stFileNo)
    {
        if (preg_match('/^(ST-[A-Z]+-\d{4}-\d+)-\d+$/i', $stFileNo, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Find MLS file number for a given parent file number
     */
    private function findMlsFileForParent($parentFileNo)
    {
        try {
            // Search in registered_instruments for ST Assignment records
            $result = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('parent_fileNo', $parentFileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->first(['MLSFileNo']);
            
            if ($result && $result->MLSFileNo) {
                return $result->MLSFileNo;
            }
            
            // Also check CofO table
            $cofoResult = DB::connection('sqlsrv')->table('CofO')
                ->where('np_fileno', $parentFileNo)
                ->first(['mlsFNo']);
            
            if ($cofoResult && $cofoResult->mlsFNo) {
                return $cofoResult->mlsFNo;
            }
        } catch (\Exception $e) {
            // Log error but continue
        }
        
        return null;
    }
    
    /**
     * Find parent file number for a given MLS file number
     */
    private function findParentFileForMls($mlsFileNo)
    {
        try {
            // Search in registered_instruments for ST Assignment records
            $result = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('MLSFileNo', $mlsFileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->first(['parent_fileNo']);
            
            if ($result && $result->parent_fileNo) {
                return $result->parent_fileNo;
            }
            
            // Also check CofO table
            $cofoResult = DB::connection('sqlsrv')->table('CofO')
                ->where('mlsFNo', $mlsFileNo)
                ->first(['np_fileno']);
            
            if ($cofoResult && $cofoResult->np_fileno) {
                return $cofoResult->np_fileno;
            }
        } catch (\Exception $e) {
            // Log error but continue
        }
        
        return null;
    }
    
    /**
     * Find child ST files for a given parent file number
     */
    private function findChildSTFiles($parentFileNo)
    {
        try {
            $results = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('parent_fileNo', $parentFileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->pluck('StFileNo')
                ->toArray();
            
            return array_filter($results); // Remove null values
        } catch (\Exception $e) {
            // Log error but continue
            return [];
        }
    }
    
    /**
     * Find related files for KANGIS file numbers
     */
    private function findRelatedFilesForKangis($kangisFileNo)
    {
        $relatedFiles = [];
        
        try {
            // Search in various tables for related file numbers
            $tables = [
                'property_records' => ['mlsFNo', 'kangisFileNo', 'NewKANGISFileno'],
                'registered_instruments' => ['MLSFileNo', 'KAGISFileNO', 'NewKANGISFileNo'],
                'CofO' => ['mlsFNo', 'kangisFileNo', 'NewKANGISFileno']
            ];
            
            foreach ($tables as $table => $fields) {
                $query = DB::connection('sqlsrv')->table($table);
                
                foreach ($fields as $field) {
                    $query->orWhere($field, $kangisFileNo);
                }
                
                $results = $query->get();
                
                foreach ($results as $result) {
                    foreach ($fields as $field) {
                        if (isset($result->$field) && $result->$field && $result->$field !== $kangisFileNo) {
                            $relatedFiles[] = $result->$field;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but continue
        }
        
        return array_unique($relatedFiles);
    }

    /**
     * Check if the query looks like a file number pattern
     * 
     * @param string $query
     * @return bool
     */
    private function isFileNumberPattern($query)
    {
        $trimmedQuery = trim($query);
        
        // Only consider it a file number if it has more specific patterns
        $patterns = [
            '/^ST-\d+/i',        // ST- followed by numbers
            '/^RES-\d+/i',       // RES- followed by numbers  
            '/^COM-\d+/i',       // COM- followed by numbers
            '/^IND-\d+/i',       // IND- followed by numbers
            '/^MLS-\d+/i',       // MLS- followed by numbers
            '/^KAN-\d+/i',       // KAN- followed by numbers
            '/^KANGIS-\d+/i',    // KANGIS- followed by numbers
            '/^[A-Z]{2,4}-\d+/i', // Pattern like XX-123 or XXX-123
            '/^\d{4}\/\d+/i',    // Pattern like 2023/123
            '/^[A-Z]+\d{3,}/i',  // Pattern like ABC123 (at least 3 digits)
        ];
        
        // If it's just a prefix like "ST-" without numbers, treat it as file number search
        if (preg_match('/^(ST|RES|COM|IND|MLS|KAN|KANGIS)-$/i', $trimmedQuery)) {
            return true;
        }
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $trimmedQuery)) {
                return true;
            }
        }
        
        return false;
    }
}