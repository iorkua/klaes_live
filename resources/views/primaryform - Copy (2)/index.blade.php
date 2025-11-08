@extends('layouts.app')
@section('page-title')
    {{ __('Primary Application Form') }}
@endsection

{{-- Include CSS Assets --}}
@include('sectionaltitling.partials.assets.css')
@include('primaryform.assets.css.styles')

{{-- External Libraries --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/htmx.org@1.9.10"></script>
<link rel="stylesheet" href="{{ asset('css/global-fileno-modal.css') }}">

@section('content')
<div class="flex-1 overflow-auto">
    {{-- Header --}}
    @include('admin.header')
    
    {{-- Dashboard Content --}}
    <div class="p-6 space-y-6">
        @php
            $draftMeta = $draftBootstrap ?? [];
            $draftList = $draftMeta['drafts'] ?? [];
            $initialMode = $draftMeta['mode'] ?? 'fresh';
        @endphp
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold leading-tight text-gray-800">
                
            </h2>
            <a href="{{ route('sectionaltitling.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Applications
            </a>
        </div>

        {{-- Instructions Panel --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-blue-200 bg-blue-100">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-blue-900">📌 Instructions for Users Before Filling the Sectional Titling Application Form</h3>
                </div>
            </div>
            <div class="px-6 py-4 space-y-4">
                <p class="text-sm text-blue-800 font-medium">Please ensure the following steps are completed before attempting to fill the Sectional Titling Application Form:</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</div>
                            <div>
                                <h4 class="font-semibold text-blue-900">Initial Bill Settlement</h4>
                                <ul class="mt-1 text-sm text-blue-800 space-y-1">
                                    <li>• Confirm that the Initial Bill has been fully paid.</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</div>
                            <div>
                                <h4 class="font-semibold text-blue-900">Mother File Scanning</h4>
                                <ul class="mt-1 text-sm text-blue-800 space-y-1">
                                    <li>• Ensure that the Mother File has been completely scanned.</li>
                                    <li>• Save all scanned documents in the A4 Sub folder of the folder named strictly with the MLSFileNo (e.g., COM-1987-234).</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">3</div>
                            <div>
                                <h4 class="font-semibold text-blue-900">Buyers' List Capture</h4>
                                <ul class="mt-1 text-sm text-blue-800 space-y-1">
                                    <li>• Confirm that all buyers have been pre-captured.</li>
                                    <li>• Use the official Buyers' List Template (CSV format) and save the file appropriately.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">4</div>
                            <div>
                                <h4 class="font-semibold text-blue-900">Passport Photograph</h4>
                                <ul class="mt-1 text-sm text-blue-800 space-y-1">
                                    <li>• Scan and save the passport photograph of each buyer.</li>
                                    <li>• Use the naming format: MLSFileNo_PP, example: COM-1987-234_PP</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">5</div>
                            <div>
                                <h4 class="font-semibold text-blue-900">Means of Identification</h4>
                                <ul class="mt-1 text-sm text-blue-800 space-y-1">
                                    <li>• Scan and save the approved identification document(s).</li>
                                    <li>• Use the naming format: MLSFileNo_ID, example: COM-1987-234_ID</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-md">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-sm font-medium text-amber-800">
                            <strong>⚠️ Note:</strong> Failure to complete these preparatory steps will result in the delays of the application processing.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-md px-4 py-3 flex flex-col md:flex-row md:items-center md:justify-between gap-4" id="draftStatusContainer">
            <div>
                <p class="text-sm text-blue-900 font-semibold">Draft status: <span id="draftStatusText" class="font-bold text-blue-700">Initializing…</span></p>
                <p class="text-xs text-blue-700" id="draftLastSavedText">Last saved: {{ $draftMeta['last_saved_at'] ? \Carbon\Carbon::parse($draftMeta['last_saved_at'])->diffForHumans() : 'Not yet saved' }}</p>
                <p class="text-xs text-blue-700 mt-1" id="draftCollaboratorText">Collaborators: <span id="draftCollaboratorCount">{{ isset($draftMeta['collaborators']) ? count($draftMeta['collaborators']) : 1 }}</span></p>
            </div>
            <div class="flex items-center gap-4 w-full md:w-auto">
                <div class="flex-1 md:flex-none w-full md:w-56 h-2 bg-white border border-blue-200 rounded-full overflow-hidden">
                    <div id="draftProgressBar" class="h-full bg-blue-600 transition-all duration-500" style="width: {{ $draftMeta['progress_percent'] ?? 0 }}%"></div>
                </div>
                <span id="draftProgressValue" class="text-sm font-semibold text-blue-900">{{ number_format($draftMeta['progress_percent'] ?? 0, 0) }}%</span>
                <div class="flex items-center gap-2">
                    <button type="button" id="draftHistoryButton" class="hidden items-center px-3 py-1.5 border border-blue-200 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-100">History</button>
                    <button type="button" id="draftShareButton" class="items-center px-3 py-1.5 border border-blue-200 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-100">Share</button>
                    <button type="button" id="draftExportButton" class="items-center px-3 py-1.5 border border-blue-200 text-blue-700 text-xs font-medium rounded-md hover:bg-blue-100">Download</button>
                </div>
            </div>
        </div>

        <div class="bg-white border border-blue-200 rounded-md px-4 py-4 space-y-4" id="draftLocatorContainer">
            <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">
                <div class="space-y-3 w-full">
                    <div class="flex flex-wrap items-center gap-2" id="draftModeSwitch">
                        <button type="button" id="draftModeFreshButton" data-mode="fresh" class="draft-mode-button px-3 py-1.5 text-xs font-semibold rounded-md transition border border-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 {{ $initialMode === 'fresh' ? 'bg-blue-600 text-white border-blue-600' : 'text-blue-700 hover:bg-blue-50' }}">Fresh Application</button>
                        <button type="button" id="draftModeDraftButton" data-mode="draft" class="draft-mode-button px-3 py-1.5 text-xs font-semibold rounded-md transition border border-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1 {{ $initialMode === 'draft' ? 'bg-blue-600 text-white border-blue-600' : 'text-blue-700 hover:bg-blue-50' }}">Continue from Draft</button>
                        <span class="text-xs text-gray-500">Choose how you’d like to begin.</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xs uppercase tracking-wide text-gray-500">Current file number:</span>
                        <code id="draftLocatorCurrentId" class="text-xs font-mono px-2 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded break-all">{{ $draftMeta['np_file_no'] ?? 'Not assigned yet' }}</code>
                        <button type="button" id="draftLocatorCopyButton" class="text-xs font-medium text-blue-600 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">Copy</button>
                    </div>
                </div>
                <form id="draftLocatorForm" class="flex flex-col sm:flex-row sm:items-center gap-2 w-full xl:w-auto">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full">
                        <input type="text" id="draftLocatorInput" name="draft_locator" class="flex-1 border border-blue-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" placeholder="Enter file number (NPFN)">
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">Load Draft</button>
                    </div>
                    <p id="draftLocatorFeedback" class="mt-1 text-xs font-medium hidden"></p>
                </form>
            </div>

            <div id="draftModeDraftPanel" class="rounded-md border border-dashed border-blue-200 bg-blue-50/40 px-3 py-3 {{ $initialMode === 'draft' && !empty($draftList) ? '' : 'hidden' }}">
                <div class="flex flex-col lg:flex-row lg:items-center gap-2">
                    <label for="draftListSelect" class="text-xs font-semibold text-blue-800 uppercase tracking-wide">My drafts</label>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full">
                        <select id="draftListSelect" class="flex-1 border border-blue-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                            <option value="">Select a draft to resume</option>
                            @foreach ($draftList as $draftSummary)
                                @php
                                    $optionValue = $draftSummary['np_file_no'] ?? $draftSummary['draft_id'];
                                    $defaultLabel = $draftSummary['np_file_no'] ?? ('Draft ' . substr($draftSummary['draft_id'], 0, 8));
                                    $optionLabel = $draftSummary['label'] ?? $defaultLabel;
                                @endphp
                                <option value="{{ $optionValue }}" data-draft-id="{{ $draftSummary['draft_id'] }}" {{ !empty($draftSummary['is_current']) ? 'selected' : '' }}>
                                    {{ $optionLabel }}
                                </option>
                            @endforeach
                        </select>
                        <button type="button" id="draftListLoadButton" class="px-3 py-2 bg-white text-blue-700 border border-blue-300 text-sm font-semibold rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-1">Load Selected</button>
                    </div>
                </div>
                <p id="draftListEmpty" class="mt-2 text-xs text-blue-700 {{ empty($draftList) ? '' : 'hidden' }}">You don’t have any saved drafts yet. Start a fresh application to create one.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
            {{-- Loading Overlay --}}
            <div class="loading-overlay">
                <div class="loader"></div>
            </div>
            
            {{-- Main Form Card --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <form id="primaryApplicationForm" method="POST" action="javascript:void(0)" enctype="multipart/form-data" onsubmit="return false;" data-draft-id="{{ $draftMeta['draft_id'] ?? '' }}" data-draft-version="{{ $draftMeta['version'] ?? 1 }}" data-auto-save-frequency="{{ $draftMeta['auto_save_frequency'] ?? 30 }}">
                    @csrf
                    
                    {{-- Hidden Fields --}}
                    <input type="hidden" name="land_use" value="{{ request()->query('landuse', 'COMMERCIAL') }}">
                    <input type="hidden" name="np_fileno" value="{{ $npFileNo ?? '' }}">
                    <input type="hidden" name="serial_no" value="{{ $serialNo ?? '' }}">
                    <input type="hidden" name="current_year" value="{{ $currentYear ?? date('Y') }}">
                    <input type="hidden" name="draft_id" id="draftIdInput" value="{{ $draftMeta['draft_id'] ?? '' }}">
                    <input type="hidden" name="draft_version" id="draftVersionInput" value="{{ $draftMeta['version'] ?? 1 }}">
                    <input type="hidden" name="draft_last_completed_step" id="draftStepInput" value="{{ $draftMeta['last_completed_step'] ?? 1 }}">
                    
                    {{-- Step 1: Basic Information --}}
                    @include('primaryform.partials.steps.step1-basic')
                    
                    {{-- Step 2: Shared Areas --}}
                    @include('primaryform.partials.steps.step2-sharedareas')
                    
                    {{-- Step 3: Documents --}}
                    @include('primaryform.partials.steps.step3-documents')
                    
                    {{-- Step 4: Buyers List --}}
                    @include('primaryform.partials.steps.step4-buyers')
                    
                    {{-- Step 5: Summary --}}
                    @include('primaryform.partials.steps.step5-summary')
                </form>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    @include('admin.footer')
</div>

{{-- Print Template (Hidden) --}}
@include('primaryform.partials.print')

{{-- JavaScript Assets --}}
{{-- Include Global File Number Modal Component --}}
@include('components.global-fileno-modal')

@include('primaryform.assets.js.scripts')

{{-- Scan Upload Global Variables --}}
<script src="{{ asset('js/primaryform/scan-upload-globals.js') }}"></script>

{{-- AJAX Form Submission Handler --}}
<script>
    // Make form submit URL available globally
    window.FORM_SUBMIT_URL = "{{ route('primaryform.store') }}";
    console.log('🔗 Form submit URL:', window.FORM_SUBMIT_URL);
</script>
<script src="{{ asset('js/primaryform/form-submission.js') }}"></script>
<script>
    window.PRIMARY_DRAFT_BOOTSTRAP = @json($draftMeta ?? []);
    @php
        $loadTemplate = route('primaryform.draft.load', ['draftId' => '__DRAFT_ID__']);
        $deleteTemplate = route('primaryform.draft.delete', ['draftId' => '__DRAFT_ID__']);
        $exportTemplate = route('primaryform.draft.export', ['draftId' => '__DRAFT_ID__']);
        $analyticsTemplate = route('primaryform.draft.analytics', ['draftId' => '__DRAFT_ID__']);
    @endphp
    window.PRIMARY_DRAFT_ENDPOINTS = {
        save: "{{ route('primaryform.draft.save') }}",
        submit: "{{ route('primaryform.draft.submit') }}",
        load: "{{ $loadTemplate }}",
        delete: "{{ $deleteTemplate }}",
        export: "{{ $exportTemplate }}",
        analytics: "{{ $analyticsTemplate }}",
        check: "{{ route('primaryform.draft.check', ['applicationId' => '__APPLICATION_ID__']) }}",
        share: "{{ route('primaryform.draft.share') }}",
        start: "{{ route('primaryform.draft.start') }}",
        mine: "{{ route('primaryform.draft.mine') }}"
    };
</script>
<script src="{{ asset('js/draft-autosave.js') }}"></script>

{{-- Emergency Address Fix - Direct Inline Functions --}}
<script>
console.log('🚀 Loading emergency address fix...');

// Emergency address display functions
function emergencyUpdateAddressDisplay() {
    console.log('🏠 Emergency updateAddressDisplay called');
    
    const houseNo = document.getElementById('ownerHouseNo')?.value || '';
    const streetName = document.getElementById('ownerStreetName')?.value || '';
    const district = document.getElementById('ownerDistrict')?.value || '';
    const lga = document.getElementById('ownerLga')?.value || '';
    const state = document.getElementById('ownerState')?.value || '';
    
    const fullAddress = [houseNo, streetName, district, lga, state]
        .filter(part => part && part.trim() !== '')
        .join(', ');
    
    console.log('📍 Contact Address:', fullAddress);
    
    const displayElement = document.getElementById('fullContactAddress');
    const hiddenElement = document.getElementById('contactAddressDisplay');
    
    if (displayElement) {
        displayElement.textContent = fullAddress || 'Enter address details above';
    }
    
    if (hiddenElement) {
        hiddenElement.value = fullAddress;
    }
}

function emergencyUpdatePropertyAddressDisplay() {
    console.log('🏢 Emergency updatePropertyAddressDisplay called');
    
    const houseNo = document.getElementById('propertyHouseNo')?.value || '';
    const plotNo = document.getElementById('propertyPlotNo')?.value || '';
    const streetName = document.getElementById('propertyStreetName')?.value || '';
    const district = document.getElementById('propertyDistrict')?.value || '';
    const lga = document.getElementById('propertyLga')?.value || '';
    const state = document.getElementById('propertyState')?.value || '';
    
    const fullAddress = [houseNo, plotNo, streetName, district, lga, state]
        .filter(part => part && part.trim() !== '')
        .join(', ');
    
    console.log('🏗️ Property Address:', fullAddress);
    
    const displayElement = document.getElementById('fullPropertyAddress');
    const hiddenElement = document.getElementById('propertyAddressDisplay');
    
    if (displayElement) {
        displayElement.textContent = fullAddress || 'Enter property details above';
    }
    
    if (hiddenElement) {
        hiddenElement.value = fullAddress;
    }
}

// Always use the emergency functions to avoid conflicts
console.log('🔧 Setting emergency functions as primary');
window.updateAddressDisplay = emergencyUpdateAddressDisplay;
window.updatePropertyAddressDisplay = emergencyUpdatePropertyAddressDisplay;

// Setup event listeners on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎯 Setting up emergency address listeners...');
    
    // Contact address fields
    const contactFields = ['ownerHouseNo', 'ownerStreetName', 'ownerDistrict', 'ownerLga', 'ownerState'];
    contactFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            element.addEventListener('input', emergencyUpdateAddressDisplay);
            element.addEventListener('change', emergencyUpdateAddressDisplay);
            console.log('✅ Added listeners to ' + fieldId);
        }
    });
    
    // Property address fields  
    const propertyFields = ['propertyHouseNo', 'propertyPlotNo', 'propertyStreetName', 'propertyDistrict', 'propertyLga', 'propertyState'];
    propertyFields.forEach(fieldId => {
        const element = document.getElementById(fieldId);
        if (element) {
            element.addEventListener('input', emergencyUpdatePropertyAddressDisplay);
            element.addEventListener('change', emergencyUpdatePropertyAddressDisplay);
            console.log('✅ Added listeners to ' + fieldId);
        }
    });
    
    // Initialize displays
    emergencyUpdateAddressDisplay();
    emergencyUpdatePropertyAddressDisplay();
    
    console.log('✅ Emergency address fix initialized');
});

console.log('🔧 Emergency address fix loaded');

// Global form submit URL for AJAX handler
window.FORM_SUBMIT_URL = '{{ route("primaryform.store") }}';
console.log('🎯 Form submit URL set to:', window.FORM_SUBMIT_URL);

// ID Document upload preview functionality
function previewIdDocument(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        const placeholder = document.getElementById('idDocumentPlaceholder');
        const preview = document.getElementById('idDocumentPreview');
        const info = document.getElementById('idDocumentInfo');
        const removeBtn = document.getElementById('removeIdDocumentBtn');

        // Validate file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'Please select a file smaller than 5MB.',
                confirmButtonColor: '#dc3545'
            });
            event.target.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select a JPG, PNG, or PDF file.',
                confirmButtonColor: '#dc3545'
            });
            event.target.value = '';
            return;
        }

        if (file.type === 'application/pdf') {
            // For PDF files, show file info instead of preview
            placeholder.classList.add('hidden');
            preview.classList.add('hidden');
            info.classList.remove('hidden');
            info.innerHTML = `
                <div class="flex items-center justify-center">
                    <svg class="w-8 h-8 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path>
                    </svg>
                    <div>
                        <p class="font-medium">${file.name}</p>
                        <p class="text-xs text-gray-500">${(file.size / 1024 / 1024).toFixed(2)} MB</p>
                    </div>
                </div>
            `;
            removeBtn.classList.remove('hidden');
        } else {
            // For image files, show preview
            reader.onload = function(e) {
                placeholder.classList.add('hidden');
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                info.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }

        // Show success toast
        Swal.fire({
            icon: 'success',
            title: 'File Uploaded',
            text: `${file.name} has been selected successfully.`,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    }
}

// Remove ID document functionality
function removeIdDocument() {
    const upload = document.getElementById('idDocumentUpload');
    const placeholder = document.getElementById('idDocumentPlaceholder');
    const preview = document.getElementById('idDocumentPreview');
    const info = document.getElementById('idDocumentInfo');
    const removeBtn = document.getElementById('removeIdDocumentBtn');

    upload.value = '';
    preview.src = '#';
    preview.classList.add('hidden');
    info.classList.add('hidden');
    placeholder.classList.remove('hidden');
    removeBtn.classList.add('hidden');

    Swal.fire({
        icon: 'info',
        title: 'File Removed',
        text: 'ID document has been removed.',
        timer: 1500,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}
</script>

@endsection