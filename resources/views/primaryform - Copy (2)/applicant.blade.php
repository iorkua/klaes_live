<div  class="mb-6">
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <!-- Applicant Type Section -->
          
            <input type="hidden" id="applicantType" value="{{ old('applicantType') }}">

            <!-- Personal Information Section -->
            <div class="mb-10" id="individualFields" style="{{ old('applicantType') == 'individual' ? 'display: block;' : 'display: none;' }}">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Personal Information</h2>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <!-- Left side - Personal Details -->
                        <div class="md:col-span-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                                <!-- Title -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Title <span class="text-red-500">*</span>
                                    </label>
                                    <select id="applicantTitle" name="applicant_title"
                                        class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm uppercase"
                                        onchange="updateApplicantNamePreview()">
                                        <option value="" disabled {{ old('applicant_title') ? '' : 'selected' }}>Select title</option>
                                        @php
                                            $titles = [
                                                'Mr.', 'Mrs.', 'Chief', 'Master', 'Capt', 'Coln', 'HRH', 'Mallam',
                                                'Prof', 'Dr.', 'Alhaji', 'Hajia', 'High Chief', 'Senator', 'Messr',
                                                'Honorable', 'Miss', 'Barr.', 'Arc.', 'Other'
                                            ];
                                        @endphp
                                        @foreach($titles as $title)
                                            <option value="{{ $title }}" {{ old('applicant_title') == $title ? 'selected' : '' }}>
                                                {{ $title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- First Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="applicantName" name="first_name" value="{{ old('first_name') }}"
                                        class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm uppercase"
                                        placeholder="Enter first name" oninput="this.value = this.value.toUpperCase(); updateApplicantNamePreview()">
                                </div>

                                <!-- Middle Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Middle Name (Optional)
                                    </label>
                                    <input type="text" id="applicantMiddleName" name="middle_name" value="{{ old('middle_name') }}"
                                        class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm uppercase"
                                        placeholder="Enter middle name" oninput="this.value = this.value.toUpperCase(); updateApplicantNamePreview()">
                                </div>

                                <!-- Surname -->
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Surname <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="applicantSurname" name="surname" value="{{ old('surname') }}"
                                        class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm uppercase"
                                        placeholder="Enter surname" oninput="this.value = this.value.toUpperCase(); updateApplicantNamePreview()">
                                </div>
                           
                                <!-- Name of Applicant -->
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Name of Applicant
                                    </label>
                                    <input type="text" id="applicantNamePreview" name="applicant_name_preview"
                                        class="w-full py-3 px-4 bg-gray-100 border border-gray-300 rounded-lg shadow-sm font-medium text-gray-800 uppercase"
                                        disabled>
                                </div>
                            </div>
                        </div>

                        <!-- Right side - Photo Upload -->
                        <div>
                            <div id="photoUploadContainer"
                                class="relative w-full max-w-[200px] aspect-[3.5/4.5] border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center bg-gray-50 hover:bg-gray-100 transition-colors mx-auto">
                                <div id="photoPlaceholder"
                                    class="flex flex-col items-center justify-center text-gray-400 absolute inset-0 z-10 bg-gray-50 rounded-lg">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 sm:h-12 sm:w-12 mb-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-xs sm:text-sm text-center px-2">Upload Photo<br>(3.5 x 4.5 cm)</p>
                                </div>
                                <img id="photoPreview" class="w-full h-full object-contain rounded-lg absolute inset-0 z-20 hidden border-2 border-blue-400 bg-white" src="#"
                                    alt="Passport Photo Preview">
                                <button type="button" id="removePhotoBtn"
                                    class="absolute top-1 right-1 sm:top-2 sm:right-2 bg-red-500 text-white rounded-full p-1 hidden hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 z-30"
                                    onclick="removePhoto()">
                                    <svg class="w-3 h-3 sm:w-4 sm:h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                                <input type="file" id="photoUpload" name="passport" accept="image/*"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-40"
                                    onchange="previewPhoto(event)">
                            </div>
                            <p class="text-xs text-gray-500 mt-2 text-center">Passport size photo (3.5 x 4.5 cm, American embassy size, clear background, max 2MB)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Corporate Body Information -->
            <div class="mb-10" id="corporateFields" style="{{ old('applicantType') == 'corporate' ? 'display: block;' : 'display: none;' }}">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Corporate Body Information</h2>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Name of Corporate Body <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="corporateName" name="corporate_name" value="{{ old('corporate_name') }}"
                                class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                                placeholder="Enter corporate body name">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                RC Number <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="rcNumber" name="rc_number" value="{{ old('rc_number') }}"
                                class="w-full py-3 px-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                                placeholder="Enter RC number">
                        </div>
                    </div>
                    
                    <!-- RC Document Upload Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload RC Document <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center bg-gray-50 hover:bg-gray-100 transition-colors">
                                <div id="corporateDocumentPlaceholder" class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mb-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="text-sm text-gray-600 mb-1">Click to upload RC document</p>
                                    <p class="text-xs text-gray-500">JPG, PNG, PDF (max. 5MB)</p>
                                </div>
                                <img id="corporateDocumentPreview" class="hidden w-full h-32 object-cover rounded-md mt-2" src="#" alt="RC Document Preview">
                                <div id="corporateDocumentInfo" class="hidden mt-2 text-sm text-gray-600"></div>
                                <input type="file" id="corporateDocumentUpload" name="id_document" accept="image/*,.pdf" class="hidden" onchange="previewCorporateDocument(event)">
                                <button type="button" id="removeCorporateDocumentBtn" class="hidden mt-2 px-3 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600" onclick="removeCorporateDocument()">Remove</button>
                            </div>
                            <div class="mt-2">
                                <button type="button" onclick="document.getElementById('corporateDocumentUpload').click()" class="w-full px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Choose File
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>      
            
            <!-- Multiple Owners Information -->
            <div class="mb-10" id="multipleOwnersFields" style="{{ old('applicantType') == 'multiple' ? 'display: block;' : 'display: none;' }}">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Multiple Owners Information</h2>
                <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                    <div id="ownersContainer" class="space-y-4">
                        <!-- Dynamic rows will be inserted here -->
                    </div>
                    <div class="mt-6">
                        <button type="button" onclick="addOwnerRow()" 
                            class="flex items-center px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium transition-colors hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Add Owner
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>    
</div>

<script>
    let ownerRowCount = 0;

    function addOwnerRow() {
        const container = document.getElementById('ownersContainer');
        const rowId = `owner-row-${ownerRowCount}`;
        const fileInputId = `owner-passport-${ownerRowCount}`;
        const previewImgId = `owner-preview-img-${ownerRowCount}`;
        const idFileInputId = `owner-idimg-${ownerRowCount}`;
        const idPreviewImgId = `owner-idimg-preview-${ownerRowCount}`;

        const row = document.createElement('div');
        row.id = rowId;
        row.className = 'border rounded-lg p-4 bg-white flex flex-col md:flex-row md:items-start gap-4 relative';

        row.innerHTML = `
            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" name="multiple_owners_names[]"
                        class="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                        placeholder="Enter full name">
                    <div class="mt-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address <span class="text-red-500">*</span></label>
                        <textarea name="multiple_owners_address[]" rows="2"
                            class="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                            placeholder="Enter complete address"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <div>
                            <label class="block text-xs text-gray-700 mb-1">Email</label>
                            <input type="email" name="multiple_owners_email[]"
                                class="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                                placeholder="Enter email">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-700 mb-1">Phone</label>
                            <input type="text" name="multiple_owners_phone[]"
                                class="w-full py-2 px-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all shadow-sm"
                                placeholder="Enter phone">
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Passport Photo</label>
                    <input type="file" id="${fileInputId}" name="multiple_owners_passport[]" accept="image/*"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        onchange="previewOwnerPhoto(event, '${rowId}', '${previewImgId}')">
                    <img id="${previewImgId}" class="owner-preview hidden w-16 h-20 object-contain mt-2 rounded-md shadow-sm border border-gray-200 bg-white" src="#" alt="Preview">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Means of Identification <span class="text-red-500">*</span></label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <label class="flex items-center text-xs"><input type="radio" name="multiple_owners_identification_type[${ownerRowCount}]" value="national_id" class="mr-1">National ID</label>
                        <label class="flex items-center text-xs"><input type="radio" name="multiple_owners_identification_type[${ownerRowCount}]" value="drivers_license" class="mr-1">Driver's License</label>
                        <label class="flex items-center text-xs"><input type="radio" name="multiple_owners_identification_type[${ownerRowCount}]" value="voters_card" class="mr-1">Voter's Card</label>
                        <label class="flex items-center text-xs"><input type="radio" name="multiple_owners_identification_type[${ownerRowCount}]" value="international_passport" class="mr-1">Int'l Passport</label>
                    </div>
                    <input type="file" id="${idFileInputId}" name="multiple_owners_identification_image[]" accept="image/*,application/pdf"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md file:mr-2 file:py-2 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        onchange="previewOwnerIdImage(event, '${rowId}', '${idPreviewImgId}')">
                    <div class="mt-2">
                        <img id="${idPreviewImgId}" class="hidden w-16 h-16 object-contain rounded-md shadow-sm border border-gray-200 bg-white" src="#" alt="ID Preview">
                        <div class="text-xs text-gray-500" id="${idPreviewImgId}-info">No file selected</div>
                    </div>
                </div>
            </div>
            <button type="button" onclick="removeOwnerRow('${rowId}')"
                class="absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        `;

        container.appendChild(row);
        ownerRowCount++;
    }

    function previewOwnerPhoto(event, rowId, previewImgId) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            const preview = document.getElementById(previewImgId);
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    function previewOwnerIdImage(event, rowId, previewImgId) {
        const file = event.target.files[0];
        const preview = document.getElementById(previewImgId);
        const info = document.getElementById(previewImgId + '-info');
        if (file) {
            if (file.type === "application/pdf") {
                preview.src = "https://img.icons8.com/ios-filled/50/000000/pdf.png";
                preview.classList.remove('hidden');
                info.textContent = file.name;
            } else {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                    info.textContent = file.name;
                }
                reader.readAsDataURL(file);
            }
        } else {
            preview.classList.add('hidden');
            info.textContent = "No file selected";
        }
    }

    function showMultipleOwnersFields() {
        document.getElementById('individualFields').style.display = 'none';
        document.getElementById('corporateFields').style.display = 'none';
        document.getElementById('multipleOwnersFields').style.display = 'block';
        
        // Clear existing rows
        document.getElementById('ownersContainer').innerHTML = '';
        // Add first row
        addOwnerRow();
    }

    // Photo upload preview functionality
    function previewPhoto(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const photoPreview = document.getElementById('photoPreview');
                const photoPlaceholder = document.getElementById('photoPlaceholder');
                const removePhotoBtn = document.getElementById('removePhotoBtn');

                photoPreview.src = e.target.result;
                photoPreview.classList.remove('hidden');
                photoPlaceholder.classList.add('hidden');
                removePhotoBtn.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }

    // Remove photo functionality
    function removePhoto() {
        const photoUpload = document.getElementById('photoUpload');
        const photoPreview = document.getElementById('photoPreview');
        const photoPlaceholder = document.getElementById('photoPlaceholder');
        const removePhotoBtn = document.getElementById('removePhotoBtn');

        photoUpload.value = '';
        photoPreview.src = '#';
        photoPreview.classList.add('hidden');
        photoPlaceholder.classList.remove('hidden');
        removePhotoBtn.classList.add('hidden');
    }

    // Initialize the handlers when the document loads
    document.addEventListener('DOMContentLoaded', function() {
        // Show the correct fields based on old input
        const applicantType = "{{ old('applicantType') }}";
        if (applicantType === 'individual') {
            showIndividualFields();
        } else if (applicantType === 'corporate') {
            showCorporateFields();
        } else if (applicantType === 'multiple') {
            showMultipleOwnersFields();
        }
        
        // Initialize applicant name preview if returning with validation errors
        if ("{{ old('first_name') }}" || "{{ old('middle_name') }}" || "{{ old('surname') }}") {
            updateApplicantNamePreview();
        }
        
        // Restore multiple owners if any
        @if(old('multiple_owners_names'))
            @foreach(old('multiple_owners_names') as $index => $name)
                // Add owner row for each old value
                if ({{ $index }} > 0) { // Skip first row as it's added by default
                    addOwnerRow();
                }
            @endforeach
        @endif
    });

    function showIndividualFields() {
        clearOtherFields('individualFields');
        document.getElementById('individualFields').style.display = 'block';
        document.getElementById('corporateFields').style.display = 'none';
        document.getElementById('multipleOwnersFields').style.display = 'none';
    }

    function showCorporateFields() {
        clearOtherFields('corporateFields');
        document.getElementById('individualFields').style.display = 'none';
        document.getElementById('corporateFields').style.display = 'block';
        document.getElementById('multipleOwnersFields').style.display = 'none';
    }

    function clearOtherFields(exceptId) {
        const fields = ['individualFields', 'corporateFields', 'multipleOwnersFields'];
        fields.forEach(id => {
            if (id !== exceptId) {
                document.getElementById(id).querySelectorAll('input, select, textarea').forEach(input => {
                    input.value = '';
                });
            }
        });
    }

    function setApplicantType(type) {
        document.getElementById('applicantType').value = type;
    }

    function updateApplicantNamePreview() {
        const title = document.getElementById('applicantTitle').value;
        const name = document.getElementById('applicantName').value;
        const middleName = document.getElementById('applicantMiddleName').value;
        const surname = document.getElementById('applicantSurname').value;
        let applicantName = '';

        if (title) {
            applicantName += title + ' ';
        }
        if (name) {
            applicantName += name + ' ';
        }
        if (middleName) {
            applicantName += middleName + ' ';
        }
        if (surname) {
            applicantName += surname;
        }

        document.getElementById('applicantNamePreview').value = applicantName.trim();
        
        // Add hidden input for the full name to be used in form submission and summary
        let fullnameInput = document.getElementById('fullname');
        if (!fullnameInput) {
            fullnameInput = document.createElement('input');
            fullnameInput.type = 'hidden';
            fullnameInput.id = 'fullname';
            fullnameInput.name = 'fullname';
            document.querySelector('form').appendChild(fullnameInput);
        }
        fullnameInput.value = applicantName.trim();
    }

    // Corporate document upload preview functionality
    function previewCorporateDocument(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            const placeholder = document.getElementById('corporateDocumentPlaceholder');
            const preview = document.getElementById('corporateDocumentPreview');
            const info = document.getElementById('corporateDocumentInfo');
            const removeBtn = document.getElementById('removeCorporateDocumentBtn');

            // Validate file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('Please select a file smaller than 5MB.');
                event.target.value = '';
                return;
            }

            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            if (!allowedTypes.includes(file.type)) {
                alert('Please select a JPG, PNG, or PDF file.');
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
        }
    }

    // Remove corporate document functionality
    function removeCorporateDocument() {
        const upload = document.getElementById('corporateDocumentUpload');
        const placeholder = document.getElementById('corporateDocumentPlaceholder');
        const preview = document.getElementById('corporateDocumentPreview');
        const info = document.getElementById('corporateDocumentInfo');
        const removeBtn = document.getElementById('removeCorporateDocumentBtn');

        upload.value = '';
        preview.src = '#';
        preview.classList.add('hidden');
        info.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
    }

    function ensureOwnerRowCount(targetCount) {
        const container = document.getElementById('ownersContainer');
        if (!container) {
            return;
        }

        const desired = Math.max(0, Number.isFinite(Number(targetCount)) ? Math.floor(Number(targetCount)) : 0);
        let current = container.children.length;

        while (current < desired) {
            addOwnerRow();
            current = container.children.length;
        }

        while (current > desired) {
            const lastRow = container.lastElementChild;
            if (!lastRow) {
                break;
            }

            container.removeChild(lastRow);
            ownerRowCount = Math.max(0, ownerRowCount - 1);
            current = container.children.length;
        }

        ownerRowCount = container.children.length;
    }

    function populateMultipleOwnersFromState(state) {
        if (!state || typeof state !== 'object') {
            return;
        }

        const names = Array.isArray(state.multiple_owners_names) ? state.multiple_owners_names : [];
        const addresses = Array.isArray(state.multiple_owners_address) ? state.multiple_owners_address : [];
        const emails = Array.isArray(state.multiple_owners_email) ? state.multiple_owners_email : [];
        const phones = Array.isArray(state.multiple_owners_phone) ? state.multiple_owners_phone : [];

        const idTypes = [];
        Object.keys(state).forEach((key) => {
            const match = key.match(/^multiple_owners_identification_type\[(\d+)\]$/);
            if (match) {
                idTypes[Number(match[1])] = state[key];
            }
        });

        const longest = Math.max(names.length, addresses.length, emails.length, phones.length, idTypes.length);

        ensureOwnerRowCount(longest);

        const container = document.getElementById('ownersContainer');
        if (!container) {
            return;
        }

        const nameInputs = container.querySelectorAll('input[name="multiple_owners_names[]"]');
        const addressInputs = container.querySelectorAll('textarea[name="multiple_owners_address[]"]');
        const emailInputs = container.querySelectorAll('input[name="multiple_owners_email[]"]');
        const phoneInputs = container.querySelectorAll('input[name="multiple_owners_phone[]"]');

        const eventOptions = { bubbles: true };

        nameInputs.forEach((input, index) => {
            input.value = names[index] ?? '';
            input.dispatchEvent(new Event('input', eventOptions));
            input.dispatchEvent(new Event('change', eventOptions));
        });

        addressInputs.forEach((input, index) => {
            input.value = addresses[index] ?? '';
            input.dispatchEvent(new Event('input', eventOptions));
            input.dispatchEvent(new Event('change', eventOptions));
        });

        emailInputs.forEach((input, index) => {
            input.value = emails[index] ?? '';
            input.dispatchEvent(new Event('input', eventOptions));
            input.dispatchEvent(new Event('change', eventOptions));
        });

        phoneInputs.forEach((input, index) => {
            input.value = phones[index] ?? '';
            input.dispatchEvent(new Event('input', eventOptions));
            input.dispatchEvent(new Event('change', eventOptions));
        });

        const identificationGroups = container.querySelectorAll('div[id^="owner-row-"]');
        identificationGroups.forEach((row, index) => {
            const targetValue = idTypes[index];
            if (!targetValue) {
                return;
            }

            const radios = row.querySelectorAll(`input[name="multiple_owners_identification_type[${index}]"]`);
            radios.forEach((radio) => {
                radio.checked = radio.value === targetValue;
                if (radio.checked) {
                    radio.dispatchEvent(new Event('change', eventOptions));
                }
            });
        });
    }
    
    // Make functions globally accessible
    window.showIndividualFields = showIndividualFields;
    window.showCorporateFields = showCorporateFields;
    window.showMultipleOwnersFields = showMultipleOwnersFields;
    window.setApplicantType = setApplicantType;
    window.clearOtherFields = clearOtherFields;
    window.ensureOwnerRowCount = ensureOwnerRowCount;
    window.populateMultipleOwnersFromState = populateMultipleOwnersFromState;
</script>


