/**
 * File Indexing - Indexed Files Module
 * Handles rendering, selection, and management of indexed files DataTable
 */

import { state } from './state.js';
import * as domUtils from './dom-utils.js';
import * as api from './api-utils.js';

// ============================================
// RENDERING FUNCTIONS
// ============================================

/**
 * Render indexed files using DataTable
 */
export async function renderIndexedFiles() {
  try {
    const data = await api.loadIndexedFiles(state.currentIndexedPage);
    const files = data?.indexed_files || [];
    state.indexedFiles = files;

    if (!files.length) {
      domUtils.showEmptyState('indexed-table-container', 'indexed-empty-state');
      updateIndexedPagination(data?.pagination);
      updateIndexedFilesCount();
      return;
    }

    domUtils.hideEmptyState('indexed-table-container', 'indexed-empty-state');

    // Initialize DataTable if not already done
    initializeIndexedFilesDataTable(files);

    // Update pagination
    updateIndexedPagination(data?.pagination);

    // Update counter
    updateIndexedFilesCount();
  } catch (err) {
    console.error('Error rendering indexed files:', err);
    api.showApiErrorNotification('Failed to load indexed files');
  }
}

/**
 * Initialize or update DataTable for indexed files
 */
export function initializeIndexedFilesDataTable(files) {
  const table = document.getElementById('indexed-files-table');
  if (!table) {
    console.error('Indexed files table not found');
    return;
  }

  const tbody = table.querySelector('tbody');
  if (!tbody) {
    console.error('Table tbody not found');
    return;
  }

  // Clear existing rows
  tbody.innerHTML = '';

  // Add file rows
  files.forEach((file) => {
    const row = createIndexedFileRow(file);
    tbody.appendChild(row);
  });

  // Bind row interactions
  bindIndexedTableInteractions();

  // Reinitialize icons
  domUtils.reinitializeIcons();
}

/**
 * Create a table row for an indexed file
 */
function createIndexedFileRow(file) {
  const tr = document.createElement('tr');
  tr.className = 'hover:bg-gray-50 transition-colors';

  const rawFileId = typeof file.id !== 'undefined' && file.id !== null ? String(file.id) : '';
  tr.dataset.fileId = rawFileId;

  const trackingId = file.tracking_id ?? file.trackingId ?? '-';
  const shelfLocation = file.shelf_location ?? '-';
  const registry = file.registry ?? '-';
  const sysBatchNo = file.sys_batch_no ?? '-';
  const mdcBatchNo = file.mdc_batch_no ?? file.batch_no ?? '-';
  const groupNo = file.group_no ?? file.group ?? '-';
  const fileNumber = file.fileNumber || file.file_number || '-';
  const fileName = file.name || file.file_title || '-';
  const plotNumber = file.plotNumber || file.plot_number || '-';
  const indexedDate = file.indexed_at || file.date || file.created_at;
  const indexedBy = file.indexed_by || '-';
  const tpNumber = file.tpNumber || file.tp_no || '-';
  const lpknNumber = file.lpknNumber || file.lpkn_no || '-';
  const landUse = file.landUseType || '-';
  const district = file.district || '-';
  const lga = file.lga || '-';
  const editUrl = rawFileId ? `/fileindexing/${encodeURIComponent(rawFileId)}/edit` : '#';

  tr.innerHTML = `
    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">${escapeHtml(trackingId)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(shelfLocation)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(registry)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(sysBatchNo)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(mdcBatchNo)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(groupNo)}</td>
    <td class="px-4 py-3 whitespace-nowrap font-medium text-gray-900">${escapeHtml(fileNumber)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(fileName)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(plotNumber)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${formatDate(indexedDate)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(indexedBy)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(tpNumber)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(lpknNumber)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(landUse)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(district)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-gray-700">${escapeHtml(lga)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-center">${buildStatusBadge(file)}</td>
    <td class="px-4 py-3 whitespace-nowrap text-center relative" style="overflow: visible;">
      <div class="indexed-actions-wrapper inline-flex items-center justify-center">
        <button
          type="button"
          class="indexed-actions-trigger inline-flex items-center justify-center rounded-md border border-gray-200 bg-white p-2 text-gray-600 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          data-file-id="${rawFileId}"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <i data-lucide="more-horizontal" class="h-4 w-4"></i>
        </button>
        <div class="indexed-actions-menu hidden absolute right-0 top-full z-40 mt-2 w-44 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
          <button type="button" class="indexed-action-view flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-file-id="${rawFileId}">
            <i data-lucide="eye" class="h-4 w-4"></i>
            View Details
          </button>
          <a href="${editUrl}" class="indexed-action-edit flex w-full items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
            <i data-lucide="edit-3" class="h-4 w-4"></i>
            Edit
          </a>
          <button type="button" class="indexed-action-delete flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50" data-file-id="${rawFileId}">
            <i data-lucide="trash-2" class="h-4 w-4"></i>
            Delete
          </button>
        </div>
      </div>
    </td>
  `;

  return tr;
}

/**
 * Build status badge HTML
 */
function buildStatusBadge(file) {
  let statusLabel = file.status || file.type || file.source || 'Indexed';
  const normalizedStatus = String(statusLabel).trim().toLowerCase();

  if (normalizedStatus === 'property document') {
    statusLabel = 'Indexed';
  }

  const normalized = statusLabel.toLowerCase();
  const statusMap = {
    indexed: 'bg-green-100 text-green-800',
    'indexed & typed': 'bg-indigo-100 text-indigo-800',
    'indexed & scanned': 'bg-blue-100 text-blue-800',
    processing: 'bg-yellow-100 text-yellow-800',
    pending: 'bg-gray-100 text-gray-800',
    application: 'bg-gray-100 text-gray-800',
    error: 'bg-red-100 text-red-800',
  };

  const className = statusMap[normalized] || statusMap.indexed;
  return `
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${className}">
      ${escapeHtml(statusLabel)}
    </span>
  `;
}

/**
 * Update indexed files count
 */
export async function updateIndexedFilesCount() {
  try {
    const stats = await api.loadStatistics();
    if (stats) {
      domUtils.updateElementText('indexed-files-count', stats.total_indexed || 0);
    }
  } catch (err) {
    console.warn('Could not update indexed files count:', err);
  }
}

// ============================================
// TABLE INTERACTIONS
// ============================================

/**
 * Bind event listeners to indexed table elements
 */
export function bindIndexedTableInteractions() {
  const table = document.getElementById('indexed-files-table');
  if (!table) {
    return;
  }

  closeAllActionMenus();

  table.querySelectorAll('.indexed-actions-trigger').forEach((trigger) => {
    trigger.setAttribute('aria-expanded', 'false');
  });

  if (state.indexedTableEventsBound) {
    return;
  }

  const tbody = table.querySelector('tbody');
  if (!tbody) {
    return;
  }

  tbody.addEventListener('click', handleIndexedTableClick);
  document.addEventListener('click', handleIndexedTableDocumentClick);
  state.indexedTableEventsBound = true;
}

async function handleIndexedTableClick(event) {
  const trigger = event.target.closest('.indexed-actions-trigger');
  if (trigger) {
    event.preventDefault();
    const wrapper = trigger.closest('.indexed-actions-wrapper');
    if (!wrapper) return;
    const menu = wrapper.querySelector('.indexed-actions-menu');
    if (!menu) return;

    const isMenuVisible = !menu.classList.contains('hidden');
    closeAllActionMenus(wrapper);

    if (isMenuVisible) {
      menu.classList.add('hidden');
      trigger.setAttribute('aria-expanded', 'false');
    } else {
      menu.classList.remove('hidden');
      trigger.setAttribute('aria-expanded', 'true');
    }

    return;
  }

  const viewBtn = event.target.closest('.indexed-action-view');
  if (viewBtn) {
    event.preventDefault();
    const fileId = parseInt(viewBtn.dataset.fileId, 10);
    closeAllActionMenus();
    if (!Number.isNaN(fileId)) {
      viewIndexedFile(fileId);
    }
    return;
  }

  const deleteBtn = event.target.closest('.indexed-action-delete');
  if (deleteBtn) {
    event.preventDefault();
    const fileId = parseInt(deleteBtn.dataset.fileId, 10);
    closeAllActionMenus();
    if (!Number.isNaN(fileId) && confirm('Are you sure you want to delete this file?')) {
      await deleteIndexedFile(fileId);
    }
    return;
  }

  const editLink = event.target.closest('.indexed-action-edit');
  if (editLink) {
    closeAllActionMenus();
  }
}

function handleIndexedTableDocumentClick(event) {
  if (!event.target.closest('.indexed-actions-wrapper')) {
    closeAllActionMenus();
  }
}

function closeAllActionMenus(exceptWrapper = null) {
  document.querySelectorAll('.indexed-actions-wrapper').forEach((wrapper) => {
    if (exceptWrapper && wrapper === exceptWrapper) {
      return;
    }

    const menu = wrapper.querySelector('.indexed-actions-menu');
    const trigger = wrapper.querySelector('.indexed-actions-trigger');

    if (menu && !menu.classList.contains('hidden')) {
      menu.classList.add('hidden');
    }

    if (trigger) {
      trigger.setAttribute('aria-expanded', 'false');
    }
  });
}

// ============================================
// SELECTION MANAGEMENT
// ============================================

/**
 * Toggle indexed file selection
 */
export function toggleIndexedFileSelection(fileId, isSelected) {
  if (isSelected) {
    if (!state.selectedIndexedFiles.includes(fileId)) {
      state.selectedIndexedFiles.push(fileId);
    }
  } else {
    state.selectedIndexedFiles = state.selectedIndexedFiles.filter((id) => id !== fileId);
  }

  updateSelectedIndexedFilesCount();
  updateSelectAllIndexedCheckbox();
}

/**
 * Toggle select all indexed files
 */
export function toggleSelectAllIndexed(isSelected) {
  const checkboxes = document.querySelectorAll('.indexed-file-checkbox');

  checkboxes.forEach((checkbox) => {
    checkbox.checked = isSelected;
    const fileId = parseInt(checkbox.dataset.fileId);
    toggleIndexedFileSelection(fileId, isSelected);
  });

  updateSelectedIndexedFilesCount();
  updateSelectAllIndexedCheckbox();
}

/**
 * Update select all checkbox state
 */
function updateSelectAllIndexedCheckbox() {
  const selectAllCheckbox = document.getElementById('select-all-indexed-checkbox');
  if (!selectAllCheckbox) return;

  const checkboxes = document.querySelectorAll('.indexed-file-checkbox');
  const allChecked = checkboxes.length > 0 && Array.from(checkboxes).every((cb) => cb.checked);
  const someChecked = Array.from(checkboxes).some((cb) => cb.checked);

  selectAllCheckbox.checked = allChecked;
  selectAllCheckbox.indeterminate = someChecked && !allChecked;
}

/**
 * Update selected indexed files count display
 */
function updateSelectedIndexedFilesCount() {
  domUtils.updateElementText('selected-indexed-files-count', state.selectedIndexedFiles.length);
}

/**
 * Clear all indexed file selections
 */
export function clearAllIndexedSelections() {
  state.selectedIndexedFiles = [];
  document.querySelectorAll('.indexed-file-checkbox').forEach((checkbox) => {
    checkbox.checked = false;
  });
  updateSelectedIndexedFilesCount();
  updateSelectAllIndexedCheckbox();
}

// ============================================
// QUICK SELECT FUNCTIONS
// ============================================

/**
 * Select first 100 indexed files
 */
export async function selectFirst100Files() {
  try {
    const data = await api.loadIndexedFiles(1, 100);
    if (data && data.indexed_files) {
      state.selectedIndexedFiles = data.indexed_files.map((f) => f.id);

      // Update UI
      document.querySelectorAll('.indexed-file-checkbox').forEach((checkbox) => {
        checkbox.checked = state.selectedIndexedFiles.includes(parseInt(checkbox.dataset.fileId));
      });

      updateSelectedIndexedFilesCount();
      updateSelectAllIndexedCheckbox();
      api.showApiSuccessNotification('Selected 100 files');
    }
  } catch (err) {
    console.error('Error selecting files:', err);
    api.showApiErrorNotification('Failed to select files');
  }
}

/**
 * Select first 200 indexed files
 */
export async function selectFirst200Files() {
  try {
    const data = await api.loadIndexedFiles(1, 200);
    if (data && data.indexed_files) {
      state.selectedIndexedFiles = data.indexed_files.map((f) => f.id);

      // Update UI
      document.querySelectorAll('.indexed-file-checkbox').forEach((checkbox) => {
        checkbox.checked = state.selectedIndexedFiles.includes(parseInt(checkbox.dataset.fileId));
      });

      updateSelectedIndexedFilesCount();
      updateSelectAllIndexedCheckbox();
      api.showApiSuccessNotification('Selected 200 files');
    }
  } catch (err) {
    console.error('Error selecting files:', err);
    api.showApiErrorNotification('Failed to select files');
  }
}

// ============================================
// FILE OPERATIONS
// ============================================

/**
 * View indexed file details
 */
function viewIndexedFile(fileId) {
  console.log('Viewing file:', fileId);
  // Could open a modal or navigate to file details page
}

/**
 * Delete indexed file
 */
async function deleteIndexedFile(fileId) {
  try {
    await api.deleteIndexedFile(fileId);
    api.showApiSuccessNotification('File deleted successfully');

    // Remove from UI
    const row = document.querySelector(`tr[data-file-id="${fileId}"]`);
    if (row) {
      row.remove();
    }

    // Remove from state
    state.selectedIndexedFiles = state.selectedIndexedFiles.filter((id) => id !== fileId);
    updateSelectedIndexedFilesCount();
  } catch (err) {
    console.error('Error deleting file:', err);
    api.showApiErrorNotification('Failed to delete file');
  }
}

// ============================================
// SEARCH & FILTERING
// ============================================

/**
 * Search indexed files with debouncing
 */
let indexedSearchTimeout;
export function searchIndexedFiles(query) {
  clearTimeout(indexedSearchTimeout);

  // Show spinner
  domUtils.toggleVisibility('search-indexed-spinner', true);

  indexedSearchTimeout = setTimeout(async () => {
    try {
      const data = await api.loadIndexedFiles(1, 50, query);
      if (data) {
        const files = data.indexed_files || [];
        state.indexedFiles = files;
        initializeIndexedFilesDataTable(files);
        updateIndexedPagination(data.pagination);
        domUtils.updateElementText('selected-indexed-files-count', 0);
        state.selectedIndexedFiles = [];
      }
    } catch (err) {
      console.error('Search error:', err);
      api.showApiErrorNotification('Search failed');
    } finally {
      domUtils.toggleVisibility('search-indexed-spinner', false);
    }
  }, 300);
}

// ============================================
// PAGINATION
// ============================================

/**
 * Update indexed pagination controls
 */
function updateIndexedPagination(pagination = {}) {
  const currentPage = pagination.current_page || state.currentIndexedPage;
  const perPage = pagination.per_page || state.itemsPerPage || 10;
  const total = pagination.total || state.indexedFiles.length;
  const totalPages = total > 0 ? Math.ceil(total / perPage) : 1;

  const prevBtn = document.getElementById('indexed-prev');
  const nextBtn = document.getElementById('indexed-next');

  if (prevBtn) {
    prevBtn.disabled = currentPage === 1;
  }

  if (nextBtn) {
    nextBtn.disabled = currentPage >= totalPages;
  }

  // Update page indicator if it exists
  const pageIndicator = document.getElementById('indexed-page-indicator');
  if (pageIndicator) {
    pageIndicator.textContent = total > 0 ? `Page ${currentPage} of ${totalPages}` : 'No pages';
  }
}

/**
 * Go to next page
 */
export async function goToIndexedNextPage() {
  state.currentIndexedPage++;
  await renderIndexedFiles();
  scrollToTop();
}

/**
 * Go to previous page
 */
export async function goToIndexedPreviousPage() {
  if (state.currentIndexedPage > 1) {
    state.currentIndexedPage--;
    await renderIndexedFiles();
    scrollToTop();
  }
}

// ============================================
// TRACKING SHEET GENERATION
// ============================================

/**
 * Generate tracking sheets for selected indexed files
 */
export async function generateTrackingSheets() {
  if (state.selectedIndexedFiles.length === 0) {
    api.showApiErrorNotification('Please select files for tracking sheet generation');
    return;
  }

  try {
    const result = await api.generateTrackingSheets(state.selectedIndexedFiles);

    if (result && result.success) {
      api.showApiSuccessNotification('Tracking sheets generated successfully');
      clearAllIndexedSelections();
      // Optionally refresh the list
      await renderIndexedFiles();
    } else {
      api.showApiErrorNotification('Failed to generate tracking sheets');
    }
  } catch (err) {
    console.error('Error generating tracking sheets:', err);
    api.showApiErrorNotification('Failed to generate tracking sheets');
  }
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  };
  return text.replace(/[&<>"']/g, (m) => map[m]);
}

/**
 * Format date for display
 */
function formatDate(dateString) {
  if (!dateString) return 'Unknown';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
}

/**
 * Scroll to top of indexed files table
 */
function scrollToTop() {
  const table = document.getElementById('indexed-files-table');
  if (table) {
    table.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}
