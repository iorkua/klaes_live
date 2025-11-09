  <style>
    /* CSS Variables for Color Scheme */
    :root {
      --primary: #3b82f6;
      --primary-hover: #2563eb;
      --primary-light: #dbeafe;
      --success: #10b981;
      --success-hover: #059669;
      --success-light: #d1fae5;
      --warning: #f59e0b;
      --warning-hover: #d97706;
      --warning-light: #fef3c7;
      --danger: #ef4444;
      --danger-hover: #dc2626;
      --danger-light: #fee2e2;
      --info: #06b6d4;
      --info-hover: #0891b2;
      --info-light: #cffafe;
      --border: #e5e7eb;
      --border-dark: #d1d5db;
      --muted: #f3f4f6;
      --muted-dark: #e5e7eb;
      --text-primary: #1f2937;
      --text-secondary: #6b7280;
      --text-muted: #9ca3af;
    }

    /* Card styles - Enhanced */
    .card {
      background-color: white;
      border-radius: 0.75rem;
      border: 1px solid var(--border);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      transition: all 0.3s ease;
    }

    .card:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    /* Stats Card Enhancement */
    .card .text-2xl.font-bold {
      color: var(--text-primary);
    }

    .card .text-sm.font-medium {
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
      font-size: 0.8rem;
    }

    .card .text-xs.text-muted-foreground {
      color: var(--text-muted);
    }

    /* Button styles - Enhanced */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 0.5rem;
      font-weight: 600;
      font-size: 0.875rem;
      line-height: 1.25rem;
      padding: 0.625rem 1.25rem;
      transition: all 0.2s ease;
      cursor: pointer;
      border: none;
      text-decoration: none;
      white-space: nowrap;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .btn:hover:not(:disabled) {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .btn:active:not(:disabled) {
      transform: translateY(1px);
    }

    /* Primary Button */
    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
      box-shadow: 0 6px 12px rgba(59, 130, 246, 0.3);
    }

    .btn-primary:focus {
      outline: 2px solid var(--primary-light);
      outline-offset: 2px;
    }

    /* Outline Button */
    .btn-outline {
      background-color: transparent;
      border: 1.5px solid var(--border);
      color: var(--text-primary);
    }

    .btn-outline:hover {
      background-color: var(--muted);
      border-color: var(--border-dark);
    }

    /* Ghost Button */
    .btn-ghost {
      background-color: transparent;
      color: var(--text-primary);
    }

    .btn-ghost:hover {
      background-color: var(--muted);
    }

    /* Destructive Button */
    .btn-destructive {
      background-color: var(--danger);
      color: white;
    }

    .btn-destructive:hover {
      background-color: var(--danger-hover);
      box-shadow: 0 6px 12px rgba(239, 68, 68, 0.3);
    }

    .btn-destructive:focus {
      outline: 2px solid var(--danger-light);
      outline-offset: 2px;
    }

    /* Success Button */
    .btn-success {
      background-color: var(--success);
      color: white;
    }

    .btn-success:hover {
      background-color: var(--success-hover);
      box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
    }

    /* Small Button */
    .btn-sm {
      padding: 0.375rem 0.875rem;
      font-size: 0.8125rem;
    }

    /* Disabled Button */
    .btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
      box-shadow: none;
    }

    .btn:disabled:hover {
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    /* Button with Gap */
    .btn.gap-1 { gap: 0.25rem; }
    .btn.gap-2 { gap: 0.5rem; }
    .btn.gap-3 { gap: 0.75rem; }

    /* Badge styles - Enhanced */
    .badge {
      display: inline-flex;
      align-items: center;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
      line-height: 1;
      padding: 0.375rem 0.75rem;
      white-space: nowrap;
      transition: all 0.2s ease;
    }

    .badge-outline {
      background-color: transparent;
      border: 1px solid var(--border);
      color: var(--text-secondary);
    }

    .badge-secondary {
      background-color: var(--muted);
      color: var(--text-primary);
    }

    .badge-primary {
      background-color: var(--primary-light);
      color: var(--primary);
    }

    .badge-success {
      background-color: var(--success-light);
      color: var(--success);
    }

    .badge-warning {
      background-color: var(--warning-light);
      color: var(--warning);
    }

    .badge-danger {
      background-color: var(--danger-light);
      color: var(--danger);
    }

    .badge-info {
      background-color: var(--info-light);
      color: var(--info);
    }

    /* Input styles - Enhanced */
    .input {
      display: block;
      width: 100%;
      border-radius: 0.5rem;
      border: 1.5px solid var(--border);
      padding: 0.625rem 0.875rem;
      font-size: 0.875rem;
      line-height: 1.25rem;
      background-color: white;
      color: var(--text-primary);
      transition: all 0.2s ease;
    }

    .input::placeholder {
      color: var(--text-muted);
    }

    .input:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: inset 0 0 0 1px var(--primary), 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .input:disabled {
      background-color: var(--muted);
      cursor: not-allowed;
      opacity: 0.6;
    }

    /* Select/Dropdown styles */
    select.input {
      cursor: pointer;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3E%3C/svg%3E");
      background-position: right 0.5rem center;
      background-repeat: no-repeat;
      background-size: 1.5em 1.5em;
      padding-right: 2.5rem;
      appearance: none;
    }

    /* Progress bar - Enhanced */
    .progress {
      position: relative;
      width: 100%;
      height: 0.625rem;
      overflow: hidden;
      background-color: var(--muted);
      border-radius: 9999px;
      border: 1px solid var(--border);
    }

    .progress-bar {
      position: absolute;
      height: 100%;
      background: linear-gradient(90deg, var(--primary), var(--info));
      transition: width 0.3s ease;
      border-radius: 9999px;
    }

    /* Dialog styles - Enhanced */
    .dialog-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.4);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 50;
      animation: fadeIn 0.2s ease-out;
    }

    .dialog-backdrop.hidden {
      display: none !important;
      visibility: hidden;
    }

    .dialog-content {
      background-color: white;
      border-radius: 0.75rem;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      width: 100%;
      max-width: 32rem;
      max-height: 90vh;
      overflow-y: auto;
      border: 1px solid var(--border);
    }

    .dialog-content .border-b {
      border-color: var(--border);
    }

    .dialog-content .border-t {
      border-color: var(--border);
    }

    .dialog-preview {
      max-width: 900px;
      height: 800px;
      display: flex;
      flex-direction: column;
    }

    /* Tab styles - Enhanced */
    .tabs {
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    .tabs-list {
      display: flex;
      border-bottom: 2px solid var(--border);
      gap: 1rem;
    }

    .tab {
      padding: 0.875rem 1.25rem;
      font-size: 0.925rem;
      font-weight: 600;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      color: var(--text-secondary);
      transition: all 0.3s ease;
      margin-bottom: -2px;
      text-decoration: none;
    }

    .tab:hover:not([aria-selected="true"]) {
      color: var(--text-primary);
      background-color: rgba(59, 130, 246, 0.05);
    }

    .tab[aria-selected="true"] {
      border-bottom-color: var(--primary);
      color: var(--primary);
      font-weight: 700;
    }

    .tab-content {
      display: none;
      padding-top: 1.5rem;
      animation: fadeIn 0.3s ease-out;
    }

    .tab-content[aria-hidden="false"] {
      display: block;
    }

    /* Radio group - Enhanced */
    .radio-group {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 0.75rem;
    }

    .radio-item {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .radio-item input[type="radio"] {
      width: 1rem;
      height: 1rem;
      cursor: pointer;
      accent-color: var(--primary);
    }

    .radio-item label {
      cursor: pointer;
      user-select: none;
    }

    /* Checkbox - Enhanced */
    input[type="checkbox"] {
      width: 1rem;
      height: 1rem;
      cursor: pointer;
      accent-color: var(--primary);
      border-radius: 0.25rem;
    }

    input[type="checkbox"]:focus {
      outline: 2px solid var(--primary-light);
      outline-offset: 2px;
    }

    /* Custom animations - Enhanced */
    @keyframes fadeIn {
      from { 
        opacity: 0;
        transform: scale(0.98);
      }
      to { 
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    .animate-fade-in {
      animation: fadeIn 0.3s ease-out;
    }

    .animate-pulse {
      animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    /* Hide scrollbar for Chrome, Safari and Opera */
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }

    /* Hide scrollbar for IE, Edge and Firefox */
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    /* PDF Conversion Styles - Enhanced */
    .pdf-conversion-info {
      background: var(--info-light);
      border: 1.5px solid var(--info);
      border-radius: 0.5rem;
      padding: 0.875rem;
      margin-top: 0.5rem;
      color: var(--info);
      font-size: 0.85rem;
    }

    .pdf-conversion-badge {
      background: var(--success);
      color: white;
      padding: 3px 10px;
      border-radius: 12px;
      font-size: 0.75rem;
      font-weight: 600;
      margin-left: 8px;
      display: inline-block;
    }

    /* Image preview styles - Enhanced */
    .document-image {
      max-height: 160px;
      object-fit: contain;
      background: white;
      border: 1px solid var(--border);
      border-radius: 0.5rem;
      padding: 0.5rem;
    }

    .loading-spinner {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 2.5px solid var(--muted);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Selected files preview styles - Enhanced */
    .file-preview-thumbnail {
      width: 64px;
      height: 64px;
      border-radius: 0.5rem;
      border: 1.5px solid var(--border);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, var(--muted) 0%, var(--muted-dark) 100%);
      flex-shrink: 0;
      transition: all 0.2s ease;
    }

    .file-preview-thumbnail img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }

    .file-preview-overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0);
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: all 0.2s ease;
    }

    .file-preview-overlay:hover {
      background: rgba(0, 0, 0, 0.4);
      opacity: 1;
    }

    .file-preview-container {
      position: relative;
      display: inline-block;
    }

    .file-preview-container:hover .file-preview-thumbnail {
      transform: scale(1.05);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .file-preview-thumbnail {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    /* Full preview modal styles - Enhanced */
    .full-preview-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.8);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 100;
      padding: 1rem;
      animation: fadeIn 0.3s ease-out;
    }

    .full-preview-content {
      background: white;
      border-radius: 0.75rem;
      max-width: 90vw;
      max-height: 90vh;
      width: auto;
      display: flex;
      flex-direction: column;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }

    .full-preview-image {
      max-width: 100%;
      max-height: 70vh;
      object-fit: contain;
    }

    /* Toast notification - Enhanced */
    .toast-notification {
      position: fixed;
      top: 1rem;
      right: 1rem;
      background: var(--success);
      color: white;
      padding: 1rem 1.25rem;
      border-radius: 0.5rem;
      box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      animation: slideIn 0.3s ease-out;
      font-weight: 600;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .toast-notification.error {
      background: var(--danger);
      box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
    }

    .toast-notification.warning {
      background: var(--warning);
      box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.3);
    }

    .toast-notification.info {
      background: var(--info);
      box-shadow: 0 10px 15px -3px rgba(6, 182, 212, 0.3);
    }

    @keyframes slideIn {
      from {
        transform: translateX(100%);
        opacity: 0;
      }
      to {
        transform: translateX(0);
        opacity: 1;
      }
    }

    /* Alert/Alert box Styles */
    .alert {
      padding: 1rem;
      border-radius: 0.5rem;
      border: 1.5px solid;
      margin-bottom: 1rem;
    }

    .alert-info {
      background-color: var(--info-light);
      border-color: var(--info);
      color: var(--info);
    }

    .alert-success {
      background-color: var(--success-light);
      border-color: var(--success);
      color: var(--success);
    }

    .alert-warning {
      background-color: var(--warning-light);
      border-color: var(--warning);
      color: var(--warning);
    }

    .alert-danger {
      background-color: var(--danger-light);
      border-color: var(--danger);
      color: var(--danger);
    }

    /* Drag and drop area styling */
    .drop-zone {
      transition: all 0.2s ease;
    }

    .drop-zone.drag-over {
      background-color: var(--primary-light);
      border-color: var(--primary);
      transform: scale(1.02);
    }

    .drop-zone.drag-over .upload-icon {
      color: var(--primary);
      transform: scale(1.1);
    }

    /* Icon styling enhancements */
    [data-lucide] {
      display: inline-block;
      vertical-align: -0.125em;
      transition: all 0.2s ease;
    }

    .btn:hover [data-lucide] {
      transform: translateY(-1px);
    }

    /* Utility classes for text colors */
    .text-primary { color: var(--primary); }
    .text-success { color: var(--success); }
    .text-warning { color: var(--warning); }
    .text-danger { color: var(--danger); }
    .text-info { color: var(--info); }
    .text-muted-foreground { color: var(--text-muted); }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .btn-sm {
        padding: 0.325rem 0.75rem;
        font-size: 0.75rem;
      }

      .radio-group {
        grid-template-columns: 1fr;
      }

      .tabs-list {
        gap: 0.5rem;
      }

      .tab {
        padding: 0.625rem 1rem;
        font-size: 0.8125rem;
      }

      .dialog-content {
        max-width: 95vw;
      }

      .dialog-preview {
        max-width: 95vw;
      }
    }
  </style>