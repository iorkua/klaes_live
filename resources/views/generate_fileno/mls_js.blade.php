
    <!-- Custom Styles for Dropdown -->
    <style>
        .dropdown-menu {
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            overflow: hidden;
            animation: fadeIn 0.15s ease-out;
        }
        
        .dropdown-menu button {
            transition: all 0.15s ease;
        }
        
        .dropdown-menu button:hover {
            transform: translateX(2px);
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Ensure dropdown appears above DataTable elements */
        .dropdown-menu {
            z-index: 1050 !important;
        }
    </style>

    <!-- jsPDF Library for PDF Generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    @php
        // Fetch all commissioning sheets and build a normalized lookup map (trimmed, uppercased keys)
        $commissioningSheetsRaw = \DB::connection('sqlsrv')
            ->table('file_commissioning_sheets')
            ->select('id', 'file_number')
            ->get();
        $commissioningSheets = [];
        foreach ($commissioningSheetsRaw as $sheet) {
            $key = strtoupper(trim($sheet->file_number));
            $commissioningSheets[$key] = [
                'id' => $sheet->id,
                'file_number' => $sheet->file_number
            ];
        }
    @endphp
    
    <script>
        // Create a JS object for quick lookup (normalized keys)
        const commissioningSheetsMap = @json($commissioningSheets);
        
        // Helper function to get commissioning sheet id for a row
        function getCommissioningSheetId(row) {
            const fileNumbers = [row.mlsfNo, row.kangisFileNo, row.NewKANGISFileNo]
                .filter(fn => fn && fn !== 'N/A' && fn.trim() !== '')
                .map(fn => fn.trim().toUpperCase());
            // ...
            for (const fn of fileNumbers) {
                if (commissioningSheetsMap[fn]) {
                    return commissioningSheetsMap[fn].id;
                }
            }
            return null;
        }

        function generateTrackingId() {
            const characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let segmentOne = '';
            let segmentTwo = '';

            for (let i = 0; i < 8; i++) {
                segmentOne += characters.charAt(Math.floor(Math.random() * characters.length));
            }

            for (let i = 0; i < 5; i++) {
                segmentTwo += characters.charAt(Math.floor(Math.random() * characters.length));
            }

            return `TRK-${segmentOne}-${segmentTwo}`;
        }

        function refreshTrackingId() {
            const displayEl = document.getElementById('trackingIdDisplay');
            const inputEl = document.getElementById('trackingIdInput');

            if (!displayEl || !inputEl) {
                return;
            }

            const newId = generateTrackingId();
            displayEl.textContent = newId;
            inputEl.value = newId;
        }
    // ...
        
        let table;
        let nextSerialNo = 1;
        let isOverrideMode = false;

        // Loading utility functions
        function showLoadingButton(buttonElement, originalText) {
            if (buttonElement) {
                buttonElement.disabled = true;
                buttonElement.innerHTML = `
                    <i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i>
                    Loading...
                `;
                lucide.createIcons();
            }
        }

        function hideLoadingButton(buttonElement, originalText) {
            if (buttonElement) {
                buttonElement.disabled = false;
                buttonElement.innerHTML = originalText;
                lucide.createIcons();
            }
        }

        function showGlobalLoading(message = 'Processing...') {
            Swal.fire({
                title: message,
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        function hideGlobalLoading() {
            Swal.close();
        }

        $(document).ready(function() {
            refreshTrackingId();
            // Initialize DataTable with performance optimizations
            table = $('#mlsfTable').DataTable({
                processing: true,
                serverSide: true,
                deferRender: true, // Improve performance for large datasets
                stateSave: true, // Save pagination/sorting state
                stateDuration: 300, // 5 minutes state duration
                ajax: {
                    url: '{{ route("file-numbers.data") }}',
                    type: 'GET',
                    timeout: 30000, // 30 second timeout
                    data: function(d) {
                        d.source = 'New'; // Filter for Generated records only
                        console.log('DataTables request:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('DataTables response:', json);
                        if (json.error) {
                            console.error('Server error:', json.error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Server Error',
                                text: json.error,
                                confirmButtonColor: '#ef4444'
                            });
                        }
                        const allRows = json.data || [];
                        // Client-side safety filter: show only Generated records
                        const filtered = allRows.filter(r => (r.type || '').toLowerCase() === 'generated');
                        if (filtered.length !== allRows.length) {
                            console.warn(`Filtered out ${allRows.length - filtered.length} non-generated records from table view.`);
                        }
                        return filtered;
                    },
                    error: function(xhr, error, code) {
                        console.error('DataTables AJAX error:', error);
                        console.error('Status:', xhr.status);
                        console.error('Response:', xhr.responseText);
                        
                        let errorMessage = 'Failed to load file numbers. Please check your connection and try again.';
                        
                        if (xhr.status === 500) {
                            errorMessage = 'Server error occurred. Please contact the administrator.';
                        } else if (xhr.status === 404) {
                            errorMessage = 'Data endpoint not found. Please contact the administrator.';
                        } else if (xhr.status === 0) {
                            errorMessage = 'Network connection error. Please check your internet connection.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error Loading Data',
                            text: errorMessage,
                            confirmButtonColor: '#ef4444',
                            footer: `<small>Error Code: ${xhr.status} - ${error}</small>`
                        });
                    }
                },
                // Optimize DOM structure for better performance
                dom: '<"top"flp>rt<"bottom"ip><"clear">',
                columns: [
                    { 
                        data: 'mlsfNo', 
                        name: 'mlsfNo',
                        title: 'MLS File No',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'kangisFileNo', 
                        name: 'kangisFileNo',
                        title: 'KANGIS File No',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'NewKANGISFileNo', 
                        name: 'NewKANGISFileNo',
                        title: 'New KANGIS File No',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'FileName', 
                        name: 'FileName',
                        title: 'File Name',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'plot_no', 
                        name: 'plot_no',
                        title: 'Plot No',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'tp_no', 
                        name: 'tp_no',
                        title: 'TP No',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'location', 
                        name: 'location',
                        title: 'Location',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'tracking_id', 
                        name: 'tracking_id',
                        title: 'Tracking ID',
                        defaultContent: 'N/A'
                    },
                    { 
                        data: 'created_by', 
                        name: 'created_by',
                        title: 'Commissioned By',
                        defaultContent: 'System'
                    },
                    { 
                        data: 'created_at', 
                        name: 'created_at',
                        title: 'Commission Date',
                        defaultContent: 'N/A',
                        render: function(data, type, row) {
                            if (type === 'display' && data && data !== 'N/A') {
                                const date = new Date(data);
                                return date.toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'short',
                                    day: 'numeric'
                                });
                            }
                            return data || 'N/A';
                        }
                    },
                    { 
                        data: null, 
                        name: 'action', 
                        title: 'Actions',
                        orderable: false, 
                        searchable: false,
                        className: 'text-center',
                        width: '120px',
                        render: function(data, type, row) {
                            // Simplified action buttons for better performance
                            return `
                                <div class="flex justify-center space-x-2">
                                    <button onclick="editRecord(${row.id})" 
                                            class="text-blue-600 hover:text-blue-800 text-sm px-2 py-1 rounded hover:bg-blue-50" 
                                            title="Edit">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="openCommissioningSheet(${row.id}, '${row.mlsfNo}', '${row.FileName}')" 
                                            class="text-green-600 hover:text-green-800 text-sm px-2 py-1 rounded hover:bg-green-50" 
                                            title="Commissioning Sheet">
                                        <i data-lucide="file-text" class="w-4 h-4"></i>
                                    </button>
                                    <button onclick="deleteRecord(${row.id})" 
                                            class="text-red-600 hover:text-red-800 text-sm px-2 py-1 rounded hover:bg-red-50" 
                                            title="Delete">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'desc']],
                pageLength: 20,
                lengthMenu: [[10, 20, 25, 50, 100], [10, 20, 25, 50, 100]],
                responsive: true,
                scrollCollapse: true,
                scroller: {
                    displayBuffer: 9
                },
                searchDelay: 500, // Delay search to reduce server requests
                language: {
                    processing: '<div class="flex items-center justify-center"><i data-lucide="loader" class="w-4 h-4 mr-2 animate-spin"></i>Loading file numbers...</div>',
                    emptyTable: '<div class="text-center py-8"><div class="text-gray-400 mb-2"><i data-lucide="database" class="w-12 h-12 mx-auto mb-2"></i></div><h3 class="text-lg font-medium text-gray-900 mb-1">No file numbers found</h3><p class="text-gray-500">Start by generating your first MLS file number using the button above.</p></div>',
                    zeroRecords: '<div class="text-center py-8"><div class="text-gray-400 mb-2"><i data-lucide="search" class="w-12 h-12 mx-auto mb-2"></i></div><h3 class="text-lg font-medium text-gray-900 mb-1">No matching records found</h3><p class="text-gray-500">Try adjusting your search criteria.</p></div>',
                    info: "Showing _START_ to _END_ of _TOTAL_ file numbers",
                    infoEmpty: "No file numbers available",
                    infoFiltered: "(filtered from _MAX_ total file numbers)",
                    lengthMenu: "Show _MENU_ file numbers per page",
                    search: "Search file numbers:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                drawCallback: function(settings) {
                    // Optimized icon initialization - use requestAnimationFrame for better performance
                    requestAnimationFrame(function() {
                        lucide.createIcons();
                    });
                },
                initComplete: function(settings, json) {
                    console.log('DataTable initialized:', {
                        recordsTotal: json?.recordsTotal || 0,
                        recordsFiltered: json?.recordsFiltered || 0,
                        dataLength: json?.data?.length || 0
                    });
                    
                    // Show a message if no data is available
                    if (json && json.recordsTotal === 0) {
                        console.log('No records found in database');
                    }
                }
            });

            // Get next serial number
            getNextSerialNumber();
            
            // Load existing file numbers for extension dropdown
            loadExistingFileNumbers();
        });

        function openGenerateModal() {
            refreshTrackingId();
            document.getElementById('generateModal').classList.remove('hidden');
            
            // Ensure the serial number is properly set when modal opens
            setTimeout(() => {
                updateAlpineSerialNumber();
            }, 100);
        }

        function closeGenerateModal() {
            document.getElementById('generateModal').classList.add('hidden');
        }

        function resetForm() {
            // Reset the main form
            const form = document.getElementById('generateForm');
            if (form) {
                form.reset();
            }
            
            // Reset Alpine.js component data
            const modalContainer = document.querySelector('[x-data="fileNumberGenerator()"]');
            if (modalContainer && modalContainer._x_dataStack && modalContainer._x_dataStack[0]) {
                const component = modalContainer._x_dataStack[0];
                
                // Reset all form fields to initial values
                component.applicationType = 'new';
                component.fileName = '';
                component.landUse = '';
                component.fileOption = '';
                component.existingFileNo = '';
                component.middlePrefix = 'KN';
                component.year = new Date().getFullYear();
                component.serialNo = nextSerialNo;
                component.preview = '-';
                
                // Update the preview
                component.updatePreview();
            }
            
            // Reset override mode
            isOverrideMode = false;
            
            // Reset form field states
            const yearInput = document.getElementById('year');
            const serialInput = document.getElementById('serialNo');
            
            if (yearInput) {
                yearInput.readOnly = true;
                yearInput.classList.remove('bg-white', 'text-gray-900');
                yearInput.classList.add('bg-gray-100', 'text-gray-600');
                yearInput.value = new Date().getFullYear();
            }
            
            if (serialInput) {
                serialInput.readOnly = true;
                serialInput.classList.remove('bg-white', 'text-gray-900');
                serialInput.classList.add('bg-gray-100', 'text-gray-600');
                serialInput.value = nextSerialNo;
            }
            
            // Reset radio buttons to default (Direct Allocation)
            const newRadio = document.querySelector('input[name="application_type"][value="new"]');
            if (newRadio) {
                newRadio.checked = true;
            }
            
            // Clear all text inputs
            const textInputs = form.querySelectorAll('input[type="text"]:not([disabled])');
            textInputs.forEach(input => {
                if (input.id !== 'middlePrefix') { // Keep middle prefix as 'KN'
                    input.value = '';
                }
            });
            
            // Reset all select dropdowns
            const selects = form.querySelectorAll('select');
            selects.forEach(select => {
                select.selectedIndex = 0;
            });
            
            // Reset hidden sections
            document.getElementById('middlePrefixSection')?.classList.add('hidden');
            document.getElementById('yearSection')?.classList.add('hidden');
            
            // Reset manual input toggles to default (use dropdown) - Alpine.js will handle this automatically
            // when the component data is reset, but we can also trigger it manually
            setTimeout(() => {
                const extensionInputRadios = form.querySelectorAll('input[name="extension_input_type"][value="false"]');
                extensionInputRadios.forEach(radio => {
                    radio.checked = true;
                });

                const temporaryInputRadios = form.querySelectorAll('input[name="temporary_input_type"][value="false"]');
                temporaryInputRadios.forEach(radio => {
                    radio.checked = true;
                });
            }, 100);
            
            // Update the preview one more time
            updatePreview();
            refreshTrackingId();
        }

        function updateApplicationType(type) {
            const newOptions = document.getElementById('newOptions');
            const conversionOptions = document.getElementById('conversionOptions');
            const rcOptions = document.querySelector('optgroup[label="RC Options"]');
            const landUseSelect = document.getElementById('landUse');
            
            if (type === 'new') {
                newOptions.style.display = 'block';
                conversionOptions.style.display = 'none';
                rcOptions.style.display = 'block';
            } else {
                newOptions.style.display = 'none';
                conversionOptions.style.display = 'block';
                rcOptions.style.display = 'none'; // Hide RC options for conversion
            }
            
            // Reset land use selection
            landUseSelect.value = '';
            updatePreview();
        }

        // Separate function for just updating preview without form manipulation
        function updatePreviewOnly() {
            const serialNo = document.getElementById('serialNo')?.value;
            const year = document.getElementById('year')?.value;
            const landUse = document.getElementById('landUse')?.value;
            const fileOption = document.getElementById('fileOption')?.value;
            
            // Get existing file number from Alpine.js component instead of DOM
            let existingFileNo = '';
            const modalContainer = document.querySelector('[x-data="fileNumberGenerator()"]');
            if (modalContainer && modalContainer._x_dataStack && modalContainer._x_dataStack[0]) {
                existingFileNo = modalContainer._x_dataStack[0].existingFileNo;
            }
            
            const middlePrefix = document.getElementById('middlePrefix')?.value || '';
            const preview = document.getElementById('mlsfPreview');

            // Preview generation logic only
            let previewText = '-';
            
            if (fileOption === 'extension' && existingFileNo) {
                previewText = existingFileNo + ' AND EXTENSION';
            } else if (fileOption === 'temporary' && existingFileNo) {
                previewText = existingFileNo + '(T)';
            } else if (fileOption === 'miscellaneous' && middlePrefix && serialNo && year) {
                previewText = `MISC-${middlePrefix}-${year}-${serialNo}`;
            } else if (fileOption === 'old_mls' && serialNo) {
                previewText = `KN ${serialNo}`;
            } else if (fileOption === 'sltr' && serialNo) {
                previewText = `SLTR-${serialNo}`;
            } else if (fileOption === 'sit' && serialNo) {
                previewText = `SIT-${year}-${serialNo}`;
            } else if (fileOption === 'normal' && serialNo && year && landUse) {
                previewText = `${landUse}-${year}-${serialNo}`;
            }
            
            if (preview) {
                preview.textContent = previewText;
                
                if (previewText !== '-') {
                    preview.classList.remove('text-gray-400');
                    preview.classList.add('text-green-600');
                } else {
                    preview.classList.remove('text-green-600');
                    preview.classList.add('text-gray-400');
                }
            }
        }

        function updatePreview() {
            const serialNo = document.getElementById('serialNo')?.value;
            const year = document.getElementById('year')?.value;
            const landUse = document.getElementById('landUse')?.value;
            const fileOption = document.getElementById('fileOption')?.value;
            
            // Get existing file number from Alpine.js component instead of DOM
            let existingFileNo = '';
            const modalContainer = document.querySelector('[x-data="fileNumberGenerator()"]');
            if (modalContainer && modalContainer._x_dataStack && modalContainer._x_dataStack[0]) {
                existingFileNo = modalContainer._x_dataStack[0].existingFileNo;
            }
            
            const middlePrefix = document.getElementById('middlePrefix')?.value || '';
            const preview = document.getElementById('mlsfPreview');
            const serialNoField = document.getElementById('serialNo');
            const serialDescription = document.getElementById('serialNoDescription');

            // Only call updateGenerateForm if elements exist and not during text input for miscellaneous files
            if (document.getElementById('middlePrefixSection') && 
                document.getElementById('yearSection')) {
                // Don't reset form if we're actively typing in miscellaneous serial field
                const isTypingInMiscSerial = fileOption === 'miscellaneous' && 
                                           document.activeElement === serialNoField &&
                                           serialNoField.getAttribute('data-text-field') === 'true';
                
                if (!isTypingInMiscSerial) {
                    updateGenerateForm(fileOption);
                }
            }
            
            // Preview generation logic - exact same as capture_existing
            let previewText = '-';
            
            if (fileOption === 'extension' && existingFileNo) {
                previewText = existingFileNo + ' AND EXTENSION';
            } else if (fileOption === 'temporary' && existingFileNo) {
                previewText = existingFileNo + '(T)';
            } else if (fileOption === 'miscellaneous' && middlePrefix && serialNo && year) {
                previewText = `MISC-${middlePrefix}-${year}-${serialNo}`;
            } else if (fileOption === 'old_mls' && serialNo) {
                previewText = `KN ${serialNo}`;
            } else if (fileOption === 'sltr' && serialNo) {
                previewText = `SLTR-${serialNo}`;
            } else if (fileOption === 'sit' && serialNo) {
                previewText = `SIT-${year}-${serialNo}`;
            } else if (fileOption === 'normal' && serialNo && year && landUse) {
                previewText = `${landUse}-${year}-${serialNo}`;
            }
            
            preview.textContent = previewText;
            
            // Update Alpine.js preview property
            if (modalContainer && modalContainer._x_dataStack && modalContainer._x_dataStack[0]) {
                modalContainer._x_dataStack[0].preview = previewText;
            }
            
            if (previewText !== '-') {
                preview.classList.remove('text-gray-400');
                preview.classList.add('text-green-600');
            } else {
                preview.classList.remove('text-green-600');
                preview.classList.add('text-gray-400');
            }
        }

        // Add the dedicated form update function like capture_existing
        function updateGenerateForm(type) {
            const middlePrefixSection = document.getElementById('middlePrefixSection');
            const yearSection = document.getElementById('yearSection');
            const serialNoField = document.getElementById('serialNo');
            const serialDescription = document.getElementById('serialNoDescription');
            
            // Hide all sections first
            middlePrefixSection.classList.add('hidden');
            yearSection.classList.add('hidden');
            
            // Reset serial number field properties and remove all restrictive attributes
            serialNoField.type = 'text';
            serialNoField.removeAttribute('min');
            serialNoField.removeAttribute('max');
            serialNoField.removeAttribute('step');
            serialNoField.removeAttribute('maxlength');
            serialNoField.removeAttribute('pattern');
            
            // Reset text field tracking attribute
            serialNoField.removeAttribute('data-text-field');
            
            if (type === 'normal') {
                yearSection.classList.remove('hidden');
                // For normal files, keep serial as number for auto-padding
                serialNoField.type = 'number';
                serialNoField.setAttribute('min', '1');
                serialNoField.setAttribute('max', '9999');
                serialNoField.placeholder = 'Auto-generated';
                serialNoField.readOnly = true;
                serialNoField.classList.add('bg-gray-100', 'text-gray-600');
                serialNoField.classList.remove('bg-white', 'text-gray-900');
                serialNoField.value = nextSerialNo;
                
                if (serialDescription) {
                    serialDescription.textContent = 'Auto-generated';
                    serialDescription.className = 'text-xs text-gray-500 mt-1';
                }
            } else if (type === 'temporary') {
                // Temporary files use the toggle section in right column
                serialNoField.placeholder = 'Not required for temporary files';
                serialNoField.value = '';
                serialNoField.readOnly = true;
                serialNoField.classList.add('bg-gray-100', 'text-gray-600');
                serialNoField.classList.remove('bg-white', 'text-gray-900');
                
                if (serialDescription) {
                    serialDescription.textContent = 'Select existing file for temporary version';
                    serialDescription.className = 'text-xs text-blue-600 mt-1 font-medium';
                }
            } else if (type === 'extension') {
                // Extension files use the toggle section in right column
                yearSection.classList.remove('hidden');
                serialNoField.placeholder = 'Not required for extensions';
                serialNoField.value = '';
                serialNoField.readOnly = true;
                serialNoField.classList.add('bg-gray-100', 'text-gray-600');
                serialNoField.classList.remove('bg-white', 'text-gray-900');
                
                if (serialDescription) {
                    serialDescription.textContent = 'Not required for extensions';
                    serialDescription.className = 'text-xs text-gray-500 mt-1';
                }
            } else if (type === 'miscellaneous' || type === 'sltr' || type === 'sit' || type === 'old_mls') {
                if (type === 'miscellaneous') {
                    middlePrefixSection.classList.remove('hidden');
                    yearSection.classList.remove('hidden');
                } else if (type === 'sit') {
                    yearSection.classList.remove('hidden');
                }
                // Make serial number plain text and editable for these types
                serialNoField.type = 'text';
                serialNoField.readOnly = false;
                serialNoField.classList.remove('bg-gray-100', 'text-gray-600');
                serialNoField.classList.add('bg-white', 'text-gray-900');
                
                // Only clear value if it's not already in a text field state (initial setup)
                if (serialNoField.getAttribute('data-text-field') !== 'true') {
                    serialNoField.value = '';
                    serialNoField.setAttribute('data-text-field', 'true');
                }
                
                // Completely remove all input restrictions for text fields
                serialNoField.removeAttribute('min');
                serialNoField.removeAttribute('max');
                serialNoField.removeAttribute('step');
                serialNoField.removeAttribute('maxlength');
                serialNoField.removeAttribute('pattern');
                serialNoField.setAttribute('inputmode', 'text');
                
                if (type === 'miscellaneous') {
                    serialNoField.placeholder = 'Enter custom serial (e.g., 001, ABC123)';
                } else if (type === 'sltr') {
                    serialNoField.placeholder = 'Enter SLTR serial (e.g., 001, 2024-001)';
                } else if (type === 'sit') {
                    serialNoField.placeholder = 'Enter SIT serial (e.g., 001, 2024-001)';
                } else if (type === 'old_mls') {
                    serialNoField.placeholder = 'Enter Old MLS number (e.g., 5467, 34874857488758)';
                }
                
                if (serialDescription) {
                    serialDescription.textContent = 'Manual entry';
                    serialDescription.className = 'text-xs text-blue-600 mt-1 font-medium';
                }
            }
        }

        function loadExistingFileNumbers() {
            fetch('{{ route("file-numbers.existing") }}')
                .then(response => response.json())
                .then(data => {
                    // Populate extension dropdown
                    const extensionSelect = document.getElementById('extensionFileNo');
                    if (extensionSelect) {
                        extensionSelect.innerHTML = '<option value="">Select existing file number...</option>';
                        
                        data.forEach(fileNo => {
                            const option = document.createElement('option');
                            option.value = fileNo.mlsfNo;
                            option.textContent = fileNo.mlsfNo;
                            extensionSelect.appendChild(option);
                        });
                    }

                    // Populate temporary dropdown
                    const temporarySelect = document.getElementById('temporaryFileNo');
                    if (temporarySelect) {
                        temporarySelect.innerHTML = '<option value="">Select existing file number...</option>';
                        
                        data.forEach(fileNo => {
                            const option = document.createElement('option');
                            option.value = fileNo.mlsfNo;
                            option.textContent = fileNo.mlsfNo;
                            temporarySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading existing file numbers:', error);
                });
        }

        function getNextSerialNumber() {
            const currentYear = new Date().getFullYear();
            
            fetch(`{{ route("file-numbers.next-serial") }}?year=${currentYear}`)
                .then(response => response.json())
                .then(data => {
                    nextSerialNo = data.nextSerial;
                    console.log('Next serial number fetched:', nextSerialNo);
                    
                    // Update the Alpine component if the modal is open
                    const modalElement = document.getElementById('generateModal');
                    if (modalElement && !modalElement.classList.contains('hidden')) {
                        updateAlpineSerialNumber();
                    }
                })
                .catch(error => {
                    console.error('Error getting next serial number:', error);
                    // Fallback to serial number 1 if fetch fails
                    nextSerialNo = 1;
                });
        }
        
        // Function to update Alpine.js component with new serial number
        function updateAlpineSerialNumber() {
            // Try to update via Alpine component method
            const modalContainer = document.querySelector('[x-data="fileNumberGenerator()"]');
            if (modalContainer && modalContainer._x_dataStack && modalContainer._x_dataStack[0]) {
                const component = modalContainer._x_dataStack[0];
                if (component.refreshSerialNumber) {
                    component.refreshSerialNumber();
                    return;
                }
            }
            
            // Fallback to direct DOM manipulation
            const serialNoElement = document.getElementById('serialNo');
            if (serialNoElement && nextSerialNo && !isOverrideMode) {
                serialNoElement.value = nextSerialNo;
                // Trigger both input and change events to ensure Alpine.js updates
                serialNoElement.dispatchEvent(new Event('input', { bubbles: true }));
                serialNoElement.dispatchEvent(new Event('change', { bubbles: true }));
                updatePreview();
            }
        }

        function showOverrideModal() {
            document.getElementById('overrideModal').classList.remove('hidden');
            document.getElementById('overrideYear').value = document.getElementById('year').value;
            document.getElementById('overrideSerialNo').value = document.getElementById('serialNo').value;
        }

        function closeOverrideModal() {
            document.getElementById('overrideModal').classList.add('hidden');
        }

        function submitOverrideForm(event) {
            event.preventDefault();
            
            const overrideYear = document.getElementById('overrideYear').value;
            const overrideSerialNo = document.getElementById('overrideSerialNo').value;
            const overrideExtension = document.getElementById('overrideExtension').checked;
            
            // Apply override values to main form
            const yearInput = document.getElementById('year');
            const serialInput = document.getElementById('serialNo');
            
            yearInput.value = overrideYear;
            serialInput.value = overrideSerialNo;
            
            // Enable manual editing
            isOverrideMode = true;
            yearInput.readOnly = false;
            serialInput.readOnly = false;
            yearInput.classList.remove('bg-gray-100', 'text-gray-600');
            serialInput.classList.remove('bg-gray-100', 'text-gray-600');
            yearInput.classList.add('bg-white', 'text-gray-900');
            serialInput.classList.add('bg-white', 'text-gray-900');
            
            // Trigger events to update Alpine.js state
            yearInput.dispatchEvent(new Event('input', { bubbles: true }));
            serialInput.dispatchEvent(new Event('input', { bubbles: true }));
            
            if (overrideExtension) {
                document.querySelector('input[name="file_option"][value="extension"]').checked = true;
            }
            
            updatePreview();
            closeOverrideModal();
        }

        function openMigrationModal() {
            document.getElementById('migrationModal').classList.remove('hidden');
        }

        function closeMigrationModal() {
            document.getElementById('migrationModal').classList.add('hidden');
        }

        function submitMigrationForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Migrating data... Please wait.');
            
            const formData = new FormData(document.getElementById('migrationForm'));
            
            fetch('{{ route("file-numbers.migrate") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    closeMigrationModal();
                    table.ajax.reload();
                    updateTotalCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred during migration',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while migrating data',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function submitForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Generating file number...');
            
            const formData = new FormData(document.getElementById('generateForm'));
            
            // Debug: Log the form data
            console.log('Form data being submitted:');
            for (let [key, value] of formData.entries()) {
                console.log(`${key}: ${value}`);
            }
            
            fetch('{{ route("file-numbers.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    // Update next serial number first
                    getNextSerialNumber();
                    
                    // Clear cache to ensure fresh data on next load
                    fetch('{{ route("file-numbers.clear-cache") }}', { method: 'POST', 
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') }
                    }).catch(e => console.warn('Cache clear failed:', e));
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    
                    // Reset the form and close modal
                    resetForm();
                    closeGenerateModal();
                    table.ajax.reload();
                    updateTotalCount();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while generating the file number',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function editRecord(id) {
            // Show loading while fetching record details
            showGlobalLoading('Loading record details...');
            
            fetch(`{{ route("file-numbers.show", ":id") }}`.replace(':id', id))
                .then(response => response.json())
                .then(data => {
                    hideGlobalLoading();
                    document.getElementById('editId').value = data.id;
                    document.getElementById('editMlsfNo').value = data.mlsfNo || data.kangisFileNo;
                    document.getElementById('editFileName').value = data.FileName || '';
                    document.getElementById('editModal').classList.remove('hidden');
                })
                .catch(error => {
                    hideGlobalLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to load record details',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function submitEditForm(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            // Show global loading
            showGlobalLoading('Updating record...');
            
            const id = document.getElementById('editId').value;
            const formData = new FormData(document.getElementById('editForm'));
            
            fetch(`{{ route("file-numbers.update", ":id") }}`.replace(':id', id), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        confirmButtonColor: '#10b981'
                    });
                    closeEditModal();
                    table.ajax.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'An error occurred',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while updating the record',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function deleteRecord(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it!',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(`{{ route("file-numbers.destroy", ":id") }}`.replace(':id', id), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Content-Type': 'application/json'
                        }
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(response.statusText);
                        }
                        return response.json();
                    })
                    .catch(error => {
                        Swal.showValidationMessage(
                            `Request failed: ${error}`
                        );
                    });
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    const data = result.value;
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            confirmButtonColor: '#10b981'
                        });
                        table.ajax.reload();
                        updateTotalCount();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'An error occurred',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                }
            });
        }

        function updateTotalCount() {
            fetch('{{ route("file-numbers.count") }}')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('totalCount').textContent = data.count;
                })
                .catch(error => {
                    console.error('Error updating count:', error);
                });
        }

        function testDatabaseConnection() {
            // Show loading for database test
            showGlobalLoading('Testing database connection...');
            
            fetch('{{ route("file-numbers.test-db") }}')
                .then(response => response.json())
                .then(data => {
                    hideGlobalLoading();
                    
                    if (data.success) {
                        let message = `Database Connection Test Results:\n\n`;
                        message += `✅ Connection: ${data.connection}\n`;
                        message += `✅ Database: ${data.database_name}\n`;
                        message += `✅ Table Exists: ${data.table_exists ? 'Yes' : 'No'}\n`;
                        message += `✅ Record Count: ${data.record_count}\n`;
                        message += `✅ Server: ${data.server_info.substring(0, 50)}...\n\n`;
                        
                        if (data.columns && data.columns.length > 0) {
                            message += `Table Columns:\n`;
                            data.columns.forEach(col => {
                                message += `- ${col.COLUMN_NAME} (${col.DATA_TYPE})\n`;
                            });
                        }
                        
                        if (data.sample_records && data.sample_records.length > 0) {
                            message += `\nSample Records:\n`;
                            data.sample_records.forEach((record, index) => {
                                message += `${index + 1}. ${record.mlsfNo || record.kangisFileNo || 'No ID'}\n`;
                            });
                        }
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Database Test Successful',
                            text: message,
                            confirmButtonColor: '#10b981',
                            customClass: {
                                content: 'text-left'
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Database Test Failed',
                            text: data.error || 'Unknown error occurred',
                            confirmButtonColor: '#ef4444',
                            footer: '<small>Check the browser console for more details</small>'
                        });
                        console.error('Database test error:', data);
                    }
                })
                .catch(error => {
                    hideGlobalLoading();
                    console.error('Database test error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Database Test Failed',
                        text: 'Failed to connect to test endpoint: ' + error.message,
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        function debugTableData() {
            // Show loading for debug data
            showGlobalLoading('Debugging table data...');
            
            fetch('{{ route("file-numbers.debug-data") }}')
                .then(response => response.json())
                .then(data => {
                    hideGlobalLoading();
                    
                    if (data.success) {
                        console.log('Raw Data:', data.raw_data);
                        console.log('Formatted Data:', data.formatted_data);
                        
                        let message = `Debug Data Results:\n\n`;
                        message += `Raw Records Found: ${data.raw_data.length}\n`;
                        message += `Formatted Records: ${data.formatted_data.length}\n\n`;
                        
                        if (data.raw_data.length > 0) {
                            message += `Raw Data Sample:\n`;
                            data.raw_data.slice(0, 3).forEach((record, index) => {
                                message += `${index + 1}. ID: ${record.id}\n`;
                                message += `   kangisFileNo: "${record.kangisFileNo}"\n`;
                                message += `   NewKANGISFileNo: "${record.NewKANGISFileNo}"\n`;
                                message += `   FileName: "${record.FileName}"\n`;
                                message += `   mlsfNo: "${record.mlsfNo}"\n\n`;
                            });
                        }
                        
                        if (data.formatted_data.length > 0) {
                            message += `Formatted Data Sample:\n`;
                            data.formatted_data.slice(0, 3).forEach((record, index) => {
                                message += `${index + 1}. ID: ${record.id}\n`;
                                message += `   kangisFileNo: "${record.kangisFileNo}"\n`;
                                message += `   NewKANGISFileNo: "${record.NewKANGISFileNo}"\n`;
                                message += `   FileName: "${record.FileName}"\n`;
                                message += `   mlsfNo: "${record.mlsfNo}"\n\n`;
                            });
                        }
                        
                        Swal.fire({
                            icon: 'info',
                            title: 'Debug Data Results',
                            text: message,
                            confirmButtonColor: '#8b5cf6',
                            customClass: {
                                content: 'text-left'
                            },
                            width: '600px'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Debug Failed',
                            text: data.error || 'Unknown error occurred',
                            confirmButtonColor: '#ef4444'
                        });
                        console.error('Debug error:', data);
                    }
                })
                .catch(error => {
                    hideGlobalLoading();
                    console.error('Debug error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Debug Failed',
                        text: 'Failed to connect to debug endpoint: ' + error.message,
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        // Simplified commissioning sheet function for better performance
        function openCommissioningSheet(recordId, fileNo, fileName) {
            // Pre-fill the commissioning sheet modal with available data
            document.getElementById('cs_file_number').value = fileNo || '';
            document.getElementById('cs_file_name').value = fileName || '';
            document.getElementById('cs_name_allottee').value = fileName || '';
            
            // Open the modal
            document.getElementById('commissioningSheetModal').classList.remove('hidden');
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                closeAllDropdowns();
            }
        });

        // Add event listeners for form inputs
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('serialNo').addEventListener('input', updatePreview);
            document.getElementById('year').addEventListener('input', updatePreview);
            document.getElementById('landUse').addEventListener('change', updatePreview);
            
            // Add event listeners for file option dropdown
            document.getElementById('fileOption').addEventListener('change', updatePreview);
            
            // Add event listener for existing file number dropdown
            document.getElementById('existingFileNo').addEventListener('change', updatePreview);

            // Add event for middle prefix input if present
            const middlePrefixEl = document.getElementById('middlePrefix');
            if (middlePrefixEl) {
                middlePrefixEl.addEventListener('input', updatePreview);
            }
        });

        // Alpine.js component for file number generator
        function fileNumberGenerator() {
            return {
                // Data properties
                applicationType: 'new',
                fileName: '',
                landUse: '',
                // start with no selection so the placeholder "Select File Option" shows
                fileOption: '',
                existingFileNo: '',
                middlePrefix: 'KN',
                year: new Date().getFullYear(),
                serialNo: '',
                plotNo: '',
                tpNo: '',
                location: '',
                preview: '-',
                
                // Computed properties
                get showYearSection() {
                    return this.fileOption !== 'old_mls' && this.fileOption !== 'sltr';
                },
                
                get isYearEditable() {
                    return isOverrideMode;
                },
                
                get isSerialEditable() {
                    return this.fileOption === 'miscellaneous' || this.fileOption === 'sltr' || this.fileOption === 'sit' || this.fileOption === 'old_mls' || isOverrideMode;
                },
                
                get isSerialReadonly() {
                    // Readonly for normal (auto-generated), temporary and extension types
                    return (this.fileOption === 'normal' || this.fileOption === 'temporary' || this.fileOption === 'extension') && !isOverrideMode;
                },
                
                get isSerialDisabled() {
                    // Disable for extension and temporary types (not needed)
                    return (this.fileOption === 'extension' || this.fileOption === 'temporary') && !isOverrideMode;
                },
                
                get serialFieldType() {
                    return this.fileOption === 'normal' && !isOverrideMode ? 'number' : 'text';
                },
                
                get yearFieldClass() {
                    return this.isYearEditable ? 'w-full px-3 py-2 border border-gray-300 rounded-md text-gray-900' : 'w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-600';
                },
                
                get serialFieldClass() {
                    if (this.isSerialDisabled) {
                        return 'w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-md text-gray-500 cursor-not-allowed';
                    } else if (this.isSerialReadonly) {
                        return 'w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-700';
                    } else {
                        return 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900';
                    }
                },
                
                get serialPlaceholder() {
                    if (this.fileOption === 'extension') {
                        return 'Not required for extensions';
                    } else if (this.fileOption === 'temporary') {
                        return 'Not required for temporary files';
                    } else if (this.fileOption === 'miscellaneous') {
                        return 'Enter custom serial (e.g., 001, ABC123)';
                    } else if (this.fileOption === 'sltr') {
                        return 'Enter SLTR serial (e.g., 001, 2024-001)';
                    } else if (this.fileOption === 'sit') {
                        return 'Enter SIT serial (e.g., 001, 2024-001)';
                    } else if (this.fileOption === 'old_mls') {
                        return 'Enter Old MLS number (e.g., 5467, 34874857488758)';
                    } else {
                        return 'Auto-generated';
                    }
                },
                
                get serialDescription() {
                    if (this.fileOption === 'extension') {
                        return 'Not required for extensions';
                    } else if (this.fileOption === 'temporary') {
                        return 'Select existing file for temporary version';
                    } else if (this.fileOption === 'miscellaneous' || this.fileOption === 'sltr' || this.fileOption === 'sit' || this.fileOption === 'old_mls') {
                        return 'Manual entry ';
                    } else {
                        return 'Auto-generated';
                    }
                },
                
                get serialDescriptionClass() {
                    if (this.fileOption === 'miscellaneous' || this.fileOption === 'sltr' || this.fileOption === 'sit' || this.fileOption === 'old_mls' || this.fileOption === 'temporary') {
                        return 'text-blue-600 font-medium';
                    } else {
                        return 'text-gray-500';
                    }
                },
                
                get yearDescription() {
                    return this.isYearEditable ? 'Editable' : 'Auto-filled';
                },
                
                get previewClass() {
                    return this.preview !== '-' ? 'text-green-600' : 'text-gray-400';
                },
                
                // Methods
                init() {
                    // Initialize with nextSerialNo, it will be cleared when user selects specific file options
                    this.serialNo = nextSerialNo;
                    this.updatePreview();
                },
                
                updateApplicationType() {
                    this.landUse = '';
                    // Reset file option to normal if SIT is selected but conversion type is chosen
                    if (this.fileOption === 'sit' && this.applicationType === 'conversion') {
                        this.fileOption = 'normal';
                    }
                    this.updatePreview();
                },
                
                updateFileOption() {
                    // Clear serial number when changing file option to special types
                    if (this.fileOption === 'miscellaneous' || this.fileOption === 'sltr' || this.fileOption === 'sit' || this.fileOption === 'old_mls') {
                        this.serialNo = '';
                    } else if (this.fileOption === 'extension' || this.fileOption === 'temporary') {
                        this.serialNo = '';
                        this.existingFileNo = ''; // Clear existing file selection
                    } else if (this.fileOption === 'normal') {
                        // Reset to auto-generated for normal files only
                        this.serialNo = nextSerialNo;
                    }
                    
                    this.updatePreview();
                },
                
                updatePreview() {
                    let previewText = '-';
                    
                    if (this.fileOption === 'extension' && this.existingFileNo) {
                        previewText = this.existingFileNo + ' AND EXTENSION';
                    } else if (this.fileOption === 'temporary' && this.existingFileNo) {
                        previewText = this.existingFileNo + '(T)';
                    } else if (this.fileOption === 'miscellaneous' && this.middlePrefix && this.serialNo && this.year) {
                        previewText = `MISC-${this.middlePrefix}-${this.year}-${this.serialNo}`;
                    } else if (this.fileOption === 'old_mls' && this.serialNo) {
                        previewText = `KN ${this.serialNo}`;
                    } else if (this.fileOption === 'sltr' && this.serialNo) {
                        previewText = `SLTR-${this.serialNo}`;
                    } else if (this.fileOption === 'sit' && this.serialNo) {
                        previewText = `SIT-${this.year}-${this.serialNo}`;
                    } else if (this.fileOption === 'normal' && this.serialNo && this.year && this.landUse) {
                        previewText = `${this.landUse}-${this.year}-${this.serialNo}`;
                    }
                    
                    this.preview = previewText;
                },
                
                // Method to refresh serial number from external call
                refreshSerialNumber() {
                    if ((this.fileOption === 'normal' || this.fileOption === '') && !isOverrideMode) {
                        this.serialNo = nextSerialNo;
                        this.updatePreview();
                    }
                }
            }
        }

        // Commissioning Sheet Functions
        function openCommissioningSheetModal() {
            document.getElementById('commissioningSheetModal').classList.remove('hidden');
        }
        
        // Function to handle commissioning sheet from dropdown
        function openCommissioningSheetFromDropdown(button) {
            openCommissioningSheetForRowData(button);
        }
        
        // Function to handle commissioning sheet with row data
        function openCommissioningSheetForRowData(button) {
            try {
                // Get data from button attributes
                const mlsfNo = button.getAttribute('data-mlsf-no');
                const kangisNo = button.getAttribute('data-kangis-no');
                const newKangisNo = button.getAttribute('data-new-kangis-no');
                const fileName = button.getAttribute('data-file-name');
                const plotNo = button.getAttribute('data-plot-no');
                const tpNo = button.getAttribute('data-tp-no');
                const location = button.getAttribute('data-location');
                
                console.log('Button data attributes:', {
                    mlsfNo, kangisNo, newKangisNo, fileName, plotNo, tpNo, location
                });
                
                // Determine the best file number to use (prioritize mlsfNo, then kangisNo, then newKangisNo)
                let fileNumber = '';
                if (mlsfNo && mlsfNo !== 'N/A' && mlsfNo.trim() !== '') {
                    fileNumber = mlsfNo;
                } else if (kangisNo && kangisNo !== 'N/A' && kangisNo.trim() !== '') {
                    fileNumber = kangisNo;
                } else if (newKangisNo && newKangisNo !== 'N/A' && newKangisNo.trim() !== '') {
                    fileNumber = newKangisNo;
                }
                
                // Clean up other fields (replace 'N/A' with empty string)
                const cleanFileName = (fileName && fileName !== 'N/A') ? fileName : '';
                const cleanPlotNo = (plotNo && plotNo !== 'N/A') ? plotNo : '';
                const cleanTpNo = (tpNo && tpNo !== 'N/A') ? tpNo : '';
                const cleanLocation = (location && location !== 'N/A') ? location : '';
                
                openCommissioningSheetForFile(fileNumber, cleanFileName, cleanPlotNo, cleanTpNo, cleanLocation);
                
            } catch (error) {
                console.error('Error reading button data:', error);
                // Fallback - just open the modal without pre-filling
                openCommissioningSheetModal();
            }
        }

        function openCommissioningSheetForFile(fileNumber, fileName, plotNo, tpNo, location) {
            // Debug logging
            console.log('Opening commissioning sheet with data:', {
                fileNumber: fileNumber,
                fileName: fileName,
                plotNo: plotNo,
                tpNo: tpNo,
                location: location
            });
            
            // Open the modal
            document.getElementById('commissioningSheetModal').classList.remove('hidden');
            
            // Pre-fill the form with file data
            document.getElementById('cs_file_number').value = fileNumber || '';
            document.getElementById('cs_file_name').value = fileName || '';
            document.getElementById('cs_plot_number').value = plotNo || '';
            document.getElementById('cs_tp_number').value = tpNo || '';
            document.getElementById('cs_location').value = location || '';
            
            // Set today's date
            document.getElementById('cs_date_created').value = new Date().toISOString().split('T')[0];
            
            // Set current user if available
            @if(Auth::check())
                document.getElementById('cs_created_by').value = '{{ Auth::user()->name }}';
            @endif
            
            // Set allottee field to same value as file name
            document.getElementById('cs_name_allottee').value = fileName || '';
            
            // Show data load status if any data was pre-filled
            const statusElement = document.getElementById('dataLoadStatus');
            if (fileNumber || fileName || plotNo || tpNo || location) {
                statusElement.classList.remove('hidden');
                setTimeout(() => {
                    statusElement.classList.add('hidden');
                }, 5000); // Hide after 5 seconds
            }
        }

        function closeCommissioningSheetModal() {
            document.getElementById('commissioningSheetModal').classList.add('hidden');
            // Hide status message
            document.getElementById('dataLoadStatus').classList.add('hidden');
            // Reset form
            document.getElementById('commissioningSheetForm').reset();
            // Set today's date
            document.getElementById('cs_date_created').value = new Date().toISOString().split('T')[0];
        }

        function submitCommissioningSheet(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading on button
            showLoadingButton(submitBtn, originalText);
            
            const formData = new FormData(document.getElementById('commissioningSheetForm'));
            
            fetch('{{ route("commissioning-sheet.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideLoadingButton(submitBtn, originalText);
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Commissioning sheet saved successfully',
                        confirmButtonColor: '#10b981'
                    }).then(() => {
                        closeCommissioningSheetModal();
                        // Reload the table to update the commissioning sheet status
                        if (typeof table !== 'undefined' && table.ajax) {
                            table.ajax.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to save commissioning sheet',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideLoadingButton(submitBtn, originalText);
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while saving the commissioning sheet',
                    confirmButtonColor: '#ef4444'
                });
            });
        }

        function generateAndPrintCommissioningSheet() {
            const form = document.getElementById('commissioningSheetForm');
            const formData = new FormData(form);

            // Validate required fields
            const fileNumber = formData.get('file_number');
            if (!fileNumber) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Validation Error',
                    text: 'File number is required',
                    confirmButtonColor: '#f59e0b'
                });
                return;
            }

            // Show loading
            showGlobalLoading('Saving commissioning sheet...');

            // Save to DB first, then generate/print
            fetch('{{ route("commissioning-sheet.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                hideGlobalLoading();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Commissioning sheet saved. Generating PDF...',
                        showConfirmButton: false,
                        timer: 1000
                    });
                    // Now generate/print PDF
                    generateCommissioningSheetPDF(formData);
                    // Optionally reload table
                    if (typeof table !== 'undefined' && table.ajax) {
                        table.ajax.reload();
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: data.message || 'Failed to save commissioning sheet',
                        confirmButtonColor: '#ef4444'
                    });
                }
            })
            .catch(error => {
                hideGlobalLoading();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'An error occurred while saving the commissioning sheet',
                    confirmButtonColor: '#ef4444'
                });
            });
        }
        
        async function generateCommissioningSheetPDF(formData) {
            try {
                // Helper to fetch image as base64
                async function getImageBase64(url) {
                    try {
                        const response = await fetch(url, {
                            mode: 'cors',
                            headers: {
                                'Accept': 'image/*'
                            }
                        });
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        const blob = await response.blob();
                        return new Promise((resolve, reject) => {
                            const reader = new FileReader();
                            reader.onloadend = () => resolve(reader.result);
                            reader.onerror = reject;
                            reader.readAsDataURL(blob);
                        });
                    } catch (error) {
                        console.warn(`Failed to fetch image from ${url}:`, error);
                        return null;
                    }
                }

                // Create new PDF document
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                // Add background watermark (very small size, centered)
                try {
                    const watermarkBase64 = await getImageBase64('/assets/logo/cor_bg.jpeg');
                    if (watermarkBase64) {
                        // Set graphics state for better visibility 
                        doc.setGState(doc.GState({opacity: 0.35}));
                        
                        // Center the watermark on the page - 50% smaller than previous
                        const pageWidth = 210; // A4 width in mm
                        const pageHeight = 297; // A4 height in mm
                        const watermarkWidth = 50; // Reduced by 50% from 100 to 50
                        const watermarkHeight = 65; // Reduced by 50% from 130 to 65
                        const xPos = (pageWidth - watermarkWidth) / 2; // Center horizontally
                        const yPos = (pageHeight - watermarkHeight) / 2; // Center vertically
                        
                        // Add centered watermark with very small size
                        doc.addImage(watermarkBase64, 'JPEG', xPos, yPos, watermarkWidth, watermarkHeight);
                        
                        // Reset graphics state to normal
                        doc.setGState(doc.GState({opacity: 1.0}));
                    }
                } catch (error) {
                    console.warn('Could not load background watermark:', error);
                }

                // Fetch header/footer logos as base64 (try several common filenames)
                let logo1Base64 = null;
                let logo2Base64 = null;

                console.log('Attempting to fetch logos...');

                try {
                    console.log('Fetching logo 1 from known locations');
                    // Try common filenames for the primary (coat-of-arms) logo
                    logo1Base64 = await getImageBase64('/assets/logo/logo1.png')
                        || await getImageBase64('/assets/logo/logo1.jpg')
                        || await getImageBase64('/assets/logo/logo1.jpeg');
                    console.log('Logo 1 loaded successfully:', logo1Base64 ? 'Yes' : 'No');
                } catch (e) {
                    console.warn('Could not fetch logo 1:', e);
                }

                try {
                    console.log('Fetching logo 2 from known locations');
                    // Try ministry / right-side logo names
                    logo2Base64 = await getImageBase64('/assets/logo/logo3.jpeg')
                        || await getImageBase64('/assets/logo/las.jpeg')
                        || await getImageBase64('/assets/logo/logo3.jpg');
                    console.log('Logo 2 loaded successfully:', logo2Base64 ? 'Yes' : 'No');
                } catch (e) {
                    console.warn('Could not fetch logo 2:', e);
                }

                // Set font
                doc.setFont("helvetica", "normal");

                // Add header logos
                console.log('Adding header logos...');
                
                // Left header logo (shrink slightly so both fit neatly)
                if (logo1Base64) {
                    console.log('Adding left header logo at position (20, 12) size 20x20');
                    doc.addImage(logo1Base64, 'JPEG', 20, 12, 20, 20);
                    console.log('Left header logo added successfully');
                } else {
                    console.warn('Left header logo not available, adding placeholder (and trying logo1.jpg)');
                    doc.setDrawColor(0, 0, 0);
                    doc.rect(20, 12, 20, 20);
                    doc.setFontSize(7);
                    doc.text("LOGO", 30, 24, { align: "center" });
                }

                // Right header logo (use logo3 / las as fallback)
                if (logo2Base64) {
                    console.log('Adding right header logo at position (170, 12) size 20x20');
                    doc.addImage(logo2Base64, 'JPEG', 170, 12, 20, 20);
                    console.log('Right header logo added successfully');
                } else {
                    console.warn('Right header logo not available, adding placeholder');
                    doc.setDrawColor(0, 0, 0);
                    doc.rect(170, 12, 20, 20);
                    doc.setFontSize(7);
                    doc.text("LOGO", 180, 24, { align: "center" });
                }

                // Header text (centered between logos) - slightly reduced for space
                doc.setFontSize(14);
                doc.setFont("helvetica", "bold");
                doc.text("MINISTRY OF LAND & PHYSICAL PLANNING", 105, 18, { align: "center" });

                doc.setFontSize(12);
                doc.text("DEPT. OF LANDS", 105, 26, { align: "center" });

                doc.setFontSize(11);
                doc.text("FILE COMMISSIONING SHEET", 105, 36, { align: "center" });

                // Draw line under header (moved up to match reduced header height)
                doc.line(20, 48, 190, 48);

                // Form fields
                doc.setFont("helvetica", "normal");
                doc.setFontSize(10);

                let yPos = 75;
                const leftMargin = 25;
                const labelWidth = 30; // Reduced width for tighter spacing between labels and lines
                const lineStartX = leftMargin + labelWidth; // Consistent line start position
                const textStartX = lineStartX + 2; // Consistent text start position
                const fieldHeight = 15;

                // File Number
                doc.setFont("helvetica", "bold");
                doc.text("File No:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('file_number') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;

                // File Name
                doc.setFont("helvetica", "bold");
                doc.text("File Name:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('file_name') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;

                // Allottee
                doc.setFont("helvetica", "bold");
                doc.text("Allottee:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('name_or_allottee') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;
                
                // Plot Number
                doc.setFont("helvetica", "bold");
                doc.text("Plot No:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('plot_number') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;

                // TP Number
                doc.setFont("helvetica", "bold");
                doc.text("TP No:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('tp_number') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;
                
                // Location
                doc.setFont("helvetica", "bold");
                doc.text("Location:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('location') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;
                
                // Date Created
                doc.setFont("helvetica", "bold");
                doc.text("Date Created:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                const dateCreated = formData.get('date_created') ? new Date(formData.get('date_created')).toLocaleDateString() : new Date().toLocaleDateString();
                doc.text(dateCreated, textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += fieldHeight;

                // Created By
                doc.setFont("helvetica", "bold");
                doc.text("Created by:", leftMargin, yPos);
                doc.setFont("helvetica", "normal");
                doc.text(formData.get('created_by') || '', textStartX, yPos);
                doc.line(lineStartX, yPos + 2, 185, yPos + 2);
                yPos += 40;
                
                // Signature boxes
                doc.setFont("helvetica", "bold");
                doc.text("Created by Signature", 50, yPos, { align: "center" });
                doc.text("Approved by Signature", 150, yPos, { align: "center" });
                
                // Signature lines (reduced gap from 25 to 15 for better spacing)
                doc.line(25, yPos + 15, 75, yPos + 15);
                doc.line(125, yPos + 15, 175, yPos + 15);
                
                // Footer
                doc.setFontSize(8);
                doc.setFont("helvetica", "normal");
                doc.text(`Generated on ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()} | File Commissioning Sheet System`, 105, 270, { align: "center" });

                // Define footer logo dimensions
                const leftFooterLogoW = 18;
                const leftFooterLogoH = 18;
                const rightFooterLogoW = Math.round(18 * 1.3); // 30% increase = 23px
                const rightFooterLogoH = 18;

                // Add footer logo on the left side (1.jpeg)
                try {
                    const leftFooterLogoBase64 = await getImageBase64('/assets/logo/1.jpeg');
                    if (leftFooterLogoBase64) {
                        // Use fixed size for left logo
                        doc.addImage(leftFooterLogoBase64, 'JPEG', 15, 272, leftFooterLogoW, leftFooterLogoH);
                        console.log('Footer 1.jpeg added successfully (fixed size)');
                    } else {
                        console.warn('Footer 1.jpeg not available');
                    }
                } catch (error) {
                    console.warn('Could not load footer 1.jpeg:', error);
                }

                // Add footer logo on the right side (las.jpeg)
                try {
                    const footerLogoBase64 = await getImageBase64('/assets/logo/las.jpeg');
                    if (footerLogoBase64) {
                        // Use wider size for right logo (30% increase in width)
                        const rightLogoX = 210 - 15 - rightFooterLogoW; // Page width minus margin minus logo width
                        doc.addImage(footerLogoBase64, 'JPEG', rightLogoX, 272, rightFooterLogoW, rightFooterLogoH);
                        console.log('Footer las.jpeg added successfully (wider fixed size)');
                    } else {
                        console.warn('Footer las.jpeg not available');
                    }
                } catch (error) {
                    console.warn('Could not load footer las.jpeg:', error);
                }
                
                // Do NOT save to database when generating the PDF; just download
                hideGlobalLoading();

                // Download PDF
                doc.save(`commissioning-sheet-${formData.get('file_number')}.pdf`);

                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Commissioning sheet generated and downloaded successfully',
                    confirmButtonColor: '#10b981'
                }).then(() => {
                    closeCommissioningSheetModal();
                });
                
            } catch (error) {
                hideGlobalLoading();
                console.error('Error generating PDF:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to generate PDF: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        }
        
        function saveCommissioningSheetData(formData) {
            return fetch('{{ route("commissioning-sheet.store") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save commissioning sheet');
                }
                return data;
            });
        }

        // Dropdown Functions
        function toggleDropdown(button) {
            // Close all other dropdowns first
            closeAllDropdowns();
            
            // Toggle the clicked dropdown
            const dropdown = button.nextElementSibling;
            if (dropdown && dropdown.classList.contains('dropdown-menu')) {
                dropdown.classList.toggle('hidden');
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
        }

        function openCommissioningSheetFromDropdown(button) {
            // Get data from button attributes (same as before)
            const mlsfNo = button.getAttribute('data-mlsf-no');
            const kangisNo = button.getAttribute('data-kangis-no');
            const newKangisNo = button.getAttribute('data-new-kangis-no');
            const fileName = button.getAttribute('data-file-name');
            const plotNo = button.getAttribute('data-plot-no');
            const tpNo = button.getAttribute('data-tp-no');
            const location = button.getAttribute('data-location');
            
            console.log('Dropdown commissioning sheet data:', {
                mlsfNo, kangisNo, newKangisNo, fileName, plotNo, tpNo, location
            });
            
            // Determine the best file number to use
            let fileNumber = '';
            if (mlsfNo && mlsfNo !== 'N/A' && mlsfNo.trim() !== '') {
                fileNumber = mlsfNo;
            } else if (kangisNo && kangisNo !== 'N/A' && kangisNo.trim() !== '') {
                fileNumber = kangisNo;
            } else if (newKangisNo && newKangisNo !== 'N/A' && newKangisNo.trim() !== '') {
                fileNumber = newKangisNo;
            }
            
            // Clean up other fields
            const cleanFileName = (fileName && fileName !== 'N/A') ? fileName : '';
            const cleanPlotNo = (plotNo && plotNo !== 'N/A') ? plotNo : '';
            const cleanTpNo = (tpNo && tpNo !== 'N/A') ? tpNo : '';
            const cleanLocation = (location && location !== 'N/A') ? location : '';
            
            openCommissioningSheetForFile(fileNumber, cleanFileName, cleanPlotNo, cleanTpNo, cleanLocation);
        }

        // View existing commissioning sheet
        function viewCommissioningSheet(commissioningSheetId) {
            if (!commissioningSheetId) {
                alert('Commissioning sheet ID not found');
                return;
            }

            // Show loading
            showGlobalLoading('Loading commissioning sheet...');

            // Fetch the commissioning sheet data
            fetch(`/commissioning-sheet/${commissioningSheetId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    hideGlobalLoading();
                    
                    if (data.success && data.data) {
                        // Convert the data object to FormData format expected by generateCommissioningSheetPDF
                        const formData = new FormData();
                        
                        // Map the response data to FormData
                        const responseData = data.data;
                        formData.append('file_number', responseData.file_number || '');
                        formData.append('file_name', responseData.file_name || '');
                        formData.append('name_or_allottee', responseData.name_or_allottee || '');
                        formData.append('plot_number', responseData.plot_number || '');
                        formData.append('tp_number', responseData.tp_number || '');
                        formData.append('location', responseData.location || '');
                        formData.append('date_created', responseData.date_created || responseData.created_at || '');
                        formData.append('created_by', responseData.created_by || '');
                        
                        // Generate PDF with the FormData
                        generateCommissioningSheetPDF(formData);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error loading commissioning sheet',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                })
                .catch(error => {
                    hideGlobalLoading();
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error loading commissioning sheet data',
                        confirmButtonColor: '#ef4444'
                    });
                });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.relative')) {
                closeAllDropdowns();
            }
        });

        // Add event listeners for form inputs
        document.addEventListener('DOMContentLoaded', function() {
            // ...existing code...
        });
    </script>