<script>
    // Quick Actions functionality
    const QuickActions = {
        
        // Initialize quick actions
        init: function() {
            this.bindEvents();
            console.log('Quick Actions initialized');
        },

        // Bind event listeners
        bindEvents: function() {
            // Search Files
            $('#quick-search-files').on('click', () => this.searchFiles());
            
            // Office List
            $('#quick-office-list').on('click', () => this.showOfficeList());
            
            // Track Status
            $('#quick-track-status').on('click', () => this.trackStatus());
            
            // Statistics
            $('#quick-statistics').on('click', () => this.showStatistics());
        },

        // Search Files functionality
        searchFiles: function() {
            this.showModal('search-files-modal', 'Search File Trackers', this.renderSearchFilesContent());
        },

        // Show office list
        showOfficeList: function() {
            this.showModal('office-list-modal', 'Office Directory', this.renderOfficeListContent());
        },

        // Track status functionality
        trackStatus: function() {
            this.showModal('track-status-modal', 'Track File Status', this.renderTrackStatusContent());
        },

        // Show statistics
        showStatistics: function() {
            this.loadStatistics().then(stats => {
                this.showModal('statistics-modal', 'File Tracking Statistics', this.renderStatisticsContent(stats));
            });
        },

        // Generic modal display function
        showModal: function(modalId, title, content) {
            // Remove existing modal if present
            $(`#${modalId}`).remove();
            
            const modal = $(`
                <div id="${modalId}" class="fixed inset-0 bg-black bg-opacity-60 z-[110] flex items-center justify-center p-4">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-hidden">
                        <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold flex items-center gap-2">
                                    <i data-lucide="layers" class="w-5 h-5"></i>
                                    ${title}
                                </h3>
                                <button class="modal-close p-2 hover:bg-white/20 rounded transition-colors">
                                    <i data-lucide="x" class="w-6 h-6"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                            ${content}
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(modal);
            
            // Bind close events
            modal.find('.modal-close').on('click', () => this.closeModal(modalId));
            modal.on('click', (e) => {
                if (e.target === modal[0]) this.closeModal(modalId);
            });
            
            // Initialize Lucide icons in modal
            lucide.createIcons();
            
            // Bind specific modal events after content is loaded
            this.bindModalEvents(modalId);
        },

        // Bind modal-specific events
        bindModalEvents: function(modalId) {
            const self = this;
            
            if (modalId === 'search-files-modal') {
                $('#perform-search').on('click', function() {
                    self.performFileSearch();
                });
                $('#clear-search').on('click', function() {
                    $('#search-query, #search-handler').val('');
                    $('#search-status, #search-priority, #search-office').val('');
                    $('#search-results').html(self.renderSearchEmptyState());
                    lucide.createIcons();
                });
                $('#search-query').on('keypress', function(e) {
                    if (e.which === 13) {
                        self.performFileSearch();
                    }
                });
            }
            
            if (modalId === 'track-status-modal') {
                $('#track-search').on('click', function() {
                    self.performTrackSearch();
                });
                $('#track-input').on('keypress', function(e) {
                    if (e.which === 13) {
                        self.performTrackSearch();
                    }
                });
            }
        },

        // Close modal
        closeModal: function(modalId) {
            $(`#${modalId}`).fadeOut(200, function() {
                $(this).remove();
            });
        },

        // Render search files content
        renderSearchFilesContent: function() {
            return `
                <div class="space-y-5">
                    <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <div class="flex flex-col gap-4 border-b border-gray-100 p-6 xl:flex-row xl:items-center">
                                        <div class="relative flex-1">
                                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"></i>
                                            <input type="text" id="search-query" placeholder="Enter tracking ID, file number, name, or handler..." class="w-full rounded-lg border border-gray-200 py-3 pl-10 pr-4 text-sm text-gray-700 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
                                        </div>
                                        <div class="flex shrink-0 gap-2">
                                            <button id="clear-search" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 transition hover:bg-gray-50">
                                                Clear Filters
                                            </button>
                                            <button id="perform-search" class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                                                <i data-lucide="search" class="mr-2 h-4 w-4"></i>
                                                Search
                                            </button>
                                        </div>
                                    </div>
                                    <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Status</label>
                                            <select id="search-status" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">All Statuses</option>
                                                <option value="active">Active</option>
                                                <option value="completed">Completed</option>
                                                <option value="archived">Archived</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Priority</label>
                                            <select id="search-priority" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">All Priorities</option>
                                                <option value="LOW">Low</option>
                                                <option value="MEDIUM">Medium</option>
                                                <option value="HIGH">High</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Office</label>
                                            <select id="search-office" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40">
                                                <option value="">All Offices</option>
                                                ${Object.entries(window.officeData || {}).map(([code, office]) => `
                                                    <option value="${code}">${office.name}</option>
                                                `).join('')}
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-xs font-semibold uppercase tracking-wide text-gray-500">Handled By</label>
                                            <input type="text" id="search-handler" placeholder="Enter handler name" class="mt-2 w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/40" />
                                        </div>
                                    </div>
                                </div>

                    <div id="search-results" class="rounded-xl border border-dashed border-gray-200 bg-white p-10 text-center text-sm text-gray-500">
                        ${this.renderSearchEmptyState()}
                    </div>
                </div>
            `;
        },

        renderSearchEmptyState: function() {
            return `
                <div class="flex flex-col items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-blue-50">
                        <i data-lucide="search" class="h-6 w-6 text-blue-400"></i>
                    </div>
                    <p class="text-base font-medium text-gray-700">Search Results</p>
                    <p class="text-sm text-gray-500">Enter your filters and click search to view matching file trackers.</p>
                </div>
            `;
        },

        // Perform file search
        performFileSearch: function() {
            const criteria = {
                query: $('#search-query').val(),
                status: $('#search-status').val(),
                priority: $('#search-priority').val(),
                office: $('#search-office').val(),
                handler: $('#search-handler').val()
            };

            $('#search-results').html(`
                <div class="space-y-4 py-16 text-center">
                    <div class="mx-auto inline-flex h-12 w-12 animate-spin items-center justify-center rounded-full border-2 border-blue-200 border-t-blue-600"></div>
                    <p class="text-sm font-medium text-gray-600">Searching file trackers...</p>
                </div>
            `);

            // Simulate search with existing fileTrackers data
            setTimeout(() => {
                const results = this.filterFileTrackers(criteria);
                this.displaySearchResults(results);
            }, 1000);
        },

        // Filter file trackers based on criteria
        filterFileTrackers: function(criteria) {
            // Check if fileTrackers is defined and is an array
            if (!window.fileTrackers || !Array.isArray(window.fileTrackers)) {
                console.warn('fileTrackers array not found or is not an array');
                return [];
            }

            return window.fileTrackers.filter(tracker => {
                let matches = true;
                const query = (criteria.query || '').toLowerCase();

                if (query) {
                    const haystack = [tracker.trackingId, tracker.fileNo, tracker.fileName, tracker.handler, tracker.currentOffice]
                        .map(value => (value || '').toString().toLowerCase())
                        .join(' ');

                    if (!haystack.includes(query)) {
                        matches = false;
                    }
                }

                if (matches && criteria.priority && tracker.priority && tracker.priority !== criteria.priority) {
                    matches = false;
                }

                if (matches && criteria.office && tracker.currentOfficeId && tracker.currentOfficeId !== criteria.office) {
                    matches = false;
                }

                if (matches && criteria.status) {
                    const active = tracker.logEntries?.some(entry => (entry.status || '').toLowerCase() === 'active');
                    const inferredStatus = active ? 'active' : 'completed';
                    if (criteria.status !== inferredStatus) {
                        matches = false;
                    }
                }

                return matches;
            });
        },

        // Display search results in table format
        displaySearchResults: function(results) {
            const self = this;

            if (results.length === 0) {
                $('#search-results').html(`
                    <div class="space-y-4 py-16 text-center text-gray-500">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-amber-50">
                            <i data-lucide="file-x" class="h-6 w-6 text-amber-500"></i>
                        </div>
                        <p class="text-base font-medium text-gray-700">No file trackers found</p>
                        <p class="text-sm">Try adjusting your filters or search keywords.</p>
                    </div>
                `);
                lucide.createIcons();
                return;
            }

            let tableHtml = `
                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-2 border-b border-gray-100 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Search Results</p>
                            <p class="text-base font-semibold text-gray-900">${results.length} file tracker${results.length === 1 ? '' : 's'} found</p>
                        </div>
                        <button id="export-search-results" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                            <i data-lucide="download" class="mr-2 h-4 w-4"></i>
                            Export Results
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-6 py-3">Tracking ID</th>
                                    <th class="px-6 py-3">File Details</th>
                                    <th class="px-6 py-3">Current Location</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white text-sm">
            `;

            results.forEach(tracker => {
                const officeCode = tracker.currentOfficeId || tracker.currentOffice || 'UNK';
                const office = (window.officeData && window.officeData[officeCode]) || { name: tracker.currentOffice || 'Unknown', code: officeCode };
                const active = tracker.logEntries?.some(entry => (entry.status || '').toLowerCase() === 'active');
                const statusLabel = active ? 'Active' : 'Completed';
                const statusClass = active
                    ? 'bg-emerald-100 text-emerald-700'
                    : 'bg-gray-200 text-gray-700';

                tableHtml += `
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="barcode" class="h-4 w-4 text-blue-500"></i>
                                <div class="text-sm font-semibold text-blue-700">${tracker.trackingId || '—'}</div>
                            </div>
                            <div class="mt-1 text-xs text-gray-500">${tracker.fileNo || '—'}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">${tracker.fileName || 'Unnamed File'}</div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-600">Priority: ${tracker.priority || 'MEDIUM'}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">${office.name}</div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-gray-500">
                                <i data-lucide="user" class="h-3.5 w-3.5 text-gray-400"></i>
                                <span>${tracker.handler || '—'}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ${statusClass}">${statusLabel}</span>
                                <span class="text-xs text-gray-500">${tracker.createdAt ? new Date(tracker.createdAt).toISOString().split('T')[0] : '—'}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="view-result-btn inline-flex items-center rounded-full bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700" data-tracking-id="${tracker.trackingId || ''}">
                                <i data-lucide="eye" class="mr-1.5 h-3.5 w-3.5"></i>
                                View
                            </button>
                        </td>
                    </tr>
                `;
            });

            tableHtml += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            $('#search-results').html(tableHtml);
            lucide.createIcons();

            $('#export-search-results').on('click', () => {
                this.exportSearchResults(results);
            });

            $('#search-results .view-result-btn').on('click', function() {
                const trackingId = $(this).data('tracking-id');
                if (trackingId && typeof window.showTrackerDetails === 'function') {
                    window.showTrackerDetails(trackingId);
                    self.closeModal('search-files-modal');
                }
            });
        },

        exportSearchResults: function(results) {
            if (!Array.isArray(results) || !results.length) {
                alert('No results to export');
                return;
            }

            const headers = ['Tracking ID', 'File Number', 'File Name', 'Priority', 'Current Office', 'Status', 'Created'];
            const rows = results.map(tracker => {
                const officeCode = tracker.currentOfficeId || tracker.currentOffice || '';
                const office = (window.officeData && window.officeData[officeCode]) || { name: tracker.currentOffice || '', code: officeCode };
                const active = tracker.logEntries?.some(entry => (entry.status || '').toLowerCase() === 'active');
                const statusLabel = active ? 'Active' : 'Completed';

                return [
                    tracker.trackingId || '',
                    tracker.fileNo || '',
                    tracker.fileName || '',
                    tracker.priority || '',
                    `${office.name} (${office.code})`,
                    statusLabel,
                    tracker.createdAt || ''
                ];
            });

            const csv = [headers, ...rows].map(row => row.map(value => {
                const cell = (value ?? '').toString().replace(/"/g, '""');
                return /[",\n]/.test(cell) ? `"${cell}"` : cell;
            }).join(',')).join('\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `search-file-trackers-${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        },

        // Render office list content
        renderOfficeListContent: function() {
            if (!window.officeData) {
                return '<div class="text-center py-8 text-gray-500"><p>Office data not available</p></div>';
            }

            let content = `
                <div class="space-y-4">
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900">Office Directory (${Object.keys(window.officeData).length} offices)</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Office Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Files</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
            `;
            
            Object.entries(window.officeData).forEach(([id, office]) => {
                const activeFiles = this.getActiveFilesForOffice(id);
                content += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <i data-lucide="building" class="w-4 h-4 text-blue-600"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">${office.name}</div>
                                    <div class="text-sm text-gray-500">Office ID: ${id}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                ${office.code}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${office.department}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                ${activeFiles} files
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <button onclick="QuickActions.viewOfficeFiles('${id}')" class="text-blue-600 hover:text-blue-900 mr-3">View Files</button>
                            <button onclick="QuickActions.contactOffice('${id}')" class="text-green-600 hover:text-green-900">Contact</button>
                        </td>
                    </tr>
                `;
            });
            
            content += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;
            return content;
        },

        // Get active files count for an office
        getActiveFilesForOffice: function(officeId) {
            if (!window.fileTrackers || !Array.isArray(window.fileTrackers)) {
                return 0;
            }
            return window.fileTrackers.filter(tracker => tracker.currentOffice === officeId).length;
        },

        // View files for specific office
        viewOfficeFiles: function(officeId) {
            if (!window.officeData || !window.officeData[officeId]) {
                console.warn('Office data not found for office:', officeId);
                return;
            }
            
            const office = window.officeData[officeId];
            const files = window.fileTrackers ? window.fileTrackers.filter(tracker => tracker.currentOffice === officeId) : [];
            
            this.showModal('office-files-modal', `Files in ${office.name}`, this.renderOfficeFilesContent(files, office));
        },

        // Render office files content
        renderOfficeFilesContent: function(files, office) {
            if (files.length === 0) {
                return `
                    <div class="text-center py-8 text-gray-500">
                        <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                        <p>No files currently in ${office.name}</p>
                    </div>
                `;
            }

            let content = `
                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                        <h4 class="text-sm font-medium text-gray-900">Files in ${office.name} (${files.length} files)</h4>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File No</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Here</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
            `;

            files.forEach(file => {
                const daysInOffice = this.calculateDaysInOffice(file);
                const priorityClass = `priority-${file.priority}`;
                
                content += `
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                ${file.fileNumber}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm font-medium text-gray-900">${file.fileName}</div>
                            <div class="text-sm text-gray-500">ID: ${file.trackingId}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${priorityClass}">
                                ${file.priority}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">${daysInOffice} days</td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">
                            <button onclick="viewDetails('${file.trackingId}')" class="text-blue-600 hover:text-blue-900">View</button>
                        </td>
                    </tr>
                `;
            });

            content += `
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            return content;
        },

        // Calculate days in current office
        calculateDaysInOffice: function(file) {
            // Simplified calculation - in real app would use actual timestamps
            return Math.floor(Math.random() * 30) + 1;
        },

        // Contact office function
        contactOffice: function(officeId) {
            if (!window.officeData || !window.officeData[officeId]) {
                alert('Office information not available');
                return;
            }
            
            const office = window.officeData[officeId];
            alert(`Contact information for ${office.name} would be displayed here.`);
        },

        // Render track status content
        renderTrackStatusContent: function() {
            return `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Enter File Number or Tracking ID</label>
                        <div class="flex gap-2">
                            <input type="text" id="track-input" placeholder="e.g., RES-2015-4859 or TRK-..." class="form-input flex-1">
                            <button id="track-search" class="btn-primary">
                                <i data-lucide="search" class="w-4 h-4 mr-2"></i>
                                Track
                            </button>
                        </div>
                    </div>
                    <div id="tracking-results" class="mt-4">
                        <div class="text-center py-8 text-gray-500">
                            <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Enter a file number or tracking ID to track its current status and movement history</p>
                        </div>
                    </div>
                </div>
            `;
        },

        // Perform track search
        performTrackSearch: function() {
            const searchTerm = $('#track-input').val().trim();
            if (!searchTerm) {
                alert('Please enter a file number or tracking ID');
                return;
            }

            $('#tracking-results').html(`
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600">Tracking...</p>
                </div>
            `);

            // Simulate API call
            setTimeout(() => {
                const tracker = this.findTracker(searchTerm);
                this.displayTrackingResults(tracker, searchTerm);
            }, 1000);
        },

        // Find tracker by file number or tracking ID
        findTracker: function(searchTerm) {
            if (!window.fileTrackers || !Array.isArray(window.fileTrackers)) {
                console.warn('fileTrackers array not found');
                return null;
            }
            
            return window.fileTrackers.find(tracker => 
                (tracker.fileNumber && tracker.fileNumber.toLowerCase() === searchTerm.toLowerCase()) ||
                (tracker.trackingId && tracker.trackingId.toLowerCase() === searchTerm.toLowerCase())
            );
        },

        // Display tracking results
        displayTrackingResults: function(tracker, searchTerm) {
            if (!tracker) {
                $('#tracking-results').html(`
                    <div class="text-center py-8 text-gray-500">
                        <i data-lucide="file-x" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                        <p>No file tracker found for "${searchTerm}"</p>
                        <p class="text-sm mt-2">Please check the file number or tracking ID and try again</p>
                    </div>
                `);
                lucide.createIcons();
                return;
            }

            const currentOffice = (window.officeData && window.officeData[tracker.currentOffice]) || { name: 'Unknown', code: 'UNK' };
            const movementHistory = this.generateMovementHistory(tracker);
            
            const content = `
                <div class="space-y-6">
                    <!-- Current Status Card -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-blue-900">Current Status</h3>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                Active
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-blue-600 font-medium">File Number</p>
                                <p class="text-blue-900 font-semibold">${tracker.fileNumber}</p>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600 font-medium">File Name</p>
                                <p class="text-blue-900 font-semibold">${tracker.fileName}</p>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600 font-medium">Priority</p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium priority-${tracker.priority}">
                                    ${tracker.priority}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-blue-200">
                            <div class="flex items-center gap-2">
                                <i data-lucide="map-pin" class="w-5 h-5 text-blue-600"></i>
                                <span class="text-blue-900 font-medium">Currently at: ${currentOffice.name} (${currentOffice.code})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Movement History -->
                    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                <i data-lucide="clock" class="w-4 h-4"></i>
                                Movement History
                            </h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">From Office</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">To Office</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
            `;

            movementHistory.forEach((movement, index) => {
                const isLatest = index === 0;
                const fromOffice = window.officeData[movement.fromOffice] || { name: 'Unknown', code: 'UNK' };
                const toOffice = window.officeData[movement.toOffice] || { name: 'Unknown', code: 'UNK' };
                
                content += `
                    <tr class="${isLatest ? 'bg-blue-50' : 'hover:bg-gray-50'}">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                            <div>${movement.date}</div>
                            <div class="text-xs text-gray-500">${movement.time}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${fromOffice.name}</div>
                            <div class="text-xs text-gray-500">${fromOffice.code}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">${toOffice.name}</div>
                            <div class="text-xs text-gray-500">${toOffice.code}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${isLatest ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                                ${movement.action}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">${movement.notes}</td>
                    </tr>
                `;
            });

            content += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            `;

            $('#tracking-results').html(content);
        },

        // Generate movement history for a tracker
        generateMovementHistory: function(tracker) {
            const movements = [
                {
                    date: '2024-12-12',
                    time: '14:30',
                    fromOffice: 'OFF-001',
                    toOffice: tracker.currentOffice,
                    action: 'Transferred',
                    notes: 'For processing and review'
                },
                {
                    date: '2024-12-11',
                    time: '09:15',
                    fromOffice: 'OFF-012',
                    toOffice: 'OFF-001',
                    action: 'Received',
                    notes: 'Initial registration'
                },
                {
                    date: '2024-12-10',
                    time: '16:45',
                    fromOffice: 'OFF-009',
                    toOffice: 'OFF-012',
                    action: 'Created',
                    notes: 'File tracker created'
                }
            ];

            return movements;
        },

        // Render statistics content
        renderStatisticsContent: function(stats) {
            return `
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-center gap-3">
                            <i data-lucide="file-text" class="w-8 h-8 text-blue-600"></i>
                            <div>
                                <p class="text-2xl font-bold text-blue-900">${stats.total || 0}</p>
                                <p class="text-sm text-blue-600">Total Trackers</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="flex items-center gap-3">
                            <i data-lucide="check-circle" class="w-8 h-8 text-green-600"></i>
                            <div>
                                <p class="text-2xl font-bold text-green-900">${stats.active || 0}</p>
                                <p class="text-sm text-green-600">Active</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <div class="flex items-center gap-3">
                            <i data-lucide="clock" class="w-8 h-8 text-orange-600"></i>
                            <div>
                                <p class="text-2xl font-bold text-orange-900">${stats.pending || 0}</p>
                                <p class="text-sm text-orange-600">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                            <div>
                                <p class="text-2xl font-bold text-red-900">${stats.overdue || 0}</p>
                                <p class="text-sm text-red-600">Overdue</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-3">By Priority</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-sm">High</span>
                                <span class="text-sm font-medium">${stats.priority?.High || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">Medium</span>
                                <span class="text-sm font-medium">${stats.priority?.Medium || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm">Low</span>
                                <span class="text-sm font-medium">${stats.priority?.Low || 0}</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-medium text-gray-900 mb-3">Recent Activity</h4>
                        <div class="space-y-2">
                            <div class="text-sm text-gray-600">Last 24 hours: ${stats.recent?.day || 0} trackers</div>
                            <div class="text-sm text-gray-600">This week: ${stats.recent?.week || 0} trackers</div>
                            <div class="text-sm text-gray-600">This month: ${stats.recent?.month || 0} trackers</div>
                        </div>
                    </div>
                </div>
            `;
        },

        // Render bulk operations content
        renderBulkOperationsContent: function() {
            return `
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <button class="bulk-action-card" data-action="bulk-move">
                            <i data-lucide="move" class="w-8 h-8 text-blue-600 mb-2"></i>
                            <h4 class="font-medium">Bulk Move</h4>
                            <p class="text-sm text-gray-600">Move multiple files to same office</p>
                        </button>
                        <button class="bulk-action-card" data-action="bulk-priority">
                            <i data-lucide="flag" class="w-8 h-8 text-orange-600 mb-2"></i>
                            <h4 class="font-medium">Update Priority</h4>
                            <p class="text-sm text-gray-600">Change priority for multiple files</p>
                        </button>
                        <button class="bulk-action-card" data-action="bulk-archive">
                            <i data-lucide="archive" class="w-8 h-8 text-green-600 mb-2"></i>
                            <h4 class="font-medium">Bulk Archive</h4>
                            <p class="text-sm text-gray-600">Archive completed trackers</p>
                        </button>
                        <button class="bulk-action-card" data-action="bulk-export">
                            <i data-lucide="download" class="w-8 h-8 text-purple-600 mb-2"></i>
                            <h4 class="font-medium">Batch Export</h4>
                            <p class="text-sm text-gray-600">Export selected trackers</p>
                        </button>
                    </div>
                </div>
                <style>
                .bulk-action-card {
                    @apply p-4 border border-gray-200 rounded-lg text-center hover:bg-gray-50 transition-colors;
                }
                </style>
            `;
        },

        // Render export content
        renderExportContent: function() {
            return `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Export Format</label>
                        <select id="export-format" class="form-input">
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                            <option value="pdf">PDF Report</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" id="export-from" class="form-input">
                            <input type="date" id="export-to" class="form-input">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Include</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" checked class="mr-2"> File Details
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" checked class="mr-2"> Movement History
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2"> Office Information
                            </label>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button id="export-cancel" class="btn-secondary">Cancel</button>
                        <button id="export-download" class="btn-primary">Download</button>
                    </div>
                </div>
            `;
        },

        // Render print labels content
        renderPrintLabelsContent: function() {
            return `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Label Type</label>
                        <select id="label-type" class="form-input">
                            <option value="qr">QR Code Labels</option>
                            <option value="barcode">Barcode Labels</option>
                            <option value="simple">Simple Text Labels</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Label Size</label>
                        <select id="label-size" class="form-input">
                            <option value="small">Small (2" x 1")</option>
                            <option value="medium">Medium (3" x 2")</option>
                            <option value="large">Large (4" x 3")</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Files</label>
                        <select id="print-files" multiple class="form-input h-32">
                            <!-- Options will be populated dynamically -->
                        </select>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button id="print-cancel" class="btn-secondary">Cancel</button>
                        <button id="print-generate" class="btn-primary">Generate Labels</button>
                    </div>
                </div>
            `;
        },

        // Render settings content
        renderSettingsContent: function() {
            return `
                <div class="space-y-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">General Settings</h4>
                        <div class="space-y-3">
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2"> Auto-refresh tracker list
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2"> Show notifications
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" class="mr-2"> Enable sound alerts
                            </label>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 mb-3">Default Values</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Priority</label>
                                <select class="form-input">
                                    <option value="Medium">Medium</option>
                                    <option value="High">High</option>
                                    <option value="Low">Low</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Default Office</label>
                                <select class="form-input">
                                    <option value="">Select office...</option>
                                    ${Object.entries(window.officeData || {}).map(([id, office]) => 
                                        `<option value="${id}">${office.name}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button id="settings-cancel" class="btn-secondary">Cancel</button>
                        <button id="settings-save" class="btn-primary">Save Settings</button>
                    </div>
                </div>
            `;
        },

        // Load statistics from API
        loadStatistics: function() {
            return $.ajax({
                url: '/api/file-trackers/dashboard',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            }).then(response => {
                if (response.success) {
                    return response.data;
                }
                return {};
            }).catch(() => {
                return {
                    total: 0,
                    active: 0,
                    pending: 0,
                    overdue: 0,
                    priority: { High: 0, Medium: 0, Low: 0 },
                    recent: { day: 0, week: 0, month: 0 }
                };
            });
        }
    };

    // Custom styles for forms
    $('head').append(`
        <style>
        .form-input {
            @apply block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
        }
        .btn-primary {
            @apply inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        .btn-secondary {
            @apply inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
        }
        </style>
    `);
</script>