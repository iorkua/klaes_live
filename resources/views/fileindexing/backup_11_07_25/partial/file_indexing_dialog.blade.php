<!-- New FileIndexDialog -->
<div class="dialog-overlay hidden" id="new-file-dialog-overlay">
    <div class="dialog">
        <div class="dialog-header">
            <div class="dialog-title">
                <i data-lucide="file-plus" class="h-5 w-5"></i>
                Create New File Index
            </div>
            <button id="close-dialog-btn" class="text-white" style="background: none; border: none; cursor: pointer;">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <div class="dialog-description">
            Enter the details for the new file to be indexed
        </div>
        <div class="dialog-content">
            <form id="new-file-form">
                <!-- File Identification Section -->
                <div class="form-section">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="form-section-title" style="margin-bottom: 0;">File Identification</h3>
                        <div class="tracking-id-container" style="text-align: right;">
                       
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-700">Tracking ID</label>
                                <div id="grouping-loading-indicator" class="hidden flex items-center justify-end gap-2 text-sm text-blue-600">
                                    <span class="inline-flex h-4 w-4 items-center justify-center">
                                        <span class="loading-spinner"></span>
                                    </span>
                                    <span>Fetching grouping record…</span>
                                </div>
                                <input type="text" id="tracking-id" 
                                       value=""
                                       class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 font-mono text-base font-bold text-red-600"
                                       readonly placeholder="Will be loaded from grouping record">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="file-number-display" class="form-label required">File Number</label>
                        
                        <div class="flex">
                            <!-- Disabled input field for displaying selected File Number -->
                            <input type="text" id="file-number-display" class="input flex-grow mr-2" readonly style="background-color: #f3f4f6; color: #6b7280;">
                            
                            <!-- Hidden input that will actually be submitted -->
                            <input type="hidden" id="fileno" name="fileno" value="">
                            <input type="hidden" id="grouping-id" name="grouping_id" value="">
                            
                            <!-- Button to open the global File Number modal -->
                            <button type="button" id="select-file-number-btn" class="btn-primary px-4" style="white-space: nowrap;">
                                Select
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="form-group flex-1 min-w-[240px]">
                            <label for="file-title" class="form-label required">File Title</label>
                            <input type="text" id="file-title" class="input">
                        </div>
                        <div class="form-group flex-1 min-w-[240px]">
                            <label for="related-file-number-display" class="form-label">Related File Number</label>
                            <div class="flex">
                                <input type="text" id="related-file-number-display" class="input flex-grow mr-2" readonly style="background-color: #f3f4f6; color: #6b7280;">
                                <input type="hidden" id="related-fileno" name="related_fileno" value="">
                                <button type="button" id="select-related-file-number-btn" class="btn-primary px-4" style="white-space: nowrap;">
                                    Select
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Property Details Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Property Details</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Land Use Type</label>
                            <select class="input" id="land-use-type">
                                <option value="">Select Land Use Type</option>
        <option value="AGRICULTURAL">AGRICULTURAL</option>
        <option value="COMMERCIAL">COMMERCIAL</option>
        <option value="COMMERCIAL ( WARE HOUSE)">COMMERCIAL ( WARE HOUSE)</option>
        <option value="COMMERCIAL (OFFICES)">COMMERCIAL (OFFICES)</option>
        <option value="COMMERCIAL (PETROL FILLING STATION)">COMMERCIAL (PETROL FILLING STATION)</option>
        <option value="COMMERCIAL (RICE PROCESSING)">COMMERCIAL (RICE PROCESSING)</option>
        <option value="COMMERCIAL (SCHOOL)">COMMERCIAL (SCHOOL)</option>
        <option value="COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)">COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)</option>
        <option value="COMMERCIAL (SHOPS AND OFFICES)">COMMERCIAL (SHOPS AND OFFICES)</option>
        <option value="COMMERCIAL (SHOPS)">COMMERCIAL (SHOPS)</option>
        <option value="COMMERCIAL (WAREHOUSE)">COMMERCIAL (WAREHOUSE)</option>
        <option value="COMMERCIAL (WORKSHOP AND OFFICES)">COMMERCIAL (WORKSHOP AND OFFICES)</option>
        <option value="COMMERCIAL AND RESIDENTIAL">COMMERCIAL AND RESIDENTIAL</option>
        <option value="INDUSTRIAL">INDUSTRIAL</option>
        <option value="INDUSTRIAL (SMALL SCALE)">INDUSTRIAL (SMALL SCALE)</option>
        <option value="RESIDENTIAL">RESIDENTIAL</option>
        <option value="RESIDENTIAL AND COMMERCIAL">RESIDENTIAL AND COMMERCIAL</option>
        <option value="RESIDENTIAL/COMMERCIAL">RESIDENTIAL/COMMERCIAL</option>
        <option value="RESIDENTIAL/COMMERCIAL LAYOUT">RESIDENTIAL/COMMERCIAL LAYOUT</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plot Number</label>
                            <input type="text" id="plot-number" class="input">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">TP Number</label>
                            <input type="text" id="tp-number" class="input">
                        </div>
                        <div class="form-group">
                            <label class="form-label">LPKN Number</label>
                            <input type="text" id="lpkn-no" class="input">
                        </div>
                    </div>
                    
                  
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">District</label>
                            <select class="input" id="district-select">
                                <option value="">Loading districts...</option>
                            </select>
                            <!-- Added custom district input field that shows when "Other" is selected -->
                            <div id="custom-district-container" class="hidden" style="margin-top: 0.5rem;">
                                <input type="text" id="custom-district-input" class="input" placeholder="Enter district name" style="font-size: 0.875rem;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">LGA</label>
                            <select id="lga-city" class="input">
                                <option value="">Loading LGAs...</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Location and Plot Size -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Location</label>
                            <input type="text" id="location" class="input" readonly placeholder="Auto-generated from Plot Number, District, LGA" style="background-color: #f9fafb; cursor: default;">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Plot Size</label>
                            <input type="text" id="plot-size" name="plot_size" class="input">
                        </div>
                    </div>
                </div>
                
                <!-- File Properties Section -->
                <div class="form-section">
                    <!-- <h3 class="form-section-title">File Properties</h3> -->
                    
                    <div class="hidden">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <div class="form-checkbox">
                                    <input type="checkbox" id="has-cofo">
                                    <label for="has-cofo">Has Certificate of Occupancy</label>
                                </div>
                                <div class="form-checkbox">
                                    <input type="checkbox" id="has-transaction">
                                    <label for="has-transaction">Has Transaction</label>
                                </div>
                                <!-- <div class="form-checkbox">
                                    <input type="checkbox" id="is-problematic">
                                    <label for="is-problematic">Problematic File</label>
                                </div> -->
                            </div>
                            <div>
                                <div class="form-checkbox">
                                    <input type="checkbox" id="co-owned-plot">
                                    <label for="co-owned-plot">Co-Owned Plot</label>
                                </div>
                                <div class="form-checkbox">
                                    <input type="checkbox" id="merged-plot">
                                    <label for="merged-plot">Merged Plot</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- File Archive Details Section -->
                <div class="form-section">
                    <h3 class="form-section-title">File Archive Details</h3>
                    
                    <!-- File No and Awaiting file no -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="archive-file-no" class="form-label">File No</label>
                            <input type="text" id="archive-file-no" class="input bg-gray-50" readonly>
                        </div>
                        <div class="form-group">
                            <label for="awaiting-file-no" class="form-label">Awaiting file no</label>
                            <div class="flex">
                                <input type="text" id="awaiting-file-no" class="input flex-grow mr-2">
                                <button type="button" id="refresh-archive-details-btn" class="px-3 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 text-xs" title="Refresh archive details from API">
                                    <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- MDC Batch No, Group No, Batch No -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label for="mdc-batch-no" class="form-label">MDC Batch No</label>
                            <input type="text" id="mdc-batch-no" class="input">
                        </div>
                        <div class="form-group">
                            <label for="group-no" class="form-label">Group No</label>
                            <input type="text" id="group-no" class="input">
                        </div>
                         <div class="form-group">
                            <label for="sys-batch-no" class="form-label">SYS Batch No</label>
                            <input type="text" id="sys-batch-no" class="input bg-gray-50" readonly>
                        </div>
                    </div>
                    
                    <!-- Physical Registry, Registry, Shelf/Rack No, Serial No -->
                    <div class="grid grid-cols-4 gap-4">
                       

                        <div class="form-group">
                            <label for="physical-registry" class="form-label">Physical Registry</label>
                            <select id="physical-registry" class="input" name="physical_registry">
                                <option value="">Select Registry</option>
                                <option value="Registry 1 - Lands">Registry 1 - Lands</option>
                                <option value="Registry 2 - Lands">Registry 2 - Lands</option>
                                <option value="Registry 3 - Lands">Registry 3 - Lands</option>
                                <option value="Registry 1 - Deeds">Registry 1 - Deeds</option>
                                <option value="Registry 2 - Deeds">Registry 2 - Deeds</option>
                                <option value="Registry 1 - Cadastral">Registry 1 - Cadastral</option>
                                <option value="Registry 2 - Cadastral">Registry 2 - Cadastral</option>
                                <option value="KANGIS Registry">KANGIS Registry</option>
                                <option value="SLTR Registry">SLTR Registry</option>
                                <option value="ST Registry">ST Registry</option>
                                <option value="DCIV Registry">DCIV Registry</option>
                                <option value="New Archive">New Archive</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="registry" class="form-label">Registry</label>
                            <input type="text" id="registry" class="input bg-gray-50" readonly>
                        </div>

                        <div class="form-group">
                            <label for="shelf-rack-no" class="form-label">Shelf/Rack No</label>
                            <input type="text" id="shelf-rack-no" class="input bg-gray-50" readonly>
                        </div>
                         <div class="form-group col-span-2 md:col-span-1">
                            <label for="serial-no" class="form-label">Serial No</label>
                            <input type="text" id="serial-no" class="input bg-gray-50" readonly>
                        </div>
                    </div>

                    
                    <!-- Indexed by, Indexed Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="indexed-by" class="form-label">Indexed by</label>
                            <input type="text" id="indexed-by" class="input bg-gray-50" readonly>
                        </div>
                        <div class="form-group">
                            <label for="indexed-date" class="form-label">Indexed Date</label>
                            <input type="date" id="indexed-date" class="input bg-gray-50" readonly>
                        </div>
                    </div>
                    
                   
                </div>
                
                <!-- CofO Details Section -->
                <div class="form-section">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="form-section-title">Certificate of Occupancy (CofO) Details</h3>
                        <div class="form-checkbox">
                            <input type="checkbox" id="has-cofo-toggle" class="mr-2">
                            <label for="has-cofo-toggle" class="form-label font-medium">Has CofO</label>
                        </div>
                    </div>
                    <div id="cofo-autofill-status" style="display:none;" class="text-sm text-blue-600 mb-3 text-right">
                        <span class="cofo-status-content">
                            <span class="cofo-status-icon"></span>
                            <span class="cofo-status-text"></span>
                        </span>
                    </div>
                    
                    <div id="cofo-details-container" class="hidden">
                        <!-- Instrument Type and CofO Date -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="cofo-instrument-type" class="form-label">Instrument Type</label>
                                <select id="cofo-instrument-type" class="input">
                                    <option value="">Select Instrument Type</option>
                                    <option value="Certificate of Occupancy" selected>Certificate of Occupancy</option>
                                    <option value="ST Certificate of Occupancy">ST Certificate of Occupancy</option>
                                    <option value="SLTR Certificate of Occupancy">SLTR Certificate of Occupancy</option>
                                    <option value="Customary Right of Occupancy">Customary Right of Occupancy</option>
                                    <option value="Deed of Transfer">Deed of Transfer</option>
                                    <option value="Deed of Assignment">Deed of Assignment</option>
                                    <option value="ST Assignment">ST Assignment</option>
                                    <option value="Deed of Mortgage">Deed of Mortgage</option>
                                    <option value="Tripartite Mortgage">Tripartite Mortgage</option>
                                    <option value="Deed of Sub Lease">Deed of Sub Lease</option>
                                    <option value="Deed of Sub Under Lease">Deed of Sub Under Lease</option>
                                    <option value="Power of Attorney">Power of Attorney</option>
                                    <option value="Irrevocable Power of Attorney">Irrevocable Power of Attorney</option>
                                    <option value="Conveyance">Conveyance</option>
                                    <option value="Deed of Gift">Deed of Gift</option>
                                    <option value="Court Affidavit">Court Affidavit</option>
                                    <option value="Consent Judgment">Consent Judgment</option>
                                    <option value="Right of Occupancy">Right of Occupancy</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="cofo-date" class="form-label">CofO Date</label>
                                <input type="date" id="cofo-date" class="input">
                            </div>
                        </div>
                        
                        <!-- Serial No, Page No, Vol No -->
                        <div class="grid grid-cols-3 gap-4">
                            <div class="form-group">
                                <label for="cofo-serial-no" class="form-label">Serial No</label>
                                <input type="text" id="cofo-serial-no" class="input">
                            </div>
                            <div class="form-group">
                                <label for="cofo-page-no" class="form-label">Page No</label>
                                <input type="text" id="cofo-page-no" class="input bg-gray-50" readonly style="background-color: #f9fafb; cursor: default;">
                            </div>
                            <div class="form-group">
                                <label for="cofo-vol-no" class="form-label">Vol No</label>
                                <input type="text" id="cofo-vol-no" class="input">
                            </div>
                        </div>
                        
                        <!-- Deeds Time and Deeds Date -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="cofo-deeds-time" class="form-label">Deeds Time</label>
                                <input type="time" id="cofo-deeds-time" class="input">
                            </div>
                            <div class="form-group">
                                <label for="cofo-deeds-date" class="form-label">Deeds Date</label>
                                <input type="date" id="cofo-deeds-date" class="input">
                            </div>
                        </div>
                        
                        <!-- Transaction Details Section (appears when instrument type is selected) -->
                        <div id="cofo-transaction-details" class="hidden mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
                            <h5 class="text-sm font-semibold text-blue-900 mb-3">Transaction Details</h5>
                            
                            <!-- Party Fields -->
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label for="cofo-first-party" class="form-label" id="cofo-first-party-label">Grantor</label>
                                    <input type="text" id="cofo-first-party" class="input">
                                </div>
                                <div class="form-group">
                                    <label for="cofo-second-party" class="form-label" id="cofo-second-party-label">Grantee</label>
                                    <input type="text" id="cofo-second-party" class="input" style="text-transform: uppercase;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button type="button" class="btn" id="cancel-btn">Cancel</button>
                    <button type="button" class="btn btn-blue" id="create-file-btn">Create File Index</button>
                </div>
                <br>
                <hrr>
            </form>
        </div>
    </div>


<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
/* Custom Select2 styling for batch selection */
.select2-container--default .select2-selection--single {
    height: 42px !important;
    border: 1px solid #d1d5db !important;
    border-radius: 0.375rem !important;
    padding: 0 12px !important;
    display: flex !important;
    align-items: center !important;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #374151 !important;
    line-height: 42px !important;
    padding-left: 0 !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px !important;
    right: 8px !important;
}

.select2-dropdown {
    border: 1px solid #d1d5db !important;
    border-radius: 0.375rem !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
}

.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6 !important;
    color: white !important;
}

.select2-container--default .select2-results__option[aria-selected=true] {
    background-color: #eff6ff !important;
    color: #1d4ed8 !important;
}

.cofo-status-content {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.cofo-status-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    font-size: 0.75rem;
}

.loading-spinner {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(37, 99, 235, 0.3);
    border-top-color: rgba(37, 99, 235, 0.9);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.autofill-locked {
    background-color: #fdf2f8 !important;
    color: #b91c1c !important;
    border-color: #f87171 !important;
    cursor: not-allowed !important;
}

.autofill-locked::placeholder {
    color: #fca5a5 !important;
}

select.autofill-locked {
    pointer-events: none !important;
}

input.autofill-locked:focus,
select.autofill-locked:focus,
textarea.autofill-locked:focus {
    outline: none !important;
    box-shadow: none !important;
}

/* Shelf location input styling */
#shelf-location {
    transition: all 0.2s ease-in-out;
}

#shelf-location:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Loading state for shelf input */
#shelf-location.loading {
    background-image: url("data:image/svg+xml,%3csvg width='20' height='20' viewBox='0 0 20 20' fill='none' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M10 3V6M10 14V17M17 10H14M6 10H3M15.364 4.636L13.536 6.464M6.464 13.536L4.636 15.364M15.364 15.364L13.536 13.536M6.464 6.464L4.636 4.636' stroke='%236b7280' stroke-width='2' stroke-linecap='round'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Success state styling */
.success-border {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
}

/* Error state styling */
.error-border {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

/* Select2 loading and result states */
.select2-results__message {
    color: #6b7280 !important;
    font-style: italic !important;
    padding: 8px 12px !important;
}

.select2-results__option.loading-results {
    color: #6b7280 !important;
    font-style: italic !important;
}

/* Batch selection feedback */
.batch-selection-feedback {
    margin-top: 0.25rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.batch-selection-feedback.success {
    color: #10b981;
}

.batch-selection-feedback.error {
    color: #ef4444;
}
</style>

<script>
/**
 * File Indexing Dialog with Dynamic Batch Selection
 * 
 * Features:
 * - Uses Rack_Shelf_Labels table for batch management
 * - Select2 with pagination (5 records at a time) and search
 * - Automatic shelf location population from full_label column
 * - Marks batches as used (is_used = 1) when files are created
 * - Real-time validation and error handling
 */

// Note: Tracking ID is now loaded from grouping records instead of being generated locally

const referenceDataStore = {
    lgas: [],
    districts: [],
    isLoading: false,
    isLoaded: false,
    loadPromise: null,
};

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const error = new Error(`Request to ${url} failed with status ${response.status}`);
        error.status = response.status;
        throw error;
    }

    return response.json();
}

async function loadReferenceData(force = false) {
    if (referenceDataStore.isLoaded && !force) {
        return {
            lgas: referenceDataStore.lgas,
            districts: referenceDataStore.districts,
        };
    }

    if (referenceDataStore.loadPromise) {
        return referenceDataStore.loadPromise;
    }

    const districtSelect = document.getElementById('district-select');
    const lgaSelect = document.getElementById('lga-city');

    referenceDataStore.isLoading = true;

    const loadTask = (async () => {
        try {
            const [lgasResponse, districtsResponse] = await Promise.all([
                fetchJson('/api/reference/lgas'),
                fetchJson('/api/reference/districts'),
            ]);

            referenceDataStore.lgas = Array.isArray(lgasResponse?.data) ? lgasResponse.data : [];
            referenceDataStore.districts = Array.isArray(districtsResponse?.data) ? districtsResponse.data : [];
            referenceDataStore.isLoaded = true;

            populateLgaSelect(referenceDataStore.lgas);
            populateDistrictSelect(referenceDataStore.districts);

            return {
                lgas: referenceDataStore.lgas,
                districts: referenceDataStore.districts,
            };
        } catch (error) {
            console.error('Failed to load reference data', error);
            referenceDataStore.isLoaded = false;

            if (lgaSelect) {
                lgaSelect.innerHTML = '<option value="">Unable to load LGAs</option>';
            }
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Unable to load districts</option>';
            }

            throw error;
        } finally {
            referenceDataStore.isLoading = false;
            referenceDataStore.loadPromise = null;
        }
    })();

    referenceDataStore.loadPromise = loadTask;
    return loadTask;
}

function populateLgaSelect(lgas, { preserveSelection = false } = {}) {
    const lgaSelect = document.getElementById('lga-city');
    if (!lgaSelect) {
        return;
    }

    const previousValue = preserveSelection ? lgaSelect.value : '';

    lgaSelect.innerHTML = '<option value="">Select LGA</option>';

    lgas.forEach((lga) => {
        const option = document.createElement('option');
        option.value = String(lga.id);
        option.textContent = lga.name;
        option.dataset.name = lga.name;
        option.dataset.code = lga.code || '';
        option.dataset.slug = lga.slug || '';
        lgaSelect.appendChild(option);
    });

    if (preserveSelection && previousValue) {
        const restored = Array.from(lgaSelect.options).some((option) => option.value === previousValue);
        if (restored) {
            lgaSelect.value = previousValue;
        }
    }
}

function populateDistrictSelect(districts, { preserveSelection = false } = {}) {
    const districtSelect = document.getElementById('district-select');
    if (!districtSelect) {
        return;
    }

    const previousValue = preserveSelection ? districtSelect.value : '';

    districtSelect.innerHTML = '<option value="">Select District</option>';

    districts.forEach((district) => {
        const option = document.createElement('option');
        option.value = String(district.id);
        option.textContent = district.name;
        option.dataset.name = district.name;

        option.dataset.slug = district.slug || '';
        districtSelect.appendChild(option);
    });

    const otherOption = document.createElement('option');
    otherOption.value = 'other';
    otherOption.textContent = 'Other';
    districtSelect.appendChild(otherOption);

    if (preserveSelection && previousValue) {
        const restored = Array.from(districtSelect.options).some((option) => option.value === previousValue);
        if (restored) {
            districtSelect.value = previousValue;
        }
    }
}

// Districts are now independent - no filtering needed

function getSelectedOptionName(selectElement) {
    if (!selectElement) {
        return '';
    }

    const option = selectElement.selectedOptions && selectElement.selectedOptions[0];
    if (!option) {
        return '';
    }

    return option.dataset?.name || option.textContent || option.value || '';
}

function getSelectedOptionId(selectElement) {
    if (!selectElement) {
        return null;
    }

    const option = selectElement.selectedOptions && selectElement.selectedOptions[0];
    if (!option || option.value === '' || option.value === 'other') {
        return null;
    }

    const id = Number(option.value);
    return Number.isNaN(id) ? null : id;
}

window.loadReferenceData = loadReferenceData;
window.referenceDataStore = referenceDataStore;

const groupingState = {
    record: null,
    normalizedAwaiting: null,
    mismatch: true,
};
    
const autofillState = {
    groupedIndexed: false,
    cofo: false
};

const cofoAutofillState = {
    container: null,
    icon: null,
    text: null,
    lastRequestId: 0,
};

function generateTrackingIdSegment(length) {
    const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    let segment = '';

    for (let i = 0; i < length; i += 1) {
        const index = Math.floor(Math.random() * characters.length);
        segment += characters[index];
    }

    return segment;
}

function generateTrackingId() {
    return `TRK-${generateTrackingIdSegment(8)}-${generateTrackingIdSegment(5)}`;
}

function ensureTrackingIdExists() {
    const input = document.getElementById('tracking-id');
    if (!input) {
        return null;
    }

    let value = (input.value || '').trim();

    if (!value) {
        value = generateTrackingId();
    }

    setAutoFilledValue(input, value);

    return value;
}

window.generateTrackingId = generateTrackingId;
window.ensureTrackingIdExists = ensureTrackingIdExists;

function getCofoAutofillElements() {
    if (!cofoAutofillState.container) {
        const container = document.getElementById('cofo-autofill-status');
        if (!container) {
            return null;
        }

        cofoAutofillState.container = container;
        cofoAutofillState.icon = container.querySelector('.cofo-status-icon');
        cofoAutofillState.text = container.querySelector('.cofo-status-text');
    }

    return cofoAutofillState.container;
}

function setCofoAutofillStatus(type, message) {
    const container = getCofoAutofillElements();
    if (!container) {
        return;
    }

    const icon = cofoAutofillState.icon;
    const text = cofoAutofillState.text;

    const resetIcon = () => {
        if (icon) {
            icon.classList.remove('loading-spinner');
            icon.textContent = '';
        }
    };

    container.classList.remove('text-blue-600', 'text-green-600', 'text-red-600', 'text-gray-600');

    if (!message) {
        container.style.display = 'none';
        resetIcon();
        if (text) {
            text.textContent = '';
        }
        return;
    }

    container.style.display = 'block';

    let colorClass = 'text-blue-600';
    if (type === 'success') {
        colorClass = 'text-green-600';
    } else if (type === 'error') {
        colorClass = 'text-red-600';
    } else if (type === 'info') {
        colorClass = 'text-gray-600';
    }
    container.classList.add(colorClass);

    resetIcon();

    if (icon) {
        if (type === 'loading') {
            icon.classList.add('loading-spinner');
        } else if (type === 'success') {
            icon.textContent = 'OK';
        } else if (type === 'error') {
            icon.textContent = '!';
        } else if (type === 'info') {
            icon.textContent = 'i';
        }
    }

    if (text) {
        text.textContent = message;
    }
}

function applyAutofillLock(fieldIds, engaged) {
    const cssClass = 'autofill-locked';

    fieldIds.forEach(id => {
        const element = document.getElementById(id);
        if (!element) {
            return;
        }

        const rawValue = element.value ?? '';
        const trimmedValue = typeof rawValue === 'string' ? rawValue.trim() : String(rawValue).trim();
        const shouldLock = engaged && trimmedValue !== '';

        const restoreOriginalState = () => {
            element.removeAttribute('data-autofill-locked');
            delete element.dataset.autofillLockedValue;
            element.classList.remove(cssClass);

            if (typeof element.readOnly === 'boolean') {
                const originalReadonly = element.dataset.originalReadonly === 'true';
                element.readOnly = originalReadonly;
                if (!originalReadonly) {
                    element.removeAttribute('readonly');
                }
            }

            const originalDisabled = element.dataset.originalDisabled === 'true';
            element.disabled = originalDisabled;
            if (!originalDisabled) {
                element.removeAttribute('disabled');
            }
        };

        if (!Object.prototype.hasOwnProperty.call(element.dataset, 'originalReadonly')) {
            element.dataset.originalReadonly = element.readOnly ? 'true' : 'false';
        }

        if (!Object.prototype.hasOwnProperty.call(element.dataset, 'originalDisabled')) {
            element.dataset.originalDisabled = element.disabled ? 'true' : 'false';
        }

        if (shouldLock) {
            element.setAttribute('data-autofill-locked', 'true');
            element.dataset.autofillLockedValue = element.value ?? '';
            element.classList.add(cssClass);

            if (typeof element.readOnly === 'boolean') {
                element.readOnly = true;
            }

            if (element.tagName === 'SELECT' || element.type === 'date' || element.type === 'time') {
                element.disabled = true;
            }

            element.blur();
            return;
        } else {
            restoreOriginalState();
        }
    });
}

function isAutofillLocked(element) {
    return element?.getAttribute('data-autofill-locked') === 'true';
}

function setAutoFilledValue(target, value, options = {}) {
    const element = typeof target === 'string' ? document.getElementById(target) : target;
    if (!element) {
        return;
    }

    const { lock = true } = options;

    let resolved = value;
    if (resolved === null || typeof resolved === 'undefined') {
        resolved = '';
    }

    const stringValue = typeof resolved === 'string' ? resolved : String(resolved);

    if ('value' in element) {
        element.value = stringValue;
    } else if ('textContent' in element) {
        element.textContent = stringValue;
    }

    if (!element.id) {
        return;
    }

    if (!lock) {
        applyAutofillLock([element.id], false);
        return;
    }

    applyAutofillLock([element.id], true);
}

window.setAutoFilledValue = setAutoFilledValue;

async function autoFillCofODetailsFromAPI(fileNumber) {
    const trimmed = (fileNumber || '').trim();

    if (trimmed === '') {
        cofoAutofillState.lastRequestId += 1;
        setCofoAutofillStatus(null, '');
        return null;
    }

    const requestId = cofoAutofillState.lastRequestId + 1;
    cofoAutofillState.lastRequestId = requestId;

    const applyStatus = (type, message) => {
        if (cofoAutofillState.lastRequestId === requestId) {
            setCofoAutofillStatus(type, message);
        }
    };

    applyStatus('loading', `Looking up CofO record for ${trimmed}...`);

    try {
        const response = await fetch(`/api/cofo-record/${encodeURIComponent(trimmed)}`);
        let data = {};

        try {
            data = await response.json();
        } catch (parseError) {
            data = {};
        }

        if (!response.ok || !data.success || !data.data) {
            const message = data.message || `No CofO record found for ${trimmed}.`;
            applyStatus('info', message);
            return null;
        }

        const fields = data.data;

        if (cofoAutofillState.lastRequestId !== requestId) {
            return null;
        }

        const hasCofoToggle = document.getElementById('has-cofo-toggle');
        const cofoDetailsContainer = document.getElementById('cofo-details-container');
        const hiddenHasCofo = document.getElementById('has-cofo');

        if (hiddenHasCofo) {
            hiddenHasCofo.checked = true;
        }

        if (hasCofoToggle) {
            if (!hasCofoToggle.checked) {
                hasCofoToggle.checked = true;
                hasCofoToggle.dispatchEvent(new Event('change'));
            } else if (cofoDetailsContainer) {
                cofoDetailsContainer.classList.remove('hidden');
            }
        }

        clearCofoFields();

        const instrumentTypeSelect = document.getElementById('cofo-instrument-type');
        if (instrumentTypeSelect) {
            const availableValues = Array.from(instrumentTypeSelect.options).map(option => option.value);
            if (fields.cofo_type && availableValues.includes(fields.cofo_type)) {
                instrumentTypeSelect.value = fields.cofo_type;
            } else {
                instrumentTypeSelect.value = 'Certificate of Occupancy';
            }
            instrumentTypeSelect.dispatchEvent(new Event('change'));
        }

        const cofoDate = document.getElementById('cofo-date');
        if (cofoDate && fields.certificate_date) {
            cofoDate.value = fields.certificate_date;
        }

        const cofoSerial = document.getElementById('cofo-serial-no');
        if (cofoSerial && fields.serial_no) {
            cofoSerial.value = fields.serial_no;
        }

        const cofoPage = document.getElementById('cofo-page-no');
        if (cofoPage && (fields.page_no || fields.serial_no)) {
            cofoPage.value = fields.page_no || fields.serial_no;
        }

        const cofoVolume = document.getElementById('cofo-vol-no');
        if (cofoVolume && fields.volume_no) {
            cofoVolume.value = fields.volume_no;
        }

        const cofoDeedsDate = document.getElementById('cofo-deeds-date');
        if (cofoDeedsDate && fields.transaction_date) {
            cofoDeedsDate.value = fields.transaction_date;
        }

        const cofoDeedsTime = document.getElementById('cofo-deeds-time');
        if (cofoDeedsTime && fields.transaction_time) {
            cofoDeedsTime.value = fields.transaction_time;
        }

        const cofoTransactionDetails = document.getElementById('cofo-transaction-details');
        if (cofoTransactionDetails && (fields.grantor || fields.grantee)) {
            cofoTransactionDetails.classList.remove('hidden');
        }

        const cofoFirstPartyInput = document.getElementById('cofo-first-party');
        if (cofoFirstPartyInput && fields.grantor) {
            cofoFirstPartyInput.value = fields.grantor;
        }

        const cofoSecondPartyInput = document.getElementById('cofo-second-party');
        if (cofoSecondPartyInput && fields.grantee) {
            cofoSecondPartyInput.value = fields.grantee;
        }

        applyAutofillLock([
            'cofo-instrument-type',
            'cofo-date',
            'cofo-serial-no',
            'cofo-page-no',
            'cofo-vol-no',
            'cofo-deeds-time',
            'cofo-deeds-date',
            'cofo-first-party',
            'cofo-second-party'
        ], true);

        autofillState.cofo = true;
        applyStatus('success', `CofO details loaded for ${trimmed}.`);

        return fields;
    } catch (error) {
        console.error('CofO lookup failed', error);
        applyStatus('error', 'Failed to load CofO details. Please try again.');
        return null;
    }
}

window.autoFillCofODetailsFromAPI = autoFillCofODetailsFromAPI;

const ARCHIVE_FIELD_IDS = [
    'awaiting-file-no',
    'group-no',
    'batch-no-field',
    'registry',
    'shelf-rack-no',
    'sys-batch-no',
    'serial-no'
];

function normalizeFileno(value) {
    if (!value && value !== 0) {
        return '';
    }
    return String(value)
        .trim()
        .toUpperCase()
        .replace(/[\s\-\/\\.,]/g, '');
}

function resetGroupingState() {
    groupingState.record = null;
    groupingState.normalizedAwaiting = null;
    groupingState.mismatch = true;

    const groupingIdInput = document.getElementById('grouping-id');
    if (groupingIdInput) {
        groupingIdInput.value = '';
    }
}

function setGroupingRecord(record) {
    groupingState.record = record || null;
    groupingState.normalizedAwaiting = record ? normalizeFileno(record.awaiting_fileno) : null;
    groupingState.mismatch = false;

    const groupingIdInput = document.getElementById('grouping-id');
    if (groupingIdInput) {
        groupingIdInput.value = record?.id ?? '';
    }
}

function updateCreateButtonState() {
    const createBtn = document.getElementById('create-file-btn');
    if (!createBtn) {
        return;
    }

    const canSubmit = Boolean(groupingState.record) && !groupingState.mismatch;

    if (canSubmit) {
        createBtn.disabled = false;
        createBtn.classList.remove('opacity-60', 'cursor-not-allowed');
    } else {
        createBtn.disabled = true;
        if (!createBtn.classList.contains('opacity-60')) {
            createBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }
    }
}

function getArchiveFieldElements() {
    return ARCHIVE_FIELD_IDS
        .map(id => document.getElementById(id))
        .filter(Boolean);
}

function lockArchiveField(element, value) {
    if (!element) {
        return;
    }

    const resolvedValue = value ?? '';
    const stringValue = typeof resolvedValue === 'string' ? resolvedValue : String(resolvedValue);
    const hasContent = stringValue.trim() !== '';

    element.value = stringValue;

    if (!hasContent) {
        element.readOnly = false;
        element.removeAttribute('readonly');
        element.classList.remove('bg-gray-50', 'autofill-locked', 'error-border');
        element.style.removeProperty('color');
        element.removeAttribute('data-autofill-locked');

        if (element.id === 'awaiting-file-no') {
            if (element.dataset) {
                delete element.dataset.locked;
                delete element.dataset.lockedValue;
            }
        }

        if (element.dataset) {
            delete element.dataset.autofillLockedValue;
        }
        return;
    }

    element.readOnly = true;
    element.setAttribute('readonly', 'readonly');
    element.setAttribute('data-autofill-locked', 'true');
    element.classList.add('bg-gray-50', 'autofill-locked');
    element.classList.remove('error-border');
    element.dataset.autofillLockedValue = element.value;

    if (!element.dataset) {
        element.dataset = {};
    }

    if (element.id === 'awaiting-file-no') {
        element.dataset.locked = 'true';
        element.dataset.lockedValue = element.value;
    }
}

function unlockArchiveField(element) {
    if (!element) {
        return;
    }

    element.readOnly = false;
    element.removeAttribute('readonly');
    element.classList.remove('bg-gray-50', 'success-border', 'error-border', 'autofill-locked');
    element.style.removeProperty('color');
    element.removeAttribute('data-autofill-locked');
    if (element.dataset) {
        delete element.dataset.autofillLockedValue;
    }

    if (element.dataset) {
        delete element.dataset.locked;
        delete element.dataset.lockedValue;
    }
}

function clearArchiveFields(options = {}) {
    const { preserveAwaiting = false, preserveIndexed = false, preservePrimary = false } = options;

    ARCHIVE_FIELD_IDS.forEach(id => {
        const element = document.getElementById(id);
        if (!element) {
            return;
        }

        if (preserveAwaiting && id === 'awaiting-file-no') {
            unlockArchiveField(element);
            return;
        }

        unlockArchiveField(element);
        element.value = '';
    });

    if (!preserveIndexed) {
        const indexedBy = document.getElementById('indexed-by');
        if (indexedBy) {
            indexedBy.value = '';
            indexedBy.classList.remove('autofill-locked');
        }

        const indexedDate = document.getElementById('indexed-date');
        if (indexedDate) {
            indexedDate.value = '';
            indexedDate.classList.remove('autofill-locked');
        }
    }

    // Clear tracking ID when fields are reset
    const trackingIdInput = document.getElementById('tracking-id');
    if (trackingIdInput) {
        trackingIdInput.value = '';
        trackingIdInput.style.backgroundColor = '#f9fafb';
    }

    clearCofoFields();

    const hasCofoToggle = document.getElementById('has-cofo-toggle');
    if (hasCofoToggle) {
        hasCofoToggle.checked = false;
    }

    const hiddenHasCofoField = document.getElementById('has-cofo');
    if (hiddenHasCofoField) {
        hiddenHasCofoField.checked = false;
    }

    const cofoDetailsContainer = document.getElementById('cofo-details-container');
    if (cofoDetailsContainer) {
        cofoDetailsContainer.classList.add('hidden');
    }

    setCofoAutofillStatus(null, '');

    const physicalRegistrySelect = document.getElementById('physical-registry');
    if (physicalRegistrySelect) {
        applyAutofillLock(['physical-registry'], false);
        physicalRegistrySelect.value = '';
    }

    const landUseSelect = document.getElementById('land-use-type');
    if (landUseSelect) {
        applyAutofillLock(['land-use-type'], false);
        landUseSelect.value = '';
    }

    if (!preservePrimary) {
        const autoFilledPrimaryFields = [
            'file-title',
            'plot-number',
            'tp-number',
            'lpkn-no',
            'location',
            'file-number-display',
            'related-file-number-display',
            'tracking-id'
        ];

        autoFilledPrimaryFields.forEach(id => {
            setAutoFilledValue(id, '', { lock: false });
        });
    }
}

window.__groupingState = groupingState;

// Tracking ID is now loaded from grouping records - no local generation needed

// Initialize file indexing form components
document.addEventListener('DOMContentLoaded', function() {
    // Tracking ID is now loaded from grouping records automatically - no manual generation needed
    
    const districtSelect = document.getElementById('district-select');
    const customDistrictContainer = document.getElementById('custom-district-container');
    const customDistrictInput = document.getElementById('custom-district-input');
    const lgaSelect = document.getElementById('lga-city');

    loadReferenceData();

    if (districtSelect && customDistrictContainer && customDistrictInput) {
        districtSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customDistrictContainer.classList.remove('hidden');
                customDistrictInput.focus();
            } else {
                customDistrictContainer.classList.add('hidden');
                customDistrictInput.value = '';
            }

            if (typeof window.__updateLocationField === 'function') {
                window.__updateLocationField();
            }
        });

        customDistrictInput.addEventListener('input', function() {
            if (typeof window.__updateLocationField === 'function') {
                window.__updateLocationField();
            }
        });
    }

    if (lgaSelect) {
        lgaSelect.addEventListener('change', function() {
            if (typeof window.__updateLocationField === 'function') {
                window.__updateLocationField();
            }
        });
    }
    
    // Registry is now a simple input field - no special handling needed
    
    // Handle CofO details toggle
    const hasCofoToggle = document.getElementById('has-cofo-toggle');
    const cofoDetailsContainer = document.getElementById('cofo-details-container');
    const hiddenHasCofoField = document.getElementById('has-cofo');
    
    if (hasCofoToggle && cofoDetailsContainer) {
        hasCofoToggle.addEventListener('change', function() {
            if (this.checked) {
                cofoDetailsContainer.classList.remove('hidden');
                if (hiddenHasCofoField) {
                    hiddenHasCofoField.checked = true;
                }
                // Auto-set instrument type to Certificate of Occupancy when enabled
                const instrumentTypeSelect = document.getElementById('cofo-instrument-type');
                if (instrumentTypeSelect) {
                    instrumentTypeSelect.value = 'Certificate of Occupancy';
                    // Trigger the change event to show transaction details
                    instrumentTypeSelect.dispatchEvent(new Event('change'));
                }
            } else {
                cofoDetailsContainer.classList.add('hidden');
                // Clear CofO fields when disabled
                clearCofoFields();
                setCofoAutofillStatus(null, '');
                if (hiddenHasCofoField) {
                    hiddenHasCofoField.checked = false;
                }
            }
        });
    }
    
    // Handle CofO Instrument Type selection (dynamic transaction details)
    const cofoInstrumentType = document.getElementById('cofo-instrument-type');
    const cofoTransactionDetails = document.getElementById('cofo-transaction-details');
    const cofoFirstPartyLabel = document.getElementById('cofo-first-party-label');
    const cofoSecondPartyLabel = document.getElementById('cofo-second-party-label');
    const cofoFirstPartyInput = document.getElementById('cofo-first-party');
    
    if (cofoInstrumentType && cofoTransactionDetails) {
        cofoInstrumentType.addEventListener('change', function() {
            if (isAutofillLocked(this)) {
                const lockedValue = this.dataset.autofillLockedValue;
                if (typeof lockedValue !== 'undefined') {
                    this.value = lockedValue;
                }
                return;
            }

            const selectedType = this.value;
            
            if (selectedType) {
                // Show transaction details
                cofoTransactionDetails.classList.remove('hidden');
                
                // Update party labels based on instrument type
                const partyLabels = getCofoPartyLabels(selectedType);
                if (cofoFirstPartyLabel) cofoFirstPartyLabel.textContent = partyLabels.first;
                if (cofoSecondPartyLabel) cofoSecondPartyLabel.textContent = partyLabels.second;
                
                // Auto-fill first party for government transactions
                const govTypes = ['Certificate of Occupancy', 'ST Certificate of Occupancy', 'SLTR Certificate of Occupancy', 'Customary Right of Occupancy'];
                if (govTypes.includes(selectedType) && cofoFirstPartyInput) {
                    cofoFirstPartyInput.value = 'KANO STATE GOVERNMENT';
                    cofoFirstPartyInput.classList.add('bg-gray-100');
                    cofoFirstPartyInput.readOnly = true;
                } else if (cofoFirstPartyInput) {
                    cofoFirstPartyInput.value = '';
                    cofoFirstPartyInput.classList.remove('bg-gray-100');
                    cofoFirstPartyInput.readOnly = false;
                }
            } else {
                // Hide transaction details
                cofoTransactionDetails.classList.add('hidden');
                clearCofoTransactionDetails();
            }
        });
    }
    
    // Sync Serial No to Page No
    const cofoSerialNoInput = document.getElementById('cofo-serial-no');
    const cofoPageNoInput = document.getElementById('cofo-page-no');
    
    if (cofoSerialNoInput && cofoPageNoInput) {
        cofoSerialNoInput.addEventListener('input', function() {
            if (isAutofillLocked(this)) {
                const lockedValue = this.dataset.autofillLockedValue;
                if (typeof lockedValue !== 'undefined') {
                    this.value = lockedValue;
                }
                return;
            }

            cofoPageNoInput.value = this.value;
        });

        cofoSerialNoInput.addEventListener('focus', function() {
            if (isAutofillLocked(this)) {
                this.blur();
            }
        });
        
        // Initialize Page No with current Serial No value if any
        if (cofoSerialNoInput.value) {
            cofoPageNoInput.value = cofoSerialNoInput.value;
        }
    }
    
    // Handle refresh archive details button
    const refreshArchiveDetailsBtn = document.getElementById('refresh-archive-details-btn');
    if (refreshArchiveDetailsBtn) {
        refreshArchiveDetailsBtn.addEventListener('click', function() {
            const fileNumberInput = document.getElementById('fileno');
            const archiveFileNoInput = document.getElementById('archive-file-no');
            const awaitingField = document.getElementById('awaiting-file-no');
            
            // Get the current file number from either field
            const fileNumber = fileNumberInput?.value || archiveFileNoInput?.value || awaitingField?.value;
            
            if (fileNumber) {
                // Show loading state on button
                this.disabled = true;
                this.innerHTML = '<i data-lucide="loader-2" class="h-3 w-3 animate-spin"></i>';
                
                // Refresh archive details from API
                autoFillArchiveDetailsFromAPI(fileNumber).finally(() => {
                    // Reset button state
                    setTimeout(() => {
                        this.disabled = false;
                        this.innerHTML = '<i data-lucide="refresh-cw" class="h-3 w-3"></i>';
                        // Re-initialize Lucide icons for the new icon
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }, 1000);
                });
            } else {
                // Show error if no file number available
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Enter Awaiting File No',
                        text: 'Type the awaiting file number or select a file before refreshing.',
                        icon: 'warning',
                        timer: 2000,
                        showConfirmButton: false,
                        toast: true,
                        position: 'top-end'
                    });
                }
            }
        });
    }
    
    // Initialize auto-assignment preview
    loadAutoAssignmentPreview();
    
    // Handle refresh assignment button
    const refreshAssignmentBtn = document.getElementById('refresh-assignment-btn');
    if (refreshAssignmentBtn) {
        refreshAssignmentBtn.addEventListener('click', function() {
            this.disabled = true;
            this.innerHTML = '<i data-lucide="loader-2" class="h-3 w-3 animate-spin"></i>';
            
            loadAutoAssignmentPreview();
            
            setTimeout(() => {
                this.disabled = false;
                this.innerHTML = '<i data-lucide="refresh-cw" class="h-3 w-3"></i>';
                // Re-initialize Lucide icons for the new icon
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }, 1000);
        });
    }

    // Initialize file indexing form submission
    initializeFileIndexingForm();

    // Ensure jQuery is available for Select2
    if (typeof $ === 'undefined' || typeof jQuery === 'undefined') {
        console.warn('jQuery not found - Select2 may not work properly');
    } else {
        console.log('jQuery loaded successfully');
    }
    
    // Ensure Select2 is available
    if (typeof $.fn.select2 === 'undefined') {
        console.warn('Select2 not found - batch selection will use fallback');
    } else {
        console.log('Select2 loaded successfully');
    }

    // Remove the problematic Select2 initialization from here
    // It will be initialized when the dialog opens
    });

 

function refreshAvailableBatches() {
    // Refresh the auto-assignment preview to show updated batch status
    console.log('Refreshing batch availability preview...');
    loadAutoAssignmentPreview();
}

// Make the function globally available
window.refreshAvailableBatches = refreshAvailableBatches;

// Auto-Assignment Functions
function loadAutoAssignmentPreview() {
    const statusEl = document.getElementById('auto-assignment-status');
    const hiddenBatch = document.getElementById('batch-no');
    const hiddenSerial = document.getElementById('serial-no');
    const hiddenShelf = document.getElementById('shelf-location');
    const hiddenShelfId = document.getElementById('shelf_label_id');
    const hiddenBatchId = document.getElementById('batch_id');

    if (!statusEl) {
        return;
    }

    // Reset hidden values
    if (hiddenBatch) hiddenBatch.value = '';
    if (hiddenSerial) hiddenSerial.value = '';
    if (hiddenShelf) hiddenShelf.value = '';
    if (hiddenShelfId) hiddenShelfId.value = '';
    if (hiddenBatchId) hiddenBatchId.value = '';

    statusEl.textContent = 'Checking current batch availability...';

    fetch('/fileindexing/get-current-batch-status')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.current_batch) {
                const batch = data.current_batch;
                const batchNo = batch.batch_no;
                const currentCount = batch.current_count;
                const nextSerial = currentCount + 1;
                const remaining = Math.max(0, 100 - currentCount);
                const shelfLocation = batch.shelf_location || 'Auto-assigned';

                statusEl.innerHTML = `
                    <span class="block">Current batch: <strong>Batch ${batchNo}</strong></span>
                    <span class="block">Next serial: <strong>#${nextSerial}</strong> (${remaining} slots remaining)</span>
                    <span class="block">Shelf location: <strong>${shelfLocation}</strong></span>
                    <span class="block text-xs text-blue-700 mt-1">Assignment confirmed when you save the file.</span>
                `;

                // Pre-populate hidden fields for form submission
                if (hiddenBatch) hiddenBatch.value = batchNo;
                if (hiddenSerial) hiddenSerial.value = nextSerial;
                if (hiddenShelf) hiddenShelf.value = shelfLocation;
                if (hiddenShelfId) hiddenShelfId.value = batch.shelf_label_id || '';
                if (hiddenBatchId) hiddenBatchId.value = batch.batch_id || batchNo;
            } else if (data.success && data.will_create_new) {
                const nextBatchNo = data.next_batch_no;
                const nextShelfLocation = data.next_shelf_location;
                
                statusEl.innerHTML = `
                    <span class="block font-semibold text-green-700">Will create new batch: <strong>Batch ${nextBatchNo}</strong></span>
                    <span class="block">First serial: <strong>#1</strong> (99 slots remaining)</span>
                    <span class="block">Shelf location: <strong>${nextShelfLocation}</strong></span>
                    <span class="block text-xs text-green-600 mt-1">New batch will be initialized when you save.</span>
                `;

                // Pre-populate hidden fields for new batch
                if (hiddenBatch) hiddenBatch.value = nextBatchNo;
                if (hiddenSerial) hiddenSerial.value = 1;
                if (hiddenShelf) hiddenShelf.value = nextShelfLocation;
                if (hiddenShelfId) hiddenShelfId.value = data.next_shelf_label_id || '';
                if (hiddenBatchId) hiddenBatchId.value = data.next_batch_id || nextBatchNo;
            } else {
                statusEl.innerHTML = `
                    <span class="block font-semibold text-amber-700">No current batch information available</span>
                    <span class="block text-xs text-amber-600">A new batch will be created automatically when you save.</span>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading auto assignment preview:', error);
            statusEl.innerHTML = `
                <span class="block font-semibold text-red-700">Unable to check batch availability</span>
                <span class="block text-xs text-red-600">Please try refreshing or contact support if the problem persists.</span>
            `;
        });
}

function refreshAutoAssignment() {
    loadAutoAssignmentPreview();
}

// Make functions globally available
window.loadAutoAssignmentPreview = loadAutoAssignmentPreview;
window.refreshAutoAssignment = refreshAutoAssignment;

// Auto-fill Archive Details from Grouping API
async function autoFillArchiveDetailsFromAPI(fileNumber) {
    const awaitingField = document.getElementById('awaiting-file-no');
    const groupField = document.getElementById('group-no');
    const batchField = document.getElementById('batch-no-field');
    const mdcField = document.getElementById('mdc-batch-no');
    const registryField = document.getElementById('registry');
    const shelfField = document.getElementById('shelf-rack-no');
    const sysBatchField = document.getElementById('sys-batch-no');
    const serialField = document.getElementById('serial-no');
    const indexedByField = document.getElementById('indexed-by');
    const indexedDateField = document.getElementById('indexed-date');
    const loadingIndicator = document.getElementById('grouping-loading-indicator');

    const archiveFields = [
        awaitingField,
        groupField,
        batchField,
        registryField,
        shelfField,
        sysBatchField,
        serialField
    ].filter(Boolean);

    if (loadingIndicator) {
        loadingIndicator.classList.remove('hidden');
    }

    const placeholders = new Map();
    archiveFields.forEach(field => {
        placeholders.set(field, field.placeholder || '');
        field.placeholder = 'Loading...';
        field.classList.remove('success-border', 'error-border');
    });

    const awaitingValue = awaitingField?.value?.trim() || '';
    const lookupCandidate = (fileNumber || '').trim();
    const lookupValue = lookupCandidate || awaitingValue;

    if (!lookupValue) {
        console.warn('No file number or awaiting file number provided for grouping lookup');
        archiveFields.forEach(field => {
            field.placeholder = placeholders.get(field) || '';
        });
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
        return null;
    }

    clearArchiveFields({ preserveAwaiting: true, preservePrimary: true });
    if (mdcField) {
        if (!mdcField.dataset.manualPlaceholder) {
            mdcField.dataset.manualPlaceholder = mdcField.placeholder || 'Enter MDC batch number';
        }
        mdcField.readOnly = false;
        mdcField.value = '';
        mdcField.placeholder = mdcField.dataset.manualPlaceholder;
        mdcField.classList.remove('bg-gray-50', 'success-border', 'error-border');
    }
    resetGroupingState();
    updateCreateButtonState();

    try {
        console.log('Fetching grouping record for awaiting file number:', lookupValue);
        const response = await fetch(`/api/grouping/awaiting/${encodeURIComponent(lookupValue)}`);
        let payload = {};

        try {
            payload = await response.json();
        } catch (jsonError) {
            payload = { success: false, message: 'Unable to decode grouping API response' };
        }

        if (!response.ok || !payload.success) {
            const error = new Error(payload?.message || 'Grouping record not found');
            error.code = response.status === 404 ? 'NOT_FOUND' : 'API_ERROR';
            throw error;
        }

        const groupingRecord = payload.data || payload;
        setGroupingRecord(groupingRecord);

        const fileNumberInput = document.getElementById('fileno');
        const fileNumberDisplay = document.getElementById('file-number-display');
        const archiveFileNoInput = document.getElementById('archive-file-no');

        const normalizedAwaiting = groupingState.normalizedAwaiting;
        const normalizedFileNumber = normalizeFileno(fileNumberInput?.value || '');
        const awaitingValueResolved = groupingRecord.awaiting_fileno ?? lookupValue;

        if (!fileNumberInput || !fileNumberInput.value || normalizedFileNumber !== normalizedAwaiting) {
            if (fileNumberInput) {
                fileNumberInput.value = awaitingValueResolved;
            }
            if (fileNumberDisplay) {
                setAutoFilledValue(fileNumberDisplay, awaitingValueResolved);
            }
        }

        if (archiveFileNoInput) {
            setAutoFilledValue(archiveFileNoInput, awaitingValueResolved);
        }

        lockArchiveField(awaitingField, awaitingValueResolved);

        const groupValue = groupingRecord.group ?? groupingRecord.batch_no ?? '';
        lockArchiveField(groupField, groupValue);

        lockArchiveField(batchField, groupingRecord.batch_no ?? '');
        lockArchiveField(sysBatchField, groupingRecord.sys_batch_no ?? '');

        let registryValue = groupingRecord.registry ?? groupingRecord.registry_name ?? groupingRecord.registry_label ?? '';
        if (!registryValue) {
            const registryMapping = {
                'COMMERCIAL': 'Registry 1 - Lands',
                'RESIDENTIAL': 'Registry 2 - Lands',
                'AGRICULTURAL': 'Registry 3 - Lands',
                'INDUSTRIAL': 'Registry 1 - Deeds',
                'MIXED_USE': 'Registry 2 - Lands',
                'COMMERCIAL AND RESIDENTIAL': 'Registry 1 - Lands'
            };
            if (groupingRecord.landuse) {
                registryValue = registryMapping[groupingRecord.landuse] || `${groupingRecord.landuse} Registry`;
            }
        }
        lockArchiveField(registryField, registryValue);

        lockArchiveField(shelfField, groupingRecord.shelf_rack ?? '');
        lockArchiveField(serialField, groupingRecord.number ?? groupingRecord.serial_no ?? '');

        if (indexedByField) {
            setAutoFilledValue(indexedByField, groupingRecord.indexed_by ?? '');
        }

        if (indexedDateField) {
            const rawDate = groupingRecord.date_index || groupingRecord.date || '';
            const normalizedDate = rawDate ? String(rawDate).substring(0, 10) : '';
            setAutoFilledValue(indexedDateField, normalizedDate);
        }

        // Update tracking ID from grouping record
        const trackingIdInput = document.getElementById('tracking-id');
        if (trackingIdInput) {
            if (groupingRecord.tracking_id) {
                setAutoFilledValue(trackingIdInput, groupingRecord.tracking_id);
            } else if (trackingIdInput.id) {
                applyAutofillLock(['tracking-id'], false);
            }
        }

        const physicalRegistrySelect = document.getElementById('physical-registry');
        if (physicalRegistrySelect) {
            const physicalRegistryValue = groupingRecord.physical_registry ?? '';
            setAutoFilledValue(physicalRegistrySelect, physicalRegistryValue);
        }

        // Populate Land Use Type if available from grouping record
        if (groupingRecord.landuse) {
            populateLandUseFromRecord(groupingRecord.landuse);
        }

        await autoFillCofODetailsFromAPI(groupingRecord.awaiting_fileno ?? lookupValue);

        groupingState.mismatch = false;
        updateCreateButtonState();

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Grouping Match Found',
                text: `Archive details loaded for ${groupingRecord.awaiting_fileno}`,
                icon: 'success',
                timer: 2200,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        return groupingRecord;
    } catch (error) {
        console.error('Error fetching archive details from API:', error);

    clearArchiveFields({ preserveAwaiting: true, preservePrimary: true });
        resetGroupingState();
        updateCreateButtonState();

        if (awaitingField) {
            awaitingField.classList.add('error-border');
            setTimeout(() => awaitingField.classList.remove('error-border'), 2500);
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: error.code === 'NOT_FOUND' ? 'Awaiting File Not Found' : 'Grouping Lookup Failed',
                text: error.message || 'Unable to retrieve archive details. Please verify the awaiting file number.',
                icon: error.code === 'NOT_FOUND' ? 'info' : 'error',
                timer: 2500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }

        return null;
    } finally {
        archiveFields.forEach(field => {
            field.placeholder = placeholders.get(field) || '';
        });

        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
    }
}

// Make the function globally available
window.autoFillArchiveDetailsFromAPI = autoFillArchiveDetailsFromAPI;

// Function to get party labels for CofO instrument types
function getCofoPartyLabels(instrumentType) {
    const partyLabels = {
        'Power of Attorney': { first: 'Grantor', second: 'Grantee' },
        'Irrevocable Power of Attorney': { first: 'Grantor', second: 'Grantee' },
        'Deed of Assignment': { first: 'Assignor', second: 'Assignee' },
        'ST Assignment': { first: 'Assignor', second: 'Assignee' },
        'Deed of Mortgage': { first: 'Mortgagor', second: 'Mortgagee' },
        'Tripartite Mortgage': { first: 'Mortgagor', second: 'Mortgagee' },
        'Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
        'ST Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
        'SLTR Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
        'Customary Right of Occupancy': { first: 'Grantor', second: 'Grantee' },
        'Deed of Transfer': { first: 'Transferor', second: 'Transferee' },
        'Deed of Gift': { first: 'Donor', second: 'Donee' },
        'Deed of Lease': { first: 'Lessor', second: 'Lessee' },
        'Deed of Sub Lease': { first: 'Lessor', second: 'Lessee' },
        'Deed of Sub Under Lease': { first: 'Lessor', second: 'Lessee' },
        'Indenture of Lease': { first: 'Lessor', second: 'Lessee' },
        'Tenancy Agreement': { first: 'Landlord', second: 'Tenant' },
        'Deed of Release': { first: 'Releasor', second: 'Releasee' },
        'Deed of Surrender': { first: 'Surrenderor', second: 'Surrenderee' },
        'Letter of Administration': { first: 'Administrator', second: 'Beneficiary' },
        'Certificate of Purchase': { first: 'Vendor', second: 'Purchaser' }
    };
    
    return partyLabels[instrumentType] || { first: 'Grantor', second: 'Grantee' };
}

// Function to clear CofO transaction details
function clearCofoTransactionDetails() {
    const transactionFields = [
        'cofo-first-party',
        'cofo-second-party'
    ];
    
    transactionFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
        }
    });
}

// Function to clear CofO fields
function clearCofoFields() {
    const cofoFields = [
        'cofo-date',
        'cofo-serial-no', 
        'cofo-page-no',
        'cofo-vol-no',
        'cofo-deeds-time',
        'cofo-deeds-date'
    ];
    
    cofoFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = '';
        }
    });
    
    // Reset instrument type to default
    const instrumentTypeSelect = document.getElementById('cofo-instrument-type');
    if (instrumentTypeSelect) {
        instrumentTypeSelect.value = 'Certificate of Occupancy';
    }

    const cofoFirstPartyInput = document.getElementById('cofo-first-party');
    if (cofoFirstPartyInput) {
        cofoFirstPartyInput.readOnly = false;
        cofoFirstPartyInput.classList.remove('bg-gray-100');
    }
    
    // Clear transaction details
    clearCofoTransactionDetails();
    
    // Hide transaction details
    const cofoTransactionDetails = document.getElementById('cofo-transaction-details');
    if (cofoTransactionDetails) {
        cofoTransactionDetails.classList.add('hidden');
    }

    applyAutofillLock([
        'cofo-instrument-type',
        'cofo-date',
        'cofo-serial-no',
        'cofo-page-no',
        'cofo-vol-no',
        'cofo-deeds-time',
        'cofo-deeds-date',
        'cofo-first-party',
        'cofo-second-party'
    ], false);

    autofillState.cofo = false;
}

// Make functions globally available
window.getCofoPartyLabels = getCofoPartyLabels;
window.clearCofoTransactionDetails = clearCofoTransactionDetails;
window.clearCofoFields = clearCofoFields;

// All shelf selection functions removed - now using automatic assignment service

// Note: Shelf assignment is now handled automatically by the backend service

function initializeFileIndexingForm() {
    const form = document.getElementById('new-file-form');
    const createBtn = document.getElementById('create-file-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const closeBtn = document.getElementById('close-dialog-btn');
    const overlay = document.getElementById('new-file-dialog-overlay');

    updateCreateButtonState();
    
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitFileIndexingForm();
        });
    }

    const awaitingField = document.getElementById('awaiting-file-no');
    if (awaitingField) {
        awaitingField.addEventListener('input', function() {
            if (awaitingField.dataset?.locked === 'true') {
                awaitingField.value = awaitingField.dataset.lockedValue || awaitingField.value;
                return;
            }

            resetGroupingState();
            updateCreateButtonState();
        });

        const triggerAwaitingLookup = () => {
            if (awaitingField.dataset?.locked === 'true') {
                return;
            }

            const pendingValue = awaitingField.value?.trim();
            if (pendingValue && pendingValue.length >= 3) {
                autoFillArchiveDetailsFromAPI(pendingValue);
            }
        };

        awaitingField.addEventListener('blur', triggerAwaitingLookup);
        awaitingField.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                triggerAwaitingLookup();
            }
        });
    }
    
    // Close dialog handlers
    [cancelBtn, closeBtn].forEach(btn => {
        if (btn) {
            btn.addEventListener('click', function() {
                closeFileIndexingDialog();
            });
        }
    });
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeFileIndexingDialog();
            }
        });
    }
}

function initializeFileIndexingForm() {
    const form = document.getElementById('new-file-form');
    const createBtn = document.getElementById('create-file-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const closeBtn = document.getElementById('close-dialog-btn');
    const overlay = document.getElementById('new-file-dialog-overlay');
    
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            e.preventDefault();
            submitFileIndexingForm();
        });
    }
    
    // Close dialog handlers
    [cancelBtn, closeBtn].forEach(btn => {
        if (btn) {
            btn.addEventListener('click', function() {
                closeFileIndexingDialog();
            });
        }
    });
    
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                closeFileIndexingDialog();
            }
        });
    }
}

function submitFileIndexingForm() {
    const form = document.getElementById('new-file-form');
    if (!form) return;
    
    // Get form data
    const fileNumber = document.getElementById('fileno')?.value || '';
    const fileTitle = document.getElementById('file-title')?.value || '';
    
    console.log('Form submission - File Number:', fileNumber);
    console.log('Form submission - File Title:', fileTitle);

    const normalizedFileNumber = normalizeFileno(fileNumber);

    if (!groupingState.record) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Awaiting File Not Confirmed',
                text: 'Please fetch the grouping record so the awaiting file number can be validated before saving.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Please load the grouping record to confirm the awaiting file number before saving.');
        }
        updateCreateButtonState();
        return;
    }

    if (!normalizedFileNumber || normalizedFileNumber !== groupingState.normalizedAwaiting) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'File Number Mismatch',
                text: 'The selected file number does not match the awaiting file number from grouping. Please refresh the archive details.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            alert('Selected file number must match the awaiting file number from grouping.');
        }
        updateCreateButtonState();
        return;
    }
    
    // Debug selection method
    const selectionMethod = document.querySelector('input[name="selection_method"]:checked')?.value || 'batch';
    console.log('Selection Method:', selectionMethod);
    
    const lgaSelect = document.getElementById('lga-city');
    const districtSelectEl = document.getElementById('district-select');
    const lgaName = getSelectedOptionName(lgaSelect);
    const lgaId = getSelectedOptionId(lgaSelect);
    const districtName = getDistrictValue();
    const districtId = getSelectedOptionId(districtSelectEl);

    const formData = {
        file_number: fileNumber,
        file_title: fileTitle,
        land_use_type: document.getElementById('land-use-type')?.value || 'residential',
        plot_number: document.getElementById('plot-number')?.value || '',
        tp_no: document.getElementById('tp-number')?.value || '',
        lpkn_no: document.getElementById('lpkn-no')?.value || '',
        location: document.getElementById('location')?.value || '',
        plot_size: document.getElementById('plot-size')?.value || '',
        district: districtName,
        district_id: districtId,
        registry: getRegistryValue(),
        lga: lgaName || 'Kano Municipal',
        lga_id: lgaId,
        has_cofo: document.getElementById('has-cofo')?.checked || false,
        has_transaction: document.getElementById('has-transaction')?.checked || false,
        is_problematic: document.getElementById('is-problematic')?.checked || false,
        is_co_owned_plot: document.getElementById('co-owned-plot')?.checked || false,
        is_merged: document.getElementById('merged-plot')?.checked || false,
        serial_no: document.getElementById('serial-no')?.value || '',
        // Automatic shelf assignment (no manual selection)
        batch_no: document.getElementById('batch-no')?.value || '',
        shelf_location: document.getElementById('shelf-location')?.value || '',
        shelf_label_id: document.getElementById('shelf_label_id')?.value || '',
        batch_id: document.getElementById('batch_id')?.value || '',
        tracking_id: document.getElementById('tracking-id')?.value || '',
        // New File Archive Details fields
        archive_file_no: document.getElementById('archive-file-no')?.value || '',
        awaiting_file_no: document.getElementById('awaiting-file-no')?.value || '',
        group_no: document.getElementById('group-no')?.value || '',
        batch_no_field: document.getElementById('batch-no-field')?.value || '',
        mdc_batch_no: document.getElementById('mdc-batch-no')?.value || '',
        physical_registry: document.getElementById('physical-registry')?.value || '',
    sys_batch_no: document.getElementById('sys-batch-no')?.value || '',
        shelf_rack_no: document.getElementById('shelf-rack-no')?.value || '',
        indexed_by: document.getElementById('indexed-by')?.value || '',
        indexed_date: document.getElementById('indexed-date')?.value || '',
        archive_location: document.getElementById('archive-location')?.value || '',
    grouping_match_id: document.getElementById('grouping-id')?.value || groupingState.record?.id || null,
        // CofO Details fields
        has_cofo: document.getElementById('has-cofo-toggle')?.checked || false,
        cofo_date: document.getElementById('cofo-date')?.value || '',
        cofo_serial_no: document.getElementById('cofo-serial-no')?.value || '',
        cofo_page_no: document.getElementById('cofo-page-no')?.value || '',
        cofo_vol_no: document.getElementById('cofo-vol-no')?.value || '',
        cofo_deeds_time: document.getElementById('cofo-deeds-time')?.value || '',
        cofo_deeds_date: document.getElementById('cofo-deeds-date')?.value || '',
        cofo_instrument_type: document.getElementById('cofo-instrument-type')?.value || '',
        // CofO Transaction Details fields
        cofo_first_party: document.getElementById('cofo-first-party')?.value || '',
        cofo_second_party: document.getElementById('cofo-second-party')?.value || '',
        cofo_land_use: document.getElementById('cofo-land-use')?.value || '',
        cofo_period: document.getElementById('cofo-period')?.value || '',
        cofo_period_unit: document.getElementById('cofo-period-unit')?.value || 'Years',
        // Include smart file selector data
        main_application_id: document.getElementById('application_id')?.value || null,
        subapplication_id: document.getElementById('sub_application_id')?.value || null,
        file_number_source: window.selectedApplication?.isManual ? 'manual' : 'existing',
        source_file_id: window.selectedApplication?.id || null,
        related_fileno: document.getElementById('related-fileno')?.value || ''
    };
    
    // Validation
    if (!formData.file_number || !formData.file_title) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Validation Error',
                text: 'File number and file title are required.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            alert('File number and file title are required.');
        }
        return;
    }

    // Note: Shelf assignment is now handled automatically by the backend service
    
    // Show loading state
    const createBtn = document.getElementById('create-file-btn');
    if (createBtn) {
        createBtn.disabled = true;
        createBtn.textContent = 'Creating...';
    }
    
    // Submit to server
    fetch('/fileindexing/store', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        console.log('Server response:', data);
        
        if (data.success) {
            // Close the file indexing dialog first
            closeFileIndexingDialog();
            
            // Refresh available data since shelves were just used
            if (typeof window.refreshAvailableBatches === 'function') {
                window.refreshAvailableBatches();
            }
            // Shelf refreshing no longer needed - using automatic assignment
            
            // Refresh the file list if available
            if (typeof refreshFileList === 'function') {
                refreshFileList();
            }
            
            // Show success message with batch assignment details
            if (typeof Swal !== 'undefined') {
                let successMessage = data.message || 'File indexing created successfully!';
                
                // Add batch assignment information if available
                if (data.batch_assignment) {
                    const assignment = data.batch_assignment;
                    successMessage += `\n\nAssigned to:\nBatch: ${assignment.batch_no}\nSerial: ${assignment.serial_no}\nShelf: ${assignment.shelf_location || 'Auto-assigned'}`;
                }
                
                Swal.fire({
                    title: 'Success!',
                    text: successMessage,
                    icon: 'success',
                    timer: 2500,
                    showConfirmButton: false
                });
            }
            
            // Automatically open property transaction modal with the created file indexing data
            console.log('Opening property transaction modal...');
            console.log('Full server response:', JSON.stringify(data, null, 2));
            
            if (typeof openPropertyTransactionModal === 'function') {
                // Extract the file indexing data from the response or use form data
                const fileIndexingData = {
                    file_number: (data.data && data.data.file_number) || formData.file_number,
                    file_title: (data.data && data.data.file_title) || formData.file_title,
                    plot_number: (data.data && data.data.plot_number) || formData.plot_number,
                    tp_no: (data.data && data.data.tp_no) || formData.tp_no,
                    lpkn_no: (data.data && data.data.lpkn_no) || formData.lpkn_no,
                    lga: (data.data && data.data.lga) || formData.lga,
                    district: (data.data && data.data.district) || formData.district,
                    location: (data.data && data.data.location) || formData.location,
                    land_use_type: (data.data && data.data.land_use_type) || formData.land_use_type
                };
                
                console.log('Final fileIndexingData to pass to modal:', fileIndexingData);
                
                // Validate that we have the required file number
                if (!fileIndexingData.file_number) {
                    console.error('File number is missing!');
                    alert('Error: File number not found. Please try again.');
                    return;
                }
                
                // Delay slightly to ensure SweetAlert finishes
                setTimeout(() => {
                    console.log('Calling openPropertyTransactionModal now...');
                    openPropertyTransactionModal(fileIndexingData);
                }, 1600);
            } else {
                console.error('openPropertyTransactionModal function not found');
                alert('Error: Modal function not found. Please refresh the page and try again.');
            }
        } else {
            throw new Error(data.message || 'Unknown error occurred');
        }
    })
    .catch(error => {
        console.error('Error creating file indexing:', error);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Error',
                text: error.message || 'Failed to create file indexing. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        } else {
            alert(error.message || 'Failed to create file indexing. Please try again.');
        }
    })
    .finally(() => {
        // Reset button state
        if (createBtn) {
        if (loadingIndicator) {
            loadingIndicator.classList.add('hidden');
        }
            createBtn.disabled = false;
            createBtn.textContent = 'Create File Index';
        }
        updateCreateButtonState();
    });
}

function getDistrictValue() {
    const districtSelect = document.getElementById('district-select');
    const customDistrictInput = document.getElementById('custom-district-input');
    
    if (!districtSelect) {
        return '';
    }

    if (districtSelect.value === 'other') {
        return customDistrictInput?.value?.trim() || '';
    }

    return getSelectedOptionName(districtSelect);
}

function getRegistryValue() {
    const registryField = document.getElementById('registry');

    if (!registryField) {
        return '';
    }

    return registryField.value;
}

function closeFileIndexingDialog() {
    const overlay = document.getElementById('new-file-dialog-overlay');
    if (overlay) {
        overlay.classList.add('hidden');

        // Reset form
        const form = document.getElementById('new-file-form');
        if (form) {
            form.reset();
        }
        
        // Reset file number fields
        const fileNumberDisplay = document.getElementById('file-number-display');
        const fileNumberInput = document.getElementById('fileno');
        if (fileNumberDisplay) {
            setAutoFilledValue(fileNumberDisplay, '', { lock: false });
        }
        if (fileNumberInput) {
            fileNumberInput.value = '';
        }

        const relatedDisplay = document.getElementById('related-file-number-display');
        const relatedInput = document.getElementById('related-fileno');
        if (relatedDisplay) {
            setAutoFilledValue(relatedDisplay, '', { lock: false });
        }
        if (relatedInput) {
            relatedInput.value = '';
        }

        // Clear batch selection based on what's available
        if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
            const $batchSelect = $('#batch-no');
            if ($batchSelect.hasClass('select2-hidden-accessible')) {
                $batchSelect.val(null).trigger('change');
            }
        } else {
            // Fallback for regular select
            const batchSelect = document.getElementById('batch-no');
            if (batchSelect) {
                batchSelect.value = '';
            }
        }
        
        // Clear shelf location
        const shelfInput = document.getElementById('shelf-location');
        if (shelfInput) {
            setAutoFilledValue(shelfInput, '', { lock: false });
            shelfInput.classList.remove('loading', 'success-border', 'error-border');
        }

        // Clear custom district input
        const customDistrictContainer = document.getElementById('custom-district-container');
        const customDistrictInput = document.getElementById('custom-district-input');
        if (customDistrictContainer && customDistrictInput) {
            customDistrictContainer.classList.add('hidden');
            customDistrictInput.value = '';
        }

        const districtSelect = document.getElementById('district-select');
        if (districtSelect) {
            if (referenceDataStore.isLoaded) {
                populateDistrictSelect(referenceDataStore.districts);
            }
            districtSelect.value = '';
        }

        const lgaSelect = document.getElementById('lga-city');
        if (lgaSelect) {
            if (referenceDataStore.isLoaded) {
                populateLgaSelect(referenceDataStore.lgas);
            }
            lgaSelect.value = '';
        }

        clearArchiveFields();
        resetGroupingState();
        updateCreateButtonState();

        // Always regenerate tracking ID when closing
        const trackingIdInput = document.getElementById('tracking-id');
        if (trackingIdInput) {
            trackingIdInput.value = generateTrackingId();
        }

        // Reset new Archive Details fields
        const archiveFileNoInput = document.getElementById('archive-file-no');
        if (archiveFileNoInput) archiveFileNoInput.value = '';

        // Reset CofO details section
        const hasCofoToggle = document.getElementById('has-cofo-toggle');
        const cofoDetailsContainer = document.getElementById('cofo-details-container');
        if (hasCofoToggle) {
            hasCofoToggle.checked = false;
        }
        if (cofoDetailsContainer) {
            cofoDetailsContainer.classList.add('hidden');
        }
        clearCofoFields();

        // Reset smart file selector
        if (typeof resetSmartFileSelector === 'function') {
            resetSmartFileSelector();
        }

        if (typeof window.__updateLocationField === 'function') {
            window.__updateLocationField();
        }
    // Ensure custom district input is hidden (already handled above when present)
    // (No redeclaration here to avoid duplicate identifier errors)
    }
}

// Function to open the file indexing dialog
function openFileIndexingDialog() {
    const overlay = document.getElementById('new-file-dialog-overlay');
    if (overlay) {
        overlay.classList.remove('hidden');

        clearArchiveFields();
        resetGroupingState();
        updateCreateButtonState();

        loadReferenceData();

        // Refresh automatic assignment preview
        console.log('Dialog opening - refreshing auto-assignment preview...');
        loadAutoAssignmentPreview();

        // Always ensure tracking ID is present when dialog opens
        const trackingIdInput = document.getElementById('tracking-id');
        if (trackingIdInput) {
            // If no tracking ID exists or it's still showing placeholder, generate one
            if (!trackingIdInput.value || trackingIdInput.value === 'Auto-generating...') {
                const newTrackingId = generateTrackingId();
                setAutoFilledValue(trackingIdInput, newTrackingId);
            } else {
                // Generate a fresh one each time dialog opens
                const newTrackingId = generateTrackingId();
                setAutoFilledValue(trackingIdInput, newTrackingId);
            }
        }

        // Auto-populate indexed by and indexed date fields
        const indexedByInput = document.getElementById('indexed-by');
        const indexedDateInput = document.getElementById('indexed-date');
        
        if (indexedByInput) {
            // You may want to replace this with actual user data from Laravel
            const defaultIndexer = '{{ Auth::user()->name ?? "Current User" }}';
            setAutoFilledValue(indexedByInput, defaultIndexer);
        }
        
        if (indexedDateInput) {
            // Set current date
            const today = new Date();
            const formattedDate = today.getFullYear() + '-' + 
                String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                String(today.getDate()).padStart(2, '0');
            setAutoFilledValue(indexedDateInput, formattedDate);
        }

        // Focus on first input
        setTimeout(() => {
            const firstInput = overlay.querySelector('input:not([readonly])');
            if (firstInput) {
                firstInput.focus();
            }
        }, 100);
        // Registry is now a simple input field - no special handling needed
        
        // Initialize location field auto-update functionality
        initializeLocationAutoUpdate();
    }
}

// Initialize location field auto-update functionality
function initializeLocationAutoUpdate() {
    const plotNumberInput = document.getElementById('plot-number');
    const districtSelect = document.getElementById('district-select');
    const customDistrictInput = document.getElementById('custom-district-input');
    const lgaSelect = document.getElementById('lga-city');
    const locationInput = document.getElementById('location');

    function getDistrictValueLocal() {
        if (!districtSelect) {
            return '';
        }

        if (districtSelect.value === 'other') {
            return customDistrictInput?.value?.trim() || '';
        }

        return getSelectedOptionName(districtSelect);
    }

    function updateLocationField() {
        if (!locationInput) {
            return;
        }

        const parts = [];
        const plotNumber = plotNumberInput?.value?.trim();
        const districtName = getDistrictValueLocal();
        const lgaName = getSelectedOptionName(lgaSelect);

        if (plotNumber) {
            parts.push(plotNumber);
        }
        if (districtName) {
            parts.push(districtName);
        }
        if (lgaName) {
            parts.push(lgaName);
        }

        const locationValue = parts.join(', ');
        locationInput.value = locationValue;

        if (locationValue) {
            locationInput.style.backgroundColor = '#f0f9ff';
            setTimeout(() => {
                locationInput.style.backgroundColor = '#f9fafb';
            }, 1000);
        }
    }

    if (plotNumberInput) {
        plotNumberInput.removeEventListener('input', updateLocationField);
        plotNumberInput.addEventListener('input', updateLocationField);
    }

    if (districtSelect) {
        districtSelect.removeEventListener('change', updateLocationField);
        districtSelect.addEventListener('change', updateLocationField);
    }

    if (customDistrictInput) {
        customDistrictInput.removeEventListener('input', updateLocationField);
        customDistrictInput.addEventListener('input', updateLocationField);
    }

    if (lgaSelect) {
        lgaSelect.removeEventListener('change', updateLocationField);
        lgaSelect.addEventListener('change', updateLocationField);
    }

    window.__updateLocationField = updateLocationField;
    updateLocationField();
}

// All batch selection functions removed - using automatic assignment service

// All batch selection functions removed - using automatic assignment service
// Replacement: loadAutoAssignmentPreview() shows current batch status automatically

// File Number Selection Logic
document.addEventListener('DOMContentLoaded', function() {
    // Ensure the GlobalFileNoModal script is loaded before using it
    const checkScriptLoaded = () => {
        if (typeof GlobalFileNoModal !== 'undefined') {
            initializeFileNumberSelector();
        } else {
            // Script not loaded yet, check again in 100ms
            setTimeout(checkScriptLoaded, 100);
        }
    };
    
    checkScriptLoaded();
    
    function initializeFileNumberSelector() {
        // Get references to the elements
    const fileNumberDisplay = document.getElementById('file-number-display');
    const fileNumberInput = document.getElementById('fileno');
    const selectFileNumberBtn = document.getElementById('select-file-number-btn');
    const relatedDisplay = document.getElementById('related-file-number-display');
    const relatedInput = document.getElementById('related-fileno');
    const selectRelatedBtn = document.getElementById('select-related-file-number-btn');
        
        // Add click event listener to the Select button
        if (selectFileNumberBtn) {
            selectFileNumberBtn.addEventListener('click', function() {
                // Open the global File Number modal
                GlobalFileNoModal.open({
                    callback: function(fileData) {
                        if (!fileData || !fileData.fileNumber) {
                            return;
                        }

                            clearArchiveFields();
                            resetGroupingState();
                            updateCreateButtonState();

                        if (fileNumberDisplay) {
                            setAutoFilledValue(fileNumberDisplay, fileData.fileNumber);
                        }

                        if (fileNumberInput) {
                            fileNumberInput.value = fileData.fileNumber;
                        }

                        // Auto-populate the archive file number field
                        const archiveFileNoInput = document.getElementById('archive-file-no');
                        if (archiveFileNoInput) {
                            setAutoFilledValue(archiveFileNoInput, fileData.fileNumber);
                        }

                        // Auto-fill File Archive Details from Grouping API
                        autoFillArchiveDetailsFromAPI(fileData.fileNumber);

                        const record = fileData.record || null;
                        const trackingIdInput = document.getElementById('tracking-id');
                        if (trackingIdInput) {
                            if (record?.tracking_id) {
                                setAutoFilledValue(trackingIdInput, record.tracking_id);
                            } else {
                                applyAutofillLock(['tracking-id'], false);
                                ensureTrackingIdExists();
                            }
                        }

                        const fileTitleInput = document.getElementById('file-title');
                        if (fileTitleInput) {
                            setAutoFilledValue(fileTitleInput, record?.file_name ?? '');
                        }

                        const plotNumberInput = document.getElementById('plot-number');
                        if (plotNumberInput) {
                            setAutoFilledValue(plotNumberInput, record?.plot_no ?? '');
                        }

                        const tpNumberInput = document.getElementById('tp-number');
                        if (tpNumberInput) {
                            setAutoFilledValue(tpNumberInput, record?.tp_no ?? '');
                        }

                        const lpknInput = document.getElementById('lpkn-no');
                        if (lpknInput) {
                            setAutoFilledValue(lpknInput, record?.lpkn_no ?? '');
                        }

                        const locationInput = document.getElementById('location');
                        const shouldAutoUpdateLocation = !record?.location;
                        if (locationInput && record?.location) {
                            setAutoFilledValue(locationInput, record.location);

                            // Parse location to populate District and LGA fields
                            parseLocationAndPopulateFields(record.location);
                        } else if (locationInput) {
                            applyAutofillLock(['location'], false);
                        }

                        if (shouldAutoUpdateLocation && typeof window.__updateLocationField === 'function') {
                            window.__updateLocationField();
                        }

                        // Populate Land Use Type if available from the record
                        if (record?.land_use || record?.landuse) {
                            const landUseSelect = document.getElementById('land-use-type');
                            if (landUseSelect) {
                                const landUse = record.land_use || record.landuse;
                                populateLandUseFromRecord(landUse);
                            }
                        }

                        console.log('File number selected:', fileData.fileNumber, record);
                    }
                });
            });
        }

        if (selectRelatedBtn) {
            selectRelatedBtn.addEventListener('click', function() {
                GlobalFileNoModal.open({
                    callback: function(fileData) {
                        if (!fileData || !fileData.fileNumber) {
                            return;
                        }

                        if (relatedDisplay) {
                            setAutoFilledValue(relatedDisplay, fileData.fileNumber);
                        }

                        if (relatedInput) {
                            relatedInput.value = fileData.fileNumber;
                        }
                    }
                });
            });
        }
    }
});

// Normalize geographic text for looser comparisons
function normalizeGeoText(value) {
    if (!value && value !== 0) {
        return '';
    }

    return value
        .toString()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // remove accents
        .toUpperCase()
        .replace(/[^A-Z0-9 ]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

// Helper function to parse location and populate District/LGA fields
function parseLocationAndPopulateFields(location) {
    if (!location) return;
    
    console.log('parseLocationAndPopulateFields: Processing location:', location);

    if (!referenceDataStore.isLoaded) {
        console.log('parseLocationAndPopulateFields: Reference data not loaded, waiting...');
        loadReferenceData()
            .then(() => {
                if (referenceDataStore.isLoaded) {
                    parseLocationAndPopulateFields(location);
                }
            })
            .catch((error) => {
                console.warn('Unable to load reference data for location auto-fill:', error);
            });
        return;
    }

    // Location format is typically "Plot X, District, LGA" or similar
    const locationParts = location
        .split(',')
        .map(part => part.trim())
        .filter(Boolean);
    
    console.log('parseLocationAndPopulateFields: Location parts:', locationParts);
    
    if (locationParts.length >= 2) {
        const district = locationParts[locationParts.length - 2];  // Second to last part
        const lga = locationParts[locationParts.length - 1];       // Last part
        
        console.log('parseLocationAndPopulateFields: Extracted district:', district, 'LGA:', lga);
        
        // Populate District select
        const districtSelect = document.getElementById('district-select');
        if (districtSelect && district) {
            console.log('parseLocationAndPopulateFields: Processing district', district, 'with', referenceDataStore.districts.length, 'available districts');
            const customDistrictContainer = document.getElementById('custom-district-container');
            const customDistrictInput = document.getElementById('custom-district-input');
            const districtMatched = populateSelectFromText(districtSelect, district, referenceDataStore.districts, 'name');

            if (districtMatched) {
                console.log('parseLocationAndPopulateFields: District matched, hiding custom input');
                if (customDistrictContainer) {
                    customDistrictContainer.classList.add('hidden');
                }
                if (customDistrictInput) {
                    customDistrictInput.value = '';
                }
            } else if (districtSelect.querySelector('option[value="other"]')) {
                console.log('parseLocationAndPopulateFields: No district match, using Other option with custom input:', district);
                districtSelect.value = 'other';
                setAutoFilledValue(districtSelect, districtSelect.value);
                if (customDistrictContainer && customDistrictInput) {
                    customDistrictContainer.classList.remove('hidden');
                    customDistrictInput.value = district;
                }
            }

            if (typeof window.__updateLocationField === 'function') {
                window.__updateLocationField();
            }
        }

        // Populate LGA select
        const lgaSelect = document.getElementById('lga-city');
        if (lgaSelect && lga) {
            const lgaMatched = populateSelectFromText(lgaSelect, lga, referenceDataStore.lgas, 'name');

            if (!lgaMatched) {
                const normalizedLga = normalizeGeoText(lga);
                let fallbackOption = Array.from(lgaSelect.options).find(option => option.dataset?.dynamic === 'true' && normalizeGeoText(option.textContent) === normalizedLga);
                if (!fallbackOption) {
                    fallbackOption = document.createElement('option');
                    fallbackOption.value = lga;
                    fallbackOption.textContent = lga;
                    fallbackOption.dataset.dynamic = 'true';
                    lgaSelect.appendChild(fallbackOption);
                }
                lgaSelect.value = fallbackOption.value;
                setAutoFilledValue(lgaSelect, lgaSelect.value);
            }

            if (typeof window.__updateLocationField === 'function') {
                window.__updateLocationField();
            }
        }
    }
}

// Helper function to populate a select field from text
function populateSelectFromText(selectElement, text, dataArray, textField) {
    if (!selectElement || !text || !dataArray || !Array.isArray(dataArray)) {
        console.log('populateSelectFromText: Invalid parameters', { selectElement: !!selectElement, text, dataArrayLength: dataArray?.length, textField });
        return false;
    }

    const normalizedText = normalizeGeoText(text);
    if (!normalizedText) {
        console.log('populateSelectFromText: No normalized text for', text);
        return false;
    }
    
    console.log('populateSelectFromText: Looking for', normalizedText, 'in', dataArray.length, 'items');
    
    // Find matching option in the data array with progressively looser matching
    let match = null;
    
    // 1. Exact match
    match = dataArray.find(item => {
        const itemText = item[textField] || item.name || item.label || '';
        const normalizedItem = normalizeGeoText(itemText);
        return normalizedItem === normalizedText;
    });
    
    // 2. Substring match (either direction)
    if (!match) {
        match = dataArray.find(item => {
            const itemText = item[textField] || item.name || item.label || '';
            const normalizedItem = normalizeGeoText(itemText);
            if (!normalizedItem) return false;
            return normalizedItem.includes(normalizedText) || normalizedText.includes(normalizedItem);
        });
    }
    
    // 3. Start of word match
    if (!match) {
        match = dataArray.find(item => {
            const itemText = item[textField] || item.name || item.label || '';
            const normalizedItem = normalizeGeoText(itemText);
            if (!normalizedItem) return false;
            
            const textWords = normalizedText.split(' ');
            const itemWords = normalizedItem.split(' ');
            
            return textWords.some(textWord => 
                itemWords.some(itemWord => 
                    itemWord.startsWith(textWord) || textWord.startsWith(itemWord)
                )
            );
        });
    }
    
    // 4. Fuzzy match - common prefix/suffix
    if (!match && normalizedText.length >= 3) {
        match = dataArray.find(item => {
            const itemText = item[textField] || item.name || item.label || '';
            const normalizedItem = normalizeGeoText(itemText);
            if (!normalizedItem || normalizedItem.length < 3) return false;
            
            // Check if they share a significant prefix or suffix
            const minLength = Math.min(normalizedText.length, normalizedItem.length);
            const prefixLength = Math.floor(minLength * 0.6); // 60% match
            
            return normalizedText.substring(0, prefixLength) === normalizedItem.substring(0, prefixLength) ||
                   normalizedText.substring(-prefixLength) === normalizedItem.substring(-prefixLength);
        });
    }
    
    if (match) {
        console.log('populateSelectFromText: Found match', match);
        const optionValue = match.id != null ? String(match.id) : (match.value || match.name || '');
        let option = Array.from(selectElement.options).find(opt => opt.value === optionValue);

        if (!option && optionValue) {
            option = document.createElement('option');
            option.value = optionValue;
            option.textContent = match.name || match.label || match.value || text;
            option.dataset.dynamic = 'true';
            selectElement.appendChild(option);
        }

        if (optionValue) {
            selectElement.value = optionValue;
        } else if (match.name) {
            selectElement.value = match.name;
        }

        if (selectElement.id) {
            setAutoFilledValue(selectElement, selectElement.value);
        }

        return true;
    }
    
    console.log('populateSelectFromText: No match found for', normalizedText);
    return false;
}

// Helper function to populate Land Use Type from record
function populateLandUseFromRecord(landUse) {
    if (!landUse) return;
    
    const landUseSelect = document.getElementById('land-use-type');
    if (!landUseSelect) return;
    
    const upperLandUse = landUse.toString().toUpperCase().trim();
    const options = Array.from(landUseSelect.options);

    let matchingOption = options.find(option => option.value.toUpperCase() === upperLandUse)
        || options.find(option => option.textContent.toUpperCase() === upperLandUse)
        || options.find(option => upperLandUse.includes(option.value.toUpperCase()))
        || options.find(option => option.value.toUpperCase().includes(upperLandUse));

    if (!matchingOption && upperLandUse.includes('MIXED')) {
        matchingOption = options.find(option => option.value.toUpperCase().includes('MIXED'));
    }

    if (!matchingOption && upperLandUse.includes('COMMERCIAL') && upperLandUse.includes('RESIDENTIAL')) {
        matchingOption = options.find(option => option.value.toUpperCase().includes('COMMERCIAL') && option.value.toUpperCase().includes('RESIDENTIAL'));
    }

    if (!matchingOption) {
        matchingOption = options.find(option => option.value.toUpperCase().includes(upperLandUse.replace(/[^A-Z]/g, '')));
    }

    if (!matchingOption) {
        const newOption = document.createElement('option');
        newOption.value = upperLandUse;
        newOption.textContent = landUse.toString().trim();
        newOption.dataset.dynamic = 'true';
        landUseSelect.appendChild(newOption);
        matchingOption = newOption;
    }

    if (matchingOption) {
        setAutoFilledValue(landUseSelect, matchingOption.value);
    }
}
</script>

<!-- Include the global file number modal component -->
@include('components.global-fileno-modal')

<!-- Include the global file number modal script -->
<script src="{{ asset('js/global-fileno-modal.js') }}"></script>

<!-- Initialize the file number selector after loading the script -->
<script>
// Initialize the file number selector after the modal script is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Wait for GlobalFileNoModal to be available
    const checkInterval = setInterval(function() {
        if (typeof GlobalFileNoModal !== 'undefined') {
            clearInterval(checkInterval);
            console.log('GlobalFileNoModal is available, initializing');
            
            // Ensure GlobalFileNoModal is initialized
            if (typeof GlobalFileNoModal.init === 'function') {
                GlobalFileNoModal.init();
            }
        }
    }, 100);
});
</script>