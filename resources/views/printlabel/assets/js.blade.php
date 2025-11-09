    <script>
        // Wait for DOM and libraries to load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Check for ST filter from URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const stFilterActive = urlParams.get('url') === 'st';
            
            // Application state
            let state = {
                selectedFiles: [],
                labelSize: "30-in-1",
                labelFormat: "qrcode",
                activeTab: "files",
                copies: 1,
                selectedTemplate: "30-in-1",
                showHistory: false,
                searchTerm: "",
                orientation: "portrait",
                showAdvancedOptions: false,
                batchMode: false,
                batchStartNumber: 1,
                batchCount: 30,
                availableFiles: [],
                generatedBatches: [],
                currentPage: 1,
                totalPages: 1,
                loading: false,
                stFilterActive: stFilterActive, // Add ST filter state
                currentBatchId: null,
            };

            const PRINT_TEMPLATE_URL = "{{ route('printlabel.print-template') }}";

            // API endpoints
            const API = {
                files: '/printlabel/api/files',
                createBatch: '/printlabel/api/batch',
                batches: '/printlabel/api/batches',
                batchDetails: '/printlabel/api/batch/',
                batchForPrinting: '/printlabel/api/batch/',
                markPrinted: '/printlabel/api/batch/',
                deleteBatch: '/printlabel/api/batch/',
                statistics: '/printlabel/api/statistics'
            };

            // Utility functions
            function showLoading(message = 'Loading...') {
                state.loading = true;
                console.log(message);
            }

            function hideLoading() {
                state.loading = false;
            }

            function showError(message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    confirmButtonColor: '#3b82f6'
                });
                console.error(message);
            }

            function showSuccess(message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: message,
                    timer: 3000,
                    showConfirmButton: false
                });
                console.log(message);
            }

            function showLoading(message = 'Loading...') {
                Swal.fire({
                    title: 'Please wait...',
                    text: message,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                state.loading = true;
                console.log(message);
            }

            function hideLoading() {
                if (state.loading) {
                    Swal.close();
                    state.loading = false;
                }
            }

            // API functions
            function fetchAvailableFiles(search, page) {
                search = search || '';
                page = page || 1;
                
                showLoading('Loading files...');
                const params = new URLSearchParams({
                    search: search,
                    page: page,
                    per_page: 30
                });
                
                // Add ST filter parameter if active
                if (state.stFilterActive) {
                    params.append('st_filter', 'true');
                }
                
                fetch(API.files + '?' + params)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            state.availableFiles = data.data;
                            state.currentPage = data.pagination.current_page;
                            state.totalPages = data.pagination.last_page;
                            renderFileList();
                            updateCounts();
                        } else {
                            showError(data.message);
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        showError('Failed to fetch files: ' + error.message);
                        hideLoading();
                    });
            }

            function createLabelBatch() {
                if (state.selectedFiles.length === 0) {
                    showError('Please select at least one file');
                    return;
                }

                // For ST mode, allow any number of files. For regular mode, keep 30 file limit
                if (!state.stFilterActive && state.selectedFiles.length > 30) {
                    showError('Cannot select more than 30 files per batch');
                    return;
                }

                showLoading('Creating batch...');
                
                fetch(API.createBatch, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        file_ids: state.selectedFiles,
                        label_format: state.selectedTemplate,
                        orientation: state.orientation,
                        batch_size: state.batchCount
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Batch ' + data.data.batch_number + ' created successfully with ' + data.data.file_count + ' files');
                        state.selectedFiles = [];
                        updateCounts();
                        renderFileList();
                        fetchAvailableFiles(); // Refresh the file list
                        fetchGeneratedBatches(); // Refresh batches
                        switchTab('generated'); // Switch to generated tab
                    } else {
                        showError(data.message);
                    }
                    hideLoading();
                })
                .catch(error => {
                    showError('Failed to create batch: ' + error.message);
                    hideLoading();
                });
            }

            function fetchGeneratedBatches(status, page) {
                status = status || '';
                page = page || 1;
                
                showLoading('Loading batches...');
                const params = new URLSearchParams({
                    status: status,
                    page: page,
                    per_page: 20
                });
                
                fetch(API.batches + '?' + params)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            state.generatedBatches = data.data;
                            renderBatchList();
                        } else {
                            showError(data.message);
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        showError('Failed to fetch batches: ' + error.message);
                        hideLoading();
                    });
            }

            function markBatchAsPrinted(batchId) {
                showLoading('Marking batch as printed...');
                
                fetch(API.markPrinted + batchId + '/print', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Batch marked as printed successfully');
                        fetchGeneratedBatches(); // Refresh batches
                        fetchStatistics(); // Refresh statistics
                    } else {
                        showError(data.message);
                    }
                    hideLoading();
                })
                .catch(error => {
                    showError('Failed to mark batch as printed: ' + error.message);
                    hideLoading();
                });
            }

            function deleteBatch(batchId) {
                if (!confirm('Are you sure you want to delete this batch?')) {
                    return;
                }

                showLoading('Deleting batch...');
                
                fetch(API.deleteBatch + batchId, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Batch deleted successfully');
                        fetchGeneratedBatches(); // Refresh batches
                        fetchStatistics(); // Refresh statistics
                    } else {
                        showError(data.message);
                    }
                    hideLoading();
                })
                .catch(error => {
                    showError('Failed to delete batch: ' + error.message);
                    hideLoading();
                });
            }

            function fetchStatistics() {
                // Build URL with ST filter if active
                let statisticsUrl = API.statistics;
                if (state.stFilterActive) {
                    const params = new URLSearchParams();
                    params.append('st_filter', 'true');
                    statisticsUrl += '?' + params.toString();
                }
                
                fetch(statisticsUrl)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateStatistics(data.data);
                        } else {
                            console.error('Failed to fetch statistics:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Failed to fetch statistics:', error.message);
                    });
            }

            function updateStatistics(stats) {
                // Only update available files count if we're in ST mode (url=st) AND the element exists
                const availableFilesElement = document.getElementById('availableFilesCount');
                if (state.stFilterActive && availableFilesElement) {
                    availableFilesElement.textContent = stats.available_files;
                }
                if (document.getElementById('totalBatchesCount')) {
                    document.getElementById('totalBatchesCount').textContent = stats.total_batches;
                }
                if (document.getElementById('generatedBatchesCount')) {
                    document.getElementById('generatedBatchesCount').textContent = stats.generated_batches;
                }
                if (document.getElementById('printedBatchesCount')) {
                    document.getElementById('printedBatchesCount').textContent = stats.printed_batches;
                }
                if (document.getElementById('completedBatchesCount')) {
                    document.getElementById('completedBatchesCount').textContent = stats.completed_batches;
                }
            }

            function normalizeLocationValue(rawValue) {
                if (!rawValue || rawValue === 'null') {
                    return 'Shelf/Rack-N/A';
                }
                const value = String(rawValue).trim();
                if (!value) {
                    return 'Shelf/Rack-N/A';
                }
                return /^shelf\/?rack/i.test(value)
                    ? value.replace(/\s+/g, ' ')
                    : `Shelf/Rack-${value}`;
            }

            function extractShelfValue(locationText) {
                if (!locationText) {
                    return 'N/A';
                }
                const cleaned = locationText.replace(/^(Shelf\/Rack[-:\s]*)/i, '').trim();
                return cleaned || 'N/A';
            }

            function getDisplayShelfValue(primary, secondary) {
                const candidates = [primary, secondary]
                    .map((value) => (value ?? '').toString().trim())
                    .filter((value) => {
                        if (!value) {
                            return false;
                        }

                        const upper = value.toUpperCase();
                        return upper !== 'N/A' && upper !== 'SHELF/RACK-N/A';
                    });

                if (!candidates.length) {
                    return '';
                }

                return candidates[0].replace(/^(Shelf\/Rack[-:\s]*)/i, '').trim();
            }

            function deriveFileNumbers(file) {
                const normalize = (value) => {
                    if (value === undefined || value === null) {
                        return '';
                    }
                    return String(value).trim();
                };

                const motherNp = normalize(file?.mother_np_fileno);
                const subNp = normalize(file?.sub_np_fileno);
                const motherLegacy = normalize(file?.mother_fileno);
                const subLegacy = normalize(file?.sub_fileno);
                const stFill = normalize(file?.st_fillno);
                const indexingNumber = normalize(file?.file_number);

                const isSubApplication = Boolean(
                    file?.subapplication_id ||
                    subNp ||
                    subLegacy
                );

                const pickFirst = (...values) => values.find((value) => Boolean(value));

                let primaryNumber;
                let secondaryNumber;

                if (state.stFilterActive && isSubApplication) {
                    primaryNumber = pickFirst(
                        subLegacy,
                        motherLegacy,
                        indexingNumber,
                        subNp,
                        motherNp
                    );

                    secondaryNumber = pickFirst(
                        subNp,
                        motherNp,
                        stFill,
                        motherLegacy,
                        subLegacy
                    );
                } else {
                    primaryNumber = pickFirst(
                        motherNp,
                        subNp,
                        indexingNumber,
                        motherLegacy,
                        subLegacy
                    );

                    secondaryNumber = pickFirst(
                        motherLegacy,
                        subLegacy,
                        stFill
                    );
                }

                if (secondaryNumber && secondaryNumber === primaryNumber) {
                    const fallbackCandidates = (state.stFilterActive && isSubApplication)
                        ? [
                            motherNp,
                            subNp,
                            stFill,
                            motherLegacy,
                            subLegacy,
                            indexingNumber,
                        ]
                        : [
                            motherLegacy,
                            subLegacy,
                            stFill,
                            motherNp,
                            subNp,
                        ];

                    secondaryNumber = pickFirst(
                        ...fallbackCandidates.filter((value) => value && value !== primaryNumber)
                    );
                }

                const isSTContext = Boolean(
                    state.stFilterActive ||
                    motherNp ||
                    subNp ||
                    motherLegacy ||
                    subLegacy ||
                    stFill
                );

                return {
                    primaryNumber: primaryNumber || indexingNumber || null,
                    secondaryNumber: secondaryNumber || null,
                    isSTContext,
                };
            }

            function getSelectedFilesData() {
                return state.selectedFiles
                    .map((fileId) => state.availableFiles.find((f) => f.id === fileId))
                    .filter((file) => file !== undefined);
            }

            function collectBaseLabelEntries() {
                const entries = [];

                if (state.batchMode) {
                    const total = Math.max(1, parseInt(state.batchCount || 0, 10));
                    for (let i = 0; i < total; i++) {
                        const fileNumber = generateBatchFileNumber(i);
                        const rawShelf = `Shelf/Rack-${String.fromCharCode(65 + Math.floor(i / 10))}${((i % 10) + 1)
                            .toString()
                            .padStart(2, '0')}`;
                        const shelfLabel = normalizeLocationValue(rawShelf);
                        const shelfValue = extractShelfValue(shelfLabel);
                        const trackingId = `TRK-${fileNumber}`;
                        const qrValue = trackingId;

                        entries.push({
                            id: `batch-${fileNumber}`,
                            fileNumber,
                            primaryFileNumber: fileNumber,
                            secondaryFileNumber: null,
                            isSTFile: false,
                            shelfLabel,
                            shelfValue,
                            trackingId,
                            fileTitle: '',
                            qrValue,
                        });
                    }
                    return entries;
                }

                const selectedFiles = getSelectedFilesData();
                selectedFiles.forEach((file) => {
                    const shelfLabel = normalizeLocationValue(file.shelf_location);
                    const shelfValue = extractShelfValue(shelfLabel);
                    const trackingIdSource = (file.tracking_id ?? '').toString().trim();
                    const fallbackTrackingId = file.batch_no
                        ? `BATCH-${file.batch_no}`
                        : file.id
                            ? `IDX-${file.id}`
                            : `IDX-${Math.random().toString(36).slice(2, 8)}`;
                    const trackingId = (trackingIdSource || fallbackTrackingId || '').toString();

                    let qrValue = trackingId;
                    if (file.qr_code_data) {
                        if (typeof file.qr_code_data === 'string') {
                            qrValue = file.qr_code_data;
                        } else {
                            try {
                                qrValue = String(file.qr_code_data.tracking_id || trackingId);
                            } catch (error) {
                                qrValue = trackingId;
                            }
                        }
                    }

                    const { primaryNumber, secondaryNumber, isSTContext } = deriveFileNumbers(file);
                    const displayPrimary = primaryNumber || file.file_number || '';
                    const displaySecondary = secondaryNumber;

                    entries.push({
                        id: file.id,
                        fileNumber: displayPrimary,
                        primaryFileNumber: primaryNumber || null,
                        secondaryFileNumber: displaySecondary || null,
                        originalFileNumber: file.file_number,
                        isSTFile: isSTContext,
                        shelfLabel,
                        shelfValue,
                        trackingId,
                        fileTitle: file.file_title || '',
                        qrValue,
                    });
                });

                return entries;
            }

            function buildLabelPayload() {
                const baseEntries = collectBaseLabelEntries();
                const copies = Math.max(1, parseInt(state.copies || 1, 10));
                const labels = [];

                baseEntries.forEach((entry) => {
                    for (let copyIndex = 0; copyIndex < copies; copyIndex++) {
                        labels.push({
                            file_number: entry.fileNumber,
                            primary_number: entry.primaryFileNumber || entry.fileNumber,
                            secondary_number: entry.secondaryFileNumber,
                            is_st: entry.isSTFile,
                            shelf_label: entry.shelfLabel,
                            shelf_value: entry.shelfValue,
                            file_title: entry.fileTitle,
                            tracking_id: entry.trackingId,
                            qr_value: entry.qrValue,
                            copy_number: copyIndex + 1,
                            copy_total: copies,
                        });
                    }
                });

                const templateSelect = document.getElementById('labelTemplate');
                const sizeSelect = document.getElementById('labelSize');

                const templateLabel = templateSelect
                    ? templateSelect.selectedOptions[0].text.split(' - ')[0]
                    : state.selectedTemplate;

                const sizeLabel = sizeSelect ? sizeSelect.selectedOptions[0].text : state.labelSize;

                const pages = labels.length === 0 ? 0 : Math.ceil(labels.length / 30);

                return {
                    baseEntries,
                    labels,
                    previewEntries: baseEntries.slice(0, 12),
                    summary: {
                        files: baseEntries.length,
                        copies,
                        totalLabels: labels.length,
                        pages,
                        templateLabel,
                        sizeLabel,
                        formatLabel: state.labelFormat === 'barcode' ? 'Barcode' : 'QR Code',
                    },
                    meta: {
                        template: state.selectedTemplate,
                        format: state.labelFormat,
                        orientation: state.orientation,
                        stFilter: state.stFilterActive,
                        batchMode: state.batchMode,
                        batchId: state.currentBatchId || null,
                        generatedAt: new Date().toISOString(),
                    },
                };
            }

            function embedQrImages(payload) {
                if (!payload || !Array.isArray(payload.labels) || payload.labels.length === 0) {
                    return payload;
                }

                if (payload.meta && payload.meta.qrImagesEmbedded) {
                    return payload;
                }

                if (typeof QRious === 'undefined') {
                    return payload;
                }

                payload.labels.forEach((label) => {
                    if (label.qr_image) {
                        return;
                    }

                    let qrValue = label.qr_value;
                    if (qrValue && typeof qrValue !== 'string') {
                        qrValue = String(qrValue);
                    }

                    if (!qrValue) {
                        return;
                    }

                    try {
                        const qrString = String(qrValue).trim();
                        if (!qrString) {
                            return;
                        }
                        const qr = new QRious({
                            value: qrString,
                            size: 240,
                            level: 'M',
                            background: '#ffffff',
                            foreground: '#000000',
                        });
                        label.qr_image = qr.toDataURL();
                    } catch (error) {
                        console.warn('Failed to embed QR image for label', label.file_number, error);
                    }
                });

                if (payload.meta) {
                    payload.meta.qrImagesEmbedded = true;
                } else {
                    payload.meta = { qrImagesEmbedded: true };
                }

                return payload;
            }

            function renderEmptyPreview(previewContent) {
                previewContent.innerHTML = `
                    <div class="preview-empty">
                        <div class="preview-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2" ry="2"></rect>
                                <path d="M3 10h18"></path>
                                <path d="M8 6v4"></path>
                            </svg>
                        </div>
                        <p class="text-sm">No files selected yet.</p>
                        <p class="text-xs text-slate-500">Pick some files and configure the settings to see the labels here.</p>
                    </div>
                `;
            }

            function renderPreview(payload) {
                const previewContent = document.getElementById('previewContent');
                if (!previewContent) {
                    return;
                }

                if (!payload.baseEntries.length) {
                    renderEmptyPreview(previewContent);
                    return;
                }

                previewContent.innerHTML = '';

                const grid = document.createElement('div');
                grid.className = 'label-preview-grid';

                payload.previewEntries.forEach((entry, index) => {
                    const card = document.createElement('div');
                    card.className = 'label-preview-card';

                    const qrWrap = document.createElement('div');
                    qrWrap.className = 'label-preview-qr';

                    const canvas = document.createElement('canvas');
                    canvas.width = 180;
                    canvas.height = 180;
                    qrWrap.appendChild(canvas);

                    const meta = document.createElement('div');
                    meta.className = 'label-preview-meta';

                    const primaryLine = document.createElement('div');
                    primaryLine.className = 'label-preview-file';
                    primaryLine.textContent = entry.primaryFileNumber || entry.fileNumber || '—';
                    meta.appendChild(primaryLine);

                    if (entry.secondaryFileNumber && (entry.isSTFile || entry.secondaryFileNumber !== entry.primaryFileNumber)) {
                        const secondaryLine = document.createElement('div');
                        secondaryLine.className = 'label-preview-file label-preview-file--secondary';
                        secondaryLine.textContent = entry.secondaryFileNumber;
                        meta.appendChild(secondaryLine);
                    }

                    const shelfDisplayValue = getDisplayShelfValue(entry.shelfValue, entry.shelfLabel);
                    const shelfLine = document.createElement('div');
                    shelfLine.className = 'label-preview-location';
                    shelfLine.textContent = `Shelf/Rack: ${shelfDisplayValue || ''}`;
                    meta.appendChild(shelfLine);

                    card.appendChild(qrWrap);
                    card.appendChild(meta);
                    grid.appendChild(card);

                    setTimeout(() => {
                        if (typeof QRious !== 'undefined') {
                            try {
                                new QRious({
                                    element: canvas,
                                    value: String(entry.qrValue || '').trim(),
                                    size: 180,
                                    level: 'M',
                                    background: '#ffffff',
                                    foreground: '#111827',
                                });
                            } catch (error) {
                                console.error('QR preview error:', error);
                                const ctx = canvas.getContext('2d');
                                ctx.fillStyle = '#f3f4f6';
                                ctx.fillRect(0, 0, 180, 180);
                                ctx.fillStyle = '#9ca3af';
                                ctx.textAlign = 'center';
                                ctx.font = '12px Arial';
                                ctx.fillText('QR error', 90, 95);
                            }
                        }
                    }, 40 + index * 20);
                });

                previewContent.appendChild(grid);

                if (payload.baseEntries.length > payload.previewEntries.length) {
                    const overflow = document.createElement('p');
                    overflow.className = 'text-xs text-gray-500 mt-3 text-center';
                    overflow.textContent = `Showing ${payload.previewEntries.length} of ${payload.baseEntries.length} labels. All labels will print.`;
                    previewContent.appendChild(overflow);
                }
            }

            function updatePrintSummaryCard(payload) {
                const printSummary = document.getElementById('printSummary');
                if (!printSummary) {
                    return;
                }

                if (!payload.baseEntries.length) {
                    printSummary.innerHTML = '<p class="preview-note">Once you select files, we’ll summarise the number of labels, copies, and template details here.</p>';
                    return;
                }

                const { files, copies, totalLabels, pages, templateLabel, sizeLabel, formatLabel } = payload.summary;
                const printedOn = new Date().toLocaleString();

                printSummary.innerHTML = `
                    <div class="flex justify-between">
                        <span class="text-sm">Labels selected:</span>
                        <span class="text-sm font-medium">${files}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Copies per label:</span>
                        <span class="text-sm font-medium">${copies}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Total labels to print:</span>
                        <span class="text-sm font-medium">${totalLabels}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Expected pages:</span>
                        <span class="text-sm font-medium">${pages || 1}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Template:</span>
                        <span class="text-sm font-medium">${templateLabel}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Label size:</span>
                        <span class="text-sm font-medium">${sizeLabel}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Format:</span>
                        <span class="text-sm font-medium">${formatLabel}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm">Prepared on:</span>
                        <span class="text-sm font-medium">${printedOn}</span>
                    </div>
                `;
            }

            // Rendering functions
            function renderFileList() {
                const fileListContent = document.getElementById("fileListContent");
                
                if (state.availableFiles.length === 0) {
                    fileListContent.innerHTML = `
                        <div class="p-8 text-center text-gray-500">
                            <div class="mb-2">
                                <i data-lucide="file-text" class="h-8 w-8 mx-auto text-gray-400"></i>
                            </div>
                            <p>No files available for label printing</p>
                            <p class="text-sm">Files need to have a batch number and not already have labels printed</p>
                        </div>
                    `;
                    lucide.createIcons();
                    return;
                }

                const filteredFiles = filterFiles();
                fileListContent.innerHTML = filteredFiles
                    .map(
                        (file) => `
                    <div class="flex items-center p-4">
                        <input type="checkbox" id="${
                            file.id
                        }" class="file-checkbox mr-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" ${
                            state.selectedFiles.includes(file.id) ? "checked" : ""
                        }>
                        <div class="flex flex-1 items-center gap-3">
                            <i data-lucide="file-text" class="h-8 w-8 text-blue-500"></i>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium text-blue-600">${
                                        file.file_number
                                    }</p>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">${
                                        file.land_use_type || 'File'
                                    }</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">${
                                    file.file_title || 'No title'
                                }</p>
                                <div class="flex flex-wrap items-center gap-2 mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${
                                        file.shelf_location || 'No location'
                                    }</span>
                                    <span class="text-xs text-gray-500">Plot: ${file.plot_number || 'N/A'}</span>
                                    <span class="text-xs text-gray-500">District: ${file.district || 'N/A'}</span>
                                    <span class="text-xs text-gray-500">LGA: ${file.lga || 'N/A'}</span>
                                    <span class="text-xs text-gray-500">Batch: ${file.batch_no || 'N/A'}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i data-lucide="check-circle" class="h-3 w-3 mr-1"></i>
                                Indexed
                            </span>
                        </div>
                    </div>
                `
                    )
                    .join("");

                // Re-initialize icons
                lucide.createIcons();

                // Add event listeners to checkboxes
                document.querySelectorAll(".file-checkbox").forEach((checkbox) => {
                    checkbox.addEventListener("change", function () {
                        const fileId = parseInt(this.id);
                        if (this.checked) {
                            // For ST mode, remove the file limit. For regular mode, keep 30 file limit
                            if (!state.stFilterActive && state.selectedFiles.length >= 30) {
                                this.checked = false;
                                showError('Cannot select more than 30 files per batch');
                                return;
                            }
                            if (!state.selectedFiles.includes(fileId)) {
                                state.selectedFiles.push(fileId);
                            }
                        } else {
                            state.selectedFiles = state.selectedFiles.filter(
                                (id) => id !== fileId
                            );
                        }
                        updateCounts();
                        updateSelectAllCheckbox();
                    });
                });
            }

            function renderBatchList() {
                const batchListContent = document.getElementById('batchListContent');
                
                if (state.generatedBatches.length === 0) {
                    batchListContent.innerHTML = `
                        <div class="p-8 text-center text-gray-500">
                            <div class="mb-2">
                                <i data-lucide="package" class="h-8 w-8 mx-auto text-gray-400"></i>
                            </div>
                            <p>No batches generated yet</p>
                            <p class="text-sm">Create your first batch in the "Select Files" tab</p>
                        </div>
                    `;
                    lucide.createIcons();
                    return;
                }

                let html = '';
                const statusColors = {
                    'pending': 'bg-yellow-100 text-yellow-800',
                    'generated': 'bg-blue-100 text-blue-800',
                    'printed': 'bg-green-100 text-green-800',
                    'completed': 'bg-gray-100 text-gray-800'
                };
                
                for (let i = 0; i < state.generatedBatches.length; i++) {
                    const batch = state.generatedBatches[i];
                    const statusClass = statusColors[batch.status] || 'bg-gray-100 text-gray-800';
                    const createdDate = new Date(batch.created_at).toLocaleDateString();
                    const creatorName = batch.creator ? batch.creator.name : 'Unknown';
                    const statusCapitalized = batch.status.charAt(0).toUpperCase() + batch.status.slice(1);
                    
                    html += `<div class="p-3 grid grid-cols-7 gap-4 hover:bg-gray-50">
                        <div class="font-medium">${batch.batch_number}</div>
                        <div class="text-sm">${createdDate}</div>
                        <div class="text-sm">${batch.batch_items ? batch.batch_items.length : 0}/${batch.batch_size}</div>
                        <div class="text-sm">${batch.label_format}</div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                ${statusCapitalized}
                            </span>
                        </div>
                        <div class="text-sm">${creatorName}</div>
                        <div class="flex gap-1">
                            <button onclick="viewBatchDetails(${batch.id})" class="p-1 text-blue-600 hover:text-blue-800" title="View Details">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>`;
                    
                    if (batch.status === 'generated') {
                        html += `<button onclick="printBatchLabels(${batch.id})" class="p-1 text-green-600 hover:text-green-800" title="Print Labels">
                                    <i data-lucide="printer" class="h-4 w-4"></i>
                                </button>`;
                    }
                    
                    if (batch.status !== 'printed' && batch.status !== 'completed') {
                        html += `<button onclick="deleteBatch(${batch.id})" class="p-1 text-red-600 hover:text-red-800" title="Delete Batch">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>`;
                    }
                    
                    html += `</div>
                    </div>`;
                }
                
                batchListContent.innerHTML = html;
                lucide.createIcons();
            }

            function viewBatchDetails(batchId) {
                showLoading('Loading batch details...');
                
                fetch(API.batchDetails + batchId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayBatchDetailsModal(data.data);
                        } else {
                            showError(data.message);
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        showError('Failed to fetch batch details: ' + error.message);
                        hideLoading();
                    });
            }

            function displayBatchDetailsModal(batch) {
                const modalHTML = `
                    <div id="batchDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                            <div class="mt-3">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Batch Details: ${batch.batch_number}</h3>
                                    <button onclick="closeBatchDetailsModal()" class="text-gray-400 hover:text-gray-600">
                                        <i data-lucide="x" class="h-6 w-6"></i>
                                    </button>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Batch Number</p>
                                        <p class="text-sm text-gray-900">${batch.batch_number}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Status</p>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            ${batch.status.charAt(0).toUpperCase() + batch.status.slice(1)}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Label Format</p>
                                        <p class="text-sm text-gray-900">${batch.label_format}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Created</p>
                                        <p class="text-sm text-gray-900">${new Date(batch.created_at).toLocaleDateString()}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Files Count</p>
                                        <p class="text-sm text-gray-900">${batch.batch_items ? batch.batch_items.length : 0}/${batch.batch_size}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-500">Created By</p>
                                        <p class="text-sm text-gray-900">${batch.creator ? batch.creator.name : 'Unknown'}</p>
                                    </div>
                                </div>

                                ${batch.batch_items && batch.batch_items.length > 0 ? `
                                    <div class="mb-4">
                                        <h4 class="text-md font-medium text-gray-900 mb-2">Files in this Batch</h4>
                                        <div class="max-h-60 overflow-y-auto border rounded-md">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">File Number</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    ${batch.batch_items.map(item => `
                                                        <tr>
                                                            <td class="px-4 py-2 text-sm font-medium text-gray-900">${item.file_number}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-500">${item.file_title || 'No title'}</td>
                                                            <td class="px-4 py-2 text-sm text-gray-500">${item.shelf_location || 'N/A'}</td>
                                                            <td class="px-4 py-2 text-sm">
                                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${item.is_printed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                                                                    ${item.is_printed ? 'Printed' : 'Pending'}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    `).join('')}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                ` : ''}

                                <div class="flex justify-end space-x-3">
                                    ${batch.status === 'generated' ? `
                                        <button onclick="markBatchAsPrinted(${batch.id}); closeBatchDetailsModal();" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                            Mark as Printed
                                        </button>
                                    ` : ''}
                                    ${batch.status !== 'printed' && batch.status !== 'completed' ? `
                                        <button onclick="deleteBatch(${batch.id}); closeBatchDetailsModal();" class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700">
                                            Delete Batch
                                        </button>
                                    ` : ''}
                                    <button onclick="closeBatchDetailsModal()" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
                                        Close
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.body.insertAdjacentHTML('beforeend', modalHTML);
                lucide.createIcons();
            }

            function closeBatchDetailsModal() {
                const modal = document.getElementById('batchDetailsModal');
                if (modal) {
                    modal.remove();
                }
            }

            function printBatchLabels(batchId) {
                showLoading('Loading batch for printing...');
                
                fetch(API.batchForPrinting + batchId + '/print')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Load the batch data into the state for printing
                            const batch = data.data.batch;
                            const files = data.data.files;
                            
                            // Clear current selection and load batch files
                            state.selectedFiles = files.map(file => file.id);
                            state.availableFiles = files; // Set the files as available for preview
                            state.selectedTemplate = batch.label_format;
                            state.labelFormat = batch.label_format === 'qr_code' ? 'qrcode' : 'barcode';
                            const requestedOrientation = (batch.orientation || 'portrait').toLowerCase();
                            state.orientation = requestedOrientation === 'portrait' ? 'portrait' : 'portrait';
                            state.batchMode = false; // We're printing existing files, not generating new ones
                            
                            // Update UI elements
                            document.getElementById('labelTemplate').value = batch.label_format;
                            document.querySelector(`[data-format="${state.labelFormat}"]`).click();
                            const orientationOption = document.querySelector(`[data-orientation="${state.orientation}"]`);
                            if (orientationOption && orientationOption.dataset.disabled !== 'true') {
                                orientationOption.click();
                            } else {
                                const portraitOption = document.querySelector('[data-orientation="portrait"]');
                                if (portraitOption) {
                                    state.orientation = 'portrait';
                                    portraitOption.click();
                                }
                            }
                            
                            // Switch to preview tab to show the labels ready for printing
                            switchTab('preview');
                            
                            showSuccess(`Loaded batch ${batch.batch_number} with ${files.length} files for printing`);
                            
                            // Store the batch ID for when we actually print
                            state.currentBatchId = batchId;
                        } else {
                            showError(data.message);
                        }
                        hideLoading();
                    })
                    .catch(error => {
                        showError('Failed to load batch for printing: ' + error.message);
                        hideLoading();
                    });
            }

            // Make functions globally accessible
            window.viewBatchDetails = viewBatchDetails;
            window.printBatchLabels = printBatchLabels;
            window.markBatchAsPrinted = markBatchAsPrinted;
            window.deleteBatch = deleteBatch;
            window.closeBatchDetailsModal = closeBatchDetailsModal;

            // Utility functions
            function generateBatchFileNumber(index) {
                const fileNumber = (state.batchStartNumber + index)
                    .toString()
                    .padStart(4, "0");
                return fileNumber;
            }

            function updateCounts() {
                document.getElementById("selectedFilesCount").textContent = state.selectedFiles.length;
                document.getElementById(
                    "selectionStatus"
                ).textContent = `${state.selectedFiles.length} of ${state.availableFiles.length} selected`;
                
                // Update button states based on selection
                updateButtonStates();
            }

            function updateButtonStates() {
                const selectedCount = state.selectedFiles.length;
                
                // Dynamic limits based on mode - ST mode has no upper limit
                const maxFiles = state.stFilterActive ? 999999 : 30; // Effectively unlimited for ST mode
                const minFiles = 1;
                
                const isValidSelection = selectedCount >= minFiles && selectedCount <= maxFiles;
                const printBtn = document.getElementById("printBtn");
                const continueToSettingsBtn = document.getElementById("continueToSettingsBtn");
                
                // Update selection feedback with mode-specific messaging
                const selectionStatus = document.getElementById("selectionStatus");
                if (selectionStatus) {
                    if (state.stFilterActive) {
                        selectionStatus.textContent = `${selectedCount} files selected`;
                        selectionStatus.classList.remove("text-red-600"); // No limits for ST
                    } else {
                        selectionStatus.textContent = `${selectedCount} of max ${maxFiles} files selected`;
                        if (selectedCount > maxFiles) {
                            selectionStatus.classList.add("text-red-600");
                            selectionStatus.textContent += ` (exceeds limit!)`;
                        } else {
                            selectionStatus.classList.remove("text-red-600");
                        }
                    }
                }
                
                // Enable buttons based on valid selection
                if (printBtn) {
                    printBtn.disabled = !isValidSelection;
                    if (isValidSelection) {
                        printBtn.classList.remove("opacity-50", "cursor-not-allowed");
                        printBtn.classList.add("hover:bg-blue-700");
                    } else {
                        printBtn.classList.add("opacity-50", "cursor-not-allowed");
                        printBtn.classList.remove("hover:bg-blue-700");
                    }
                }
                
                if (continueToSettingsBtn) {
                    continueToSettingsBtn.disabled = !isValidSelection;
                    if (isValidSelection) {
                        continueToSettingsBtn.classList.remove("opacity-50", "cursor-not-allowed");
                        continueToSettingsBtn.classList.add("hover:bg-blue-700");
                    } else {
                        continueToSettingsBtn.classList.add("opacity-50", "cursor-not-allowed");
                        continueToSettingsBtn.classList.remove("hover:bg-blue-700");
                    }
                }
                
                // Update tab accessibility
                updateTabAccessibility();

                if (state.activeTab === 'preview') {
                    updatePreview();
                }
            }

            function filterFiles() {
                return state.availableFiles.filter(
                    (file) =>
                        !state.searchTerm ||
                        file.file_number.toLowerCase().includes(state.searchTerm.toLowerCase()) ||
                        (file.file_title && file.file_title.toLowerCase().includes(state.searchTerm.toLowerCase())) ||
                        (file.plot_number && file.plot_number.toLowerCase().includes(state.searchTerm.toLowerCase())) ||
                        (file.district && file.district.toLowerCase().includes(state.searchTerm.toLowerCase())) ||
                        (file.lga && file.lga.toLowerCase().includes(state.searchTerm.toLowerCase()))
                );
            }

            function updateTabAccessibility() {
                const selectedCount = state.selectedFiles.length;
                // ST mode has no upper limit, regular mode limited to 30
                const maxFiles = state.stFilterActive ? 999999 : 30;
                const hasValidSelection = selectedCount >= 1 && selectedCount <= maxFiles;
                
                // Get tab buttons
                const settingsTab = document.querySelector('[data-tab="settings"]');
                const previewTab = document.querySelector('[data-tab="preview"]');
                
                // Settings tab accessibility
                if (settingsTab) {
                    if (hasValidSelection) {
                        settingsTab.classList.remove('opacity-50', 'cursor-not-allowed');
                        settingsTab.style.pointerEvents = 'auto';
                        settingsTab.title = 'Configure label settings';
                    } else {
                        settingsTab.classList.add('opacity-50', 'cursor-not-allowed');
                        settingsTab.style.pointerEvents = 'none';
                        settingsTab.title = `Select ${state.stFilterActive ? 'at least 1 SUA file' : 'up to 30 files'} first`;
                    }
                }
                
                // Preview tab accessibility (requires settings to be configured)
                if (previewTab) {
                    const hasSettings = state.labelFormat && state.labelSize;
                    if (hasValidSelection && hasSettings) {
                        previewTab.classList.remove('opacity-50', 'cursor-not-allowed');
                        previewTab.style.pointerEvents = 'auto';
                        previewTab.title = 'Preview and print labels';
                    } else {
                        previewTab.classList.add('opacity-50', 'cursor-not-allowed');
                        previewTab.style.pointerEvents = 'none';
                        if (!hasValidSelection) {
                            previewTab.title = `Select ${state.stFilterActive ? 'at least 1 SUA file' : 'up to 30 files'} first`;
                        } else {
                            previewTab.title = 'Configure label settings first';
                        }
                    }
                }
            }

            function updateSelectAllCheckbox() {
                const selectAllCheckbox = document.getElementById("selectAll");
                const filteredFiles = filterFiles();
                selectAllCheckbox.checked =
                    state.selectedFiles.length === filteredFiles.length &&
                    filteredFiles.length > 0;
            }

            function switchTab(tabName) {
                // Update tab buttons
                document.querySelectorAll(".tab-btn").forEach((btn) => {
                    btn.classList.remove("active", "border-blue-500", "text-blue-600");
                    btn.classList.add("border-transparent", "text-gray-500");
                });
                document
                    .querySelector(`[data-tab="${tabName}"]`)
                    .classList.add("active", "border-blue-500", "text-blue-600");
                document
                    .querySelector(`[data-tab="${tabName}"]`)
                    .classList.remove("border-transparent", "text-gray-500");

                // Update tab content
                document.querySelectorAll(".tab-content").forEach((content) => {
                    content.classList.remove("active");
                });
                document.getElementById(`${tabName}-tab`).classList.add("active");

                state.activeTab = tabName;
                if (tabName === "preview") {
                    updatePreview();
                } else if (tabName === "generated") {
                    fetchGeneratedBatches();
                } else if (tabName === "settings") {
                    // Update settings tab with real data
                    updateSettingsPreview();
                }
            }

            function updateSettingsPreview() {
                // Update the settings tab to show real selected files data
                const selectedFilesData = state.selectedFiles.map(fileId => 
                    state.availableFiles.find(f => f.id === fileId)
                ).filter(file => file !== undefined);

                // Update any preview elements in the settings tab
                if (selectedFilesData.length > 0) {
                    console.log('Selected files for settings:', selectedFilesData);
                    // You can add specific UI updates here if needed
                }
            }

            function updatePreview() {
                const previewDescription = document.getElementById("previewDescription");
                const payload = buildLabelPayload();

                if (previewDescription) {
                    if (!payload.baseEntries.length) {
                        previewDescription.textContent = state.batchMode
                            ? "Enable batch generation to preview labels, or select specific files first."
                            : "No files selected yet. Choose files and configure settings to generate a preview.";
                    } else {
                        const labelWord = payload.summary.files === 1 ? "label" : "labels";
                        const copyWord = payload.summary.copies === 1 ? "copy" : "copies";
                        const pageWord = payload.summary.pages === 1 ? "page" : "pages";
                        previewDescription.textContent = `Previewing ${Math.min(payload.previewEntries.length, payload.summary.files)} ${labelWord} (${payload.summary.copies} ${copyWord} each). Printing will cover ${payload.summary.pages || 1} ${pageWord}.`;
                    }
                }

                renderPreview(payload);
                updatePrintSummaryCard(payload);

                return payload;
            }

            function openPrintWindowWithPayload(payload) {
                const payloadKey = `printlabel-${Date.now()}`;

                try {
                    localStorage.setItem(payloadKey, JSON.stringify(payload));
                } catch (error) {
                    console.warn('Unable to persist print payload in localStorage', error);
                }

                const printUrl = `${PRINT_TEMPLATE_URL}?payloadKey=${encodeURIComponent(payloadKey)}`;
                let printWindow = null;

                try {
                    printWindow = window.open('', '_blank', 'width=980,height=720,scrollbars=1');
                } catch (error) {
                    console.warn('Failed to open print window', error);
                }

                if (!printWindow) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Enable pop-ups',
                        text: 'We couldn’t open the print window. Please allow pop-ups for this site and click Print again.',
                        confirmButtonColor: '#3b82f6',
                    });
                    return null;
                }

                try {
                    printWindow.document.write('<!DOCTYPE html><html><head><title>Preparing labels…</title><style>body{font-family:Inter,Segoe UI,Arial,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f8fafc;color:#475569;}</style></head><body><div>Preparing label print view…</div></body></html>');
                    printWindow.document.close();
                } catch (error) {
                    console.warn('Unable to prime print window content', error);
                }

                try {
                    printWindow.focus();
                } catch (error) {
                    console.warn('Unable to focus print window', error);
                }

                const navigateToTemplate = () => {
                    try {
                        printWindow.location.replace(printUrl);
                    } catch (error) {
                        try {
                            printWindow.location.href = printUrl;
                        } catch (navigationError) {
                            console.warn('Unable to navigate print window to template', navigationError);
                        }
                    }
                };

                setTimeout(navigateToTemplate, 25);

                const message = {
                    type: 'print-labels',
                    payload,
                };

                const sendMessage = () => {
                    if (printWindow.closed) {
                        return;
                    }

                    try {
                        printWindow.postMessage(message, window.location.origin);
                    } catch (err) {
                        console.warn('Unable to postMessage to print window yet', err);
                    }
                };

                // Initial attempt after a short delay to allow the window to boot.
                setTimeout(sendMessage, 500);

                // A few retries to improve reliability on slower machines.
                let attempts = 0;
                const retryTimer = setInterval(() => {
                    attempts += 1;
                    if (attempts > 5 || printWindow.closed) {
                        clearInterval(retryTimer);
                        return;
                    }
                    sendMessage();
                }, 800);

                return printWindow;
            }

            function printLabels() {
                const payload = buildLabelPayload();

                if (!payload.baseEntries.length) {
                    showError('Please select at least one file (or enable batch mode) before printing.');
                    return;
                }

                payload.autoPrint = true;
                payload.notice = 'Generated via Print Labels interface';
                payload.meta.copies = payload.summary.copies;
                payload.meta.templateLabel = payload.summary.templateLabel;
                payload.meta.sizeLabel = payload.summary.sizeLabel;
                payload.meta.formatLabel = payload.summary.formatLabel;

                embedQrImages(payload);

                const printWindow = openPrintWindowWithPayload(payload);

                if (!printWindow) {
                    return;
                }

                showSuccess('Print window prepared. Use your browser’s print dialog to finish printing.');
            }

            function handlePrintWindowMessages(event) {
                if (event.origin !== window.location.origin) {
                    return;
                }

                if (!event.data || typeof event.data !== 'object') {
                    return;
                }

                if (event.data.type === 'print-labels:afterprint') {
                    if (state.currentBatchId) {
                        setTimeout(() => {
                            if (confirm('Have you successfully printed the labels? This will mark the batch as printed.')) {
                                markBatchAsPrinted(state.currentBatchId);
                            }
                            state.currentBatchId = null;
                        }, 250);
                    }
                }
            }

            window.addEventListener('message', handlePrintWindowMessages);

            // Initialize button states
            updateButtonStates();
            
            // Load initial data
            fetchAvailableFiles();
            fetchStatistics();

            // Tab switching
            document.querySelectorAll(".tab-btn").forEach((btn) => {
                btn.addEventListener("click", function () {
                    switchTab(this.dataset.tab);
                });
            });

            // History toggle
            if (document.getElementById("historyBtn")) {
                document
                    .getElementById("historyBtn")
                    .addEventListener("click", function () {
                        state.showHistory = !state.showHistory;
                        document.getElementById("printHistory").style.display =
                            state.showHistory ? "block" : "none";
                    });
            }

            if (document.getElementById("closeHistoryBtn")) {
                document
                    .getElementById("closeHistoryBtn")
                    .addEventListener("click", function () {
                        state.showHistory = false;
                        document.getElementById("printHistory").style.display = "none";
                    });
            }

            // Reset form
            document
                .getElementById("resetBtn")
                .addEventListener("click", function () {
                    state.selectedFiles = [];
                    state.labelSize = "30-in-1";
                    state.labelFormat = "qrcode";
                    state.copies = 1;
                    state.selectedTemplate = "30-in-1";
                    state.orientation = "portrait";
                    state.showAdvancedOptions = false;
                    state.batchMode = false;
                    state.batchStartNumber = 1;
                    state.batchCount = 30;

                    // Reset UI
                    document.getElementById("copies").value = 1;
                    document.getElementById("batchMode").checked = false;
                    document.getElementById("batchControls").style.display = "none";
                    document.getElementById("batchStart").value = 1;
                    document.getElementById("batchCount").value = 30;

                    const portraitOption = document.querySelector('[data-orientation="portrait"]');
                    const landscapeOption = document.querySelector('[data-orientation="landscape"]');
                    if (portraitOption) {
                        portraitOption.classList.add('selected');
                        const radio = portraitOption.querySelector('input[type="radio"]');
                        if (radio) {
                            radio.checked = true;
                        }
                    }
                    if (landscapeOption) {
                        landscapeOption.classList.remove('selected');
                        const radio = landscapeOption.querySelector('input[type="radio"]');
                        if (radio) {
                            radio.checked = false;
                        }
                    }

                    renderFileList();
                    updateCounts();
                });

            // Search functionality
            document
                .getElementById("searchInput")
                .addEventListener("input", function () {
                    state.searchTerm = this.value;
                    // Debounce the search
                    clearTimeout(this.searchTimeout);
                    this.searchTimeout = setTimeout(() => {
                        fetchAvailableFiles(state.searchTerm);
                    }, 500);
                });

            // Select all functionality
            document
                .getElementById("selectAll")
                .addEventListener("change", function () {
                    const filteredFiles = filterFiles();
                    if (this.checked) {
                        state.selectedFiles = [
                            ...new Set([
                                ...state.selectedFiles,
                                ...filteredFiles.map((f) => f.id),
                            ]),
                        ];
                    } else {
                        const filteredIds = filteredFiles.map((f) => f.id);
                        state.selectedFiles = state.selectedFiles.filter(
                            (id) => !filteredIds.includes(id)
                        );
                    }
                    renderFileList();
                    updateCounts();
                });

            // Batch mode toggle
            document
                .getElementById("batchMode")
                .addEventListener("change", function () {
                    state.batchMode = this.checked;
                    document.getElementById("batchControls").style.display = this
                        .checked
                        ? "flex"
                        : "none";
                });

            document
                .getElementById("batchStart")
                .addEventListener("change", function () {
                    state.batchStartNumber = parseInt(this.value);
                });

            document
                .getElementById("batchCount")
                .addEventListener("change", function () {
                    state.batchCount = parseInt(this.value);
                });

            document
                .getElementById("generateBatchBtn")
                .addEventListener("click", function () {
                    const startFileNumber = generateBatchFileNumber(0);
                    const endFileNumber = generateBatchFileNumber(state.batchCount - 1);
                    alert(
                        `Generated ${state.batchCount} batch labels from ${startFileNumber} to ${endFileNumber}`
                    );
                });

            // Label format selection
            document.querySelectorAll(".label-format-option").forEach((option) => {
                option.addEventListener("click", function () {
                    document
                        .querySelectorAll(".label-format-option")
                        .forEach((opt) => opt.classList.remove("selected"));
                    this.classList.add("selected");
                    state.labelFormat = this.dataset.format;
                    updateTabAccessibility(); // Update tab accessibility when format changes
                });
            });

            // Orientation selection
            document.querySelectorAll(".orientation-option").forEach((option) => {
                option.addEventListener("click", function () {
                    if (this.dataset.disabled === 'true') {
                        return;
                    }
                    document
                        .querySelectorAll(".orientation-option")
                        .forEach((opt) => {
                            if (opt.dataset.disabled === 'true') {
                                opt.classList.remove('selected');
                                const input = opt.querySelector('input[type="radio"]');
                                if (input) {
                                    input.checked = false;
                                }
                                return;
                            }
                            opt.classList.remove('selected');
                        });
                    this.classList.add("selected");
                    state.orientation = this.dataset.orientation;
                    const targetRadio = document.querySelector(
                        `input[value="${this.dataset.orientation}"]`
                    );
                    if (targetRadio) {
                        targetRadio.checked = true;
                    }
                });
            });

            // Advanced options toggle
            if (document.getElementById("advancedToggle")) {
                document
                    .getElementById("advancedToggle")
                    .addEventListener("click", function () {
                        state.showAdvancedOptions = !state.showAdvancedOptions;
                        document.getElementById("advancedOptions").style.display =
                            state.showAdvancedOptions ? "block" : "none";
                        this.textContent = state.showAdvancedOptions
                            ? "Hide Advanced"
                            : "Show Advanced";
                    });
            }

            // Copies input
            document
                .getElementById("copies")
                .addEventListener("change", function () {
                    state.copies = parseInt(this.value);
                });

            // Template and size selection
            document
                .getElementById("labelTemplate")
                .addEventListener("change", function () {
                    state.selectedTemplate = this.value;
                });

            document
                .getElementById("labelSize")
                .addEventListener("change", function () {
                    state.labelSize = this.value;
                    updateTabAccessibility(); // Update tab accessibility when size changes
                });

            // Navigation buttons
            document
                .getElementById("continueToSettingsBtn")
                .addEventListener("click", function () {
                    if (this.disabled) return;
                    
                    const selectedCount = state.selectedFiles.length;
                    const maxFiles = state.stFilterActive ? 999999 : 30; // No limit for ST mode
                    const minFiles = 1;
                    
                    if (selectedCount < minFiles) {
                        showError(`Please select at least ${minFiles} file${minFiles > 1 ? 's' : ''} to continue.`);
                        return;
                    }
                    
                    if (!state.stFilterActive && selectedCount > maxFiles) {
                        showError(`Too many files selected. Maximum ${maxFiles} files allowed. Currently selected: ${selectedCount}`);
                        return;
                    }
                    
                    switchTab("settings");
                });

            document
                .getElementById("backToFilesBtn")
                .addEventListener("click", function () {
                    switchTab("files");
                });

            document
                .getElementById("continueToPreviewBtn")
                .addEventListener("click", function () {
                    switchTab("preview");
                });

            document
                .getElementById("backToSettingsBtn")
                .addEventListener("click", function () {
                    switchTab("settings");
                });

            // Action buttons
            if (document.getElementById("duplicateBtn")) {
                document
                    .getElementById("duplicateBtn")
                    .addEventListener("click", function () {
                        state.copies = state.copies + 1;
                        document.getElementById("copies").value = state.copies;
                        alert(
                            `Duplicated selected labels. Now printing ${state.copies} copies of each.`
                        );
                    });
            }

            if (document.getElementById("exportPdfBtn")) {
                document
                    .getElementById("exportPdfBtn")
                    .addEventListener("click", function () {
                        alert("Exporting labels as PDF...");
                    });
            }

            if (document.getElementById("saveTemplateBtn")) {
                document
                    .getElementById("saveTemplateBtn")
                    .addEventListener("click", function () {
                        const templateName = prompt("Enter a name for this template:");
                        if (templateName) {
                            alert(`Template "${templateName}" saved successfully!`);
                        }
                    });
            }

            if (document.getElementById("importTemplateBtn")) {
                document
                    .getElementById("importTemplateBtn")
                    .addEventListener("click", function () {
                        alert(
                            "Import template functionality would open a file dialog here"
                        );
                    });
            }

            // Print buttons - now calls backend to create batch
            document
                .getElementById("printBtn")
                .addEventListener("click", function () {
                    if (this.disabled) return;
                    const payloadPreview = buildLabelPayload();

                    if (!payloadPreview.baseEntries.length) {
                        showError('Please select at least one file before printing.');
                        return;
                    }

                    switchTab('preview');

                    setTimeout(() => {
                        printLabels();
                    }, 120);
                });

            // Status filter for batches
            if (document.getElementById("statusFilter")) {
                document
                    .getElementById("statusFilter")
                    .addEventListener("change", function () {
                        fetchGeneratedBatches(this.value);
                    });
            }

            // Refresh batches button
            if (document.getElementById("refreshBatchesBtn")) {
                document
                    .getElementById("refreshBatchesBtn")
                    .addEventListener("click", function () {
                        fetchGeneratedBatches();
                        fetchStatistics();
                    });
            }

            // Final print button - now calls the printLabels function
            document
                .getElementById("finalPrintBtn")
                .addEventListener("click", printLabels);

            // Initialize the page
            fetchAvailableFiles();
            fetchStatistics();
            updateCounts();
            updateTabAccessibility(); // Initialize tab accessibility

        });
    </script>