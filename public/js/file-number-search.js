/**
 * File Number Search and Button State Management
 * Handles searching across property_records, registered_instruments, and CofO tables
 * Manages button states based on file number existence
 */

// Global state for file number search
window.fileNumberSearchState = {
    fileNumberExists: false,
    selectedFileData: null,
    searchResults: null,
    normalized: null,
    sources: null
};

const AUTOFILL_CONFIDENCE_THRESHOLD = 0.5;
const AUTOFILL_SUMMARY_ENABLED = false;
const AUTOFILL_FIELD_CONFIG = {
    location: { selector: '#location', event: 'input' },
    petitioner: { selector: '#petitioner', event: 'input' },
    grantor: { selector: '#grantor', event: 'input' },
    grantee: { selector: '#grantee', event: 'input' },
    'serial_no': { selector: '#serial-no', event: 'input' },
    'page_no': { selector: '#page-no', event: 'input', readonly: true },
    'volume_no': { selector: '#volume-no', event: 'input' },
    'registration_number': { selector: '#registration-number', type: 'text-display' },
    'start_date': { selector: '#start-date', event: 'change' },
    'instructions': { selector: '#instructions', event: 'input' },
    'remarks': { selector: '#remarks', event: 'input' },
    'encumbrance_type': { selector: '#encumbrance-type', event: 'change', type: 'select' },
    'instrument_type': { selector: '#instrument-type', event: 'change', type: 'select' }
};

window.caveatAutofillState = {
    appliedFields: new Set(),
    summary: [],
    initialized: false
};

function initializeAutofillTracking() {
    if (window.caveatAutofillState.initialized) {
        return;
    }

    Object.values(AUTOFILL_FIELD_CONFIG).forEach(config => {
        const element = document.querySelector(config.selector);
        if (!element) {
            return;
        }

        const markEdited = () => {
            element.dataset.userEdited = 'true';
        };

        if (config.type === 'text-display') {
            return;
        }

        element.addEventListener(config.event || 'input', markEdited);
    });

    window.caveatAutofillState.initialized = true;
}

function renderAutofillSummary(summaryItems, sources) {
    const container = document.getElementById('autofill-summary');
    const list = document.getElementById('autofill-summary-list');

    if (!container || !list) {
        return;
    }

    if (!AUTOFILL_SUMMARY_ENABLED) {
        container.classList.add('hidden');
        list.innerHTML = '';
        return;
    }

    if (!summaryItems || summaryItems.length === 0) {
        container.classList.add('hidden');
        list.innerHTML = '';
        return;
    }

    const rows = summaryItems.map(item => {
        const confidence = item.confidence ? ` ${(item.confidence * 100).toFixed(0)}%` : '';
        const displayValue = formatAutofillSummaryValue(item.field, item.value);
        return `
            <li class="flex items-start gap-2 py-1">
                <span class="text-blue-600 mt-1"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                <div class="text-sm text-gray-700">
                    <span class="font-medium">${item.label}</span>
                    <span class="text-gray-500">${displayValue}</span>
                    <span class="block text-xs text-gray-400">Source: ${item.source}${confidence}</span>
                </div>
            </li>
        `;
    }).join('');

    list.innerHTML = rows;
    container.classList.remove('hidden');
}

function formatAutofillSummaryValue(field, value) {
    if (value === null || value === undefined) {
        return '';
    }

    if (field === 'start_date') {
        return formatDateTimeDisplay(value);
    }

    return value;
}

function formatDateTimeDisplay(rawValue) {
    if (!rawValue) {
        return '';
    }

    if (rawValue instanceof Date && !isNaN(rawValue.getTime())) {
        return formatDateComponents({
            year: rawValue.getFullYear(),
            month: rawValue.getMonth() + 1,
            day: rawValue.getDate(),
            hour: rawValue.getHours(),
            minute: rawValue.getMinutes()
        });
    }

    const stringValue = String(rawValue).trim();
    if (!stringValue) {
        return '';
    }

    const sanitized = stringValue.replace(/(\.\d+)?(Z|[\+\-]\d{2}:?\d{2})?$/i, '');
    const match = sanitized.match(/^(\d{4})-(\d{2})-(\d{2})(?:[T\s](\d{2}):(\d{2})(?::(\d{2}))?)?/);

    if (match) {
        const [, year, month, day, hourGroup, minuteGroup] = match;
        return formatDateComponents({
            year: Number(year),
            month: Number(month),
            day: Number(day),
            hour: hourGroup !== undefined ? Number(hourGroup) : null,
            minute: minuteGroup !== undefined ? Number(minuteGroup) : null
        });
    }

    return stringValue;
}

function formatDateComponents({ year, month, day, hour, minute }) {
    if (!year || !month || !day) {
        return '';
    }

    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const monthIndex = Math.max(0, Math.min(11, (month || 1) - 1));
    const monthLabel = months[monthIndex] || String(month).padStart(2, '0');
    const dayLabel = Number.isFinite(day) ? String(day).padStart(2, '0') : String(day);

    const hasTime = Number.isFinite(hour) && Number.isFinite(minute);
    const timeLabel = hasTime ? `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}` : '';

    return `${dayLabel} ${monthLabel} ${year}${timeLabel ? `, ${timeLabel}` : ''}`;
}

function applyNormalizedAutofill(normalized, response) {
    initializeAutofillTracking();

    caveatAutofillState.appliedFields = new Set();
    caveatAutofillState.summary = [];

    if (!normalized || !normalized.fields) {
        renderAutofillSummary([], response?.sources);
        return caveatAutofillState.appliedFields;
    }

    Object.entries(normalized.fields).forEach(([field, entry]) => {
        if (!entry || entry.value === undefined || entry.value === null) {
            return;
        }

        const config = AUTOFILL_FIELD_CONFIG[field];
        if (!config) {
            return;
        }

        const confidence = entry.confidence ?? 0;
        if (confidence < AUTOFILL_CONFIDENCE_THRESHOLD) {
            return;
        }

        const element = document.querySelector(config.selector);
        if (!element) {
            return;
        }

        if (element.dataset.userEdited === 'true') {
            return;
        }

        const applied = setAutofillValue(element, config, entry.value);
        if (!applied) {
            return;
        }

        element.dataset.autofilled = 'true';
        element.dataset.autofillSource = entry.source || entry.source_key || '';
        caveatAutofillState.appliedFields.add(field);

        caveatAutofillState.summary.push({
            field,
            label: getFieldLabel(field),
            value: config.type === 'text-display' ? entry.value : element.value || entry.value,
            source: entry.source || entry.source_key || 'auto',
            confidence: confidence
        });
    });

    renderAutofillSummary(caveatAutofillState.summary, response?.sources);

    if (caveatAutofillState.appliedFields.has('serial_no') || caveatAutofillState.appliedFields.has('volume_no')) {
        updateRegistrationNumber();
    }

    return caveatAutofillState.appliedFields;
}

function getFieldLabel(field) {
    const labels = {
        location: 'Location',
        petitioner: 'Applicant/Solicitor',
        grantor: 'Grantor',
        grantee: 'Grantee',
        serial_no: 'Serial No.',
        page_no: 'Page No.',
        volume_no: 'Volume No.',
        registration_number: 'Registration Number',
        start_date: 'Date Placed',
        instructions: 'Instructions',
        remarks: 'Remarks',
        encumbrance_type: 'Encumbrance Type',
        instrument_type: 'Instrument Type'
    };

    return labels[field] || field;
}

function setAutofillValue(element, config, value) {
    if (!element) {
        return false;
    }

    if (config.type === 'text-display') {
        if (value !== element.textContent) {
            element.textContent = value;
            return true;
        }
        return false;
    }

    if (config.type === 'select') {
        const select = element;
        const options = Array.from(select.options);
        let matched = options.find(option => option.value === value);

        if (!matched) {
            matched = options.find(option => option.text.trim().toLowerCase() === String(value).trim().toLowerCase());
        }

        if (!matched && value) {
            matched = new Option(`${value} (auto)`, value, true, true);
            matched.dataset.autofill = 'true';
            select.add(matched, select.options[1] || null);
        }

        if (matched) {
            select.value = matched.value;
            select.dispatchEvent(new Event(config.event || 'change'));
            return true;
        }
        return false;
    }

    const normalizedValue = value === undefined || value === null ? '' : String(value);
    const currentValue = element.value;
    if (currentValue === normalizedValue) {
        return false;
    }

    element.value = normalizedValue;
    element.dispatchEvent(new Event(config.event || 'input'));
    return true;
}

function resetAutofillFlags() {
    Object.values(AUTOFILL_FIELD_CONFIG).forEach(config => {
        const element = document.querySelector(config.selector);
        if (!element) {
            return;
        }

        if (config.type !== 'text-display') {
            delete element.dataset.userEdited;
        }

        delete element.dataset.autofilled;
        delete element.dataset.autofillSource;
    });

    caveatAutofillState.appliedFields = new Set();
    caveatAutofillState.summary = [];
}
/**
 * Search for file number across all 3 tables
 * @param {string} fileNumber - The file number to search for
 * @returns {Promise} - Promise that resolves with search results
 */
async function searchFileNumberInTables(fileNumber) {
    if (!fileNumber || fileNumber.trim() === '') {
        console.log('No file number provided for search');
        return null;
    }

    console.log('Searching for file number:', fileNumber);

    resetAutofillFlags();
    renderAutofillSummary([], null);

    try {
        const response = await fetch('/caveat/api/search-file-number', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                file_number: fileNumber.trim()
            })
        });

        let data;
        if (!response.ok) {
            // Try to parse JSON error message from server
            try {
                const errBody = await response.json();
                const msg = errBody.error || errBody.message || `HTTP error! status: ${response.status}`;
                throw new Error(msg);
            } catch (parseErr) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
        } else {
            data = await response.json();
        }
        console.log('File number search results:', data);

        if (data && data.success) {
            window.fileNumberSearchState.searchResults = data;
            window.fileNumberSearchState.fileNumberExists = data.found;
            window.fileNumberSearchState.selectedFileData = data.data;
            window.fileNumberSearchState.normalized = data.normalized;
            window.fileNumberSearchState.sources = data.sources;
            
            // Update Current Selection Preview for manual entry
            updateCurrentSelectionPreview(fileNumber, data.found, data.table);
            
            // Implement exact button logic as requested
            if (data.found) {
                // 2. When a file number is searched and found in any of the 3 tables
                // Save Record → Stay disabled & greyed out (not needed)
                // Place Caveat → Enabled, so the user can place a caveat immediately
                disableSaveRecordButton();
                enablePlaceCaveatButton();
                
                // Show success message (not using SweetAlert as requested)
                showToast('Record found. You can now place a caveat.', 'success');
                
                const appliedFields = applyNormalizedAutofill(data.normalized, data);

                // Populate form with existing data (fallback for fields not autofilled)
                if (data.data) {
                    populateFormWithFileData(data.data, { skipFields: appliedFields });
                }

                showFileNumberStatus('found');
            } else {
                // 3. When a file number is searched and NOT found in any of the 3 tables
                // Save Record → Enabled (so user can create a new record in property_records)
                // Place Caveat → Disabled & greyed out until the record exists
                enableSaveRecordButton();
                disablePlaceCaveatButton();
                
                // Show toast notification as requested (not SweetAlert)
                showToast('Record Not Found. Please create the record first before placing a caveat.', 'info');

                renderAutofillSummary([], null);
                caveatAutofillState.appliedFields = new Set();
                caveatAutofillState.summary = [];
                showFileNumberStatus('not_found');
            }
            
            return data;
        } else {
            console.error('File number search failed:', data.error);
            return null;
        }
    } catch (error) {
        console.error('Error searching file number:', error);
        
        // Show error message to user
        showToast('Failed to search for file number. Please try again.', 'error');
        
        return null;
    }
}

/**
 * Enable Place Caveat button
 */
function enablePlaceCaveatButton() {
    const placeCaveatBtn = document.getElementById('place-caveat');
    if (placeCaveatBtn) {
        placeCaveatBtn.disabled = false;
        placeCaveatBtn.className = 'px-4 py-2 bg-blue-600 text-white rounded-md flex items-center hover:bg-blue-700 transition-colors';
    }
}

/**
 * Disable Place Caveat button
 */
function disablePlaceCaveatButton() {
    const placeCaveatBtn = document.getElementById('place-caveat');
    if (placeCaveatBtn) {
        placeCaveatBtn.disabled = true;
        placeCaveatBtn.className = 'px-4 py-2 bg-gray-300 text-gray-600 rounded-md flex items-center cursor-not-allowed';
    }
}

/**
 * Enable Save Record button
 */
function enableSaveRecordButton() {
    const saveRecordBtn = document.getElementById('save-draft');
    if (saveRecordBtn) {
        saveRecordBtn.disabled = false;
        saveRecordBtn.className = 'px-4 py-2 border border-green-500 text-green-600 rounded-md flex items-center hover:bg-green-50 transition-colors';
    }
}

/**
 * Disable Save Record button
 */
function disableSaveRecordButton() {
    const saveRecordBtn = document.getElementById('save-draft');
    if (saveRecordBtn) {
        saveRecordBtn.disabled = true;
        saveRecordBtn.className = 'px-4 py-2 border rounded-md flex items-center bg-gray-300 text-gray-600 cursor-not-allowed';
    }
}

/**
 * Show file number search status to user
 * @param {string} status - 'found' or 'not_found'
 */
function showFileNumberStatus(status) {
    const statusContainer = document.getElementById('file-number-status');
    
    if (status === 'found') {
        const record = window.fileNumberSearchState.selectedFileData;
        const table = window.fileNumberSearchState.searchResults?.table || 'Unknown';
        
        if (statusContainer) {
            statusContainer.innerHTML = `
                <div class="mt-2 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fa-solid fa-check-circle text-green-600 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">File Number Found</p>
                            <p class="text-xs text-green-600">Found in ${table} table. Form populated with existing data.</p>
                        </div>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');
        }
        
    } else if (status === 'not_found') {
        if (statusContainer) {
            statusContainer.innerHTML = `
                <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fa-solid fa-info-circle text-yellow-600 mr-2"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800">File Number Not Found</p>
                            <p class="text-xs text-yellow-600">Click "Save record to database" to create a new record, then you can place a caveat.</p>
                        </div>
                    </div>
                </div>
            `;
            statusContainer.classList.remove('hidden');
        }
    }
}

/**
 * Populate form with existing file data
 * @param {Object} record - The record data from database
 */
function populateFormWithFileData(record, options = {}) {
    if (!record) return;

    const skipFields = options.skipFields || new Set();
    const safeGet = (obj, keys, fallback = '') => {
        if (!obj) return fallback;
        for (const key of keys) {
            if (obj[key] !== undefined && obj[key] !== null && obj[key] !== '') {
                return obj[key];
            }
        }
        return fallback;
    };

    const fillInput = (fieldKey, selector, possibleKeys) => {
        if (skipFields.has(fieldKey)) return;

        const element = document.querySelector(selector);
        if (!element || element.dataset.userEdited === 'true') return;

        const value = safeGet(record, possibleKeys);
        if (value === '' || value === null || value === undefined) return;

        if (element.value === String(value)) return;

        element.value = value;
        element.dispatchEvent(new Event('input'));
    };

    console.log('Populating form with file data (fallback):', record, { skipFields: Array.from(skipFields) });

    fillInput('location', '#location', ['property_description', 'location', 'PropertyDescription']);
    fillInput('grantor', '#grantor', ['Grantor', 'grantor']);
    fillInput('grantee', '#grantee', ['Grantee', 'grantee']);
    fillInput('petitioner', '#petitioner', ['Assignor', 'Mortgagor', 'Grantor', 'petitioner']);

    if (!skipFields.has('serial_no')) {
        const serial = safeGet(record, ['serialNo', 'serial_no', 'SerialNo']);
        const serialInput = document.getElementById('serial-no');
        if (serialInput && serial && serialInput.dataset.userEdited !== 'true') {
            serialInput.value = serial;
            serialInput.dispatchEvent(new Event('input'));
        }
    }

    if (!skipFields.has('page_no')) {
        const page = safeGet(record, ['pageNo', 'page_no', 'PageNo']);
        const pageInput = document.getElementById('page-no');
        if (pageInput && page && pageInput.dataset.userEdited !== 'true') {
            pageInput.value = page;
        }
    }

    if (!skipFields.has('volume_no')) {
        const volume = safeGet(record, ['volumeNo', 'volume_no', 'VolumeNo']);
        const volumeInput = document.getElementById('volume-no');
        if (volumeInput && volume && volumeInput.dataset.userEdited !== 'true') {
            volumeInput.value = volume;
            volumeInput.dispatchEvent(new Event('input'));
        }
    }

    if (!skipFields.has('instrument_type')) {
        const instrumentType = safeGet(record, ['instrument_type', 'transaction_type', 'InstrumentType']);
        if (instrumentType) {
            const selectElement = document.getElementById('instrument-type');
            if (selectElement) {
                setAutofillValue(selectElement, { type: 'select', event: 'change' }, instrumentType);
            }
        }
    }

    if (!skipFields.has('encumbrance_type')) {
        const encumbranceType = safeGet(record, ['encumbrance_type']);
        if (encumbranceType) {
            const selectElement = document.getElementById('encumbrance-type');
            if (selectElement) {
                setAutofillValue(selectElement, { type: 'select', event: 'change' }, encumbranceType);
            }
        }
    }

    updateRegistrationNumber();
}

/**
 * Save new record to property_records table
 * @returns {Promise} - Promise that resolves when record is saved
 */
async function saveNewRecord() {
    const fileNumber = getFileNumberFromForm();
    
    if (!fileNumber) {
        showToast('Please enter a file number before saving.', 'error');
        return;
    }
    
    // Collect form data
    const formData = {
        file_number: fileNumber,
        location: document.getElementById('location')?.value || '',
        grantor: document.getElementById('grantor')?.value || '',
        grantee: document.getElementById('grantee')?.value || '',
        instrument_type: document.getElementById('instrument-type')?.options[document.getElementById('instrument-type')?.selectedIndex]?.text || '',
        transaction_date: document.getElementById('transaction-date')?.value || null,
        property_description: document.getElementById('location')?.value || ''
    };
    
    console.log('Saving new record:', formData);
    
    try {
        const response = await fetch('/caveat/api/create-property-record', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify(formData)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Save record response:', data);
        
        if (data.success) {
            // Record saved successfully
            window.fileNumberSearchState.fileNumberExists = true;
            window.fileNumberSearchState.normalized = data.normalized;
            window.fileNumberSearchState.sources = data.sources;
            window.fileNumberSearchState.selectedFileData = data.data;
            
            // Show concise confirmation message
            showToast('Record created successfully! You can now place a caveat.', 'success');
            
            // 4. After the user clicks "Save Record" (new record created in property_records)
            // Save Record → Disabled & greyed out again (since it has already been saved)
            // Place Caveat → Enabled, so the user can now place a caveat on the new record
            disableSaveRecordButton();
            enablePlaceCaveatButton();
            
        } else {
            throw new Error(data.error || 'Failed to save record');
        }
        
    } catch (error) {
        console.error('Error saving record:', error);
        
        showToast(error.message || 'Failed to save record. Please try again.', 'error');
    }
}

// Expose function globally so buttons can call it
window.saveNewRecord = saveNewRecord;

/**
 * Get file number from form inputs
 * @returns {string} - The file number
 */
function getFileNumberFromForm() {
    // Check hidden input first
    let fileNumber = document.getElementById('file_number')?.value;
    
    if (!fileNumber) {
        // Check manual input
        fileNumber = document.getElementById('file-number-input')?.value;
    }
    
    if (!fileNumber) {
        // Check selector display
        const fileNumberValue = document.getElementById('file-number-value');
        if (fileNumberValue && !fileNumberValue.textContent.includes('Search and select')) {
            fileNumber = fileNumberValue.textContent.trim();
        }
    }
    
    return fileNumber?.trim() || '';
}

/**
 * Build registration number string from serial, page, and volume numbers
 * @returns {string} - The registration number in format serial/page/volume
 * Note: renamed to avoid colliding with the global DOM-updating
 * `generateRegistrationNumber` defined in `caveat-events.js` which
 * is responsible for updating the UI element. This helper only
 * returns the computed string.
 */
function generateRegistrationString() {
    const serialNo = document.getElementById('serial-no')?.value?.trim() || '';
    const pageNo = document.getElementById('page-no')?.value?.trim() || '';
    const volumeNo = document.getElementById('volume-no')?.value?.trim() || '';

    if (serialNo && volumeNo) {
        const finalPageNo = pageNo || serialNo;
        return `${serialNo}/${finalPageNo}/${volumeNo}`;
    }
    return '';
}

function updateRegistrationNumber() {
    // Update page number to match serial number
    const serialNoInput = document.getElementById('serial-no');
    const pageNoInput = document.getElementById('page-no');
    const serialNo = serialNoInput?.value?.trim() || '';
    
    if (serialNo && pageNoInput) {
        if (window.updatePageNumber) {
            window.updatePageNumber(serialNo);
        } else {
            pageNoInput.value = serialNo;
        }
    }
    
    const registrationDisplay = document.getElementById('registration-number');
    if (registrationDisplay) {
        const regNo = generateRegistrationString();
        if (regNo) {
            registrationDisplay.textContent = regNo;
            registrationDisplay.style.color = '#1f2937';
            registrationDisplay.style.fontWeight = 'bold';
        } else {
            registrationDisplay.textContent = 'Enter Serial No. and Volume No. to generate';
            registrationDisplay.style.color = '#6b7280';
            registrationDisplay.style.fontWeight = 'normal';
        }
    }
}

/**
 * Initialize file number search functionality
 */
function initializeFileNumberSearch() {
    console.log('Initializing file number search functionality');
    
    // Add status container after file number input
    const fileNumberContainer = document.querySelector('#file-number-selector').parentElement;
    if (fileNumberContainer && !document.getElementById('file-number-status')) {
        const statusDiv = document.createElement('div');
        statusDiv.id = 'file-number-status';
        statusDiv.className = 'hidden';
        fileNumberContainer.appendChild(statusDiv);
    }
    
    // Set initial button states as per requirements:
    // 1. Default State (before file search)
    // Save Record → Disabled & greyed out
    // Place Caveat → Disabled & greyed out
    const saveRecordBtn = document.getElementById('save-draft');
    const placeCaveatBtn = document.getElementById('place-caveat');
    
    if (saveRecordBtn) {
        saveRecordBtn.disabled = true;
        saveRecordBtn.className = 'px-4 py-2 border rounded-md flex items-center bg-gray-300 text-gray-600 cursor-not-allowed';
    }
    
    if (placeCaveatBtn) {
        placeCaveatBtn.disabled = true;
        placeCaveatBtn.className = 'px-4 py-2 bg-gray-300 text-gray-600 rounded-md flex items-center cursor-not-allowed';
    }
    
    // Add event listeners for file number changes
    const fileNumberInput = document.getElementById('file-number-input');
    if (fileNumberInput) {
        fileNumberInput.addEventListener('blur', handleFileNumberChange);
        fileNumberInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                handleFileNumberChange();
            }
        });
    }
    
    // Add event listener for save record button
    if (saveRecordBtn) {
        saveRecordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (!saveRecordBtn.disabled) {
                saveNewRecord();
            }
        });
    }
    
    renderAutofillSummary([], null);

    console.log('File number search functionality initialized');
}

/**
 * Handle file number change event
 */
async function handleFileNumberChange() {
    const fileNumber = getFileNumberFromForm();
    
    if (fileNumber) {
        console.log('File number changed:', fileNumber);
        
        // Update hidden input
        const hiddenInput = document.getElementById('file_number');
        if (hiddenInput) {
            hiddenInput.value = fileNumber;
        }
        
        // Search for file number
        await searchFileNumberInTables(fileNumber);
    } else {
        // Reset button states if no file number - back to default state
        window.fileNumberSearchState.fileNumberExists = false;
        window.fileNumberSearchState.selectedFileData = null;
        
        const saveRecordBtn = document.getElementById('save-draft');
        const placeCaveatBtn = document.getElementById('place-caveat');
        
        // 1. Default State (before file search)
        // Save Record → Disabled & greyed out
        // Place Caveat → Disabled & greyed out
        if (saveRecordBtn) {
            saveRecordBtn.disabled = true;
            saveRecordBtn.className = 'px-4 py-2 border rounded-md flex items-center bg-gray-300 text-gray-600 cursor-not-allowed';
        }
        
        if (placeCaveatBtn) {
            placeCaveatBtn.disabled = true;
            placeCaveatBtn.className = 'px-4 py-2 bg-gray-300 text-gray-600 rounded-md flex items-center cursor-not-allowed';
        }
        
        // Hide status
        const statusContainer = document.getElementById('file-number-status');
        if (statusContainer) {
            statusContainer.classList.add('hidden');
        }

        renderAutofillSummary([], null);
        resetAutofillFlags();
    }
}

/**
 * Update Current Selection Preview
 * @param {string} fileNumber - The file number
 * @param {boolean} found - Whether the record was found
 * @param {string} table - The table where the record was found
 */
function updateCurrentSelectionPreview(fileNumber, found, table) {
    const previewContainer = document.getElementById('current-selection-preview');
    const previewFileNumber = document.getElementById('preview-file-number');
    
    if (previewContainer && previewFileNumber) {
        // For manual-created file numbers, display without leading zeros in the serial
        try {
            previewFileNumber.textContent = removeLeadingZerosFromFileNumber(fileNumber);
        } catch (e) {
            previewFileNumber.textContent = fileNumber;
        }
        
        previewContainer.classList.remove('hidden');
        console.log('Updated current selection preview:', fileNumber);
    }
}

/**
 * Remove leading zeros from the serial number part of a file number
 * @param {string} fileNumber - The file number to clean
 * @returns {string} - The cleaned file number without leading zeros
 */
function removeLeadingZerosFromFileNumber(fileNumber) {
    if (!fileNumber) return fileNumber;
    
    // Handle different file number patterns
    // Pattern: CON-RES-2025-0003 -> CON-RES-2025-3
    // Pattern: RES-2025-0456 -> RES-2025-456
    // Pattern: COM-2016-0208 -> COM-2016-208
    
    // Split by dashes and process the last part (serial number)
    const parts = fileNumber.split('-');
    if (parts.length >= 2) {
        // Get the last part (serial number)
        const lastPart = parts[parts.length - 1];
        
        // Remove leading zeros but keep at least one digit
        const cleanedLastPart = lastPart.replace(/^0+/, '') || '0';
        
        // Reconstruct the file number
        parts[parts.length - 1] = cleanedLastPart;
        return parts.join('-');
    }
    
    return fileNumber;
}

// Export functions for global access
window.searchFileNumberInTables = searchFileNumberInTables;
window.updateButtonStates = updateButtonStates;
window.saveNewRecord = saveNewRecord;
window.getFileNumberFromForm = getFileNumberFromForm;
window.initializeFileNumberSearch = initializeFileNumberSearch;
window.handleFileNumberChange = handleFileNumberChange;
window.enablePlaceCaveatButton = enablePlaceCaveatButton;
window.disablePlaceCaveatButton = disablePlaceCaveatButton;
window.enableSaveRecordButton = enableSaveRecordButton;
window.disableSaveRecordButton = disableSaveRecordButton;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initializeFileNumberSearch();
    initializeAutofillTracking();
});