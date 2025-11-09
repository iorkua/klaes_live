{{-- Step 1: Basic Information --}}
<div class="form-section active" id="step1" style="display: block !important; visibility: visible !important; opacity: 1 !important; height: auto !important;">
  <div class="p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-center text-gray-800">MINISTRY OF LAND AND PHYSICAL PLANNING</h2>
      <button id="closeModal" class="text-gray-500 hover:text-gray-700">
        <i data-lucide="x" class="w-5 h-5"></i>
      </button>
    </div>

    <div class="mb-6">
      <div class="flex items-center mb-2">
        <i data-lucide="file-text" class="w-5 h-5 mr-2 text-green-600"></i>
        <h3 class="text-lg font-bold">Application for Sectional Titling - Main Application</h3>
        <div class="ml-auto flex items-center">
          <span class="text-gray-600 mr-2">Land Use:</span>
          <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
            @if (request()->query('landuse') === 'Commercial')
              Commercial
            @elseif (request()->query('landuse') === 'Residential')
              Residential
            @elseif (request()->query('landuse') === 'Industrial')
              Industrial
            @else
              Mixed Use
            @endif
          </span>
        </div>
      </div>
      <p class="text-gray-600">Complete the form below to submit a new primary application for sectional titling</p>
    </div>

    {{-- Step Navigation --}}
    <div class="flex items-center mb-8">
      <div class="flex items-center mr-4">
        <div class="step-circle active cursor-pointer" onclick="goToStep(1)">1</div>
      </div>
      <div class="flex items-center mr-4">
        <div class="step-circle inactive cursor-pointer" onclick="goToStep(2)">2</div>
      </div>
      <div class="flex items-center mr-4">
        <div class="step-circle inactive cursor-pointer" onclick="goToStep(3)">3</div>
      </div>
      <div class="flex items-center mr-4">
        <div class="step-circle inactive cursor-pointer" onclick="goToStep(4)">4</div>
      </div>
      <div class="flex items-center">
        <div class="step-circle inactive cursor-pointer" onclick="goToStep(5)">5</div>
      </div>
      <div class="ml-4">Step 1 of 5</div>
    </div>

    <div class="mb-6">
      <div class="text-right text-sm text-gray-500">CODE: ST FORM - 1</div>
      <hr class="my-4">
      
      <div class="space-y-6">
        {{-- Applicant Type Selection Card --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm">
          <div class="flex items-center mb-4">
            <div class="bg-blue-500 p-2 rounded-lg mr-3">
              <i data-lucide="users" class="w-5 h-5 text-white"></i>
            </div>
            <div>
              <h3 class="text-lg font-semibold text-gray-900">Applicant Information</h3>
              <p class="text-sm text-gray-600">Select the type of applicant for this application</p>
            </div>
          </div>
          
          <div class="space-y-4">
            <label class="block text-sm font-medium text-gray-700 mb-3">
              Applicant Type <span class="text-red-500">*</span>
            </label>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              {{-- Individual Option --}}
              <label class="relative flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group">
                <input type="radio" name="applicantType" class="mr-3 text-blue-600 focus:ring-blue-500" value="individual" onchange="handleApplicantTypeChange(this)">
                <div class="flex-1">
                  <div class="flex items-center">
                    <i data-lucide="user" class="w-4 h-4 mr-2 text-gray-500 group-hover:text-blue-600"></i>
                    <span class="font-medium text-gray-900">Individual</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Single person ownership</p>
                </div>
              </label>
              
              {{-- Corporate Option --}}
              <label class="relative flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group">
                <input type="radio" name="applicantType" class="mr-3 text-blue-600 focus:ring-blue-500" value="corporate" onchange="handleApplicantTypeChange(this)">
                <div class="flex-1">
                  <div class="flex items-center">
                    <i data-lucide="building" class="w-4 h-4 mr-2 text-gray-500 group-hover:text-blue-600"></i>
                    <span class="font-medium text-gray-900">Corporate Body</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Company or organization</p>
                </div>
              </label>
              
              {{-- Multiple Owners Option --}}
              <label class="relative flex items-center p-4 bg-white rounded-lg border-2 border-gray-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-all group">
                <input type="radio" name="applicantType" class="mr-3 text-blue-600 focus:ring-blue-500" value="multiple" onchange="handleApplicantTypeChange(this)">
                <div class="flex-1">
                  <div class="flex items-center">
                    <i data-lucide="users" class="w-4 h-4 mr-2 text-gray-500 group-hover:text-blue-600"></i>
                    <span class="font-medium text-gray-900">Multiple Owners</span>
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Joint ownership</p>
                </div>
              </label>
            </div>
          </div>
        </div>

        {{-- Date Information Card --}}
        <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
          <div class="flex items-center mb-4">
            <div class="bg-green-500 p-2 rounded-lg mr-3">
              <i data-lucide="calendar" class="w-5 h-5 text-white"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Application Dates</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Application Date --}}
            <div class="relative">
              <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                <i data-lucide="calendar-check" class="w-4 h-4 mr-2 text-green-600"></i>
                Application Date
              </label>
              <div class="relative">
                <input type="date" 
                       class="w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all" 
                       value="{{ date('Y-m-d') }}" 
                       name="application_date">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <i data-lucide="calendar" class="w-4 h-4 text-gray-400"></i>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">Date when the application was submitted</p>
            </div>
            
            {{-- Date Captured --}}
            <div class="relative">
              <label class="flex items-center text-sm font-medium text-gray-700 mb-2">
                <i data-lucide="calendar-clock" class="w-4 h-4 mr-2 text-blue-600"></i>
                Date Captured
              </label>
              <div class="relative">
                <input type="date" 
                       class="w-full pl-10 pr-4 py-3 bg-blue-50 border border-blue-200 rounded-lg cursor-not-allowed" 
                       value="{{ date('Y-m-d') }}"  name="created_at" 
                       readonly
                       disabled>
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <i data-lucide="lock" class="w-4 h-4 text-blue-400"></i>
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-1">System capture date (auto-generated)</p>
            </div>
          </div>
          
          {{-- Info Alert --}}
          <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
              <i data-lucide="info" class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0"></i>
              <p class="text-xs text-blue-800">
                The application date can be backdated if needed. The capture date is automatically set by the system.
              </p>
            </div>
          </div>
        </div>
      </div>

      {{-- Hidden Land Use Input --}}
      <input type="hidden" name="land_use" value="{{ request()->query('landuse') === 'Commercial' ? 'Commercial' : (request()->query('landuse') === 'Residential' ? 'Residential' : (request()->query('landuse') === 'Industrial' ? 'Industrial' : 'Mixed Use')) }}">
      
      {{-- File Numbers Section --}}
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 mb-6 px-4">
        {{-- New Primary FileNo (NPFN) Card --}}
        <div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-blue-100 border border-blue-200 rounded-xl p-10 shadow-lg min-h-[450px] w-full max-w-none">
          <div class="flex items-center mb-4">
            <div class="bg-blue-500 p-3 rounded-full mr-4 shadow-md">
              <i data-lucide="file-text" class="w-6 h-6 text-white"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">New Primary FileNo</h3>
              <p class="text-sm text-gray-600">Auto-generated identifier</p>
            </div>
            <div class="bg-green-500 px-3 py-1 rounded-full shadow-sm">
              <span class="text-white text-xs font-medium">AUTO</span>
            </div>
          </div>
          
          <div class="bg-white rounded-lg border border-blue-100 p-6 shadow-inner">
            <label class="block text-sm font-medium text-gray-700 mb-3">Generated FileNo (NPFN)</label>
            <div class="relative">
              <input type="text" 
                     class="w-full px-4 py-4 bg-blue-50 border-2 border-blue-200 rounded-lg text-blue-900 font-mono text-xl font-bold cursor-not-allowed transition-all" 
                     value="{{ $npFileNo ?? 'ST-COM-'.date('Y').'-01' }}" 
                     readonly
                     title="New Primary FileNo - Auto Generated">
              <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                <i data-lucide="lock" class="w-6 h-6 text-blue-500"></i>
              </div>
            </div>
            
            {{-- Components Display --}}
            <div class="mt-6 space-y-3">
              <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                <span class="text-gray-600">Prefix:</span>
                <span class="font-semibold text-blue-700">ST</span>
              </div>
              <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                <span class="text-gray-600">Land Use:</span>
                <span class="font-semibold text-blue-700">
                  @php
                    $landUse = strtoupper(request()->query('landuse', 'COMMERCIAL'));
                  @endphp
                  @if ($landUse === 'COMMERCIAL') COM
                  @elseif ($landUse === 'INDUSTRIAL') IND
                  @elseif ($landUse === 'RESIDENTIAL') RES
                  @elseif ($landUse === 'MIXED') MIXED
                  @else COM @endif
                </span>
              </div>
              <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                <span class="text-gray-600">Year:</span>
                <span class="font-semibold text-blue-700">{{ $currentYear ?? date('Y') }}</span>
              </div>
              <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                <span class="text-gray-600">Serial:</span>
                <span class="font-semibold text-blue-700">{{ $serialNo ?? '1' }}</span>
              </div>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
              <div class="flex items-start">
                <i data-lucide="info" class="w-4 h-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-blue-800">
                  Primary identifier for this application and all unit applications.
                </p>
              </div>
            </div>
          </div>
        </div>

        {{-- File Number Information Card --}}
        <div class="bg-gradient-to-br from-green-50 via-emerald-50 to-green-100 border border-green-200 rounded-xl p-10 shadow-lg min-h-[450px]">
          <div class="flex items-center mb-4">
            <div class="bg-green-500 p-3 rounded-full mr-4 shadow-md">
              <i data-lucide="search" class="w-6 h-6 text-white"></i>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-semibold text-gray-900">File Number Information</h3>
              <p class="text-sm text-gray-600">Select existing file number</p>
            </div>
            <div class="bg-orange-500 px-3 py-1 rounded-full shadow-sm">
              <span class="text-white text-xs font-medium">SELECT</span>
            </div>
          </div>
          
          <div class="bg-white rounded-lg border border-green-100 p-6 shadow-inner">
            <div class="flex items-center justify-between mb-4">
              <label class="block text-sm font-medium text-gray-700">
                <i data-lucide="hash" class="w-4 h-4 inline mr-1"></i>
                Applied File Number
              </label>
              <button type="button" 
                      id="open-fileno-modal-btn" 
                      class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all shadow-sm"
                      onclick="openFileNumberModal()">
                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                Browse Files
              </button>
            </div>
            
            <div class="relative">
              <input type="text" 
                     id="applied-file-number" 
                     name="applied_file_number"
                     class="w-full px-4 py-4 bg-gray-50 border-2 border-green-200 rounded-lg text-gray-900 font-mono text-lg cursor-pointer hover:bg-gray-100 transition-all"
                     placeholder="Click 'Browse Files' to select..."
                     readonly
                     onclick="openFileNumberModal()"
                     title="Click to select file number">
              <div class="absolute inset-y-0 right-0 flex items-center pr-4">
                <i data-lucide="file-search" class="w-6 h-6 text-green-500"></i>
              </div>
            </div>
            
            {{-- File Number Details --}}
            <div id="file-number-details" class="mt-6 hidden">
              <div class="space-y-3">
                <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                  <span class="text-gray-600">Type:</span>
                  <span id="file-type" class="font-semibold text-green-700">-</span>
                </div>
                <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                  <span class="text-gray-600">Status:</span>
                  <span id="file-status" class="font-semibold text-green-700">-</span>
                </div>
                <div class="flex justify-between items-center text-sm bg-gray-50 px-3 py-2 rounded">
                  <span class="text-gray-600">Owner:</span>
                  <span id="file-owner" class="font-semibold text-green-700">-</span>
                </div>
              </div>
            </div>
            
            <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
              <div class="flex items-start">
                <i data-lucide="info" class="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-green-800">
                  Link this application to an existing file number from the registry.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Applicant Details Section --}}
    @include('primaryform.applicant')

    {{-- Owner Address Section --}}
    <div class="bg-gray-50 p-4 rounded-md mb-6" id="mainOwnerAddressSection">
      <div class="mb-4">
        <p class="text-sm mb-1">Owner's Address</p>
        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm mb-1">House No.</label>
            <input type="text" id="ownerHouseNo" class="w-full p-2 border border-gray-300 rounded-md" placeholder="HOUSE NO." name="address_house_no" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updateAddressDisplay();">
          </div>
          <div>
            <label class="block text-sm mb-1">Street Name</label>
            <input type="text" id="ownerStreetName" class="w-full p-2 border border-gray-300 rounded-md" placeholder="STREET NAME" name="owner_street_name" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updateAddressDisplay();">
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm mb-1">District</label>
            <input type="text" id="ownerDistrict" class="w-full p-2 border border-gray-300 rounded-md" placeholder="DISTRICT" name="owner_district" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updateAddressDisplay();">
          </div>
          <div>
            <label class="block text-sm mb-1">LGA <span class="text-red-500">*</span></label>
            <select id="ownerLga" class="w-full p-2 border border-gray-300 rounded-md opacity-50" name="owner_lga" onchange="updateAddressDisplay();" disabled>
              <option value="">SELECT LGA</option>
            </select>
          </div>
          <div>
            <label class="block text-sm mb-1">State <span class="text-red-500">*</span></label>
            <select id="ownerState" class="w-full p-2 border border-gray-300 rounded-md" name="owner_state" onchange="selectOwnerLGA(this); updateAddressDisplay();">
              <option value="">SELECT STATE</option>
            </select>
          </div>
        </div>
        
        <div class="mb-4">
          <label class="block text-sm mb-1">Contact Address:</label>
          <div id="contactAddressPreview" class="p-2 bg-white border border-gray-300 rounded-md min-h-[40px]">
            <span id="fullContactAddress" style="display: block; padding: 4px; text-transform: uppercase;"></span>
          </div>
          <input type="hidden" name="address" id="contactAddressDisplay">
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm mb-1">Phone No. 1 <span class="text-red-500">*</span></label>
            <input type="text" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER PHONE NUMBER" name="phone_number[]" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
          </div>
          <div>
            <label class="block text-sm mb-1">Phone No. 2</label>
            <input type="text" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER ALTERNATE PHONE" name="phone_number[]" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
          </div>
        </div>
          
        <div>
          <label class="block text-sm mb-1">Email Address</label>
          <input type="email" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Enter email address" name="owner_email">
        </div>
      </div>
    </div>

    {{-- Identification Section --}}
    <div class="grid grid-cols-2 gap-6 mb-6" id="mainOwnerIdentificationSection">
      {{-- Left column --}}
      <div id="meansOfIdentificationSection">
        <label class="block mb-2 font-medium">Means of identification <span class="text-red-500">*</span></label>
        <div class="grid grid-cols-1 gap-2">
          <label class="flex items-center">
            <input type="radio" name="idType" class="mr-2" value="national_id" checked>
            <span>National ID</span>
          </label>
          <label class="flex items-center">
            <input type="radio" name="idType" class="mr-2" value="drivers_license">
            <span>Driver's License</span>
          </label>
          <label class="flex items-center">
            <input type="radio" name="idType" class="mr-2" value="voters_card">
            <span>Voter's Card</span>
          </label>
          <label class="flex items-center">
            <input type="radio" name="idType" class="mr-2" value="international_passport">
            <span>International Passport</span>
          </label>
          <label class="flex items-center">
            <input type="radio" name="idType" class="mr-2" value="others">
            <span>Others</span>
          </label>
        </div>
      </div>
      
      {{-- Right column - ID Document Upload --}}
      <div>
        <label class="block mb-2 font-medium" id="uploadDocumentLabel">Upload ID Document <span class="text-red-500">*</span></label>
        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50 hover:bg-gray-100 transition-colors">
          <div id="idDocumentPlaceholder" class="flex flex-col items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="text-sm text-gray-600 mb-1" id="uploadDocumentText">Click to upload ID document</p>
            <p class="text-xs text-gray-500">JPG, PNG, PDF (max. 5MB)</p>
          </div>
          <img id="idDocumentPreview" class="hidden w-full h-32 object-cover rounded-md mt-2" src="#" alt="ID Document Preview">
          <div id="idDocumentInfo" class="hidden mt-2 text-sm text-gray-600"></div>
          <input type="file" id="idDocumentUpload" name="id_document" accept="image/*,.pdf" class="hidden" onchange="previewIdDocument(event)">
          <button type="button" id="removeIdDocumentBtn" class="hidden mt-2 px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600" onclick="removeIdDocument()">Remove</button>
        </div>
        <div class="mt-2">
          <button type="button" onclick="document.getElementById('idDocumentUpload').click()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Choose File
          </button>
        </div>
      </div>
    </div>

    {{-- Property Details Section --}}
    <div class="bg-gray-50 p-4 rounded-md mb-6">
      <h3 class="font-medium mb-4">Property Details</h3>
      
      @include('primaryform.types.commercial')
      @include('primaryform.types.residential')
      @include('primaryform.types.industrial')
      
      <div class="grid grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm mb-1">No. of Units <span class="text-red-500">*</span></label>
          <input type="number" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Enter number of units" name="units_count" min="1">
        </div>
        <div>
          <label class="block text-sm mb-1">No. of Blocks <span class="text-red-500">*</span></label>
          <input type="number" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Enter number of blocks" name="blocks_count" min="1">
        </div>
        <div>
          <label class="block text-sm mb-1">No. of Sections (Floors) <span class="text-red-500">*</span></label>
          <input type="number" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Enter number of floors" name="sections_count" min="1">
        </div>
        <div>
          <label class="block text-sm mb-1">Plot Size</label>
          <input type="text" class="w-full p-2 border border-gray-300 rounded-md" placeholder="Enter plot size" name="plot_size" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase();">
        </div>
      </div>
      
      <h4 class="font-medium mb-2 mt-4">Property Address</h4>
      <div class="grid grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm mb-1">Scheme Number <span class="text-red-500">*</span></label>
          <input type="text" id="schemeNumber" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER SCHEME NUMBER" name="scheme_no" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updatePropertyAddressDisplay();">
        </div>
        <div>
          <label class="block text-sm mb-1">House No.</label>
          <input type="text" id="propertyHouseNo" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER HOUSE NUMBER" name="property_house_no" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updatePropertyAddressDisplay();">
        </div>
        <div>
          <label class="block text-sm mb-1">Plot No.</label>
          <input type="text" id="propertyPlotNo" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER PLOT NUMBER" name="property_plot_no" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updatePropertyAddressDisplay();">
        </div>
        <div>
          <label class="block text-sm mb-1">Street Name <span class="text-red-500">*</span></label>
          <input type="text" id="propertyStreetName" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER STREET NAME" name="property_street_name" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updatePropertyAddressDisplay();">
        </div>
      </div>
      
      <div class="grid grid-cols-3 gap-4 mb-4">
        <div>
          <label class="block text-sm mb-1">District</label>
          <input type="text" id="propertyDistrict" class="w-full p-2 border border-gray-300 rounded-md" placeholder="ENTER DISTRICT" name="property_district" style="text-transform:uppercase" oninput="this.value = this.value.toUpperCase(); updatePropertyAddressDisplay();">
        </div>
        <div>
          <label class="block text-sm mb-1">LGA <span class="text-red-500">*</span></label>
          <select id="propertyLga" name="property_lga" class="w-full p-2 border border-gray-300 rounded-md opacity-50" onchange="updatePropertyAddressDisplay();" disabled>
            <option value="">Select LGA</option>
          </select>
        </div>
        <div>
          <label class="block text-sm mb-1">State <span class="text-red-500">*</span></label>
          <select id="propertyState" class="w-full p-2 border border-gray-300 rounded-md" name="property_state" onchange="selectPropertyLGA(this); updatePropertyAddressDisplay();">
            <option value="">Select State</option>
          </select>
        </div>
      </div>
      
      {{-- Property Address Preview and Hidden Input --}}
      <div class="mb-4">
        <label class="block text-sm mb-1">Property Address:</label>
        <div id="propertyAddressPreview" class="p-2 bg-white border border-gray-300 rounded-md min-h-[40px]">
          <span id="fullPropertyAddress" style="display: block; padding: 4px; text-transform: uppercase;"></span>
        </div>
        <input type="hidden" name="property_address" id="propertyAddressDisplay">
      </div>
    </div>

    @include('primaryform.types.ownership')
    
    <div class="mb-4">
      <label class="block text-sm mb-1">Write any comments that will assist in processing the application</label>
      <textarea class="w-full p-2 border border-gray-300 rounded-md" rows="4" placeholder="Enter any additional comments or information" name="comments"></textarea>
    </div>
    
    @include('primaryform.partials.initial_bill')

    {{-- Hidden fields for file number selection --}}
    <input type="hidden" id="selected-file-id" name="selected_file_id" value="">
    <input type="hidden" id="selected-file-type" name="selected_file_type" value="">
    <input type="hidden" id="selected-file-data" name="selected_file_data" value="">

    {{-- Navigation buttons --}}
    <div class="flex justify-between mt-8">
      <button type="button" onclick="window.history.back()" class="px-4 py-2 bg-white border border-gray-300 rounded-md">Cancel</button>
      <div class="flex items-center">
        <span class="text-sm text-gray-500 mr-4">Step 1 of 5</span>
        <button type="button" onclick="goToStep(2)" class="px-4 py-2 bg-black text-white rounded-md">Next</button>
      </div>
    </div>
  </div>
</div>