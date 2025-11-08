<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('page-title', 'KLAES')</title>
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('storage/favicon_io/favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('storage/favicon_io/favicon-16x16.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('storage/favicon_io/apple-touch-icon.png') }}">


<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<!-- Lucide Icons (pinned for stability) -->
<script src="https://unpkg.com/lucide@0.429.0/dist/umd/lucide.min.js" crossorigin="anonymous"></script>
<script>
  (function attachLucideSafeGuards() {
    function ensureLucideReady() {
      const lib = window.lucide;
      if (!lib) {
        return false;
      }

      const fallbackIcons = lib.icons || lib.allIcons || lib.iconNodes || lib.availableIcons;
      if (!lib.icons && fallbackIcons) {
        lib.icons = fallbackIcons;
      }

      if (typeof lib.createIcons === 'function' && !lib.__patchedCreateIcons) {
        const originalCreateIcons = lib.createIcons.bind(lib);
        lib.createIcons = function safeCreateIcons(options = {}) {
          const icons = options.icons || lib.icons || lib.allIcons || lib.iconNodes || lib.availableIcons;
          if (!icons) {
            console.warn('[Lucide] Icon map is unavailable; skipping render.', options);
            return;
          }

          try {
            return originalCreateIcons({ ...options, icons });
          } catch (error) {
            console.error('[Lucide] Failed to render icons:', error);
          }
        };
        lib.__patchedCreateIcons = true;
      }

      return true;
    }

    const ensure = () => ensureLucideReady();

    if (!ensure()) {
      document.addEventListener('DOMContentLoaded', ensure, { once: true });
      window.addEventListener('load', ensure, { once: true });
      setTimeout(ensure, 0);
    }

    window.ensureLucideIcons = ensure;
  })();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" /> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdn.jsdelivr.net/npm/jspdf@3.0.1/dist/jspdf.es.min.js"></script>
  <style>
    .sidebar-item {
      display: flex;
      align-items: center;
      padding: 0.75rem;
      cursor: pointer;
      border-radius: 0.375rem;
      transition: background-color 0.2s;
    }
    .sidebar-item:hover {
      background-color: #f3f4f6;
    }
    .sidebar-item.active {
      background-color: #eff6ff;
      color: #2563eb;
    }
    .sidebar-icon {
      width: 1.5rem;
      height: 1.5rem;
      margin-right: 0.75rem;
 
    }
    .klas-logo {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2px;
      width: 24px;
      height: 24px;
    }
    .klas-logo-red { background-color: #ef4444; }
    .klas-logo-green { background-color: #10b981; }
    .klas-logo-yellow { background-color: #f59e0b; }
    .klas-logo-blue { background-color: #3b82f6; }

    .submenu-item {
      display: flex;
      align-items: center;
      padding: 0.5rem 0.75rem 0.5rem 2.5rem;
      cursor: pointer;
      border-radius: 0.375rem;
      transition: background-color 0.2s;
    }
    .submenu-item:hover {
      background-color: #f3f4f6;
    }
    .submenu-item.active {
      background-color: #eff6ff;
      color: #2563eb;
    }

    .submenu {
    display: none;
  }

  .submenu.hidden {
    display: none;
  }

  .submenu:not(.hidden) {
    display: block;
  }



  .sidebar {
    width: 280px;
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }
  
  .sidebar-content {
    flex: 1;
    overflow-y: auto;
    height: calc(100vh - 8rem);
  }
  
  .sidebar-content::-webkit-scrollbar {
    width: 4px;
  }
  
  .sidebar-content::-webkit-scrollbar-track {
    background: transparent;
  }
  
  .sidebar-content::-webkit-scrollbar-thumb {
    background-color: rgba(0, 0, 0, 0.1);
    border-radius: 10px;
  }
  
  .active {
    font-weight: 500;
    background-color: #EBF5FF;
    border-left: 4px solid #3B82F6;
  }
  
  .sidebar-badge {
    font-size: 0.65rem;
    padding: 0.1rem 0.4rem;
    border-radius: 9999px;
    background-color: #E5E7EB;
    color: #374151;
  }
  
  .module-badge-programmes {
    background-color: #DBEAFE;
    color: #1E40AF;
  }
  
  .module-badge-legal-search {
    background-color: #DCFCE7;
    color: #166534;
  }
  
  .module-badge-instrument {
    background-color: #FEF3C7;
    color: #92400E;
  }
  
  .sidebar-item {
    transition: all 0.2s;
  }
  
  .sidebar-item:hover {
    background-color: #F9FAFB;
  }
  
  .animate-ping {
    animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
  }
  
  @keyframes ping {
    75%, 100% {
      transform: scale(2);
      opacity: 0;
    }
  }
  
  /* Module icon colors for different sections */
  .module-icon-dashboard {
    opacity: 0.8;
    color: #2563eb; /* Blue */
  }
  
  .module-icon-customer {
    opacity: 0.8;
    color: #7c3aed; /* Purple */
  }
  
  .module-icon-programmes {
    opacity: 0.8;
    color: #059669; /* Green */
  }
  
  .module-icon-info-products {
    opacity: 0.8;
    color: #d97706; /* Orange */
  }
  
  .module-icon-legal-search {
    opacity: 0.8;
    color: #0891b2; /* Teal */
  }
  
  .module-icon-instrument {
    opacity: 0.8;
    color: #002f64; /* Red */
  }
  
  .module-icon-file-registry {
    opacity: 0.8;
    color: #4f46e5; /* Indigo */
  }
  
  .module-icon-systems {
    opacity: 0.8;
    color: #db2777; /* Pink */
  }
  
  .module-icon-legacy {
    opacity: 0.8;
    color: #92400e; /* Brown */
  }
  
  .module-icon-admin {
    opacity: 0.8;
    color: #4b5563; /* Gray */
  }
  
  /* Nested submenu styles */
  .submenu-l1 {
    padding-left: 1.5rem;
  }
  
  .submenu-l2 {
    padding-left: 2.5rem;
  }
  
  .submenu-l3 {
    padding-left: 3.5rem;
  }
  
  .submenu-item {
    font-size: 0.875rem;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    border-radius: 0.375rem;
    transition: all 0.2s;
  }
  
  .submenu-item:hover {
    background-color: #F9FAFB;
  }
  
  .submenu-item.active {
    font-weight: 500;
    background-color: #EBF5FF;
    border-left: 4px solid #3B82F6;
  }
</style>
<style>

  

  .stat-card {
    background-color: white;
    border-radius: 0.375rem;
    padding: 1.25rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
  }
  .tab {
    padding: 0.75rem 1rem;
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
  }
  .tab:hover {
    color: #4b5563;
  }
  .tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
  }
  .service-card {
    background-color: white;
    border-radius: 0.375rem;
    padding: 1.5rem;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
  }
  .badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
  }
  .badge-primary {
    background-color: #f3f4f6;
    color: #4b5563;
  }
  .badge-progress {
    background-color: #dbeafe;
    color: #2563eb;
  }
  .badge-approved {
    background-color: #d1fae5;
    color: #059669;
  }
  .badge-pending {
    background-color: #fef3c7;
    color: #d97706;
  }
  .progress-bar {
    height: 8px;
    border-radius: 4px;
    background-color: #e5e7eb;
    overflow: hidden;
  }
  .progress-bar-fill {
    height: 100%;
    border-radius: 4px;
  }
  .progress-bar-blue {
    background-color: #3b82f6;
  }
  .progress-bar-orange {
    background-color: #f59e0b;
  }
  .progress-bar-red {
    background-color: #ef4444;
  }
  .table-header {
    background-color: #f9fafb;
    font-weight: 500;
    color: #4b5563;
    text-align: left;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
  }
  .table-cell {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e5e7eb;
  }
  </style>

  @livewireStyles
</head>
<body class="bg-gray-100 flex h-screen font-sans antialiased">
<!-- Preloader -->
 <div id="preloader" class="fixed inset-0 bg-white bg-opacity-80 flex items-center justify-center z-50">
  <img src="{{ asset('storage/upload/logo/klas_logo.gif') }}" alt="Loading..." style="width: 200px; height: auto;">
</div>  

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const preloader = document.getElementById('preloader');
    setTimeout(function () {
      preloader.style.display = 'none';
    }, 3000);  
  });
</script> 
  <!-- Sidebar -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      @if(session('success'))
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: '{{ session('success') }}',
          confirmButtonColor: '#10b981'
        });
      @endif

      @if(session('error'))
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: '{{ session('error') }}',
          confirmButtonColor: '#ef4444'
        });
      @endif

      @if($errors->any())
        Swal.fire({
          icon: 'error',
          title: 'Validation Errors',
          html: '<ul style="text-align: left;">' +
            @foreach($errors->all() as $error)
              '<li>{{ $error }}</li>' +
            @endforeach
            '</ul>',
          confirmButtonColor: '#ef4444'
        });
      @endif
    });
  </script>

 
    <!-- Sidebar Menu -->
    @include('admin.menu')
  <!-- Main Content (Placeholder) -->
 
    @include('admin.content')
 

  <script>
    // Initialize Lucide icons
    lucide.createIcons();
  </script>
  @yield('footer-scripts')
  <script>
    // Initialize Lucide icons
    document.addEventListener('DOMContentLoaded', function() {
      if(typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
    });
  </script>
  <script src="{{ asset('js/tailwind-modal.js') }}"></script>
  @livewireScripts
  <script>
    // Provide a global CSV import handler for legacy Step 4 view when injected dynamically
    (function() {
      if (window.handleCsvImport) return; // don't re-define if exists

      function parseRow(line) {
        const out = [];
        let cur = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
          const ch = line[i];
          if (ch === '"') {
            if (inQuotes && line[i + 1] === '"') { cur += '"'; i++; }
            else { inQuotes = !inQuotes; }
          } else if (ch === ',' && !inQuotes) {
            out.push(cur); cur = '';
          } else { cur += ch; }
        }
        out.push(cur);
        return out.map(v => v.replace(/^\s+|\s+$/g, ''));
      }

      function parseCsv(text) {
        const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');
        if (!lines.length) return [];
        const headers = parseRow(lines.shift()).map(h => h.trim().toLowerCase());
        const rows = [];
        for (const line of lines) {
          const cols = parseRow(line);
          const obj = {};
          headers.forEach((h, idx) => obj[h] = (cols[idx] || '').trim());
          rows.push(obj);
        }
        return rows;
      }

      window.handleCsvImport = function() {
        try {
          const fileInput = document.getElementById('csvFileInput');
          const resultDiv = document.getElementById('csv-result');
          const loadingDiv = document.getElementById('csv-loading');
          if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            if (resultDiv) resultDiv.innerHTML = '<div class="text-red-600 text-sm">Please select a CSV file first.</div>';
            return;
          }
          const file = fileInput.files[0];
          if (!file.name.toLowerCase().endsWith('.csv') && file.type !== 'text/csv') {
            if (resultDiv) resultDiv.innerHTML = '<div class="text-red-600 text-sm">Please select a valid CSV file.</div>';
            return;
          }
          if (loadingDiv) loadingDiv.style.display = 'block';
          const reader = new FileReader();
          reader.onload = function(e) {
            try {
              const text = e.target.result;
              const records = parseCsv(text);
              const buyers = records.map(r => ({
                buyerTitle: r['title'] || '',
                firstName: r['first name'] || r['first_name'] || '',
                middleName: r['middle name'] || r['middle_name'] || '',
                surname: r['surname'] || r['last name'] || r['last_name'] || '',
                unit_no: (r['unit number'] || r['unit_no'] || '').toUpperCase(),
                unitMeasurement: r['unit measurement'] || r['unit_measurement'] || ''
              }));
              window.dispatchEvent(new CustomEvent('update-buyers', { detail: { buyers } }));
              if (resultDiv) resultDiv.innerHTML = `<div class="text-green-700 bg-green-50 border border-green-200 p-2 rounded text-sm">Imported ${buyers.length} buyer(s) from CSV.</div>`;
              if (typeof lucide !== 'undefined') { setTimeout(() => lucide.createIcons(), 50); }
            } catch (err) {
              console.error('CSV parse error:', err);
              if (resultDiv) resultDiv.innerHTML = '<div class="text-red-700 bg-red-50 border border-red-200 p-2 rounded text-sm">Error parsing CSV. Please check the file format.</div>';
            } finally {
              if (loadingDiv) loadingDiv.style.display = 'none';
            }
          };
          reader.onerror = function() {
            if (loadingDiv) loadingDiv.style.display = 'none';
            if (resultDiv) resultDiv.innerHTML = '<div class="text-red-700 bg-red-50 border border-red-200 p-2 rounded text-sm">Failed to read the file.</div>';
          };
          reader.readAsText(file);
        } catch (e) {
          console.error('handleCsvImport global error:', e);
        }
      }

      // Delegated click handler so buttons inside dynamically injected HTML work reliably
      document.addEventListener('click', function(evt) {
        const btn = evt.target.closest('#importCsvButton');
        if (btn) {
          evt.preventDefault();
          if (typeof window.handleCsvImport === 'function') {
            window.handleCsvImport();
          }
        }
      });
    })();
  </script>
</body>
</html>
