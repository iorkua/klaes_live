@extends('layouts.app')
@section('page-title')
{{ __('EDIT UNIT APPLICATION') }}
@endsection

@include('sectionaltitling.partials.assets.css')
@section('content')
<!-- Main Content -->
<div class="flex-1 overflow-auto">
    <!-- Header -->
    @include('admin.header')
    <!-- Dashboard Content -->
    <div class="p-6">
        <!-- Edit Unit Application Form -->
        <div class="bg-white rounded-md shadow-sm border border-gray-200 p-6">
            <div class="container mx-auto mt-4 p-4">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-gray-50 border-b border-gray-200 py-3 px-4">
                        <div class="flex justify-between items-center">
                            <h2 class="text-xl font-bold text-gray-800">Edit Unit Application</h2>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('sectionaltitling.viewrecorddetail_sub', ['id' => $application->id]) }}" 
                                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-md transition duration-150 ease-in-out flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M7.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l2.293 2.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Back to Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-6">
                        @if(session('success'))
                            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if(session('error'))
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                {{ session('error') }}
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                                <ul class="list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('sectionaltitling.update_sub', $application->id) }}" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <!-- Mother Application Info -->
                            @if(isset($motherApplication))
                            <div class="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                <h3 class="text-lg font-semibold text-blue-800 mb-4 pb-2 border-b border-blue-300">Original Application Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700 mb-1">Original File No:</label>
                                        <p class="text-blue-900 font-medium">{{ $motherApplication->fileno ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700 mb-1">Original Owner:</label>
                                        <p class="text-blue-900">
                                            @if($motherApplication->applicant_type == 'corporate')
                                                {{ $motherApplication->corporate_name ?? 'N/A' }}
                                            @else
                                                {{ $motherApplication->applicant_title ?? '' }} {{ $motherApplication->first_name ?? '' }} {{ $motherApplication->surname ?? '' }}
                                            @endif
                                        </p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-blue-700 mb-1">Land Use:</label>
                                        <p class="text-blue-900">{{ ucfirst($motherApplication->land_use ?? 'N/A') }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Unit Owner Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Unit Owner Information</h3>
                                
                                <!-- Applicant Type -->
                                <div class="mb-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Applicant Type</label>
                                    <select name="applicantType" id="applicant_type" class="w-full py-2 px-3 border border-gray-300 rounded-md" onchange="toggleApplicantFields()">
                                        <option value="">Select Applicant Type</option>
                                        <option value="individual" {{ $application->applicant_type == 'individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="corporate" {{ $application->applicant_type == 'corporate' ? 'selected' : '' }}>Corporate</option>
                                        <option value="multiple" {{ $application->applicant_type == 'multiple' ? 'selected' : '' }}>Multiple Owners</option>
                                    </select>
                                </div>

                                <!-- Individual Applicant Fields -->
                                <div id="individual_fields" class="{{ $application->applicant_type == 'individual' ? '' : 'hidden' }}">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Individual Unit Owner Details</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                            <select name="applicant_title" class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                                <option value="">Select title</option>
                                                <option value="Mr." {{ $application->applicant_title == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                                <option value="Mrs." {{ $application->applicant_title == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                                <option value="Chief" {{ $application->applicant_title == 'Chief' ? 'selected' : '' }}>Chief</option>
                                                <option value="Master" {{ $application->applicant_title == 'Master' ? 'selected' : '' }}>Master</option>
                                                <option value="Capt" {{ $application->applicant_title == 'Capt' ? 'selected' : '' }}>Capt</option>
                                                <option value="Coln" {{ $application->applicant_title == 'Coln' ? 'selected' : '' }}>Coln</option>
                                                <option value="Pastor" {{ $application->applicant_title == 'Pastor' ? 'selected' : '' }}>Pastor</option>
                                                <option value="King" {{ $application->applicant_title == 'King' ? 'selected' : '' }}>King</option>
                                                <option value="Prof" {{ $application->applicant_title == 'Prof' ? 'selected' : '' }}>Prof</option>
                                                <option value="Dr." {{ $application->applicant_title == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                                <option value="Alhaji" {{ $application->applicant_title == 'Alhaji' ? 'selected' : '' }}>Alhaji</option>
                                                <option value="Alhaja" {{ $application->applicant_title == 'Alhaja' ? 'selected' : '' }}>Alhaja</option>
                                                <option value="High Chief" {{ $application->applicant_title == 'High Chief' ? 'selected' : '' }}>High Chief</option>
                                                <option value="Lady" {{ $application->applicant_title == 'Lady' ? 'selected' : '' }}>Lady</option>
                                                <option value="Bishop" {{ $application->applicant_title == 'Bishop' ? 'selected' : '' }}>Bishop</option>
                                                <option value="Senator" {{ $application->applicant_title == 'Senator' ? 'selected' : '' }}>Senator</option>
                                                <option value="Messr" {{ $application->applicant_title == 'Messr' ? 'selected' : '' }}>Messr</option>
                                                <option value="Honorable" {{ $application->applicant_title == 'Honorable' ? 'selected' : '' }}>Honorable</option>
                                                <option value="Miss" {{ $application->applicant_title == 'Miss' ? 'selected' : '' }}>Miss</option>
                                                <option value="Rev." {{ $application->applicant_title == 'Rev.' ? 'selected' : '' }}>Rev.</option>
                                                <option value="Barr." {{ $application->applicant_title == 'Barr.' ? 'selected' : '' }}>Barr.</option>
                                                <option value="Arc." {{ $application->applicant_title == 'Arc.' ? 'selected' : '' }}>Arc.</option>
                                                <option value="Sister" {{ $application->applicant_title == 'Sister' ? 'selected' : '' }}>Sister</option>
                                                <option value="Other" {{ $application->applicant_title == 'Other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                                            <input type="text" name="first_name" value="{{ old('first_name', $application->first_name) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                            <input type="text" name="middle_name" value="{{ old('middle_name', $application->middle_name) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Surname</label>
                                            <input type="text" name="surname" value="{{ old('surname', $application->surname) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>

                                <!-- Corporate Applicant Fields -->
                                <div id="corporate_fields" class="{{ $application->applicant_type == 'corporate' ? '' : 'hidden' }}">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Corporate Unit Owner Details</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Corporate Name</label>
                                            <input type="text" name="corporate_name" value="{{ old('corporate_name', $application->corporate_name) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">RC Number</label>
                                            <input type="text" name="rc_number" value="{{ old('rc_number', $application->rc_number) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>

                                <!-- Multiple Owners Fields -->
                                <div id="multiple_fields" class="{{ $application->applicant_type == 'multiple' ? '' : 'hidden' }}">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Multiple Unit Owners Details</h4>
                                    <div class="space-y-4">
                                        @php
                                            $multipleOwnersNames = [];
                                            if (isset($application->multiple_owners_names_array)) {
                                                $multipleOwnersNames = $application->multiple_owners_names_array;
                                            } elseif ($application->multiple_owners_names) {
                                                $multipleOwnersNames = is_string($application->multiple_owners_names) 
                                                    ? json_decode($application->multiple_owners_names, true) 
                                                    : $application->multiple_owners_names;
                                                if (!is_array($multipleOwnersNames)) {
                                                    $multipleOwnersNames = [];
                                                }
                                            }
                                        @endphp
                                        
                                        <div id="owners_container">
                                            @if(count($multipleOwnersNames) > 0)
                                                @foreach($multipleOwnersNames as $index => $ownerName)
                                                    <div class="owner-row flex items-center gap-4 mb-2">
                                                        <div class="flex-1">
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">Owner {{ $index + 1 }} Name</label>
                                                            <input type="text" name="multiple_owners_names[]" value="{{ $ownerName }}" 
                                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md" 
                                                                   placeholder="Enter owner name">
                                                        </div>
                                                        @if($index > 0)
                                                            <button type="button" onclick="removeOwner(this)" 
                                                                    class="bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 mt-6">
                                                                Remove
                                                            </button>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="owner-row flex items-center gap-4 mb-2">
                                                    <div class="flex-1">
                                                        <label class="block text-sm font-medium text-gray-700 mb-1">Owner 1 Name</label>
                                                        <input type="text" name="multiple_owners_names[]" value="" 
                                                               class="w-full py-2 px-3 border border-gray-300 rounded-md" 
                                                               placeholder="Enter owner name">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <button type="button" onclick="addOwner()" 
                                                class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">
                                            Add Another Owner
                                        </button>
                                    </div>
                                </div>

                                <!-- Contact Information -->
                                <div class="mt-6">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Contact Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                            <input type="email" name="owner_email" value="{{ old('owner_email', $application->email) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                            <input type="text" name="phone_number" value="{{ old('phone_number', $application->phone_number) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md" 
                                                   placeholder="Enter phone number">
                                        </div>
                                    </div>
                                </div>

                                <!-- Address Information -->
                                <div class="mt-6">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Address Information</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="col-span-2">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Address</label>
                                            <textarea name="address" rows="3" class="w-full py-2 px-3 border border-gray-300 rounded-md" placeholder="Enter full address">{{ old('address', $application->address) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Street Name</label>
                                            <input type="text" name="address_street_name" value="{{ old('address_street_name', $application->address_street_name) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">District</label>
                                            <input type="text" name="address_district" value="{{ old('address_district', $application->address_district) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">LGA</label>
                                            <input type="text" name="address_lga" value="{{ old('address_lga', $application->address_lga) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">State</label>
                                            <input type="text" name="address_state" value="{{ old('address_state', $application->address_state) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Unit Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Unit Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Block Number</label>
                                        <input type="text" name="block_number" value="{{ old('block_number', $application->block_number) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Floor Number</label>
                                        <input type="text" name="floor_number" value="{{ old('floor_number', $application->floor_number) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Unit Number</label>
                                        <input type="text" name="unit_number" value="{{ old('unit_number', $application->unit_number) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Scheme Number</label>
                                        <input type="text" name="scheme_no" value="{{ old('scheme_no', $application->scheme_no) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                </div>

                                <!-- Property Type -->
                                <div class="mt-6">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Property Type</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Residence Type</label>
                                            <select name="residence_type" class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                                <option value="">Select Type</option>
                                                <option value="detached" {{ $application->residence_type == 'detached' ? 'selected' : '' }}>Detached</option>
                                                <option value="semi_detached" {{ $application->residence_type == 'semi_detached' ? 'selected' : '' }}>Semi-Detached</option>
                                                <option value="terrace" {{ $application->residence_type == 'terrace' ? 'selected' : '' }}>Terrace</option>
                                                <option value="apartment" {{ $application->residence_type == 'apartment' ? 'selected' : '' }}>Apartment</option>
                                                <option value="flat" {{ $application->residence_type == 'flat' ? 'selected' : '' }}>Flat</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Commercial Type</label>
                                            <input type="text" name="commercial_type" value="{{ old('commercial_type', $application->commercial_type) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md" placeholder="e.g., Office, Shop, Restaurant">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Industrial Type</label>
                                            <input type="text" name="industrial_type" value="{{ old('industrial_type', $application->industrial_type) }}" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md" placeholder="e.g., Factory, Warehouse">
                                        </div>
                                    </div>
                                </div>

                                <!-- Shared Areas -->
                                <div class="mt-6">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Shared Areas</h4>
                                    @php
                                        $sharedAreas = isset($application->shared_areas_array) ? $application->shared_areas_array : [];
                                        $availableSharedAreas = ['parking', 'garden', 'playground', 'swimming_pool', 'gym', 'laundry', 'storage', 'rooftop', 'lobby', 'security_post'];
                                    @endphp
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                                        @foreach($availableSharedAreas as $area)
                                            <label class="flex items-center">
                                                <input type="checkbox" name="shared_areas[]" value="{{ $area }}" 
                                                       {{ in_array($area, $sharedAreas) ? 'checked' : '' }}
                                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $area)) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Financial Information</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Application Fee</label>
                                        <input type="number" step="0.01" name="application_fee" value="{{ old('application_fee', $application->application_fee) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Processing Fee</label>
                                        <input type="number" step="0.01" name="processing_fee" value="{{ old('processing_fee', $application->processing_fee) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Site Plan Fee</label>
                                        <input type="number" step="0.01" name="site_plan_fee" value="{{ old('site_plan_fee', $application->site_plan_fee) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                                        <input type="date" name="payment_date" value="{{ old('payment_date', $application->payment_date) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Receipt Number</label>
                                        <input type="text" name="receipt_number" value="{{ old('receipt_number', $application->receipt_number) }}" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                    </div>
                                </div>
                            </div>

                            <!-- Document Uploads -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Document Uploads</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Passport Photo</label>
                                        @if($application->passport)
                                            <div class="mb-2">
                                                <img src="{{ asset('storage/app/public/' . $application->passport) }}" 
                                                     alt="Current Passport" class="w-20 h-20 object-cover rounded border">
                                                <p class="text-xs text-gray-500 mt-1">Current passport photo</p>
                                            </div>
                                        @endif
                                        <input type="file" name="passport" accept="image/*" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        <p class="text-xs text-gray-500 mt-1">Upload new passport photo to replace current one</p>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">ID Document</label>
                                        @if($application->id_document)
                                            <div class="mb-2">
                                                <p class="text-sm text-green-600">✓ ID document uploaded</p>
                                            </div>
                                        @endif
                                        <input type="file" name="id_document" accept=".pdf,.jpg,.jpeg,.png" 
                                               class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        <p class="text-xs text-gray-500 mt-1">Upload new ID document to replace current one</p>
                                    </div>
                                </div>

                                <!-- Additional Documents -->
                                <div class="mt-6">
                                    <h4 class="text-md font-semibold text-gray-800 mb-3">Additional Documents</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Application Letter</label>
                                            @if(isset($application->documents_array['application_letter']))
                                                <div class="mb-2">
                                                    <p class="text-sm text-green-600">✓ Application letter uploaded</p>
                                                </div>
                                            @endif
                                            <input type="file" name="application_letter" accept=".pdf,.jpg,.jpeg,.png" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Building Plan</label>
                                            @if(isset($application->documents_array['building_plan']))
                                                <div class="mb-2">
                                                    <p class="text-sm text-green-600">✓ Building plan uploaded</p>
                                                </div>
                                            @endif
                                            <input type="file" name="building_plan" accept=".pdf,.jpg,.jpeg,.png" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Architectural Design</label>
                                            @if(isset($application->documents_array['architectural_design']))
                                                <div class="mb-2">
                                                    <p class="text-sm text-green-600">✓ Architectural design uploaded</p>
                                                </div>
                                            @endif
                                            <input type="file" name="architectural_design" accept=".pdf,.jpg,.jpeg,.png" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Ownership Document</label>
                                            @if(isset($application->documents_array['ownership_document']))
                                                <div class="mb-2">
                                                    <p class="text-sm text-green-600">✓ Ownership document uploaded</p>
                                                </div>
                                            @endif
                                            <input type="file" name="ownership_document" accept=".pdf,.jpg,.jpeg,.png" 
                                                   class="w-full py-2 px-3 border border-gray-300 rounded-md">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Comments -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Comments</h3>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Comments</label>
                                    <textarea name="application_comment" rows="4" class="w-full py-2 px-3 border border-gray-300 rounded-md">{{ old('application_comment', $application->application_comment) }}</textarea>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex justify-end gap-4 pt-6">
                                <a href="{{ route('sectionaltitling.viewrecorddetail_sub', ['id' => $application->id]) }}" 
                                   class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded-md transition duration-150 ease-in-out">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-md transition duration-150 ease-in-out">
                                    Update Unit Application
                                </button>
                            </div>
                        </form>
                    </div> <!-- end card-body -->
                </div> <!-- end card -->
            </div> <!-- end container -->
        </div> <!-- end bg-white rounded-md shadow-sm border border-gray-200 p-6 -->
 

<script>
    // Toggle applicant fields based on applicant type
    function toggleApplicantFields() {
        const applicantType = document.getElementById('applicant_type').value;
        const individualFields = document.getElementById('individual_fields');
        const corporateFields = document.getElementById('corporate_fields');
        const multipleFields = document.getElementById('multiple_fields');
        
        // Hide all fields first
        individualFields.classList.add('hidden');
        corporateFields.classList.add('hidden');
        multipleFields.classList.add('hidden');
        
        // Show relevant fields based on selection
        if (applicantType === 'individual') {
            individualFields.classList.remove('hidden');
        } else if (applicantType === 'corporate') {
            corporateFields.classList.remove('hidden');
        } else if (applicantType === 'multiple') {
            multipleFields.classList.remove('hidden');
        }
    }
    
    // Add new owner field for multiple owners
    function addOwner() {
        const container = document.getElementById('owners_container');
        const ownerRows = container.querySelectorAll('.owner-row');
        const newIndex = ownerRows.length + 1;
        
        const newRow = document.createElement('div');
        newRow.className = 'owner-row flex items-center gap-4 mb-2';
        newRow.innerHTML = `
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">Owner ${newIndex} Name</label>
                <input type="text" name="multiple_owners_names[]" value="" 
                       class="w-full py-2 px-3 border border-gray-300 rounded-md" 
                       placeholder="Enter owner name">
            </div>
            <button type="button" onclick="removeOwner(this)" 
                    class="bg-red-500 text-white px-3 py-2 rounded-md hover:bg-red-600 mt-6">
                Remove
            </button>
        `;
        
        container.appendChild(newRow);
        updateOwnerLabels();
    }
    
    // Remove owner field
    function removeOwner(button) {
        const row = button.closest('.owner-row');
        row.remove();
        updateOwnerLabels();
    }
    
    // Update owner labels to maintain sequential numbering
    function updateOwnerLabels() {
        const container = document.getElementById('owners_container');
        const ownerRows = container.querySelectorAll('.owner-row');
        
        ownerRows.forEach((row, index) => {
            const label = row.querySelector('label');
            label.textContent = `Owner ${index + 1} Name`;
            
            // Hide remove button for first owner
            const removeButton = row.querySelector('button');
            if (removeButton) {
                if (index === 0) {
                    removeButton.style.display = 'none';
                } else {
                    removeButton.style.display = 'block';
                }
            }
        });
    }
    
    // Initialize form on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleApplicantFields();
        updateOwnerLabels();
    });
</script>

<!-- Footer -->
@include('admin.footer')
@endsection