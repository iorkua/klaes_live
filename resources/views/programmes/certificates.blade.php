@extends('layouts.app')

@section('page-title')
    {{ $PageTitle ?? __('KLAES') }}
@endsection

@section('content')
<style>
    /* Enhanced Badge Styles */
    .badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        transition: all 0.2s ease-in-out;
    }
    
    .badge-approved {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        color: #047857;
        border: 1px solid #10b981;
    }
    
    .badge-pending {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #b45309;
        border: 1px solid #f59e0b;
    }
    
    .badge-declined {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        color: #b91c1c;
        border: 1px solid #ef4444;
    }
    
    .badge-issued {
        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
        color: #1d4ed8;
        border: 1px solid #3b82f6;
    }
    
    .badge-blocked {
        background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
        color: #991b1b;
        border: 1px solid #dc2626;
    }

    /* Enhanced Table Styles */
    .table-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        font-weight: 600;
        color: #1e40af;
        text-align: left;
        padding: 1rem;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .table-cell {
        padding: 1rem;
        border-bottom: 1px solid #f1f5f9;
        position: relative;
        vertical-align: middle;
        transition: background-color 0.2s ease-in-out;
    }
    
    .table-row:hover .table-cell {
        background-color: #f8fafc;
    }

    /* Enhanced Dropdown Styles */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: fixed;
        background-color: #ffffff;
        min-width: 220px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        z-index: 99999;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        transform: translateY(-5px);
        opacity: 0;
        transition: all 0.2s ease-in-out;
    }

    .dropdown-content a {
        color: #374151;
        padding: 0.875rem 1.25rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #f3f4f6;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }

    .dropdown-content a:hover {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: #1f2937;
        transform: translateX(4px);
    }

    .dropdown-content a:last-child {
        border-bottom: none;
    }

    .dropdown-content a[style*="color: #dc2626"] {
        color: #dc2626 !important;
    }

    .dropdown-content a[style*="color: #dc2626"]:hover {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #b91c1c !important;
    }

    .dropdown.show .dropdown-content {
        display: block;
        transform: translateY(0);
        opacity: 1;
    }

    .dropdown-toggle {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.2s ease-in-out;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dropdown-toggle:hover {
        background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
        border-color: #94a3b8;
        transform: scale(1.05);
    }

    .dropdown-toggle:active {
        transform: scale(0.95);
    }

    /* Enhanced Stats Cards */
    .stats-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        transition: all 0.3s ease-in-out;
        position: relative;
        overflow: hidden;
    }

    .stats-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        transform: scaleX(0);
        transition: transform 0.3s ease-in-out;
    }

    .stats-card:hover::before {
        transform: scaleX(1);
    }

    .stats-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Enhanced Tab Styles */
    .tab-button {
        position: relative;
        transition: all 0.3s ease-in-out;
        font-weight: 600;
    }

    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 50%;
        transform: translateX(-50%);
        width: 80%;
        height: 3px;
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
        border-radius: 2px;
    }

    /* Responsive Table */
    .table-container {
        overflow-x: auto;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
    }

    @media (max-width: 768px) {
        .table-cell {
            padding: 0.75rem 0.5rem;
            font-size: 0.875rem;
        }
        
        .dropdown-content {
            min-width: 180px;
            right: -50px;
        }
        
        .stats-card {
            padding: 1rem;
        }
    }

    /* Loading Animation */
    .loading-shimmer {
        background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%);
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { background-position: -200% 0; }
        100% { background-position: 200% 0; }
    }

    /* Enhanced Info Box */
    .info-box {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-left: 4px solid #3b82f6;
        border-radius: 0.5rem;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Ensure horizontal scroll so all columns (incl. Actions) are reachable */
    .tab-content {
        overflow-x: auto !important;
    }
    .tab-content table {
        width: max-content;   /* expand to fit columns */
        min-width: 100%;      /* at least full container width */
    }
    .tab-content th,
    .tab-content td {
        white-space: nowrap;  /* prevent wrapping so width can overflow and scroll */
    }
    /* Give Actions column some breathing room */
    .tab-content th:last-child,
    .tab-content td:last-child {
        min-width: 140px;
    }
</style>

<div class="flex-1 overflow-auto">
    <!-- Header -->
    @include($headerPartial ?? 'admin.header')
    
    <!-- Main Content -->
    <div class="p-6">
        <div class="bg-white rounded-md shadow-sm p-6">
            <h2 class="text-xl font-bold mb-6">ST Certificate of Occupancy Management</h2>
            
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i data-lucide="info" class="w-5 h-5 text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            This dashboard shows all approved applications that are eligible for ST Certificate of Occupancy issuance.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-500 text-sm font-medium">Total Eligible Applications</h3>
                        <span class="text-blue-500 bg-blue-100 p-2 rounded-full">
                            <i data-lucide="file-text" class="w-5 h-5"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ collect($approvedUnitApplications)->where('planning_recommendation_status', 'Approved')->where('application_status', 'Approved')->count() }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-500 text-sm font-medium">Generated Certificates</h3>
                        <span class="text-green-500 bg-green-100 p-2 rounded-full">
                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ collect($approvedUnitApplications)->where('certificate_issued', true)->count() }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-gray-500 text-sm font-medium">Not Generated</h3>
                        <span class="text-yellow-500 bg-yellow-100 p-2 rounded-full">
                            <i data-lucide="clock" class="w-5 h-5"></i>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ collect($approvedUnitApplications)->where('certificate_issued', '!=', true)->count() }}</p>
                </div>
            </div>

            <!-- Applications Table -->
            <div class="bg-white rounded-md shadow-sm border border-gray-200 overflow-hidden">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-lg font-medium">Approved Applications Eligible for Certificate</h3>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px">
                        <button id="tab-not-generated" class="tab-button active py-4 px-6 text-center border-b-2 border-blue-500 font-medium text-blue-600 flex-1" data-tab="not-generated">
                            Not Generated <span class="ml-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">{{ collect($approvedUnitApplications)->where('certificate_issued', '!=', true)->count() }}</span>
                        </button>
                        <button id="tab-generated" class="tab-button py-4 px-6 text-center border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium flex-1" data-tab="generated">
                            Generated <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">{{ collect($approvedUnitApplications)->where('certificate_issued', true)->count() }}</span>
                        </button>
                    </nav>
                </div>
                
                <!-- Not Generated Certificates Table -->
                <div id="content-not-generated" class="tab-content overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-xs">
                                <th class="table-header">File No</th>
                                <th class="table-header">Scheme No</th>
                                <th class="table-header">Unit Owner</th>
                                 
                                <th class="table-header">Unit/Section/Block</th>
                                <th class="table-header">Land Use</th>
                                <th class="table-header">RegNo</th>
                                <th class="table-header">Prerequisites</th>
                                <th class="table-header">Status</th>
                                <th class="table-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $notGeneratedCount = 0; @endphp
                            @foreach($approvedUnitApplications as $application)
                                @if(!$application->certificate_issued)
                                    @php 
                                        $notGeneratedCount++;
                                        
                                        // Check prerequisites
                                        $hasSTMemo = \DB::connection('sqlsrv')->table('memos')
                                            ->where('application_id', $application->main_application_id ?? $application->id)
                                            ->exists();
                                            
                                        $hasRofo = \DB::connection('sqlsrv')->table('rofo')
                                            ->where('sub_application_id', $application->id)
                                            ->where('active', 1)
                                            ->exists();

                                        // Check ST CofO and Reg Particulars with robust matching and ordering
                                        $cleanFileNo = trim($application->fileno ?? '');
                                        $instrument = \DB::connection('sqlsrv')->table('registered_instruments')
                                            ->where(function($q) use ($cleanFileNo) {
                                                $q->whereRaw("UPPER(RTRIM(LTRIM(registered_instruments.StFileNo))) = UPPER(RTRIM(LTRIM(?)))", [$cleanFileNo])
                                                  ->orWhereRaw("UPPER(REPLACE(registered_instruments.StFileNo,' ','')) = UPPER(REPLACE(?,' ',''))", [$cleanFileNo]);
                                            })
                                            ->whereNotNull('registered_instruments.particularsRegistrationNumber')
                                            ->orderByRaw("CASE WHEN UPPER(LTRIM(RTRIM(registered_instruments.instrument_type))) LIKE 'SECTIONAL TITLING%' THEN 0 ELSE 1 END")
                                            ->orderByRaw("CASE WHEN registered_instruments.status = 'registered' THEN 0 ELSE 1 END")
                                            ->orderByDesc('registered_instruments.id')
                                            ->first();
                                        $hasSTCofO = !empty($instrument);
                                        $regParticulars = $instrument->particularsRegistrationNumber ?? ($application->RegNo ?? 'N/A');

                                        $canGenerate = $hasSTMemo && $hasRofo && $hasSTCofO;
                                        $missingItems = [];
                                        if (!$hasSTMemo) $missingItems[] = 'ST Memo';
                                        if (!$hasRofo) $missingItems[] = 'RofO';
                                        if (!$hasSTCofO) $missingItems[] = 'ST CofO';
                                    @endphp
                                    <tr class="text-sm text-gray-700">
                                        <td class="table-cell">{{ $application->fileno }}</td>
                                        <td class="table-cell">{{ $application->scheme_no }}</td>
                                        <td class="table-cell">{{ $application->owner_name }}</td>
                                        
                                        <td class="table-cell">
                                        {{ $application->unit_number ?? 'N/A' }}-{{ $application->block_number ?? 'N/A' }}-{{ $application->floor_number ?? 'N/A' }}
                                        </td>
                                        <td class="table-cell">{{ $application->land_use }}</td>
                                        <td class="table-cell">{{ $regParticulars }}</td>
                                        <td class="table-cell">
                                            <div class="flex flex-wrap gap-1">
                                                <span class="badge {{ $hasSTMemo ? 'badge-approved' : 'badge-pending' }}">
                                                    <i data-lucide="{{ $hasSTMemo ? 'check' : 'x' }}" class="w-3 h-3 mr-1"></i>
                                                    ST Memo
                                                </span>
                                                <span class="badge {{ $hasRofo ? 'badge-approved' : 'badge-pending' }}">
                                                    <i data-lucide="{{ $hasRofo ? 'check' : 'x' }}" class="w-3 h-3 mr-1"></i>
                                                    RofO
                                                </span>
                                                <span class="badge {{ $hasSTCofO ? 'badge-approved' : 'badge-pending' }}">
                                                    <i data-lucide="{{ $hasSTCofO ? 'check' : 'x' }}" class="w-3 h-3 mr-1"></i>
                                                    ST CofO
                                                </span>
                                            </div>
                                        </td>
                                        <td class="table-cell">
                                            @if($canGenerate)
                                                <span class="badge badge-approved">Ready to Generate</span>
                                            @else
                                                <span class="badge badge-blocked">Pending</span>
                                            @endif
                                        </td>
                                        <td class="table-cell">
                                            <div class="dropdown">
                                                <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                                                    <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                                                </button>
                                                <div class="dropdown-content">
                                                    <a href="{{ route('sectionaltitling.viewrecorddetail_sub', $application->id) }}">
                                                        <i data-lucide="eye" class="w-4 h-4 mr-2 inline"></i>
                                                        View Application
                                                    </a>
                                                    @if($canGenerate)
                                                        <a href="{{route('programmes.generate_cofo', $application->id)}}">
                                                            <i data-lucide="file-text" class="w-4 h-4 mr-2 inline"></i>
                                                            Generate CofO
                                                        </a>
                                                    @else
                                                        <a href="#" onclick="showPrerequisiteError({{ json_encode($missingItems) }}); return false;" style="color: #ecececce;">
                                                            <i data-lucide="alert-circle" class="w-4 h-4 mr-2 inline"></i>
                                                           Generate CofO
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            @if($notGeneratedCount == 0)
                                <tr>
                                    <td colspan="10" class="table-cell text-center py-4">No applications pending certificate generation</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
                
                <!-- Generated Certificates Table -->
                <div id="content-generated" class="tab-content hidden overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-xs">
                                <th class="table-header">File No</th>
                                <th class="table-header">CofONo</th> 
                                <th class="table-header">RegNo</th>
                                <th class="table-header">Scheme No</th>
                                <th class="table-header">Unit Owner</th>
                               
                                <th class="table-header">Unit/Section/Block</th>
                                <th class="table-header">Land Use</th>
                                <th class="table-header">Status</th>
                                <th class="table-header">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php $generatedCount = 0; @endphp
                            @foreach($approvedUnitApplications as $application)
                                @if($application->certificate_issued)
                                    @php 
                                        $generatedCount++;
                                        // Fetch registration number with robust matching and ordering
                                        $cleanFileNo = trim($application->fileno ?? '');
                                        $instrument = \DB::connection('sqlsrv')->table('registered_instruments')
                                            ->where(function($q) use ($cleanFileNo) {
                                                $q->whereRaw("UPPER(RTRIM(LTRIM(registered_instruments.StFileNo))) = UPPER(RTRIM(LTRIM(?)))", [$cleanFileNo])
                                                  ->orWhereRaw("UPPER(REPLACE(registered_instruments.StFileNo,' ','')) = UPPER(REPLACE(?,' ',''))", [$cleanFileNo]);
                                            })
                                            ->whereNotNull('registered_instruments.particularsRegistrationNumber')
                                            ->orderByRaw("CASE WHEN UPPER(LTRIM(RTRIM(registered_instruments.instrument_type))) LIKE 'SECTIONAL TITLING%' THEN 0 ELSE 1 END")
                                            ->orderByRaw("CASE WHEN registered_instruments.status = 'registered' THEN 0 ELSE 1 END")
                                            ->orderByDesc('registered_instruments.id')
                                            ->first();
                                        $regParticulars = $instrument->particularsRegistrationNumber ?? ($application->RegNo ?? 'N/A');
                                        $application->RegNo = $regParticulars; // optional downstream use
                                    @endphp
                                    <tr class="text-sm text-gray-700">
                                        <td class="table-cell">{{ $application->fileno }}</td> 
                                        <td class="table-cell">{{ $application->certificate_number ?? 'N/A' }}</td>
                                        <td class="table-cell">{{ $regParticulars }}</td>
                                        <td class="table-cell">{{ $application->scheme_no }}</td>
                                        <td class="table-cell">{{ $application->owner_name }}</td>
                                
                                        <td class="table-cell">
                                            {{ $application->unit_number ?? 'N/A' }}-{{ $application->block_number ?? 'N/A' }}-{{ $application->floor_number ?? 'N/A' }}
                                            </td>
                                        <td class="table-cell">{{ $application->land_use }}</td>
                                        <td class="table-cell">
                                            <span class="badge badge-issued">Generated</span>
                                        </td>
                                        <td class="table-cell">
                                            <div class="dropdown">
                                                <button class="dropdown-toggle" onclick="toggleDropdown(this)">
                                                    <i data-lucide="more-horizontal" class="w-5 h-5"></i>
                                                </button>
                                                <div class="dropdown-content">
                                                    <a href="{{ route('sectionaltitling.viewrecorddetail_sub', $application->id) }}">
                                                        <i data-lucide="eye" class="w-4 h-4 mr-2 inline"></i>
                                                        View Application
                                                    </a>
                                                    <a href="{{route('programmes.view_cofo', $application->id)}}">
                                                        <i data-lucide="file-text" class="w-4 h-4 mr-2 inline"></i>
                                                        View Certificate
                                                    </a>
                                                    <a href="{{ route('programmes.view_cofo', $application->id) }}">
                                                        <i data-lucide="printer" class="w-4 h-4 mr-2 inline"></i>
                                                        Print Certificate
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                            @if($generatedCount == 0)
                                <tr>
                                    <td colspan="10" class="table-cell text-center py-4">No generated certificates found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Page Footer -->
    @include($footerPartial ?? 'admin.footer')
</div>

<script>
    // Enhanced dropdown toggle function with proper positioning
    function toggleDropdown(button) {
        // Close all other dropdowns first
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            if (dropdown !== button.parentElement) {
                dropdown.classList.remove('show');
            }
        });
        
        const dropdown = button.parentElement;
        const dropdownContent = dropdown.querySelector('.dropdown-content');
        
        // Toggle current dropdown
        const isCurrentlyOpen = dropdown.classList.contains('show');
        
        if (isCurrentlyOpen) {
            dropdown.classList.remove('show');
        } else {
            // Position the dropdown before showing it
            positionDropdown(button, dropdownContent);
            dropdown.classList.add('show');
        }
        
        // Prevent event bubbling
        if (event) {
            event.stopPropagation();
        }
    }

    // Function to position dropdown properly
    function positionDropdown(button, dropdownContent) {
        const buttonRect = button.getBoundingClientRect();
        const dropdownWidth = 220; // min-width from CSS
        const dropdownHeight = dropdownContent.scrollHeight || 150; // estimated height
        
        // Calculate position
        let left = buttonRect.right - dropdownWidth;
        let top = buttonRect.bottom + 4;
        
        // Adjust if dropdown would go off-screen to the left
        if (left < 10) {
            left = buttonRect.left;
        }
        
        // Adjust if dropdown would go off-screen to the right
        const windowWidth = window.innerWidth;
        if (left + dropdownWidth > windowWidth - 10) {
            left = windowWidth - dropdownWidth - 10;
        }
        
        // Adjust if dropdown would go off-screen at the bottom
        const windowHeight = window.innerHeight;
        if (top + dropdownHeight > windowHeight - 10) {
            top = buttonRect.top - dropdownHeight - 4;
        }
        
        // Apply the calculated position
        dropdownContent.style.left = left + 'px';
        dropdownContent.style.top = top + 'px';
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown').forEach(dropdown => {
                dropdown.classList.remove('show');
            });
        }
    });

    // Close dropdowns on scroll to prevent misalignment
    window.addEventListener('scroll', function() {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });

    // Close dropdowns on window resize
    window.addEventListener('resize', function() {
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            dropdown.classList.remove('show');
        });
    });

    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Close any open dropdowns when switching tabs
                document.querySelectorAll('.dropdown').forEach(dropdown => {
                    dropdown.classList.remove('show');
                });
                
                // Remove active class from all buttons
                tabButtons.forEach(btn => {
                    btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                    btn.classList.add('border-transparent', 'text-gray-500');
                });
                
                // Add active class to clicked button
                this.classList.add('active', 'border-blue-500', 'text-blue-600');
                this.classList.remove('border-transparent', 'text-gray-500');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show the selected tab content
                const tabKey = this.getAttribute('data-tab');
                const targetContent = document.getElementById('content-' + tabKey);
                if (targetContent) {
                    targetContent.classList.remove('hidden');
                }
            });
        });
    });

    // Function to show prerequisite error
    function showPrerequisiteError(missingItems) {
        let message = 'Cannot generate Certificate of Occupancy. The following prerequisites are missing:\n\n';
        missingItems.forEach(item => {
            message += '• ' + item + '\n';
        });
        message += '\nPlease ensure all prerequisites are completed before generating the CofO.';
        
        // Use SweetAlert if available, otherwise fallback to alert
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Prerequisites Missing',
                text: message,
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        } else {
            alert(message);
        }
    }
</script>
@endsection