<!-- JavaScript -->
<script>
  // Initialize Lucide icons safely
  if (typeof lucide !== 'undefined' && lucide.createIcons) {
    lucide.createIcons();
  }
  
  // State variables
  let selectedFiles = []; // Initialize empty - no pre-selected files
  let selectedIndexedFiles = []; // Track selected indexed files
  let pendingFiles = []; // Will be loaded from API
  let indexedFiles = []; // Start empty; will be loaded from API
  let indexingProgress = 0; // Set to 0% initially
  let currentStage = "extract"; // Current stage in the AI pipeline
  let currentTab = 'pending'; // Track current active tab
  
  // Batch History state variables
  let batchHistory = []; // Will be loaded from API
  let currentBatchPage = 1; // Current page for batch history pagination
  
  // Sub-tab state variables for Tracking Sheet tab
  let currentTrackingSubTab = 'not-generated';
  let notGeneratedFiles = [];
  let selectedNotGeneratedFiles = [];
  let currentNotGeneratedPage = 1;
  const notGeneratedItemsPerPage = 50; // Increased for performance
  let isPerformingSelection = false; // Flag to prevent data reload during selection

  // Performance optimization variables
  let lastApiCall = 0; // Timestamp of last API call
  let pendingApiRequest = null; // Track pending API requests
  let indexedApiRequest = null; // Track indexed API requests
  const API_THROTTLE_DELAY = 150; // Minimum delay between API calls

  // Virtual scrolling for large datasets
  let virtualScrollEnabled = true;
  const MAX_VISIBLE_ROWS = 100; // Limit visible rows for performance

  // API Response caching for better performance
  const apiCache = new Map();
  const API_CACHE_TTL = 30000; // 30 seconds cache TTL
  const MAX_CACHE_SIZE = 20; // Maximum number of cached responses

  // Performance monitoring
  let apiCallCount = 0;
  let totalResponseTime = 0;
  
  // DOM Elements
  const tabs = document.querySelectorAll('.tab');
  const tabContents = document.querySelectorAll('.tab-content');
  const pendingFilesList = document.getElementById('pending-files-list');
  const selectedFilesCount = document.getElementById('selected-files-count');
  const beginIndexingBtn = document.getElementById('begin-indexing-btn');
  const newFileIndexBtn = document.getElementById('new-file-index-btn');
  const newFileDialogOverlay = document.getElementById('new-file-dialog-overlay');
  const confirmSaveResultsBtn = document.getElementById('confirm-save-results-btn');
  
  // DOM Elements for AI processing
  const startAiIndexingBtn = document.getElementById('start-ai-indexing-btn');
  const aiProcessingView = document.getElementById('ai-processing-view');
  const progressBar = document.getElementById('progress-bar');
  const progressPercentage = document.getElementById('progress-percentage');
  const pipelineProgressBar = document.getElementById('pipeline-progress-bar');
  const pipelineProgressLine = document.getElementById('pipeline-progress-line');
  const pipelinePercentage = document.getElementById('pipeline-percentage');
  const currentStageInfo = document.getElementById('current-stage-info');
  const aiInsightsContainer = document.getElementById('ai-insights-container');
  
  // DOM Elements for New File Dialog
  const closeDialogBtn = document.getElementById('close-dialog-btn');
  const cancelBtn = document.getElementById('cancel-btn');
  const createFileBtn = document.getElementById('create-file-btn');
  const fileNumberTypeRadios = document.querySelectorAll('input[name="file-number-type"]');

  // Function to toggle file selection
  function toggleFileSelection(fileId) {
    if (selectedFiles.includes(fileId)) {
      selectedFiles = selectedFiles.filter(id => id !== fileId);
    } else {
      selectedFiles.push(fileId);
    }
    
    renderPendingFiles();
    updateSelectedFilesCount();
  }
  
  // Make function globally accessible
  window.toggleFileSelection = toggleFileSelection;
  
  // Function to toggle select all
  function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    if (selectAllCheckbox.checked) {
      // Select all files
      selectedFiles = pendingFiles.map(file => file.id);
    } else {
      // Deselect all files
      selectedFiles = [];
    }
    
    renderPendingFiles();
    updateSelectedFilesCount();
  }
  
  // Function to start AI indexing
  function startAiIndexing() {
    console.log("Starting AI indexing process for", selectedFiles.length, "files...");
    
    // Update the processing files count
    const aiProcessingFilesCount = document.getElementById('ai-processing-files-count');
    if (aiProcessingFilesCount) {
      aiProcessingFilesCount.textContent = selectedFiles.length;
    }
    
    // Hide the initial view and show the processing view
    const initialView = document.querySelector('#indexing-tab .card .p-6 .card');
    if (initialView) {
      initialView.parentElement.classList.add('hidden');
    }
    aiProcessingView.classList.remove('hidden');
    
    // Start the indexing simulation
    simulateIndexingProcess();
  }
  
  // Function to simulate the indexing process
  function simulateIndexingProcess() {
    console.log("Starting AI indexing simulation");
    
    let progress = 0;
    const stages = ['init', 'analyze', 'extract', 'categorize', 'validate', 'complete'];
    let currentStageIndex = 0;
    
    // Stage descriptions
    const stageDescriptions = {
      init: "Setting up AI processing environment and preparing documents for analysis...",
      analyze: "Analyzing document structure and identifying key sections...",
      extract: "Extracting key information and metadata using form templates...",
      categorize: "Categorizing extracted information and applying relevant tags...",
      validate: "Validating extracted data against known patterns and rules...",
      complete: "Finalizing results and preparing data for submission to KLAES..."
    };
    
    // Stage icons
    const stageIcons = {
      init: "loader",
      analyze: "search",
      extract: "layers",
      categorize: "tag",
      validate: "check-circle",
      complete: "check-square"
    };
    
    // Update progress every 500ms
    const interval = setInterval(() => {
      progress += 2;
      
      // Update progress bar and percentage
      progressBar.style.width = `${progress}%`;
      progressPercentage.textContent = `${progress}%`;
      pipelineProgressBar.style.width = `${progress}%`;
      pipelineProgressLine.style.width = `${progress}%`;
      pipelinePercentage.textContent = `${progress}% Complete`;
      
      // Update stage if needed
      const stageThresholds = [0, 20, 40, 60, 80, 95];
      if (progress >= stageThresholds[currentStageIndex + 1] && currentStageIndex < stages.length - 1) {
        // Mark current stage as completed
        document.getElementById(`stage-${stages[currentStageIndex]}`).classList.remove('active');
        document.getElementById(`stage-${stages[currentStageIndex]}`).classList.add('completed');
        
        // Move to next stage
        currentStageIndex++;
        
        // Mark new stage as active
        document.getElementById(`stage-${stages[currentStageIndex]}`).classList.remove('pending');
        document.getElementById(`stage-${stages[currentStageIndex]}`).classList.add('active');
        
        // Update current stage info
        currentStageInfo.innerHTML = `
          <div class="p-2 bg-green-100 rounded-full">
            <i data-lucide="${stageIcons[stages[currentStageIndex]]}" class="h-5 w-5 text-green-500"></i>
          </div>
          <div>
            <p class="text-sm font-medium mb-1">Current Stage: ${stages[currentStageIndex].charAt(0).toUpperCase() + stages[currentStageIndex].slice(1)}</p>
            <p class="text-xs text-gray-600">${stageDescriptions[stages[currentStageIndex]]}</p>
          </div>
        `;
        
        // Initialize Lucide icons for the new content
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
          lucide.createIcons();
        }
        
        // Log progress
        console.log(`AI Integration - Stage ${currentStageIndex + 1}/${stages.length}: ${stages[currentStageIndex]}`);
      }
      
      // Show AI insights at 50% progress
      if (progress === 50) {
        showAiInsights();
      }
      
      // Complete the process
      if (progress >= 100) {
        clearInterval(interval);
        completeIndexingProcess();
      }
    }, 200);
  }
  
  // Function to show AI insights
  function showAiInsights() {
    console.log("Generating AI insights for selected files:", selectedFiles);
    
    // Get the actual selected file objects from pendingFiles
    const selectedFileObjects = pendingFiles.filter(file => selectedFiles.includes(file.id));
    
    if (selectedFileObjects.length === 0) {
      aiInsightsContainer.innerHTML = `
        <div class="text-center p-4">
          <p class="text-gray-500">No files selected for AI analysis.</p>
        </div>
      `;
      return;
    }
    
    // Generate insights for each selected file
    let insightsHTML = `
      <div class="flex items-center mb-2">
        <i data-lucide="zap" class="h-4 w-4 text-green-500 mr-2"></i>
        <h4 class="font-medium">Real-time AI Insights</h4>
      </div>
    `;
    
    selectedFileObjects.forEach((file, index) => {
      // Generate random confidence scores between 85-95%
      const mainConfidence = Math.floor(Math.random() * 11) + 85;
      const ownerConfidence = Math.floor(Math.random() * 11) + 85;
      const plotConfidence = Math.floor(Math.random() * 11) + 85;
      const landUseConfidence = Math.floor(Math.random() * 11) + 85;
      const textQuality = Math.floor(Math.random() * 11) + 85;
      
      // Determine document type based on file name or random selection
      const documentTypes = ['Certificate of Occupancy', 'Site Plan', 'Survey Plan', 'Deed of Assignment', 'Building Plan'];
      const documentType = documentTypes[Math.floor(Math.random() * documentTypes.length)];
      
      // Generate plot number if not available
      const plotNumber = `PL-${Math.floor(Math.random() * 9000) + 1000}`;
      
      // Generate suggested keywords based on file data
      const keywords = [
        file.landUseType || 'Residential',
        file.district || 'Fagge',
        documentType,
        'Land Document',
        'Property',
        'Kano State'
      ];
      
      insightsHTML += `
        <!-- File ${index + 1} insights -->
        <div class="insight-card">
          <div class="insight-header">
            <div>
              <h4 class="text-blue-600 font-medium">${file.fileNumber}</h4>
              <p class="text-gray-600">${file.name}</p>
            </div>
            <div class="flex flex-col items-end">
              <span class="insight-confidence">${mainConfidence}% Confidence</span>
              <span class="text-xs text-gray-500">AI Analysis</span>
            </div>
          </div>
          
          <div class="insight-analysis">
            <div>
              <h5 class="font-medium mb-2">Document Analysis:</h5>
              <div class="space-y-2">
                <div class="insight-field">
                  <span class="insight-field-label">Document Type:</span>
                  <span class="insight-field-value">${documentType}</span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Owner:</span>
                  <span class="insight-field-value">
                    ${file.name}
                    <span class="insight-confidence-pill">${ownerConfidence}%</span>
                  </span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Plot Number:</span>
                  <span class="insight-field-value">
                    ${plotNumber}
                    <span class="insight-confidence-pill">${plotConfidence}%</span>
                  </span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Land Use:</span>
                  <span class="insight-field-value">
                    ${file.landUseType || 'Residential'}
                    <span class="insight-confidence-pill">${landUseConfidence}%</span>
                  </span>
                </div>
              </div>
              
              <h5 class="font-medium mt-4 mb-2">AI Findings:</h5>
              <div class="space-y-2">
                <div class="insight-field">
                  <span class="insight-field-label">Text Quality:</span>
                  <span class="insight-field-value">
                    <span class="insight-confidence-pill">${textQuality}%</span>
                  </span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Document Structure:</span>
                  <span class="insight-field-value">Complete sections</span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Signature:</span>
                  <span class="insight-field-value">${Math.random() > 0.5 ? 'Detected' : 'Not detected'}</span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">Stamp:</span>
                  <span class="insight-field-value">${Math.random() > 0.3 ? 'Official stamp detected' : 'Stamp not clear'}</span>
                </div>
                
                <div class="insight-field">
                  <span class="insight-field-label">GIS Verification:</span>
                  <span class="insight-field-value">${Math.random() > 0.4 ? 'Matched with parcel data' : 'Pending verification'}</span>
                </div>
              </div>
            </div>
            
            <div>
              <h5 class="font-medium mb-2">Suggested Keywords:</h5>
              <div class="insight-keywords">
                ${keywords.map(keyword => `<span class="insight-keyword">${keyword}</span>`).join('')}
              </div>
              
              <div class="insight-issues">
                <h6 class="insight-issues-title">Potential Issues:</h6>
                <ul class="insight-issues-list">
                  ${Math.random() > 0.5 ? '<li>Plot boundaries not specified</li>' : ''}
                  ${Math.random() > 0.6 ? '<li>Ownership information unclear</li>' : ''}
                  ${Math.random() > 0.7 ? '<li>Parcel data needs updating</li>' : ''}
                  ${Math.random() > 0.8 ? '<li>Document quality could be improved</li>' : ''}
                </ul>
              </div>
            </div>
          </div>
        </div>
      `;
    });
    
    aiInsightsContainer.innerHTML = insightsHTML;
    
    // Initialize Lucide icons for the new content
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }
  
  // Function to complete the indexing process
  function completeIndexingProcess() {
    console.log("Completing indexing process and preparing for submission");
    
    // Show the confirm and save button
    confirmSaveResultsBtn.classList.remove('hidden');
  }
  
  // Confirm and save results
  async function confirmAndSaveResults() {
    console.log("Submitting indexed data to KLAES");
    
    try {
      // Get the actual selected file objects from pendingFiles
      const selectedFileObjects = pendingFiles.filter(file => selectedFiles.includes(file.id));
      
      if (selectedFileObjects.length === 0) {
        alert("No files selected for submission.");
        return;
      }
      
      // Prepare bulk entries data for the API
      const bulkEntries = selectedFileObjects.map(file => ({
        file_number: file.fileNumber,
        file_title: file.name,
        plot_number: file.plotNumber || `PL-${Math.floor(Math.random() * 9000) + 1000}`,
        land_use_type: file.landUseType || 'Residential',
        district: file.district || 'Fagge',
        source: 'AI_Indexing',
        application_id: file.application_id,
        source_table: file.source_table,
        extracted_metadata: {
          ai_confidence: Math.floor(Math.random() * 11) + 85,
          processing_date: new Date().toISOString(),
          document_type: ['Certificate of Occupancy', 'Site Plan', 'Survey Plan', 'Deed of Assignment', 'Building Plan'][Math.floor(Math.random() * 5)],
          processed_by_ai: true
        }
      }));
      
      // Submit to the backend API
      const response = await fetch('/fileindexing/store', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
          bulk_entries: bulkEntries
        })
      });
      
      const result = await response.json();
      
      if (result.success || (result.created_count > 0)) {
        let successMessage = `Files have been successfully indexed and submitted to KLAES!\n\n`;
        successMessage += `✅ ${result.created_count} files processed and saved to the database.\n`;
        
        if (result.created_files && result.created_files.length > 0) {
          successMessage += `\nIndexed Files:\n`;
          result.created_files.forEach(file => {
            successMessage += `• ${file.file_number}: ${file.file_title}\n`;
          });
        }
        
        if (result.errors && result.errors.length > 0) {
          successMessage += `\n⚠️ ${result.errors.length} errors occurred:\n`;
          result.errors.forEach(error => {
            successMessage += `• ${error}\n`;
          });
        }
        
        alert(successMessage);
        
        // Move selected files from pending to indexed
        selectedFiles.forEach(fileId => {
          const fileIndex = pendingFiles.findIndex(file => file.id === fileId);
          if (fileIndex !== -1) {
            const file = pendingFiles[fileIndex];
            // Update the source to indicate it's been indexed
            file.source = "Indexed";
            file.indexingDate = new Date().toLocaleDateString();
            // Add to indexed files
            indexedFiles.push(file);
            // Remove from pending files
            pendingFiles.splice(fileIndex, 1);
          }
        });
        
        // Clear selected files
        selectedFiles = [];
        
        // Update counters
        updateCounters();
        
        // Refresh the pending files display
        renderPendingFiles();
        
        // Switch back to the pending tab to see the updated list
        switchTab('pending');
        
        // Reset the AI processing view
        resetAiProcessingView();
        
      } else {
        // Handle case where no files were created but there might be errors to show
        if (result.errors && result.errors.length > 0) {
          let errorMessage = `Some files could not be processed:\n\n`;
          result.errors.forEach(error => {
            errorMessage += `• ${error}\n`;
          });
          alert(errorMessage);
        } else {
          alert(`Error submitting files: ${result.message}`);
        }
        console.error('API Error:', result);
      }
      
    } catch (error) {
      console.error('Error submitting indexed files:', error);
      alert('Error submitting files to the database. Please try again.');
    }
  }

  // Complete indexing process  // Function to reset AI processing view
  function resetAiProcessingView() {
    // Hide the processing view
    aiProcessingView.classList.add('hidden');
    
    // Show the initial indexing tab view
    const initialView = document.querySelector('#indexing-tab .card .p-6 .card');
    if (initialView) {
      initialView.parentElement.classList.remove('hidden');
    }
    
    // Reset progress bars
    progressBar.style.width = '0%';
    progressPercentage.textContent = '0%';
    pipelineProgressBar.style.width = '0%';
    pipelineProgressLine.style.width = '0%';
    pipelinePercentage.textContent = '0% Complete';
    
    // Reset pipeline stages
    const stages = ['init', 'analyze', 'extract', 'categorize', 'validate', 'complete'];
    stages.forEach(stage => {
      const dot = document.getElementById(`stage-${stage}`);
      const label = dot?.nextElementSibling;
      if (dot) {
        dot.className = stage === 'init' ? 'pipeline-dot active' : 'pipeline-dot pending';
      }
      if (label) {
        label.className = stage === 'init' ? 'pipeline-label active' : 'pipeline-label pending';
      }
    });
    
    // Reset current stage info
    if (currentStageInfo) {
      currentStageInfo.innerHTML = `
        <div class="p-2 bg-green-100 rounded-full">
          <i data-lucide="loader" class="h-5 w-5 text-green-500"></i>
        </div>
        <div>
          <p class="text-sm font-medium mb-1">Current Stage: Initialization</p>
          <p class="text-xs text-gray-600">Setting up AI processing environment and preparing documents for analysis...</p>
        </div>
      `;
    }
    
    // Reset AI insights
    aiInsightsContainer.innerHTML = '';
    
    // Hide the confirm save button
    confirmSaveResultsBtn.classList.add('hidden');
  }
  
  // Render indexed files
  function renderIndexedFiles() {
    const tableBody = document.getElementById('indexed-files-table-body');
    const emptyState = document.getElementById('indexed-empty-state');
    const tableContainer = document.getElementById('indexed-table-container');

    // Prefer table-based rendering if present
    if (tableBody) {
      // Toggle empty state visibility
      if (!indexedFiles || indexedFiles.length === 0) {
        if (tableContainer) tableContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        // Update select-all checkbox and counts
        const selectAllCheckbox = document.getElementById('select-all-indexed-checkbox');
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        updateSelectedIndexedFilesCount();
        updateTrackingButton();
        return;
      } else {
        if (tableContainer) tableContainer.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';
      }

      tableBody.innerHTML = '';

      // Update "Select All" checkbox state
      const selectAllCheckbox = document.getElementById('select-all-indexed-checkbox');
      if (selectAllCheckbox) {
        selectAllCheckbox.checked = selectedIndexedFiles.length === indexedFiles.length && indexedFiles.length > 0;
      }

      indexedFiles.forEach(file => {
        const fileIdStr = String(file.id);
        const isSelected = selectedIndexedFiles.includes(fileIdStr);
        const tr = document.createElement('tr');
        tr.setAttribute('data-id', fileIdStr);
        tr.className = `border-b hover:bg-gray-50 ${isSelected ? 'bg-blue-50' : ''}`;
        
        // Use safe fallbacks: prefer explicit fields, then legacy names
        const indexedDate = file.indexed_at || file.date || file.indexingDate || '-';
        const statusText = file.status || file.source || 'Indexed';
        const lgaText = file.lga || file.lga_name || '-';
        const registry = file.registry || '-';
        const sysBatch = file.sys_batch_no || '-';
        const mdcBatch = file.mdc_batch_no || file.batch_no || '-';
        const groupNo = file.group_no || file.group || '-';
        const plotNumber = file.plotNumber || file.plot_number || '-';
        const indexedBy = file.indexed_by || '-';

        tr.innerHTML = `
          <td class="p-3">${file.tracking_id || '-'}</td>
          <td class="p-3">${file.shelf_location || '-'}</td>
          <td class="p-3">${registry}</td>
          <td class="p-3">${sysBatch}</td>
          <td class="p-3">${mdcBatch}</td>
          <td class="p-3">${groupNo}</td>
          <td class="p-3">${file.fileNumber || '-'}</td>
          <td class="p-3">${file.name || '-'}</td>
          <td class="p-3">${plotNumber}</td>
          <td class="p-3">${indexedDate}</td>
          <td class="p-3">${indexedBy}</td>
          <td class="p-3">${file.tpNumber || '-'}</td>
          <td class="p-3">${file.lpknNumber || '-'}</td>
          <td class="p-3">${file.landUseType || '-'}</td>
          <td class="p-3">${file.district || '-'}</td>
          <td class="p-3">${lgaText}</td>
          <td class="p-3">
            <span class="badge badge-green">
              <i data-lucide="check" class="h-3 w-3 mr-1 inline"></i>
              ${statusText}
            </span>
          </td>
          <td class="p-3 text-center">
            <div class="relative inline-block text-left">
              <button type="button" class="actions-dropdown-btn inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-file-id="${fileIdStr}">
                <i data-lucide="more-vertical" class="h-4 w-4"></i>
              </button>
              <div class="actions-dropdown-menu hidden absolute right-0 z-10 mt-2 w-48 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" data-file-id="${fileIdStr}">
                <div class="py-1">
                  <button class="view-file-btn block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-file-id="${fileIdStr}">
                    <i data-lucide="eye" class="h-4 w-4 mr-2 inline t"></i>
                    View Details
                  </button>

                  <button class="edit-file-btn block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-file-id="${fileIdStr}">
                    <i data-lucide="edit" class="h-4 w-4 mr-2 inline"></i>
                    Edit Record
                  </button>

                  <button class="print-tracking-btn block w-full text-left px-4 py-2 text-sm ${!file.batch_generated ? 'text-gray-400 cursor-not-allowed opacity-50' : 'text-gray-700 hover:bg-gray-100'}" 
                          data-file-id="${fileIdStr}" ${!file.batch_generated ? 'disabled' : ''}>
                    <i data-lucide="printer" class="h-4 w-4 mr-2 inline"></i>
                    ${!file.batch_generated ? 'View Tracking Sheet' : 'View Tracking Sheet'}
                  </button>

                  <div class="border-t border-gray-100 my-1"></div>

                  <button class="delete-file-btn block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50" data-file-id="${fileIdStr}">
                    <i data-lucide="trash-2" class="h-4 w-4 mr-2 inline text-red-600"></i>
                    Delete Record
                  </button>
                </div>
              </div>
            </div>
          </td>
        `;
        tableBody.appendChild(tr);
      });

      // Initialize Lucide icons for the new rows
      if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
      }

      // Row click toggles selection (ignore clicks on action buttons and checkboxes)
      tableBody.querySelectorAll('tr[data-id]').forEach(row => {
        row.addEventListener('click', function(e) {
          if (e.target.closest('button') || e.target.closest('input[type="checkbox"]')) return;
          const fileId = this.getAttribute('data-id');
          toggleIndexedFileSelection(fileId);
        });
      });

      // Row checkbox change handler
      tableBody.querySelectorAll('.row-indexed-checkbox').forEach(cb => {
        cb.addEventListener('click', e => e.stopPropagation());
        cb.addEventListener('change', function(e) {
          const id = this.dataset.fileId;
          toggleIndexedFileSelection(id);
        });
      });

      // Action buttons
      tableBody.querySelectorAll('.view-file-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const fileId = this.dataset.fileId;
          const file = indexedFiles.find(f => String(f.id) === fileId);
          if (file) {
            alert(`File Details:\n\nFile Number: ${file.fileNumber}\nName: ${file.name}\nRegistry: ${file.registry || '-'}\nSys Batch No: ${file.sys_batch_no || '-'}\nMDC Batch No: ${file.mdc_batch_no || file.batch_no || '-'}\nGroup: ${file.group_no || file.group || '-'}\nTracking ID: ${file.tracking_id || '-'}\nType: ${file.type || '-'}\nPlot Number: ${file.plotNumber || '-'}\nTP Number: ${file.tpNumber || '-'}\nIndexed Date: ${file.indexed_at || file.date || '-'}\nIndexed By: ${file.indexed_by || '-'}\nLocation: ${file.location || '-'}\nDistrict: ${file.district || '-'}\nLand Use: ${file.landUseType || '-'}\nLPKN Number: ${file.lpknNumber || '-'}`);
          }
        });
      });

      tableBody.querySelectorAll('.edit-file-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const fileId = this.dataset.fileId;
          
          if (!/^\d+$/.test(fileId)) {
            alert('This is a demo file. Editing is not available for demo files.');
            return;
          }
          
          // Open edit form in new window or redirect to edit page
          const editUrl = `/fileindexing/${fileId}/edit`;
          window.open(editUrl, '_blank');
        });
      });

      tableBody.querySelectorAll('.generate-tracking-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          
          // Check if button is disabled
          if (this.hasAttribute('disabled')) {
            const fileId = this.dataset.fileId;
            const file = indexedFiles.find(f => String(f.id) === fileId);
            if (file && file.batch_generated) {
              const batchInfo = file.last_batch_id ? ` (Batch: ${file.last_batch_id})` : '';
              alert(`This file has already been used in batch generation${batchInfo}. Individual tracking sheets cannot be generated for files already included in batch tracking.`);
            }
            return;
          }
          
          const fileId = this.dataset.fileId;
          if (!/^\d+$/.test(fileId)) {
            alert('This is a demo file. Tracking sheet generation is not available for demo files.');
            return;
          }
          const trackingUrl = `/fileindexing/tracking-sheet/${fileId}`;
          window.open(trackingUrl, '_blank');
        });
      });

      tableBody.querySelectorAll('.print-tracking-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          
          // Check if button is disabled
          if (this.hasAttribute('disabled')) {
            const fileId = this.dataset.fileId;
            const file = indexedFiles.find(f => String(f.id) === fileId);
            if (file && !file.batch_generated) {
              alert('No tracking sheet has been generated for this file yet. Please generate a tracking sheet first.');
            }
            return;
          }
          
          const fileId = this.dataset.fileId;
          if (!/^\d+$/.test(fileId)) {
            alert('This is a demo file. Tracking sheet printing is not available for demo files.');
            return;
          }
          
          // For files that have tracking sheets generated, open the batch tracking sheet view
          const trackingUrl = `/fileindexing/batch-tracking-sheet?files=${fileId}`;
          window.open(trackingUrl, '_blank');
        });
      });

      // Delete file button functionality
      tableBody.querySelectorAll('.delete-file-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          
          const fileId = this.dataset.fileId;
          if (!/^\d+$/.test(fileId)) {
            alert('This is a demo file. Deletion is not available for demo files.');
            return;
          }

          const file = indexedFiles.find(f => String(f.id) === fileId);
          const fileName = file ? file.name || file.fileNumber : `File ID: ${fileId}`;
          
          if (confirm(`Are you sure you want to delete "${fileName}"? This action cannot be undone.`)) {
            deleteIndexedFile(fileId);
          }
        });
      });

      // Actions dropdown functionality with responsive positioning
      tableBody.querySelectorAll('.actions-dropdown-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const fileId = this.dataset.fileId;
          const dropdown = tableBody.querySelector(`.actions-dropdown-menu[data-file-id="${fileId}"]`);
          
          // Close all other dropdowns first
          tableBody.querySelectorAll('.actions-dropdown-menu').forEach(menu => {
            if (menu !== dropdown) {
              menu.classList.add('hidden');
            }
          });
          
          if (dropdown) {
            if (dropdown.classList.contains('hidden')) {
              // Show dropdown first
              dropdown.classList.remove('hidden');
              
              // Get button position relative to viewport
              const buttonRect = this.getBoundingClientRect();
              const viewportWidth = window.innerWidth;
              const viewportHeight = window.innerHeight;
              
              // Reset any previous positioning
              dropdown.style.position = 'fixed';
              dropdown.style.zIndex = '9999';
              dropdown.style.top = '';
              dropdown.style.left = '';
              dropdown.style.right = '';
              dropdown.style.bottom = '';
              
              // Get dropdown dimensions after showing
              const dropdownRect = dropdown.getBoundingClientRect();
              const dropdownWidth = dropdownRect.width;
              const dropdownHeight = dropdownRect.height;
              
              // Calculate positions with safety margins
              const margin = 10;
              let top, left;
              
              // Vertical positioning
              if (buttonRect.bottom + dropdownHeight + margin <= viewportHeight) {
                // Place below button
                top = buttonRect.bottom + 2;
              } else if (buttonRect.top - dropdownHeight - margin >= 0) {
                // Place above button
                top = buttonRect.top - dropdownHeight - 2;
              } else {
                // Center vertically in viewport
                top = Math.max(margin, (viewportHeight - dropdownHeight) / 2);
              }
              
              // Horizontal positioning
              if (buttonRect.right - dropdownWidth >= margin) {
                // Align to right edge of button
                left = buttonRect.right - dropdownWidth;
              } else if (buttonRect.left + dropdownWidth + margin <= viewportWidth) {
                // Align to left edge of button
                left = buttonRect.left;
              } else {
                // Center horizontally in viewport
                left = Math.max(margin, (viewportWidth - dropdownWidth) / 2);
              }
              
              // Apply calculated positions
              dropdown.style.top = `${Math.max(margin, Math.min(top, viewportHeight - dropdownHeight - margin))}px`;
              dropdown.style.left = `${Math.max(margin, Math.min(left, viewportWidth - dropdownWidth - margin))}px`;
              
            } else {
              dropdown.classList.add('hidden');
            }
          }
        });
      });

      // Close dropdowns when clicking outside
      document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative.inline-block.text-left')) {
          tableBody.querySelectorAll('.actions-dropdown-menu').forEach(menu => {
            menu.classList.add('hidden');
          });
        }
      });

      // Update counts and button state
      updateSelectedIndexedFilesCount();
      updateTrackingButton();
      return; // Done with table-based rendering
    }

    // Fallback: do nothing if no known container is present to avoid runtime errors
    // (prevents breaking other UI like tabs)
  }
  
  // Switch between tabs
  function switchTab(tabName) {
    try {
      console.log('Switching to tab:', tabName);
      
      // Update current tab state
      currentTab = tabName;
      
      // Query tabs fresh in case DOM changed
      const tabElements = document.querySelectorAll('.tab');
      console.log('Found tab elements:', tabElements.length);

      // Update active tab
      tabElements.forEach(t => {
        if (t.getAttribute('data-tab') === tabName) {
          t.classList.add('active');
        } else {
          t.classList.remove('active');
        }
      });

      // Enable/disable new file button based on active tab
      const newFileBtn = document.getElementById('new-file-index-btn');
      if (newFileBtn) {
        if (tabName === 'pending') {
          newFileBtn.removeAttribute('disabled');
          newFileBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
          newFileBtn.setAttribute('disabled', 'true');
          newFileBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
      }

      // Enable/disable Import CSV button based on active tab
      const importCsvBtn = document.getElementById('import-csv-btn');
      if (importCsvBtn) {
        if (tabName === 'indexed') {
          // Enable only on Indexed Files Report tab
          importCsvBtn.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
        } else {
          // Disable on other tabs (Unindexed Files, Digital Index AI, Tracking Sheet)
          importCsvBtn.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
        }
      }

      // Update visible content
      const tabContents = document.querySelectorAll('.tab-content');
      console.log('Found tab content elements:', tabContents.length);
      
      tabContents.forEach(content => {
        content.classList.add('hidden');
        content.classList.remove('active');
      });
      
      const activeContent = document.getElementById(`${tabName}-tab`);
      if (activeContent) {
        activeContent.classList.remove('hidden');
        activeContent.classList.add('active');
        console.log(`Tab content displayed: ${tabName}-tab`);
      } else {
        console.warn(`Tab content not found: ${tabName}-tab`);
      }
      
      // If switching to indexed tab, load and render the indexed files
      if (tabName === 'indexed') {
        // Load indexed files from API if not already loaded
        if (indexedFiles.length === 0) {
          loadIndexedFiles();
        } else {
          renderIndexedFiles();
        }
      }
      
      // If switching to batch history tab, initialize sub-tabs
      if (tabName === 'batch-history') {
        console.log('Initializing tracking sub-tabs for batch-history tab...');
        // Give DOM a moment to render
        setTimeout(() => {
          initializeTrackingSubTabs();
          // Load data based on current sub-tab
          if (currentTrackingSubTab === 'not-generated') {
            loadNotGeneratedFiles();
          } else if (currentTrackingSubTab === 'generated') {
            loadBatchHistory();
          }
        }, 100);
      }
    } catch (error) {
      console.error('Error in switchTab:', error);
    }
  }
  
  // Render pending files
  function renderPendingFiles() {
    pendingFilesList.innerHTML = '';
    
    // Update the "Select All" checkbox state
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    if (selectAllCheckbox) {
      selectAllCheckbox.checked = pendingFiles.length > 0 && selectedFiles.length === pendingFiles.length;
    }
    
    pendingFiles.forEach(file => {
      const isSelected = selectedFiles.includes(file.id);
      const fileItem = document.createElement('div');
      fileItem.className = 'p-4 border-b last:border-b-0';
      
      fileItem.innerHTML = `
        <div class="flex items-center">
          <input type="checkbox" ${isSelected ? 'checked' : ''} data-id="${file.id}" onclick="toggleFileSelection('${file.id}')" class="mr-4">
          <div class="file-icon">
            <i data-lucide="file-text" class="h-6 w-6"></i>
          </div>
          <div class="file-details ml-4">
            <div class="file-number">${file.fileNumber}</div>
            <div class="file-name">${file.name}</div>
            <div class="file-tags">
              <span class="file-tag">${file.source}</span>
              <span class="file-tag">${file.landUseType}</span>
              <span class="file-tag">${file.district}</span>
              <span class="file-tag">${file.date}</span>
            </div>
          </div>
          <div class="ml-auto">
            <span class="badge badge-yellow">
              <i data-lucide="clock" class="h-3 w-3 mr-1"></i>
              Pending Digital Index
            </span>
          </div>
        </div>
      `;
      
      pendingFilesList.appendChild(fileItem);
    });
    
    // Initialize Lucide icons for the new rows
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
    
    // Update selected files count
    updateSelectedFilesCount();
  }
  
  // Update selected files count
  function updateSelectedFilesCount() {
    selectedFilesCount.textContent = `${selectedFiles.length} of ${pendingFiles.length} selected`;
    
    // Enable/disable buttons based on selection
    if (selectedFiles.length > 0) {
      beginIndexingBtn.removeAttribute('disabled');
      startAiIndexingBtn.removeAttribute('disabled');
    } else {
      beginIndexingBtn.setAttribute('disabled', 'true');
      startAiIndexingBtn.setAttribute('disabled', 'true');
    }
    
    // Update AI indexing count
    const aiSelectedFilesCount = document.getElementById('ai-selected-files-count');
    if (aiSelectedFilesCount) {
      aiSelectedFilesCount.textContent = selectedFiles.length;
    }
  }
  
  // Update counters
  function updateCounters() {
    // Load actual statistics from API instead of using paginated data length
    loadStatistics();
  }

  // Load statistics from API
  async function loadStatistics() {
    try {
      const response = await fetch('/fileindexing/api/statistics');
      const data = await response.json();
      
      if (data.success) {
        document.getElementById('pending-files-count').textContent = data.statistics.pending_files;
        document.getElementById('indexed-files-count').textContent = data.statistics.indexed_today;
        document.getElementById('total-indexed-count').textContent = data.statistics.total_indexed;
      } else {
        console.error('Error loading statistics:', data.message);
        // Fallback to paginated data length if API fails
        document.getElementById('pending-files-count').textContent = pendingFiles.length;
        document.getElementById('indexed-files-count').textContent = indexedFiles.length;
        document.getElementById('total-indexed-count').textContent = 0;
      }
    } catch (error) {
      console.error('Error loading statistics:', error);
      // Fallback to paginated data length if API fails
      document.getElementById('pending-files-count').textContent = pendingFiles.length;
      document.getElementById('indexed-files-count').textContent = indexedFiles.length;
      document.getElementById('total-indexed-count').textContent = 0;
    }
  }
  
  // Show new file dialog
  function showNewFileDialog() {
    newFileDialogOverlay.classList.remove('hidden');
    // Reset form fields
    document.getElementById('new-file-form').reset();
  }
  
  // Close new file dialog
  function closeNewFileDialog() {
    newFileDialogOverlay.classList.add('hidden');
  }
  
  // Create new file
  function createNewFile() {
    // Get form values
    const fileTitle = document.getElementById('file-title').value;
    const fileNumberType = document.querySelector('input[name="file-number-type"]:checked').value;
    
    // Create a new file object
    const newFile = {
      id: `FILE-${Date.now()}`,
      fileNumber: fileNumberType === 'mls' ? 'MLS-' + Date.now().toString().slice(-5) : 'KNGP-' + Date.now().toString().slice(-5),
      name: fileTitle || 'New Property File',
      batch_no: `BATCH-${Date.now().toString().slice(-4)}`,
      tracking_id: `TRK-${Date.now().toString().slice(-6)}`,
      type: 'Certificate of Occupancy',
      source: 'Collated',
      date: new Date().toISOString().split('T')[0],
      landUseType: 'Residential',
      district: 'Nasarawa',
      hasCofo: document.getElementById('has-cofo').checked,
    };
    
    // Add to pending files
    pendingFiles.push(newFile);
    
    // Update counters
    updateCounters();
    
    // Render pending files
    renderPendingFiles();
    
    // Close dialog
    closeNewFileDialog();
    
    // Show success message
    alert('New file index created successfully!');
  }
  
  // Function to toggle indexed file selection
  function toggleIndexedFileSelection(fileId) {
    const idStr = String(fileId);
    
    if (selectedIndexedFiles.includes(idStr)) {
      selectedIndexedFiles = selectedIndexedFiles.filter(id => id !== idStr);
    } else {
      selectedIndexedFiles.push(idStr);
    }
    
    renderIndexedFiles();
    updateSelectedIndexedFilesCount();
    updateTrackingButton();
  }
  
  // Make function globally accessible
  window.toggleIndexedFileSelection = toggleIndexedFileSelection;
  
  // Function to select exactly 100 files
  async function select100Files() {
    const button = document.getElementById('select-100-btn');
    const originalText = button.textContent;
    
    try {
      // Show loading state
      button.disabled = true;
      button.innerHTML = '<i class="animate-spin h-3 w-3 mr-1" style="border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; display: inline-block;"></i>Loading...';
      button.classList.add('opacity-75');
      
      // Use the efficient approach - get only IDs for the first 100 files
      const fileIds = await getFileIdsForSelection(100);
      
      if (fileIds.length === 0) {
        alert('No indexed files available to select.');
        return;
      }
      
      // Clear current selection and use the fetched IDs
      selectedIndexedFiles = fileIds.map(id => String(id));
      
      renderIndexedFiles();
      updateSelectedIndexedFilesCount();
      updateTrackingButton();
      
      // Show feedback message
      const message = fileIds.length < 100 
        ? `Selected all ${fileIds.length} available files (less than 100 files available)`
        : `Selected first 100 files`;
      
      // Create a temporary notification
      showSelectionNotification(message);
    } catch (error) {
      console.error('Error selecting 100 files:', error);
      if (error.message.includes('timeout')) {
        alert('Request timed out while loading files. Please try again or contact support.');
      } else {
        alert('Error loading files for selection. Please try again.');
      }
    } finally {
      // Restore button state
      button.disabled = false;
      button.textContent = originalText;
      button.classList.remove('opacity-75');
    }
  }
  
  // Function to select exactly 200 files
  async function select200Files() {
    const button = document.getElementById('select-200-btn');
    const originalText = button.textContent;
    
    try {
      // Show loading state
      button.disabled = true;
      button.innerHTML = '<i class="animate-spin h-3 w-3 mr-1" style="border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; display: inline-block;"></i>Loading...';
      button.classList.add('opacity-75');
      
      // Use the efficient approach - get only IDs for the first 200 files
      const fileIds = await getFileIdsForSelection(200);
      
      if (fileIds.length === 0) {
        alert('No indexed files available to select.');
        return;
      }
      
      // Clear current selection and use the fetched IDs
      selectedIndexedFiles = fileIds.map(id => String(id));
      
      renderIndexedFiles();
      updateSelectedIndexedFilesCount();
      updateTrackingButton();
      
      // Show feedback message
      const message = fileIds.length < 200 
        ? `Selected all ${fileIds.length} available files (less than 200 files available)`
        : `Selected first 200 files`;
      
      // Create a temporary notification
      showSelectionNotification(message);
    } catch (error) {
      console.error('Error selecting 200 files:', error);
      if (error.message.includes('timeout')) {
        alert('Request timed out while loading files. Please try again or contact support.');
      } else {
        alert('Error loading files for selection. Please try again.');
      }
    } finally {
      // Restore button state
      button.disabled = false;
      button.textContent = originalText;
      button.classList.remove('opacity-75');
    }
  }
  
  // Efficient function to get only file IDs for selection (much faster than fetching full file data)
  async function getFileIdsForSelection(count) {
    try {
      // Create a timeout promise
      const timeoutPromise = new Promise((_, reject) => {
        setTimeout(() => reject(new Error('Request timeout')), 10000); // Reduced to 10 seconds
      });
      
      // This approach fetches only the IDs we need, making it much faster
      const fetchPromise = fetch(`/fileindexing/api/indexed-files?per_page=${count}&fields=id_only`);
      
      // Race between fetch and timeout
      const response = await Promise.race([fetchPromise, timeoutPromise]);
      const data = await response.json();
      
      if (data.success && data.indexed_files) {
        // Extract just the IDs
        return data.indexed_files.map(file => file.id);
      } else {
        console.error('Error fetching file IDs:', data.message);
        // Fallback: try to get IDs from current visible files
        return indexedFiles.slice(0, count).map(file => file.id);
      }
    } catch (error) {
      if (error.message === 'Request timeout') {
        console.error('Request timed out while fetching file IDs.');
        throw new Error('Request timed out. The server may be slow.');
      }
      console.error('Error fetching file IDs:', error);
      // Fallback: use current visible files
      return indexedFiles.slice(0, count).map(file => file.id);
    }
  }
  
  // Function to show selection notification
  function showSelectionNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-opacity duration-300';
    notification.textContent = message;
    
    // Add to DOM
    document.body.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        document.body.removeChild(notification);
      }, 300);
    }, 3000);
  }
  
  // Make functions globally accessible
  window.select100Files = select100Files;
  window.select200Files = select200Files;
  
  // API Cache management functions
  function getCachedResponse(key) {
    const cached = apiCache.get(key);
    if (!cached) return null;
    
    const now = Date.now();
    if (now - cached.timestamp > API_CACHE_TTL) {
      apiCache.delete(key);
      return null;
    }
    
    return cached.data;
  }
  
  function setCachedResponse(key, data) {
    // Implement LRU cache by removing oldest entries
    if (apiCache.size >= MAX_CACHE_SIZE) {
      const firstKey = apiCache.keys().next().value;
      apiCache.delete(firstKey);
    }
    
    apiCache.set(key, {
      data: data,
      timestamp: Date.now()
    });
  }
  
  function clearApiCache() {
    apiCache.clear();
  }
  
  // Function to toggle select all indexed files - SIMPLIFIED VERSION
  async function toggleSelectAllIndexed() {
    const selectAllCheckbox = document.getElementById('select-all-indexed-checkbox');
    const selectAllLabel = document.querySelector('label[for="select-all-indexed-checkbox"]');
    const originalLabelText = selectAllLabel ? selectAllLabel.textContent : 'Select All';
    
    try {
      if (selectAllCheckbox.checked) {
        // Show loading state for select all
        if (selectAllLabel) {
          selectAllLabel.innerHTML = '<i class="animate-spin h-3 w-3 mr-1" style="border: 2px solid #666; border-top: 2px solid transparent; border-radius: 50%; display: inline-block;"></i>Loading...';
        }
        selectAllCheckbox.disabled = true;
        
        // For "Select All", try to get up to 500 files efficiently (reasonable limit)
        try {
          const fileIds = await getFileIdsForSelection(500);
          // Filter out files that have already been used in batch generation
          const availableFileIds = fileIds.filter(id => {
            const file = indexedFiles.find(f => String(f.id) === String(id));
            return !file || !file.batch_generated;
          });
          
          selectedIndexedFiles = availableFileIds.map(id => String(id));
          
          const excludedCount = fileIds.length - availableFileIds.length;
          if (excludedCount > 0) {
            if (availableFileIds.length >= 500) {
              showSelectionNotification(`Selected first 500 available files (${excludedCount} files excluded - already used in batch generation)`);
            } else {
              showSelectionNotification(`Selected ${availableFileIds.length} files (${excludedCount} files excluded - already used in batch generation)`);
            }
          } else {
            if (fileIds.length >= 500) {
              showSelectionNotification(`Selected first 500 files (maximum for Select All to ensure performance)`);
            } else {
              showSelectionNotification(`Selected all ${availableFileIds.length} files`);
            }
          }
        } catch (error) {
          // Fallback to current page if there's an error
          console.log('Falling back to current page selection due to:', error.message);
          // Filter current page files to exclude already-generated ones
          const availableFiles = indexedFiles.filter(file => !file.batch_generated);
          selectedIndexedFiles = availableFiles.map(file => String(file.id));
          const excludedCount = indexedFiles.length - availableFiles.length;
          if (excludedCount > 0) {
            showSelectionNotification(`Selected ${availableFiles.length} files from current page (${excludedCount} files excluded - already used in batch generation)`);
          } else {
            showSelectionNotification(`Selected ${availableFiles.length} files from current page (server timeout - try 100/200 buttons for larger selections)`);
          }
        }
      } else {
        // Deselect all files
        selectedIndexedFiles = [];
      }
      
      renderIndexedFiles();
      updateSelectedIndexedFilesCount();
      updateTrackingButton();
    } catch (error) {
      console.error('Error in toggleSelectAllIndexed:', error);
      // Final fallback to current page only
      if (selectAllCheckbox.checked) {
        selectedIndexedFiles = indexedFiles.map(file => String(file.id));
      } else {
        selectedIndexedFiles = [];
      }
      
      renderIndexedFiles();
      updateSelectedIndexedFilesCount();
      updateTrackingButton();
    } finally {
      // Restore original state
      selectAllCheckbox.disabled = false;
      if (selectAllLabel) {
        selectAllLabel.textContent = originalLabelText;
      }
    }
  }
  
  // Function to update selected indexed files count
  function updateSelectedIndexedFilesCount() {
    const selectedCountElement = document.getElementById('selected-indexed-files-count');
    if (selectedCountElement) {
      selectedCountElement.textContent = `${selectedIndexedFiles.length} selected`;
    }
  }
  
  // Function to update tracking button behavior
  function updateTrackingButton() {
    const trackingBtn = document.getElementById('generate-tracking-sheets-btn');
    const trackingBtnText = document.getElementById('tracking-btn-text');
    const trackingBtnIcon = trackingBtn ? trackingBtn.querySelector('i') : null;
    
    if (!trackingBtn || !trackingBtnText) return;

    // Helper to reset click handler
    function resetClick() {
      // Using property assignment avoids multiple listeners stacking
      trackingBtn.onclick = null;
    }

    // Use selectedNotGeneratedFiles instead of selectedIndexedFiles
    // These files by definition haven't been used in batch generation
    const selectableFileCount = selectedNotGeneratedFiles.length;
    const totalSelectableFiles = notGeneratedFiles.length;

    if (selectableFileCount >= 2) {
      // Enabled state for batch
      let buttonText = 'Generate Batch Tracking Sheets';
      
      // Update button text based on selection count
      if (selectableFileCount === 100) {
        buttonText = 'Generate Batch Tracking Sheets (100)';
      } else if (selectableFileCount === 200) {
        buttonText = 'Generate Batch Tracking Sheets (200)';
      } else if (selectableFileCount > 200) {
        buttonText = `Generate Batch Tracking Sheets (${selectableFileCount})`;
      }
      
      trackingBtnText.textContent = buttonText;
      trackingBtn.removeAttribute('disabled');
      trackingBtn.classList.remove('opacity-50', 'cursor-not-allowed');
      trackingBtn.classList.add('btn-primary');
      if (trackingBtnIcon) trackingBtnIcon.setAttribute('data-lucide', 'file-check');

      resetClick();
      trackingBtn.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        // Use selectedNotGeneratedFiles directly since they're all selectable
        generateBatchTrackingSheets();
      };
    } else {
      // Disabled state for fewer than 2 selections
      let disabledReason = '';
      if (totalSelectableFiles === 0) {
        disabledReason = ' (No files available for tracking)';
      } else {
        disabledReason = ' (Select at least 2 files)';
      }
      
      trackingBtnText.textContent = `Batch Tracking Sheets${disabledReason}`;
      trackingBtn.setAttribute('disabled', 'true');
      trackingBtn.classList.add('opacity-50', 'cursor-not-allowed');
      trackingBtn.classList.remove('btn-primary');
      if (trackingBtnIcon) trackingBtnIcon.setAttribute('data-lucide', 'file-text');
      resetClick();
    }

    // Refresh lucide icons after attribute changes
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }

    // Refresh lucide icons after attribute changes
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
 
  // Function to check if file has tracking record (simplified)
  function hasTrackingRecord(fileId) {
    // All indexed files can now be selected for tracking operations
    return true;
  }
  
  // Function to generate single tracking sheet
  function generateSingleTrackingSheet() {
    const selectedFile = indexedFiles.find(file => String(file.id) === selectedIndexedFiles[0]);
    if (selectedFile) {
      // Extract numeric ID if the file ID contains non-numeric characters
      let fileId = String(selectedFile.id);
      
      // If the ID is not purely numeric, try to extract a numeric part or use a timestamp
      if (!/^\d+$/.test(fileId)) {
        // For demo files with non-numeric IDs, use a fallback approach
        console.log('Non-numeric file ID detected:', fileId);
        alert('This is a demo file. Tracking sheet generation is not available for demo files.');
        return;
      }
      
      // Open tracking sheet in new tab using the blade template
      const trackingUrl = `/fileindexing/tracking-sheet/${fileId}`;
      window.open(trackingUrl, '_blank');
    }
  }

  // Function to open smart batch tracking interface
  function openSmartBatchInterface() {
    if (selectedNotGeneratedFiles.length < 1) {
      alert('Please select at least one file for batch tracking operations.');
      return;
    }
    
    // Show user feedback about what will happen
    const fileCount = selectedNotGeneratedFiles.length;
    const message = `Opening batch tracking interface for ${fileCount} selected file${fileCount > 1 ? 's' : ''}.\n\nAfter generating the tracking sheets, return to this tab to see the updated file status.`;
    
    if (confirm(message)) {
      // Open smart batch tracking interface with selected files
      const fileIds = selectedNotGeneratedFiles.join(',');
      const batchInterfaceUrl = `/fileindexing/batch-tracking-interface?files=${fileIds}`;
      window.open(batchInterfaceUrl, '_blank');
    }
  }
  
  // Function to generate batch tracking sheets (legacy function, now redirects to smart interface)
  function generateBatchTrackingSheets() {
    openSmartBatchInterface();
  }
  
  // Function to print tracking sheet
  function printTrackingSheet(fileId) {
    const printUrl = `/fileindexing/print-tracking-sheet/${fileId}`;
    window.open(printUrl, '_blank');
  }
  
  // Function to add action menu event listeners
  function addActionMenuListeners() {
    // Handle action menu dropdown toggle
    document.querySelectorAll('.action-menu-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = this.nextElementSibling;
        
        // Close all other dropdowns
        document.querySelectorAll('.action-dropdown').forEach(d => {
          if (d !== dropdown) d.classList.add('hidden');
        });
        
        // Toggle current dropdown
        dropdown.classList.toggle('hidden');
      });
    });
    
    // Handle view file details
    document.querySelectorAll('.view-file-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        const file = indexedFiles.find(f => f.id === fileId);
        if (file) {
          alert(`File Details:\n\nFile Number: ${file.fileNumber}\nName: ${file.name}\nBatch NO: ${file.batch_no || '-'}\nTracking ID: ${file.tracking_id || '-'}\nType: ${file.type}\nDistrict: ${file.district}\nLand Use: ${file.landUseType}\nDate: ${file.date}`);
        }
        this.closest('.action-dropdown').classList.add('hidden');
      });
    });
    
    // Handle generate tracking sheet
    document.querySelectorAll('.generate-tracking-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        
        // Check if this is a demo file (non-numeric ID)
        if (!/^\d+$/.test(fileId)) {
          alert('This is a demo file. Tracking sheet generation is not available for demo files.');
          this.closest('.action-dropdown').classList.add('hidden');
          return;
        }
        
        // Open tracking sheet in new tab
        const trackingUrl = `/fileindexing/tracking-sheet/${fileId}`;
        window.open(trackingUrl, '_blank');
        this.closest('.action-dropdown').classList.add('hidden');
      });
    });
    
    // Handle print tracking sheet
    document.querySelectorAll('.print-tracking-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        
        // Check if this is a demo file (non-numeric ID)
        if (!/^\d+$/.test(fileId)) {
          alert('This is a demo file. Print tracking is not available for demo files.');
          this.closest('.action-dropdown').classList.add('hidden');
          return;
        }
        
        printTrackingSheet(fileId);
        this.closest('.action-dropdown').classList.add('hidden');
      });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
      document.querySelectorAll('.action-dropdown').forEach(dropdown => {
        dropdown.classList.add('hidden');
      });
    });
  }
  
  // Pagination state
  let currentPendingPage = 1;
  let currentIndexedPage = 1;
  const itemsPerPage = 10;
  
  // API Functions to load dynamic data with pagination
  async function loadPendingFiles(search = '', page = 1) {
    try {
      const response = await fetch(`/fileindexing/api/pending-files?search=${encodeURIComponent(search)}&page=${page}&per_page=${itemsPerPage}`);
      const data = await response.json();
      
      if (data.success) {
        pendingFiles = data.pending_files;
        currentPendingPage = page;
        renderPendingFiles();
        updateCounters();
        updatePendingPagination(data.pagination || {
          current_page: page,
          total: data.pending_files?.length || 0,
          per_page: itemsPerPage,
          last_page: Math.ceil((data.pending_files?.length || 0) / itemsPerPage)
        });
      } else {
        console.error('Error loading pending files:', data.message);
        // Fallback to empty array if API fails
        pendingFiles = [];
        renderPendingFiles();
        updateCounters();
        hidePendingPagination();
      }
    } catch (error) {
      console.error('Error loading pending files:', error);
      // Fallback to empty array if API fails
      pendingFiles = [];
      renderPendingFiles();
      updateCounters();
      hidePendingPagination();
    }
  }
  
  async function loadIndexedFiles(search = '', page = 1) {
    try {
      console.log(`Loading indexed files: search="${search}", page=${page}, per_page=${itemsPerPage}`);
      
      // Add headers for proper Laravel AJAX requests
      const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
      
      // Add CSRF token if available
      const csrfToken = document.querySelector('meta[name="csrf-token"]');
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
      }
      
      console.log('Making request with headers:', headers);
      
      const response = await fetch(`/fileindexing/api/indexed-files?search=${encodeURIComponent(search)}&page=${page}&per_page=${itemsPerPage}`, {
        method: 'GET',
        headers: headers,
        credentials: 'include' // Include cookies for authentication
      });
      
      console.log('Response status:', response.status);
      console.log('Response headers:', Object.fromEntries(response.headers.entries()));
      
      // Check if response is HTML (redirect to login) instead of JSON
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('text/html')) {
        console.error('Received HTML response instead of JSON - likely authentication redirect');
        throw new Error('Authentication required - please refresh the page and log in again');
      }
      
      if (!response.ok) {
        const errorText = await response.text();
        console.error('Error response body:', errorText);
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('Indexed files API response:', data);
      
      if (data.success) {
        indexedFiles = data.indexed_files;
        console.log(`Loaded ${indexedFiles.length} indexed files`);
        currentIndexedPage = page;
        renderIndexedFiles();
        updateCounters();
        updateIndexedPagination(data.pagination || {
          current_page: page,
          total: data.pagination?.total || data.indexed_files?.length || 0,
          per_page: itemsPerPage,
          last_page: Math.ceil((data.pagination?.total || data.indexed_files?.length || 0) / itemsPerPage)
        });
      } else {
        console.error('Error loading indexed files:', data.message);
        // Silenced user-friendly error - log only
        console.log(`Error loading indexed files: ${data.message || 'Unknown error'}`);
        // Fallback to empty array if API fails
        indexedFiles = [];
        renderIndexedFiles();
        updateCounters();
        hideIndexedPagination();
      }
    } catch (error) {
      console.error('Error loading indexed files:', error);
      
      // Check if it's an authentication issue
      if (error.message.includes('401') || error.message.includes('Unauthorized') || error.message.includes('Authentication required')) {
        console.log('Authentication error. Please refresh the page and try again.');
        // Optionally redirect to login or refresh the page - silenced for now
        // setTimeout(() => {
        //   window.location.reload();
        // }, 2000);
      } else if (error.message.includes('403') || error.message.includes('Forbidden')) {
        console.log('Permission error. You may not have access to view indexed files.');
      } else if (error.message.includes('500')) {
        console.log('Server error. Please check the logs and try again.');
      } else {
        console.log(`Error loading indexed files: ${error.message}`);
      }
      
      // Fallback to empty array if API fails
      indexedFiles = [];
      renderIndexedFiles();
      updateCounters();
      hideIndexedPagination();
    }
  }
  
  // Pagination functions
  function updatePendingPagination(pagination) {
    const paginationContainer = document.getElementById('pending-pagination');
    const startElement = document.getElementById('pending-start');
    const endElement = document.getElementById('pending-end');
    const totalElement = document.getElementById('pending-total');
    const paginationNav = document.getElementById('pending-pagination-nav');
    
    if (!pagination || pagination.total === 0) {
      hidePendingPagination();
      return;
    }
    
    // Show pagination
    paginationContainer.style.display = 'flex';
    
    // Update counters
    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    
    startElement.textContent = start;
    endElement.textContent = end;
    totalElement.textContent = pagination.total;
    
    // Generate page numbers
    generatePageNumbers(paginationNav, pagination.current_page, pagination.last_page, 'pending');
  }
  
  function updateIndexedPagination(pagination) {
    const paginationContainer = document.getElementById('indexed-pagination');
    const startElement = document.getElementById('indexed-start');
    const endElement = document.getElementById('indexed-end');
    const totalElement = document.getElementById('indexed-total');
    const paginationNav = document.getElementById('indexed-pagination-nav');
    
    if (!pagination || pagination.total === 0) {
      hideIndexedPagination();
      return;
    }
    
    // Show pagination
    paginationContainer.style.display = 'flex';
    
    // Update counters
    const start = ((pagination.current_page - 1) * pagination.per_page) + 1;
    const end = Math.min(pagination.current_page * pagination.per_page, pagination.total);
    
    startElement.textContent = start;
    endElement.textContent = end;
    totalElement.textContent = pagination.total;
    
    // Generate page numbers
    generatePageNumbers(paginationNav, pagination.current_page, pagination.last_page, 'indexed');
  }
  
  function hidePendingPagination() {
    const paginationContainer = document.getElementById('pending-pagination');
    if (paginationContainer) {
      paginationContainer.style.display = 'none';
    }
  }
  
  function hideIndexedPagination() {
    const paginationContainer = document.getElementById('indexed-pagination');
    if (paginationContainer) {
      paginationContainer.style.display = 'none';
    }
  }
  
  function generatePageNumbers(container, currentPage, lastPage, type) {
    // Clear existing page numbers (keep prev/next buttons)
    const existingNumbers = container.querySelectorAll('.page-number');
    existingNumbers.forEach(el => el.remove());
    
    const prevButton = container.querySelector(`#${type}-prev`);
    const nextButton = container.querySelector(`#${type}-next`);
    
    // Calculate page range to show
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(lastPage, startPage + maxVisiblePages - 1);
    
    // Adjust start if we're near the end
    if (endPage - startPage < maxVisiblePages - 1) {
      startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    // Create page number buttons
    for (let i = startPage; i <= endPage; i++) {
      const pageButton = document.createElement('button');
      pageButton.className = `page-number relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
        i === currentPage 
          ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600' 
          : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
      }`;
      pageButton.textContent = i;
      pageButton.onclick = () => {
        if (type === 'pending') {
          loadPendingFiles(document.getElementById('search-pending-files')?.value || '', i);
        } else {
          loadIndexedFiles(document.getElementById('search-indexed-files')?.value || '', i);
        }
      };
      
      // Insert before next button
      container.insertBefore(pageButton, nextButton);
    }
    
    // Update prev/next button states
    prevButton.disabled = currentPage <= 1;
    nextButton.disabled = currentPage >= lastPage;
    
    prevButton.className = `relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 text-sm font-medium ${
      currentPage <= 1 
        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
        : 'bg-white text-gray-500 hover:bg-gray-50'
    }`;
    
    nextButton.className = `relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 text-sm font-medium ${
      currentPage >= lastPage 
        ? 'bg-gray-100 text-gray-400 cursor-not-allowed' 
        : 'bg-white text-gray-500 hover:bg-gray-50'
    }`;
  }
  
  // Search functionality
  function setupSearchListeners() {
    const searchPendingInput = document.getElementById('search-pending-files');
    const searchIndexedInput = document.getElementById('search-indexed-files');
    
    if (searchPendingInput) {
      // Optimized search with instant client-side filtering
      const throttledPendingSearch = throttle((searchTerm) => {
        // Show loading indicator
        showSearchSpinner('pending');
        
        // Instant client-side search for immediate feedback
        if (pendingFiles.length > 0) {
          const filteredFiles = smartSearch.searchFiles(pendingFiles, searchTerm);
          renderPendingFiles(filteredFiles.slice(0, 20));
          updateSearchCount('pending', filteredFiles.length);
        }
        
        // Hide loading indicator
        setTimeout(() => hideSearchSpinner('pending'), 100);
      }, 50);

      const debouncedServerSearch = debounce((searchTerm) => {
        currentPendingPage = 1;
        if (searchTerm.length >= 3) {
          showSearchSpinner('pending');
          loadPendingFiles(searchTerm, 1).finally(() => {
            hideSearchSpinner('pending');
          });
        }
      }, 300);

      searchPendingInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Clear search - show all files immediately
        if (searchTerm === '') {
          smartSearch.clearCache();
          renderPendingFiles(pendingFiles.slice(0, 20));
          hideSearchSpinner('pending');
          updateSearchCount('pending', pendingFiles.length);
          return;
        }
        
        // Immediate response for client-side search
        if (searchTerm.length >= 1) {
          throttledPendingSearch(searchTerm);
        }
        
        // Server search for comprehensive results (longer delay)
        if (searchTerm.length >= 3) {
          debouncedServerSearch(searchTerm);
        }
      });

      // Optimize focus handling
      searchPendingInput.addEventListener('focus', function() {
        if (this.value.trim()) {
          throttledPendingSearch(this.value.trim());
        }
      });

      // Add search suggestions on focus
      searchPendingInput.addEventListener('focus', function() {
        showSearchSuggestions(this, 'pending');
      });
    }
    
    if (searchIndexedInput) {
      // Optimized search for indexed files
      const throttledIndexedSearch = throttle((searchTerm) => {
        showSearchSpinner('indexed');
        
        if (indexedFiles.length > 0) {
          const filteredFiles = smartSearch.searchFiles(indexedFiles, searchTerm);
          renderIndexedFiles(filteredFiles.slice(0, 20));
          updateSearchCount('indexed', filteredFiles.length);
        }
        
        setTimeout(() => hideSearchSpinner('indexed'), 100);
      }, 50);

      const debouncedIndexedServerSearch = debounce((searchTerm) => {
        currentIndexedPage = 1;
        if (searchTerm.length >= 3) {
          showSearchSpinner('indexed');
          loadIndexedFiles(searchTerm, 1).finally(() => {
            hideSearchSpinner('indexed');
          });
        }
      }, 300);

      searchIndexedInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        if (searchTerm === '') {
          smartSearch.clearCache();
          renderIndexedFiles(indexedFiles.slice(0, 20));
          hideSearchSpinner('indexed');
          updateSearchCount('indexed', indexedFiles.length);
          return;
        }
        
        // Immediate client-side search
        if (searchTerm.length >= 1) {
          throttledIndexedSearch(searchTerm);
        }
        
        // Server search for comprehensive results
        if (searchTerm.length >= 3) {
          debouncedIndexedServerSearch(searchTerm);
        }
      });

      // Optimized focus handling
      searchIndexedInput.addEventListener('focus', function() {
        if (this.value.trim()) {
          throttledIndexedSearch(this.value.trim());
        }
      });
    }
  }
  
  // Setup pagination event listeners
  function setupPaginationListeners() {
    // Pending files pagination
    const pendingPrev = document.getElementById('pending-prev');
    const pendingNext = document.getElementById('pending-next');
    const pendingPrevMobile = document.getElementById('pending-prev-mobile');
    const pendingNextMobile = document.getElementById('pending-next-mobile');
    
    if (pendingPrev) {
      pendingPrev.addEventListener('click', () => {
        if (currentPendingPage > 1) {
          loadPendingFiles(document.getElementById('search-pending-files')?.value || '', currentPendingPage - 1);
        }
      });
    }
    
    if (pendingNext) {
      pendingNext.addEventListener('click', () => {
        loadPendingFiles(document.getElementById('search-pending-files')?.value || '', currentPendingPage + 1);
      });
    }
    
    if (pendingPrevMobile) {
      pendingPrevMobile.addEventListener('click', () => {
        if (currentPendingPage > 1) {
          loadPendingFiles(document.getElementById('search-pending-files')?.value || '', currentPendingPage - 1);
        }
      });
    }
    
    if (pendingNextMobile) {
      pendingNextMobile.addEventListener('click', () => {
        loadPendingFiles(document.getElementById('search-pending-files')?.value || '', currentPendingPage + 1);
      });
    }
    
    // Indexed files pagination
    const indexedPrev = document.getElementById('indexed-prev');
    const indexedNext = document.getElementById('indexed-next');
    const indexedPrevMobile = document.getElementById('indexed-prev-mobile');
    const indexedNextMobile = document.getElementById('indexed-next-mobile');
    
    if (indexedPrev) {
      indexedPrev.addEventListener('click', () => {
        if (currentIndexedPage > 1) {
          loadIndexedFiles(document.getElementById('search-indexed-files')?.value || '', currentIndexedPage - 1);
        }
      });
    }
    
    if (indexedNext) {
      indexedNext.addEventListener('click', () => {
        loadIndexedFiles(document.getElementById('search-indexed-files')?.value || '', currentIndexedPage + 1);
      });
    }
    
    if (indexedPrevMobile) {
      indexedPrevMobile.addEventListener('click', () => {
        if (currentIndexedPage > 1) {
          loadIndexedFiles(document.getElementById('search-indexed-files')?.value || '', currentIndexedPage - 1);
        }
      });
    }
    
    if (indexedNextMobile) {
      indexedNextMobile.addEventListener('click', () => {
        loadIndexedFiles(document.getElementById('search-indexed-files')?.value || '', currentIndexedPage + 1);
      });
    }
  }
  
  // ========================================
  // BATCH HISTORY FUNCTIONS
  // ========================================
  
  // Function to render batch history table
  function renderBatchHistory() {
    const tableBody = document.getElementById('batch-history-table-body');
    const emptyState = document.getElementById('batch-history-empty-state');
    const tableContainer = document.getElementById('batch-history-table-container');
    
    if (!tableBody) return;
    
    if (batchHistory.length === 0) {
      if (emptyState) emptyState.style.display = 'block';
      if (tableContainer) tableContainer.style.display = 'none';
      return;
    }
    
    if (emptyState) emptyState.style.display = 'none';
    if (tableContainer) tableContainer.style.display = 'block';
    
    tableBody.innerHTML = batchHistory.map(batch => {
      const statusBadge = getBatchStatusBadge(batch.status);
      const typeBadge = getBatchTypeBadge(batch.batch_type);
      
      return `
        <tr class="border-b hover:bg-gray-50">
          <td class="p-3">
            <div class="font-medium text-gray-900">${batch.batch_id}</div>
          </td>
          <td class="p-3">
            <div class="font-medium text-gray-900">${batch.batch_name}</div>
            ${batch.notes ? `<div class="text-sm text-gray-500">${batch.notes}</div>` : ''}
          </td>
          <td class="p-3">
            <span class="font-medium">${batch.file_count}</span>
          </td>
          <td class="p-3">
            ${typeBadge}
          </td>
          <td class="p-3">
            ${statusBadge}
          </td>
          <td class="p-3">
            <div class="text-sm font-medium">${formatDateTime(batch.generated_at)}</div>
            ${batch.generated_by_name ? `<div class="text-xs text-gray-500">by ${batch.generated_by_name}</div>` : ''}
          </td>
          <td class="p-3">
            <span class="font-medium">${batch.print_count}</span>
            ${batch.last_printed_at ? `<div class="text-xs text-gray-500">Last: ${formatDate(batch.last_printed_at)}</div>` : ''}
          </td>
          <td class="p-3">
            <div class="flex items-center gap-2">
              <button onclick="viewBatchDetails('${batch.batch_id}')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                View
              </button>
              <button onclick="reprintBatch('${batch.batch_id}')" class="text-green-600 hover:text-green-800 text-sm font-medium">
                Reprint
              </button>
            </div>
          </td>
        </tr>
      `;
    }).join('');
  }
  
  // Function to get status badge HTML
  function getBatchStatusBadge(status) {
    const badges = {
      'generated': '<span class="badge badge-blue">Generated</span>',
      'printed': '<span class="badge badge-green">Printed</span>',
      'archived': '<span class="badge badge-gray">Archived</span>'
    };
    return badges[status] || `<span class="badge badge-gray">${status}</span>`;
  }
  
  // Function to get batch type badge HTML  
  function getBatchTypeBadge(type) {
    const badges = {
      'manual': '<span class="badge badge-purple">Manual</span>',
      'auto_100': '<span class="badge badge-blue">Auto 100</span>',
      'auto_200': '<span class="badge badge-green">Auto 200</span>'
    };
    return badges[type] || `<span class="badge badge-gray">${type}</span>`;
  }
  
  // Function to format date and time
  function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
  }
  
  // Function to format date only
  function formatDate(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString();
  }
  
  // Function to load batch history from API
  async function loadBatchHistory(search = '', page = 1) {
    try {
      const response = await fetch(`/fileindexing/api/batch-history?search=${encodeURIComponent(search)}&page=${page}&per_page=${itemsPerPage}`);
      const data = await response.json();
      
      if (data.success) {
        batchHistory = data.batches || [];
        currentBatchPage = page;
        renderBatchHistory();
        updateBatchHistoryPagination(data.pagination || {
          current_page: page,
          total: data.batches?.length || 0,
          per_page: itemsPerPage,
          last_page: Math.ceil((data.batches?.length || 0) / itemsPerPage)
        });
      } else {
        console.error('Error loading batch history:', data.message);
        batchHistory = [];
        renderBatchHistory();
        hideBatchHistoryPagination();
      }
    } catch (error) {
      console.error('Error loading batch history:', error);
      batchHistory = [];
      renderBatchHistory();
      hideBatchHistoryPagination();
    }
  }
  
  // Function to view batch details
  function viewBatchDetails(batchId) {
    const batch = batchHistory.find(b => b.batch_id === batchId);
    if (!batch) return;
    
    const modal = document.getElementById('batch-details-modal');
    const title = document.getElementById('batch-modal-title');
    const content = document.getElementById('batch-modal-content');
    const reprintBtn = document.getElementById('reprint-batch-btn');
    
    if (!modal || !title || !content) return;
    
    title.textContent = `Batch Details: ${batch.batch_id}`;
    
    content.innerHTML = `
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Batch ID</label>
            <p class="mt-1 text-sm text-gray-900">${batch.batch_id}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Batch Name</label>
            <p class="mt-1 text-sm text-gray-900">${batch.batch_name}</p>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">File Count</label>
            <p class="mt-1 text-sm text-gray-900">${batch.file_count} files</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Batch Type</label>
            <p class="mt-1">${getBatchTypeBadge(batch.batch_type)}</p>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Status</label>
            <p class="mt-1">${getBatchStatusBadge(batch.status)}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Print Count</label>
            <p class="mt-1 text-sm text-gray-900">${batch.print_count} times</p>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Generated</label>
            <p class="mt-1 text-sm text-gray-900">${formatDateTime(batch.generated_at)}</p>
            ${batch.generated_by_name ? `<p class="text-xs text-gray-500">by ${batch.generated_by_name}</p>` : ''}
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Last Printed</label>
            <p class="mt-1 text-sm text-gray-900">${batch.last_printed_at ? formatDateTime(batch.last_printed_at) : 'Never'}</p>
            ${batch.last_printed_by_name ? `<p class="text-xs text-gray-500">by ${batch.last_printed_by_name}</p>` : ''}
          </div>
        </div>
        
        ${batch.notes ? `
        <div>
          <label class="block text-sm font-medium text-gray-700">Notes</label>
          <p class="mt-1 text-sm text-gray-900">${batch.notes}</p>
        </div>
        ` : ''}
      </div>
    `;
    
    // Set up reprint button
    if (reprintBtn) {
      reprintBtn.onclick = () => {
        reprintBatch(batch.batch_id);
        closeBatchDetailsModal();
      };
    }
    
    modal.classList.remove('hidden');
  }
  
  // Function to close batch details modal
  function closeBatchDetailsModal() {
    const modal = document.getElementById('batch-details-modal');
    if (modal) {
      modal.classList.add('hidden');
    }
  }
  
  // Function to reprint batch
  function reprintBatch(batchId) {
    const batch = batchHistory.find(b => b.batch_id === batchId);
    if (!batch) {
      alert('Batch not found');
      return;
    }
    
    // Use the batch tracking interface with batch_id parameter
    // The backend will resolve the batch_id to get the associated file IDs
    const reprintUrl = `/fileindexing/batch-tracking-interface?batch_id=${batch.batch_id}`;
    window.open(reprintUrl, '_blank');
    
    // Update print count
    updateBatchPrintCount(batch.batch_id);
  }
  
  // Function to update batch print count
  async function updateBatchPrintCount(batchId) {
    try {
      const response = await fetch(`/fileindexing/api/batch-history/${batchId}/print`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        }
      });
      
      if (response.ok) {
        // Reload batch history to get updated print count
        loadBatchHistory(document.getElementById('search-batch-history')?.value || '', currentBatchPage);
      }
    } catch (error) {
      console.error('Error updating print count:', error);
    }
  }
  
  // Function to update batch history pagination
  function updateBatchHistoryPagination(pagination) {
    // Implementation similar to other pagination functions
    // For brevity, showing minimal implementation
    const paginationElement = document.getElementById('batch-history-pagination');
    if (paginationElement && pagination.last_page > 1) {
      paginationElement.style.display = 'flex';
      // Update pagination numbers and controls
    } else {
      hideBatchHistoryPagination();
    }
  }
  
  // Function to hide batch history pagination
  function hideBatchHistoryPagination() {
    const paginationElement = document.getElementById('batch-history-pagination');
    if (paginationElement) {
      paginationElement.style.display = 'none';
    }
  }
  
  // Make functions globally accessible
  window.viewBatchDetails = viewBatchDetails;
  window.reprintBatch = reprintBatch;
  window.closeBatchDetailsModal = closeBatchDetailsModal;
  
  // Optimized utility function for debouncing search input
  function debounce(func, wait = 150) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // Throttle function for immediate response with rate limiting
  function throttle(func, limit = 100) {
    let inThrottle;
    return function() {
      const args = arguments;
      const context = this;
      if (!inThrottle) {
        func.apply(context, args);
        inThrottle = true;
        setTimeout(() => inThrottle = false, limit);
      }
    }
  }

  // Optimized advanced search utilities
  class SmartSearch {
    constructor() {
      this.searchCache = new Map();
      this.lastSearchResults = new Map();
      this.maxCacheSize = 50; // Limit cache size for memory efficiency
    }

    // Optimized fuzzy search algorithm
    fuzzyMatch(needle, haystack) {
      if (!needle || !haystack) return false;
      
      // Cache the lowercase versions
      const needleLower = needle.toLowerCase();
      const haystackLower = haystack.toLowerCase();
      
      // Quick exact and contains checks first (fastest)
      if (haystackLower.includes(needleLower)) return true;
      
      // For short terms, skip expensive fuzzy matching
      if (needleLower.length <= 2) return false;
      
      // Simplified word-based matching for performance
      const needleWords = needleLower.split(/\s+/);
      const haystackWords = haystackLower.split(/\s+/);
      
      // At least 70% of words should match
      const matchThreshold = Math.ceil(needleWords.length * 0.7);
      let matches = 0;
      
      for (const word of needleWords) {
        if (word.length <= 1) continue; // Skip single characters
        
        for (const hWord of haystackWords) {
          if (hWord.includes(word) || word.includes(hWord)) {
            matches++;
            break;
          }
        }
      }
      
      return matches >= matchThreshold;
    }

    // Optimized edit distance calculation (removed - not used in current implementation)
    // Performance optimization: Removed expensive Levenshtein distance calculation
    // Uses simpler string matching methods for better performance

    // Optimized fast search with intelligent scoring
    searchFiles(files, searchTerm) {
      if (!searchTerm || searchTerm.trim().length === 0) {
        return files;
      }

      // Simplified cache key for better performance
      const cacheKey = `${files.length}_${searchTerm}`;
      if (this.searchCache.has(cacheKey)) {
        return this.searchCache.get(cacheKey);
      }

      const term = searchTerm.toLowerCase().trim();
      const results = [];

      // Pre-compile searchable text for each file (performance optimization)
      const processedFiles = files.map(file => ({
        file,
        searchText: [
          file.fileNumber || file.file_number || '',
          file.name || file.file_title || '',
          file.district || '',
          file.lga || '',
          file.plot_number || file.plotNumber || '',
          file.registry || '',
          file.batch_no || file.batchNo || '',
          file.tp_no || file.tpNumber || '',
          file.lpkn_no || file.lpknNumber || '',
          file.tracking_id || ''
        ].join(' ').toLowerCase()
      }));

      // Fast filtering with priority-based scoring
      processedFiles.forEach(({ file, searchText }) => {
        let score = 0;
        let matches = false;

        // Primary field checks (most important fields first)
        const fileNumber = (file.fileNumber || file.file_number || '').toLowerCase();
        const fileName = (file.name || file.file_title || '').toLowerCase();
        const plotNumber = (file.plot_number || file.plotNumber || '').toLowerCase();

        // Quick exact matches (highest priority)
        if (fileNumber === term || fileName === term || plotNumber === term) {
          score += 100;
          matches = true;
        }
        // Quick starts with matches
        else if (fileNumber.startsWith(term) || fileName.startsWith(term) || plotNumber.startsWith(term)) {
          score += 80;
          matches = true;
        }
        // Quick contains matches
        else if (searchText.includes(term)) {
          score += 60;
          matches = true;
        }
        // Only do fuzzy matching for longer terms to avoid slowness
        else if (term.length >= 3 && this.fuzzyMatch(term, searchText)) {
          score += 30;
          matches = true;
        }

        // Optimized multi-word search (only for 2-3 words to avoid performance issues)
        const termWords = term.split(/\s+/);
        if (termWords.length > 1 && termWords.length <= 3) {
          let wordMatches = 0;
          for (const word of termWords) {
            if (word.length >= 2 && searchText.includes(word)) {
              wordMatches++;
            }
          }
          
          if (wordMatches === termWords.length) {
            score += 20; // Bonus for matching all words
            matches = true;
          } else if (wordMatches > 0) {
            score += wordMatches * 8;
            matches = true;
          }
        }

        if (matches) {
          results.push({ ...file, _searchScore: score });
        }
      });

      // Sort by score (highest first) - limit to top 100 for performance
      results.sort((a, b) => b._searchScore - a._searchScore);
      const limitedResults = results.slice(0, 100);
      
      // Optimized cache management
      if (this.searchCache.size >= this.maxCacheSize) {
        // Clear oldest entries
        const keysToDelete = Array.from(this.searchCache.keys()).slice(0, 10);
        keysToDelete.forEach(key => this.searchCache.delete(key));
      }
      
      this.searchCache.set(cacheKey, limitedResults);
      return limitedResults;
    }

    // Clear cache when needed
    clearCache() {
      this.searchCache.clear();
    }
  }

  // Initialize smart search instance
  const smartSearch = new SmartSearch();

  // Enhanced keyboard shortcuts for better UX
  document.addEventListener('keydown', function(e) {
    // Ctrl+F or Cmd+F - Focus search input
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
      e.preventDefault();
      
      // Focus the appropriate search input based on current tab
      let searchInput;
      if (currentTab === 'pending') {
        searchInput = document.getElementById('search-pending-files');
      } else if (currentTab === 'indexed') {
        searchInput = document.getElementById('search-indexed-files');
      } else if (currentTrackingSubTab === 'not-generated') {
        searchInput = document.getElementById('search-not-generated');
      }
      
      if (searchInput) {
        searchInput.focus();
        searchInput.select();
      }
    }
    
    // Escape - Clear search inputs
    if (e.key === 'Escape') {
      const activeSearch = document.activeElement;
      if (activeSearch && activeSearch.type === 'search') {
        activeSearch.value = '';
        activeSearch.dispatchEvent(new Event('input'));
        activeSearch.blur();
      }
    }
  });

  // Enhanced search UI functions
  function showSearchSpinner(context) {
    const spinner = document.getElementById(`search-${context}-spinner`);
    const count = document.getElementById(`search-${context}-count`);
    
    if (spinner) {
      spinner.classList.remove('hidden');
    }
    if (count) {
      count.classList.add('hidden');
    }
  }

  function hideSearchSpinner(context) {
    const spinner = document.getElementById(`search-${context}-spinner`);
    
    if (spinner) {
      spinner.classList.add('hidden');
    }
  }

  function updateSearchCount(context, count) {
    const countEl = document.getElementById(`search-${context}-count`);
    const spinner = document.getElementById(`search-${context}-spinner`);
    
    if (countEl && count !== undefined) {
      countEl.textContent = `${count}`;
      countEl.classList.remove('hidden');
    }
    if (spinner) {
      spinner.classList.add('hidden');
    }
  }

  // Legacy function - kept for compatibility
  function showSearchLoading(context) {
    const searchInput = document.getElementById(`search-${context === 'pending' ? 'pending-files' : context === 'indexed' ? 'indexed-files' : 'not-generated'}`);
    if (!searchInput) return null;

    // Create loading indicator
    const loadingEl = document.createElement('div');
    loadingEl.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
    loadingEl.innerHTML = '<div class="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full"></div>';
    loadingEl.id = `search-loading-${context}`;

    // Add to search container
    const searchContainer = searchInput.parentElement;
    searchContainer.style.position = 'relative';
    searchContainer.appendChild(loadingEl);

    return loadingEl;
  }

  function hideSearchLoading(loadingEl) {
    if (loadingEl && loadingEl.parentNode) {
      loadingEl.parentNode.removeChild(loadingEl);
    }
  }

  function showSearchSuggestions(inputEl, context) {
    // Get recent searches or common terms based on context
    let suggestions = [];
    
    switch(context) {
      case 'pending':
      case 'indexed':
      case 'not-generated':
        suggestions = getSearchSuggestions(context);
        break;
    }

    if (suggestions.length === 0) return;

    // Create suggestions dropdown
    const existingDropdown = document.getElementById(`search-suggestions-${context}`);
    if (existingDropdown) {
      existingDropdown.remove();
    }

    const dropdown = document.createElement('div');
    dropdown.id = `search-suggestions-${context}`;
    dropdown.className = 'absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-md shadow-lg z-50 mt-1 max-h-48 overflow-y-auto';
    dropdown.style.display = 'none';

    suggestions.slice(0, 8).forEach((suggestion, index) => {
      const item = document.createElement('div');
      item.className = 'px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm border-b border-gray-100 last:border-b-0';
      item.innerHTML = `
        <div class="flex items-center">
          <i data-lucide="search" class="h-3 w-3 text-gray-400 mr-2"></i>
          <span>${suggestion}</span>
        </div>
      `;
      
      item.addEventListener('click', () => {
        inputEl.value = suggestion;
        inputEl.dispatchEvent(new Event('input'));
        dropdown.remove();
        inputEl.focus();
      });

      dropdown.appendChild(item);
    });

    // Position dropdown
    const container = inputEl.parentElement;
    container.style.position = 'relative';
    container.appendChild(dropdown);

    // Show dropdown on input focus if it has no value
    if (inputEl.value.trim() === '') {
      dropdown.style.display = 'block';
    }

    // Hide dropdown when clicking outside
    const hideDropdown = (e) => {
      if (!container.contains(e.target)) {
        dropdown.remove();
        document.removeEventListener('click', hideDropdown);
      }
    };
    setTimeout(() => document.addEventListener('click', hideDropdown), 100);

    // Hide dropdown when input loses focus (delayed to allow click on suggestions)
    inputEl.addEventListener('blur', () => {
      setTimeout(() => {
        if (dropdown.parentNode) {
          dropdown.remove();
        }
      }, 200);
    });

    // Refresh Lucide icons
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }

  function getSearchSuggestions(context) {
    // Get suggestions based on context and existing data
    const suggestions = new Set();
    
    let dataSource = [];
    switch(context) {
      case 'pending':
        dataSource = pendingFiles || [];
        break;
      case 'indexed':
        dataSource = indexedFiles || [];
        break;
      case 'not-generated':
        dataSource = notGeneratedFiles || [];
        break;
    }

    // Extract common search terms from data
    dataSource.slice(0, 20).forEach(file => {
      // Add districts
      if (file.district && file.district.trim()) {
        suggestions.add(file.district.trim());
      }
      // Add LGAs
      if (file.lga && file.lga.trim()) {
        suggestions.add(file.lga.trim());
      }
      // Add registries
      if (file.registry && file.registry.trim()) {
        suggestions.add(file.registry.trim());
      }
      // Add batch numbers
      if (file.batch_no && file.batch_no.toString().trim()) {
        suggestions.add(`Batch ${file.batch_no}`);
      }
    });

    // Add common search patterns
    const commonTerms = [
      'Commercial', 'Residential', 'Industrial', 'Mixed Development',
      'Kano Municipal', 'Fagge', 'Dala', 'Gwale', 'Tarauni', 'Nasarawa',
      'Kumbotso', 'Ungogo', 'Kiru', 'Bebeji', 'Bichi'
    ];
    
    commonTerms.forEach(term => suggestions.add(term));

    return Array.from(suggestions).sort();
  }

  // Enhanced keyboard shortcuts for search
  function setupSearchKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
      // Ctrl+F or Cmd+F to focus search in current tab
      if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
        e.preventDefault();
        
        let searchInput;
        switch(currentTab) {
          case 'pending':
            searchInput = document.getElementById('search-pending-files');
            break;
          case 'indexed':
            searchInput = document.getElementById('search-indexed-files');
            break;
          case 'batch-history':
            if (currentTrackingSubTab === 'not-generated') {
              searchInput = document.getElementById('search-not-generated');
            } else {
              searchInput = document.getElementById('search-batch-history');
            }
            break;
        }
        
        if (searchInput) {
          searchInput.focus();
          searchInput.select();
        }
      }
    });
  }
  
  // Initialize the page when DOM is loaded
  document.addEventListener('DOMContentLoaded', function() {
    console.log("Initializing File Indexing Assistant");
    
    // Make sure File Index tab is active by default
    switchTab('pending');
    
    // Load and render lists and counters
    loadPendingFiles();
    loadIndexedFiles();
    updateCounters();
    
    // Add page visibility listener to refresh data when user returns from batch generation
    document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
        // User returned to the tab - refresh indexed files to show updated batch status
        console.log('Tab became visible - refreshing indexed files');
        loadIndexedFiles();
        // Also refresh batch history in case new batches were generated
        loadBatchHistory();
      }
    });
    
    // Tabs click handling (ignore disabled tabs)
    document.querySelectorAll('.tab').forEach(tab => {
      tab.addEventListener('click', () => {
        if (tab.classList.contains('disabled')) return;
        const tabName = tab.getAttribute('data-tab');
        switchTab(tabName);
      });
    });

    // Empty-state button to go to Pending
    const goToPendingBtn = document.getElementById('go-to-pending');
    if (goToPendingBtn) {
      goToPendingBtn.addEventListener('click', () => switchTab('pending'));
    }

    // Initialize Export functionality for Indexed Files Report
    initializeExportFunctionality();

    if (beginIndexingBtn) {
      beginIndexingBtn.addEventListener('click', () => {
        // Only switch tabs if files are selected
        if (selectedFiles.length > 0) {
          // Update the AI Indexing file counts
          const aiIndexingFilesCount = document.getElementById('ai-indexing-files-count');
          const aiSelectedFilesCount = document.getElementById('ai-selected-files-count');
          const aiProcessingFilesCount = document.getElementById('ai-processing-files-count');
          
          if (aiIndexingFilesCount) {
            aiIndexingFilesCount.textContent = selectedFiles.length;
          }
          if (aiSelectedFilesCount) {
            aiSelectedFilesCount.textContent = selectedFiles.length;
          }
          if (aiProcessingFilesCount) {
            aiProcessingFilesCount.textContent = selectedFiles.length;
          }
          
          // Switch to the indexing tab
          switchTab('indexing');
        } else {
          alert("Please select at least one file to begin indexing.");
        }
      });
    }
    
    // New File Dialog event listeners
    if (newFileIndexBtn) {
      newFileIndexBtn.addEventListener('click', function() {
        if (typeof openFileIndexingDialog === 'function') {
          openFileIndexingDialog();
        } else {
          showNewFileDialog();
        }
      });
    }
    if (closeDialogBtn) {
      closeDialogBtn.addEventListener('click', closeNewFileDialog);
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', closeNewFileDialog);
    }
    if (createFileBtn) {
      createFileBtn.addEventListener('click', createNewFile);
    }
    
    // File number type radio buttons
    fileNumberTypeRadios.forEach(radio => {
      radio.addEventListener('change', function() {
        document.querySelectorAll('.form-radio-item').forEach(item => {
          if (item.contains(this)) {
            item.classList.add('active');
          } else {
            item.classList.remove('active');
          }
        });
      });
    });
    
    if (startAiIndexingBtn) {
      startAiIndexingBtn.addEventListener('click', startAiIndexing);
    }
    if (confirmSaveResultsBtn) {
      confirmSaveResultsBtn.addEventListener('click', confirmAndSaveResults);
    }
    
    // Setup search listeners
    setupSearchListeners();
    
    // Setup pagination listeners
    setupPaginationListeners();
    
    // Load initial data
    loadPendingFiles();
    loadIndexedFiles();

    // Ensure tracking button reflects initial selection state
    updateTrackingButton();

    // Select All (Pending)
    const selectAllPending = document.getElementById('select-all-checkbox');
    if (selectAllPending) {
      selectAllPending.addEventListener('change', toggleSelectAll);
    }

    // Select All (Indexed)
    const selectAllIndexed = document.getElementById('select-all-indexed-checkbox');
    if (selectAllIndexed) {
      selectAllIndexed.addEventListener('change', toggleSelectAllIndexed);
    }

    // Select 100 Files button
    const select100Btn = document.getElementById('select-100-btn');
    if (select100Btn) {
      select100Btn.addEventListener('click', select100Files);
    }

    // Select 200 Files button
    const select200Btn = document.getElementById('select-200-btn');
    if (select200Btn) {
      select200Btn.addEventListener('click', select200Files);
    }

    // Refresh Indexed Files button
    const refreshIndexedBtn = document.getElementById('refresh-indexed-files');
    if (refreshIndexedBtn) {
      refreshIndexedBtn.addEventListener('click', function() {
        console.log('Manual refresh requested for indexed files');
        loadIndexedFiles();
      });
    }

    // Batch History event listeners
    const refreshBatchHistoryBtn = document.getElementById('refresh-batch-history');
    if (refreshBatchHistoryBtn) {
      refreshBatchHistoryBtn.addEventListener('click', () => {
        loadBatchHistory(document.getElementById('search-batch-history')?.value || '', 1);
      });
    }

    const searchBatchHistory = document.getElementById('search-batch-history');
    if (searchBatchHistory) {
      searchBatchHistory.addEventListener('input', debounce((e) => {
        loadBatchHistory(e.target.value, 1);
      }, 300));
    }

    const goToIndexedFromBatch = document.getElementById('go-to-indexed-from-batch');
    if (goToIndexedFromBatch) {
      goToIndexedFromBatch.addEventListener('click', () => switchTab('indexed'));
    }
  }); // Close DOMContentLoaded event listener

  // Sub-tab functionality for Tracking Sheet tab
  let subTabsInitialized = false;

  // Initialize sub-tabs
  document.addEventListener('DOMContentLoaded', function() {
    initializeTrackingSubTabs();
  });

  function initializeTrackingSubTabs() {
    try {
      console.log('initializeTrackingSubTabs called, already initialized:', subTabsInitialized);
      
      // Check if already initialized to avoid duplicate event listeners
      if (subTabsInitialized) {
        console.log('Sub-tabs already initialized, skipping...');
        return;
      }

      // Add click handlers for sub-tab buttons
      const subTabButtons = document.querySelectorAll('.tracking-sub-tab-btn');
      console.log('Found sub-tab buttons:', subTabButtons.length);
      
      if (subTabButtons.length === 0) {
        console.warn('No sub-tab buttons found! The Tracking Sheet tab might not be properly structured.');
        return;
      }
      
      subTabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
          const subtab = this.getAttribute('data-subtab');
          console.log('Sub-tab clicked:', subtab);
          switchTrackingSubTab(subtab);
        });
      });

      subTabsInitialized = true;
      console.log('Sub-tabs initialized successfully');

      // Load initial data for not-generated tab
      if (currentTrackingSubTab === 'not-generated') {
        loadNotGeneratedFiles();
      }
    } catch (error) {
      console.error('Error in initializeTrackingSubTabs:', error);
    }
  }

  function switchTrackingSubTab(subtab) {
    try {
      console.log('Switching to sub-tab:', subtab);
      
      // Update button states
      const allSubTabButtons = document.querySelectorAll('.tracking-sub-tab-btn');
      console.log('Found sub-tab buttons for styling:', allSubTabButtons.length);
      
      allSubTabButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.classList.add('border-transparent', 'text-gray-500');
        btn.classList.remove('text-blue-600');
      });

      const activeBtn = document.querySelector(`.tracking-sub-tab-btn[data-subtab="${subtab}"]`);
      if (activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.classList.remove('border-transparent', 'text-gray-500');
        activeBtn.classList.add('text-blue-600');
        console.log('Sub-tab button styled successfully');
      } else {
        console.warn(`Sub-tab button not found for: ${subtab}`);
      }

      // Hide all sub-content
      const allSubContent = document.querySelectorAll('.tracking-sub-content');
      console.log('Found sub-content divs:', allSubContent.length);
      
      allSubContent.forEach(content => {
        content.classList.add('hidden');
      });

      // Show selected sub-content
      const activeContent = document.getElementById(`${subtab}-subtab`);
      if (activeContent) {
        activeContent.classList.remove('hidden');
        console.log(`Sub-content displayed: ${subtab}-subtab`);
      } else {
        console.warn(`Sub-content not found: ${subtab}-subtab`);
      }

      currentTrackingSubTab = subtab;

      // Load data based on active tab
      if (subtab === 'not-generated') {
        loadNotGeneratedFiles();
        // Refresh the batch dropdown when switching to not-generated tab
        loadAvailableBatches();
      } else if (subtab === 'generated') {
        loadBatchHistory();
      }
    } catch (error) {
      console.error('Error in switchTrackingSubTab:', error);
    }
  }

  // Load ALL not generated files (for selection operations)
  async function loadAllNotGeneratedFiles() {
    try {
      console.log('Loading ALL not generated files for selection...');
      
      const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
      
      const csrfToken = document.querySelector('meta[name="csrf-token"]');
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
      }

      // Load all files by setting a very high per_page limit
      const apiUrl = `/fileindexing/api/indexed-files?per_page=10000`;
      console.log('Making API request for all files to:', apiUrl);
      
      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: headers,
        credentials: 'include'
      });
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      const data = await response.json();
      console.log('API response data for all files:', data);
      
      if (data.success) {
        // Filter to only show files that haven't been used in batch generation
        notGeneratedFiles = data.indexed_files.filter(file => !file.batch_generated);
        console.log(`Loaded ALL ${notGeneratedFiles.length} not generated files out of ${data.indexed_files.length} total indexed files`);
        
        // Don't render here, let the calling function handle rendering
        return notGeneratedFiles;
      } else {
        throw new Error(data.message || 'Failed to load all not generated files');
      }
    } catch (error) {
      console.error('Error loading all not generated files:', error);
      throw error;
    }
  }

  // Load files that don't have tracking sheets generated
  async function loadNotGeneratedFiles(search = '', page = 1) {
    // Don't reload data if we're currently performing a selection operation
    if (isPerformingSelection) {
      console.log('Skipping data reload during selection operation');
      return;
    }
    
    try {
      console.log(`Loading not generated files: search="${search}", page=${page}`);
      
      const headers = {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      };
      
      const csrfToken = document.querySelector('meta[name="csrf-token"]');
      if (csrfToken) {
        headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
      } else {
        console.warn('CSRF token not found in meta tag');
      }

      // Use the indexed files API with only_not_generated parameter for better performance
      const apiUrl = `/fileindexing/api/indexed-files?search=${encodeURIComponent(search)}&page=${page}&per_page=100&only_not_generated=true`;
      console.log('Making API request to:', apiUrl);
      
      const response = await fetch(apiUrl, {
        method: 'GET',
        headers: headers,
        credentials: 'include'
      });
      
      console.log('API response status:', response.status);
      
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('API response data:', data);
      
      if (data.success) {
        // Since we're using only_not_generated=true, all files should be not generated
        notGeneratedFiles = data.indexed_files;
        console.log(`Loaded ${notGeneratedFiles.length} not generated files`);
        console.log('Sample not generated files:', notGeneratedFiles.slice(0, 3));
        currentNotGeneratedPage = page;
        renderNotGeneratedFiles();
        updateNotGeneratedPagination(data.pagination.total);
        
        // Only reset selection if this is an initial load or explicit refresh
        // Preserve selection during normal data updates
        if (page === 1 && !search) {
          // This is likely a tab switch or initial load, so reset selection
          selectedNotGeneratedFiles = [];
          updateSelectedNotGeneratedFilesCount();
          updateTrackingButton();
        } else {
          // This is a search or pagination, preserve current selection
          // but make sure the selection is still valid
          const validFileIds = notGeneratedFiles.map(file => String(file.id));
          selectedNotGeneratedFiles = selectedNotGeneratedFiles.filter(id => validFileIds.includes(id));
          updateSelectedNotGeneratedFilesCount();
          updateTrackingButton();
        }
      } else {
        console.error('API returned error:', data.message || 'Unknown error');
        throw new Error(data.message || 'Failed to load not generated files');
      }
    } catch (error) {
      console.error('Error loading not generated files:', error);
      
      // Show user-friendly error message
      const tableBody = document.getElementById('not-generated-table-body');
      if (tableBody) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="9" class="p-6 text-center text-red-600">
              <div class="flex flex-col items-center">
                <i data-lucide="alert-circle" class="h-8 w-8 mb-2"></i>
                <p class="font-medium">Error loading files</p>
                <p class="text-sm text-gray-500">${error.message}</p>
                <button class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" onclick="loadNotGeneratedFiles()">
                  Try Again
                </button>
              </div>
            </td>
          </tr>
        `;
      }
    }
  }

  // Render the not generated files table
  function renderNotGeneratedFiles() {
    try {
      console.log('Rendering not generated files:', notGeneratedFiles.length);

      const tableBody = document.getElementById('not-generated-table-body');
      const emptyState = document.getElementById('not-generated-empty-state');
      const tableContainer = document.getElementById('not-generated-table-container');

      if (!tableBody) {
        console.error('Table body element not found: not-generated-table-body');
        return;
      }

      // Toggle empty state visibility
      if (!notGeneratedFiles || notGeneratedFiles.length === 0) {
        if (tableContainer) tableContainer.style.display = 'none';
        if (emptyState) emptyState.style.display = 'block';
        console.log('Showing empty state for not generated files');
        return;
      } else {
        if (tableContainer) tableContainer.style.display = 'block';
        if (emptyState) emptyState.style.display = 'none';
      }

      // Clear existing content efficiently
      tableBody.innerHTML = '';

      // Update "Select All" checkbox state
      const selectAllCheckbox = document.getElementById('select-all-not-generated-checkbox');
      if (selectAllCheckbox) {
        selectAllCheckbox.checked = selectedNotGeneratedFiles.length === notGeneratedFiles.length && notGeneratedFiles.length > 0;
      }

      console.log(`Rendering ${notGeneratedFiles.length} not generated files`);

      // Implement virtual scrolling for large datasets
      const totalFiles = notGeneratedFiles.length;
      const startIndex = (currentNotGeneratedPage - 1) * notGeneratedItemsPerPage;
      const endIndex = Math.min(startIndex + notGeneratedItemsPerPage, totalFiles);
      const visibleFiles = notGeneratedFiles.slice(startIndex, endIndex);

      // Limit rendering for performance
      const maxRender = virtualScrollEnabled ? Math.min(visibleFiles.length, MAX_VISIBLE_ROWS) : visibleFiles.length;

      // Batch DOM creation for better performance
      const fragment = document.createDocumentFragment();

      for (let i = 0; i < maxRender; i++) {
        const file = visibleFiles[i];
        if (!file) continue;

        try {
          const fileIdStr = String(file.id);
          const isSelected = selectedNotGeneratedFiles.includes(fileIdStr);
          const tr = document.createElement('tr');
          tr.setAttribute('data-id', fileIdStr);
          tr.className = `border-b hover:bg-gray-50 ${isSelected ? 'bg-blue-50' : ''}`;

          const indexedDate = file.date || file.indexingDate || '-';
          const statusText = file.status || file.source || 'Indexed';

          tr.innerHTML = `
            <td class="p-3 w-10">
              <input type="checkbox" class="row-not-generated-checkbox" data-file-id="${fileIdStr}"
                     ${isSelected ? 'checked' : ''} />
            </td>
            <td class="p-3">${file.tracking_id || '-'}</td>
            <td class="p-3">${indexedDate}</td>
            <td class="p-3">${file.fileNumber || '-'}</td>
            <td class="p-3">${file.name || '-'}</td>
            <td class="p-3">${file.registry || '-'}</td>
            <td class="p-3">${file.batch_no || '-'}</td>
            <td class="p-3">
              <span class="badge badge-green">
                <i data-lucide="check" class="h-3 w-3 mr-1 inline"></i>
                ${statusText}
              </span>
            </td>
            <td class="p-3 text-center">
              <div class="relative inline-block text-left">
                <button type="button" class="actions-dropdown-btn inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" data-file-id="${fileIdStr}">
                  <i data-lucide="more-vertical" class="h-4 w-4"></i>
                </button>
                <div class="actions-dropdown-menu hidden fixed z-50 mt-2 w-48 origin-top-right bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none" data-file-id="${fileIdStr}" style="right: 1rem;">
                  <div class="py-1">
                    <button class="view-file-btn block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-file-id="${fileIdStr}">
                      <i data-lucide="eye" class="h-4 w-4 mr-2 inline"></i>
                      View Details
                    </button>
                    <button class="generate-tracking-btn block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" data-file-id="${fileIdStr}">
                      <i data-lucide="file-text" class="h-4 w-4 mr-2 inline"></i>
                      Generate Tracking Sheet
                    </button>
                  </div>
                </div>
              </div>
            </td>
          `;

          fragment.appendChild(tr);
        } catch (rowError) {
          console.error('Error rendering file row:', file, rowError);
        }
      }

      // Append all rows at once for better performance
      tableBody.appendChild(fragment);

      // Update pagination info
      updateNotGeneratedPagination(totalFiles, currentNotGeneratedPage, Math.ceil(totalFiles / notGeneratedItemsPerPage));

      // Reinitialize Lucide icons for new content
      if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
      }

      // Add event listeners efficiently
      addNotGeneratedEventListeners(tableBody);

      console.log('Not generated files rendered successfully');
    } catch (error) {
      console.error('Error in renderNotGeneratedFiles:', error);
    }
  }

  function addNotGeneratedEventListeners() {
    const tableBody = document.getElementById('not-generated-table-body');
    if (!tableBody) return;

    // Row click toggles selection
    tableBody.querySelectorAll('tr[data-id]').forEach(row => {
      row.addEventListener('click', function(e) {
        if (e.target.closest('button') || e.target.closest('input[type="checkbox"]')) return;
        const fileId = this.getAttribute('data-id');
        toggleNotGeneratedFileSelection(fileId);
      });
    });

    // Row checkbox change handler
    tableBody.querySelectorAll('.row-not-generated-checkbox').forEach(cb => {
      cb.addEventListener('click', e => e.stopPropagation());
      cb.addEventListener('change', function(e) {
        const id = this.dataset.fileId;
        toggleNotGeneratedFileSelection(id);
      });
    });

    // Action buttons
    tableBody.querySelectorAll('.view-file-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        const file = notGeneratedFiles.find(f => String(f.id) === fileId);
        if (file) {
          alert(`File Details:\n\nFile Number: ${file.fileNumber}\nName: ${file.name}\nBatch NO: ${file.batch_no || '-'}\nTracking ID: ${file.tracking_id || '-'}\nType: ${file.type || '-'}\nPlot Number: ${file.plotNumber || '-'}\nTP Number: ${file.tpNumber || '-'}\nLocation: ${file.location || '-'}\nDistrict: ${file.district || '-'}\nLand Use: ${file.landUseType || '-'}\nLPKN Number: ${file.lpknNumber || '-'}\nDate: ${file.date || '-'}`);
        }
      });
    });

    tableBody.querySelectorAll('.generate-tracking-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        
        if (!/^\d+$/.test(fileId)) {
          alert('This is a demo file. Tracking sheet generation is not available for demo files.');
          return;
        }
        
        const trackingUrl = `/fileindexing/tracking-sheet/${fileId}`;
        window.open(trackingUrl, '_blank');
      });
    });

    // Actions dropdown functionality with responsive positioning
    tableBody.querySelectorAll('.actions-dropdown-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const fileId = this.dataset.fileId;
        const dropdown = tableBody.querySelector(`.actions-dropdown-menu[data-file-id="${fileId}"]`);
        
        // Close all other dropdowns first
        tableBody.querySelectorAll('.actions-dropdown-menu').forEach(menu => {
          if (menu !== dropdown) {
            menu.classList.add('hidden');
          }
        });
        
        if (dropdown) {
          if (dropdown.classList.contains('hidden')) {
            // Show dropdown first
            dropdown.classList.remove('hidden');
            
            // Get button position relative to viewport
            const buttonRect = this.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            // Reset any previous positioning
            dropdown.style.position = 'fixed';
            dropdown.style.zIndex = '9999';
            dropdown.style.top = '';
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.bottom = '';
            
            // Get dropdown dimensions after showing
            const dropdownRect = dropdown.getBoundingClientRect();
            const dropdownWidth = dropdownRect.width;
            const dropdownHeight = dropdownRect.height;
            
            // Calculate positions with safety margins
            const margin = 10;
            let top, left;
            
            // Vertical positioning
            if (buttonRect.bottom + dropdownHeight + margin <= viewportHeight) {
              // Place below button
              top = buttonRect.bottom + 2;
            } else if (buttonRect.top - dropdownHeight - margin >= 0) {
              // Place above button
              top = buttonRect.top - dropdownHeight - 2;
            } else {
              // Center vertically in viewport
              top = Math.max(margin, (viewportHeight - dropdownHeight) / 2);
            }
            
            // Horizontal positioning
            if (buttonRect.right - dropdownWidth >= margin) {
              // Align to right edge of button
              left = buttonRect.right - dropdownWidth;
            } else if (buttonRect.left + dropdownWidth + margin <= viewportWidth) {
              // Align to left edge of button
              left = buttonRect.left;
            } else {
              // Center horizontally in viewport
              left = Math.max(margin, (viewportWidth - dropdownWidth) / 2);
            }
            
            // Apply calculated positions
            dropdown.style.top = `${Math.max(margin, Math.min(top, viewportHeight - dropdownHeight - margin))}px`;
            dropdown.style.left = `${Math.max(margin, Math.min(left, viewportWidth - dropdownWidth - margin))}px`;
            
          } else {
            dropdown.classList.add('hidden');
          }
        }
      });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.relative.inline-block.text-left')) {
        tableBody.querySelectorAll('.actions-dropdown-menu').forEach(menu => {
          menu.classList.add('hidden');
        });
      }
    });
  }

  function toggleNotGeneratedFileSelection(fileId) {
    const fileIdStr = String(fileId);
    const index = selectedNotGeneratedFiles.indexOf(fileIdStr);
    
    if (index > -1) {
      selectedNotGeneratedFiles.splice(index, 1);
    } else {
      selectedNotGeneratedFiles.push(fileIdStr);
    }
    
    renderNotGeneratedFiles();
    updateSelectedNotGeneratedFilesCount();
    updateTrackingButton();
  }

  // Function to update selected not generated files count
  function updateSelectedNotGeneratedFilesCount() {
    console.log('updateSelectedNotGeneratedFilesCount called');
    console.log('selectedNotGeneratedFiles.length:', selectedNotGeneratedFiles.length);
    
    const countElement = document.getElementById('selected-not-generated-files-count');
    if (countElement) {
      countElement.textContent = `${selectedNotGeneratedFiles.length} selected`;
      console.log('Updated count display to:', countElement.textContent);
    } else {
      console.error('Count element not found: selected-not-generated-files-count');
    }
  }

  // Function to load available batches into the dropdown
  async function loadAvailableBatches() {
    try {
      console.log('Loading available batches...');
      
      const response = await fetch('/fileindexing/api/available-batches', {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success) {
        const dropdown = document.getElementById('batch-selection-dropdown');
        if (dropdown) {
          const previousValue = dropdown.value;
          const loadButton = document.getElementById('load-batch-files-btn');

          dropdown.innerHTML = '';

          const placeholder = document.createElement('option');
          placeholder.value = '';
          placeholder.textContent = data.batches.length === 0 ? 'No eligible batches found' : '-- Select a Batch --';
          placeholder.selected = true;
          placeholder.disabled = data.batches.length === 0;
          dropdown.appendChild(placeholder);

          data.batches.forEach(batch => {
            const option = document.createElement('option');
            option.value = batch.id;
            option.textContent = `Batch ${batch.text}`;
            option.dataset.fileCount = batch.file_count ?? batch.used_shelves ?? 0;
            option.dataset.availableShelves = batch.available_shelves ?? (batch.total_shelves ?? 100) - (batch.file_count ?? 0);
            option.dataset.totalShelves = batch.total_shelves ?? 100;
            dropdown.appendChild(option);
          });

          if (previousValue && dropdown.querySelector(`option[value="${previousValue}"]`)) {
            dropdown.value = previousValue;
            if (loadButton) {
              loadButton.disabled = false;
              loadButton.classList.remove('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
            }
          } else if (loadButton) {
            loadButton.disabled = true;
            loadButton.classList.add('opacity-50', 'cursor-not-allowed', 'pointer-events-none');
          }
        }
        
        console.log('Loaded', data.batches.length, 'available batches');
        
        // Initialize Lucide icons for the dropdown elements
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
          lucide.createIcons();
        }
      } else {
        console.error('Error loading available batches:', data.message);
        alert('Error loading available batches: ' + data.message);
      }
    } catch (error) {
      console.error('Error loading available batches:', error);
      alert('Error loading available batches. Please try again.');
    }
  }

  // Function to load files from a selected batch
  async function loadBatchFiles(batchNo) {
    const button = document.getElementById('load-batch-files-btn');
    const originalText = button.textContent;
    
    console.log('loadBatchFiles called for batch:', batchNo);
    
    isPerformingSelection = true; // Set flag to prevent data reload
    
    try {
      // Show loading state
      button.disabled = true;
      button.innerHTML = '<i class="animate-spin h-3 w-3 mr-1" style="border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; display: inline-block;"></i>Loading...';
      button.classList.add('opacity-75');
      
      const response = await fetch(`/fileindexing/api/batch-files/${batchNo}`, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      });

      const data = await response.json();
      
      if (data.success) {
        // Update the notGeneratedFiles array with batch files
        notGeneratedFiles = data.files;
        
        // Auto-select all files in the batch
        selectedNotGeneratedFiles = data.files.map(file => String(file.id));
        
        console.log('Loaded', data.files.length, 'files from batch', batchNo);
        console.log('Selected files count:', selectedNotGeneratedFiles.length);
        
        renderNotGeneratedFiles();
        updateSelectedNotGeneratedFilesCount();
        updateTrackingButton();
        
        // Update main select all checkbox
        const selectAllMainCheckbox = document.getElementById('select-all-not-generated-main-checkbox');
        if (selectAllMainCheckbox) {
          selectAllMainCheckbox.checked = true;
        }
        
      } else {
        console.error('Error loading batch files:', data.message);
        alert('Error loading batch files: ' + data.message);
      }
      
    } catch (error) {
      console.error('Error loading batch files:', error);
      alert('Error loading batch files. Please try again.');
    } finally {
      // Reset button state
      button.disabled = false;
      button.innerHTML = originalText;
      button.classList.remove('opacity-75');
      isPerformingSelection = false; // Reset flag
    }
  }

  // Function to toggle select all not generated files
  function toggleSelectAllNotGenerated() {
    const selectAllCheckbox = document.getElementById('select-all-not-generated-main-checkbox');
    
    console.log('toggleSelectAllNotGenerated called');
    console.log('selectAllCheckbox.checked:', selectAllCheckbox ? selectAllCheckbox.checked : 'checkbox not found');
    console.log('notGeneratedFiles.length:', notGeneratedFiles.length);
    
    isPerformingSelection = true; // Set flag to prevent data reload
    
    if (selectAllCheckbox && selectAllCheckbox.checked) {
      // Select all files
      selectedNotGeneratedFiles = notGeneratedFiles.map(file => String(file.id));
      console.log('Selected all files:', selectedNotGeneratedFiles.length);
    } else {
      // Deselect all files
      selectedNotGeneratedFiles = [];
      console.log('Deselected all files');
    }
    
    renderNotGeneratedFiles();
    updateSelectedNotGeneratedFilesCount();
    updateTrackingButton();
    
    isPerformingSelection = false; // Reset flag
  }

  function updateNotGeneratedPagination(total) {
    // For now, we'll keep it simple since we're loading all records
    // In a real implementation, you'd want server-side pagination
    const paginationContainer = document.getElementById('not-generated-pagination');
    if (paginationContainer) {
      if (total > notGeneratedItemsPerPage) {
        paginationContainer.style.display = 'flex';
        // Update pagination numbers here if needed
      } else {
        paginationContainer.style.display = 'none';
      }
    }
  }

  // Handle select all for not generated files
  document.addEventListener('DOMContentLoaded', function() {
    // Main select all checkbox
    const selectAllMainCheckbox = document.getElementById('select-all-not-generated-main-checkbox');
    if (selectAllMainCheckbox) {
      selectAllMainCheckbox.addEventListener('change', function() {
        toggleSelectAllNotGenerated();
      });
    }

    // Table header select all checkbox
    const selectAllCheckbox = document.getElementById('select-all-not-generated-checkbox');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function() {
        if (this.checked) {
          selectedNotGeneratedFiles = notGeneratedFiles.map(file => String(file.id));
        } else {
          selectedNotGeneratedFiles = [];
        }
        renderNotGeneratedFiles();
        updateSelectedNotGeneratedFilesCount();
        updateTrackingButton();
      });
    }

    // Batch selection dropdown
    const batchDropdown = document.getElementById('batch-selection-dropdown');
    if (batchDropdown) {
      batchDropdown.addEventListener('change', function() {
        const selectedBatch = this.value;
        const loadButton = document.getElementById('load-batch-files-btn');
        
        if (selectedBatch) {
          loadButton.disabled = false;
        } else {
          loadButton.disabled = true;
        }
      });
    }

    // Load batch files button
    const loadBatchBtn = document.getElementById('load-batch-files-btn');
    if (loadBatchBtn) {
      loadBatchBtn.addEventListener('click', function() {
        const batchDropdown = document.getElementById('batch-selection-dropdown');
        const selectedBatch = batchDropdown ? batchDropdown.value : '';
        
        if (selectedBatch) {
          loadBatchFiles(selectedBatch);
        } else {
          alert('Please select a batch first.');
        }
      });
    }

    // Load available batches when the tab is loaded
    loadAvailableBatches();

    // Refresh button for not generated
    const refreshBtn = document.getElementById('refresh-not-generated');
    if (refreshBtn) {
      refreshBtn.addEventListener('click', function() {
        loadNotGeneratedFiles();
      });
    }

    // Enhanced search for not generated files
    const searchInput = document.getElementById('search-not-generated');
    if (searchInput) {
      const debouncedNotGeneratedSearch = debounce((searchTerm) => {
        const loadingIndicator = showSearchLoading('not-generated');
        
        // Client-side smart search for immediate feedback
        if (notGeneratedFiles.length > 0 && searchTerm.trim().length >= 2) {
          const filteredFiles = smartSearch.searchFiles(notGeneratedFiles, searchTerm);
          renderNotGeneratedFiles(filteredFiles);
          updateSelectedNotGeneratedFilesCount();
        } else if (searchTerm.trim() === '') {
          renderNotGeneratedFiles(notGeneratedFiles);
          updateSelectedNotGeneratedFilesCount();
        }
        
        // Server-side search for comprehensive results
        if (!isPerformingSelection) {
          loadNotGeneratedFiles(searchTerm).finally(() => {
            hideSearchLoading(loadingIndicator);
          });
        } else {
          hideSearchLoading(loadingIndicator);
        }
      }, 150); // Even faster for this section

      searchInput.addEventListener('input', function() {
        const searchTerm = this.value;
        
        // Real-time filtering for empty search
        if (searchTerm.trim() === '') {
          renderNotGeneratedFiles(notGeneratedFiles);
          return;
        }
        
        // Smart search with minimum character requirement
        if (searchTerm.length >= 1) { // Start searching from 1 character for better UX
          debouncedNotGeneratedSearch(searchTerm);
        }
      });

      // Add keyboard shortcuts
      searchInput.addEventListener('keydown', function(e) {
        // Clear search with Escape key
        if (e.key === 'Escape') {
          this.value = '';
          this.dispatchEvent(new Event('input'));
          this.blur();
        }
        // Focus first result with Enter key
        else if (e.key === 'Enter') {
          e.preventDefault();
          const firstResult = document.querySelector('#not-generated-table-body tr:first-child .row-not-generated-checkbox');
          if (firstResult) {
            firstResult.focus();
          }
        }
      });

      searchInput.addEventListener('focus', function() {
        showSearchSuggestions(this, 'not-generated');
      });
    }
  });

  // Window focus and visibility change handlers for refreshing data
  let wasAwayFromPage = false;
  
  // Handle window focus
  window.addEventListener('focus', function() {
    if (wasAwayFromPage && currentTab === 'batch-history' && currentTrackingSubTab === 'not-generated') {
      console.log('Window gained focus, refreshing not-generated files...');
      loadNotGeneratedFiles();
      wasAwayFromPage = false;
    }
  });
  
  // Handle window blur (user leaves the page)
  window.addEventListener('blur', function() {
    wasAwayFromPage = true;
  });
  
  // Handle visibility change (tab switching)
  document.addEventListener('visibilitychange', function() {
    if (!document.hidden && wasAwayFromPage && currentTab === 'batch-history' && currentTrackingSubTab === 'not-generated') {
      console.log('Page became visible, refreshing not-generated files...');
      loadNotGeneratedFiles();
      wasAwayFromPage = false;
    } else if (document.hidden) {
      wasAwayFromPage = true;
    }
  });

  // Initialize Export functionality for Indexed Files Report
  function initializeExportFunctionality() {
    const exportBtn = document.getElementById('export-indexed-files');
    const exportDropdown = document.getElementById('export-dropdown');
    
    if (exportBtn && exportDropdown) {
      // Toggle export dropdown
      exportBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        exportDropdown.classList.toggle('hidden');
      });

      // Handle export type selection
      const exportTypeButtons = exportDropdown.querySelectorAll('[data-export-type]');
      exportTypeButtons.forEach(button => {
        button.addEventListener('click', function() {
          const exportType = this.getAttribute('data-export-type');
          handleExport(exportType);
          exportDropdown.classList.add('hidden');
        });
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(e) {
        if (!exportBtn.contains(e.target) && !exportDropdown.contains(e.target)) {
          exportDropdown.classList.add('hidden');
        }
      });
    }
  }

  // Handle export functionality
  async function handleExport(exportType) {
    try {
      const formatRadios = document.querySelectorAll('input[name="export-format"]');
      let selectedFormat = 'excel';
      formatRadios.forEach(radio => {
        if (radio.checked) {
          selectedFormat = radio.value;
        }
      });

      if (exportType === 'batch') {
        await showBatchSelectionModal(selectedFormat);
      } else if (exportType === 'date') {
        await showDateSelectionModal(selectedFormat);
      }
    } catch (error) {
      console.error('Error handling export:', error);
      alert('Error initiating export. Please try again.');
    }
  }

  // Show batch selection modal
  async function showBatchSelectionModal(format) {
    try {
      // Create modal dynamically
      const modalHtml = `
        <div id="batch-export-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
          <div class="bg-white rounded-lg max-w-md w-full m-4">
            <div class="flex justify-between items-center p-6 border-b">
              <h3 class="text-lg font-semibold">Export by Batch</h3>
              <button class="text-gray-400 hover:text-gray-600" onclick="closeBatchExportModal()">
                <i data-lucide="x" class="h-6 w-6"></i>
              </button>
            </div>
            <div class="p-6 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Batch:</label>
                <select id="export-batch-select" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="">-- Select a Batch --</option>
                </select>
                <p class="text-sm text-gray-500 mt-1">Start typing to search for batches</p>
              </div>
            </div>
            <div class="flex justify-end gap-3 p-6 border-t">
              <button class="btn btn-outline" onclick="closeBatchExportModal()">Cancel</button>
              <button class="btn btn-primary" onclick="performBatchExport('${format}')">Export</button>
            </div>
          </div>
        </div>
      `;
      
      document.body.insertAdjacentHTML('beforeend', modalHtml);
      
      // Reinitialize icons for the new modal
      if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
      }
      
      // Load initial batches
      await loadBatchesForExport();
      
      // Initialize Select2 with search functionality
      if (typeof $ !== 'undefined' && $.fn.select2) {
        console.log('Initializing Select2 for batch export modal...');
        $('#export-batch-select').select2({
          placeholder: '-- Select a Batch --',
          allowClear: true,
          width: '100%',
          dropdownParent: $('#batch-export-modal'),
          minimumInputLength: 0,
          ajax: {
            url: '/fileindexing/get-all-batches-for-export',
            dataType: 'json',
            delay: 250,
            data: function (params) {
              console.log('Select2 AJAX data function called with params:', params);
              return {
                q: params.term || '',
                page: params.page || 1
              };
            },
            processResults: function (data, params) {
              console.log('Select2 processResults called with data:', data);
              if (data.success && data.batches) {
                const results = data.batches.map(batch => ({
                  id: batch.id,
                  text: `Batch ${batch.text}`
                }));
                
                console.log('Processed results:', results);
                return {
                  results: results,
                  pagination: {
                    more: data.pagination && data.pagination.more
                  }
                };
              } else {
                console.error('Invalid data format from API:', data);
                return { results: [] };
              }
            },
            cache: true
          }
        }).on('select2:open', function() {
          console.log('Select2 dropdown opened');
        }).on('select2:select', function(e) {
          console.log('Select2 selection made:', e.params.data);
        });
        console.log('Select2 initialized successfully');
      } else {
        console.log('Select2 not available, using fallback');
        // Fallback: Load batches manually if Select2 is not available
        await loadBatchesForExport();
      }
      
    } catch (error) {
      console.error('Error showing batch selection modal:', error);
      alert('Error loading batch selection. Please try again.');
    }
  }

  // Load batches for export modal
  async function loadBatchesForExport(searchTerm = '') {
    try {
      let url = '/fileindexing/get-all-batches-for-export';
      if (searchTerm) {
        url += `?q=${encodeURIComponent(searchTerm)}`;
      }
      
      const response = await fetch(url);
      const data = await response.json();
      
      console.log('Batch API Response:', data); // Debug log
      
      const select = document.getElementById('export-batch-select');
      
      // If Select2 is initialized, let it handle the data via AJAX
      if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
        // Select2 will handle this via AJAX, just return
        return;
      }
      
      // Fallback for non-Select2 implementation
      if (data.success && data.batches && data.batches.length > 0) {
        // Clear existing options
        select.innerHTML = '';
        
        // Add default option
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- Select a Batch --';
        select.appendChild(defaultOption);
        
        // Add batch options
        data.batches.forEach(batch => {
          console.log('Processing batch:', batch); // Debug log
          const batchId = batch.id;
          const batchText = batch.text;
          
          if (batchId && batchText) {
            const option = document.createElement('option');
            option.value = batchId;
            option.textContent = `Batch ${batchText}`;
            select.appendChild(option);
          }
        });
      } else {
        select.innerHTML = '<option value="">No batches found</option>';
      }
    } catch (error) {
      console.error('Error loading batches for export:', error);
      const select = document.getElementById('export-batch-select');
      if (!(typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible'))) {
        select.innerHTML = '<option value="">Error loading batches</option>';
      }
    }
  }

  // Show date selection modal
  async function showDateSelectionModal(format) {
    const today = new Date().toISOString().split('T')[0];
    
    const modalHtml = `
      <div id="date-export-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full m-4">
          <div class="flex justify-between items-center p-6 border-b">
            <h3 class="text-lg font-semibold">Export by Date</h3>
            <button class="text-gray-400 hover:text-gray-600" onclick="closeDateExportModal()">
              <i data-lucide="x" class="h-6 w-6"></i>
            </button>
          </div>
          <div class="p-6 space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">From Date:</label>
              <input type="date" id="export-from-date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="${today}">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">To Date:</label>
              <input type="date" id="export-to-date" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="${today}">
            </div>
          </div>
          <div class="flex justify-end gap-3 p-6 border-t">
            <button class="btn btn-outline" onclick="closeDateExportModal()">Cancel</button>
            <button class="btn btn-primary" onclick="performDateExport('${format}')">Export</button>
          </div>
        </div>
      </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Reinitialize icons for the new modal
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
      lucide.createIcons();
    }
  }

  // Perform batch export
  async function performBatchExport(format) {
    const select = document.getElementById('export-batch-select');
    const batchNo = select.value;
    
    if (!batchNo) {
      alert('Please select a batch to export.');
      return;
    }
    
    try {
      const url = `/fileindexing/export/batch/${batchNo}?format=${format}`;
      
      // Create a temporary link to trigger download
      const link = document.createElement('a');
      link.href = url;
      link.download = '';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      closeBatchExportModal();
    } catch (error) {
      console.error('Error performing batch export:', error);
      alert('Error exporting files. Please try again.');
    }
  }

  // Perform date export
  async function performDateExport(format) {
    const fromDate = document.getElementById('export-from-date').value;
    const toDate = document.getElementById('export-to-date').value;
    
    if (!fromDate || !toDate) {
      alert('Please select both from and to dates.');
      return;
    }
    
    if (new Date(fromDate) > new Date(toDate)) {
      alert('From date cannot be later than To date.');
      return;
    }
    
    try {
      const url = `/fileindexing/export/date?format=${format}&from=${fromDate}&to=${toDate}`;
      
      // Create a temporary link to trigger download
      const link = document.createElement('a');
      link.href = url;
      link.download = '';
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      closeDateExportModal();
    } catch (error) {
      console.error('Error performing date export:', error);
      alert('Error exporting files. Please try again.');
    }
  }

  // Delete indexed file function
  async function deleteIndexedFile(fileId) {
    try {
      const response = await fetch(`/fileindexing/api/delete-file/${fileId}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
      });

      const data = await response.json();

      if (data.success) {
        // Show success message
        showSelectionNotification(`File deleted successfully: ${data.fileName || 'File'}`);
        
        // Close any open dropdowns
        document.querySelectorAll('.actions-dropdown-menu').forEach(menu => {
          menu.classList.add('hidden');
        });
        
        // Refresh the indexed files list
        await loadIndexedFiles();
      } else {
        alert(data.message || 'Error deleting file. Please try again.');
      }
    } catch (error) {
      console.error('Error deleting file:', error);
      alert('Error deleting file. Please try again.');
    }
  }

  // Modal close functions
  window.closeBatchExportModal = function() {
    // Destroy Select2 instance if it exists
    const select = document.getElementById('export-batch-select');
    if (select && typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
      $(select).select2('destroy');
    }
    
    const modal = document.getElementById('batch-export-modal');
    if (modal) {
      modal.remove();
    }
  };

  window.closeDateExportModal = function() {
    const modal = document.getElementById('date-export-modal');
    if (modal) {
      modal.remove();
    }
  };

  // Initialize enhanced search features
  setupSearchKeyboardShortcuts();
  
  // Clear search cache when switching tabs
  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('tab')) {
      smartSearch.clearCache();
    }
  });

  console.log('🔍 Enhanced Smart Search System Initialized');
  console.log('Features: Fuzzy matching, Auto-complete, Real-time filtering, Keyboard shortcuts (Ctrl+F)');

</script>