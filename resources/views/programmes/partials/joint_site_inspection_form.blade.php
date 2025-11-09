@php
    $editorMode = $editorMode ?? 'modal';
    $formClasses = trim(($formClasses ?? 'p-6') . ' space-y-6');
    $defaultCancelUrl = route('programmes.approvals.planning_recomm', ['url' => 'view']);
    $cancelUrl = $cancelUrl ?? (url()->previous() ?: $defaultCancelUrl);
@endphp

<form id="jointInspectionForm" class="{{ $formClasses }}">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <input type="hidden" name="application_id" id="modal_application_id" value="">
    <input type="hidden" name="sub_application_id" id="modal_sub_application_id" value="">
    <input type="hidden" name="existing_site_measurement_entries" id="existing_site_measurement_entries" value="">

    <div id="jointInspectionValidationErrors" class="hidden rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"></div>

    <div class="rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
        Complete every section carefully. Fields marked with <span class="font-semibold text-red-500">*</span> are required for the Joint Site Inspection report.
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Inspection Date <span class="text-red-500">*</span></label>
            <input type="date" name="inspection_date" id="jointInspectionDate" max="{{ now()->format('Y-m-d') }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" data-jsi-validate>
            <p class="mt-1 text-[11px] text-gray-500">Use the actual date the inspection took place.</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">LPKN Number <span class="text-red-500">*</span></label>
            <input type="text" name="lkn_number" id="jointInspectionLkn" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Enter LPKN number" data-jsi-validate>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Applicant Name <span class="text-red-500">*</span></label>
            <input type="text" name="applicant_name" id="jointInspectionApplicant" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Name of applicant" data-jsi-validate>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
            <input type="text" name="location" id="jointInspectionLocation" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Enter location" data-jsi-validate>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1" id="plotNumberLabel">Plot Number <span class="text-red-500">*</span></label>
            <input type="text" name="plot_number" id="jointInspectionPlot" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Plot number" data-jsi-validate>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Scheme Number <span class="text-red-500">*</span></label>
            <input type="text" name="scheme_number" id="jointInspectionScheme" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Scheme number" data-jsi-validate>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1" id="sectionsCountLabel">No. of Sections <span class="text-red-500">*</span></label>
            <input type="number" min="0" name="sections_count" id="jointInspectionSections" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Sections count" data-jsi-validate>
            <p class="mt-1 text-[11px] text-gray-500">Enter the total number of sections inspected. Use 0 if not applicable.</p>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Road Reservation <span class="text-red-500">*</span></label>
            <input type="text" name="road_reservation" id="jointInspectionRoadReservation" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="e.g. 9m" data-jsi-validate>
        </div>
    </div>

    <div class="space-y-4">
        <div id="boundaryDescriptionSection" class="space-y-3">
            <label class="block text-xs font-medium text-gray-700">Boundary Description <span class="text-red-500">*</span></label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="block text-[11px] font-medium text-gray-600 uppercase tracking-wide">North <span class="text-red-500">*</span></label>
                    <textarea data-boundary-direction="north" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Describe the northern boundary" data-jsi-validate></textarea>
                </div>
                <div class="space-y-1">
                    <label class="block text-[11px] font-medium text-gray-600 uppercase tracking-wide">East <span class="text-red-500">*</span></label>
                    <textarea data-boundary-direction="east" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Describe the eastern boundary" data-jsi-validate></textarea>
                </div>
                <div class="space-y-1">
                    <label class="block text-[11px] font-medium text-gray-600 uppercase tracking-wide">South <span class="text-red-500">*</span></label>
                    <textarea data-boundary-direction="south" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Describe the southern boundary" data-jsi-validate></textarea>
                </div>
                <div class="space-y-1">
                    <label class="block text-[11px] font-medium text-gray-600 uppercase tracking-wide">West <span class="text-red-500">*</span></label>
                    <textarea data-boundary-direction="west" rows="2" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Describe the western boundary" data-jsi-validate></textarea>
                </div>
            </div>
            <textarea name="boundary_description" id="jointInspectionBoundary" rows="3" class="hidden" aria-hidden="true"></textarea>
            <p class="text-xs text-gray-500">Enter a note for each direction. We'll draft the combined boundary report automatically.</p>
        </div>

        <div id="sharedUtilitiesSection" class="hidden">
            <p class="text-xs font-medium text-gray-700 mb-2">Shared Utilities</p>
            <div id="sharedUtilitiesContainer" class="grid grid-cols-1 md:grid-cols-2 gap-2 mb-4">
                <!-- Shared utilities checkboxes will be populated here by JavaScript -->
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-medium text-gray-700">Shared Utilities &amp; Measurements <span class="text-red-500">*</span></label>
                <button type="button" id="addMeasurementEntry" class="inline-flex items-center px-2 py-1 text-xs font-medium text-green-700 border border-green-200 rounded-md hover:bg-green-50">
                    <span class="mr-1">+</span>
                    Add Entry
                </button>
            </div>
            <div id="measurementEntriesContainer" class="space-y-2"></div>
            <p class="text-xs text-gray-500">Add measurement entries for utilities found on site. Click "Add Entry" to add rows for capturing utility measurements and details.</p>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Utilities Measurement Summary <span class="text-red-500">*</span></label>
            <textarea name="existing_site_measurement_summary" id="jointInspectionMeasurementSummary" rows="3" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Provide a short summary for the measurements section" data-jsi-validate>The following existing site measurements were observed during the joint site inspection:</textarea>
            <p class="text-xs text-gray-500 mt-1">This note appears before the site measurement list in the generated report.</p>
        </div>

        <div class="flex items-center space-x-3">
            <label class="inline-flex items-center text-sm text-gray-700">
                <input type="checkbox" name="available_on_ground" value="1" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                <span class="ml-2">Site is available on the ground <span class="text-red-500">*</span></span>
            </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Prevailing Land Use <span class="text-red-500">*</span></label>
                <select name="prevailing_land_use" id="jointInspectionPrevailingLandUse" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" data-jsi-validate>
                    <option value="">Select prevailing land use</option>
                    <option value="Residential">Residential</option>
                    <option value="Commercial">Commercial</option>
                    <option value="Industrial">Industrial</option>
                    <option value="Mixed Use">Mixed Use</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Applied Land Use <span class="text-red-500">*</span></label>
                <select name="applied_land_use" id="jointInspectionAppliedLandUse" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" data-jsi-validate>
                    <option value="">Select applied land use</option>
                    <option value="Residential">Residential</option>
                    <option value="Commercial">Commercial</option>
                    <option value="Industrial">Industrial</option>
                    <option value="Mixed Use">Mixed Use</option>
                </select>
            </div>
        </div>

        <div id="sharedAreasSection" class="hidden">
            <p class="text-xs font-medium text-gray-700 mb-2">Shared Areas (from Application)</p>
            <div id="sharedAreasDisplay" class="flex flex-wrap gap-2 mb-4">
                <!-- Shared areas will be populated here -->
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Compliance Status <span class="text-red-500">*</span></label>
                <select name="compliance_status" id="jointInspectionCompliance" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" data-jsi-validate>
                    <option value="obtainable">Obtainable</option>
                    <option value="not_obtainable">Not Obtainable</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Inspection Officer / Rank <span class="text-red-500">*</span></label>
                <input type="text" name="inspection_officer" id="jointInspectionOfficer" value="" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Officer name and rank" data-jsi-validate>
            </div>
        </div>

        <div>
            <p class="text-xs font-medium text-gray-700 mb-2">Additional Observations? <span class="text-red-500">*</span></p>
            <div class="flex items-center space-x-4">
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="radio" name="has_additional_observations" value="1" class="text-green-600 focus:ring-green-500">
                    <span class="ml-2">Yes</span>
                </label>
                <label class="inline-flex items-center text-sm text-gray-700">
                    <input type="radio" name="has_additional_observations" value="0" class="text-green-600 focus:ring-green-500" checked>
                    <span class="ml-2">No</span>
                </label>
            </div>
        </div>

        <div id="jointInspectionObservationsWrapper" class="hidden">
            <label class="block text-xs font-medium text-gray-700 mb-1">Additional Observation Details <span class="text-red-500">*</span></label>
            <textarea name="additional_observations" id="jointInspectionObservations" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-green-500" placeholder="Document notable observations" data-jsi-validate></textarea>
        </div>
    </div>

    <div class="flex items-center justify-between border-t pt-4">
        <div class="flex items-center space-x-2">
            <div id="jsiWorkflowStatus" class="text-xs text-gray-500">
                <span id="statusIndicator" class="inline-flex items-center">
                    <span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>
                    <span id="statusText">Draft</span>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            @if($editorMode === 'modal')
                <button type="button" class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50" data-joint-inspection-dismiss>Cancel</button>
            @else
                <a href="{{ $cancelUrl }}" class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
            @endif
            <button type="button" id="jointInspectionSave" class="px-4 py-2 text-sm font-semibold bg-gray-400 text-white rounded-md cursor-not-allowed" disabled>Save</button>
        </div>
    </div>
</form>
