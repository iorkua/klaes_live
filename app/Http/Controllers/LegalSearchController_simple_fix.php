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

        // SIMPLIFIED APPROACH: Handle specific ST file search differently
        $fileType = $this->identifyFileNumberType($fileNo);
        
        if ($fileType === 'st' && !empty($fileNo)) {
            // SPECIFIC ST FILE SEARCH - Only include that ST file + its parent + MLS
            return $this->searchSpecificSTFile($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        } else {
            // GENERAL SEARCH - Use the existing hierarchical logic
            return $this->searchGeneral($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        }
    }

    /**
     * Search for a specific ST file - SELECTIVE APPROACH
     */
    private function searchSpecificSTFile($stFileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        // Step 1: Get the parent file number for this ST file
        $parentFileNo = $this->extractParentFromSTFile($stFileNo);
        
        // Step 2: Get the MLS file number associated with the parent
        $mlsFileNo = null;
        if ($parentFileNo) {
            $mlsFileNo = $this->findMlsFileForParent($parentFileNo);
        }
        
        // Step 3: Build the selective file numbers list
        $selectiveFileNumbers = [$stFileNo]; // Only the specific ST file
        
        if ($parentFileNo) {
            $selectiveFileNumbers[] = $parentFileNo; // Add parent
        }
        
        if ($mlsFileNo) {
            $selectiveFileNumbers[] = $mlsFileNo; // Add MLS file
        }
        
        // Step 4: Search the three tables with only these selective file numbers
        $property_records = $this->searchPropertyRecords($selectiveFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        $registered_instruments = $this->searchRegisteredInstruments($selectiveFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        $cofo = $this->searchCofoRecords($selectiveFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);

        return response()->json([
            'property_records' => $property_records,
            'registered_instruments' => $registered_instruments,
            'cofo' => $cofo,
        ]);
    }

    /**
     * General search using the existing hierarchical logic
     */
    private function searchGeneral($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat)
    {
        // Step 1: Get all parent file numbers (np_fileno) that match the search criteria
        $parentFileNumbers = $this->getParentFileNumbers($fileNo, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        
        // Step 2: Get all related file numbers from the parent-child relationships
        $allRelatedFileNumbers = $this->getAllRelatedFileNumbers($parentFileNumbers, $fileNo);

        // Step 3: Search across the three main tables using all related file numbers
        $property_records = $this->searchPropertyRecords($allRelatedFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
        $registered_instruments = $this->searchRegisteredInstruments($allRelatedFileNumbers, $guarantorName, $guaranteeName, $lga, $district, $location, $plotNumber, $planNumber, $size, $caveat);
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
     * Get all related file numbers from parent-child relationships - FOR GENERAL SEARCH
     */
    private function getAllRelatedFileNumbers($parentFileNumbers, $originalFileNo)
    {
        $allFileNumbers = [];

        foreach ($parentFileNumbers as $parentFileNo) {
            // Add the parent file number itself
            $allFileNumbers[] = $parentFileNo;

            // Find all child ST files for this parent (for general search)
            $childSTFiles = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('parent_fileNo', $parentFileNo)
                ->whereIn('instrument_type', ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'])
                ->pluck('StFileNo')
                ->toArray();
            
            $allFileNumbers = array_merge($allFileNumbers, array_filter($childSTFiles));

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

        // Include the original search file number
        if (!empty($originalFileNo)) {
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
        
        // ST File Number patterns: ST-RES-2024-01-001, ST-COM-2024-02-002, ST-IND-2024-03-009
        if (preg_match('/^ST-(RES|COM|IND|AG)-\d{4}-\d+-\d+$/i', $cleanFileNo)) {
            return 'st';
        }
        
        // Parent File Number (NP) patterns: ST-RES-2024-01, ST-COM-2024-02, ST-IND-2024-03
        if (preg_match('/^ST-(RES|COM|IND|AG)-\d{4}-\d+$/i', $cleanFileNo)) {
            return 'parent';
        }
        
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
        
        return 'unknown';
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
}