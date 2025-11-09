 <script>
    // Initialize PDF.js worker
    if (typeof pdfjsLib !== "undefined") {
      pdfjsLib.GlobalWorkerOptions.workerSrc =
        "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
    }

    // Initialize Lucide icons
    lucide.createIcons();

    // State management
    const state = {
      activeTab: 'upload',
      uploadStatus: 'idle', // 'idle', 'uploading', 'complete', 'error'
      uploadProgress: 0,
      previewOpen: false,
      selectedFile: null,
      zoomLevel: 100,
      rotation: 0,
      currentPreviewPage: 1,
      selectedIndexedFile: null,
      showFileSelector: false,
      selectedUploadFiles: [],
      showFolderView: false,
      selectedPageInFolder: null,
      showDocumentDetails: false,
      currentDocumentIndex: null,
      documentBatches: [], // This will be populated from server
      uploadDocuments: [],
      filterPaperSize: 'All',
      searchQuery: '',
      // PDF Conversion State
      pdfConversionEnabled: true,
      hasPdfFiles: false,
      pdfConversionResults: {
        converted: 0,
        failed: 0,
        total: 0,
        failedFiles: []
      },
      currentFolderId: null,
      // Store actual file URLs for display
      filePreviews: new Map(),
  // Server upload configuration - endpoints provided via dataset
  uploadEndpoint: null,
  logEndpoint: null,
  deleteEndpointTemplate: null,
  debugEndpoint: null,
  csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      // NEW: Track selected file number for upload more functionality
      selectedFileNumberForUpload: null,
      indexedFilesSearchTerm: ''
    };

    // Document type suggestions
    const documentTypes = ["Certificate", "Deed", "Letter", "Application Form", "Map", "Survey Plan", "Receipt", "Other"];

    const indexedFilesEndpoint = "{{ route('fileindexing.api.indexed-files') }}";
  let indexedFiles = [];
  let indexedFilesPagination = { current_page: 1, per_page: 25, last_page: 1, total: 0 };
  let indexedFilesLoading = false;
  let indexedFilesError = '';

    // DOM Elements
    const elements = {
      // Tabs
      tabs: document.querySelectorAll('[role="tab"]'),
      tabContents: document.querySelectorAll('[role="tabpanel"]'),
      
      // Upload tab
      selectedFileBadge: document.querySelector('.selected-file-badge'),
      selectedFileNumber: document.getElementById('selected-file-number'),
      selectFileBtn: document.getElementById('select-file-btn'),
      changeFileText: document.getElementById('change-file-text'),
      uploadIdle: document.getElementById('upload-idle'),
      fileUpload: document.getElementById('file-upload'),
      browseFilesBtn: document.getElementById('browse-files-btn'),
      selectFileWarning: document.getElementById('select-file-warning'),
      selectedFilesContainer: document.getElementById('selected-files-container'),
      selectedFilesCount: document.getElementById('selected-files-count'),
      selectedFilesList: document.getElementById('selected-files-list'),
      clearAllBtn: document.getElementById('clear-all-btn'),
      uploadProgress: document.getElementById('upload-progress'),
      uploadingCount: document.getElementById('uploading-count'),
      uploadPercentage: document.getElementById('upload-percentage'),
      progressBar: document.getElementById('progress-bar'),
      uploadComplete: document.getElementById('upload-complete'),
      uploadError: document.getElementById('upload-error'),
      uploadErrorMessage: document.getElementById('upload-error-message'),
      startUploadBtn: document.getElementById('start-upload-btn'),
      cancelUploadBtn: document.getElementById('cancel-upload-btn'),
      uploadMoreBtn: document.getElementById('upload-more-btn'),
      viewUploadedBtn: document.getElementById('view-uploaded-btn'),
      
      // PDF Conversion elements
      convertPdfs: document.getElementById('convertPdfs'),
      pdfConversionText: document.getElementById('pdfConversionText'),
      autoConvertBadge: document.getElementById('autoConvertBadge'),
      pdfConversionModal: document.getElementById('pdf-conversion-modal'),
      pdfConversionCurrentFile: document.getElementById('pdf-conversion-current-file'),
      pdfConversionProgressPercent: document.getElementById('pdf-conversion-progress-percent'),
      pdfConversionProgressBar: document.getElementById('pdf-conversion-progress-bar'),
      
      // Uploaded files tab
      uploadsCount: document.getElementById('uploads-count'),
      pendingCount: document.getElementById('pending-count'),
      paperSizeFilter: document.getElementById('paper-size-filter'),
      fileSearch: document.getElementById('file-search'),
      toggleViewBtn: document.getElementById('toggle-view-btn'),
      noDocuments: document.getElementById('no-documents'),
      listView: document.getElementById('list-view'),
      folderView: document.getElementById('folder-view'),
      batchActions: document.getElementById('batch-actions'),
      proceedToTypingBtn: document.getElementById('proceed-to-typing-btn'),
      goToUploadBtn: document.getElementById('go-to-upload-btn'),
      
      // Preview dialog
      previewDialog: document.getElementById('preview-dialog'),
      previewTitle: document.getElementById('preview-title'),
      previewImage: document.getElementById('preview-image'),
      documentInfo: document.getElementById('document-info'),
      prevPageBtn: document.getElementById('prev-page-btn'),
      nextPageBtn: document.getElementById('next-page-btn'),
      zoomOutBtn: document.getElementById('zoom-out-btn'),
      zoomLevel: document.getElementById('zoom-level'),
      zoomInBtn: document.getElementById('zoom-in-btn'),
      rotateBtn: document.getElementById('rotate-btn'),
      proceedToTypingFromPreviewBtn: document.getElementById('proceed-to-typing-from-preview-btn'),
      
      // File selector dialog
      fileSelectorDialog: document.getElementById('file-selector-dialog'),
      fileSearchInput: document.getElementById('file-search-input'),
      indexedFilesList: document.getElementById('indexed-files-list'),
      cancelFileSelectBtn: document.getElementById('cancel-file-select-btn'),
      confirmFileSelectBtn: document.getElementById('confirm-file-select-btn'),
      
      // Document details dialog
      documentDetailsDialog: document.getElementById('document-details-dialog'),
      documentName: document.getElementById('document-name'),
      paperSizeRadios: document.querySelectorAll('input[name="paper-size"]'),
      documentType: document.getElementById('document-type'),
      documentNotes: document.getElementById('document-notes'),
      cancelDetailsBtn: document.getElementById('cancel-details-btn'),
      saveDetailsBtn: document.getElementById('save-details-btn')
    };

    const rootElement = document.querySelector('[data-scan-upload-root]');
    if (rootElement) {
      const {
        uploadEndpoint: uploadEndpointAttr,
        logEndpoint: logEndpointAttr,
        deleteEndpoint: deleteEndpointAttr,
        debugEndpoint: debugEndpointAttr,
        scanUploads: scanUploadsAttr
      } = rootElement.dataset;

      state.uploadEndpoint = uploadEndpointAttr || state.uploadEndpoint || window.location.href;
      state.logEndpoint = logEndpointAttr || state.logEndpoint;
      state.deleteEndpointTemplate = deleteEndpointAttr || state.deleteEndpointTemplate;
      state.debugEndpoint = debugEndpointAttr || state.debugEndpoint;

      if (scanUploadsAttr) {
        try {
          const initialUploads = JSON.parse(scanUploadsAttr);
          if (Array.isArray(initialUploads) && initialUploads.length && !state.documentBatches.length) {
            state.documentBatches = initialUploads
              .map(normalizeServerDocument)
              .filter(Boolean)
              .reduce((batches, doc) => {
                const fileNumber = doc.fileNumber || doc.file_number || 'UNKNOWN';
                let batch = batches.find(item => item.fileNumber === fileNumber);

                if (!batch) {
                  batch = {
                    id: `BATCH-INITIAL-${fileNumber}`,
                    fileNumber,
                    name: doc.fileTitle || doc.fileName || 'Indexed File',
                    documents: [],
                    date: doc.date || (doc.uploadedAt ? new Date(doc.uploadedAt).toLocaleDateString() : ''),
                    status: doc.status || 'Ready for page typing'
                  };
                  batches.push(batch);
                }

                batch.documents.push(doc);
                return batches;
              }, []);
          }
        } catch (error) {
          console.warn('Unable to parse initial scan uploads payload:', error);
        }
      }
    } else {
      console.warn('Scan Upload root element not found. Falling back to current URL for uploads.');
      state.uploadEndpoint = state.uploadEndpoint || window.location.href;
    }

    // Helper functions
    function formatDateFromIso(value) {
      if (!value) {
        return '';
      }

      const date = value instanceof Date ? value : new Date(value);
      if (Number.isNaN(date.getTime())) {
        return '';
      }
      return date.toLocaleDateString();
    }

    function normalizeServerDocument(rawDoc) {
      if (!rawDoc) {
        return null;
      }

      const fileNumber = rawDoc.fileNumber || rawDoc.file_number || rawDoc.fileno || null;
      const uploadedAt = rawDoc.uploadedAt || rawDoc.date || null;
      const fileName = rawDoc.fileName || rawDoc.originalName || rawDoc.original_filename || rawDoc.name || (rawDoc.id ? `Document ${rawDoc.id}` : 'Document');
      const fileSize = typeof rawDoc.fileSize === 'number' ? rawDoc.fileSize : (rawDoc.size ?? 0);
      const downloadUrl = rawDoc.downloadUrl || rawDoc.webPath || null;
      const documentPath = rawDoc.documentPath || rawDoc.serverPath || null;

      return {
        id: rawDoc.id ?? null,
        scanId: rawDoc.id ?? null,
        fileNumber,
        fileTitle: rawDoc.fileTitle || rawDoc.name || null,
        file: null,
        fileName,
        fileSize,
        paperSize: rawDoc.paperSize || rawDoc.paper_size || 'A4',
        documentType: rawDoc.documentType || rawDoc.document_type || 'Document',
        notes: rawDoc.notes || '',
        date: formatDateFromIso(uploadedAt),
        uploadedAt,
        status: rawDoc.status || 'pending',
        serverPath: documentPath || downloadUrl,
        downloadUrl,
        serverFileName: documentPath || rawDoc.serverFileName || null,
        isConvertedPdf: Boolean(rawDoc.isPdfConverted || rawDoc.is_pdf_converted),
        isPdfFolder: Boolean(rawDoc.isPdfFolder)
      };
    }

    function formatFileSize(bytes) {
      if (bytes < 1024) return bytes + " B";
      else if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + " KB";
      else return (bytes / (1024 * 1024)).toFixed(2) + " MB";
    }

    function getPaperSizeColor(size) {
      switch (size) {
        case "A4": return "bg-blue-500";
        case "A5": return "bg-green-500";
        case "A3": return "bg-purple-500";
        case "Letter": return "bg-amber-500";
        case "Legal": return "bg-rose-500";
        case "Custom": return "bg-gray-500";
        default: return "bg-gray-500";
      }
    }

    function debounce(fn, delay = 300) {
      let timeoutId;
      return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(null, args), delay);
      };
    }

    function findIndexedFileById(id) {
      if (!id) {
        return null;
      }
      return indexedFiles.find(file => String(file.id) === String(id)) || null;
    }

    function escapeHtml(value) {
      if (value === null || value === undefined) {
        return '';
      }
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // File to URL conversion
    function fileToUrl(file) {
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => resolve(e.target.result);
        reader.readAsDataURL(file);
      });
    }

    // Store file preview URLs
    async function storeFilePreview(file, key) {
      if (!state.filePreviews.has(key)) {
        const url = await fileToUrl(file);
        state.filePreviews.set(key, url);
      }
      return state.filePreviews.get(key);
    }

    // Debug function to check file paths
    function debugDeletePaths() {
      console.log('=== DELETE PATH DEBUG ===');
      state.documentBatches.forEach((batch, batchIndex) => {
        console.log(`Batch ${batchIndex}: ${batch.fileNumber}`);
        batch.documents.forEach((doc, docIndex) => {
          console.log(`  Document ${docIndex}:`, {
            fileName: doc.fileName,
            serverPath: doc.serverPath,
            hasServerPath: !!doc.serverPath
          });
        });
      });
    }

    // Test server configuration
    async function testServerConfig() {
      const debugUrl = state.debugEndpoint || (state.uploadEndpoint ? `${state.uploadEndpoint}?action=debug` : null);
      if (!debugUrl) {
        console.warn('Debug endpoint is not configured for Scan Uploads. Skipping diagnostics.');
        return;
      }

      try {
        const response = await fetch(debugUrl, {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!response.ok) {
          throw new Error(`Debug endpoint responded with ${response.status}`);
        }
        const result = await response.json();
        console.log('=== SERVER CONFIG DEBUG ===', result);
        return result;
      } catch (error) {
        console.error('Failed to test server config:', error);
      }
    }

    // PDF Conversion Functions
    function detectFileTypes(files) {
      state.hasPdfFiles = false;
      let pdfCount = 0;
      
      for (const file of files) {
        const name = file.name.toLowerCase();
        if (name.endsWith('.pdf')) {
          state.hasPdfFiles = true;
          pdfCount++;
        }
      }
      
      // Update UI based on PDF detection
      if (state.hasPdfFiles) {
        if (state.pdfConversionEnabled) {
          elements.pdfConversionText.textContent = `Found ${pdfCount} PDF file(s). PDF conversion activated.`;
          elements.autoConvertBadge.classList.remove("hidden");
        } else {
          elements.pdfConversionText.textContent = `Found ${pdfCount} PDF file(s). Enable conversion to convert to images.`;
          elements.autoConvertBadge.classList.add("hidden");
        }
      } else {
        elements.pdfConversionText.textContent = "No PDF files detected";
        elements.autoConvertBadge.classList.add("hidden");
      }
    }

    async function convertPdfInBrowser(pdfFile) {
      const arrayBuffer = await pdfFile.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
      const images = [];
      
      for (let i = 1; i <= pdf.numPages; i++) {
        const page = await pdf.getPage(i);
        const viewport = page.getViewport({ scale: 2.0 });
        
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        await page.render({
          canvasContext: ctx,
          viewport: viewport
        }).promise;
        
        const blob = await new Promise(resolve => {
          canvas.toBlob(resolve, 'image/jpeg', 0.85);
        });
        
        const imageFile = new File([blob], `page_${i}.jpg`, { 
          type: 'image/jpeg',
          lastModified: pdfFile.lastModified
        });
        
        images.push({
          file: imageFile,
          page: i,
          width: canvas.width,
          height: canvas.height,
          fileName: `page_${i}.jpg`
        });
      }
      
      return images;
    }

    async function processPdfFilesForUpload(files) {
      const processedFiles = [];
      state.pdfConversionResults = { converted: 0, failed: 0, total: 0, failedFiles: [] };
      
      // Find all PDF files
      const pdfFiles = files.filter(file => file.name.toLowerCase().endsWith('.pdf'));
      state.pdfConversionResults.total = pdfFiles.length;
      
      if (pdfFiles.length === 0) {
        // If no PDFs, just return all files as-is
        return files.map(file => ({ file, type: 'file' }));
      }
      
      // Show PDF conversion modal
      if (elements.pdfConversionModal) elements.pdfConversionModal.classList.remove("hidden");
      
      for (let i = 0; i < pdfFiles.length; i++) {
        const pdfFile = pdfFiles[i];
        
        // Update progress
        const progress = (i / pdfFiles.length) * 100;
        updatePdfConversionProgress(progress, `Converting: ${pdfFile.name}`);
        
        try {
          const images = await convertPdfInBrowser(pdfFile);
          
          // Create a folder structure for this PDF
          const pdfFolderName = pdfFile.name.replace(/\.pdf$/i, '');
          const pdfFolder = {
            id: `FOLDER-${Date.now()}-${i}`,
            name: pdfFolderName,
            type: 'folder',
            status: 'Ready for page typing',
            date: new Date().toLocaleDateString(),
            children: [],
            isPdfFolder: true,
            originalPdfFile: pdfFile
          };
          
          // Add each page as a separate file within the folder
          for (let j = 0; j < images.length; j++) {
            const image = images[j];
            
            const fileEntry = {
              id: `UPLOAD-${Date.now()}-${i}-${j}`,
              name: image.fileName,
              size: formatFileSize(image.file.size),
              type: 'image/jpeg',
              status: 'Ready for page typing',
              date: new Date().toLocaleDateString(),
              file: image.file,
              isConvertedPdf: true,
              parentFolder: pdfFolder.id,
              pageNumber: j + 1
            };
            
            pdfFolder.children.push(fileEntry);
          }
          
          processedFiles.push(pdfFolder);
          state.pdfConversionResults.converted++;
          console.log(`Converted ${pdfFile.name} to ${images.length} pages in folder ${pdfFolderName}`);
          
        } catch (error) {
          state.pdfConversionResults.failed++;
          state.pdfConversionResults.failedFiles.push(pdfFile.name);
          console.error(`Failed to convert ${pdfFile.name}:`, error);
          // Add original PDF if conversion fails
          processedFiles.push({
            file: pdfFile,
            type: 'file',
            isConvertedPdf: false
          });
        }
      }
      
      // Add all non-PDF files
      const nonPdfFiles = files.filter(file => !file.name.toLowerCase().endsWith('.pdf'));
      nonPdfFiles.forEach((file, index) => {
        processedFiles.push({
          file: file,
          type: 'file',
          isConvertedPdf: false
        });
      });
      
      // Hide conversion modal
      if (elements.pdfConversionModal) elements.pdfConversionModal.classList.add("hidden");
      
      return processedFiles;
    }

    function updatePdfConversionProgress(progress, text = '') {
      if (elements.pdfConversionProgressPercent) elements.pdfConversionProgressPercent.textContent = `${Math.round(progress)}%`;
      if (elements.pdfConversionProgressBar) elements.pdfConversionProgressBar.style.width = `${progress}%`;
      if (elements.pdfConversionCurrentFile && text) elements.pdfConversionCurrentFile.textContent = text;
    }

    // Server Upload Functions
  async function uploadFileToServer(doc, indexedFile, displayOrder = null) {
    if (!doc?.file) {
      throw new Error('Upload document is missing the file payload.');
    }

    if (!indexedFile) {
      throw new Error('Indexed file metadata is required for uploads.');
    }

    if (!state.uploadEndpoint) {
      throw new Error('Upload endpoint is not configured.');
    }

    const formData = new FormData();
    formData.append('file', doc.file);
    formData.append('file_number', indexedFile.fileNumber);
    formData.append('file_indexing_id', indexedFile.id);
    formData.append('paper_size', doc.paperSize || 'A4');
    formData.append('document_type', doc.documentType || 'Document');
  formData.append('original_filename', doc.file?.name || doc.fileName || 'document');
  formData.append('display_order', Number.isFinite(displayOrder) ? displayOrder : (doc.displayOrder ?? 0));

    if (doc.notes) {
      formData.append('notes', doc.notes);
    }

    if (doc.isConvertedPdf) {
      formData.append('is_pdf_converted', doc.isConvertedPdf ? '1' : '0');
    }

    const headers = {
      'Accept': 'application/json'
    };
    if (state.csrfToken) {
      headers['X-CSRF-TOKEN'] = state.csrfToken;
    }

    try {
      console.log('=== UPLOAD ATTEMPT ===');
      console.log('Uploading to:', state.uploadEndpoint);
      console.log('File details:', {
        name: doc.file.name,
        size: doc.file.size,
        type: doc.file.type,
        lastModified: doc.file.lastModified
      });
      console.log('File number:', indexedFile.fileNumber);

      const response = await fetch(state.uploadEndpoint, {
        method: 'POST',
        headers,
        body: formData,
      });

      console.log('=== SERVER RESPONSE ===');
      console.log('Status:', response.status, response.statusText);

      const responseText = await response.text();
      console.log('Raw response:', responseText);

      let result;
      try {
        result = JSON.parse(responseText);
      } catch (parseError) {
        console.error('Failed to parse JSON response:', parseError);
        throw new Error(`Server returned invalid JSON: ${responseText.substring(0, 200)}`);
      }

      console.log('Parsed result:', result);

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${result.message || 'Upload failed'}`);
      }

      if (!result.success) {
        throw new Error(result.message || 'Upload failed');
      }

      console.log('✅ Upload successful:', result.message);
      return result.data || null;
    } catch (error) {
      console.error('❌ Upload failed:', {
        message: error.message,
        fileName: doc.file.name,
        fileSize: doc.file.size,
        fileType: doc.file.type,
        stack: error.stack
      });
      throw error;
    }
    }

    // Helper function to create batch from uploaded files
    function createBatchFromUploadedFiles(indexedFile, uploadedFiles) {
      if (uploadedFiles.length === 0) return;
      
      const batchDocuments = [];
      
      // Group PDF pages by folder
      const pdfFolders = {};
      
      uploadedFiles.forEach(doc => {
        if (doc.isPdfFolder) {
          const folderKey = doc.folderName;
          if (!pdfFolders[folderKey]) {
            pdfFolders[folderKey] = {
              id: `FOLDER-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`,
              fileName: doc.folderName + '.pdf',
              fileSize: (doc.file?.size || doc.fileSize || 0) * doc.pageCount,
              paperSize: doc.paperSize || 'A4',
              documentType: doc.documentType || 'PDF Document',
              isPdfFolder: true,
              folderName: doc.folderName,
              pageCount: doc.pageCount,
              date: new Date().toLocaleDateString(),
              pages: [],
              downloadUrl: doc.downloadUrl || doc.serverPath || null
            };
          }
          pdfFolders[folderKey].pages.push({
            file: doc.file,
            fileName: doc.file?.name || doc.fileName || `Page ${pdfFolders[folderKey].pages.length + 1}`,
            fileSize: doc.file?.size || doc.fileSize || 0,
            paperSize: doc.paperSize || 'A4',
            documentType: doc.documentType || 'Certificate',
            pageNumber: pdfFolders[folderKey].pages.length + 1,
            serverFileName: doc.serverFileName,
            serverPath: doc.serverPath
          });
        } else {
          // Regular file
          const rawFile = doc.file || null;
          const displayName = doc.fileName || rawFile?.name || doc.originalName || 'Document';
          const resolvedSize = typeof doc.fileSize === 'number' ? doc.fileSize : rawFile?.size || 0;

          batchDocuments.push({
            file: rawFile,
            fileName: displayName,
            fileSize: resolvedSize,
            paperSize: doc.paperSize || 'A4',
            documentType: doc.documentType || 'Other',
            notes: doc.notes,
            date: new Date().toLocaleDateString(),
            isConvertedPdf: doc.isConvertedPdf || false,
            serverFileName: doc.serverFileName || doc.serverPath || null,
            serverPath: doc.serverPath || doc.downloadUrl || null,
            downloadUrl: doc.downloadUrl || doc.serverPath || null,
            scanId: doc.scanId || null
          });
        }
      });
      
      // Add PDF folders and their pages
      Object.values(pdfFolders).forEach(folder => {
        batchDocuments.push({
          id: folder.id,
          fileName: folder.fileName,
          fileSize: folder.fileSize,
          paperSize: folder.paperSize,
          documentType: folder.documentType,
          isPdfFolder: true,
          folderName: folder.folderName,
          pageCount: folder.pageCount,
          date: folder.date,
          downloadUrl: folder.downloadUrl || null
        });
        
        folder.pages.forEach(page => {
          batchDocuments.push({
            fileName: page.fileName,
            fileSize: page.fileSize,
            paperSize: page.paperSize,
            documentType: page.documentType,
            parentFolder: folder.id,
            pageNumber: page.pageNumber,
            file: page.file,
            date: new Date().toLocaleDateString(),
            serverFileName: page.serverFileName,
            serverPath: page.serverPath || page.downloadUrl || null,
            downloadUrl: page.downloadUrl || page.serverPath || null,
            scanId: page.scanId || null
          });
        });
      });
      
      const newBatch = {
        id: `BATCH-${Date.now()}`,
        fileNumber: indexedFile.fileNumber,
        name: indexedFile.name,
        documents: batchDocuments,
        date: new Date().toLocaleDateString(),
        status: 'Ready for page typing',
        uploadTime: new Date().toISOString()
      };
      
      // Add to client state (pre-existing batches from server + new one)
      state.documentBatches = [newBatch, ...state.documentBatches];
      console.log('Created new batch:', newBatch);
      console.log('Total batches:', state.documentBatches.length);
    }

    async function startUpload() {
      if (!state.selectedIndexedFile) {
        state.showFileSelector = true;
        updateUI();
        return;
      }
      
      if (state.uploadDocuments.length === 0) {
        alert('Please select files to upload');
        return;
      }
      
      state.uploadStatus = 'uploading';
      state.uploadProgress = 0;
      updateUI();
      
      // Find the selected indexed file
  const indexedFile = findIndexedFileById(state.selectedIndexedFile);
      
      if (!indexedFile) {
        alert('Selected file not found');
        state.uploadStatus = 'idle';
        updateUI();
        return;
      }
      
      // Upload files one by one
      const totalFiles = state.uploadDocuments.length;
      let successfulUploads = 0;
      let failedUploads = 0;
      const uploadedFiles = []; // Track successfully uploaded files
      
      for (let i = 0; i < totalFiles; i++) {
        const doc = state.uploadDocuments[i];
        
        // Update progress
        state.uploadProgress = Math.round((i / totalFiles) * 100);
        updateUI();
        
        try {
          const serverDoc = await uploadFileToServer(doc, indexedFile, i);
          successfulUploads++;

          const normalized = normalizeServerDocument({
            ...serverDoc,
            fileNumber: indexedFile.fileNumber,
            fileTitle: indexedFile.name
          }) || {};

          uploadedFiles.push({
            ...doc,
            fileName: normalized.fileName || doc.file?.name,
            fileSize: typeof normalized.fileSize === 'number' && normalized.fileSize > 0
              ? normalized.fileSize
              : doc.file?.size,
            paperSize: normalized.paperSize || doc.paperSize,
            documentType: normalized.documentType || doc.documentType,
            notes: normalized.notes || doc.notes,
            serverFileName: normalized.serverFileName,
            serverPath: normalized.serverPath || normalized.downloadUrl,
            downloadUrl: normalized.downloadUrl,
            scanId: normalized.scanId,
            status: normalized.status || 'pending',
            uploadedAt: normalized.uploadedAt,
            fileNumber: indexedFile.fileNumber,
            isConvertedPdf: doc.isConvertedPdf || normalized.isConvertedPdf
          });

          console.log('Successfully uploaded:', {
            localName: doc.file.name,
            serverDocument: normalized
          });
        } catch (error) {
          console.error(`Failed to upload ${doc.file.name}:`, error);
          failedUploads++;
        }
      }
      
      // Complete upload
      state.uploadProgress = 100;
      
      if (failedUploads > 0) {
        if (successfulUploads > 0) {
          // Some files uploaded successfully - create batch with successful ones
          createBatchFromUploadedFiles(indexedFile, uploadedFiles);
          state.uploadStatus = 'complete';
          elements.uploadErrorMessage.textContent = `${successfulUploads} files uploaded successfully, ${failedUploads} files failed. Documents tab has been updated.`;
          elements.uploadError.classList.remove('hidden');
          console.log(`Partial success: ${successfulUploads} uploaded, ${failedUploads} failed`);
        } else {
          // All files failed
          state.uploadStatus = 'error';
          elements.uploadErrorMessage.textContent = `All ${failedUploads} files failed to upload. Check console for details.`;
          elements.uploadError.classList.remove('hidden');
          console.log('All files failed to upload');
        }
      } else {
        // All files uploaded successfully
        createBatchFromUploadedFiles(indexedFile, uploadedFiles);
        state.uploadStatus = 'complete';
        elements.uploadError.classList.add('hidden');
        
        // Show conversion results if applicable
        if (state.pdfConversionEnabled && state.hasPdfFiles && state.pdfConversionResults.converted > 0) {
          console.log(`PDF conversion complete. Converted ${state.pdfConversionResults.converted} PDF file(s) to images.`);
        }
        console.log('All files uploaded successfully');
      }
      
      updateUI();
    }
    
    async function loadIndexedFiles(searchTerm = '') {
      if (!indexedFilesEndpoint) {
        console.warn('Indexed files endpoint is not configured.');
        return;
      }

      indexedFilesLoading = true;
      indexedFilesError = '';
      state.indexedFilesSearchTerm = searchTerm;

      if (elements.indexedFilesList) {
        elements.indexedFilesList.innerHTML = `
          <div class="p-4 text-sm text-muted-foreground">Loading indexed files...</div>
        `;
      }

      try {
        const params = new URLSearchParams({
          per_page: indexedFilesPagination.per_page ?? 25,
        });

        if (searchTerm) {
          params.append('search', searchTerm);
        }

        const response = await fetch(`${indexedFilesEndpoint}?${params.toString()}`, {
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`Failed to load indexed files: ${response.status} ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
          indexedFiles = Array.isArray(data.indexed_files) ? data.indexed_files : [];

          if (data.pagination) {
            indexedFilesPagination = {
              current_page: data.pagination.current_page ?? 1,
              per_page: data.pagination.per_page ?? (indexedFilesPagination.per_page ?? 25),
              last_page: data.pagination.last_page ?? 1,
              total: data.pagination.total ?? indexedFiles.length,
            };
          } else {
            indexedFilesPagination = {
              current_page: 1,
              per_page: indexedFilesPagination.per_page ?? 25,
              last_page: 1,
              total: indexedFiles.length,
            };
          }

          if (state.selectedIndexedFile) {
            const stillExists = indexedFiles.some(file => String(file.id) === String(state.selectedIndexedFile));
            if (!stillExists) {
              state.selectedIndexedFile = null;
              state.selectedFileNumberForUpload = null;
            }
          }
        } else {
          indexedFiles = [];
          indexedFilesError = data.message || 'No indexed files found.';
        }
      } catch (error) {
        console.error('Failed to load indexed files:', error);
        indexedFiles = [];
        indexedFilesError = error.message || 'Unable to load indexed files.';
      } finally {
        indexedFilesLoading = false;
        renderIndexedFiles();
        updateUI();
      }
    }

    /**
     * Fetches the upload log from the server and populates the state.
     */
    async function loadServerData() {
      if (!state.logEndpoint) {
        console.warn('Scan Uploads log endpoint is not configured. Skipping log fetch.');
        return;
      }

      try {
        const response = await fetch(state.logEndpoint, {
          headers: {
            'Accept': 'application/json'
          }
        });
        if (!response.ok) {
          throw new Error(`Failed to fetch log: ${response.statusText}`);
        }
        
        const result = await response.json();
        if (result.success && result.data) {
          const rawEntries = Array.isArray(result.data)
            ? result.data
            : Object.entries(result.data).map(([fileNumber, documents]) => ({
                fileNumber,
                documents
              }));

          const newBatches = rawEntries.map((serverBatch, index) => {
            const fileNumber = serverBatch.fileNumber || serverBatch.file_number || `UNKNOWN-${index}`;
            const indexedFile = indexedFiles.find(f => f.fileNumber === fileNumber);
            const documents = Array.isArray(serverBatch.documents)
              ? serverBatch.documents.map(normalizeServerDocument).filter(Boolean)
              : [];

            return {
              id: `BATCH-SERVER-${fileNumber}-${index}`,
              fileNumber,
              name: indexedFile ? indexedFile.name : (serverBatch.name || documents[0]?.fileTitle || 'Indexed File'),
              documents,
              date: documents[0]?.date || formatDateFromIso(serverBatch.date),
              status: 'Ready for page typing'
            };
          });

          state.documentBatches = newBatches;
          console.log('Loaded server data:', state.documentBatches);

          // Debug the loaded paths
          debugDeletePaths();
        } else {
          state.documentBatches = [];
        }
      } catch (error) {
        console.error('Failed to load server data:', error);
        // Don't show an alert, just log it.
      }
    }
    
    /**
     * Deletes a single document from the server and updates the state.
     * @param {string} serverPath The web-accessible path to the file (e.g., /storage/FILE-123/unique_name.jpg)
     */
    async function deleteDocument({ scanId, serverPath }) {
      if (!confirm('Are you sure you want to delete this document?\nThis action cannot be undone.')) {
        return;
      }

      try {
        console.log('=== DELETE ATTEMPT ===', { scanId, serverPath });

        let response;
        let result = null;

        if (scanId && state.deleteEndpointTemplate) {
          const deleteUrl = state.deleteEndpointTemplate.replace('ID', scanId);
          response = await fetch(deleteUrl, {
            method: 'DELETE',
            headers: {
              'Accept': 'application/json',
              ...(state.csrfToken ? { 'X-CSRF-TOKEN': state.csrfToken } : {})
            }
          });

          const text = await response.text();
          try {
            result = text ? JSON.parse(text) : {};
          } catch (parseError) {
            console.warn('Delete endpoint returned non-JSON payload:', text);
            result = {};
          }
        } else if (serverPath && state.uploadEndpoint) {
          response = await fetch(state.uploadEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              ...(state.csrfToken ? { 'X-CSRF-TOKEN': state.csrfToken } : {})
            },
            body: JSON.stringify({
              action: 'delete',
              path: serverPath
            })
          });

          try {
            result = await response.json();
          } catch (parseError) {
            console.warn('Legacy delete endpoint returned non-JSON payload.');
            result = {};
          }
        } else {
          throw new Error('No valid endpoint configured for document deletion.');
        }

        if (!response.ok || (result && result.success === false)) {
          throw new Error(result?.message || `Failed to delete document (HTTP ${response.status}).`);
        }

        console.log('✅ Successfully deleted:', scanId || serverPath);

        // Remove the file from the client state
        removeDocumentFromState({ scanId, serverPath });

      } catch (error) {
        console.error('❌ Delete failed:', error);
        alert(`Error: ${error.message}`);
      }
    }

    /**
     * Removes a document from the client state by server path
     */
    function removeDocumentFromState({ scanId, serverPath }) {
      const normalizedScanId = scanId ? String(scanId) : null;
      const normalizedPath = serverPath || null;

      state.documentBatches = state.documentBatches.map(batch => {
        return {
          ...batch,
          documents: batch.documents.filter(doc => {
            if (normalizedScanId) {
              const docId = doc.scanId || doc.id || null;
              if (docId && String(docId) === normalizedScanId) {
                return false;
              }
            }

            if (normalizedPath) {
              const matchesServerPath = doc.serverPath === normalizedPath || doc.downloadUrl === normalizedPath;
              if (matchesServerPath) {
                return false;
              }
            }

            return true;
          })
        };
      }).filter(batch => batch.documents.length > 0); // Remove empty batches
      
      updateUI();
    }

    // NEW: Handle Upload More functionality for specific file numbers
    function handleUploadMoreToFolder(fileNumber) {
      console.log(`Uploading more documents to file number: ${fileNumber}`);
      
      // Find the indexed file by file number
      const selectedFile = indexedFiles.find(f => f.fileNumber === fileNumber);
      
      if (selectedFile) {
        // Set the selected file for upload
        state.selectedIndexedFile = String(selectedFile.id);
        state.selectedFileNumberForUpload = selectedFile.fileNumber;
        
        // Switch to upload tab
        switchTab('upload');
        
        // Update UI to show the selected file
        updateUI();
        
        // Show success notification
        showNotification(`Ready to upload more documents to <strong>${fileNumber}</strong>`);
      } else {
        console.error(`File number ${fileNumber} not found in indexed files`);
        alert(`Error: File number ${fileNumber} not found.`);
      }
    }

    // NEW: Show toast notification
    function showNotification(message) {
      const notification = document.createElement('div');
      notification.className = 'toast-notification';
      notification.innerHTML = `
        <div class="flex items-center gap-2">
          <i data-lucide="check-circle" class="h-5 w-5"></i>
          <span>${message}</span>
        </div>
      `;
      document.body.appendChild(notification);
      lucide.createIcons();
      
      // Auto-remove after 5 seconds
      setTimeout(() => {
        notification.remove();
      }, 5000);
    }

    // Image Preview Functions
    function createFileIcon(fileType) {
      const isImage = fileType.startsWith('image/');
      const isPDF = fileType === 'application/pdf' || fileType.endsWith('.pdf');
      
      if (isImage) {
        return `
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#E5E5E5"/>
            <path d="M22 12H10V20H22V12Z" fill="#9CA3AF"/>
            <circle cx="13" cy="14" r="2" fill="#6B7280"/>
            <path d="M22 16L18 12L10 20L14 16L16 18L22 12V16Z" fill="#6B7280"/>
          </svg>
        `;
      } else if (isPDF) {
        return `
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#E5E5E5"/>
            <path d="M24 28H8C4.68629 28 2 27.3137 2 24V8C2 4.68629 4.68629 2 8 2H24C27.3137 2 30 4.68629 30 8V24C30 27.3137 27.3137 28 24 28Z" fill="#E5E5E5"/>
            <path d="M18 10H12V16H18V10Z" fill="#999999"/>
            <path d="M20 22H22C22.5523 22 23 21.5523 23 21V14C23 13.4477 22.5523 13 22 13H20C19.4477 13 19 13.4477 19 14V21C19 21.5523 19.4477 22 20 22Z" fill="#999999"/>
            <path d="M12 22H14C14.5523 22 15 21.5523 15 21V14C15 13.4477 14.5523 13 14 13H12C11.4477 13 11 13.4477 11 14V21C11 21.5523 11.4477 22 12 22Z" fill="#999999"/>
            <path d="M8 22H10C10.5523 22 11 21.5523 11 21V14C11 13.4477 10.5523 13 10 13H8C7.44772 13 7 13.4477 7 14V21C7 21.5523 7.44772 22 8 22Z" fill="#999999"/>
          </svg>
        `;
      } else {
        return `
          <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect width="32" height="32" rx="4" fill="#E5E5E5"/>
            <path d="M19 12H9V14H19V12Z" fill="#9CA3AF"/>
            <path d="M19 16H9V18H19V16Z" fill="#9CA3AF"/>
            <path d="M13 20H9V22H13V20Z" fill="#9CA3AF"/>
            <path d="M23 12V20H21V14H17V12H23Z" fill="#6B7280"/>
          </svg>
        `;
      }
    }

    async function renderSelectedFiles() {
      elements.selectedFilesList.innerHTML = '';
      
      for (const [index, doc] of state.uploadDocuments.entries()) {
        const fileItem = document.createElement('div');
        fileItem.className = 'flex items-center justify-between p-3 border-b last:border-b-0';
        
        // Generate preview URL for image files
        const isImage = doc.file.type.startsWith('image/');
        const isPDF = doc.file.name.toLowerCase().endsWith('.pdf');
        
        let previewUrl = createFileIcon(doc.file.type);
        let thumbnailContent = previewUrl;
        
        if (isImage) {
          try {
            const imageUrl = await fileToUrl(doc.file);
            thumbnailContent = `
              <div class="file-preview-container">
                <div class="file-preview-thumbnail">
                  <img 
                    src="${imageUrl}" 
                    alt="${doc.file.name}"
                    class="w-full h-full object-contain"
                    onerror="this.style.display='none'; this.nextElementSibling.style.display='block'"
                  >
                  <div style="display: none;">${previewUrl}</div>
                </div>
                <div class="file-preview-overlay">
                  <button class="bg-white bg-opacity-90 rounded-full p-2 shadow-lg preview-file-btn" data-index="${index}">
                    <i data-lucide="zoom-in" class="h-4 w-4"></i>
                  </button>
                </div>
              </div>
            `;
          } catch (error) {
            console.error('Failed to load image preview:', error);
            thumbnailContent = previewUrl;
          }
        } else {
          thumbnailContent = `
            <div class="file-preview-thumbnail">
              ${previewUrl}
            </div>
          `;
        }
        
        fileItem.innerHTML = `
          <div class="flex items-center gap-3 flex-1">
            <!-- Preview Thumbnail -->
            ${thumbnailContent}
            
            <!-- File Info -->
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm truncate" title="${doc.file.name}">${doc.file.name}</p>
              <div class="flex items-center gap-2 mt-1 flex-wrap">
                <span class="badge ${getPaperSizeColor(doc.paperSize)} text-white text-xs">${doc.paperSize}</span>
                <span class="badge badge-outline text-xs">${doc.documentType}</span>
                <span class="text-xs text-muted-foreground">${formatFileSize(doc.file.size)}</span>
                ${isPDF ? '<span class="badge bg-red-500 text-white text-xs">PDF</span>' : ''}
                ${doc.isConvertedPdf ? '<span class="badge bg-green-500 text-white text-xs">PDF Converted</span>' : ''}
              </div>
              <p class="text-xs text-gray-500 mt-1">
                ${isImage ? 'Image' : isPDF ? 'PDF Document' : 'File'} • ${doc.file.type || 'Unknown type'}
              </p>
            </div>
          </div>
          
          <!-- Actions -->
          <div class="flex items-center gap-2 ml-4">
            ${isImage ? `
              <button class="btn btn-outline btn-sm preview-file" data-index="${index}" title="Preview">
                <i data-lucide="eye" class="h-4 w-4"></i>
              </button>
            ` : ''}
            <button class="btn btn-outline btn-sm edit-details" data-index="${index}" title="Edit Details">
              <i data-lucide="edit" class="h-4 w-4"></i>
            </button>
            <button class="btn btn-ghost btn-sm text-red-500 remove-file" data-index="${index}" title="Remove">
              <i data-lucide="trash-2" class="h-4 w-4"></i>
            </button>
          </div>
        `;
        
        elements.selectedFilesList.appendChild(fileItem);
      }
      
      // Initialize icons for the new elements
      lucide.createIcons();
      
      // Add event listeners
      document.querySelectorAll('.edit-details').forEach(btn => {
        btn.addEventListener('click', () => {
          const index = parseInt(btn.getAttribute('data-index'));
          openDocumentDetails(index);
        });
      });
      
      document.querySelectorAll('.remove-file').forEach(btn => {
        btn.addEventListener('click', () => {
          const index = parseInt(btn.getAttribute('data-index'));
          removeFile(index);
        });
      });
      
      document.querySelectorAll('.preview-file').forEach(btn => {
        btn.addEventListener('click', () => {
          const index = parseInt(btn.getAttribute('data-index'));
          previewSelectedFile(index);
        });
      });
      
      document.querySelectorAll('.preview-file-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const index = parseInt(btn.getAttribute('data-index'));
          previewSelectedFile(index);
        });
      });
    }

    // Function to preview selected file in modal
    function previewSelectedFile(index) {
      const doc = state.uploadDocuments[index];
      if (!doc || !doc.file.type.startsWith('image/')) return;
      
      // Create preview modal
      const previewModal = document.createElement('div');
      previewModal.className = 'full-preview-modal';
      previewModal.innerHTML = `
        <div class="full-preview-content">
          <div class="flex justify-between items-center p-4 border-b">
            <h3 class="text-lg font-semibold">${doc.file.name}</h3>
            <button class="btn btn-ghost btn-sm close-preview">
              <i data-lucide="x" class="h-5 w-5"></i>
            </button>
          </div>
          <div class="p-4 flex items-center justify-center max-h-[70vh] overflow-auto">
            <img 
              src="${URL.createObjectURL(doc.file)}" 
              alt="${doc.file.name}"
              class="full-preview-image"
            >
          </div>
          <div class="p-4 border-t bg-gray-50">
            <div class="flex justify-between items-center text-sm">
              <div class="flex gap-4">
                <span class="badge ${getPaperSizeColor(doc.paperSize)} text-white">${doc.paperSize}</span>
                <span class="badge badge-outline">${doc.documentType}</span>
                <span class="text-gray-600">${formatFileSize(doc.file.size)}</span>
              </div>
              <button class="btn btn-primary btn-sm close-preview-btn">
                Close
              </button>
            </div>
          </div>
        </div>
      `;
      
      document.body.appendChild(previewModal);
      
      // Initialize icons
      lucide.createIcons();
      
      // Close on backdrop click
      previewModal.addEventListener('click', (e) => {
        if (e.target === previewModal) {
          previewModal.remove();
        }
      });
      
      // Close on button click
      previewModal.querySelector('.close-preview').addEventListener('click', () => {
        previewModal.remove();
      });
      
      previewModal.querySelector('.close-preview-btn').addEventListener('click', () => {
        previewModal.remove();
      });
      
      // Close on escape key
      const handleEscape = (e) => {
        if (e.key === 'Escape') {
          previewModal.remove();
          document.removeEventListener('keydown', handleEscape);
        }
      };
      document.addEventListener('keydown', handleEscape);
    }

    // Function to pre-generate image previews
    async function pregenerateImagePreviews() {
      for (const doc of state.uploadDocuments) {
        if (doc.file.type.startsWith('image/')) {
          const previewKey = `selected-${doc.file.name}-${doc.file.lastModified}`;
          await storeFilePreview(doc.file, previewKey);
        }
      }
    }

    // UI update functions
    function updateUI() {
      // Update tabs
      elements.tabs.forEach(tab => {
        const tabId = tab.getAttribute('data-tab');
        tab.setAttribute('aria-selected', tabId === state.activeTab);
      });
      
      elements.tabContents.forEach(content => {
        const contentId = content.getAttribute('data-tab-content');
        content.setAttribute('aria-hidden', contentId !== state.activeTab);
      });

      // Update selected file badge
      if (state.selectedIndexedFile) {
        const selectedFile = findIndexedFileById(state.selectedIndexedFile);
        elements.selectedFileBadge.classList.remove('hidden');
        elements.selectedFileNumber.textContent = selectedFile ? selectedFile.fileNumber : 'No file selected';
        elements.changeFileText.textContent = 'Change File';
        elements.browseFilesBtn.disabled = false;
        elements.selectFileWarning.classList.add('hidden');
      } else {
        elements.selectedFileBadge.classList.add('hidden');
        elements.changeFileText.textContent = 'Select File';
        elements.browseFilesBtn.disabled = true;
        elements.selectFileWarning.classList.remove('hidden');
      }

      // Update upload status
      elements.uploadIdle.classList.toggle('hidden', state.uploadStatus !== 'idle');
      elements.uploadProgress.classList.toggle('hidden', state.uploadStatus !== 'uploading');
      elements.uploadComplete.classList.toggle('hidden', state.uploadStatus !== 'complete');
      elements.uploadError.classList.toggle('hidden', state.uploadStatus !== 'error');
      
      // Update buttons based on upload status
      elements.startUploadBtn.classList.toggle('hidden', 
        !(state.uploadStatus === 'idle' && state.uploadDocuments.length > 0));
      elements.cancelUploadBtn.classList.toggle('hidden', state.uploadStatus !== 'uploading');
      elements.uploadMoreBtn.classList.toggle('hidden', state.uploadStatus !== 'complete' && state.uploadStatus !== 'error');
      elements.viewUploadedBtn.classList.toggle('hidden', state.uploadStatus !== 'complete' && state.uploadStatus !== 'error');

      // Update selected files
      elements.selectedFilesContainer.classList.toggle('hidden', state.uploadDocuments.length === 0);
      elements.selectedFilesCount.textContent = state.uploadDocuments.length;
      
      if (state.uploadDocuments.length > 0) {
        renderSelectedFiles();
      }

      // Update upload progress
      if (state.uploadStatus === 'uploading') {
        elements.uploadingCount.textContent = state.uploadDocuments.length;
        elements.uploadPercentage.textContent = `${state.uploadProgress}%`;
        elements.progressBar.style.width = `${state.uploadProgress}%`;
      }

      // Update uploaded files tab
      // Get today's date in MM/DD/YYYY format
      const today = new Date().toLocaleDateString();
      const todaysUploads = state.documentBatches.filter(batch => batch.date === today).length;
      
      elements.uploadsCount.textContent = todaysUploads;
      elements.pendingCount.textContent = state.documentBatches.reduce(
        (total, batch) => total + batch.documents.length, 0
      );

      elements.noDocuments.classList.toggle('hidden', state.documentBatches.length > 0);
      elements.batchActions.classList.toggle('hidden', state.documentBatches.length === 0);
      
      // Update view toggle
      elements.toggleViewBtn.textContent = state.showFolderView ? 'List View' : 'Folder View';
      elements.listView.classList.toggle('hidden', state.showFolderView);
      elements.folderView.classList.toggle('hidden', !state.showFolderView);

      if (state.documentBatches.length > 0) {
        renderBatches();
      }

      // Update dialogs
      elements.previewDialog.classList.toggle('hidden', !state.previewOpen);
      elements.fileSelectorDialog.classList.toggle('hidden', !state.showFileSelector);
      elements.documentDetailsDialog.classList.toggle('hidden', !state.showDocumentDetails);

      // Update preview
      if (state.previewOpen && state.selectedFile) {
        updatePreview();
      }

      // Update file selector
      if (state.showFileSelector) {
        renderIndexedFiles();
      }

      // Update document details
      if (state.showDocumentDetails && state.currentDocumentIndex !== null) {
        populateDocumentDetailsForm();
      }
    }

    function renderBatches() {
      const filteredBatches = getFilteredBatches();
      
      if (state.showFolderView) {
        renderFolderView(filteredBatches);
      } else {
        renderListView(filteredBatches);
      }
    }

    function renderListView(batches) {
      elements.listView.innerHTML = '';
      
      batches.forEach(batch => {
        // Check if this batch contains PDF folders
        const hasPdfFolders = batch.documents.some(doc => doc.isPdfFolder);
        
        if (hasPdfFolders) {
          // Render PDF folders separately
          batch.documents.forEach(doc => {
            if (doc.isPdfFolder) {
              const folderItem = document.createElement('div');
              folderItem.className = 'flex items-center justify-between p-4 border-b';
              
              folderItem.innerHTML = `
                <div class="flex items-center gap-3">
                  <i data-lucide="folder" class="h-8 w-8 text-yellow-500"></i>
                  <div>
                    <p class="font-medium text-blue-600">${batch.fileNumber} - ${doc.folderName}</p>
                    <p class="text-sm text-gray-600">PDF Document (${doc.pageCount} pages)</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="badge bg-green-500 text-white text-xs">PDF Converted</span>
                      <span class="text-xs text-muted-foreground">${doc.date}</span>
                      <span class="badge badge-outline text-xs">
                        ${doc.pageCount} ${doc.pageCount === 1 ? 'page' : 'pages'}
                      </span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <!-- ADD UPLOAD MORE BUTTON -->
                  <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                    <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                    Upload More
                  </button>
                  <button class="btn btn-outline btn-sm preview-batch" data-id="${batch.id}" data-folder="${doc.id}">
                    <i data-lucide="zoom-in" class="h-4 w-4 mr-1"></i>
                    Preview
                  </button>
                  <button class="btn btn-outline btn-sm start-typing" data-id="${batch.id}" data-folder="${doc.id}">
                    Start Page Typing
                  </button>
                </div>
              `;
              
              elements.listView.appendChild(folderItem);
            } else if (!doc.parentFolder) {
              // Render individual files that are not part of PDF folders
              const fileItem = document.createElement('div');
              fileItem.className = 'flex items-center justify-between p-4 border-b';
              
              fileItem.innerHTML = `
                <div class="flex items-center gap-3">
                  <i data-lucide="file" class="h-8 w-8 text-blue-500"></i>
                  <div>
                    <p class="font-medium text-blue-600">${batch.fileNumber}</p>
                    <p class="text-sm text-gray-600">${doc.fileName}</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="badge ${getPaperSizeColor(doc.paperSize)} text-white text-xs">${doc.paperSize}</span>
                      <span class="badge badge-outline text-xs">${doc.documentType}</span>
                      <span class="text-xs text-muted-foreground">${formatFileSize(doc.fileSize)}</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <!-- ADD UPLOAD MORE BUTTON -->
                  <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                    <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                    Upload More
                  </button>
                  <button class="btn btn-outline btn-sm preview-batch" data-id="${batch.id}" data-doc-index="${batch.documents.indexOf(doc)}">
                    <i data-lucide="zoom-in" class="h-4 w-4 mr-1"></i>
                    Preview
                  </button>
                  <button class="btn btn-outline btn-sm start-typing" data-id="${batch.id}" data-doc-index="${batch.documents.indexOf(doc)}">
                    Start Page Typing
                  </button>
            <button class="btn btn-ghost btn-sm text-red-500 delete-document" 
              data-server-path="${doc.serverPath || ''}"
              data-scan-id="${doc.scanId || ''}">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                  </button>
                </div>
              `;
              
              elements.listView.appendChild(fileItem);
            }
          });
        } else {
          // Original rendering for non-PDF batches
          const batchItem = document.createElement('div');
          batchItem.className = 'flex items-center justify-between p-4';
          
          // Get unique paper sizes
          const uniquePaperSizes = Array.from(new Set(batch.documents.map(d => d.paperSize)));
          
          batchItem.innerHTML = `
            <div class="flex items-center gap-3">
              <i data-lucide="file-text" class="h-8 w-8 text-blue-500"></i>
              <div>
                <p class="font-medium text-blue-600">${batch.fileNumber}</p>
                <p class="text-sm text-gray-600">${batch.name}</p>
                <div class="flex items-center gap-2 mt-1">
                  <span class="badge badge-outline text-xs">
                    ${batch.documents.length} ${batch.documents.length === 1 ? 'document' : 'documents'}
                  </span>
                  <span class="text-xs text-muted-foreground">${batch.date}</span>
                  <div class="flex gap-1">
                    ${uniquePaperSizes.map(size => 
                      `<span class="badge ${getPaperSizeColor(size)} text-white text-xs">${size}</span>`
                    ).join('')}
                  </div>
                </div>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <!-- ADD UPLOAD MORE BUTTON -->
              <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                Upload More
              </button>
              <button class="btn btn-outline btn-sm preview-batch" data-id="${batch.id}">
                <i data-lucide="zoom-in" class="h-4 w-4 mr-1"></i>
                Preview
              </button>
              <button class="btn btn-outline btn-sm start-typing" data-id="${batch.id}">
                Start Page Typing
              </button>
            </div>
          `;
          
          elements.listView.appendChild(batchItem);
        }
      });
      
      // Initialize icons for the new elements
      lucide.createIcons();
      
      // Add event listeners
      document.querySelectorAll('.preview-batch').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const folderId = btn.getAttribute('data-folder');
          const docIndex = btn.getAttribute('data-doc-index');
          openPreview(id, docIndex ? parseInt(docIndex) : 0, folderId);
        });
      });
      
      document.querySelectorAll('.start-typing').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const folderId = btn.getAttribute('data-folder');
          window.location.href = `/file-digital-registry/page-typing?fileId=${id}${folderId ? `&folder=${folderId}` : ''}`;
        });
      });
      
      document.querySelectorAll('.delete-document').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const scanId = btn.getAttribute('data-scan-id');
          const serverPath = btn.getAttribute('data-server-path');
          if (!scanId && !serverPath) {
            alert('Missing identifiers for deletion. Please check console for details.');
            console.error('Delete button missing scan id and server path');
            return;
          }

          deleteDocument({
            scanId: scanId || null,
            serverPath: serverPath || null
          });
        });
      });

      // NEW: Add event listeners for upload more buttons
      document.querySelectorAll('.upload-more-to-folder').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const fileNumber = btn.getAttribute('data-file-number');
          handleUploadMoreToFolder(fileNumber);
        });
      });
    }

    async function renderFolderView(batches) {
      elements.folderView.innerHTML = '';
      
      for (const batch of batches) {
        const hasPdfFolders = batch.documents.some(doc => doc.isPdfFolder);
        
        if (hasPdfFolders) {
          // Render PDF folders in folder view
          for (const doc of batch.documents) {
            if (doc.isPdfFolder) {
              const folderItem = document.createElement('div');
              folderItem.className = 'border rounded-md overflow-hidden mb-4';
              
              folderItem.innerHTML = `
                <div class="p-4 bg-yellow-50 border-b">
                  <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                      <i data-lucide="folder" class="h-6 w-6 text-yellow-500"></i>
                      <div>
                        <p class="font-medium text-blue-600">${batch.fileNumber} - ${doc.folderName}</p>
                        <p class="text-sm">PDF Document (${doc.pageCount} pages)</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="badge bg-green-500 text-white text-xs">PDF Converted</span>
                      <!-- ADD UPLOAD MORE BUTTON -->
                      <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                        <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                        Upload More
                      </button>
                      <button class="btn btn-outline btn-sm start-typing" data-id="${batch.id}" data-folder="${doc.id}">
                        Start Page Typing
                      </button>
                    </div>
                  </div>
                </div>
                <div class="p-4">
                  <h4 class="text-sm font-medium mb-3">PDF Pages (${doc.pageCount} pages)</h4>
                  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 documents-grid" data-batch-id="${batch.id}" data-folder-id="${doc.id}">
                    <!-- PDF pages will be added here -->
                  </div>
                </div>
              `;
              
              elements.folderView.appendChild(folderItem);
              
              // Add PDF pages to the grid
              const documentsGrid = folderItem.querySelector('.documents-grid');
              
              // Get all pages for this PDF folder
              const pdfPages = batch.documents.filter(page => page.parentFolder === doc.id);
              
              for (const [index, page] of pdfPages.entries()) {
                const pageItem = document.createElement('div');
                pageItem.className = 'border rounded-md overflow-hidden cursor-pointer hover:border-blue-500 transition-colors document-item';
                pageItem.setAttribute('data-batch-id', batch.id);
                pageItem.setAttribute('data-folder-id', doc.id);
                pageItem.setAttribute('data-page-index', index);
                
                // Get the actual image URL from stored files
                const previewKey = `${batch.id}-${doc.id}-${index}`;
                let imageUrl = '/placeholder.svg';
                
                if (page.file && page.file instanceof File) {
                  imageUrl = await storeFilePreview(page.file, previewKey);
                } else if (page.serverPath) {
                  imageUrl = page.serverPath;
                } else if (page.downloadUrl) {
                  imageUrl = page.downloadUrl;
                }
                
                pageItem.innerHTML = `
                  <div class="h-40 bg-muted flex items-center justify-center">
                    <img
                      src="${imageUrl}"
                      alt="Page ${index + 1}"
                      class="document-image max-h-full max-w-full object-contain"
                      onerror="this.src='/placeholder.svg'"
                    >
                  </div>
                  <div class="p-2 bg-gray-50 border-t">
                    <div class="flex justify-between items-center">
                      <span class="text-sm font-medium">Page ${index + 1}</span>
                      <span class="badge ${getPaperSizeColor(page.paperSize)} text-white text-xs">${page.paperSize}</span>
                    </div>
                    <div class="mt-1">
                      <span class="badge badge-outline text-xs w-full justify-center">${page.documentType}</span>
                    </div>
                    <div class="mt-1 flex justify-between items-center">
                      <span class="badge bg-blue-500 text-white text-xs overflow-hidden text-ellipsis">
                        ${batch.fileNumber}-P${(index + 1).toString().padStart(2, '0')}
                      </span>
                      <button class="btn btn-ghost btn-sm text-red-500 delete-document" data-server-path="${page.serverPath || ''}" data-scan-id="${page.scanId || ''}" style="padding: 2px; height: auto;">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                      </button>
                    </div>
                  </div>
                `;
                
                documentsGrid.appendChild(pageItem);
              }
            }
          }
          
          // Also render individual files that are not part of PDF folders
          const individualFiles = batch.documents.filter(doc => !doc.isPdfFolder && !doc.parentFolder);
          if (individualFiles.length > 0) {
            const filesItem = document.createElement('div');
            filesItem.className = 'border rounded-md overflow-hidden mb-4';
            
            filesItem.innerHTML = `
              <div class="p-4 bg-blue-50 border-b">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <i data-lucide="file-text" class="h-6 w-6 text-blue-500"></i>
                    <div>
                      <p class="font-medium text-blue-600">${batch.fileNumber}</p>
                      <p class="text-sm">Individual Files</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="badge badge-outline text-xs">
                      ${individualFiles.length} ${individualFiles.length === 1 ? 'file' : 'files'}
                    </span>
                    <!-- ADD UPLOAD MORE BUTTON -->
                    <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                      <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                      Upload More
                    </button>
                  </div>
                </div>
              </div>
              <div class="p-4">
                <h4 class="text-sm font-medium mb-3">Files</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 documents-grid" data-batch-id="${batch.id}">
                  <!-- Individual files will be added here -->
                </div>
              </div>
            `;
            
            elements.folderView.appendChild(filesItem);
            
            // Add individual files to the grid
            const documentsGrid = filesItem.querySelector('.documents-grid');
            
            for (const [index, doc] of individualFiles.entries()) {
              const docItem = document.createElement('div');
              docItem.className = 'border rounded-md overflow-hidden cursor-pointer hover:border-blue-500 transition-colors document-item';
              docItem.setAttribute('data-batch-id', batch.id);
              docItem.setAttribute('data-index', batch.documents.indexOf(doc));
              
              // Get the actual image URL from stored files
              const previewKey = `${batch.id}-${index}`;
              let imageUrl = '/placeholder.svg';
              
              if (doc.file && doc.file instanceof File) {
                imageUrl = await storeFilePreview(doc.file, previewKey);
              } else if (doc.serverPath) {
                imageUrl = doc.serverPath;
              }
              
              docItem.innerHTML = `
                <div class="h-40 bg-muted flex items-center justify-center">
                  <img
                    src="${imageUrl}"
                    alt="${doc.fileName}"
                    class="document-image max-h-full max-w-full object-contain"
                    onerror="this.src='/placeholder.svg'"
                  >
                </div>
                <div class="p-2 bg-gray-50 border-t">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium overflow-hidden text-ellipsis whitespace-nowrap" style="max-width: 100px;">${doc.fileName}</span>
                    <span class="badge ${getPaperSizeColor(doc.paperSize)} text-white text-xs">${doc.paperSize}</span>
                  </div>
                  <div class="mt-1">
                    <span class="badge badge-outline text-xs w-full justify-center">${doc.documentType}</span>
                  </div>
                  <div class="mt-1 flex justify-between items-center">
                    <span class="badge bg-blue-500 text-white text-xs mt-1 overflow-hidden text-ellipsis">
                      ${batch.fileNumber}-${(index + 1).toString().padStart(2, '0')}
                    </span>
                    <button class="btn btn-ghost btn-sm text-red-500 delete-document" data-server-path="${doc.serverPath}" style="padding: 2px; height: auto;">
                      <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                  </div>
                </div>
              `;
              
              documentsGrid.appendChild(docItem);
            }
          }
        } else {
          // Original folder view for non-PDF batches
          const folderItem = document.createElement('div');
          folderItem.className = 'border rounded-md overflow-hidden';
          
          folderItem.innerHTML = `
            <div class="p-4 bg-muted/20 border-b">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <i data-lucide="folder-open" class="h-6 w-6 text-blue-500"></i>
                  <div>
                    <p class="font-medium text-blue-600">${batch.fileNumber}</p>
                    <p class="text-sm">${batch.name}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <span class="badge badge-outline text-xs">
                    ${batch.documents.length} ${batch.documents.length === 1 ? 'document' : 'documents'}
                  </span>
                  <!-- ADD UPLOAD MORE BUTTON -->
                  <button class="btn btn-outline btn-sm upload-more-to-folder" data-file-number="${batch.fileNumber}">
                    <i data-lucide="upload" class="h-4 w-4 mr-1"></i>
                    Upload More
                  </button>
                  <button class="btn btn-outline btn-sm start-typing" data-id="${batch.id}">
                    Start Page Typing
                  </button>
                </div>
              </div>
            </div>
            <div class="p-4">
              <h4 class="text-sm font-medium mb-3">Documents</h4>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 documents-grid" data-batch-id="${batch.id}">
                <!-- Documents will be added here dynamically -->
              </div>
            </div>
          `;
          
          elements.folderView.appendChild(folderItem);
          
          // Add documents to the grid
          const documentsGrid = folderItem.querySelector('.documents-grid');
          
          for (const [index, doc] of batch.documents.entries()) {
            if (state.filterPaperSize === 'All' || doc.paperSize === state.filterPaperSize) {
              const docItem = document.createElement('div');
              docItem.className = 'border rounded-md overflow-hidden cursor-pointer hover:border-blue-500 transition-colors document-item';
              docItem.setAttribute('data-batch-id', batch.id);
              docItem.setAttribute('data-index', index);
              
              // Get the actual image URL from stored files
              const previewKey = `${batch.id}-${index}`;
              let imageUrl = '/placeholder.svg';
              
              if (doc.file && doc.file instanceof File) {
                imageUrl = await storeFilePreview(doc.file, previewKey);
              } else if (doc.serverPath) {
                imageUrl = doc.serverPath;
              }
              
              docItem.innerHTML = `
                <div class="h-40 bg-muted flex items-center justify-center">
                  <img
                    src="${imageUrl}"
                    alt="Document ${index + 1}"
                    class="document-image max-h-full max-w-full object-contain"
                    onerror="this.src='/placeholder.svg'"
                  >
                </div>
                <div class="p-2 bg-gray-50 border-t">
                  <div class="flex justify-between items-center">
                    <span class="text-sm font-medium">Document ${index + 1}</span>
                    <span class="badge ${getPaperSizeColor(doc.paperSize)} text-white text-xs">${doc.paperSize}</span>
                  </div>
                  <div class="mt-1">
                    <span class="badge badge-outline text-xs w-full justify-center">${doc.documentType}</span>
                  </div>
                  <div class="mt-1 flex justify-between items-center">
                    <span class="badge bg-blue-500 text-white text-xs mt-1 overflow-hidden text-ellipsis">
                      ${batch.fileNumber}-${(index + 1).toString().padStart(2, '0')}
                    </span>
                    <button class="btn btn-ghost btn-sm text-red-500 delete-document" data-server-path="${doc.serverPath}" style="padding: 2px; height: auto;">
                      <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                  </div>
                </div>
              `;
              
              documentsGrid.appendChild(docItem);
            }
          }
        }
      }
      
      // Initialize icons for the new elements
      lucide.createIcons();
      
      // Add event listeners
      document.querySelectorAll('.start-typing').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-id');
          const folderId = btn.getAttribute('data-folder');
          window.location.href = `/file-digital-registry/page-typing?fileId=${id}${folderId ? `&folder=${folderId}` : ''}`;
        });
      });
      
      document.querySelectorAll('.document-item').forEach(item => {
        item.addEventListener('click', () => {
          const batchId = item.getAttribute('data-batch-id');
          const folderId = item.getAttribute('data-folder-id');
          const index = parseInt(item.getAttribute('data-index') || item.getAttribute('data-page-index') || 0);
          openPreview(batchId, index, folderId);
        });
      });
      
      document.querySelectorAll('.delete-document').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation(); // Prevent preview from opening
          const serverPath = btn.getAttribute('data-server-path');
          if (serverPath) {
            console.log('Delete button clicked for:', serverPath);
            deleteDocument(serverPath);
          } else {
            alert('No file path found for deletion. Please check console for details.');
            console.error('No server path found for delete button');
          }
        });
      });

      // NEW: Add event listeners for upload more buttons
      document.querySelectorAll('.upload-more-to-folder').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const fileNumber = btn.getAttribute('data-file-number');
          handleUploadMoreToFolder(fileNumber);
        });
      });
    }

    function updatePreview() {
      const batch = state.documentBatches.find(b => b.id === state.selectedFile);
      
      if (!batch) return;
      
      let currentDocument;
      let totalPages;
      
      if (state.currentFolderId) {
        // Show PDF page from folder
        const folderPages = batch.documents.filter(doc => doc.parentFolder === state.currentFolderId);
        currentDocument = folderPages[state.currentPreviewPage - 1];
        totalPages = folderPages.length;
      } else {
        // Show regular document
        currentDocument = batch.documents[state.currentPreviewPage - 1];
        totalPages = batch.documents.filter(doc => !doc.parentFolder).length;
      }
      
      // Update title
      let title = `${batch.name}`;
      if (state.currentFolderId) {
        const folder = batch.documents.find(doc => doc.id === state.currentFolderId);
        title += ` - ${folder ? folder.folderName : 'PDF'} - Page ${state.currentPreviewPage} of ${totalPages}`;
      } else {
        title += ` - Document ${state.currentPreviewPage} of ${totalPages}`;
      }
      elements.previewTitle.textContent = title;
      
      // Update image with actual file
    if (currentDocument) {
      if (currentDocument.file) { // Prioritize client-side file
              const previewKey = state.currentFolderId 
                ? `${batch.id}-${state.currentFolderId}-${state.currentPreviewPage - 1}`
                : `${batch.id}-${state.currentPreviewPage - 1}`;
              
              const imageUrl = state.filePreviews.get(previewKey) || '/placeholder.svg';
              elements.previewImage.src = imageUrl;
      } else if (currentDocument.serverPath) { // Fallback to server path
              elements.previewImage.src = currentDocument.serverPath;
      } else if (currentDocument.downloadUrl) {
        elements.previewImage.src = currentDocument.downloadUrl;
          } else {
              elements.previewImage.src = '/placeholder.svg';
          }
      } else {
          elements.previewImage.src = '/placeholder.svg';
      }
      
      elements.previewImage.style.transform = `scale(${state.zoomLevel / 100}) rotate(${state.rotation}deg)`;
      
      // Update document info
      elements.documentInfo.innerHTML = '';
      
      const fileNumberBadge = document.createElement('span');
      fileNumberBadge.className = 'badge mr-2';
      
      if (state.currentFolderId) {
        fileNumberBadge.textContent = `${batch.fileNumber}-P${state.currentPreviewPage.toString().padStart(2, '0')}`;
      } else {
        fileNumberBadge.textContent = `${batch.fileNumber}-${state.currentPreviewPage.toString().padStart(2, '0')}`;
      }
      
      elements.documentInfo.appendChild(fileNumberBadge);
      
      if (currentDocument) {
        const paperSizeBadge = document.createElement('span');
        paperSizeBadge.className = `badge mr-2 ${getPaperSizeColor(currentDocument.paperSize)}`;
        paperSizeBadge.textContent = currentDocument.paperSize;
        elements.documentInfo.appendChild(paperSizeBadge);
        
        const typeBadge = document.createElement('span');
        typeBadge.className = 'badge badge-outline';
        typeBadge.textContent = currentDocument.documentType;
        elements.documentInfo.appendChild(typeBadge);
        
        // Add PDF conversion badge if applicable
        if (currentDocument.isConvertedPdf || state.currentFolderId) {
          const pdfBadge = document.createElement('span');
          pdfBadge.className = 'badge bg-green-500 text-white ml-2';
          pdfBadge.textContent = 'PDF Converted';
          elements.documentInfo.appendChild(pdfBadge);
        }
      }
      
      // Update navigation buttons
      elements.prevPageBtn.disabled = state.currentPreviewPage <= 1;
      elements.nextPageBtn.disabled = state.currentPreviewPage >= totalPages;
      
      // Update zoom level
      elements.zoomLevel.textContent = `${state.zoomLevel}%`;
    }

    function renderIndexedFiles() {
      if (!elements.indexedFilesList) {
        return;
      }

      elements.indexedFilesList.innerHTML = '';

      if (indexedFilesLoading) {
        elements.indexedFilesList.innerHTML = `
          <div class="p-4 text-sm text-muted-foreground">Loading indexed files...</div>
        `;
        return;
      }

      if (indexedFilesError) {
        const errorMessage = document.createElement('div');
        errorMessage.className = 'p-4 text-sm text-red-600';
        errorMessage.textContent = indexedFilesError;
        elements.indexedFilesList.appendChild(errorMessage);
        elements.confirmFileSelectBtn.disabled = true;
        return;
      }

      if (!indexedFiles.length) {
        const message = state.indexedFilesSearchTerm
          ? `No indexed files found for "${state.indexedFilesSearchTerm}".`
          : 'No indexed files found.';
        const emptyState = document.createElement('div');
        emptyState.className = 'p-4 text-sm text-muted-foreground';
        emptyState.textContent = message;
        elements.indexedFilesList.appendChild(emptyState);
        elements.confirmFileSelectBtn.disabled = true;
        return;
      }

      const selectedId = state.selectedIndexedFile ? String(state.selectedIndexedFile) : null;

      indexedFiles.forEach(file => {
        const fileId = String(file.id);
        const isSelected = selectedId === fileId;

        const fileItem = document.createElement('div');
        fileItem.className = `flex items-center p-4 cursor-pointer hover:bg-muted/50 ${
          isSelected ? 'bg-muted' : ''
        }`;
        fileItem.setAttribute('data-id', fileId);

        const landUse = escapeHtml(file.landUseType || 'Unknown');
        const district = escapeHtml(file.district || 'Unspecified');
        const fileNumber = escapeHtml(file.fileNumber || 'No File Number');
        const fileName = escapeHtml(file.name || 'Unnamed File');

        fileItem.innerHTML = `
          <i data-lucide="folder" class="h-6 w-6 mr-3 ${
            isSelected ? 'text-blue-500' : 'text-gray-400'
          }"></i>
          <div>
            <p class="font-medium text-blue-600">${fileNumber}</p>
            <p class="text-sm">${fileName}</p>
            <div class="flex items-center gap-2 mt-1">
              <span class="badge badge-secondary text-xs">${landUse}</span>
              <span class="badge badge-outline text-xs">${district}</span>
            </div>
          </div>
        `;

        elements.indexedFilesList.appendChild(fileItem);
      });

      // Initialize icons for the new elements
      lucide.createIcons();

      // Add event listeners
      elements.indexedFilesList.querySelectorAll('[data-id]').forEach(item => {
        item.addEventListener('click', () => {
          const id = item.getAttribute('data-id');
          selectIndexedFileTemp(id);
        });
      });

      // Update confirm button
      elements.confirmFileSelectBtn.disabled = !state.selectedIndexedFile;
    }

    function populateDocumentDetailsForm() {
      const doc = state.uploadDocuments[state.currentDocumentIndex];
      
      if (!doc) return;
      
      // Update file name
      elements.documentName.textContent = doc.file.name;
      
      // Update paper size
      elements.paperSizeRadios.forEach(radio => {
        radio.checked = radio.value === doc.paperSize;
      });
      
      // Update document type
      elements.documentType.value = doc.documentType;
      
      // Update notes
      elements.documentNotes.value = doc.notes || '';
    }

    function getFilteredBatches() {
      let filtered = state.documentBatches;
      
      // Filter by paper size
      if (state.filterPaperSize !== 'All') {
        filtered = filtered.filter(batch => 
          batch.documents.some(doc => doc.paperSize === state.filterPaperSize)
        );
      }
      
      // Filter by search query
      if (state.searchQuery) {
        const query = state.searchQuery.toLowerCase();
        filtered = filtered.filter(batch => 
          batch.fileNumber.toLowerCase().includes(query) ||
          batch.name.toLowerCase().includes(query) ||
          batch.documents.some(doc => doc.fileName.toLowerCase().includes(query))
        );
      }
      
      return filtered;
    }

    // Event handlers
    function switchTab(tabId) {
      state.activeTab = tabId;
      updateUI();
    }

    async function handleFileSelect(e) {
      if (e.target.files && e.target.files.length > 0) {
        const files = Array.from(e.target.files);
        state.selectedUploadFiles = files;
        
        // Detect file types for PDF conversion
        detectFileTypes(files);
        
        // Process files (convert PDFs if enabled)
        let filesToProcess = files;
        
        if (state.pdfConversionEnabled && state.hasPdfFiles) {
          try {
            const processedFiles = await processPdfFilesForUpload(files);
            
            // Convert processed files to upload documents
            state.uploadDocuments = [];
            
            for (const item of processedFiles) {
              if (item.type === 'folder' && item.isPdfFolder) {
                // For PDF folders, add all pages as separate upload documents
                for (const page of item.children) {
                  state.uploadDocuments.push({
                    file: page.file,
                    paperSize: 'A4',
                    documentType: 'Certificate',
                    isPdfFolder: true,
                    folderName: item.name,
                    pageCount: item.children.length,
                    originalFile: item.originalPdfFile,
                    displayOrder: state.uploadDocuments.length
                  });
                }
              } else {
                state.uploadDocuments.push({
                  file: item.file,
                  paperSize: 'A4',
                  documentType: 'Other',
                  isConvertedPdf: item.isConvertedPdf || false,
                  displayOrder: state.uploadDocuments.length
                });
              }
            }
            
          } catch (error) {
            console.error("PDF conversion failed:", error);
            alert("PDF conversion failed. Uploading original files instead.");
            // Continue with original files if conversion fails
            state.uploadDocuments = files.map(file => ({
              file,
              paperSize: 'A4',
              documentType: 'Other',
              displayOrder: 0 // Placeholder, will be reassigned below
            }));
          }
        } else {
          // Process files without conversion
          state.uploadDocuments = files.map(file => ({
            file,
            paperSize: 'A4',
            documentType: 'Other',
            displayOrder: 0 // Placeholder, will be reassigned below
          }));
        }
        
        // Pre-generate previews for images
        await pregenerateImagePreviews();
        
        // Ensure sequential display order values
        state.uploadDocuments = state.uploadDocuments.map((doc, idx) => ({
          ...doc,
          displayOrder: idx
        }));

        updateUI();
      }
    }

    function openDocumentDetails(index) {
      state.currentDocumentIndex = index;
      state.showDocumentDetails = true;
      updateUI();
    }

    function updateDocumentDetails(index, updates) {
      state.uploadDocuments = state.uploadDocuments.map((doc, i) => 
        i === index ? { ...doc, ...updates } : doc
      );
      updateUI();
    }

    function removeFile(index) {
      state.selectedUploadFiles = state.selectedUploadFiles.filter((_, i) => i !== index);
      state.uploadDocuments = state.uploadDocuments.filter((_, i) => i !== index);
      updateUI();
    }

    function resetUpload() {
      state.uploadStatus = 'idle';
      state.uploadProgress = 0;
      state.selectedUploadFiles = [];
      state.uploadDocuments = [];
      elements.fileUpload.value = '';
      elements.uploadError.classList.add('hidden');
      updateUI();
    }

    function sendToPageTyping() {
      if (state.documentBatches.length === 0) {
        alert('No files to send to page typing');
        return;
      }
      
      window.location.href = '/file-digital-registry/page-typing';
    }

    function openPreview(batchId, documentIndex = 0, folderId = null) {
      state.selectedFile = batchId;
      state.currentPreviewPage = documentIndex + 1;
      state.zoomLevel = 100;
      state.rotation = 0;
      state.previewOpen = true;
      state.currentFolderId = folderId;
      updateUI();
    }

    function closePreview() {
      state.previewOpen = false;
      state.currentFolderId = null;
      updateUI();
    }

    function nextPage() {
      const batch = state.documentBatches.find(b => b.id === state.selectedFile);
      if (!batch) return;
      
      let totalPages;
      if (state.currentFolderId) {
        const folderPages = batch.documents.filter(doc => doc.parentFolder === state.currentFolderId);
        totalPages = folderPages.length;
      } else {
        totalPages = batch.documents.filter(doc => !doc.parentFolder).length;
      }
      
      if (state.currentPreviewPage < totalPages) {
        state.currentPreviewPage++;
        updateUI();
      }
    }

    function prevPage() {
      if (state.currentPreviewPage > 1) {
        state.currentPreviewPage--;
        updateUI();
      }
    }

    function zoomIn() {
      state.zoomLevel = Math.min(state.zoomLevel + 25, 200);
      updateUI();
    }

    function zoomOut() {
      state.zoomLevel = Math.max(state.zoomLevel - 25, 50);
      updateUI();
    }

    function rotate() {
      state.rotation = (state.rotation + 90) % 360;
      updateUI();
    }

    function selectIndexedFileTemp(fileId) {
      const normalizedId = String(fileId);

      document.querySelectorAll('#indexed-files-list > div').forEach(item => {
        const id = item.getAttribute('data-id');
        const folderIcon = item.querySelector('[data-lucide="folder"]');
        const isSelected = id === normalizedId;

        item.classList.toggle('bg-muted', isSelected);
        if (folderIcon) {
          folderIcon.classList.toggle('text-blue-500', isSelected);
          folderIcon.classList.toggle('text-gray-400', !isSelected);
        }
      });

      state.selectedIndexedFile = normalizedId;

      const selectedFile = findIndexedFileById(normalizedId);
      state.selectedFileNumberForUpload = selectedFile ? selectedFile.fileNumber : null;

      elements.confirmFileSelectBtn.disabled = !state.selectedIndexedFile;
    }

    function selectIndexedFile() {
      const selectedFile = findIndexedFileById(state.selectedIndexedFile);

      if (!selectedFile) {
        alert('Please select an indexed file before continuing.');
        return;
      }

      state.selectedFileNumberForUpload = selectedFile.fileNumber;
      state.showFileSelector = false;
      updateUI();
      showNotification(`Ready to upload documents to <strong>${selectedFile.fileNumber}</strong>`);
    }

    function deleteBatch(id) {
      if (confirm('Are you sure you want to delete this batch?')) {
        state.documentBatches = state.documentBatches.filter(batch => batch.id !== id);
        updateUI();
      }
    }

    function saveDocumentDetails() {
      if (state.currentDocumentIndex === null) return;
      
      const paperSize = Array.from(elements.paperSizeRadios).find(radio => radio.checked)?.value || 'A4';
      const documentType = elements.documentType.value;
      const notes = elements.documentNotes.value;
      
      updateDocumentDetails(state.currentDocumentIndex, {
        paperSize: paperSize,
        documentType: documentType,
        notes: notes
      });
      
      state.showDocumentDetails = false;
      updateUI();
    }

    // Initialize the page
    async function init() {
      // Set up event listeners
      
      // Tab switching
      elements.tabs.forEach(tab => {
        tab.addEventListener('click', () => {
          const tabId = tab.getAttribute('data-tab');
          switchTab(tabId);
        });
      });
      
      // File upload
      elements.fileUpload.addEventListener('change', handleFileSelect);
      elements.browseFilesBtn.addEventListener('click', () => elements.fileUpload.click());
      
      // PDF Conversion checkbox
      if (elements.convertPdfs) {
        elements.convertPdfs.addEventListener('change', (e) => {
          state.pdfConversionEnabled = e.target.checked;
          if (state.selectedUploadFiles.length > 0) {
            detectFileTypes(state.selectedUploadFiles);
          }
        });
      }
      
      // Select file
      elements.selectFileBtn.addEventListener('click', async () => {
        state.showFileSelector = true;
        updateUI();

        if (!indexedFiles.length && !indexedFilesLoading) {
          await loadIndexedFiles(state.indexedFilesSearchTerm || '');
        } else {
          renderIndexedFiles();
        }

        if (elements.fileSearchInput) {
          elements.fileSearchInput.focus();
          elements.fileSearchInput.select();
        }
      });

      if (elements.fileSearchInput) {
        const debouncedIndexedSearch = debounce((term) => {
          loadIndexedFiles(term.trim());
        }, 350);

        elements.fileSearchInput.addEventListener('input', (e) => {
          debouncedIndexedSearch(e.target.value || '');
        });
      }
      
      // Upload actions
      elements.clearAllBtn.addEventListener('click', resetUpload);
      elements.startUploadBtn.addEventListener('click', startUpload);
      elements.cancelUploadBtn.addEventListener('click', resetUpload);
      elements.uploadMoreBtn.addEventListener('click', resetUpload);
      elements.viewUploadedBtn.addEventListener('click', () => switchTab('uploaded-files'));
      elements.proceedToTypingBtn.addEventListener('click', sendToPageTyping);
      elements.goToUploadBtn.addEventListener('click', () => switchTab('upload'));
      
      // Search functionality
      elements.fileSearch.addEventListener('input', (e) => {
        state.searchQuery = e.target.value;
        updateUI();
      });
      
      // Preview dialog
      elements.previewDialog.addEventListener('click', e => {
        if (e.target === elements.previewDialog) {
          closePreview();
        }
      });
      elements.prevPageBtn.addEventListener('click', prevPage);
      elements.nextPageBtn.addEventListener('click', nextPage);
      elements.zoomInBtn.addEventListener('click', zoomIn);
      elements.zoomOutBtn.addEventListener('click', zoomOut);
      elements.rotateBtn.addEventListener('click', rotate);
      elements.proceedToTypingFromPreviewBtn.addEventListener('click', () => {
        if (state.selectedFile) {
          window.location.href = `/file-digital-registry/page-typing?fileId=${state.selectedFile}`;
        }
      });
      
      // File selector dialog
      elements.fileSelectorDialog.addEventListener('click', e => {
        if (e.target === elements.fileSelectorDialog) {
          state.showFileSelector = false;
          updateUI();
        }
      });
      elements.cancelFileSelectBtn.addEventListener('click', () => {
        state.showFileSelector = false;
        updateUI();
      });
      elements.confirmFileSelectBtn.addEventListener('click', selectIndexedFile);
      
      // Document details dialog
      elements.documentDetailsDialog.addEventListener('click', e => {
        if (e.target === elements.documentDetailsDialog) {
          state.showDocumentDetails = false;
          updateUI();
        }
      });
      elements.cancelDetailsBtn.addEventListener('click', () => {
        state.showDocumentDetails = false;
        updateUI();
      });
      elements.saveDetailsBtn.addEventListener('click', saveDocumentDetails);
      
      // Toggle view
      elements.toggleViewBtn.addEventListener('click', () => {
        state.showFolderView = !state.showFolderView;
        updateUI();
      });
      
      // Paper size filter
      elements.paperSizeFilter.addEventListener('change', () => {
        state.filterPaperSize = elements.paperSizeFilter.value;
        updateUI();
      });
      
      // Test server configuration first
      await testServerConfig();
      
      // Load indexed files for the selector
      await loadIndexedFiles();

      // Load data from server
      await loadServerData();
      
      // Initial UI update
      updateUI();
      
      console.log('Application initialized successfully');
    }
    
    // Initialize the page when DOM is loaded
    document.addEventListener('DOMContentLoaded', init);
  </script>