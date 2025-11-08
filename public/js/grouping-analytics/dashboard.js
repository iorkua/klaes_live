window.groupingAnalyticsDashboard = function groupingAnalyticsDashboard() {
    return {
        isRefreshing: false,
        lastUpdated: null,
        previewDataTable: null,
        searchModal: null,
        searchForm: null,
        activeFilters: {
            batch: '',
            landuse: '',
            year: '',
            fileno: ''
        },

        init() {
            if (window.__GROUPING_FILTERS__) {
                this.activeFilters = Object.assign({
                    batch: '',
                    landuse: '',
                    year: '',
                    fileno: ''
                }, window.__GROUPING_FILTERS__);
            }

            if (typeof requestAnimationFrame === 'function') {
                requestAnimationFrame(() => this.refreshPreviewDataTable());
            } else {
                setTimeout(() => this.refreshPreviewDataTable(), 0);
            }
            this.setupAdvancedSearch();
            this.refreshStats();
        },

        async refreshStats() {
            if (this.isRefreshing) {
                return;
            }

            this.isRefreshing = true;

            try {
                const previewPromise = this.hasActiveFilters()
                    ? Promise.resolve(null)
                    : fetch('/api/grouping-analytics/preview?limit=100', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                const [totalsResponse, statsResponse, previewResponse] = await Promise.all([
                    fetch('/api/grouping/totals', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }),
                    fetch('/api/grouping-analytics/stats', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }),
                    previewPromise
                ]);

                const totalsPayload = totalsResponse.ok ? await totalsResponse.json() : null;
                if (!totalsResponse.ok || !totalsPayload?.success) {
                    throw new Error(totalsPayload?.message || `Totals request failed with status ${totalsResponse.status}`);
                }

                const statsPayload = statsResponse.ok ? await statsResponse.json() : null;
                if (!statsResponse.ok || !statsPayload?.success) {
                    console.warn('Grouping stats endpoint unavailable, falling back to totals data only.');
                }

                let previewPayload = null;
                if (previewResponse) {
                    previewPayload = previewResponse.ok ? await previewResponse.json() : null;
                    if (!previewResponse.ok || !previewPayload?.success) {
                        console.warn('Preview endpoint unavailable.', previewResponse.status);
                    }
                }

                const totalsData = totalsPayload.data || {};
                const overallStatsFromStats = statsPayload?.data?.overall || {};

                const totalFiles = Number.isFinite(totalsData.total_records)
                    ? Number(totalsData.total_records)
                    : Number(overallStatsFromStats.total_files ?? 0);

                const matchedFromStats = Number.isFinite(overallStatsFromStats.matched_files)
                    ? Number(overallStatsFromStats.matched_files)
                    : null;

                const matchedFromSummary = Number.isFinite(totalsData.summary?.matched_records)
                    ? Number(totalsData.summary.matched_records)
                    : null;

                const matchedFiles = matchedFromStats ?? matchedFromSummary;

                let unmatchedFiles = Number.isFinite(overallStatsFromStats.unmatched_files)
                    ? Number(overallStatsFromStats.unmatched_files)
                    : Number.isFinite(totalsData.summary?.unmatched_records)
                        ? Number(totalsData.summary.unmatched_records)
                        : null;

                if (Number.isFinite(totalFiles)) {
                    if (Number.isFinite(matchedFiles)) {
                        unmatchedFiles = Math.max(totalFiles - matchedFiles, 0);
                    } else {
                        unmatchedFiles = totalFiles;
                    }
                }

                let matchingPercentage = Number.isFinite(overallStatsFromStats.matching_percentage)
                    ? Number(overallStatsFromStats.matching_percentage)
                    : null;

                if (matchingPercentage === null && Number.isFinite(totalFiles) && Number.isFinite(matchedFiles) && totalFiles > 0) {
                    matchingPercentage = (matchedFiles / totalFiles) * 100;
                }

                const combinedOverall = {
                    total_files: Number.isFinite(totalFiles) ? totalFiles : 0,
                    matched_files: Number.isFinite(matchedFiles) ? matchedFiles : null,
                    unmatched_files: Number.isFinite(unmatchedFiles) ? unmatchedFiles : null,
                    matching_percentage: matchingPercentage,
                    today_matches: Number.isFinite(overallStatsFromStats.today_matches)
                        ? Number(overallStatsFromStats.today_matches)
                        : null,
                    last_match_time: overallStatsFromStats.last_match_time ?? null
                };

                this.updateKpiCards(combinedOverall);
                this.updateLandUseTable(totalsData.land_use_breakdown || []);
                this.updateActivityList(statsPayload?.data?.recent_activity || []);

                if (previewPayload?.success && !this.hasActiveFilters()) {
                    this.updatePreviewTable(previewPayload.data || []);
                    this.updatePreviewCount(previewPayload.count || 0);
                }

                this.lastUpdated = new Date().toLocaleTimeString();
            } catch (error) {
                console.error('Analytics refresh failed:', error);
                this.notify('Unable to refresh analytics at this time.', 'error');
            } finally {
                this.isRefreshing = false;
            }
        },

        setupAdvancedSearch() {
            this.searchModal = document.getElementById('grouping-search-modal');
            this.searchForm = document.getElementById('grouping-search-form');

            const closeNodes = document.querySelectorAll('[data-action="close-search"]');
            closeNodes.forEach((node) => {
                node.addEventListener('click', () => this.closeSearchModal());
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeSearchModal();
                }
            });
        },

        hasActiveFilters() {
            return Object.values(this.activeFilters || {}).some((value) => value !== null && value !== undefined && String(value).trim() !== '');
        },


        updateKpiCards(overall) {
            const isPlaceholder = overall && (overall._placeholder || overall._fallback);
            const mappings = {
                total: ['total_files', '[data-metric="total-files"]'],
                matched: ['matched_files', '[data-metric="matched-files"]'],
                unmatched: ['unmatched_files', '[data-metric="unmatched-files"]'],
                percentage: ['matching_percentage', '[data-metric="matching-percentage"]'],
                today: ['today_matches', '[data-metric="today-matches"]'],
                lastMatch: ['last_match_time', '[data-metric="last-match-time"]']
            };

            Object.values(mappings).forEach(([key, selector]) => {
                const node = document.querySelector(selector);
                if (!node) {
                    return;
                }

                if (isPlaceholder) {
                    node.textContent = key === 'last_match_time' ? '—' : 'Loading…';
                    node.classList.add('loading-skeleton');
                    return;
                }

                let value = overall[key];

                if (value === null || value === undefined) {
                    node.textContent = '—';
                    node.classList.remove('loading-skeleton');
                    return;
                }

                // Handle fallback/loading states
                if (typeof value === 'string' && (value.includes('Loading') || value === '—')) {
                    node.textContent = value;
                    node.classList.add('loading-skeleton');
                    return;
                }

                node.classList.remove('loading-skeleton');

                if (key === 'matching_percentage' && typeof value === 'number') {
                    value = `${value.toFixed(2)}%`;
                } else if (typeof value === 'number') {
                    value = new Intl.NumberFormat().format(value);
                } else if (key === 'last_match_time' && value) {
                    value = new Date(value).toLocaleString();
                }

                node.textContent = value;
            });
        },

        updateLandUseTable(rows) {
            const tbody = document.querySelector('[data-table="landuse"]');
            if (!tbody) {
                return;
            }

            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="empty">No land use data available.</td></tr>';
                return;
            }

            const formatter = new Intl.NumberFormat();
            const html = rows.map((row) => {
                const total = formatter.format(Number(row.count ?? row.total_files ?? 0));
                const share = Number(row.percentage ?? row.match_percentage ?? 0).toFixed(2);
                return `
                <tr>
                    <th scope="row">${row.landuse ?? 'Unknown'}</th>
                    <td class="numeric">${total}</td>
                    <td class="numeric">${share}%</td>
                </tr>`;
            }).join('');

            tbody.innerHTML = html;
        },

        updateActivityList(activities) {
            const list = document.querySelector('[data-list="recent-activity"]');
            if (!list) {
                return;
            }

            if (!Array.isArray(activities) || activities.length === 0) {
                list.innerHTML = '<li class="empty">No recent matches recorded.</li>';
                return;
            }

            const html = activities.map((activity) => {
                const awaiting = activity.awaiting_fileno ?? '—';
                const mls = activity.mls_fileno ?? 'N/A';
                const status = mls && mls !== 'N/A' ? 'matched' : 'pending';
                const group = activity.group_number ?? '—';
                const position = activity.position_in_group ?? '—';
                const matchedAt = activity.matched_at ? new Date(activity.matched_at).toLocaleString() : '—';

                return `
                    <li>
                        <div class="activity-meta">
                            <span class="file-number">${awaiting}</span>
                            <span class="match-status ${status}">${status === 'matched' ? 'Matched' : 'Pending'}</span>
                        </div>
                        <div class="activity-details">
                            <span>MLS: ${mls}</span>
                            <span>Group ${group} · Pos ${position}</span>
                            <span>${matchedAt}</span>
                        </div>
                    </li>
                `;
            }).join('');

            list.innerHTML = html;
        },

        updatePreviewTable(rows) {
            const tbody = document.querySelector('[data-table="preview"]');
            if (!tbody) {
                return;
            }

            if (!Array.isArray(rows) || rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="empty">No records available for preview.</td></tr>';
                this.refreshPreviewDataTable();
                return;
            }

            const html = rows.map((row) => {
                const isMatched = Number(row.mapping ?? 0) === 1;
                const createdDate = row.created_at ? new Date(row.created_at) : null;
                const createdAtLabel = createdDate ? createdDate.toLocaleString() : '—';
                const createdAtOrder = row.created_at ?? '';
                const groupValue = row.group_number ?? row.group ?? row.groupNumber ?? '—';
                const batchValue = row.batch_no ?? row.batch ?? '—';
                const mdcBatchValue = row.mdc_batch_no ?? row.mdc_batch ?? '—';
                const registryValue = row.registry ?? row.registry_label ?? '—';
                const shelfRackValue = row.shelf_rack ?? row.shelfRack ?? '—';
                const indexedByValue = row.indexed_by ?? row.indexedBy ?? '—';
                const landUseValue = row.landuse ?? row.land_use ?? '—';
                const yearValue = row.year ?? row.record_year ?? '—';

                return `
                    <tr>
                        <td data-heading="Awaiting File No" class="whitespace-nowrap">${row.awaiting_fileno ?? '—'}</td>
                        <td data-heading="MLS File No" class="whitespace-nowrap">${row.mls_fileno ?? '—'}</td>
                        <td data-heading="Mapping" class="whitespace-nowrap">
                            <span class="pill ${isMatched ? 'pill-success' : 'pill-warning'}">
                                ${isMatched ? 'Matched' : 'Pending'}
                            </span>
                        </td>
                        <td data-heading="Group" class="whitespace-nowrap">${groupValue}</td>
                        <td data-heading="Batch" class="whitespace-nowrap">${batchValue}</td>
                        <td data-heading="MDC Batch" class="whitespace-nowrap">${mdcBatchValue}</td>
                        <td data-heading="Registry" class="whitespace-nowrap">${registryValue}</td>
                        <td data-heading="Shelf Rack" class="whitespace-nowrap">${shelfRackValue}</td>
                        <td data-heading="Indexed By" class="whitespace-nowrap">${indexedByValue}</td>
                        <td data-heading="Land Use" class="whitespace-nowrap">${landUseValue}</td>
                        <td data-heading="Year" class="whitespace-nowrap">${yearValue}</td>
                        <td data-heading="Created" class="whitespace-nowrap" data-order="${createdAtOrder}">${createdAtLabel}</td>
                    </tr>
                `;
            }).join('');

            tbody.innerHTML = html;
            this.refreshPreviewDataTable();
        },

        refreshPreviewDataTable() {
            const jQ = window.jQuery;
            if (!jQ || !jQ.fn || !jQ.fn.DataTable) {
                return;
            }

            const $table = jQ('#grouping-preview-table');
            if ($table.length === 0) {
                return;
            }

            if (this.previewDataTable) {
                this.previewDataTable.destroy();
                this.previewDataTable = null;
            }

            this.previewDataTable = $table.DataTable({
                paging: true,
                pagingType: 'simple_numbers',
                pageLength: 50,
                lengthMenu: [
                    [50, 100, 250],
                    ['50', '100', '250']
                ],
                autoWidth: false,
                order: [[11, 'desc']],
                dom: '<"grouping-table-controls"f><"table-responsive-scroll"t><"grouping-table-footer"ip>',
                language: {
                    search: 'Quick filter:',
                    searchPlaceholder: 'Type to filter…',
                    lengthMenu: 'Show _MENU_ rows',
                    info: 'Showing _START_–_END_ of _TOTAL_ rows',
                    infoEmpty: 'No records to display',
                    zeroRecords: 'No matching records found'
                },
                columnDefs: [
                    { targets: [2], orderable: false, searchable: false }
                ]
            });
        },

        updatePreviewCount(count) {
            const node = document.querySelector('[data-counter="preview-count"]');
            if (node) {
                node.textContent = new Intl.NumberFormat().format(Number(count) || 0);
            }
        },

        openSearchModal() {
            if (!this.searchModal) {
                this.notify('Advanced search is not available in this environment.', 'info');
                return;
            }

            this.searchModal.classList.remove('hidden');
        },

        closeSearchModal() {
            if (this.searchModal) {
                this.searchModal.classList.add('hidden');
            }
        },

        triggerExport() {
            this.notify('Exporting snapshot… This may take a few seconds.', 'info');
            window.location.href = '/grouping-analytics/export/snapshot';
        },

        notify(message, type = 'info') {
            if (window.Swal) {
                window.Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2600,
                    icon: type,
                    title: message
                });
                return;
            }

            console[type === 'error' ? 'error' : 'log'](message);
        }
    };
};
