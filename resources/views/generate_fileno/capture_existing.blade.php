@extends('layouts.app')
@section('page-title')
    {{ __('Capture Existing File Numbers') }}
@endsection

@push('styles')
    <style>
        #captureTable_wrapper .dataTables_filter input {
            border-radius: 9999px;
            border: 1px solid #d1d5db;
            padding: 0.5rem 1rem;
            background-color: #f8fafc;
            color: #1f2937;
        }

        #captureTable_wrapper .dataTables_filter input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
            border-color: #10b981;
            background-color: #ffffff;
        }

        #captureTable_wrapper .dataTables_length select {
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            padding: 0.25rem 0.75rem;
            background-color: #ffffff;
        }

        #captureTable thead th,
        #captureTable tbody td {
            white-space: nowrap !important;
        }

        #captureTable tbody tr:nth-child(odd) {
            background-color: rgba(248, 250, 252, 0.6);
        }

        #captureTable tbody tr:hover {
            background-color: rgba(209, 250, 229, 0.55) !important;
        }
    </style>
@endpush

@section('content') 
    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <!-- Header -->
        @include('admin.header', [
            'PageTitle' => 'Capture Existing File Numbers',
            'PageDescription' => 'Capture and manage existing file numbers'
        ])
        
        <!-- Dashboard Content -->
        <div class="p-6">
            <div class="container mx-auto py-6 space-y-6">
                
                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex space-x-4">
                        <button 
                            onclick="openCaptureModal()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Capture Existing File</span>
                        </button>
                        <button 
                            onclick="openMigrationModal()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Migrate Data</span>
                        </button>  
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <div class="text-sm text-gray-700 font-medium">
                                Total Captured: 
                                <span id="totalCount" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold bg-green-100 text-green-800 ml-1">
                                    {{ $totalCount ?? 0 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                @php
                    $stats = [
                        [
                            'label' => 'MLS File No',
                            'value' => number_format($mlsfCount ?? 0),
                            'icon' => 'square-stack',
                            'accent' => 'text-sky-600',
                            'chip' => 'bg-sky-100 text-sky-700'
                        ],
                        [
                            'label' => 'KANGIS File No',
                            'value' => number_format($kangisCount ?? 0),
                            'icon' => 'map',
                            'accent' => 'text-amber-600',
                            'chip' => 'bg-amber-100 text-amber-700'
                        ],
                        [
                            'label' => 'New KANGIS File No',
                            'value' => number_format($newKangisCount ?? 0),
                            'icon' => 'sparkles',
                            'accent' => 'text-fuchsia-600',
                            'chip' => 'bg-fuchsia-100 text-fuchsia-700'
                        ],
                    ];
                @endphp

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4 mb-6">
                    @foreach ($stats as $stat)
                        <div class="bg-white/90 backdrop-blur border border-slate-100 rounded-xl shadow-sm p-5 flex flex-col gap-4">
                            <div class="flex items-center justify-between">
                                <div class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 uppercase tracking-wide">
                                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-slate-100">
                                        <i data-lucide="{{ $stat['icon'] }}" class="w-4 h-4 {{ $stat['accent'] }}"></i>
                                    </span>
                                    {{ $stat['label'] }}
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $stat['chip'] }}">
                                    Live
                                </span>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-slate-800">{{ $stat['value'] }}</span>
                                <span class="text-xs font-semibold text-slate-400">records captured</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- DataTable -->
                <div class="bg-white/90 backdrop-blur-md rounded-2xl border border-slate-100 shadow-xl">
                    <div class="px-6 pt-6 pb-3 space-y-5">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 text-slate-800 text-base font-semibold">
                                    <i data-lucide="grid" class="w-4 h-4 text-emerald-500"></i>
                                    <span>Captured File Registry</span>
                                </div>
                            
                            </div>
                            
                        </div>
                    </div>
                    <div class="px-6 pb-6">
                        <div class="overflow-x-auto">
                            <table id="captureTable" class="min-w-full divide-y divide-slate-100 text-sm">
                                <thead>
                                    <tr class="bg-slate-50/60">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">MLS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">KANGIS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">New KANGIS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Source</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">File Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Plot No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">TP No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Captured By</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Capture Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100 text-slate-600">
                                    <!-- Data will be loaded via DataTables AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        @include('admin.footer')
    </div>

    <!-- Capture Modal -->
    <div id="captureModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-5 mx-auto p-6 border w-[800px] max-w-4xl shadow-xl rounded-lg bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div>
                        <h3 id="modalTitle" class="text-xl font-semibold text-gray-900">Capture Existing File</h3>
                        <p class="text-sm text-gray-500 mt-1">Enter the details of an existing file number to capture it in the system</p>
                    </div>
                    <button onclick="closeCaptureModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form id="captureForm" onsubmit="submitCaptureForm(event)" class="space-y-6">
                    @csrf
                    <input type="hidden" id="trackingId" name="tracking_id" value="{{ $trackingId ?? '' }}">
                    
                    <!-- Main Form Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">

                            <!-- Tracking ID Display -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 max-w-xs">
                                <div class="text-gray-700 font-mono text-sm font-bold whitespace-nowrap">
                                    <i data-lucide="file-search" class="inline h-2 w-2 mr-1"></i>
                                    Tracking ID: <span id="trackingIdDisplay" class="font-bold text-slate-500">
                                        {{ $trackingId ?? 'Awaiting grouping match' }}</span>
                                </div>
                                <div id="trackingIdStatus" class="text-xs text-slate-500 mt-1"></div>
                            </div>
                            <div>
                                <label for="fileOption" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="settings" class="w-4 h-4 inline mr-1"></i>
                                    File Options
                                </label>
                                <select id="fileOption" name="file_option" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        onchange="updateCaptureForm(this.value)" required>
                                    <option value="normal" selected>Normal File</option>
                                    <option value="temporary">Temporary File</option>
                                    <option value="extension">Extension</option>
                                    <option value="miscellaneous">Miscellaneous</option>
                                    <option value="old_mls">Old MLS</option>
                                    <option value="sltr">SLTR</option>
                                    <option value="sit">SIT</option>
                                </select>
                            </div>
                            <!-- File Name -->
                            <div>
                                <label for="fileName" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                                    File Name
                                </label>
                                <input type="text" id="fileName" name="file_name" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter file name" required>
                            </div>
                            
                            <!-- Prefix (for normal files) -->
                            <div id="prefixSection">
                                <label for="prefix" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i>
                                    Prefix
                                </label>
                                <select id="prefix" name="prefix" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                        onchange="updateCapturePreview()">
                                    <option value="">Select Prefix</option>
                                    <!-- Standard Options -->
                                    <optgroup label="Standard">
                                        <option value="RES">RES - Residential</option>
                                        <option value="COM">COM - Commercial</option>
                                        <option value="IND">IND - Industrial</option>
                                        <option value="AG">AG - Agricultural</option>
                                    </optgroup>
                                    <!-- Conversion Options -->
                                    <optgroup label="Conversion">
                                        <option value="CON-RES">CON-RES - Conversion to Residential</option>
                                        <option value="CON-COM">CON-COM - Conversion to Commercial</option>
                                        <option value="CON-IND">CON-IND - Conversion to Industrial</option>
                                        <option value="CON-AG">CON-AG - Conversion to Agricultural</option>
                                        <option value="CON-RES-RC">CON-RES-RC - Conversion to Residential</option>
                                        <option value="CON-COM-RC">CON-COM-RC - Conversion to Commercial</option>
                                        <option value="CON-AG-RC">CON-AG-RC - Conversion to Agricultural</option>
                                    </optgroup>
                                    <!-- RC Options -->
                                    <optgroup label="RC Options">
                                        <option value="RES-RC">RES-RC</option>
                                        <option value="COM-RC">COM-RC</option>
                                        <option value="AG-RC">AG-RC</option>
                                        <option value="IND-RC">IND-RC</option>
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Middle Prefix (for miscellaneous files) -->
                            <div id="middlePrefixSection" class="hidden">
                                <label for="middlePrefix" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i>
                                    Middle Prefix
                                </label>
                                <input type="text" id="middlePrefix" name="middle_prefix" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="e.g., KN" onchange="updateCapturePreview()" value="KN">
                            </div>

                            <!-- Plot Number -->
                            <div>
                                <label for="plotNo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                    Plot Number
                                </label>
                                <input type="text" id="plotNo" name="plot_no" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter plot number">
                            </div>

                            <!-- TP Number -->
                            <div>
                                <label for="tpNo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                                    TP Number
                                </label>
                                <input type="text" id="tpNo" name="tp_no" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter TP number">
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="map" class="w-4 h-4 inline mr-1"></i>
                                    Location
                                </label>
                                <input type="text" id="location" name="location" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter location details">
                            </div>

                                <!-- Commissioned By -->
                          
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <!-- Extension File Selection -->
                            <div id="extensionFileSection" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="link" class="w-4 h-4 inline mr-1"></i>
                                    Select Existing MLS File Number
                                </label>
                                
                                <!-- Toggle between dropdown and manual input -->
                                <div class="flex items-center mb-3 space-x-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="extension_input_type" id="extensionDropdown" value="false" class="mr-2 text-green-600" checked>
                                        <span class="text-sm">Select from existing</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="extension_input_type" id="extensionManual" value="true" class="mr-2 text-green-600">
                                        <span class="text-sm">Enter manually</span>
                                    </label>
                                </div>

                                <!-- Dropdown for existing files -->
                                <div id="extensionDropdownSection">
                                    <select id="existingFileNo" name="existing_file_no" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                            onchange="updateCapturePreview()">
                                        <option value="">Select existing file number...</option>
                                        <!-- Options will be populated via AJAX -->
                                    </select>
                                </div>

                                <!-- Manual input field -->
                                <div id="extensionManualInput" class="hidden">
                                    <input type="text" id="extensionManualFile" name="existing_file_no_manual" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           placeholder="Enter file number manually (e.g., RES-2024-0567)"
                                           oninput="updateCapturePreview()">
                                    <p class="text-xs text-gray-500 mt-1">Enter the exact file number you want to extend</p>
                                </div>
                            </div>

                            <!-- Temporary File Selection -->
                            <div id="temporaryFileSection" class="hidden">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="clock" class="w-4 h-4 inline mr-1"></i>
                                    Select Existing File for Temporary Version
                                </label>
                                
                                <!-- Toggle between dropdown and manual input -->
                                <div class="flex items-center mb-3 space-x-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="temporary_input_type" id="temporaryDropdown" value="false" class="mr-2 text-green-600" checked>
                                        <span class="text-sm">Select from existing</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="temporary_input_type" id="temporaryManual" value="true" class="mr-2 text-green-600">
                                        <span class="text-sm">Enter manually</span>
                                    </label>
                                </div>

                                <!-- Dropdown for existing files -->
                                <div id="temporaryDropdownSection">
                                    <select id="temporaryFileNo" name="existing_file_no" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                            onchange="updateCapturePreview()">
                                        <option value="">Select existing file number...</option>
                                        <!-- Options will be populated via AJAX -->
                                    </select>
                                </div>

                                <!-- Manual input field -->
                                <div id="temporaryManualInput" class="hidden">
                                    <input type="text" id="temporaryManualFile" name="existing_file_no_manual" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           placeholder="Enter file number manually (e.g., RES-2024-0567)"
                                           oninput="updateCapturePreview()">
                                    <p class="text-xs text-gray-500 mt-1">Enter the exact file number for temporary version</p>
                                </div>
                            </div>

                            <!-- Year (for normal files) -->
                            <div id="yearSection" class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                        Year
                                    </label>
                                    <input type="number" id="year" name="year" 
                                           value="{{ date('Y') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           min="1900" max="2050" onchange="updateCapturePreview()">
                                </div>

                                <!-- Serial Number -->
                                <div>
                                    <label for="serialNo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                                        Serial Number
                                    </label>
                                    <input type="text" id="serialNo" name="serial_no" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                           placeholder="Enter serial number" onchange="updateCapturePreview()" required>
                                </div>
                            </div>

                            <!-- Full File Number Preview -->
                            <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-4 rounded-lg border border-green-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i>
                                    File Number Preview
                                </label>
                                <div id="capturePreview" class="w-full px-4 py-3 bg-white border border-green-300 rounded-md text-lg font-mono text-center text-green-800 font-bold shadow-sm">
                                    -
                                </div>
                            </div>

                                 <!-- Commission Date -->
                                 <div>
                                <label for="commissionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4 inline mr-1"></i>
                                    Capture Date 
                                </label>
                                <input type="text" id="commissionDate" name="commission_date"                 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100"
                                       placeholder="Auto-filled" disabled value="{{ date('Y-m-d') }}">
                            </div>

                            <div>
                                <label for="commissionedBy" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="user-check" class="w-4 h-4 inline mr-1"></i>
                                    Captured By
                                </label>
                                <input type="text" id="commissionedBy" name="commissioned_by"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100"
                                       placeholder="Auto-filled" disabled value="{{ Auth::user()->name }}">
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <button type="button" onclick="closeCaptureModal()" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors flex items-center space-x-2">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Submit</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Migration Modal -->
    <div id="migrationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Migrate Data from Excel</h3>
                    <button onclick="closeMigrationModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Migration Form -->
                <form id="migrationForm" onsubmit="submitMigrationForm(event)" enctype="multipart/form-data">
                    @csrf
                    
                    <!-- File Upload -->
                    <div class="mb-4">
                        <label for="excelFile" class="block text-sm font-medium text-gray-700 mb-2">CSV File</label>
                        <input type="file" id="excelFile" name="excel_file" 
                               accept=".csv,.txt"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Upload CSV file with columns: mlsfNo, kangisFile, NewKANGISFileNo, FileName (ignore SN column)</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeMigrationModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Migrate Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-[500px] shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit File Record</h3>
                    <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Edit Form -->
                <form id="editForm" onsubmit="submitEditForm(event)">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="editId" name="id">
                    
                    <!-- MLSF Number (Read-only) -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">MLSF Number</label>
                        <input type="text" id="editMlsfNo" 
                               class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md"
                               readonly>
                    </div>

                    <!-- KANGIS File Number (Editable) -->
                    <div class="mb-4">
                        <label for="editKangisFileNo" class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="file" class="w-4 h-4 inline mr-1"></i>
                            KANGIS File No
                        </label>
                        <input type="text" id="editKangisFileNo" name="kangis_file_no" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter KANGIS file number">
                    </div>

                    <!-- New KANGIS File Number (Editable) -->
                    <div class="mb-4">
                        <label for="editNewKangisFileNo" class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="file-plus" class="w-4 h-4 inline mr-1"></i>
                            New KANGIS File No
                        </label>
                        <input type="text" id="editNewKangisFileNo" name="new_kangis_file_no" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter new KANGIS file number">
                    </div>

                    <!-- File Name (Editable) -->
                    <div class="mb-4">
                        <label for="editFileName" class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                            File Name
                        </label>
                        <input type="text" id="editFileName" name="file_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Enter file name" required>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        let table;

        const BADGE_VARIANTS = {
            mls: { wrapper: 'border border-sky-200 bg-sky-100 text-sky-700', dot: 'bg-sky-500' },
            st: { wrapper: 'border border-emerald-200 bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500' },
            kangis: { wrapper: 'border border-amber-200 bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
            newkangis: { wrapper: 'border border-fuchsia-200 bg-fuchsia-100 text-fuchsia-700', dot: 'bg-fuchsia-500' },
            muted: { wrapper: 'border border-slate-200 bg-slate-100 text-slate-500', dot: 'bg-slate-400' }
        };

        function sanitizeValue(value) {
            return $('<div/>').text(value ?? '').html();
        }

        function renderFileBadge(value, variant = 'muted') {
            if (!value || value === 'N/A' || value === '-' || (typeof value === 'string' && value.trim() === '')) {
                const muted = BADGE_VARIANTS.muted;
                return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${muted.wrapper} whitespace-nowrap">
                            <span class="w-1.5 h-1.5 rounded-full ${muted.dot}"></span>
                            -
                        </span>`;
            }

            const sanitized = sanitizeValue(value);
            const config = BADGE_VARIANTS[variant] ?? BADGE_VARIANTS.muted;

            return `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold ${config.wrapper} whitespace-nowrap">
                        <span class="w-1.5 h-1.5 rounded-full ${config.dot}"></span>
                        ${sanitized}
                    </span>`;
        }

        function renderPlainText(value, fallback = '-') {
            if (!value || value === 'N/A' || value === '-' || (typeof value === 'string' && value.trim() === '')) {
                return `<span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-xs font-semibold text-slate-500">${fallback}</span>`;
            }

            return `<span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-50 text-sm font-medium text-slate-700">${sanitizeValue(value)}</span>`;
        }

        // Loading utility functions
        function showLoadingButton(buttonElement, originalText) {
            if (buttonElement) {
                buttonElement.disabled = true;
                buttonElement.innerHTML = `
                    <i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i>
                    Loading...
                `;
                lucide.createIcons();
            }
        }

        function hideLoadingButton(buttonElement, originalText) {
            if (buttonElement) {
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalText;
                lucide.createIcons();
            }
        }

        function showGlobalLoading(message = 'Processing...') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        function hideGlobalLoading() {
            Swal.close();
        }

        // Debug function to test the endpoint
        function testDataEndpoint() {
            console.log('Testing data endpoint...');
            fetch('{{ route("existing-file-numbers.data") }}?draw=1&start=0&length=5', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Endpoint test result:', data);
            })
            .catch(error => {
                console.error('Endpoint test error:', error);
            });
        }

        $(document).ready(function() {
            lucide.createIcons();
            
            // Test the endpoint first
            testDataEndpoint();
            
            // Initialize DataTable
            table = $('#captureTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route("existing-file-numbers.data") }}',
                    type: 'GET',
                    data: function(d) {
                        console.log('DataTables request:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('DataTables response:', json);
                        if (json.error) {
                            console.error('Server error:', json.error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: json.error,
                                confirmButtonColor: '#ef4444'
                            });
                            return [];
                        }
                        if (!json.data) {
                            console.warn('No data property in response:', json);
                            return [];
                        }
                        console.log('Returning data array with length:', json.data.length);
                        return json.data;
                    },
                    error: function(xhr, error, code) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        
                        let errorMessage = 'Failed to load captured file numbers. Please check your connection and try again.';
                        
                        if (xhr.status === 500) {
                            errorMessage = 'Server error occurred. Please contact the administrator.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Data endpoint not found. Please contact the administrator.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network connection error. Please check your internet connection.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Loading Data',
                            text: errorMessage,
                            confirmButtonColor: '#ef4444',
                            footer: `<small>Error Code: ${xhr.status} - ${error}</small>`
                        });
                    }
                },
                columns: [
                    { 
                        data: 'mlsfNo', 
                        name: 'mlsfNo',
                        title: 'MLS File No',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap font-semibold text-slate-700',
                        render: function(data, type, row) {
                            return renderFileBadge(data, 'mls');
                        }
                    },

                    { 
                        data: 'kangisFileNo', 
                        name: 'kangisFileNo',
                        title: 'KANGIS File No',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap font-semibold text-slate-700',
                        render: function(data, type, row) {
                            return renderFileBadge(data, 'kangis');
                        }
                    },
                    { 
                        data: 'NewKANGISFileNo', 
                        name: 'NewKANGISFileNo',
                        title: 'New KANGIS File No',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap font-semibold text-slate-700',
                        render: function(data, type, row) {
                            return renderFileBadge(data, 'newkangis');
                        }
                    },
                    {
                        data: 'source',
                        name: 'source',
                        title: 'Source',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data, '-');
                        }
                    },
                    { 
                        data: 'FileName', 
                        name: 'FileName',
                        title: 'File Name',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data, 'Not Captured');
                        }
                    },
                    {
                        data: 'plot_no',
                        name: 'plot_no',
                        title: 'Plot No',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data);
                        }
                    },
                    { 
                        data: 'tp_no', 
                        name: 'tp_no',
                        title: 'TP No',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data);
                        }
                    },
                    { 
                        data: 'location', 
                        name: 'location',
                        title: 'Location',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data);
                        }
                    },

                    { 
                        data: 'created_by', 
                        name: 'created_by',
                        title: 'Captured By',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            return renderPlainText(data, 'System');
                        }
                    },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        title: 'Capture Date',
                        defaultContent: '-',
                        className: 'align-middle whitespace-nowrap text-slate-600',
                        render: function(data, type, row) {
                            if (data && data !== '-' && data.trim() !== '') {
                                try {
                                    const date = new Date(data);
                                    if (!isNaN(date)) {
                                        const formatted = date.toLocaleDateString('en-US', {
                                            year: 'numeric',
                                            month: 'short',
                                            day: 'numeric',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                            hour12: true
                                        });
                                        return `<span class="inline-flex items-center px-2 py-1 rounded-md bg-emerald-50 text-xs font-semibold text-emerald-700">${formatted}</span>`;
                                    }
                                } catch (e) {
                                    console.log('Date parsing error:', e);
                                }
                            }
                            return renderPlainText(null);
                        }
                    },
                    { 
                        data: 'action', 
                        name: 'action', 
                        title: 'Actions',
                        orderable: false, 
                        searchable: false,
                        className: 'align-middle whitespace-nowrap text-center text-slate-500',
                        defaultContent: '<span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-xs font-semibold text-slate-500">No actions</span>',
                        render: function(data, type, row) {
                            if (data && data !== '-' && data.trim() !== '') {
                                return data;
                            }
                            return '<span class="inline-flex items-center px-2 py-1 rounded-md bg-slate-100 text-xs font-semibold text-slate-500">No actions</span>';
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    processing: '<div class="flex items-center justify-center"><i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i>Loading captured file numbers...</div>',
                    emptyTable: '<div class="text-center py-8"><div class="text-gray-400 mb-2"><i data-lucide="database" class="w-12 h-12 mx-auto mb-2"></i></div><h3 class="text-lg font-medium text-gray-900 mb-1">No captured file numbers found</h3><p class="text-gray-500">Start by capturing your first existing file number using the button above.</p></div>',
                    zeroRecords: '<div class="text-center py-8"><div class="text-gray-400 mb-2"><i data-lucide="search" class="w-12 h-12 mx-auto mb-2"></i></div><h3 class="text-lg font-medium text-gray-900 mb-1">No matching records found</h3><p class="text-gray-500">Try adjusting your search criteria.</p></div>',
                    info: "Showing _START_ to _END_ of _TOTAL_ captured file numbers",
                    infoEmpty: "No captured file numbers available",
                    infoFiltered: "(filtered from _MAX_ total captured file numbers)",
                    lengthMenu: "Show _MENU_ captured file numbers per page",
                    search: "Search captured file numbers:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                drawCallback: function(settings) {
                    // Reinitialize Lucide icons after table redraw
                    setTimeout(function() {
                        lucide.createIcons();
                    }, 100);
                    
                    // Log draw information for debugging
                    console.log('DataTable draw completed:', {
                        recordsTotal: settings.json?.recordsTotal || 0,
                        recordsFiltered: settings.json?.recordsFiltered || 0,
                        dataLength: settings.json?.data?.length || 0
                    });
                },
                createdRow: function(row) {
                    $(row).addClass('transition-colors');
                },
                initComplete: function(settings, json) {
                    console.log('DataTable initialized:', {
                        recordsTotal: json?.recordsTotal || 0,
                        recordsFiltered: json?.recordsFiltered || 0,
                        dataLength: json?.data?.length || 0
                    });
                    
                    // Show a message if no data is available
                    if (json && json.recordsTotal === 0) {
                        console.log('No records found in database');
                    }
                }
            });
        });

        let trackingLookupTimeout = null;
        let trackingLookupRequestId = 0;

        function setTrackingIdDisplay(text, tone = 'muted') {
            const display = document.getElementById('trackingIdDisplay');
            if (!display) {
                return;
            }

            display.textContent = text;
            display.classList.remove('text-slate-500', 'text-green-600', 'text-red-600');

            if (tone === 'success') {
                display.classList.add('text-green-600');
            } else if (tone === 'error') {
                display.classList.add('text-red-600');
            } else {
                display.classList.add('text-slate-500');
            }
        }

        function setTrackingStatus(message = '') {
            const statusEl = document.getElementById('trackingIdStatus');
            if (!statusEl) {
                return;
            }
            statusEl.textContent = message;
        }

        function resetTrackingIdState() {
            const trackingInput = document.getElementById('trackingId');
            if (trackingInput) {
                trackingInput.value = '';
            }
            setTrackingIdDisplay('Awaiting grouping match');
            setTrackingStatus('');
        }

        function scheduleGroupingLookup(previewValue) {
            if (trackingLookupTimeout) {
                clearTimeout(trackingLookupTimeout);
            }

            if (!previewValue || previewValue === '-') {
                resetTrackingIdState();
                return;
            }

            trackingLookupTimeout = setTimeout(() => {
                lookupGroupingTrackingId(previewValue);
            }, 400);
        }

        async function lookupGroupingTrackingId(previewValue) {
            trackingLookupTimeout = null;
            trackingLookupRequestId += 1;
            const requestId = trackingLookupRequestId;

            const trackingInput = document.getElementById('trackingId');
            setTrackingIdDisplay('Searching...');
            setTrackingStatus('Checking grouping records...');

            try {
                const response = await fetch(`/api/grouping/awaiting/${encodeURIComponent(previewValue)}`);
                const payload = await response.json().catch(() => ({}));

                if (requestId !== trackingLookupRequestId) {
                    return;
                }

                if (!response.ok || !payload.success) {
                    throw new Error(payload?.message || 'Grouping record not found');
                }

                const record = payload.data || payload;
                const trackingId = (record?.tracking_id || '').trim();

                if (trackingInput) {
                    trackingInput.value = trackingId;
                }

                if (trackingId) {
                    setTrackingIdDisplay(trackingId, 'success');
                    setTrackingStatus('Linked to grouping record.');
                } else {
                    setTrackingIdDisplay('Grouping record has no tracking ID');
                    setTrackingStatus('Assign tracking ID manually before saving.');
                }
            } catch (error) {
                if (requestId !== trackingLookupRequestId) {
                    return;
                }

                if (trackingInput) {
                    trackingInput.value = '';
                }

                setTrackingIdDisplay('Grouping tracking ID not found', 'error');
                setTrackingStatus('A new tracking ID will be generated on save.');
                console.error('Error fetching grouping tracking ID:', error);
            }
        }

        function openCaptureModal() {
            document.getElementById('captureModal').classList.remove('hidden');
            
            // Reset form
            document.getElementById('captureForm').reset();
            document.getElementById('year').value = new Date().getFullYear();

            if (trackingLookupTimeout) {
                clearTimeout(trackingLookupTimeout);
                trackingLookupTimeout = null;
            }
            trackingLookupRequestId += 1;

            resetTrackingIdState();
            
            // Set default file type to normal in the select dropdown
            document.getElementById('fileOption').value = 'normal';
            updateCaptureForm('normal');

            // Load existing file numbers for extension dropdown
            loadExistingFileNumbers();
            
            updateCapturePreview();
        }

        function closeCaptureModal() {
            document.getElementById('captureModal').classList.add('hidden');
            if (trackingLookupTimeout) {
                clearTimeout(trackingLookupTimeout);
                trackingLookupTimeout = null;
            }
            trackingLookupRequestId += 1;
        }

        function updateCaptureForm(type) {
            const prefixSection = document.getElementById('prefixSection');
            const middlePrefixSection = document.getElementById('middlePrefixSection');
            const yearSection = document.getElementById('yearSection');
            const extensionFileSection = document.getElementById('extensionFileSection');
            const temporaryFileSection = document.getElementById('temporaryFileSection');
            const serialNoField = document.getElementById('serialNo');
            
            // Hide all sections first
            if (prefixSection) prefixSection.classList.add('hidden');
            if (middlePrefixSection) middlePrefixSection.classList.add('hidden');
            if (yearSection) yearSection.classList.add('hidden');
            if (extensionFileSection) extensionFileSection.classList.add('hidden');
            if (temporaryFileSection) temporaryFileSection.classList.add('hidden');
            
            // Reset serial number field properties and remove all restrictive attributes
            serialNoField.type = 'text';
            serialNoField.removeAttribute('min');
            serialNoField.removeAttribute('max');
            serialNoField.removeAttribute('step');
            serialNoField.removeAttribute('maxlength');
            serialNoField.removeAttribute('pattern');
            serialNoField.removeAttribute('inputmode');
            serialNoField.disabled = false;
            
            if (type === 'normal') {
                if (prefixSection) prefixSection.classList.remove('hidden');
                if (yearSection) yearSection.classList.remove('hidden');
                // For normal files, keep serial as number for auto-padding
                serialNoField.type = 'number';
                serialNoField.setAttribute('min', '1');
                serialNoField.setAttribute('max', '9999');
                serialNoField.placeholder = 'Enter serial number (1-9999)';
            } else if (type === 'temporary') {
                if (temporaryFileSection) temporaryFileSection.classList.remove('hidden');
                if (yearSection) yearSection.classList.remove('hidden');
                serialNoField.placeholder = 'Not required for temporary files';
                serialNoField.value = '';
                serialNoField.disabled = true;
                loadExistingFileNumbers();
                setupTemporaryToggle();
            } else if (type === 'extension') {
                if (extensionFileSection) extensionFileSection.classList.remove('hidden');
                if (yearSection) yearSection.classList.remove('hidden');
                serialNoField.placeholder = 'Not required for extensions';
                serialNoField.value = '';
                serialNoField.disabled = true;
                loadExistingFileNumbers();
                setupExtensionToggle();
            } else if (type === 'miscellaneous') {
                if (middlePrefixSection) middlePrefixSection.classList.remove('hidden');
                if (yearSection) yearSection.classList.remove('hidden');
                // Make serial number plain text and editable
                serialNoField.type = 'text';
                serialNoField.setAttribute('inputmode', 'text');
                serialNoField.placeholder = 'Enter custom serial (e.g., 1, ABC123)';
            } else if (type === 'old_mls' || type === 'sltr' || type === 'sit') {
                if (type === 'sit' && yearSection) {
                    yearSection.classList.remove('hidden');
                }
                // Make serial number plain text and editable for these types
                serialNoField.type = 'text';
                serialNoField.setAttribute('inputmode', 'text');
                
                if (type === 'sit') {
                    serialNoField.placeholder = 'Enter SIT serial (e.g., 1, 2024-1)';
                } else if (type === 'old_mls') {
                    serialNoField.placeholder = 'Enter Old MLS number (e.g., 5467, 34874857488758)';
                } else if (type === 'sltr') {
                    serialNoField.placeholder = 'Enter SLTR serial (e.g., 1, 2024-1)';
                }
            }
            
            updateCapturePreview();
        }

        function setupExtensionToggle() {
            const dropdownRadio = document.getElementById('extensionDropdown');
            const manualRadio = document.getElementById('extensionManual');
            const dropdownSection = document.getElementById('extensionDropdownSection');
            const manualInput = document.getElementById('extensionManualInput');
            
            // Set initial state - dropdown selected by default
            if (document.getElementById('extensionManualFile')) {
                document.getElementById('extensionManualFile').disabled = true;
            }
            if (document.getElementById('existingFileNo')) {
                document.getElementById('existingFileNo').disabled = false;
            }
            
            if (dropdownRadio && manualRadio) {
                dropdownRadio.addEventListener('change', function() {
                    if (this.checked) {
                        dropdownSection.classList.remove('hidden');
                        manualInput.classList.add('hidden');
                        document.getElementById('extensionManualFile').value = '';
                        document.getElementById('extensionManualFile').disabled = true;
                        document.getElementById('existingFileNo').disabled = false;
                        updateCapturePreview();
                    }
                });
                
                manualRadio.addEventListener('change', function() {
                    if (this.checked) {
                        dropdownSection.classList.add('hidden');
                        manualInput.classList.remove('hidden');
                        document.getElementById('existingFileNo').value = '';
                        document.getElementById('existingFileNo').disabled = true;
                        document.getElementById('extensionManualFile').disabled = false;
                        updateCapturePreview();
                    }
                });
            }
        }
        
        function setupTemporaryToggle() {
            const dropdownRadio = document.getElementById('temporaryDropdown');
            const manualRadio = document.getElementById('temporaryManual');
            const dropdownSection = document.getElementById('temporaryDropdownSection');
            const manualInput = document.getElementById('temporaryManualInput');
            
            // Set initial state - dropdown selected by default
            if (document.getElementById('temporaryManualFile')) {
                document.getElementById('temporaryManualFile').disabled = true;
            }
            if (document.getElementById('temporaryFileNo')) {
                document.getElementById('temporaryFileNo').disabled = false;
            }
            
            if (dropdownRadio && manualRadio) {
                dropdownRadio.addEventListener('change', function() {
                    if (this.checked) {
                        dropdownSection.classList.remove('hidden');
                        manualInput.classList.add('hidden');
                        document.getElementById('temporaryManualFile').value = '';
                        document.getElementById('temporaryManualFile').disabled = true;
                        document.getElementById('temporaryFileNo').disabled = false;
                        updateCapturePreview();
                    }
                });
                
                manualRadio.addEventListener('change', function() {
                    if (this.checked) {
                        dropdownSection.classList.add('hidden');
                        manualInput.classList.remove('hidden');
                        document.getElementById('temporaryFileNo').value = '';
                        document.getElementById('temporaryFileNo').disabled = true;
                        document.getElementById('temporaryManualFile').disabled = false;
                        updateCapturePreview();
                    }
                });
            }
        }

        function updateCapturePreview() {
            const fileOption = document.getElementById('fileOption').value;
            const prefix = document.getElementById('prefix').value;
            const middlePrefix = document.getElementById('middlePrefix').value;
            const year = document.getElementById('year').value;
            const serialNo = document.getElementById('serialNo').value;
            const preview = document.getElementById('capturePreview');
            
            let previewText = '-';
            let extensionFileValue = '';
            let temporaryFileValue = '';
            
            // Get extension file value (dropdown or manual)
            if (fileOption === 'extension') {
                const extensionDropdownRadio = document.getElementById('extensionDropdown');
                if (extensionDropdownRadio && extensionDropdownRadio.checked) {
                    extensionFileValue = (document.getElementById('existingFileNo').value || '').trim();
                } else {
                    extensionFileValue = (document.getElementById('extensionManualFile').value || '').trim();
                }
            }
            
            // Get temporary file value (dropdown or manual)
            if (fileOption === 'temporary') {
                const temporaryDropdownRadio = document.getElementById('temporaryDropdown');
                if (temporaryDropdownRadio && temporaryDropdownRadio.checked) {
                    temporaryFileValue = (document.getElementById('temporaryFileNo').value || '').trim();
                } else {
                    temporaryFileValue = (document.getElementById('temporaryManualFile').value || '').trim();
                }
            }
            
            if (fileOption === 'extension' && extensionFileValue) {
                previewText = extensionFileValue + ' AND EXTENSION';
            } else if (fileOption === 'temporary' && temporaryFileValue) {
                previewText = temporaryFileValue + '(T)';
            } else if (fileOption === 'miscellaneous' && middlePrefix && serialNo && year) {
                previewText = `MISC-${middlePrefix}-${year}-${serialNo}`;
            } else if (fileOption === 'old_mls' && serialNo) {
                previewText = `KN ${serialNo}`;
            } else if (fileOption === 'sltr' && serialNo) {
                previewText = `SLTR-${serialNo}`;
            } else if (fileOption === 'sit' && serialNo && year) {
                previewText = `SIT-${year}-${serialNo}`;
            } else if (fileOption === 'normal' && prefix && year && serialNo) {
                const unpaddedSerial = serialNo.toString().trim();
                previewText = `${prefix}-${year}-${unpaddedSerial}`;
            }
            
            preview.textContent = previewText;
            
            if (previewText !== '-') {
                preview.classList.remove('text-gray-400');
                preview.classList.add('text-green-600');
            } else {
                preview.classList.remove('text-green-600');
                preview.classList.add('text-gray-400');
            }

            let groupingLookupTarget = null;

            if (fileOption === 'normal' && previewText !== '-') {
                groupingLookupTarget = previewText;
            } else if (fileOption === 'extension' && extensionFileValue) {
                groupingLookupTarget = extensionFileValue;
            } else if (fileOption === 'temporary' && temporaryFileValue) {
                groupingLookupTarget = temporaryFileValue;
            }

            if (groupingLookupTarget) {
                scheduleGroupingLookup(groupingLookupTarget);
            } else if (fileOption === 'normal') {
                resetTrackingIdState();
            } else {
                resetTrackingIdState();
                setTrackingIdDisplay('Will be generated on save');
            }
        }

        function submitCaptureForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Capturing existing file number...');
            
            const formData = new FormData(document.getElementById('captureForm'));
            
            fetch('{{ route("existing-file-numbers.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    closeCaptureModal();
                    table.ajax.reload();
                    updateTotalCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while capturing the file number',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function openMigrationModal() {
            document.getElementById('migrationModal').classList.remove('hidden');
        }

        function closeMigrationModal() {
            document.getElementById('migrationModal').classList.add('hidden');
        }

        function submitMigrationForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Migrating data... Please wait.');
            
            const formData = new FormData(document.getElementById('migrationForm'));
            
            fetch('{{ route("file-numbers.migrate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    closeMigrationModal();
                    table.ajax.reload();
                    updateTotalCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred during migration',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while migrating data',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function editRecord(id) {
            // Show loading while fetching record details
            showGlobalLoading('Loading record details...');
            
            fetch(`{{ route("file-numbers.show", ":id") }}`.replace(':id', id))
                .then(response => response.json())
                .then(data => {
                    hideGlobalLoading();
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editMlsfNo').value = data.mlsfNo || data.kangisFileNo || 'N/A';
                    document.getElementById('editKangisFileNo').value = data.kangisFileNo || '';
                    document.getElementById('editNewKangisFileNo').value = data.NewKANGISFileNo || '';
                    document.getElementById('editFileName').value = data.FileName || '';
                    document.getElementById('editModal').classList.remove('hidden');
                })
                .catch(error => {
                    hideGlobalLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load record details',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function submitEditForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Updating record...');
            
            const id = document.getElementById('editId').value;
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch(`{{ route("file-numbers.update", ":id") }}`.replace(':id', id), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    closeEditModal();
                    table.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating the record',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function deleteRecord(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`{{ route("file-numbers.destroy", ":id") }}`.replace(':id', id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const data = result.value;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            confirmButtonColor: '#10b981'
                        });
                        table.ajax.reload();
                        updateTotalCount();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'An error occurred',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                }
            });
        }

        function loadExistingFileNumbers() {
            fetch('{{ route("file-numbers.existing") }}')
                .then(response => response.json())
                .then(data => {
                    // Populate extension dropdown
                    const extensionSelect = document.getElementById('existingFileNo');
                    extensionSelect.innerHTML = '<option value="">Select existing file number...</option>';
                    
                    // Populate temporary dropdown  
                    const temporarySelect = document.getElementById('temporaryFileNo');
                    temporarySelect.innerHTML = '<option value="">Select existing file number...</option>';
                    
                    data.forEach(fileNo => {
                        // Create option for extension dropdown
                        const extensionOption = document.createElement('option');
                        extensionOption.value = fileNo.mlsfNo;
                        extensionOption.textContent = fileNo.mlsfNo;
                        extensionSelect.appendChild(extensionOption);
                        
                        // Create option for temporary dropdown
                        const temporaryOption = document.createElement('option');
                        temporaryOption.value = fileNo.mlsfNo;
                        temporaryOption.textContent = fileNo.mlsfNo;
                        temporarySelect.appendChild(temporaryOption);
                    });
                })
                .catch(error => {
                    console.error('Error loading existing file numbers:', error);
                });
        }

        function updateTotalCount() {
            fetch('{{ route("file-numbers.count") }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalCount').textContent = data.count;
                })
                .catch(error => {
                    console.error('Error updating count:', error);
                });
        }

        // Add event listeners for form inputs
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('serialNo').addEventListener('input', updateCapturePreview);
            document.getElementById('year').addEventListener('input', updateCapturePreview);
            document.getElementById('prefix').addEventListener('change', updateCapturePreview);
            document.getElementById('middlePrefix').addEventListener('input', updateCapturePreview);
            document.getElementById('existingFileNo').addEventListener('change', updateCapturePreview);
            
            // Add event listeners for new fields
            document.getElementById('extensionManualFile').addEventListener('input', updateCapturePreview);
            document.getElementById('temporaryFileNo').addEventListener('change', updateCapturePreview);
            document.getElementById('temporaryManualFile').addEventListener('input', updateCapturePreview);
            
            // Add event listener for file option select dropdown
            document.getElementById('fileOption').addEventListener('change', function() {
                updateCaptureForm(this.value);
            });
        });
    </script>
@endsection