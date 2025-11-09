<!-- Joint Site Inspection Modal JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Joint Site Inspection Modal functionality
    let currentApplicationId = null;
    let currentSubApplicationId = null;
    let isUnitApplication = false;
    const boundaryDirections = ['north', 'east', 'south', 'west'];

    const unitSurveyEndpointTemplate = '{{ route('programmes.planning.unit.survey', ['id' => '__UNIT_ID__']) }}';

    async function resolveApplicationId(applicationId, subApplicationId) {
        if (applicationId) {
            return String(applicationId).trim();
        }

        if (!subApplicationId) {
            return '';
        }

        const endpoint = unitSurveyEndpointTemplate.replace('__UNIT_ID__', encodeURIComponent(subApplicationId));

        try {
            const response = await fetch(endpoint, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message || 'Unable to load linked primary application details for this unit.');
            }

            if (!payload.success) {
                throw new Error(payload.message || 'Unable to load linked primary application details for this unit.');
            }

            const resolvedId = payload.data?.primary_id ?? null;

            if (!resolvedId) {
                return '';
            }

            return String(resolvedId).trim();
        } catch (error) {
            if (error instanceof SyntaxError) {
                throw new Error('Received an unexpected response while looking up the linked primary application.');
            }
            throw error;
        }
    }

    function hideAllActionMenus() {
        document.querySelectorAll('.action-menu').forEach(menu => menu.classList.add('hidden'));
    }

    function showErrorMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: message || 'An unexpected error occurred while processing your request.'
            });
        } else {
            alert(message || 'An unexpected error occurred while processing your request.');
        }
    }

    function showSuccessMessage(message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message || 'Operation completed successfully!',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            alert(message || 'Operation completed successfully!');
        }
    }

    const validationSummary = document.getElementById('jointInspectionValidationErrors');

    function resetFieldValidationState() {
        if (!jointInspectionForm) {
            return;
        }

        jointInspectionForm.querySelectorAll('[data-jsi-validate]').forEach(field => {
            field.classList.remove('border-red-400', 'ring-1', 'ring-red-500', 'focus:ring-red-500', 'bg-red-50');
            field.removeAttribute('aria-invalid');
        });

        if (validationSummary) {
            validationSummary.classList.add('hidden');
            validationSummary.innerHTML = '';
        }
    }

    function markFieldInvalid(field) {
        if (!field) {
            return;
        }

        field.classList.add('border-red-400', 'ring-1', 'ring-red-500', 'focus:ring-red-500', 'bg-red-50');
        field.setAttribute('aria-invalid', 'true');
    }

    function renderValidationErrors(errors) {
        if (!validationSummary) {
            return;
        }

        if (!errors || errors.length === 0) {
            validationSummary.classList.add('hidden');
            validationSummary.innerHTML = '';
            return;
        }

        validationSummary.innerHTML = `
            <p class="font-semibold">Please fix the following to continue:</p>
            <ul class="mt-1 list-disc space-y-1 pl-4">
                ${errors.map(error => `<li>${error}</li>`).join('')}
            </ul>
        `;
        validationSummary.classList.remove('hidden');
    }

    function validateJointInspectionForm(context = {}) {
        const errors = [];
        let firstInvalidField = null;

        resetFieldValidationState();

        const pushError = (field, message) => {
            errors.push(message);
            markFieldInvalid(field);
            if (!firstInvalidField && field) {
                firstInvalidField = field;
            }
        };

        const dateField = document.getElementById('jointInspectionDate');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        if (!dateField || !dateField.value) {
            pushError(dateField, 'Inspection date is required.');
        } else {
            const enteredDate = new Date(dateField.value);
            if (Number.isNaN(enteredDate.getTime())) {
                pushError(dateField, 'Inspection date must be a valid date.');
            } else if (enteredDate > today) {
                pushError(dateField, 'Inspection date cannot be in the future.');
            }
        }

        const textFieldRules = [
            { id: 'jointInspectionApplicant', name: 'Applicant name', required: true, maxLength: 255 },
            { id: 'jointInspectionLocation', name: 'Location', required: true, maxLength: 255 },
            { id: 'jointInspectionPlot', name: 'Plot number', required: true, maxLength: 255 },
            { id: 'jointInspectionScheme', name: 'Scheme number', required: true, maxLength: 255 },
            { id: 'jointInspectionLkn', name: 'LPKN number', required: false, maxLength: 255 },
            { id: 'jointInspectionRoadReservation', name: 'Road reservation', required: false, maxLength: 255 },
            { id: 'jointInspectionOfficer', name: 'Inspection officer / rank', required: true, maxLength: 255 },
            { id: 'jointInspectionMeasurementSummary', name: 'Utilities measurement summary', required: true, maxLength: 1000 }
        ];

        textFieldRules.forEach(rule => {
            const field = document.getElementById(rule.id);
            if (!field) {
                return;
            }

            const value = (field.value || '').trim();

            if (rule.required && value === '') {
                pushError(field, `${rule.name} is required.`);
                return;
            }

            if (rule.maxLength && value.length > rule.maxLength) {
                pushError(field, `${rule.name} must not exceed ${rule.maxLength} characters.`);
            }
        });

        const sectionsField = document.getElementById('jointInspectionSections');
        if (sectionsField) {
            const rawValue = (sectionsField.value || '').trim();
            if (rawValue === '') {
                pushError(sectionsField, 'Number of sections is required. Use 0 if not applicable.');
            } else {
                const parsed = Number(rawValue);
                if (!Number.isInteger(parsed) || parsed < 0) {
                    pushError(sectionsField, 'Number of sections must be zero or a positive whole number.');
                }
            }
        }

        const prevailingSelect = document.getElementById('jointInspectionPrevailingLandUse');
        if (prevailingSelect && prevailingSelect.value.trim() === '') {
            pushError(prevailingSelect, 'Select the prevailing land use.');
        }

        const appliedSelect = document.getElementById('jointInspectionAppliedLandUse');
        if (appliedSelect && appliedSelect.value.trim() === '') {
            pushError(appliedSelect, 'Select the applied land use.');
        }

        const complianceSelect = document.getElementById('jointInspectionCompliance');
        if (complianceSelect && complianceSelect.value.trim() === '') {
            pushError(complianceSelect, 'Select the compliance status.');
        }

        const boundarySegments = context.boundarySegments || {};
        const boundaryValues = Object.values(boundarySegments)
            .map(value => (value || '').trim())
            .filter(value => value !== '');

        if (boundaryValues.length === 0) {
            const northField = document.querySelector('[data-boundary-direction="north"]');
            pushError(northField, 'Provide at least one boundary description.');
        } else {
            ['north', 'east', 'south', 'west'].forEach(direction => {
                const value = (boundarySegments[direction] || '').trim();
                if (value.length > 1000) {
                    const field = document.querySelector(`[data-boundary-direction="${direction}"]`);
                    pushError(field, `${direction.charAt(0).toUpperCase() + direction.slice(1)} boundary must not exceed 1000 characters.`);
                }
            });
        }

        const measurementEntries = Array.isArray(context.measurementEntries)
            ? context.measurementEntries
            : [];

        measurementEntries.forEach((entry, index) => {
            if (!entry) {
                return;
            }

            const description = (entry.description || '').trim();
            const dimension = (entry.dimension || '').trim();
            const countRaw = (entry.count || '').toString().trim();

            if (description === '' && (dimension !== '' || countRaw !== '')) {
                pushError(null, `Measurement entry ${index + 1} requires a description.`);
            }

            if (countRaw !== '') {
                const numericCount = Number(countRaw);
                if (!Number.isFinite(numericCount) || numericCount < 0) {
                    pushError(null, `Measurement entry ${index + 1} count must be zero or a positive number.`);
                }
            }
        });

        const observationToggle = jointInspectionForm
            ? jointInspectionForm.querySelector('input[name="has_additional_observations"]:checked')
            : null;

        if (observationToggle && observationToggle.value === '1') {
            const observationField = document.getElementById('jointInspectionObservations');
            if (!observationField || observationField.value.trim() === '') {
                pushError(observationField, 'Enter the additional observation details or switch to "No".');
            }
        }

        renderValidationErrors(errors);

        return {
            valid: errors.length === 0,
            errors,
            firstInvalidField,
        };
    }

    function setBoundarySegmentFields(segments = {}) {
        boundaryDirections.forEach(direction => {
            const field = document.querySelector(`[data-boundary-direction="${direction}"]`);

            if (!field) {
                return;
            }

            const value = segments && typeof segments === 'object'
                ? (segments[direction] ?? '')
                : '';

            field.value = value === null ? '' : String(value);
        });
    }

    function parseBoundaryDescriptionSegments(description) {
        if (!description || typeof description !== 'string') {
            return null;
        }

        const normalized = description.replace(/\s+/g, ' ').trim();

        if (!normalized) {
            return null;
        }

        const pattern = /On the\s+(north|east|south|west)\s*:?[\s-]*(.*?)(?=(?:On the\s+(?:north|east|south|west)\s*:)|$)/gi;
        const segments = {};
        let match;

        while ((match = pattern.exec(normalized)) !== null) {
            const direction = match[1].toLowerCase();
            let text = match[2] || '';
            text = text.trim().replace(/[\s.;,]+$/, '');

            if (text !== '') {
                segments[direction] = text;
            }
        }

        if (Object.keys(segments).length > 0) {
            return segments;
        }

        const fallbackSegments = {};
        normalized.split(/\s*;\s*/).forEach(part => {
            if (!part) {
                return;
            }

            const fallbackMatch = part.match(/On the\s+(north|east|south|west)\s*:?[\s-]*(.*)/i);
            if (!fallbackMatch) {
                return;
            }

            const direction = fallbackMatch[1].toLowerCase();
            let text = (fallbackMatch[2] || '').trim().replace(/[\s.;,]+$/, '');

            if (text !== '') {
                fallbackSegments[direction] = text;
            }
        });

        return Object.keys(fallbackSegments).length > 0 ? fallbackSegments : null;
    }
 
    // Function to open the editor (modal or page)
    function openJointInspectionModal(applicationId, subApplicationId = null, options = {}) {
        const modal = document.getElementById('jointInspectionModal');
        const modalAvailable = Boolean(modal);
        const shouldDisplayModal = options.showModal !== false && modalAvailable;

        const fallbackApplicationId = window.jointInspectionDefaults?.application_id ?? null;
        const fallbackSubApplicationId = window.jointInspectionDefaults?.sub_application_id ?? null;

        const resolvedApplicationId = applicationId ?? fallbackApplicationId ?? null;
        const resolvedSubApplicationId = subApplicationId ?? fallbackSubApplicationId ?? null;

        currentApplicationId = resolvedApplicationId !== null && resolvedApplicationId !== undefined && resolvedApplicationId !== ''
            ? String(resolvedApplicationId).trim()
            : '';
        currentSubApplicationId = resolvedSubApplicationId !== null && resolvedSubApplicationId !== undefined && resolvedSubApplicationId !== ''
            ? String(resolvedSubApplicationId).trim()
            : '';
        window.currentApplicationId = currentApplicationId;
        window.currentSubApplicationId = currentSubApplicationId;
        isUnitApplication = currentSubApplicationId !== '';

        console.log('Opening Joint Inspection Editor:', {
            applicationId: currentApplicationId,
            subApplicationId: currentSubApplicationId,
            isUnitApplication,
            shouldDisplayModal
        });

        // Set form values
        const modalApplicationField = document.getElementById('modal_application_id');
        const modalSubApplicationField = document.getElementById('modal_sub_application_id');

        if (modalApplicationField) {
            modalApplicationField.value = currentApplicationId;
            modalApplicationField.setAttribute('value', currentApplicationId);
            modalApplicationField.dispatchEvent(new Event('input', { bubbles: true }));
            modalApplicationField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (modalSubApplicationField) {
            modalSubApplicationField.value = currentSubApplicationId;
            modalSubApplicationField.setAttribute('value', currentSubApplicationId);
            modalSubApplicationField.dispatchEvent(new Event('input', { bubbles: true }));
            modalSubApplicationField.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Reset form and clear previous data before loading new data
        if (jointInspectionForm && options.preserveValues !== true) {
            jointInspectionForm.reset();
            resetFieldValidationState();
        }
        resetMeasurementEntriesUI();

        // Hide shared areas section
        const sharedAreasSection = document.getElementById('sharedAreasSection');
        if (sharedAreasSection) {
            sharedAreasSection.classList.add('hidden');
        }

        if (shouldDisplayModal) {
            hideAllActionMenus();
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        } else if (modalAvailable) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        // Reset workflow state
        jsiWorkflowState = {
            isSaved: false,
            isGenerated: false,
            isSubmitted: false,
            recordId: null
        };
        window.jsiWorkflowState = jsiWorkflowState;
        updateWorkflowStatus();

        // Load existing data if available
        loadExistingJointInspectionData(currentApplicationId || null, currentSubApplicationId || null);
    }

    // Function to close the editor
    function closeJointInspectionModal(options = {}) {
        const modal = document.getElementById('jointInspectionModal');
        const modalVisible = modal && modal.classList.contains('flex');

        if (modalVisible) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        if (jointInspectionForm && options.preserveValues !== true) {
            jointInspectionForm.reset();
            resetFieldValidationState();
        }
        if (options.preserveMeasurements !== true) {
            resetMeasurementEntriesUI();
        }
        hideAllActionMenus();

        if (modalVisible && options.reload) {
            window.location.reload();
            return;
        }

        if (!modalVisible) {
            if (options.reload) {
                window.location.reload();
                return;
            }

            if (options.redirect !== false) {
                const targetUrl = options.returnUrl || window.jointInspectionReturnUrl;
                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            }
        }
    }

    // Function to load existing data
    async function loadExistingJointInspectionData(applicationId, subApplicationId = null) {
        console.log('Loading existing data for:', { applicationId, subApplicationId });
        
        try {
            let endpoint = '';
            let data = null;
            
            if (subApplicationId) {
                // Load unit application data
                endpoint = '{{ route("programmes.planning.unit.survey", ["id" => "__UNIT_ID__"]) }}'.replace('__UNIT_ID__', subApplicationId);
            } else if (applicationId) {
                // Load primary application data
                endpoint = '{{ route("programmes.planning.primary.survey", ["id" => "__PRIMARY_ID__"]) }}'.replace('__PRIMARY_ID__', applicationId);
            }
            
            if (endpoint) {
                const response = await fetch(endpoint, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data) {
                        data = result.data;
                    }
                }
            }
            
            // Pre-populate form fields based on loaded data
            if (data) {
                // Set applicant name from owner_name if available
                const applicantField = document.getElementById('jointInspectionApplicant');
                if (applicantField && data.owner_name) {
                    applicantField.value = data.owner_name;
                }
                
                // Set location from property_location or location field if available
                const locationField = document.getElementById('jointInspectionLocation');
                if (locationField && (data.property_location || data.location)) {
                    locationField.value = data.property_location || data.location;
                }
                
                // Set scheme number
                const schemeField = document.getElementById('jointInspectionScheme');
                if (schemeField && data.scheme_plan_number) {
                    schemeField.value = data.scheme_plan_number;
                } else if (schemeField && data.scheme_no) {
                    schemeField.value = data.scheme_no;
                }
                
                // Set LPKN number
                const lknField = document.getElementById('jointInspectionLkn');
                if (lknField && data.lkn_number) {
                    lknField.value = data.lkn_number;
                }
                
                // Display shared areas from application
                if (data.shared_utilities && Array.isArray(data.shared_utilities) && data.shared_utilities.length > 0) {
                    const sharedAreasSection = document.getElementById('sharedAreasSection');
                    const sharedAreasDisplay = document.getElementById('sharedAreasDisplay');
                    
                    if (sharedAreasSection && sharedAreasDisplay) {
                        // Clear previous content
                        sharedAreasDisplay.innerHTML = '';
                        
                        // Add each shared area as a badge
                        data.shared_utilities.forEach(area => {
                            const badge = document.createElement('span');
                            badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200';
                            badge.textContent = area;
                            sharedAreasDisplay.appendChild(badge);
                        });
                        
                        // Show the section
                        sharedAreasSection.classList.remove('hidden');
                    }
                } else {
                    // Hide the section if no shared areas
                    const sharedAreasSection = document.getElementById('sharedAreasSection');
                    if (sharedAreasSection) {
                        sharedAreasSection.classList.add('hidden');
                    }
                }
                
                // Auto-populate measurement entries from shared utilities and measurements
                const measurementEntries = [];

                if (Array.isArray(data.shared_utilities) && data.shared_utilities.length > 0) {
                    data.shared_utilities.forEach(rawUtility => {
                        const description = (rawUtility || '')
                            .toString()
                            .replace(/[_-]/g, ' ')
                            .replace(/\b\w/g, letter => letter.toUpperCase())
                            .trim();

                        if (description) {
                            measurementEntries.push({
                                description,
                                count: '1',
                                dimension: ''
                            });
                        }
                    });
                }

                if (Array.isArray(data.site_measurements) && data.site_measurements.length > 0) {
                    data.site_measurements.forEach(measurement => {
                        const description = (measurement.utility_type || measurement.description || '')
                            .toString()
                            .trim();
                        const dimensionValue = measurement.measurement || measurement.dimension || '';

                        if (!description) {
                            return;
                        }

                        const existing = measurementEntries.find(entry => entry.description.toLowerCase() === description.toLowerCase());
                        if (existing) {
                            existing.dimension = dimensionValue !== undefined && dimensionValue !== null
                                ? String(dimensionValue)
                                : '';
                            existing.count = measurement.count !== undefined && measurement.count !== null
                                ? String(measurement.count)
                                : (measurement.quantity !== undefined && measurement.quantity !== null
                                    ? String(measurement.quantity)
                                    : (existing.count && String(existing.count).trim() !== '' ? String(existing.count).trim() : '1'));
                        } else {
                            measurementEntries.push({
                                description,
                                count: measurement.count !== undefined && measurement.count !== null
                                    ? String(measurement.count)
                                    : (measurement.quantity !== undefined && measurement.quantity !== null
                                        ? String(measurement.quantity)
                                        : '1'),
                                dimension: dimensionValue !== undefined && dimensionValue !== null
                                    ? String(dimensionValue)
                                    : ''
                            });
                        }
                    });
                }

                if (measurementEntries.length > 0) {
                    propagateMeasurementEntries(measurementEntries);
                } else {
                    resetMeasurementEntriesUI();
                }
            }
            
            // Load existing joint inspection report if it exists
            if (applicationId || subApplicationId) {
                await loadExistingInspectionReport(applicationId, subApplicationId);
            }
            
            // Update workflow status after loading data
            updateWorkflowStatus();
            
        } catch (error) {
            console.error('Error loading existing data:', error);
            // Don't show error to user as this is optional pre-population
        }
    }
    
    // Load existing inspection report data
    async function loadExistingInspectionReport(applicationId, subApplicationId) {
        try {
            // Determine the correct route based on application type
            let route = '';
            if (subApplicationId) {
                route = '{{ route("sub-actions.planning-recommendation.joint-inspection.show", ["subApplication" => "__SUB_ID__"]) }}'.replace('__SUB_ID__', subApplicationId);
            } else if (applicationId) {
                route = '{{ route("planning-recommendation.joint-inspection.show", ["application" => "__APP_ID__"]) }}'.replace('__APP_ID__', applicationId);
            }
            
            if (!route) {
                console.log('No valid route found for loading existing inspection report');
                return;
            }
            
            const response = await fetch(route, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (response.ok) {
                const result = await response.json();
                if (result.success && result.data) {
                    const report = result.data;
                    
                    // Set workflow state based on existing report
                    jsiWorkflowState.recordId = report.id;
                    jsiWorkflowState.isSaved = true; // If record exists, it's been saved
                    jsiWorkflowState.isGenerated = Boolean(report.is_generated === 1 || report.is_generated === true);
                    jsiWorkflowState.isSubmitted = Boolean(report.is_submitted === 1 || report.is_submitted === true);
                    
                    console.log('DEBUG - Loaded existing report workflow state:', jsiWorkflowState);
                    
                    // Populate form fields from existing report
                    populateFormFromReport(report);
                    
                    // Update workflow status after loading
                    updateWorkflowStatus();
                }
            } else if (response.status === 404) {
                // No existing report found - this is normal for new inspections
                console.log('No existing inspection report found (404)');
            } else {
                console.warn('Error loading existing inspection report:', response.status, response.statusText);
            }
        } catch (error) {
            console.error('Error loading existing inspection report:', error);
        }
    }

    function normalizeInspectionDateValue(dateValue) {
        if (!dateValue) {
            return '';
        }

        if (typeof window.formatDateForInput === 'function') {
            const formatted = window.formatDateForInput(dateValue);
            if (formatted) {
                return formatted;
            }
        }

        if (typeof dateValue === 'string') {
            const isoMatch = dateValue.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (isoMatch) {
                return `${isoMatch[1]}-${isoMatch[2]}-${isoMatch[3]}`;
            }
        }

        try {
            const parsed = new Date(dateValue);
            if (!isNaN(parsed.getTime())) {
                const year = parsed.getUTCFullYear();
                const month = String(parsed.getUTCMonth() + 1).padStart(2, '0');
                const day = String(parsed.getUTCDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        } catch (error) {
            console.warn('Unable to normalize inspection date value:', dateValue, error);
        }

        return '';
    }

    // Populate form fields from existing report data
    function populateFormFromReport(report) {
        // Populate basic fields
        const fieldMappings = {
            'jointInspectionDate': 'inspection_date',
            'jointInspectionLkn': 'lkn_number',
            'jointInspectionLocation': 'location',
            'jointInspectionPlot': 'plot_number',  
            'jointInspectionScheme': 'scheme_number',
            'jointInspectionSections': 'sections_count',
            'jointInspectionRoadReservation': 'road_reservation',
            'jointInspectionPrevailingLandUse': 'prevailing_land_use',
            'jointInspectionAppliedLandUse': 'applied_land_use',
            'jointInspectionCompliance': 'compliance_status',
            'jointInspectionOfficer': 'inspection_officer',
            'jointInspectionMeasurementSummary': 'existing_site_measurement_summary',
            'jointInspectionObservations': 'additional_observations'
        };

        Object.entries(fieldMappings).forEach(([fieldId, reportKey]) => {
            const field = document.getElementById(fieldId);
            if (!field || !(reportKey in report)) {
                return;
            }

            let value = report[reportKey];

            if (value === null || typeof value === 'undefined') {
                return;
            }

            if (fieldId === 'jointInspectionDate') {
                value = normalizeInspectionDateValue(value);
                if (!value) {
                    return;
                }
            }

            if (typeof value === 'object') {
                return;
            }

            field.value = value;
        });

        // Handle checkboxes and radio buttons
        if (report.available_on_ground) {
            const checkbox = document.querySelector('input[name="available_on_ground"]');
            if (checkbox) checkbox.checked = true;
        }

        if (report.has_additional_observations !== null) {
            const radio = document.querySelector(`input[name="has_additional_observations"][value="${report.has_additional_observations}"]`);
            if (radio) {
                radio.checked = true;
                // Trigger change event to show/hide observations wrapper
                radio.dispatchEvent(new Event('change'));
            }
        }

        // Handle boundary descriptions
        let boundarySegments = null;

        if (report.boundary_segments) {
            try {
                boundarySegments = typeof report.boundary_segments === 'string'
                    ? JSON.parse(report.boundary_segments)
                    : report.boundary_segments;
            } catch (error) {
                console.warn('Error parsing boundary segments:', error);
            }
        }

        if ((!boundarySegments || Object.keys(boundarySegments).length === 0) && report.boundary_description) {
            const parsedSegments = parseBoundaryDescriptionSegments(report.boundary_description);
            if (parsedSegments) {
                boundarySegments = parsedSegments;
            }
        }

        setBoundarySegmentFields(boundarySegments || {});

        // Handle measurement entries
        if (report.existing_site_measurement_entries) {
            try {
                const measurements = typeof report.existing_site_measurement_entries === 'string'
                    ? JSON.parse(report.existing_site_measurement_entries)
                    : report.existing_site_measurement_entries;

                if (Array.isArray(measurements)) {
                    const normalized = measurements.map(entry => ({
                        description: (entry.description || entry.utility_type || '').toString().trim(),
                        count: entry.count !== undefined && entry.count !== null
                            ? String(entry.count).trim()
                            : (entry.quantity ? String(entry.quantity).trim() : ''),
                        dimension: entry.dimension !== undefined && entry.dimension !== null
                            ? String(entry.dimension)
                            : (entry.measurement ? String(entry.measurement) : '')
                    })).filter(entry => entry.description);

                    if (normalized.length > 0) {
                        propagateMeasurementEntries(normalized);
                    } else {
                        resetMeasurementEntriesUI();
                    }
                }
            } catch (error) {
                console.warn('Error parsing measurement entries:', error);
                resetMeasurementEntriesUI();
            }
        } else {
            resetMeasurementEntriesUI();
        }
    }

    // Handle form submission
    const jointInspectionForm = document.getElementById('jointInspectionForm');
    if (!jointInspectionForm) {
        console.warn('Joint inspection form element not found. Aborting modal setup.');
        return;
    }

    // Shared function to prepare form data
    function prepareFormData() {
        const formData = new FormData(jointInspectionForm);
        const csrfTokenInput = jointInspectionForm.querySelector('input[name="_token"]');

        if (!csrfTokenInput) {
            throw new Error('Security token is missing. Please refresh the page and try again.');
        }
        
        // Collect boundary segments
        const boundarySegments = {};
        boundaryDirections.forEach(direction => {
            const field = document.querySelector(`[data-boundary-direction="${direction}"]`);
            boundarySegments[direction] = field ? field.value.trim() : '';
        });

        const measurementEntries = collectMeasurementEntries();

        const validationResult = validateJointInspectionForm({
            boundarySegments,
            measurementEntries
        });

        if (!validationResult.valid) {
            if (validationResult.firstInvalidField && typeof validationResult.firstInvalidField.focus === 'function') {
                validationResult.firstInvalidField.focus({ preventScroll: false });
                validationResult.firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            throw new Error('Please review the highlighted fields before saving the report.');
        }
        
        // Generate boundary description
        const boundaryDescription = generateBoundaryDescription(boundarySegments);
        const boundaryField = document.getElementById('jointInspectionBoundary');
        if (boundaryField) {
            boundaryField.value = boundaryDescription;
        }
        formData.set('boundary_description', boundaryDescription);
        
        formData.delete('boundary_segments');
        boundaryDirections.forEach(direction => {
            formData.set(`boundary_segments[${direction}]`, boundarySegments[direction] || '');
        });

        // Ensure we always send an array, even if empty
        const measurementEntriesArray = Array.isArray(measurementEntries) ? measurementEntries : [];
        console.log('Measurement entries being sent:', measurementEntriesArray);
        
        // Send measurement entries as proper form array data
        measurementEntriesArray.forEach((entry, index) => {
            formData.set(`existing_site_measurement_entries[${index}][sn]`, entry.sn || '');
            formData.set(`existing_site_measurement_entries[${index}][description]`, entry.description || '');
            formData.set(`existing_site_measurement_entries[${index}][count]`, entry.count && String(entry.count).trim() !== '' ? String(entry.count).trim() : '1');
            formData.set(`existing_site_measurement_entries[${index}][dimension]`, entry.dimension || '');
        });
        
        return { formData, csrfToken: csrfTokenInput.value };
    }

    // Shared function to submit form data
    function submitFormData(formData, csrfToken, action = 'save') {
        // Add action type to form data
        formData.set('action', action);
        
        // Determine the correct route based on application type
        const route = isUnitApplication 
            ? '{{ route("sub-actions.planning-recommendation.joint-inspection.store") }}'
            : '{{ route("planning-recommendation.joint-inspection.store") }}';
        
        return fetch(route, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(async response => {
            let data;
            try {
                data = await response.json();
            } catch (parseError) {
                console.error('Failed to parse response JSON:', parseError);
                throw new Error('Received an unexpected response from the server.');
            }

            if (!response.ok) {
                throw new Error(data.message || 'An error occurred while processing the request.');
            }

            if (!data.success) {
                throw new Error(data.message || 'An error occurred while processing the request.');
            }

            return data;
        });
    }

    // JSI Workflow State Management
    let jsiWorkflowState = {
        isSaved: false,
        isGenerated: false,
        isSubmitted: false,
        recordId: null
    };

    window.jsiWorkflowState = jsiWorkflowState;

    // Update workflow status display
    function updateWorkflowStatus() {
        const statusIndicator = document.getElementById('statusIndicator');
        const statusText = document.getElementById('statusText');
        const saveBtn = document.getElementById('jointInspectionSave');
        const generateBtn = document.getElementById('jointInspectionGenerate');
        const submitBtn = document.getElementById('jointInspectionSubmit');

        // Check if elements exist before updating
        if (!statusIndicator || !statusText || !saveBtn) {
            console.warn('Required workflow elements not found, skipping status update');
            return;
        }

        if (jsiWorkflowState.isSubmitted) {
            // Final state: All buttons disabled
            statusIndicator.innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>';
            statusText.textContent = 'Submitted';
            
            saveBtn.disabled = true;
            saveBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            saveBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                generateBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }
            
        } else if (jsiWorkflowState.isGenerated) {
            // Generated state: Save enabled for revisions, Generate disabled, Submit enabled
            statusIndicator.innerHTML = '<span class="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>';
            statusText.textContent = 'Generated';
            
            saveBtn.disabled = false;
            saveBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            saveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                generateBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                submitBtn.classList.add('bg-purple-600', 'hover:bg-purple-700');
            }
        } else if (jsiWorkflowState.isSaved) {
            // Saved state prior to auto-generation fallback: Save enabled, Generate enabled, Submit disabled
            statusIndicator.innerHTML = '<span class="w-2 h-2 bg-blue-400 rounded-full mr-1"></span>';
            statusText.textContent = 'Saved';
            
            saveBtn.disabled = false;
            saveBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            saveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            if (generateBtn) {
                generateBtn.disabled = false;
                generateBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                generateBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }
            
        } else {
            // Draft state: Only Save enabled
            statusIndicator.innerHTML = '<span class="w-2 h-2 bg-gray-400 rounded-full mr-1"></span>';
            statusText.textContent = 'Draft';
            
            saveBtn.disabled = false;
            saveBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            saveBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            if (generateBtn) {
                generateBtn.disabled = true;
                generateBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
                generateBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed');
            }
        }
    }

    window.updateJointInspectionWorkflowStatus = updateWorkflowStatus;


    // Handle Save button click
    const saveButton = document.getElementById('jointInspectionSave');
    if (saveButton) {
        saveButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (saveButton.disabled) {
                return;
            }
            
            try {
                const { formData, csrfToken } = prepareFormData();
                
                // Add record ID if we have one (for updates)
                if (jsiWorkflowState.recordId) {
                    formData.set('record_id', jsiWorkflowState.recordId);
                }
                
                saveButton.disabled = true;
                saveButton.textContent = 'Saving...';
                saveButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                saveButton.classList.add('bg-gray-400', 'cursor-not-allowed');
                
                submitFormData(formData, csrfToken, 'save')
                .then(data => {
                    jsiWorkflowState.isSaved = true;
                    jsiWorkflowState.recordId = data.report_id || data.record_id;
                    
                    // Set workflow flags based on server response
                    if (data.is_generated !== undefined) {
                        jsiWorkflowState.isGenerated = Boolean(data.is_generated);
                    }
                    if (data.is_submitted !== undefined) {
                        jsiWorkflowState.isSubmitted = Boolean(data.is_submitted);
                    }
                    
                    saveButton.disabled = false;
                    saveButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                    saveButton.textContent = 'Save';

                    const fallbackUrl = data.report_url || data.view_url || null;
                    if (fallbackUrl) {
                        window.lastJointInspectionViewUrl = fallbackUrl;
                    }

                    updateWorkflowStatus();
                    showSuccessMessage(data.message || 'Joint Site Inspection Report saved successfully!');
                })
                .catch(error => {
                    console.error('Error saving joint inspection report:', error);
                    showErrorMessage(error.message || 'An error occurred while saving the report.');
                    
                    // Reset button state on error
                    saveButton.disabled = false;
                    saveButton.textContent = 'Save';
                    saveButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                });
            } catch (error) {
                showErrorMessage(error.message);
                
                // Reset button state on error
                saveButton.disabled = false;
                saveButton.textContent = 'Save';
                saveButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
            }
        });
    }

    // Handle Generate Report button click
    const generateButton = document.getElementById('jointInspectionGenerate');
    if (generateButton) {
        generateButton.addEventListener('click', function(e) {
            e.preventDefault();

            if (generateButton.disabled) {
                return;
            }

            if (!jsiWorkflowState.isSaved) {
                showErrorMessage('Please save the Joint Site Inspection Report before marking it as generated.');
                return;
            }

            // If the report is already generated, just refresh the status flag without calling the generate endpoint again
            if (jsiWorkflowState.isGenerated && typeof updateGeneratedStatus === 'function') {
                generateButton.disabled = true;
                generateButton.textContent = 'Updating...';
                generateButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                generateButton.classList.add('bg-gray-400', 'cursor-not-allowed');

                updateGeneratedStatus()
                    .then(() => {
                        showSuccessMessage('Joint Site Inspection Report marked as generated.');
                        generateButton.textContent = 'Generate';
                        updateWorkflowStatus();
                    })
                    .catch(error => {
                        console.error('Error updating generated status:', error);
                        showErrorMessage(error.message || 'Could not update the generated status.');
                        generateButton.disabled = false;
                        generateButton.textContent = 'Generate';
                        generateButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                        generateButton.classList.add('bg-green-600', 'hover:bg-green-700');
                    });
                return;
            }

            try {
                const { formData, csrfToken } = prepareFormData();
                if (jsiWorkflowState.recordId) {
                    formData.set('record_id', jsiWorkflowState.recordId);
                }

                generateButton.disabled = true;
                generateButton.textContent = 'Generating...';
                generateButton.classList.remove('bg-green-600', 'hover:bg-green-700');
                generateButton.classList.add('bg-gray-400', 'cursor-not-allowed');

                submitFormData(formData, csrfToken, 'generate')
                    .then(data => {
                        jsiWorkflowState.isGenerated = true;

                        if (data.is_generated !== undefined) {
                            jsiWorkflowState.isGenerated = Boolean(data.is_generated);
                        }
                        if (data.is_submitted !== undefined) {
                            jsiWorkflowState.isSubmitted = Boolean(data.is_submitted);
                        }

                        updateWorkflowStatus();

                        if (data.report_url) {
                            window.lastJointInspectionViewUrl = data.report_url;
                            window.open(data.report_url, '_blank');
                        }

                        showSuccessMessage(data.message || 'Joint Site Inspection Report generated successfully!');
                        generateButton.textContent = 'Generate';
                        updateWorkflowStatus();
                    })
                    .catch(error => {
                        console.error('Error generating joint inspection report:', error);
                        showErrorMessage(error.message || 'An error occurred while generating the report.');

                        generateButton.disabled = false;
                        generateButton.textContent = 'Generate';
                        generateButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                        generateButton.classList.add('bg-green-600', 'hover:bg-green-700');
                    });
            } catch (error) {
                showErrorMessage(error.message);
                generateButton.disabled = false;
                generateButton.textContent = 'Generate';
                generateButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                generateButton.classList.add('bg-green-600', 'hover:bg-green-700');
            }
        });
    }

    // Handle Submit button click
    const submitButton = document.getElementById('jointInspectionSubmit');
    if (submitButton) {
        submitButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Prevent submit if not generated or already submitted
            if (!jsiWorkflowState.isGenerated || jsiWorkflowState.isSubmitted || submitButton.disabled) {
                console.log('Submit prevented: not generated, already submitted, or button disabled');
                return;
            }
            
            // Confirm submission
            if (!confirm('Are you sure you want to submit this Joint Site Inspection Report? This action cannot be undone.')) {
                return;
            }
            
            try {
                const { formData, csrfToken } = prepareFormData();
                if (jsiWorkflowState.recordId) {
                    formData.set('record_id', jsiWorkflowState.recordId);
                }
                
                submitButton.disabled = true;
                submitButton.textContent = 'Submitting...';
                submitButton.classList.remove('bg-purple-600', 'hover:bg-purple-700');
                submitButton.classList.add('bg-gray-400', 'cursor-not-allowed');
                
                submitFormData(formData, csrfToken, 'submit')
                .then(data => {
                    jsiWorkflowState.isSubmitted = true;
                    
                    // Set workflow flags based on server response
                    if (data.is_submitted !== undefined) {
                        jsiWorkflowState.isSubmitted = Boolean(data.is_submitted);
                    }
                    
                    updateWorkflowStatus();
                    showSuccessMessage(data.message || 'Joint Site Inspection Report submitted successfully!');
                    
                    setTimeout(() => {
                        const closeOptions = {
                            reload: Boolean(data.reload),
                            returnUrl: data.redirect_url || data.return_url || data.back_url || window.jointInspectionReturnUrl,
                            redirect: data.redirect === false ? false : true
                        };
                        closeJointInspectionModal(closeOptions);
                    }, 2000);
                })
                .catch(error => {
                    console.error('Error submitting joint inspection report:', error);
                    showErrorMessage(error.message || 'An error occurred while submitting the report.');
                    
                    // Reset button state on error
                    submitButton.disabled = false;
                    submitButton.textContent = 'Submit';
                    submitButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
                    submitButton.classList.add('bg-purple-600', 'hover:bg-purple-700');
                });
            } catch (error) {
                showErrorMessage(error.message);
                submitButton.disabled = false;
                submitButton.textContent = 'Submit';
            }
        });
    }

    // Prevent default form submission
    jointInspectionForm.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    // Handle modal close events
    document.querySelectorAll('[data-joint-inspection-dismiss]').forEach(element => {
        element.addEventListener('click', closeJointInspectionModal);
    });

    // Handle additional observations toggle
    document.querySelectorAll('input[name="has_additional_observations"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const wrapper = document.getElementById('jointInspectionObservationsWrapper');
            if (this.value === '1') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
            }
        });
    });

    // Handle boundary description generation
    function generateBoundaryDescription(segments) {
        const descriptions = [];

        boundaryDirections.forEach(direction => {
            if (segments[direction] && segments[direction].trim()) {
                descriptions.push(`On the ${direction}: ${segments[direction].trim()}`);
            }
        });
        
        if (descriptions.length === 0) {
            return 'Boundary description not provided.';
        }
        
        return descriptions.join('; ') + '.';
    }

    function normalizeMeasurementEntry(entry, index) {
        const description = (entry?.description || entry?.utility_type || '').toString().trim();
        const dimensionValue = entry?.dimension !== undefined && entry?.dimension !== null
            ? String(entry.dimension)
            : (entry?.measurement ? String(entry.measurement) : '');
        const countValue = entry?.count !== undefined && entry?.count !== null
            ? String(entry.count)
            : (entry?.quantity ? String(entry.quantity) : '');

        return {
            sn: index + 1,
            description,
            count: countValue.trim() !== '' ? countValue.trim() : '1',
            dimension: dimensionValue.trim()
        };
    }

    function propagateMeasurementEntries(entries = []) {
        const sourceArray = Array.isArray(entries) ? entries : [];
        const normalized = sourceArray
            .map((entry, index) => normalizeMeasurementEntry(entry, index))
            .filter(entry => entry.description);

        const displayEntries = normalized.length > 0
            ? normalized
            : [{ sn: 1, description: '', count: '1', dimension: '' }];

        if (window.__jointInspectionUI && typeof window.__jointInspectionUI.setMeasurementEntries === 'function') {
            window.__jointInspectionUI.setMeasurementEntries(displayEntries);
        } else {
            window.pendingMeasurementEntries = normalized;
            window.pendingMeasurementEntriesDisplay = displayEntries;
        }
    }

    function resetMeasurementEntriesUI() {
        propagateMeasurementEntries([]);
    }

    function collectMeasurementEntries() {
        try {
            if (window.__jointInspectionUI && typeof window.__jointInspectionUI.getMeasurementEntries === 'function') {
                const entries = window.__jointInspectionUI.getMeasurementEntries();
                if (Array.isArray(entries)) {
                    return entries
                        .map((entry, index) => normalizeMeasurementEntry(entry, index))
                        .filter(entry => entry.description);
                }
            }

            if (Array.isArray(window.pendingMeasurementEntries)) {
                return window.pendingMeasurementEntries
                    .map((entry, index) => normalizeMeasurementEntry(entry, index))
                    .filter(entry => entry.description);
            }

            const hiddenField = document.getElementById('existing_site_measurement_entries');
            if (hiddenField && hiddenField.value) {
                const parsed = JSON.parse(hiddenField.value);
                if (Array.isArray(parsed)) {
                    return parsed
                        .map((entry, index) => normalizeMeasurementEntry(entry, index))
                        .filter(entry => entry.description);
                }
            }
        } catch (error) {
            console.warn('Error collecting measurement entries:', error);
        }

        return [];
    }



    // Global functions to allow external triggers
    window.openJointInspectionModal = openJointInspectionModal;
    window.initializeJointInspectionEditor = function(applicationId, subApplicationId = null, options = {}) {
        const config = Object.assign({ showModal: false }, options || {});
        openJointInspectionModal(applicationId, subApplicationId, config);
    };

    // Handle "Enter Inspection Details" button clicks
    document.addEventListener('click', async function(e) {
        if (e.target.closest('.joint-inspection-trigger')) {
            e.preventDefault();
            const button = e.target.closest('.joint-inspection-trigger');
            if (!button) {
                return;
            }

            hideAllActionMenus();

            let applicationId = (button.dataset.applicationId || '').trim();
            const subApplicationId = (button.dataset.subApplicationId || '').trim();

            const normalizedSubApplicationId = subApplicationId || null;

            try {
                applicationId = await resolveApplicationId(applicationId, normalizedSubApplicationId);
            } catch (error) {
                console.error('Joint inspection trigger error:', error);
                showErrorMessage(error.message || 'Unable to open the joint inspection modal right now.');
                return;
            }

            if (!applicationId && !normalizedSubApplicationId) {
                showErrorMessage('Unable to open inspection modal because the application ID is missing.');
                return;
            }

            openJointInspectionModal(applicationId || '', normalizedSubApplicationId);
        }
    });
});
</script>