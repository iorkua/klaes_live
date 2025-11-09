  <script>
        // CSRF Token for Laravel
        const csrfToken = '{{ csrf_token() }}';
        
        // Set up default headers for AJAX requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        // Office data
        const officeData = {
            'OFF-001': { name: 'Reception', code: 'RCP', department: 'Customer Service' },
            'OFF-002': { name: 'Customer Care Unit', code: 'CCU', department: 'Customer Service' },
            'OFF-003': { name: 'Document Verification', code: 'DVF', department: 'Legal' },
            'OFF-004': { name: 'Survey Department', code: 'SUR', department: 'Technical' },
            'OFF-005': { name: 'Legal Department', code: 'LEG', department: 'Legal' },
            'OFF-006': { name: 'Planning Department', code: 'PLN', department: 'Technical' },
            'OFF-007': { name: "Director's Office", code: 'DIR', department: 'Management' },
            'OFF-008': { name: 'Certificate Issuance', code: 'CRT', department: 'Operations' },
            'OFF-009': { name: 'Archive', code: 'ARC', department: 'Records' },
            'OFF-010': { name: 'Finance Department', code: 'FIN', department: 'Finance' },
            'OFF-011': { name: 'IT Department', code: 'ITD', department: 'Technical' },
            'OFF-012': { name: 'Registry', code: 'REG', department: 'Records' }
        };

    let fileTrackers = [];
    const expandedTrackerHistory = new Set();
        let currentTracker = null;
        let pendingLogEntry = null; // Store pending log entry while waiting for notes
        let currentDetailsTracker = null; // Store the tracker being viewed in details

        // Global variables exposed to window for debugging and external access
        window.fileTrackers = fileTrackers;
        window.officeData = officeData;
    let lastMetadataLookup = null;
    let activeMetadataRequest = null;
    let resolvedFileIndexingId = null;
    let forceNextMetadataLookup = false;

        function normalizeFileNumberForLookup(value) {
            return (value || '').toString().trim().toUpperCase();
        }

        function resetMetadataFields() {
            if (activeMetadataRequest) {
                activeMetadataRequest.abort();
                activeMetadataRequest = null;
            }

            const trackingField = $('#tracking-id');
            const fileNameField = $('#file-name');

            trackingField.removeClass('metadata-loading tracking-id-locked')
                .prop('disabled', false)
                .val('');
            trackingField.removeData('prevValue');

            fileNameField.removeClass('metadata-loading metadata-locked')
                .prop('disabled', false)
                .val('');
            fileNameField.removeData('prevValue');

            $('#sidebar-tracking-id').text('-');
            lastMetadataLookup = null;
            resolvedFileIndexingId = null;
            forceNextMetadataLookup = false;
        }

        function startMetadataFetch() {
            const trackingField = $('#tracking-id');
            const fileNameField = $('#file-name');

            if (!trackingField.hasClass('metadata-loading')) {
                trackingField.data('prevValue', trackingField.val() || '');
            }

            if (!fileNameField.hasClass('metadata-loading')) {
                fileNameField.data('prevValue', fileNameField.val() || '');
            }

            trackingField.addClass('metadata-loading').prop('disabled', true).val('Fetching...');
            fileNameField.addClass('metadata-loading metadata-locked').prop('disabled', true);
        }

        function applyMetadataResult(data, context = {}) {
            const trackingField = $('#tracking-id');
            const fileNameField = $('#file-name');
            const resolvedFileNumber = data.file_number || context.originalFileNumber || '';

            trackingField.removeClass('metadata-loading')
                .val(data.tracking_id || '')
                .prop('disabled', true)
                .addClass('tracking-id-locked');
            trackingField.removeData('prevValue');

            const resolvedName = (data.file_name || '').trim();
            fileNameField.removeClass('metadata-loading');

            if (resolvedName !== '') {
                fileNameField
                    .val(resolvedName)
                    .prop('disabled', true)
                    .addClass('metadata-locked');
            } else {
                fileNameField
                    .val('')
                    .prop('disabled', false)
                    .removeClass('metadata-locked');
            }

            fileNameField.removeData('prevValue');

            if (resolvedFileNumber) {
                $('#file-no').val(resolvedFileNumber);
                $('#sidebar-file-no').text(resolvedFileNumber);
            }

            $('#sidebar-tracking-id').text(data.tracking_id || '-');

            resolvedFileIndexingId = data.id ?? null;
            lastMetadataLookup = normalizeFileNumberForLookup(resolvedFileNumber);
            forceNextMetadataLookup = false;

            if (!context.silent) {
                showNotification('File metadata loaded from File Indexing.', 'success');
            }
        }

        function handleMetadataFailure(message, { suppressToast = false } = {}) {
            const trackingField = $('#tracking-id');
            const fileNameField = $('#file-name');

            const previousTracking = trackingField.data('prevValue') || '';
            const previousName = fileNameField.data('prevValue') || '';

            trackingField.removeClass('metadata-loading tracking-id-locked')
                .prop('disabled', false)
                .val(previousTracking);
            trackingField.removeData('prevValue');

            fileNameField.removeClass('metadata-loading metadata-locked')
                .prop('disabled', false)
                .val(previousName);
            fileNameField.removeData('prevValue');

            $('#sidebar-tracking-id').text(previousTracking || '-');

            if (!suppressToast && message) {
                showNotification(message, 'error');
            }

            lastMetadataLookup = null;
            resolvedFileIndexingId = null;
            forceNextMetadataLookup = false;
        }

        function fetchFileMetadata(fileNumber, context = {}) {
            if (!fileNumber) {
                resetMetadataFields();
                return;
            }

            startMetadataFetch();

            if (activeMetadataRequest) {
                activeMetadataRequest.abort();
            }

            activeMetadataRequest = $.ajax({
                url: '/api/file-indexings/lookup-by-number',
                method: 'GET',
                data: { file_number: fileNumber },
                dataType: 'json',
                timeout: 7000
            })
            .done(function(response) {
                if (response && response.success && response.data && response.data.tracking_id) {
                    applyMetadataResult(response.data, { originalFileNumber: fileNumber, ...context });
                } else {
                    const message = response?.message || 'No indexing record with a tracking ID was found for this file number.';
                    handleMetadataFailure(message);
                }
            })
            .fail(function(xhr, status) {
                if (status === 'abort') {
                    return;
                }

                let message = 'Unable to fetch file metadata at this time.';
                if (xhr?.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }
                handleMetadataFailure(message);
            })
            .always(function() {
                activeMetadataRequest = null;
            });
        }

        function triggerMetadataLookup(rawValue, context = {}) {
            const normalized = normalizeFileNumberForLookup(rawValue);

            if (!normalized) {
                resetMetadataFields();
                return;
            }

            if (!context.force && normalized === lastMetadataLookup) {
                return;
            }

            lastMetadataLookup = normalized;
            fetchFileMetadata(rawValue, context);
        }
        
        // Initialize File Number Modal Integration
        function initializeFileNoModal() {
            console.log('Initializing File Number Modal...');
            
            // Check if modal element exists in DOM
            const modalElement = $('#global-fileno-modal');
            console.log('Modal element found:', modalElement.length > 0);
            if (modalElement.length > 0) {
                console.log('Modal element classes:', modalElement.attr('class'));
                console.log('Modal element display:', modalElement.css('display'));
            }
            
            // Initialize the global file number modal
            if (typeof GlobalFileNoModal !== 'undefined') {
                console.log('GlobalFileNoModal object available');
                GlobalFileNoModal.init();
                
                // Set up the file number selector button
                $('#fileno-selector-btn').on('click', function() {
                    console.log('File number selector button clicked');
                    
                    // Additional debug before opening
                    const modal = $('#global-fileno-modal');
                    console.log('Before open - Modal element:', modal.length);
                    console.log('Before open - Modal classes:', modal.attr('class'));
                    console.log('Before open - Modal display:', modal.css('display'));
                    
                    const result = GlobalFileNoModal.open({
                        targetFields: ['file_number'], // Target our file number field
                        callback: function(data) {
                            // This callback is executed when user clicks Apply
                            console.log('File number selected:', data.fileNumber);
                            
                            // Update the file number field
                            forceNextMetadataLookup = true;
                            $('#file-no').val(data.fileNumber).trigger('change');

                            // Inform user that metadata will be resolved
                            showNotification(`File number ${data.fileNumber} selected. Resolving metadata...`, 'info');
                        }
                    });
                    
                    console.log('Modal open result:', result);
                    
                    // Check modal state after opening attempt
                    setTimeout(() => {
                        const modalAfter = $('#global-fileno-modal');
                        console.log('After open - Modal classes:', modalAfter.attr('class'));
                        console.log('After open - Modal display:', modalAfter.css('display'));
                        console.log('After open - Modal z-index:', modalAfter.css('z-index'));
                        console.log('After open - Modal visibility:', modalAfter.css('visibility'));
                    }, 100);
                });
                
                // Listen for the global modal apply event
                $(document).on('fileno-modal:applied', function(event, data) {
                    console.log('File number modal applied:', data);
                });
            } else {
                console.warn('GlobalFileNoModal not available');
            }
        }
        
        // Notification function
    function showNotification(message, type = 'info') {
            // Create a simple notification
            const notification = $(`
                <div class="fixed top-4 right-4 z-50 px-4 py-2 rounded-md shadow-lg text-white ${type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500'}">
                    ${message}
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }

        function getTrackerKey(tracker) {
            if (!tracker) {
                return null;
            }
            if (tracker.trackingId) {
                return tracker.trackingId;
            }
            if (tracker.id !== undefined && tracker.id !== null) {
                return `id-${tracker.id}`;
            }
            return null;
        }

        function transformApiTracker(tracker) {
            if (!tracker) {
                return null;
            }

            const movementLog = Array.isArray(tracker.movement_log) ? tracker.movement_log : [];

            const convertedEntries = movementLog.map(log => ({
                logId: log.log_id,
                officeId: log.office_code,
                officeName: log.office_name,
                logInTime: log.log_in_time,
                logInDate: log.log_in_date,
                logOutTime: log.log_out_time || '',
                logOutDate: log.log_out_date || '',
                notes: log.notes || '',
                status: (log.status || 'completed').toLowerCase(),
                createdAt: log.timestamp || null
            }));

            return {
                id: tracker.id,
                trackingId: tracker.tracking_id,
                fileNo: tracker.file_number,
                fileName: tracker.file_title,
                fileType: tracker.file_type,
                priority: tracker.priority || 'MEDIUM',
                currentOffice: tracker.current_office_name,
                currentOfficeId: tracker.current_office_code,
                office: tracker.current_office_name && tracker.current_office_code ? {
                    name: tracker.current_office_name,
                    code: tracker.current_office_code,
                    department: tracker.department || ''
                } : null,
                logEntries: convertedEntries,
                notes: tracker.notes,
                createdAt: tracker.created_at,
                department: tracker.department,
                description: tracker.description,
                totalOffices: tracker.total_offices,
                completedOffices: tracker.completed_offices
            };
        }

        function upsertLocalTracker(updatedTracker, options = {}) {
            if (!updatedTracker) {
                return;
            }

            const { render = true } = options;
            const matchIndex = fileTrackers.findIndex(existing => {
                if (existing.id && updatedTracker.id) {
                    return existing.id === updatedTracker.id;
                }
                return existing.trackingId === updatedTracker.trackingId;
            });

            if (matchIndex !== -1) {
                fileTrackers[matchIndex] = updatedTracker;
            } else {
                fileTrackers.push(updatedTracker);
            }

            window.fileTrackers = fileTrackers;

            if (render) {
                updateFileTrackersTable();
            }
        }

        // Generate unique IDs
        function generateLogId() {
            const now = new Date();
            const timestamp = now.toISOString().replace(/[-:T]/g, '').split('.')[0];
            const random = Math.floor(Math.random() * 999).toString().padStart(3, '0');
            return `LOG-${timestamp}-${random}`;
        }

        // Update current date and time
        function updateDateTime() {
            const now = new Date();
            const date = now.toISOString().split('T')[0];
            const time = now.toTimeString().split(' ')[0].substring(0, 8);
            
            document.getElementById('current-time').textContent = time;
            document.getElementById('current-date').textContent = date;
            document.getElementById('log-in-time').value = time.substring(0, 5);
            document.getElementById('log-in-date').value = date;
        }

        // Initialize IDs
        function initializeIds() {
            resetMetadataFields();

            const logId = generateLogId();
            document.getElementById('log-id').value = logId;
            document.getElementById('sidebar-log-id').textContent = logId;
        }

        // Handle office selection
        document.getElementById('current-office').addEventListener('change', function() {
            const officeId = this.value;
            const officeIdField = document.getElementById('current-office-id');
            const officeInfo = document.getElementById('office-info');
            
            if (officeId && officeData[officeId]) {
                const office = officeData[officeId];
                officeIdField.value = officeId;
                
                document.getElementById('office-badge').textContent = officeId;
                document.getElementById('office-code-badge').textContent = office.code;
                document.getElementById('office-department').textContent = `Department: ${office.department}`;
                
                officeInfo.classList.remove('hidden');
            } else {
                officeIdField.value = '';
                officeInfo.classList.add('hidden');
            }
        });

        // Create file tracker
        function createFileTracker() {
            const fileNo = document.getElementById('file-no').value;
            const fileName = document.getElementById('file-name').value;
            const trackingId = document.getElementById('tracking-id').value.trim();
            const fileIndexingId = resolvedFileIndexingId;
            const officeId = document.getElementById('current-office').value;
            const priority = document.getElementById('file-priority').value;
            const notes = document.getElementById('office-notes').value;
            
            console.log('Creating tracker with:', { fileNo, fileName, trackingId, fileIndexingId, officeId, priority, notes });
            
            if (!fileNo || !fileName || !officeId) {
                alert('Please fill in all required fields');
                return;
            }

            if (!trackingId) {
                alert('Tracking ID could not be resolved. Please select a file number that already exists in File Indexing.');
                return;
            }
            
            const office = officeData[officeId];
            if (!office) {
                alert('Invalid office selected. Please select a valid office.');
                console.error('Office not found for ID:', officeId);
                return;
            }
            
            console.log('Office found:', office);
            const now = new Date();
            const logId = document.getElementById('log-id').value;
            
            currentTracker = {
                fileNo,
                fileName,
                trackingId,
                fileIndexingId,
                priority,
                currentOffice: office.name,
                currentOfficeId: officeId,
                office: office, // Add the complete office object
                logEntries: [{
                    logId,
                    logInTime: document.getElementById('log-in-time').value,
                    logInDate: document.getElementById('log-in-date').value,
                    logOutTime: '',
                    logOutDate: '',
                    officeId,
                    officeName: office.name,
                    notes,
                    status: 'active',
                    createdAt: now.toISOString()
                }],
                notes: notes, // Add notes to the main tracker object
                createdAt: now.toISOString()
            };
            
            showPreview();
        }

        // Show preview dialog
        function showPreview() {
            if (!currentTracker) return;
            
            const priorityClass = `priority-${currentTracker.priority}`;
            const priorityColors = {
                LOW: 'bg-green-50 border-green-200 text-green-800',
                MEDIUM: 'bg-amber-50 border-amber-200 text-amber-800',
                HIGH: 'bg-red-50 border-red-200 text-red-800'
            };
            const priorityText = currentTracker.priority.charAt(0) + currentTracker.priority.slice(1).toLowerCase();
            const priorityBg = priorityColors[currentTracker.priority] || priorityColors.MEDIUM;
            
            const previewContent = document.getElementById('preview-content');
            previewContent.innerHTML = `
                <div class="space-y-6">
                    <!-- Main File Header Card -->
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-gray-900 mb-2">${currentTracker.fileName}</h3>
                                <div class="flex items-center gap-4 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-600 uppercase">File No:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-mono font-bold bg-blue-600 text-white">${currentTracker.fileNo}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-semibold text-gray-600 uppercase">Tracking ID:</span>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-mono font-bold bg-red-600 text-white">${currentTracker.trackingId}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 items-end">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${priorityBg} border">${priorityText} Priority</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 border border-green-300 text-green-800">
                                    <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                                    Active
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Two Column Grid for Details -->
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Left Column: File Information -->
                        <div class="space-y-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
                                    <i data-lucide="file-text" class="h-4 w-4 text-blue-600"></i>
                                    File Information
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">File No:</span>
                                        <span class="text-sm font-semibold text-gray-900">${currentTracker.fileNo}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Tracking ID:</span>
                                        <span class="text-sm font-mono font-semibold text-red-600">${currentTracker.trackingId}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">File Name:</span>
                                        <span class="text-sm font-semibold text-gray-900">${currentTracker.fileName}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Priority:</span>
                                        <span class="text-xs font-semibold px-2 py-1 rounded-full ${priorityBg} border">${priorityText}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Office Information -->
                        <div class="space-y-4">
                            <div class="bg-white border border-gray-200 rounded-lg p-4">
                                <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
                                    <i data-lucide="map-pin" class="h-4 w-4 text-purple-600"></i>
                                    Current Location
                                </h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Office:</span>
                                        <span class="text-sm font-semibold text-gray-900">${currentTracker.currentOffice}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Office ID:</span>
                                        <span class="text-sm font-mono font-semibold text-purple-600">${currentTracker.currentOfficeId}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Department:</span>
                                        <span class="text-sm font-semibold text-gray-900">${currentTracker.office?.department || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2 pb-3 border-b border-gray-100">
                            <i data-lucide="message-square" class="h-4 w-4 text-orange-600"></i>
                            Notes & Remarks
                        </h4>
                        <div class="bg-gray-50 border border-gray-200 rounded p-3">
                            <p class="text-sm text-gray-700 leading-relaxed">${currentTracker.logEntries[0].notes || '<span class="italic text-gray-500">No notes provided</span>'}</p>
                        </div>
                    </div>

                    <!-- Log Entry Section -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-4 flex items-center gap-2 pb-3 border-b border-gray-100">
                            <i data-lucide="clock" class="h-4 w-4 text-green-600"></i>
                            Initial Log Entry
                        </h4>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="flex items-center justify-center h-8 w-8 rounded-full bg-green-600 text-white">
                                    <i data-lucide="log-in" class="h-4 w-4"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-gray-900">LOG IN</span>
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-200 text-gray-800">${currentTracker.logEntries[0].logId}</span>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-gray-700 pl-11">
                                <div><span class="font-semibold text-gray-900">Date:</span> ${currentTracker.logEntries[0].logInDate}</div>
                                <div><span class="font-semibold text-gray-900">Time:</span> ${currentTracker.logEntries[0].logInTime}</div>
                                <div><span class="font-semibold text-gray-900">Office:</span> ${currentTracker.logEntries[0].officeName}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="grid grid-cols-3 gap-4 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">${currentTracker.fileNo}</div>
                            <div class="text-xs font-semibold text-gray-600 uppercase mt-1">File Number</div>
                        </div>
                        <div class="text-center border-l border-r border-gray-300">
                            <div class="text-2xl font-bold text-green-600">${currentTracker.logEntries.length}</div>
                            <div class="text-xs font-semibold text-gray-600 uppercase mt-1">Log Entries</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">${priorityText}</div>
                            <div class="text-xs font-semibold text-gray-600 uppercase mt-1">Priority</div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('preview-dialog').classList.add('show');
            lucide.createIcons();
        }

        // Save file tracker
        function saveFileTracker() {
            console.log('Save function called, currentTracker:', currentTracker);
            
            if (!currentTracker) {
                alert('No tracker data to save');
                return;
            }

            // Ensure office data is available
            const office = currentTracker.office || officeData[currentTracker.currentOfficeId] || {};
            const department = office.department || 'Unknown Department';

            console.log('Office data:', office);
            console.log('Department:', department);

            // Prepare data for API
            const apiData = {
                file_number: currentTracker.fileNo,
                file_title: currentTracker.fileName,
                file_type: 'Application', // Can be made dynamic later
                priority: currentTracker.priority,
                department: department,
                description: `File tracked in ${office.name || currentTracker.currentOffice || 'Unknown Office'}`,
                deadline: null, // Can be added later
                movement_log: [{
                    office_code: currentTracker.currentOfficeId,
                    office_name: office.name || currentTracker.currentOffice || 'Unknown Office',
                    log_in_time: currentTracker.logEntries?.[0]?.logInTime || '',
                    log_in_date: currentTracker.logEntries?.[0]?.logInDate || '',
                    notes: currentTracker.notes || currentTracker.logEntries?.[0]?.notes || 'Initial file tracking entry'
                }],
                notes: currentTracker.notes || ''
            };

            // Show loading state
            const saveButton = document.getElementById('save-tracker-btn');
            const originalText = saveButton.innerHTML;
            saveButton.innerHTML = '<i data-lucide="loader" class="h-4 w-4 mr-2 animate-spin"></i>Saving...';
            saveButton.disabled = true;

            // Send to API
            $.ajax({
                url: '/api/file-trackers',
                method: 'POST',
                data: JSON.stringify(apiData),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    if (response.success && response.data) {
                        const normalizedTracker = transformApiTracker(response.data);
                        upsertLocalTracker(normalizedTracker);
                        document.getElementById('preview-dialog').classList.remove('show');
                        showNotification(`File tracker created successfully. Tracking ID: ${response.data.tracking_id}`, 'success');
                        resetForm();
                        currentTracker = null;
                    } else {
                        const message = response.message || 'Error creating file tracker';
                        alert(message);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error creating file tracker';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        errorMessage = xhr.responseText;
                    }
                    alert(errorMessage);
                },
                complete: function() {
                    // Restore button state
                    saveButton.innerHTML = originalText;
                    saveButton.disabled = false;
                    lucide.createIcons(); // Reinitialize icons
                }
            });
        }

        // Update file trackers table
        function updateFileTrackersTable() {
            const container = document.getElementById('trackers-container');
            if (!container) {
                return;
            }

            updateLogCount();
            updateTrackerStats();

            if (fileTrackers.length === 0) {
                container.innerHTML = `
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-12 text-center shadow-sm">
                        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gray-100">
                            <i data-lucide="file-x" class="h-8 w-8 text-gray-400"></i>
                        </div>
                        <h3 class="mt-6 text-lg font-semibold text-gray-900">No trackers logged yet</h3>
                        <p class="mt-2 text-sm text-gray-600">Once you create a file tracker, its journey will appear here with full audit history.</p>
                        <button id="create-first-tracker" class="mt-6 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700">
                            <i data-lucide="plus" class="mr-2 h-4 w-4"></i>
                            Create First Tracker
                        </button>
                    </div>
                `;
                lucide.createIcons();

                const createFirstButton = document.getElementById('create-first-tracker');
                if (createFirstButton) {
                    createFirstButton.addEventListener('click', function() {
                        switchMainTab('create');
                    });
                }
                return;
            }

            const sanitize = (value) => {
                return (value || '').toString()
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');
            };

            const cards = fileTrackers.map(tracker => {
                const trackerKey = getTrackerKey(tracker);
                const isExpanded = trackerKey ? expandedTrackerHistory.has(trackerKey) : false;
                const historyTargetIdBase = trackerKey || `temp-${Math.random().toString(36).slice(2, 9)}`;
                const historyTargetId = `history-${historyTargetIdBase.replace(/[^A-Za-z0-9]/g, '-')}`;
                const priority = (tracker.priority || 'MEDIUM').toUpperCase();
                const priorityLabel = priority.charAt(0) + priority.slice(1).toLowerCase();
                const priorityBadge = {
                    LOW: 'bg-green-100 text-green-800 border border-green-200',
                    MEDIUM: 'bg-amber-100 text-amber-800 border border-amber-200',
                    HIGH: 'bg-red-100 text-red-800 border border-red-200'
                }[priority] || 'bg-amber-100 text-amber-800 border border-amber-200';

                const movements = tracker.logEntries || [];
                const totalMovements = movements.length;
                const activeMovements = movements.filter(entry => (entry.status || '').toLowerCase() === 'active');
                const hasActiveMovement = activeMovements.length > 0;
                const statusLabel = hasActiveMovement ? 'In Transit' : 'Completed';
                const statusBadge = hasActiveMovement
                    ? 'bg-amber-100 text-amber-800 border border-amber-200'
                    : 'bg-emerald-100 text-emerald-800 border border-emerald-200';

                const lastMovement = movements[movements.length - 1] || null;
                const lastMovementDate = lastMovement ? `${lastMovement.logInDate || ''} ${lastMovement.logInTime || ''}`.trim() || '—' : '—';
                const lastMovementDuration = lastMovement
                    ? calculateDuration(lastMovement.logInDate, lastMovement.logInTime, lastMovement.logOutDate, lastMovement.logOutTime)
                    : '—';

                const currentOfficeName = tracker.currentOffice
                    || lastMovement?.officeName
                    || lastMovement?.office
                    || officeData[tracker.currentOfficeId]?.name
                    || officeData[lastMovement?.officeId]?.name
                    || '—';

                const currentOfficeCode = tracker.currentOfficeId
                    || lastMovement?.officeId
                    || '—';

                const rows = totalMovements > 0
                    ? movements.map(entry => {
                        const entryStatus = (entry.status || 'completed').toLowerCase();
                        const statusClass = entryStatus === 'active'
                            ? 'bg-amber-100 text-amber-800 border border-amber-200'
                            : 'bg-gray-100 text-gray-700 border border-gray-200';
                        const officeLabel = entry.officeName
                            || entry.office
                            || officeData[entry.officeId]?.name
                            || entry.officeId
                            || '—';
                        const duration = calculateDuration(entry.logInDate, entry.logInTime, entry.logOutDate, entry.logOutTime);
                        const safeNotes = sanitize(entry.notes);

                        return `
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-700">${sanitize(entry.logId)}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">${sanitize(entry.officeId)}</span>
                                        <span>${sanitize(officeLabel)}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    <div>${sanitize(entry.logInDate || '—')}</div>
                                    <div class="text-xs text-gray-500">${sanitize(entry.logInTime || '—')}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    <div>${sanitize(entry.logOutDate || '—')}</div>
                                    <div class="text-xs text-gray-500">${sanitize(entry.logOutTime || '—')}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">${duration}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${statusClass}">
                                        ${entryStatus === 'active' ? 'Active' : 'Completed'}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    ${safeNotes ? `<span class="line-clamp-2" title="${safeNotes}">${safeNotes}</span>` : '—'}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <div class="relative inline-block text-left">
                                        <button type="button" class="dropdown-trigger inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 bg-white shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" data-tracker-id="${sanitize(tracker.trackingId)}" data-log-id="${sanitize(entry.logId)}" data-status="${entryStatus}">
                                            <i data-lucide="more-horizontal" class="h-4 w-4 text-gray-500"></i>
                                        </button>
                                        <div class="dropdown-menu hidden absolute right-0 bottom-full z-50 mb-2 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5">
                                            <div class="py-1">
                                                <button class="dropdown-item flex w-full items-center px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100" data-action="generate-log">
                                                    <i data-lucide="file-down" class="mr-2 h-4 w-4"></i>
                                                    <span>Generate Log Sheet</span>
                                                </button>
                                                <button class="dropdown-item flex w-full items-center px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100" data-action="print-log">
                                                    <i data-lucide="printer" class="mr-2 h-4 w-4"></i>
                                                    <span>Print Log Sheet</span>
                                                </button>
                                                ${entryStatus === 'active' ? `
                                                    <button class="dropdown-item flex w-full items-center px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-100" data-action="log-out">
                                                        <i data-lucide="log-out" class="mr-2 h-4 w-4"></i>
                                                        <span>Log Out</span>
                                                    </button>
                                                ` : ''}
                                                <hr class="my-1 border-gray-200">
                                                <button class="dropdown-item flex w-full items-center px-4 py-2 text-sm text-red-600 transition hover:bg-gray-100" data-action="delete-log">
                                                    <i data-lucide="trash-2" class="mr-2 h-4 w-4"></i>
                                                    <span>Delete Log Entry</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('')
                    : `
                        <tr>
                            <td colspan="8" class="px-6 py-6 text-center text-sm text-gray-600">
                                No office movements recorded for this tracker yet.
                            </td>
                        </tr>
                    `;

                return `
                    <div class="tracker-card overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-blue-400 px-6 py-5 text-white">
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <h3 class="text-2xl font-semibold leading-tight">${sanitize(tracker.fileName || 'Unnamed File')}</h3>
                                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                                        <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 font-mono font-semibold">${sanitize(tracker.fileNo || '—')}</span>
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 font-mono font-semibold text-red-800">${sanitize(tracker.trackingId || '—')}</span>
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${priorityBadge}">${priorityLabel} Priority</span>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${statusBadge}">${statusLabel}</span>
                                    <button class="view-details-btn inline-flex items-center rounded-md border border-white/40 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20" data-tracking-id="${sanitize(tracker.trackingId)}">
                                        <i data-lucide="eye" class="mr-2 h-4 w-4"></i>
                                        View Details
                                    </button>
                                    <button class="history-toggle inline-flex items-center rounded-md border border-white/40 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-white/20" data-tracker-key="${trackerKey || ''}" data-history-target="${historyTargetId}" data-expanded="${isExpanded ? 'true' : 'false'}">
                                        <i data-lucide="chevron-down" class="mr-2 h-4 w-4 transition-transform ${isExpanded ? 'rotate-180' : ''}"></i>
                                        <span>${isExpanded ? 'Hide history' : 'Show history'}</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-4 border-b border-gray-100 bg-gray-50 px-6 py-4 text-sm text-gray-700 md:grid-cols-4">
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Active Entries</span>
                                <div class="mt-1 text-lg font-semibold text-gray-900">${activeMovements.length}</div>
                                <p class="text-xs text-gray-500">Waiting for log-out confirmation</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Movements</span>
                                <div class="mt-1 text-lg font-semibold text-gray-900">${totalMovements}</div>
                                <p class="text-xs text-gray-500">Full office handover history</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Last Movement</span>
                                <div class="mt-1 text-lg font-semibold text-gray-900">${lastMovementDate}</div>
                                <p class="text-xs text-gray-500">Duration ${lastMovementDuration}</p>
                            </div>
                            <div>
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">Current Office</span>
                                <div class="mt-1 flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <span>${sanitize(currentOfficeName)}</span>
                                </div>
                                <p class="text-xs text-gray-500">Office ID ${sanitize(currentOfficeCode)}</p>
                            </div>
                        </div>
                        <div id="${historyTargetId}" class="tracker-history ${isExpanded ? '' : 'hidden'} px-6 py-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-white">
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <th class="px-4 py-3">Log ID</th>
                                            <th class="px-4 py-3">Office</th>
                                            <th class="px-4 py-3">Log In</th>
                                            <th class="px-4 py-3">Log Out</th>
                                            <th class="px-4 py-3">Duration</th>
                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Notes</th>
                                            <th class="px-4 py-3 text-right">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 bg-white">
                                        ${rows}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 border-t border-gray-100 bg-white px-6 py-4 text-sm text-gray-600 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-2 text-gray-700">
                                <i data-lucide="map-pin" class="h-4 w-4 text-gray-500"></i>
                                <span>Currently at <span class="font-semibold">${sanitize(currentOfficeName)}</span></span>
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">${sanitize(currentOfficeCode)}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Move file to:</label>
                                <select class="add-log-select w-64 rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" data-tracker-id="${sanitize(tracker.trackingId)}">
                                    <option value="">Select office...</option>
                                    ${Object.entries(officeData).map(([officeId, office]) => `
                                        <option value="${officeId}">${office.name} (${office.code})</option>
                                    `).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            container.innerHTML = cards;
            setupDropdownMenus();
            setupAddLogSelects();
            setupViewDetailsButtons();
            setupHistoryToggles();
            lucide.createIcons();
        }

        // Setup view details buttons
        function setupViewDetailsButtons() {
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const trackingId = this.dataset.trackingId;
                    showTrackerDetails(trackingId);
                });
            });
        }

        function setupHistoryToggles() {
            document.querySelectorAll('.history-toggle').forEach(button => {
                button.addEventListener('click', function() {
                    const trackerKey = this.dataset.trackerKey || this.dataset.historyTarget;
                    const targetId = this.dataset.historyTarget;
                    if (!targetId) {
                        return;
                    }

                    const historySection = document.getElementById(targetId);
                    if (!historySection) {
                        return;
                    }

                    const isExpanded = trackerKey && expandedTrackerHistory.has(trackerKey);

                    if (isExpanded) {
                        if (trackerKey) {
                            expandedTrackerHistory.delete(trackerKey);
                        }
                        historySection.classList.add('hidden');
                        this.dataset.expanded = 'false';
                    } else {
                        if (trackerKey) {
                            expandedTrackerHistory.add(trackerKey);
                        }
                        historySection.classList.remove('hidden');
                        this.dataset.expanded = 'true';
                    }

                    const icon = this.querySelector('[data-lucide="chevron-down"]');
                    if (icon) {
                        icon.classList.toggle('rotate-180', !isExpanded);
                    }

                    const label = this.querySelector('span');
                    if (label) {
                        label.textContent = !isExpanded ? 'Hide history' : 'Show history';
                    }
                });
            });
        }

        // Show tracker details
        function showTrackerDetails(trackingId) {
            const tracker = fileTrackers.find(t => t.trackingId === trackingId);
            if (!tracker) return;
            
            currentDetailsTracker = tracker;
            
            const priorityClass = `priority-${tracker.priority}`;
            const priorityText = tracker.priority.charAt(0) + tracker.priority.slice(1).toLowerCase();
            
            const detailsContent = document.getElementById('details-content');
            detailsContent.innerHTML = `
                <div class="py-4 space-y-6">
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div>
                            <h3 class="text-xl font-semibold">${tracker.fileName}</h3>
                            <div class="flex items-center gap-3 mt-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-600">File No:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 font-mono">${tracker.fileNo}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-600">Tracking ID:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 font-mono">${tracker.trackingId}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${priorityClass}">${priorityText}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${tracker.currentOfficeId}</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">File Information</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">File Name:</span>
                                        <span class="font-medium">${tracker.fileName}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">File No:</span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 font-mono">${tracker.fileNo}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Tracking ID:</span>
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 font-mono">${tracker.trackingId}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Priority:</span>
                                        <span class="font-medium ${priorityClass} px-2 py-0.5 rounded text-xs">${priorityText}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Created:</span>
                                        <span>${new Date(tracker.createdAt).toLocaleString()}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">Current Location</h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Office:</span>
                                        <span>${tracker.currentOffice}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Office ID:</span>
                                        <span class="font-mono">${tracker.currentOfficeId}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Department:</span>
                                        <span>${officeData[tracker.currentOfficeId]?.department || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Movement History</h4>
                            <div class="space-y-3">
                                ${tracker.logEntries.map((entry, index) => {
                                    const isActive = entry.status === 'active';
                                    const entryClass = isActive ? 'log-entry log-entry-active' : 'log-entry log-entry-completed';
                                    return `
                                    <div class="${entryClass} p-3 rounded">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-medium">${entry.officeName}</span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isActive ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                                ${isActive ? 'Active' : 'Completed'}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600 space-y-1">
                                            <div>Log ID: <span class="font-mono">${entry.logId}</span></div>
                                            <div>Logged In: ${entry.logInDate} at ${entry.logInTime}</div>
                                            ${entry.logOutDate ? `<div>Logged Out: ${entry.logOutDate} at ${entry.logOutTime}</div>` : ''}
                                            ${entry.notes ? `<div class="mt-1"><strong>Notes:</strong> ${entry.notes}</div>` : ''}
                                        </div>
                                    </div>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('details-dialog').classList.add('show');
            lucide.createIcons();

            // Bind print button after content is loaded
            document.getElementById('print-details-btn')?.addEventListener('click', () => {
                printFileTrackerDetails(tracker);
            });
        }

        // Print tracker details
        function printFileTrackerDetails(tracker) {
            const priorityText = tracker.priority.charAt(0) + tracker.priority.slice(1).toLowerCase();
            const currentDate = new Date().toLocaleString();

            const printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>File Tracker Details - ${tracker.trackingId}</title>
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        body {
                            font-family: Arial, sans-serif;
                            color: #333;
                            line-height: 1.6;
                            background: white;
                        }
                        .print-container {
                            max-width: 8.5in;
                            margin: 0 auto;
                            padding: 0.5in;
                            background: white;
                        }
                        .header {
                            text-align: center;
                            margin-bottom: 2rem;
                            padding-bottom: 1rem;
                            border-bottom: 3px solid #2563eb;
                        }
                        .header h1 {
                            font-size: 1.5rem;
                            margin-bottom: 0.5rem;
                            color: #1f2937;
                        }
                        .header-meta {
                            display: flex;
                            justify-content: space-around;
                            margin-top: 1rem;
                            font-size: 0.9rem;
                            color: #666;
                        }
                        .header-meta div {
                            flex: 1;
                            text-align: center;
                        }
                        .section {
                            margin-bottom: 1.5rem;
                            page-break-inside: avoid;
                        }
                        .section-title {
                            font-size: 1.1rem;
                            font-weight: bold;
                            color: #1f2937;
                            background: #f3f4f6;
                            padding: 0.5rem;
                            margin-bottom: 1rem;
                            border-left: 4px solid #2563eb;
                        }
                        .section-content {
                            display: grid;
                            grid-template-columns: 1fr 1fr;
                            gap: 1rem;
                        }
                        .info-group {
                            page-break-inside: avoid;
                        }
                        .info-row {
                            display: flex;
                            justify-content: space-between;
                            padding: 0.5rem 0;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        .info-row:last-child {
                            border-bottom: none;
                        }
                        .info-label {
                            font-weight: bold;
                            color: #374151;
                            width: 40%;
                        }
                        .info-value {
                            color: #666;
                            font-family: monospace;
                            word-break: break-word;
                        }
                        .table-section {
                            grid-column: 1 / -1;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            font-size: 0.9rem;
                        }
                        table th {
                            background: #f3f4f6;
                            border: 1px solid #d1d5db;
                            padding: 0.5rem;
                            text-align: left;
                            font-weight: bold;
                            color: #1f2937;
                        }
                        table td {
                            border: 1px solid #e5e7eb;
                            padding: 0.5rem;
                            vertical-align: top;
                        }
                        table tr:nth-child(even) {
                            background: #f9fafb;
                        }
                        .status-active {
                            color: #16a34a;
                            font-weight: bold;
                        }
                        .status-completed {
                            color: #666;
                        }
                        .priority-HIGH {
                            color: #dc2626;
                            font-weight: bold;
                        }
                        .priority-MEDIUM {
                            color: #f59e0b;
                            font-weight: bold;
                        }
                        .priority-LOW {
                            color: #10b981;
                            font-weight: bold;
                        }
                        .footer {
                            text-align: center;
                            font-size: 0.8rem;
                            color: #999;
                            margin-top: 2rem;
                            padding-top: 1rem;
                            border-top: 1px solid #e5e7eb;
                        }
                        @media print {
                            body {
                                margin: 0;
                                padding: 0;
                            }
                            .print-container {
                                max-width: 100%;
                                margin: 0;
                                padding: 0.5in;
                            }
                            .footer {
                                position: relative;
                                page-break-after: always;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-container">
                        <div class="header">
                            <h1>File Tracker Details Report</h1>
                            <div class="header-meta">
                                <div>
                                    <strong>File No:</strong> ${tracker.fileNo}
                                </div>
                                <div>
                                    <strong>Tracking ID:</strong> ${tracker.trackingId}
                                </div>
                                <div>
                                    <strong>Print Date:</strong> ${currentDate}
                                </div>
                            </div>
                        </div>

                        <div class="section">
                            <div class="section-title">File Information</div>
                            <div class="section-content">
                                <div class="info-group">
                                    <div class="info-row">
                                        <span class="info-label">File Name:</span>
                                        <span class="info-value">${tracker.fileName}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">File No:</span>
                                        <span class="info-value">${tracker.fileNo}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tracking ID:</span>
                                        <span class="info-value">${tracker.trackingId}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Priority:</span>
                                        <span class="info-value priority-${tracker.priority}">${priorityText}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Created:</span>
                                        <span class="info-value">${new Date(tracker.createdAt).toLocaleString()}</span>
                                    </div>
                                </div>

                                <div class="info-group">
                                    <div class="info-row">
                                        <span class="info-label">Current Office:</span>
                                        <span class="info-value">${tracker.currentOffice}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Office ID:</span>
                                        <span class="info-value">${tracker.currentOfficeId}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Department:</span>
                                        <span class="info-value">${officeData[tracker.currentOfficeId]?.department || 'N/A'}</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Handler:</span>
                                        <span class="info-value">${tracker.handler || 'N/A'}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section table-section">
                            <div class="section-title">Movement History</div>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Office</th>
                                        <th>Log In</th>
                                        <th>Log Out</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tracker.logEntries && tracker.logEntries.length > 0 ? tracker.logEntries.map(entry => `
                                        <tr>
                                            <td>${entry.logId}</td>
                                            <td>${entry.officeName}</td>
                                            <td>${entry.logInDate} ${entry.logInTime}</td>
                                            <td>${entry.logOutDate && entry.logOutTime ? entry.logOutDate + ' ' + entry.logOutTime : '—'}</td>
                                            <td>
                                                <span class="status-${entry.status || 'COMPLETED'}">
                                                    ${entry.status === 'active' ? 'Active' : 'Completed'}
                                                </span>
                                            </td>
                                            <td>${entry.notes || '—'}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="6" style="text-align: center;">No movement history available</td></tr>'}
                                </tbody>
                            </table>
                        </div>

                        <div class="footer">
                            <p>This is an official printout of file tracker details. Page generated automatically.</p>
                        </div>
                    </div>
                </body>
                </html>
            `;

            const printWindow = window.open('', '_blank');
            if (printWindow) {
                printWindow.document.write(printContent);
                printWindow.document.close();
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            } else {
                alert('Unable to open print preview. Please check your browser settings.');
            }
        }

        function setupAddLogSelects() {
            document.querySelectorAll('.add-log-select').forEach(select => {
                select.addEventListener('change', function() {
                    const trackerId = this.dataset.trackerId;
                    const officeId = this.value;
                    
                    if (officeId) {
                        // Store the pending log entry info
                        pendingLogEntry = { trackerId, officeId };
                        
                        // Show the notes dialog
                        document.getElementById('notes-dialog').classList.add('show');
                        document.getElementById('new-log-notes').value = '';
                        document.getElementById('new-log-notes').focus();
                        
                        this.value = ''; // Reset the select
                    }
                });
            });
        }

        function handleAddLogEntry(trackerId, officeId, notes) {
            const tracker = fileTrackers.find(t => t.trackingId === trackerId);
            const office = officeData[officeId];

            if (!tracker) {
                alert('Unable to locate this tracker. Please refresh and try again.');
                return;
            }

            if (!tracker.id) {
                alert('Please save this tracker before logging movements.');
                return;
            }

            if (!office) {
                alert('Invalid office selected.');
                return;
            }

            const now = new Date();
            const logInTime = now.toTimeString().split(' ')[0].substring(0, 5);
            const logInDate = now.toISOString().split('T')[0];
            const hasActiveEntry = tracker.logEntries.some(entry => (entry.status || '').toLowerCase() === 'active');

            const performAddMovement = () => {
                $.ajax({
                    url: `/api/file-trackers/${tracker.id}/movements`,
                    method: 'POST',
                    data: JSON.stringify({
                        office_code: officeId,
                        office_name: office.name,
                        log_in_time: logInTime,
                        log_in_date: logInDate,
                        notes: notes || null
                    }),
                    contentType: 'application/json',
                    processData: false,
                    success: function(response) {
                        if (response.success && response.data) {
                            const updatedTracker = transformApiTracker(response.data);
                            upsertLocalTracker(updatedTracker);
                            showNotification(`File moved to ${office.name}`, 'success');
                        } else {
                            const message = response.message || 'Unable to move file to the selected office.';
                            alert(message);
                        }
                    },
                    error: function(xhr) {
                        const message = xhr.responseJSON?.message || 'Unable to move file at this time.';
                        alert(message);
                    }
                });
            };

            if (!hasActiveEntry) {
                performAddMovement();
                return;
            }

            $.ajax({
                url: `/api/file-trackers/${tracker.id}/complete-movement`,
                method: 'POST',
                data: JSON.stringify({
                    log_out_time: logInTime
                }),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    if (response.success && response.data) {
                        const updatedTracker = transformApiTracker(response.data);
                        upsertLocalTracker(updatedTracker);
                        performAddMovement();
                    } else {
                        const message = response.message || 'Unable to complete the current movement.';
                        alert(message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Unable to complete the current movement.';
                    alert(message);
                }
            });
        }

        // Reset form
        function resetForm() {
            document.getElementById('file-no').value = '';
            document.getElementById('file-name').value = '';
            document.getElementById('file-priority').value = 'MEDIUM';
            document.getElementById('current-office').value = '';
            document.getElementById('current-office-id').value = '';
            document.getElementById('office-notes').value = '';
            document.getElementById('office-info').classList.add('hidden');
            document.getElementById('sidebar-file-no').textContent = '-';
            currentTracker = null;
            initializeIds();
        }

        // Event listeners
        document.getElementById('resetBtn').addEventListener('click', resetForm);
        document.getElementById('resetFormBtn').addEventListener('click', resetForm);
        document.getElementById('previewBtn').addEventListener('click', createFileTracker);
        document.getElementById('saveFileLogBtn').addEventListener('click', createFileTracker);
        document.getElementById('close-preview').addEventListener('click', () => {
            document.getElementById('preview-dialog').classList.remove('show');
        });
        document.getElementById('close-preview-btn').addEventListener('click', () => {
            document.getElementById('preview-dialog').classList.remove('show');
        });
        document.getElementById('save-tracker-btn').addEventListener('click', saveFileTracker);
        
        // Notes dialog event listeners
        document.getElementById('close-notes').addEventListener('click', () => {
            document.getElementById('notes-dialog').classList.remove('show');
            pendingLogEntry = null;
        });
        document.getElementById('cancel-notes').addEventListener('click', () => {
            document.getElementById('notes-dialog').classList.remove('show');
            pendingLogEntry = null;
        });
        document.getElementById('confirm-notes').addEventListener('click', () => {
            const notes = document.getElementById('new-log-notes').value;
            if (pendingLogEntry) {
                handleAddLogEntry(pendingLogEntry.trackerId, pendingLogEntry.officeId, notes);
                document.getElementById('notes-dialog').classList.remove('show');
                pendingLogEntry = null;
            }
        });

        // Details dialog event listeners
        document.getElementById('close-details').addEventListener('click', () => {
            document.getElementById('details-dialog').classList.remove('show');
        });
        document.getElementById('close-details-btn').addEventListener('click', () => {
            document.getElementById('details-dialog').classList.remove('show');
        });
        document.getElementById('print-details-btn').addEventListener('click', () => {
            if (currentDetailsTracker) {
                printFileTrackerDetails(currentDetailsTracker);
            } else {
                alert('No tracker data available to print');
            }
        });

        function setupDropdownMenus() {
            // Handle dropdown toggle
            document.querySelectorAll('.dropdown-trigger').forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const menu = this.nextElementSibling;
                    
                    // Close all other dropdowns
                    document.querySelectorAll('.dropdown-menu').forEach(otherMenu => {
                        if (otherMenu !== menu) {
                            otherMenu.classList.add('hidden');
                            otherMenu.style.position = '';
                            otherMenu.style.top = '';
                            otherMenu.style.left = '';
                            otherMenu.style.right = '';
                        }
                    });
                    
                    // Toggle current dropdown
                    if (menu.classList.contains('hidden')) {
                        const rect = this.getBoundingClientRect();
                        const menuHeight = 200; // Approximate height of dropdown menu
                        const spaceAbove = rect.top;
                        const spaceBelow = window.innerHeight - rect.bottom;
                        
                        // Position the dropdown
                        menu.style.position = 'fixed';
                        menu.style.right = (window.innerWidth - rect.right) + 'px';
                        
                        // Show above if there's more space above or if there's not enough space below
                        if (spaceAbove > menuHeight || spaceBelow < menuHeight) {
                            menu.style.top = (rect.top - menuHeight) + 'px';
                            menu.style.bottom = 'auto';
                        } else {
                            menu.style.top = (rect.bottom + 8) + 'px';
                            menu.style.bottom = 'auto';
                        }
                        
                        menu.classList.remove('hidden');
                    } else {
                        menu.classList.add('hidden');
                        menu.style.position = '';
                        menu.style.top = '';
                        menu.style.left = '';
                        menu.style.right = '';
                    }
                });
            });

            // Handle dropdown item clicks
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.addEventListener('click', function() {
                    const action = this.dataset.action;
                    const trigger = this.closest('.relative').querySelector('.dropdown-trigger');
                    const trackerId = trigger.dataset.trackerId;
                    const logId = trigger.dataset.logId;
                    const status = trigger.dataset.status;
                    
                    // Close dropdown
                    this.closest('.dropdown-menu').classList.add('hidden');
                    
                    // Handle different actions
                    switch(action) {
                        case 'generate-log':
                            alert(`Generating log sheet for ${logId}`);
                            break;
                        case 'print-log':
                            alert(`Printing log sheet for ${logId}`);
                            break;
                        case 'log-out':
                            handleLogOut(trackerId, logId);
                            break;
                        case 'delete-log':
                            handleDeleteLogEntry(trackerId, logId);
                            break;
                    }
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                document.querySelectorAll('.dropdown-menu').forEach(menu => {
                    menu.classList.add('hidden');
                    menu.style.position = '';
                    menu.style.top = '';
                    menu.style.left = '';
                    menu.style.right = '';
                });
            });
        }

        function handleLogOut(trackerId, logId) {
            const tracker = fileTrackers.find(t => t.trackingId === trackerId);

            if (!tracker) {
                alert('Unable to locate this tracker. Please refresh and try again.');
                return;
            }

            if (!tracker.id) {
                alert('Please save this tracker before logging out movements.');
                return;
            }

            const activeEntry = tracker.logEntries.find(entry => (entry.status || '').toLowerCase() === 'active');

            if (!activeEntry || activeEntry.logId !== logId) {
                alert('This log entry is no longer active.');
                return;
            }

            const now = new Date();
            const logOutTime = now.toTimeString().split(' ')[0].substring(0, 5);

            $.ajax({
                url: `/api/file-trackers/${tracker.id}/complete-movement`,
                method: 'POST',
                data: JSON.stringify({
                    log_out_time: logOutTime
                }),
                contentType: 'application/json',
                processData: false,
                success: function(response) {
                    if (response.success && response.data) {
                        const updatedTracker = transformApiTracker(response.data);
                        upsertLocalTracker(updatedTracker);
                        showNotification('File logged out successfully.', 'success');
                    } else {
                        const message = response.message || 'Unable to log out this file entry.';
                        alert(message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Unable to log out this file entry.';
                    alert(message);
                }
            });
        }

        function handleDeleteLogEntry(trackerId, logId) {
            if (confirm('Are you sure you want to delete this log entry?')) {
                const tracker = fileTrackers.find(t => t.trackingId === trackerId);
                if (tracker) {
                    tracker.logEntries = tracker.logEntries.filter(e => e.logId !== logId);
                    updateFileTrackersTable();
                    alert('Log entry deleted successfully!');
                }
            }
        }

        // Load existing file trackers from API
        function loadFileTrackers() {
            console.log('Loading file trackers from API...');
            $.ajax({
                url: '/create-file-tracker/list',
                method: 'GET',
                success: function(response) {
                    console.log('API Response:', response);
                    if (response.success && Array.isArray(response.data)) {
                        console.log('Converting API data, received:', response.data.length, 'trackers');
                        fileTrackers = response.data
                            .map(transformApiTracker)
                            .filter(Boolean);

                        window.fileTrackers = fileTrackers;
                        updateFileTrackersTable();
                    } else {
                        console.log('API response success but no data or data structure issue:', response);
                        fileTrackers = [];
                        window.fileTrackers = fileTrackers;
                        updateFileTrackersTable();
                    }
                },
                error: function(xhr) {
                    console.error('Error loading file trackers:', xhr);
                    fileTrackers = [];
                    window.fileTrackers = fileTrackers;
                    updateFileTrackersTable();
                }
            });
        }

        // Tab Management Functions
        function initializeTabs() {
            // Set up tab click handlers
            document.querySelectorAll('.tracker-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    switchTab(tabName);
                });
            });
            
            // Set up search functionality for File Log Table tab
            const searchInput = document.getElementById('search-files');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    filterFileLogTable(this.value);
                });
            }
            
            // Set up refresh button
            const refreshButton = document.getElementById('refresh-files');
            if (refreshButton) {
                refreshButton.addEventListener('click', function() {
                    loadFileTrackers();
                    updateOverviewStats();
                });
            }
            
            // Set up export button
            const exportButton = document.getElementById('export-files');
            if (exportButton) {
                exportButton.addEventListener('click', function() {
                    exportFileTrackers();
                });
            }
        }
        
        function switchTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.tracker-tab').forEach(tab => {
                tab.classList.remove('active');
                tab.classList.add('border-transparent', 'text-gray-500');
                tab.classList.remove('border-blue-500', 'text-blue-600');
            });
            
            document.getElementById(`tab-${tabName}`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById(`tab-${tabName}`).classList.add('border-blue-500', 'text-blue-600');
            
            // Update content visibility
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
                content.classList.add('hidden');
            });
            
            document.getElementById(`content-${tabName}`).classList.add('active');
            document.getElementById(`content-${tabName}`).classList.remove('hidden');
            
            // Update data based on tab
            if (tabName === 'overview') {
                updateOverviewStats();
            } else if (tabName === 'file-log') {
                updateFileTrackersTable();
            }
        }
        
        function updateOverviewStats() {
            // Update overview statistics
            const totalFiles = fileTrackers.length;
            const inTransitFiles = fileTrackers.filter(tracker => 
                tracker.logEntries.some(entry => entry.status === 'active')
            ).length;
            const completedFiles = fileTrackers.filter(tracker => 
                tracker.logEntries.every(entry => entry.status === 'completed')
            ).length;
            
            // Update summary cards
            document.getElementById('total-files').textContent = totalFiles;
            document.getElementById('in-transit-files').textContent = inTransitFiles;
            document.getElementById('completed-files').textContent = completedFiles;
            document.getElementById('file-count').textContent = totalFiles;
            
            // Update recent activity
            updateRecentActivity();
            updateQuickStats();
        }
        
        function updateRecentActivity() {
            const recentActivity = document.getElementById('recent-activity');
            
            if (fileTrackers.length === 0) {
                recentActivity.innerHTML = '<p class="text-gray-500 text-sm">No recent activity</p>';
                return;
            }
            
            // Get the 5 most recent log entries across all trackers
            const allLogEntries = [];
            fileTrackers.forEach(tracker => {
                tracker.logEntries.forEach(entry => {
                    allLogEntries.push({
                        ...entry,
                        fileNo: tracker.fileNo,
                        fileName: tracker.fileName
                    });
                });
            });
            
            const recentEntries = allLogEntries
                .sort((a, b) => new Date(b.logInDate + ' ' + b.logInTime) - new Date(a.logInDate + ' ' + a.logInTime))
                .slice(0, 5);
            
            recentActivity.innerHTML = recentEntries.map(entry => {
                const office = officeData[entry.officeId];
                const statusColor = entry.status === 'active' ? 'text-yellow-600' : 'text-green-600';
                const statusIcon = entry.status === 'active' ? 'clock' : 'check-circle';
                
                return `
                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                        <div class="flex items-center gap-3">
                            <i data-lucide="${statusIcon}" class="h-4 w-4 ${statusColor}"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900">${entry.fileName}</p>
                                <p class="text-xs text-gray-500">${entry.fileNo} → ${office ? office.name : entry.officeId}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">${entry.logInDate}</p>
                            <p class="text-xs text-gray-400">${entry.logInTime}</p>
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        function updateQuickStats() {
            const quickStats = document.getElementById('quick-stats');
            
            // Calculate some quick statistics
            const highPriorityFiles = fileTrackers.filter(tracker => tracker.priority === 'HIGH').length;
            const avgLogEntries = fileTrackers.length > 0 ? 
                (fileTrackers.reduce((sum, tracker) => sum + tracker.logEntries.length, 0) / fileTrackers.length).toFixed(1) : 0;
            
            const officeDistribution = {};
            fileTrackers.forEach(tracker => {
                const activeEntry = tracker.logEntries.find(entry => entry.status === 'active');
                if (activeEntry) {
                    const office = officeData[activeEntry.officeId];
                    const officeName = office ? office.name : activeEntry.officeId;
                    officeDistribution[officeName] = (officeDistribution[officeName] || 0) + 1;
                }
            });
            
            const topOffice = Object.entries(officeDistribution)
                .sort(([,a], [,b]) => b - a)[0];
            
            quickStats.innerHTML = `
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                    <span class="text-sm text-gray-600">High Priority Files</span>
                    <span class="text-sm font-medium text-gray-900">${highPriorityFiles}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                    <span class="text-sm text-gray-600">Avg. Movements per File</span>
                    <span class="text-sm font-medium text-gray-900">${avgLogEntries}</span>
                </div>
                <div class="flex items-center justify-between p-3 bg-white rounded-lg border">
                    <span class="text-sm text-gray-600">Busiest Office</span>
                    <span class="text-sm font-medium text-gray-900">${topOffice ? `${topOffice[0]} (${topOffice[1]})` : 'N/A'}</span>
                </div>
            `;
        }
        
        function filterFileLogTable(searchTerm) {
            const trackerCards = document.querySelectorAll('#trackers-container .border.rounded-lg');
            
            trackerCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const isVisible = text.includes(searchTerm.toLowerCase());
                card.style.display = isVisible ? 'block' : 'none';
            });
        }
        
        function exportFileTrackers() {
            if (fileTrackers.length === 0) {
                alert('No file trackers to export');
                return;
            }
            
            // Simple CSV export
            const csvData = [
                ['File Number', 'File Name', 'File Type', 'Priority', 'Current Location', 'Status', 'Created Date']
            ];
            
            fileTrackers.forEach(tracker => {
                const activeEntry = tracker.logEntries.find(entry => entry.status === 'active');
                const currentLocation = activeEntry && officeData[activeEntry.officeId] ? 
                    officeData[activeEntry.officeId].name : 'Unknown';
                const status = activeEntry ? 'In Transit' : 'Completed';
                
                csvData.push([
                    tracker.fileNo,
                    tracker.fileName,
                    tracker.fileType,
                    tracker.priority,
                    currentLocation,
                    status,
                    tracker.createdAt || 'N/A'
                ]);
            });
            
            const csvContent = csvData.map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.href = url;
            a.download = `file_trackers_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function calculateDuration(logInDate, logInTime, logOutDate, logOutTime) {
            if (!logInDate || !logInTime) {
                return '—';
            }

            if (!logOutDate || !logOutTime) {
                return 'Ongoing';
            }

            try {
                const start = new Date(`${logInDate}T${logInTime}`);
                const end = new Date(`${logOutDate}T${logOutTime}`);

                if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
                    return '—';
                }

                const diffMs = end.getTime() - start.getTime();
                const hours = Math.floor(diffMs / (1000 * 60 * 60));
                const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));

                if (hours === 0 && minutes === 0) {
                    return '<1m';
                }

                if (hours === 0) {
                    return `${minutes}m`;
                }

                return `${hours}h ${minutes.toString().padStart(2, '0')}m`;
            } catch (error) {
                return '—';
            }
        }

        function updateTrackerStats() {
            const totals = {
                trackers: fileTrackers.length,
                active: 0,
                highPriority: 0,
                movements: 0
            };

            fileTrackers.forEach(tracker => {
                if ((tracker.priority || '').toUpperCase() === 'HIGH') {
                    totals.highPriority += 1;
                }

                const movements = tracker.logEntries || [];
                totals.movements += movements.length;

                if (movements.some(entry => (entry.status || '').toLowerCase() === 'active')) {
                    totals.active += 1;
                }
            });

            const totalEl = document.getElementById('total-trackers');
            const activeEl = document.getElementById('active-trackers');
            const highEl = document.getElementById('high-priority');
            const movementEl = document.getElementById('total-movements');

            if (totalEl) totalEl.textContent = totals.trackers;
            if (activeEl) activeEl.textContent = totals.active;
            if (highEl) highEl.textContent = totals.highPriority;
            if (movementEl) movementEl.textContent = totals.movements;
        }

        function exportFileTrackersToCSV() {
            if (!fileTrackers.length) {
                alert('No file trackers to export');
                return;
            }

            const headers = [
                'File Number',
                'File Name',
                'Tracking ID',
                'Priority',
                'Log ID',
                'Office Code',
                'Office Name',
                'Log In',
                'Log Out',
                'Duration',
                'Status',
                'Notes',
                'Current Office',
                'Created At'
            ];

            const rows = [];

            fileTrackers.forEach(tracker => {
                const movements = tracker.logEntries && tracker.logEntries.length ? tracker.logEntries : [null];

                movements.forEach(entry => {
                    const logIn = entry ? `${entry.logInDate || ''} ${entry.logInTime || ''}`.trim() : '';
                    const logOut = entry ? `${entry.logOutDate || ''} ${entry.logOutTime || ''}`.trim() : '';
                    const officeName = entry
                        ? (entry.officeName || entry.office || officeData[entry.officeId]?.name || '')
                        : '';
                    const duration = entry ? calculateDuration(entry.logInDate, entry.logInTime, entry.logOutDate, entry.logOutTime) : '';

                    rows.push([
                        tracker.fileNo || '',
                        tracker.fileName || '',
                        tracker.trackingId || '',
                        (tracker.priority || '').toUpperCase(),
                        entry ? entry.logId || '' : '',
                        entry ? entry.officeId || '' : '',
                        officeName,
                        logIn,
                        logOut,
                        duration,
                        entry ? (entry.status || '') : '',
                        entry ? (entry.notes || '') : '',
                        tracker.currentOffice || '',
                        tracker.createdAt || ''
                    ]);
                });
            });

            const formatCell = (value) => {
                const cell = (value ?? '').toString();
                const escaped = cell.replace(/"/g, '""');
                return /[",\n]/.test(escaped) ? `"${escaped}"` : escaped;
            };

            const csvLines = [headers, ...rows]
                .map(row => row.map(formatCell).join(','))
                .join('\n');

            const blob = new Blob([csvLines], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `file-trackers-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        }

        // Two-Tab System Functions
        function initializeMainTabs() {
            // Set up main tab click handlers
            document.querySelectorAll('.main-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabName = this.getAttribute('data-tab');
                    switchMainTab(tabName);
                });
            });
            
            // Set up search functionality for logs tab
            const searchLogsInput = document.getElementById('search-logs');
            if (searchLogsInput) {
                searchLogsInput.addEventListener('input', function() {
                    filterTrackerLogs(this.value);
                });
            }
            
            // Set up refresh button for logs tab
            const refreshLogsButton = document.getElementById('refresh-logs');
            if (refreshLogsButton) {
                refreshLogsButton.addEventListener('click', function() {
                    loadFileTrackers();
                    updateLogCount();
                });
            }

            const exportLogsButton = document.getElementById('export-logs');
            if (exportLogsButton) {
                exportLogsButton.addEventListener('click', function() {
                    exportFileTrackersToCSV();
                });
            }
        }
        
        function switchMainTab(tabName) {
            console.log('Switching to tab:', tabName);
            
            // Update tab buttons
            document.querySelectorAll('.main-tab').forEach(tab => {
                tab.classList.remove('active');
                tab.classList.add('border-transparent', 'text-gray-500');
                tab.classList.remove('border-blue-500', 'text-blue-600');
            });
            
            document.getElementById(`tab-${tabName}`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.remove('border-transparent', 'text-gray-500');
            document.getElementById(`tab-${tabName}`).classList.add('border-blue-500', 'text-blue-600');
            
            // Update content visibility
            document.querySelectorAll('.main-tab-content').forEach(content => {
                content.classList.remove('active');
                content.classList.add('hidden');
            });
            
            const targetContent = document.getElementById(`content-${tabName}`);
            console.log('Target content element:', targetContent);
            if (targetContent) {
                console.log('Before changes - classes:', targetContent.className);
                console.log('Before changes - computed display:', window.getComputedStyle(targetContent).display);
                
                targetContent.classList.add('active');
                targetContent.classList.remove('hidden');
                
                console.log('After changes - classes:', targetContent.className);
                console.log('After changes - computed display:', window.getComputedStyle(targetContent).display);
                console.log('Tab content should now be visible');
            } else {
                console.error('Target content element not found:', `content-${tabName}`);
            }
            
            // Update data based on tab
            if (tabName === 'logs') {
                console.log('Loading logs tab, fileTrackers:', fileTrackers.length);
                updateFileTrackersTable();
                updateLogCount();
            }
        }
        
        function updateLogCount() {
            const logCount = document.getElementById('log-count');
            if (logCount) {
                logCount.textContent = fileTrackers.length;
            }
        }
        
        function filterTrackerLogs(searchTerm) {
            const trackerCards = document.querySelectorAll('#trackers-container .tracker-card');
            const term = (searchTerm || '').toLowerCase();

            trackerCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                card.style.display = text.includes(term) ? 'block' : 'none';
            });
        }

        // Initialize
        initializeIds();
        
        // Add file number field change listener
        $('#file-no').on('input change', function(event) {
            const fileNo = ($(this).val() || '').trim();
            $('#sidebar-file-no').text(fileNo || '-');

            if (event.type === 'change') {
                triggerMetadataLookup(fileNo, { force: forceNextMetadataLookup });
                forceNextMetadataLookup = false;
            }
        });
        
        updateDateTime();
        loadFileTrackers(); // Load existing data
        initializeFileNoModal(); // Initialize file number modal
        QuickActions.init(); // Initialize quick actions
        initializeMainTabs(); // Initialize two-tab system
        switchMainTab('create'); // Set default tab to create
        setInterval(updateDateTime, 1000);
        lucide.createIcons();
    </script>