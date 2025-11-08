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
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold leading-tight text-gray-800">
                Primary Application Form
            </h2>
            <a href="{{ route('sectionaltitling.index') }}" class="btn btn-secondary">
                <i data-lucide="arrow-left" class="w-4 h-4 mr-2"></i> Back to Applications
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-1 gap-6">
            {{-- Loading Overlay --}}
            <div class="loading-overlay">
                <div class="loader"></div>
            </div>
            
            {{-- Main Form Card --}}
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <form id="primaryApplicationForm" method="POST" action="javascript:void(0)" enctype="multipart/form-data" onsubmit="return false;">
                    @csrf
                    
                    {{-- Hidden Fields --}}
                    <input type="hidden" name="land_use" value="{{ request()->query('landuse', 'COMMERCIAL') }}">
                    <input type="hidden" name="np_fileno" value="{{ $npFileNo ?? '' }}">
                    <input type="hidden" name="serial_no" value="{{ $serialNo ?? '' }}">
                    <input type="hidden" name="current_year" value="{{ $currentYear ?? date('Y') }}">
                    
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

{{-- AJAX Form Submission Handler --}}
<script>
    // Make form submit URL available globally
    window.FORM_SUBMIT_URL = "{{ route('primaryform.store') }}";
    console.log('🔗 Form submit URL:', window.FORM_SUBMIT_URL);
</script>
<script src="{{ asset('js/primaryform/form-submission.js') }}"></script>

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