@extends('layouts.app')
@section('page-title')
    {{ __('MLS File Number Generator') }}
@endsection

@section('content') 
    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <!-- Header -->
        @include('admin.header', [
            'PageTitle' => 'MLS File Number Generator',
            'PageDescription' => 'Generate and manage MLS file numbers'
        ])
        
        <!-- Dashboard Content -->
        <div class="p-6">
            <div class="container mx-auto py-6 space-y-6">
                
                <!-- Action Buttons -->
                <div class="flex justify-between items-center mb-6">
                    <div class="flex space-x-4">
                        <button 
                            onclick="openGenerateModal()"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Generate New  FileNO</span>
                        </button>
                      
                         <!-- <button 
                            onclick="openMigrationModal()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="upload" class="w-4 h-4"></i>
                            <span>Migrate Data</span>
                        </button>   -->
                        <!-- <button 
                            onclick="testDatabaseConnection()"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="database" class="w-4 h-4"></i>
                            <span>Test Database</span>
                        </button> -->
                        <!-- <button 
                            onclick="debugTableData()"
                            class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center space-x-2 transition-colors">
                            <i data-lucide="bug" class="w-4 h-4"></i>
                            <span>Debug Data</span>
                        </button> -->
                    </div>
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 shadow-sm">
    <div class="flex items-center space-x-2">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
        </svg>
        <div class="text-sm text-gray-700 font-medium">
            Total Generated: 
            <span id="totalCount" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-sm font-bold bg-blue-100 text-blue-800 ml-1">
                {{ $totalCount ?? 0 }}
            </span>
        </div>
    </div>
</div>
                </div>

                <!-- DataTable -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table id="mlsfTable" class="w-full table-auto">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MLS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">KANGIS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">New KANGIS File No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plot No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TP No</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking ID</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission By</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
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

    <!-- Generate Modal with Alpine.js -->
    <div id="generateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-5 mx-auto p-6 border w-[800px] max-w-4xl shadow-xl rounded-lg bg-white" 
             x-data="fileNumberGenerator()" x-init="init()">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-200">
                    <div>
                        <h3 id="modalTitle" class="text-xl font-semibold text-gray-900">Generate New Application</h3>
                        <p class="text-sm text-gray-500 mt-1">Fill in the details to generate a new MLS file number</p>
                    </div>
                    <button onclick="closeGenerateModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form id="generateForm" onsubmit="submitForm(event)" class="space-y-6">
                    @csrf
                    
                    <!-- Hidden field for the generated file number that backend expects -->
                    <input type="hidden" name="generated_file_number" x-model="preview" id="generatedFileNumber">
                    <input type="hidden" name="tracking_id" id="trackingIdInput" value="">
                    
                    <!-- Application Type Selection -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Application Type</label>
                                          <br>
                 <div class="bg-gray-100 px-4 py-2 rounded-md mb-6 flex justify-between items-center max-w-xs">
    <div class="text-gray-700 font-mono text-sm font-bold whitespace-nowrap">
        <i data-lucide="file-search" class="inline h-4 w-4 mr-1"></i>
        Tracking ID: <span id="trackingIdDisplay" class="text-red-600 font-bold">--</span>
    </div>
  </div>
                        <div class="flex space-x-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="application_type" value="new" class="mr-3 text-blue-600" 
                                       x-model="applicationType" @change="updateApplicationType()" checked>
                                <span class="text-sm font-medium">Direct Allocation</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="radio" name="application_type" value="conversion" class="mr-3 text-blue-600" 
                                       x-model="applicationType" @change="updateApplicationType()">
                                <span class="text-sm font-medium">Conversion</span>
                            </label>

                    
                        </div>

                
</div>

                    </div>

                    <!-- Main Form Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <!-- File Name -->


                              <!-- File Options -->

                            <!-- Middle Prefix Section -->
                            <div id="middlePrefixSection" class="hidden">
                                <label for="middlePrefix" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i>
                                    Middle Prefix
                                </label>
                                <input type="text" id="middlePrefix" name="middle_prefix" x-model="middlePrefix"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    placeholder="e.g., KN" value="KN">
                            </div>

                            <!-- This section has been moved to the right column with toggle functionality -->

                            <!-- File Options -->
                            <div>
                                <label for="fileOption" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="settings" class="w-4 h-4 inline mr-1"></i>
                                    File Options
                                </label>
                                <select id="fileOption" name="file_option" x-model="fileOption" @change="updateFileOption()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">Select File Option</option>
                                    <option value="normal">Normal File</option>
                                    <option value="temporary">Temporary File</option>
                                    <option value="extension">Extension</option>
                                    <option value="miscellaneous">Miscellaneous</option>
                                    <option value="old_mls">Old MLS</option>
                                    <option value="sltr">SLTR</option>
                                    <option value="sit" x-show="applicationType === 'new'">SIT</option>
                                </select>
                            </div>

                            <div>
                                <label for="fileName" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                                    File Name
                                </label>
                                <input type="text" id="fileName" name="file_name" x-model="fileName"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter file name">
                            </div>
                            
                            <!-- Land Use -->
                            <div>
                                <label for="landUse" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="map" class="w-4 h-4 inline mr-1"></i>
                                    Land Use
                                </label>
                                <select id="landUse" name="land_use" x-model="landUse" @change="updatePreview()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                  
                                    <!-- New Application Options -->
                                    <optgroup label="New Application" x-show="applicationType === 'new'">
                                          <option value="">Select Land Use</option>
                                        <option value="RES">RES - Residential</option>
                                        <option value="COM">COM - Commercial</option>
                                        <option value="IND">IND - Industrial</option>
                                        <option value="AG">AG - Agricultural</option>
                                    </optgroup> 
                                    <!-- Conversion Options -->
                                    <optgroup label="Conversion" x-show="applicationType === 'conversion'">
                                        <option value="CON-RES">CON-RES - Conversion to Residential</option>
                                        <option value="CON-COM">CON-COM - Conversion to Commercial</option>
                                        <option value="CON-IND">CON-IND - Conversion to Industrial</option>
                                        <option value="CON-AG">CON-AG - Conversion to Agricultural</option>
                                        <option value="CON-RES-RC">CON-RES-RC - Conversion to Residential</option>
                                        <option value="CON-COM-RC">CON-COM-RC - Conversion to Commercial</option>
                                        <option value="CON-AG-RC">CON-AG-RC - Conversion to Agricultural</option>

                                    </optgroup>
                                    <!-- RC Options -->
                                    <optgroup label="RC Options" x-show="applicationType === 'new'">
                                      
                                        <option value="RES-RC">RES-RC</option>
                                        <option value="COM-RC">COM-RC</option>
                                        <option value="AG-RC">AG-RC</option>
                                        <option value="IND-RC">IND-RC</option>
                                        
                                   
                                    </optgroup>
                                </select>
                            </div>

                            <!-- Plot Number -->
                            <div>
                                <label for="plotNo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="map-pin" class="w-4 h-4 inline mr-1"></i>
                                    Plot Number
                                </label>
                                <input type="text" id="plotNo" name="plot_no" x-model="plotNo"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter plot number">
                            </div>

                            <!-- TP Number -->
                            <div>
                                <label for="tpNo" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                                    TP Number
                                </label>
                                <input type="text" id="tpNo" name="tp_no" x-model="tpNo"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter TP number">
                            </div>

                            <!-- Location -->
                            <div>
                                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="map" class="w-4 h-4 inline mr-1"></i>
                                    Location
                                </label>
                                <input type="text" id="location" name="location" x-model="location"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="Enter location details">
                            </div>

                     
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                         

                            <!-- Extension File Selection -->
                            <div x-show="fileOption === 'extension'" x-data="{ useManualInput: 'false' }" x-init="useManualInput = 'false'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="link" class="w-4 h-4 inline mr-1"></i>
                                    Select Existing MLS File Number
                                </label>
                                
                                <!-- Toggle between dropdown and manual input -->
                                <div class="flex items-center mb-3 space-x-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="extension_input_type" x-model="useManualInput" value="false" class="mr-2 text-blue-600">
                                        <span class="text-sm">Select from existing</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="extension_input_type" x-model="useManualInput" value="true" class="mr-2 text-blue-600">
                                        <span class="text-sm">Enter manually</span>
                                    </label>
                                </div>

                                <!-- Dropdown for existing files -->
                                <div x-show="useManualInput === 'false'">
                                    <select id="extensionFileNo" name="existing_file_no" x-model="existingFileNo" @change="updatePreview()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select existing file number...</option>
                                        <!-- Options will be populated via AJAX -->
                                    </select>
                                </div>

                                <!-- Manual input field -->
                                <div x-show="useManualInput === 'true'">
                                    <input type="text" name="existing_file_no_manual" x-model="existingFileNo" @input="updatePreview()" 
                                           placeholder="Enter file number manually (e.g., RES-2024-0567)"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Enter the exact file number you want to extend</p>
                                </div>
                            </div>

                            <!-- Temporary File Selection -->
                            <div x-show="fileOption === 'temporary'" x-data="{ useManualInput: 'false' }" x-init="useManualInput = 'false'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="clock" class="w-4 h-4 inline mr-1"></i>
                                    Select Existing File for Temporary Version
                                </label>
                                
                                <!-- Toggle between dropdown and manual input -->
                                <div class="flex items-center mb-3 space-x-4">
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="temporary_input_type" x-model="useManualInput" value="false" class="mr-2 text-blue-600">
                                        <span class="text-sm">Select from existing</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="radio" name="temporary_input_type" x-model="useManualInput" value="true" class="mr-2 text-blue-600">
                                        <span class="text-sm">Enter manually</span>
                                    </label>
                                </div>

                                <!-- Dropdown for existing files -->
                                <div x-show="useManualInput === 'false'">
                                    <select id="temporaryFileNo" name="existing_file_no" x-model="existingFileNo" @change="updatePreview()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="">Select existing file number...</option>
                                        <!-- Options will be populated via AJAX -->
                                    </select>
                                </div>

                                <!-- Manual input field -->
                                <div x-show="useManualInput === 'true'">
                                    <input type="text" name="existing_file_no_manual" x-model="existingFileNo" @input="updatePreview()" 
                                           placeholder="Enter file number manually (e.g., RES-2024-0567)"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <p class="text-xs text-gray-500 mt-1">Enter the exact file number for temporary version</p>
                                </div>
                            </div>

                            <!-- Middle Prefix (for miscellaneous files) -->
                            <div x-show="fileOption === 'miscellaneous'">
                                <label for="middlePrefix" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i>
                                    Middle Prefix
                                </label>
                                <input type="text" id="middlePrefix" name="middle_prefix" x-model="middlePrefix" @input="updatePreview()"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="e.g., KN" value="KN">
                            </div>

                            <!-- Year and Serial Number Grid -->
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Year -->
                                <div x-show="showYearSection" style="display:none;">
                                    <label for="year" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                                        Year
                                    </label>
                                    <input type="number" id="year" name="year" x-model="year" @input="updatePreview()"
                                           :class="yearFieldClass"
                                           min="2020" max="2050" :readonly="!isYearEditable">
                                    <p class="text-xs text-gray-500 mt-1" x-text="yearDescription"></p>
                                </div>

                                <!-- Serial Number -->
                                <div>
                                    <label for="serialNo" class="block text-sm font-medium text-gray-700 mb-2">
                                        <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                                        Serial No.
                                    </label>
                                    <input :type="serialFieldType" id="serialNo" name="serial_no" 
                                           x-model="serialNo" 
                                           x-on:input="serialFieldType === 'text' ? updatePreviewOnly() : updatePreview()"
                                           :class="serialFieldClass"
                                           :placeholder="serialPlaceholder" 
                                           :readonly="isSerialReadonly" 
                                           :disabled="isSerialDisabled"
                                           x-bind:min="serialFieldType === 'number' ? '1' : false"
                                           x-bind:max="serialFieldType === 'number' ? '9999' : false"
                                           :inputmode="serialFieldType === 'text' ? 'text' : 'numeric'"
                                           autocomplete="off"
                                           required>
                                    <p class="text-xs mt-1" :class="serialDescriptionClass" x-text="serialDescription"></p>
                                </div>
                            </div>

                            <!-- Full File Number Preview -->
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i>
                                    Generated File Number Preview
                                </label>
                                <div id="mlsfPreview" class="w-full px-4 py-3 bg-white border border-blue-300 rounded-md text-lg font-mono text-center font-bold shadow-sm"
                                     :class="previewClass" x-text="preview">
                                </div>

                                
                            </div>


                            <!-- Commissioned By -->
                            <div>
                                <label for="commissionedBy" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="user-check" class="w-4 h-4 inline mr-1"></i>
                                    Commissioned By     
                                </label>
                                <input type="text" id="commissionedBy" name="commissioned_by"  
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100"
                                       placeholder="Auto-filled" disabled value="{{ Auth::user()->name }}">
                            </div>

                            <!-- Commission Date -->
                            <div>
                                <label for="commissionDate" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i data-lucide="calendar-check" class="w-4 h-4 inline mr-1"></i>
                                    Commission Date 
                                </label>
                                <input type="text" id="commissionDate" name="commission_date"                 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-gray-100"
                                       placeholder="Auto-filled" disabled value="{{ date('Y-m-d') }}">
                            </div>
                           
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <button type="button" onclick="showOverrideModal()" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700 transition-colors flex items-center space-x-2">
                            <i data-lucide="edit" class="w-4 h-4"></i>
                            <span>Override</span>
                        </button>
                        <div class="flex space-x-3">
                            <button type="button" onclick="closeGenerateModal()" 
                                    class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center space-x-2">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Generate</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Override Modal -->
    <div id="overrideModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Override File Number</h3>
                    <button onclick="closeOverrideModal()" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Override Form -->
                <form id="overrideForm" onsubmit="submitOverrideForm(event)">
                    @csrf
                    
                    <!-- Manual Year -->
                    <div class="mb-4">
                        <label for="overrideYear" class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <input type="number" id="overrideYear" name="override_year" 
                               value="{{ date('Y') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               >
                    </div>

                    <!-- Manual Serial Number -->
                    <div class="mb-4">
                        <label for="overrideSerialNo" class="block text-sm font-medium text-gray-700 mb-2">Serial Number</label>
                        <input type="number" id="overrideSerialNo" name="override_serial_no" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               min="1" max="9999">
                    </div>

                    <!-- Extension Option -->
                    <div class="mb-4" style="display:none;">
                        <label class="flex items-center">
                            <input type="checkbox" id="overrideExtension" name="override_extension" class="mr-2">
                            <span>File Extension</span>
                        </label>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeOverrideModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            Apply Override
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
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit File Name</h3>
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

                    <!-- File Name (Editable) -->
                    <div class="mb-4">
                        <label for="editFileName" class="block text-sm font-medium text-gray-700 mb-2">
                            <i data-lucide="file-text" class="w-4 h-4 inline mr-1"></i>
                            File Name
                        </label>
                        <input type="text" id="editFileName" name="file_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Enter file name" required>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Update File Name
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- File Commissioning Sheet Modal -->
    <div id="commissioningSheetModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">File Number Commissioning Sheet</h3>
                    <button onclick="closeCommissioningSheetModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Form Container -->
                <form id="commissioningSheetForm" onsubmit="submitCommissioningSheet(event)">
                    @csrf
                    
                    <!-- Ministry Header -->
                    <div class="text-center mb-6 border-b pb-4">
                        <h2 class="text-lg font-bold text-gray-800">Ministry of Land & Physical Planning</h2>
                        <h3 class="text-base font-medium text-gray-700">Dept. of Lands</h3>
                        <h4 class="text-base font-medium text-gray-600 mt-2">File Commissioning Sheet</h4>
                    </div>

                    <!-- Data Load Status -->
                    <div id="dataLoadStatus" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-md hidden">
                        <div class="flex items-center">
                            <i data-lucide="info" class="w-4 h-4 text-blue-600 mr-2"></i>
                            <span class="text-sm text-blue-800">Data loaded from selected file number record</span>
                        </div>
                    </div>

                    <!-- Form Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- File Number -->
                        <div class="md:col-span-1">
                            <label for="cs_file_number" class="block text-sm font-medium text-gray-700 mb-2">
                                File No:
                            </label>
                            <input type="text" id="cs_file_number" name="file_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter file number" required>
                        </div>

                        <!-- File Name -->
                        <div class="md:col-span-1">
                            <label for="cs_file_name" class="block text-sm font-medium text-gray-700 mb-2">
                                File Name:
                            </label>
                            <input type="text" id="cs_file_name" name="file_name" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter file name"
                                   oninput="document.getElementById('cs_name_allottee').value = this.value">
                        </div>

                        <!-- Allottee -->
                        <div class="md:col-span-2">
                            <label for="cs_name_allottee" class="block text-sm font-medium text-gray-700 mb-2">
                                Allottee:
                            </label>
                            <input type="text" id="cs_name_allottee" name="name_or_allottee" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50"
                                   placeholder="Auto-filled from file name" readonly>
                        </div>

                        <!-- Plot Number -->
                        <div class="md:col-span-1">
                            <label for="cs_plot_number" class="block text-sm font-medium text-gray-700 mb-2">
                                Plot No:
                            </label>
                            <input type="text" id="cs_plot_number" name="plot_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter plot number">
                        </div>

                        <!-- TP Number -->
                        <div class="md:col-span-1">
                            <label for="cs_tp_number" class="block text-sm font-medium text-gray-700 mb-2">
                                TP No:
                            </label>
                            <input type="text" id="cs_tp_number" name="tp_number" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter TP number">
                        </div>

                        <!-- Location -->
                        <div class="md:col-span-2">
                            <label for="cs_location" class="block text-sm font-medium text-gray-700 mb-2">
                                Location:
                            </label>
                            <textarea id="cs_location" name="location" rows="2"
                                     class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                     placeholder="Enter location"></textarea>
                        </div>

                        <!-- Date Created -->
                        <div class="md:col-span-1">
                            <label for="cs_date_created" class="block text-sm font-medium text-gray-700 mb-2">
                                Date Created:
                            </label>
                            <input type="date" id="cs_date_created" name="date_created" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   value="{{ date('Y-m-d') }}">
                        </div>

                        <!-- Created By -->
                        <div class="md:col-span-1">
                            <label for="cs_created_by" class="block text-sm font-medium text-gray-700 mb-2">
                                Created by:
                            </label>
                            <input type="text" id="cs_created_by" name="created_by" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="Enter creator name"
                                   value="{{ Auth::user()->name ?? '' }}">
                        </div>
                    </div>

                    <!-- Signatures Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 border-t pt-6">
                        <div class="text-center">
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                Created by Signature
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 h-24 flex items-center justify-center">
                                <span class="text-gray-500 text-sm">Signature area</span>
                            </div>
                        </div>
                        <div class="text-center">
                            <label class="block text-sm font-medium text-gray-700 mb-4">
                                Approved by Signature
                            </label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 h-24 flex items-center justify-center">
                                <span class="text-gray-500 text-sm">Signature area</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 border-t pt-6">
                        <button type="button" onclick="closeCommissioningSheetModal()" 
                                class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            <i data-lucide="x" class="w-4 h-4 mr-2"></i>
                            Cancel
                        </button>
                        <button type="button" onclick="generateAndPrintCommissioningSheet()" 
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                            <i data-lucide="printer" class="w-4 h-4 mr-2"></i>
                            Generate & Print
                        </button>
                        <!-- <button type="submit" 
                                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                            Save Draft
                        </button> -->
                    </div>
                </form>
            </div>
        </div>
    </div>
@include('generate_fileno.mls_js')
@endsection

