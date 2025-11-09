@extends('layouts.app')

@section('page-title')
    {{ $PageTitle ?? __('Instrument Registration (New Registration)') }}
@endsection

 

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>

<!-- Inline script to make sure critical functions are defined early -->
<script>
    // Base URL for instrument registration AJAX endpoints
    window.baseUrl = "{{ url('') }}";
    
    // Define critical functions in the global scope first
    function openBatchRegisterModal() {
        // Opening batch registration modal from inline script
        
        // Check if there are multiple instruments selected
        const checkedBoxes = document.querySelectorAll('.main-table-checkbox:checked:not([disabled])');
        const checkedCount = checkedBoxes.length;
        
        if (checkedCount >= 2) {
            // Multiple instruments selected - open quick batch modal
            // Opening quick batch modal for selected instruments
            
            // Get the selected instrument data
            const selectedInstruments = Array.from(checkedBoxes).map(checkbox => {
                const id = checkbox.getAttribute('data-id');
                const status = checkbox.getAttribute('data-status');
                
                // Find the instrument data from serverCofoData
                const instrumentData = serverCofoData.find(item => String(item.id) === String(id));
                
                if (instrumentData) {
                    return {
                        id: instrumentData.id,
                        fileNo: instrumentData.fileno,
                        grantor: instrumentData.Grantor || '',
                        grantee: instrumentData.Grantee || '',
                        status: status,
                        instrumentType: instrumentData.instrument_type || '',
                        lga: instrumentData.lga || '',
                        district: instrumentData.district || '',
                        plotNumber: instrumentData.plotNumber || '',
                        plotSize: instrumentData.size || '',
                        plotDescription: instrumentData.propertyDescription || '',
                        duration: instrumentData.duration || instrumentData.leasePeriod || '',
                        deeds_date: instrumentData.deeds_date || instrumentData.instrumentDate || '',
                        deeds_time: instrumentData.deeds_time || '',
                        rootRegistrationNumber: instrumentData.rootRegistrationNumber || instrumentData.Deeds_Serial_No || '',
                        solicitorName: instrumentData.solicitorName || '',
                        solicitorAddress: instrumentData.solicitorAddress || '',
                        landUseType: instrumentData.landUseType || instrumentData.land_use || ''
                    };
                }
                return null;
            }).filter(item => item !== null);
            
            // Open quick batch modal with selected instruments
            if (typeof window.openQuickBatchModal === 'function') {
                window.openQuickBatchModal(selectedInstruments);
            } else {
                // Quick batch modal function not available
            }
        } else {
            // No selection or single selection - open normal batch modal
            // Opening normal batch modal
            if (typeof window.openBatchRegisterModalImplementation === 'function') {
                window.openBatchRegisterModalImplementation();
            } else {
                // Fallback implementation if main JS hasn't loaded yet
                document.getElementById('batchRegisterModal').style.display = 'block';
                // We'll reload the page after a slight delay to ensure JS is properly loaded
                setTimeout(() => {
                    location.reload();
                }, 500);
            }
        }
    }
</script>
@include('instrument_registration.partials.css')

<div class="flex-1 overflow-auto">
    <!-- Header -->
    @include($headerPartial ?? 'admin.header')
    
    <!-- Main Content -->
    <div class="container mx-auto py-6 space-y-6 px-4">
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h1 class="text-2xl font-bold">Instrument Registration</h1>
            <div>
                <button id="batchRegisterBtn" onclick="openBatchRegisterModal()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg flex items-center gap-2">
                    <i class="fas fa-layer-group"></i> 
                    <span id="batchBtnText">Registration</span>
                </button>
            </div>
        </div>
    
        <!-- Stats Cards -->
        @include('instrument_registration.partials.statistic_card')
    
        <!-- Main Content Table -->
        <div class="table-container">
            <!-- Table tabs & controls -->
            <div class="table-header px-6 py-4 flex justify-between items-center flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <h2 class="text-lg font-semibold text-gray-900">Instrument Registry</h2>
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <i class="fas fa-database text-blue-500"></i>
                        <span id="totalRecordsCount">{{ $totalCount ?? 0 }} Total Records</span>
                    </div>
                </div>
                 
                <!-- Search and Pagination Controls -->
                <div class="flex items-center gap-4">
                    <!-- Records per page selector -->
                    <div class="flex items-center gap-2">
                        <label for="recordsPerPage" class="text-sm text-gray-600">Show:</label>
                        <select id="recordsPerPage" class="border border-gray-300 rounded px-2 py-1 text-sm">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    
                    <!-- Search -->
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        <input id="searchInput" type="search" placeholder="Search by File No..." 
                               class="search-input pl-10 pr-4 py-2.5 text-sm w-80 rounded-lg">
                    </div>
                </div>
            </div>
        
            <!-- Table with Fixed Header -->
            <div class="table-wrapper" style="max-height: 600px; overflow-y: auto;">
              <table class="min-w-full enhanced-table" id="instrumentTable">
              <thead class="bg-gray-50">
                <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <input type="checkbox" class="rounded" id="selectAll" onchange="toggleSelectAll(this)">
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(1)">
                  Reg Particulars
                  <span class="inline-block align-middle" id="sortIcon-1">▲</span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(12)">
                  Captured Date
                  <span class="inline-block align-middle" id="sortIcon-12"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(13)">
                  REG DATE
                  <span class="inline-block align-middle" id="sortIcon-13"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(2)">
                  FileNo
                  <span class="inline-block align-middle" id="sortIcon-2"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(3)">
                  Parent FileNo
                  <span class="inline-block align-middle" id="sortIcon-3"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(4)">
                  Status
                  <span class="inline-block align-middle" id="sortIcon-4"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(5)">
                  Instrument Type
                  <span class="inline-block align-middle" id="sortIcon-5"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(6)">
                  Grantor
                  <span class="inline-block align-middle" id="sortIcon-6"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(7)">
                  Grantee
                  <span class="inline-block align-middle" id="sortIcon-7"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(8)">
                  LGA
                  <span class="inline-block align-middle" id="sortIcon-8"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(9)">
                  District
                  <span class="inline-block align-middle" id="sortIcon-9"></span>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(10)">
                  Plot Number
                  <span class="inline-block align-middle" id="sortIcon-10"></span>
                </th> 
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(11)">
                  Plot Size
                  <span class="inline-block align-middle" id="sortIcon-11"></span>
                </th>
               
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Action
                </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200" id="cofoTableBody">
                @forelse($approvedApplications as $app)
                <tr class="cofo-row" data-status="{{ $app->status }}" data-id="{{ $app->id }}">
                <td class="px-6 py-4 whitespace-nowrap">
                  @php
                    $isDisabled = $app->status === 'registered';
                    // For ST CofO, check if corresponding ST Assignment is registered
                    if ($app->status === 'pending' && $app->instrument_type === 'Sectional Titling CofO') {
                        // This should be dynamically determined based on whether ST Assignment is registered
                        // For now, we'll let JavaScript handle this logic
                        $isDisabled = false; // Let JavaScript determine the actual state
                    }
                  @endphp
                  <input type="checkbox" class="rounded main-table-checkbox" 
                         data-id="{{ $app->id }}" 
                         data-status="{{ $app->status }}"
                         data-instrument-type="{{ $app->instrument_type }}"
                         data-fileno="{{ $app->fileno }}"
                         {{ $isDisabled ? 'disabled' : '' }}
                         onchange="handleMainTableCheckboxChange()">
                </td>
                <!-- 1. Reg Particulars - FIXED: Only show for registered instruments -->
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  @if($app->status === 'registered')
                    @if($app->instrument_type === 'ST Fragmentation')
                      <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-md font-mono text-xs">0/0/0</span>
                    @else
                      <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-md font-mono text-xs">{{ $app->Deeds_Serial_No ?? 'N/A' }}</span>
                    @endif
                  @else
                    <span class="text-gray-400 text-xs">N/A</span>
                  @endif
                </td>

                    <!-- 12. Captured Date -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  @if($app->captured_date ?? null)
                    <div class="flex items-center">
                      <i class="fas fa-calendar-plus text-blue-400 mr-2"></i>
                      {{ date('M d, Y', strtotime($app->captured_date)) }}
                    </div>
                  @else
                    <span class="text-gray-400">N/A</span>
                  @endif
                </td>
                <!-- 13. Reg Date -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  @if($app->reg_date ?? null)
                    <div class="flex items-center">
                      <i class="fas fa-calendar-check text-green-400 mr-2"></i>
                      {{ date('M d, Y', strtotime($app->reg_date)) }}
                    </div>
                  @else
                    <span class="text-gray-400">N/A</span>
                  @endif
                </td>
                <!-- 2. FileNo -->
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="file-number">{{ $app->fileno ?? 'N/A' }}</span>
                </td>
                <!-- 3. Parent FileNo -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                  @if($app->instrument_type === 'ST Fragmentation')
                    <span class="file-number">{{ $app->parent_fileNo ?? 'N/A' }}</span>
                  @elseif(in_array($app->instrument_type, ['ST Assignment (Transfer of Title)', 'Sectional Titling CofO']))
                    <span class="file-number">{{ $app->parent_fileNo ?? $app->fileno ?? 'N/A' }}</span>
                  @else
                    <span class="text-gray-400">N/A</span>
                  @endif
                </td>
                <!-- 4. Status -->
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span class="status-badge badge-{{ $app->status }}">{{ ucfirst($app->status) }}</span>
                </td>
                <!-- 5. Instrument Type -->
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  @if($app->instrument_type === 'ST Fragmentation')
                    <span class="badge badge-st-fragmentation">
                      <i class="fas fa-puzzle-piece mr-1"></i>
                      ST Fragmentation
                    </span>
                  @elseif($app->instrument_type === 'ST Assignment (Transfer of Title)')
                    <span class="badge badge-st-assignment">
                      <i class="fas fa-exchange-alt mr-1"></i>
                      ST Assignment (Transfer of Title )
                    </span>
                  @elseif($app->instrument_type === 'Sectional Titling CofO')
                    <span class="badge badge-sectional-titling">
                      <i class="fas fa-building mr-1"></i>
                      ST CofO
                    </span>
                  @else
                    <span class="badge badge-other-instrument">
                      <i class="fas fa-file-alt mr-1"></i>
                      {{ $app->instrument_type ?? 'Other' }}
                    </span>
                  @endif
                </td>
                <!-- 6. Grantor -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                  @php
                    $grantor = $app->Grantor ?? 'N/A';
                    // Try to decode JSON if it's a string and looks like an array
                    if (is_string($grantor) && str_starts_with(trim($grantor), '[')) {
                      $grantorArr = json_decode($grantor, true);
                      if (json_last_error() !== JSON_ERROR_NONE) {
                        $grantorArr = [$grantor];
                      }
                    } elseif (is_array($grantor)) {
                      $grantorArr = $grantor;
                    } else {
                      $grantorArr = [$grantor];
                    }
                  @endphp
                  @if(is_array($grantorArr) && count($grantorArr) > 1)
                    <span 
                      class="cursor-pointer underline decoration-dotted"
                      tabindex="0"
                      onclick="Swal.fire({title: 'Grantors', html: `{!! implode('<br>', array_map('e', $grantorArr)) !!}` , icon: 'info'})"
                      onkeydown="if(event.key==='Enter'){Swal.fire({title: 'Grantors', html: `{!! implode('<br>', array_map('e', $grantorArr)) !!}` , icon: 'info'})}"
                    >
                      {{ $grantorArr[0] ?? 'N/A' }} +{{ count($grantorArr)-1 }} more
                    </span>
                  @else
                    {{ $grantorArr[0] ?? 'N/A' }}
                  @endif
                </td>
                <!-- 7. Grantee -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                  @php
                    $grantee = $app->Grantee ?? 'N/A';
                    if (is_string($grantee) && str_starts_with(trim($grantee), '[')) {
                      $granteeArr = json_decode($grantee, true);
                      if (json_last_error() !== JSON_ERROR_NONE) {
                        $granteeArr = [$grantee];
                      }
                    } elseif (is_array($grantee)) {
                      $granteeArr = $grantee;
                    } else {
                      $granteeArr = [$grantee];
                    }
                  @endphp
                  @if(is_array($granteeArr) && count($granteeArr) > 1)
                    <span 
                      class="cursor-pointer underline decoration-dotted"
                      tabindex="0"
                      onclick="Swal.fire({title: 'Grantees', html: `{!! implode('<br>', array_map('e', $granteeArr)) !!}` , icon: 'info'})"
                      onkeydown="if(event.key==='Enter'){Swal.fire({title: 'Grantees', html: `{!! implode('<br>', array_map('e', $granteeArr)) !!}` , icon: 'info'})}"
                    >
                      {{ $granteeArr[0] ?? 'N/A' }} +{{ count($granteeArr)-1 }} more
                    </span>
                  @else
                    {{ $granteeArr[0] ?? 'N/A' }}
                  @endif
                </td>
                <!-- 8. LGA -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $app->lga ?? 'N/A' }}</td>
                <!-- 9. District -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $app->district ?? 'N/A' }}</td>
                <!-- 10. Plot Number -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $app->plotNumber ?? 'N/A' }}</td>
                <!-- 11. Plot Size -->
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $app->size ?? 'N/A' }}</td>
            
                <!-- 14. Action -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                  <div class="dropdown-wrapper">
                    <button 
                      class="action-button text-gray-500 hover:text-gray-700 p-2 rounded-md transition-colors duration-200"
                      onclick="toggleDropdown(this, '{{ $app->id }}')" 
                      type="button">
                      <i data-lucide="more-vertical" class="w-4 h-4"></i>
                    </button>
                  </div>
                </td>
                </tr>
                @empty
                <tr>
                <td colspan="15" class="px-6 py-10 text-center text-gray-500">
                  No instrument registrations available.
                </td>
                </tr>
                @endforelse
              </tbody>
              </table>
            </div>

            <!-- Pagination Controls -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                <div class="flex items-center text-sm text-gray-700">
                    <span>Showing</span>
                    <span class="font-medium mx-1" id="showingStart">1</span>
                    <span>to</span>
                    <span class="font-medium mx-1" id="showingEnd">25</span>
                    <span>of</span>
                    <span class="font-medium mx-1" id="showingTotal">{{ $totalCount ?? 0 }}</span>
                    <span>results</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button id="prevPage" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-chevron-left mr-1"></i>
                        Previous
                    </button>
                    
                    <div id="pageNumbers" class="flex items-center space-x-1">
                        <!-- Page numbers will be dynamically generated -->
                    </div>
                    
                    <button id="nextPage" class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                        Next
                        <i class="fas fa-chevron-right ml-1"></i>
                    </button>
                </div>
            </div>

            <script>
            let sortDirections = {1: true}; // Default sort direction for column 1 is ascending
            function sortTable(colIndex) {
              const table = document.getElementById('instrumentTable');
              const tbody = table.tBodies[0];
              const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => !row.querySelector('td[colspan]'));
              const isNumeric = [11].includes(colIndex); // Plot Size column (index 11) is numeric
              const isDate = [12, 13].includes(colIndex); // Date columns (index 12 and 13) are dates
              sortDirections[colIndex] = !sortDirections[colIndex];
              rows.sort((a, b) => {
              let aText = a.children[colIndex]?.innerText.trim() || '';
              let bText = b.children[colIndex]?.innerText.trim() || '';
              if (isNumeric) {
                aText = parseFloat(aText.replace(/[^0-9.]/g, '')) || 0;
                bText = parseFloat(bText.replace(/[^0-9.]/g, '')) || 0;
              } else if (isDate) {
                // Handle N/A values for dates
                if (aText === 'N/A') aText = new Date(0);
                else aText = new Date(aText);
                if (bText === 'N/A') bText = new Date(0);
                else bText = new Date(bText);
              }
              if (aText < bText) return sortDirections[colIndex] ? -1 : 1;
              if (aText > bText) return sortDirections[colIndex] ? 1 : -1;
              return 0;
              });
              // Remove all rows and re-append sorted
              rows.forEach(row => tbody.appendChild(row));
              // Update sort icons
              for (let i = 1; i <= 13; i++) {
              const icon = document.getElementById('sortIcon-' + i);
              if (icon) icon.innerHTML = '';
              }
              const icon = document.getElementById('sortIcon-' + colIndex);
              if (icon) icon.innerHTML = sortDirections[colIndex] ? '▲' : '▼';
            }
            </script>
        </div>
    </div>
    
    <!-- Mobile Dropdown Backdrop -->
    <div id="dropdown-backdrop" class="dropdown-backdrop hidden"></div>
    
    <!-- Dropdown Menu Container -->
    <div id="dropdown-menu" class="dropdown-menu hidden">
        <!-- Dynamic content will be populated here -->
    </div>

    <!-- Include Modals -->
    @include('instrument_registration.partials.singleregistermodal')
    @include('instrument_registration.partials.batchregistermodal')
    @include('instrument_registration.partials.quickbatchmodal')
    
    <!-- Page Footer -->
    @include($footerPartial ?? 'admin.footer')
</div>

<script>
  // Helper function to format multiple owner names
  function formatMultipleOwners(ownerString) {
    if (!ownerString) return 'N/A';
    
    // Check if it's a JSON array string
    if (typeof ownerString === 'string' && ownerString.trim().startsWith('[')) {
      try {
        const owners = JSON.parse(ownerString);
        if (Array.isArray(owners) && owners.length > 0) {
          // Return first name with count if multiple
          return owners.length > 1 
            ? `${owners[0]} +${owners.length - 1} more`
            : owners[0];
        }
      } catch (e) {
        // If JSON parsing fails, return the original string
        return ownerString;
      }
    }
    
    return ownerString;
  }

  // Pass PHP data to JavaScript and format multiple owners
  let serverCofoData = @json($approvedApplications).map(item => {
    // Format Grantor and Grantee if they contain JSON arrays
    if (item.Grantor) {
      item.Grantor = formatMultipleOwners(item.Grantor);
    }
    if (item.Grantee) {
      item.Grantee = formatMultipleOwners(item.Grantee);
    }
    // Initialize RDS/CoR status
    item.rds_exists = false;
    item.rds_data = null;
    item.cor_exists = false;
    return item;
  });
  // Server data loaded: ${serverCofoData.length} records
  
  // RDS status is now checked on-demand when user clicks Generate/View
  console.log('Instrument registration page loaded. RDS status will be checked on-demand.');

</script>

<!-- Define base URL for instrument registration routes -->
<script>
    window.instrumentRegistrationBase = "{{ url('') }}";
</script>

<!-- Include SweetAlert -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Include the external JavaScript file -->
<script src="{{ asset('js/instrument_registration.js') }}?v={{ time() }}"></script>

<!-- Include the updated JavaScript file for single registration modal -->
<script src="{{ asset('js/instrument_registration_updated.js') }}?v={{ time() }}"></script>

<!-- Include the FINAL batch registration handler -->
<script src="{{ asset('js/batch_registration_handler_final.js') }}?v={{ time() }}"></script>

<!-- Include the batch fix to handle empty selectedBatchProperties -->

<!-- Include the quick batch handler -->
<script src="{{ asset('js/quick_batch_handler.js') }}?v={{ time() }}"></script>


@if(session('success'))
<script>
    Swal.fire({
        title: 'Success!',
        text: "{{ session('success') }}",
        icon: 'success',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
</script>
@endif

@if(session('error'))
<script>
    Swal.fire({
        title: 'Error!',
        text: "{{ session('error') }}",
        icon: 'error',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
</script>
@endif

<!-- Floating UI CDN -->
<script src="https://cdn.jsdelivr.net/npm/@floating-ui/dom@1.5.3/dist/floating-ui.dom.min.js"></script>

<script>
let currentDropdown = null;
let currentButton = null;
let currentAppData = null;

// Store app data for easy access
const appData = @json($approvedApplications->keyBy('id'));

async function toggleDropdown(button, appId) {
    const dropdown = document.getElementById('dropdown-menu');
    
    // If clicking the same button, close dropdown
    if (currentButton === button && !dropdown.classList.contains('hidden')) {
        closeDropdown();
        return;
    }
    
    // Close any existing dropdown
    closeDropdown();
    
    // Set current references
    currentButton = button;
    currentDropdown = dropdown;
    currentAppData = appData[appId];
    
    // Populate dropdown content
    populateDropdownContent(currentAppData);
    
    // Position dropdown using Floating UI BEFORE showing it
    try {
        // Determine best placement based on screen size
        const isMobile = window.innerWidth < 640;
        const placement = isMobile ? 'bottom' : 'bottom-end';
        
        const {x, y} = await FloatingUIDOM.computePosition(button, dropdown, {
            placement: placement,
            middleware: [
                FloatingUIDOM.offset(4),
                FloatingUIDOM.flip({
                    fallbackPlacements: ['top', 'bottom', 'left', 'right'],
                }),
                FloatingUIDOM.shift({ 
                    padding: isMobile ? 16 : 8,
                    boundary: document.body
                }),
                // Ensure dropdown stays within viewport
                FloatingUIDOM.size({
                    apply({availableWidth, availableHeight, elements}) {
                        Object.assign(elements.floating.style, {
                            maxWidth: `${Math.min(availableWidth - 16, 280)}px`,
                            maxHeight: `${Math.min(availableHeight - 16, window.innerHeight * 0.9)}px`,
                        });
                    },
                })
            ],
        });
        
        // Set position first
        Object.assign(dropdown.style, {
            left: `${x}px`,
            top: `${y}px`,
        });
        
        // Show backdrop on mobile
        const backdrop = document.getElementById('dropdown-backdrop');
        if (window.innerWidth < 640 && backdrop) {
            backdrop.classList.remove('hidden');
        }
        
        // Then show dropdown
        dropdown.classList.remove('hidden');
        
    } catch (error) {
        console.error('Error positioning dropdown:', error);
        // Fallback positioning if Floating UI fails
        const rect = button.getBoundingClientRect();
        const dropdownWidth = 200;
        const viewportWidth = window.innerWidth;
        
        // Smart positioning: keep dropdown in viewport
        let left = rect.right - dropdownWidth;
        if (left < 8) left = 8; // Prevent going off left edge
        if (left + dropdownWidth > viewportWidth - 8) {
            left = viewportWidth - dropdownWidth - 8; // Prevent going off right edge
        }
        
        dropdown.style.left = `${left}px`;
        dropdown.style.top = `${rect.bottom + 4}px`;
        
        // Show backdrop on mobile
        const backdrop = document.getElementById('dropdown-backdrop');
        if (window.innerWidth < 640 && backdrop) {
            backdrop.classList.remove('hidden');
        }
        
        dropdown.classList.remove('hidden');
    }
}

function populateDropdownContent(app) {
    const dropdown = document.getElementById('dropdown-menu');
    
    const editClass = app.status === 'pending' ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed';
    const editIcon = app.status === 'pending' ? 'text-blue-500' : 'text-gray-300';
    const editHref = app.status === 'pending' ? `{{ url('instrument_registration') }}/${app.id}/edit` : '#';
    const editClick = app.status !== 'pending' ? 'onclick="return false;"' : '';
    
    // Check if ST Assignment is registered for ST CofO instruments
    const canRegister = checkCanRegisterInstrument(app);
    const registerClass = canRegister ? 'text-gray-700 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed';
    const registerIcon = canRegister ? 'text-green-500' : 'text-gray-300';
    const registerClick = canRegister ? `onclick="openSingleRegisterModalWithData('${app.id}'); return false;"` : 'onclick="showSTCofoRestrictionMessage(); return false;"';
    
    const deleteClass = app.status === 'pending' ? 'text-red-600 hover:bg-gray-100' : 'text-gray-400 cursor-not-allowed';
    const deleteIcon = app.status === 'pending' ? '' : 'text-gray-300';
    const deleteClick = app.status === 'pending' ? `onclick="deleteInstrument('${app.id}'); return false;"` : 'onclick="return false;"';
    
    // RDS logic - check if RDS exists for this instrument
    let generateRdsContent = '';
    let viewRdsContent = '';
    
    console.log(`Building dropdown for instrument ${app.id}: status=${app.status}, STM_Ref=${app.STM_Ref}`);
    
    if (app.status === 'registered' && app.STM_Ref) {
        // Always check RDS status in real-time using the instrument_id
        generateRdsContent = `<a href="#" onclick="checkRDSAndShowGenerate('${app.id}', '${app.STM_Ref}'); return false;" class="dropdown-item">
             <i class="fas fa-file-alt w-4 h-4 text-purple-500"></i>
             <span>Generate RDS</span>
           </a>`;
        
        // View RDS - disabled by default, will be enabled if RDS exists
        viewRdsContent = `<a href="#" onclick="checkRDSAndShowView('${app.id}', '${app.STM_Ref}'); return false;" class="dropdown-item text-gray-400">
             <i class="fas fa-eye w-4 h-4 text-gray-400"></i>
             <span>View RDS</span>
           </a>`;
    } else {
        // Not registered or no STM_Ref - disable both
        generateRdsContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
             <i class="fas fa-file-alt w-4 h-4 text-gray-300"></i>
             <span>Generate RDS</span>
           </a>`;
        
        viewRdsContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
             <i class="fas fa-eye w-4 h-4 text-gray-300"></i>
             <span>View RDS</span>
           </a>`;
    }
    
    // CoR logic - similar to RDS, check if CoR exists
    let generateCorContent = '';
    let viewCorContent = '';
    
    if (app.status === 'registered' && app.STM_Ref && app.instrument_type !== 'ST Fragmentation') {
        // Check if CoR already exists (assuming similar logic)
        if (app.cor_exists === true) {
            // CoR exists - disable generate, enable view
            generateCorContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
                 <i class="fas fa-certificate w-4 h-4 text-gray-300"></i>
                 <span>Generate CoR (Already Generated)</span>
               </a>`;
            
            viewCorContent = `<a href="{{ route('coroi.index') }}?url=registered_instruments?STM_Ref=${app.STM_Ref}" class="dropdown-item">
                 <i class="fas fa-eye w-4 h-4 text-blue-500"></i>
                 <span>View CoR</span>
               </a>`;
        } else {
            // CoR doesn't exist - enable generate, disable view
            generateCorContent = `<a href="#" onclick="showGenerateCoRModal('${app.id}', '${app.STM_Ref}'); return false;" class="dropdown-item">
                 <i class="fas fa-certificate w-4 h-4 text-orange-500"></i>
                 <span>Generate CoR</span>
               </a>`;
            
            viewCorContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
                 <i class="fas fa-eye w-4 h-4 text-gray-300"></i>
                 <span>View CoR</span>
               </a>`;
        }
    } else {
        // Not registered, no STM_Ref, or ST Fragmentation - disable both
        generateCorContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
             <i class="fas fa-certificate w-4 h-4 text-gray-300"></i>
             <span>Generate CoR</span>
           </a>`;
        
        viewCorContent = `<a href="#" onclick="return false;" class="dropdown-item text-gray-400 cursor-not-allowed">
             <i class="fas fa-eye w-4 h-4 text-gray-300"></i>
             <span>View CoR</span>
           </a>`;
    }
    
    dropdown.innerHTML = `
        <a href="${editHref}" ${editClick} class="dropdown-item ${editClass}">
            <i class="fas fa-edit w-4 h-4 ${editIcon}"></i>
            <span>Edit Record</span>
        </a>
        <a href="#" ${registerClick} class="dropdown-item ${registerClass}">
            <i class="fas fa-file-signature w-4 h-4 ${registerIcon}"></i>
            <span>Register Instrument</span>
        </a>
        ${generateRdsContent}
        ${viewRdsContent}
        ${generateCorContent}
        ${viewCorContent}
        <a href="#" ${deleteClick} class="dropdown-item ${deleteClass}">
            <i class="fas fa-trash w-4 h-4 ${deleteIcon}"></i>
            <span>Delete Record</span>
        </a>
    `;
}

function closeDropdown() {
    const dropdown = document.getElementById('dropdown-menu');
    const backdrop = document.getElementById('dropdown-backdrop');
    
    dropdown.classList.add('hidden');
    if (backdrop) {
        backdrop.classList.add('hidden');
    }
    
    currentDropdown = null;
    currentButton = null;
    currentAppData = null;
}

function deleteInstrument(id) {
    // Extract numeric ID if it contains underscore (e.g., "2_st_fragmentation" -> "2")
    const numericId = String(id).split('_')[0];
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send DELETE request
            fetch(`{{ url('instrument_registration/delete') }}/${numericId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire(
                        'Deleted!',
                        data.message,
                        'success'
                    ).then(() => {
                        // Reload the page to refresh the table
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        data.error || 'Failed to delete instrument',
                        'error'
                    );
                }
            })
            .catch(error => {
                // Error occurred while deleting the instrument
                Swal.fire(
                    'Error!',
                    'An error occurred while deleting the instrument',
                    'error'
                );
            });
        }
    });
}

// Function to generate RDS (Registered Document Sheet)
function generateRDS(id, stmRef) {
    // Extract numeric ID if it contains underscore (e.g., "2_st_fragmentation" -> "2")
    const numericId = String(id).split('_')[0];
    
    // Show loading indicator
    Swal.fire({
        title: 'Generating RDS',
        text: 'Please wait while we generate the Registered Document Sheet...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send request to generate RDS
    fetch(`{{ url('instrument_registration/generate-rds') }}/${numericId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            stm_ref: stmRef
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'RDS Generated',
                text: data.message || 'Registered Document Sheet has been generated successfully',
                confirmButtonText: 'View RDS'
            }).then((result) => {
                if (result.isConfirmed && data.rds_url) {
                    // Try to open in new tab, with fallback if popup is blocked
                    const newWindow = window.open(data.rds_url, '_blank');
                    
                    // Check if popup was blocked
                    if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                        // Popup was blocked, open in same tab
                        window.location.href = data.rds_url;
                    }
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Generation Failed',
                text: data.error || 'Failed to generate RDS'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while generating the RDS'
        });
    });
}

// Function to view RDS (Registered Document Sheet)
function viewRDS(id, stmRef) {
    // Extract numeric ID if it contains underscore (e.g., "2_st_fragmentation" -> "2")
    const numericId = String(id).split('_')[0];
    
    // Show loading indicator
    Swal.fire({
        title: 'Loading RDS',
        text: 'Please wait...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Send request to get RDS URL
    fetch(`{{ url('instrument_registration/view-rds') }}/${numericId}`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.success && data.rds_url) {
            // Try to open in new tab, with fallback if popup is blocked
            const newWindow = window.open(data.rds_url, '_blank');
            
            // Check if popup was blocked
            if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
                // Popup was blocked, show message with link
                Swal.fire({
                    icon: 'info',
                    title: 'Open RDS',
                    html: 'Please click the button below to view the RDS document:<br><br>' +
                          '<a href="' + data.rds_url + '" target="_blank" class="inline-block px-6 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Open RDS</a>',
                    confirmButtonText: 'OK',
                    allowOutsideClick: true
                });
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'RDS Not Available',
                text: data.message || 'RDS has not been generated yet. Would you like to generate it now?',
                showCancelButton: true,
                confirmButtonText: 'Generate Now',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    generateRDS(id, stmRef);
                }
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while loading the RDS'
        });
    });
}

// Function to show RDS generation confirmation modal
function showGenerateRDSModal(id, stmRef) {
    Swal.fire({
        title: 'Generate RDS',
        text: 'Are you sure you want to generate a Registered Document Sheet (RDS) for this instrument?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Generate RDS',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            generateRDS(id, stmRef);
        }
    });
}

// Function to show CoR generation confirmation modal
function showGenerateCoRModal(id, stmRef) {
    Swal.fire({
        title: 'Generate CoR',
        text: 'Are you sure you want to generate a Certificate of Registration (CoR) for this instrument?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Generate CoR',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            generateCoR(id, stmRef);
        }
    });
}

// Function to generate CoR (placeholder - implement as needed)
function generateCoR(id, stmRef) {
    // Show loading indicator
    Swal.fire({
        title: 'Generating CoR',
        text: 'Please wait while we generate the Certificate of Registration...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // TODO: Implement CoR generation API call
    // For now, just redirect to existing CoR page
    setTimeout(() => {
        Swal.close();
        window.open(`{{ route('coroi.index') }}?url=registered_instruments?STM_Ref=${stmRef}`, '_blank');
        
        // Refresh the data to update the dropdown state
        refreshInstrumentData();
    }, 1000);
}

// Simple function to refresh page after RDS generation
function refreshInstrumentData() {
    location.reload();
}

// Function to check RDS status and handle Generate action
async function checkRDSAndShowGenerate(instrumentId, stmRef) {
    console.log(`Checking RDS status for Generate on instrument ${instrumentId}`);
    
    try {
        // Extract numeric ID
        const numericId = String(instrumentId).split('_')[0];
        
        const response = await fetch(`/instrument_registration/rds-status/${numericId}`);
        const data = await response.json();
        
        if (data.success && data.exists) {
            // RDS already exists - Generate should be disabled
            Swal.fire({
                title: 'RDS Already Generated',
                text: 'An RDS document has already been generated for this instrument.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'View Existing RDS',
                cancelButtonText: 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    viewRDS(instrumentId, stmRef);
                }
            });
        } else {
            // No RDS exists - allow generation
            showGenerateRDSModal(instrumentId, stmRef);
        }
    } catch (error) {
        console.error('Error checking RDS status:', error);
        Swal.fire('Error', 'Failed to check RDS status', 'error');
    }
}

// Function to check RDS status and handle View action
async function checkRDSAndShowView(instrumentId, stmRef) {
    console.log(`Checking RDS status for View on instrument ${instrumentId}`);
    
    try {
        // Extract numeric ID
        const numericId = String(instrumentId).split('_')[0];
        
        const response = await fetch(`/instrument_registration/rds-status/${numericId}`);
        const data = await response.json();
        
        if (data.success && data.exists) {
            // RDS exists - allow viewing
            viewRDS(instrumentId, stmRef);
        } else {
            // No RDS exists - View should be disabled
            Swal.fire({
                title: 'No RDS Available',
                text: 'No RDS document has been generated for this instrument yet.',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Generate RDS Now',
                cancelButtonText: 'Close'
            }).then((result) => {
                if (result.isConfirmed) {
                    showGenerateRDSModal(instrumentId, stmRef);
                }
            });
        }
    } catch (error) {
        console.error('Error checking RDS status:', error);
        Swal.fire('Error', 'Failed to check RDS status', 'error');
    }
}

// Debug function to manually check RDS status (can be called from console)
window.debugRDSStatus = async function(instrumentId) {
    console.log(`=== DEBUG RDS STATUS FOR INSTRUMENT ${instrumentId} ===`);
    
    // Find instrument in data
    const instrument = serverCofoData.find(item => String(item.id).split('_')[0] === String(instrumentId));
    if (!instrument) {
        console.error(`Instrument ${instrumentId} not found in serverCofoData`);
        return;
    }
    
    console.log('Instrument data:', instrument);
    console.log('Current RDS status in cache:', instrument.rds_exists);
    
    // Check API directly
    try {
        const numericId = String(instrumentId).split('_')[0];
        const response = await fetch(`{{ url('instrument_registration/rds-status') }}/${numericId}`);
        const data = await response.json();
        
        console.log('API Response:', data);
        
        if (data.success) {
            console.log(`API says RDS exists: ${data.exists}`);
            if (data.exists !== instrument.rds_exists) {
                console.warn(`MISMATCH! Cache says ${instrument.rds_exists}, API says ${data.exists}`);
                // Update the cache
                instrument.rds_exists = data.exists;
                instrument.rds_data = data.rds || null;
                console.log('Updated cache with API data');
            }
        } else {
            console.error('API returned error:', data);
        }
    } catch (error) {
        console.error('Error calling API:', error);
    }
    
    console.log('=== END DEBUG ===');
};

// Function to force refresh all RDS statuses (can be called from console)
window.forceRefreshAllRDSStatus = async function() {
    console.log('=== FORCE REFRESH ALL RDS STATUS ===');
    if (serverCofoData && serverCofoData.length > 0) {
        try {
            serverCofoData = await checkRDSStatus(serverCofoData);
            console.log('All RDS statuses refreshed successfully');
            serverCofoData.forEach(item => {
              if (item.status === 'registered') {
                console.log(`Instrument ${item.id}: RDS exists = ${item.rds_exists}`);
              }
            });
        } catch (error) {
            console.error('Error refreshing RDS statuses:', error);
        }
    }
    console.log('=== END FORCE REFRESH ===');
};

// Function to force refresh dropdown for a specific instrument
function refreshDropdownForInstrument(instrumentId) {
    // Find the instrument in serverCofoData
    const instrument = serverCofoData.find(item => String(item.id).split('_')[0] === String(instrumentId).split('_')[0]);
    
    if (instrument) {
        // Find any open dropdown for this instrument and refresh it
        const activeDropdown = document.getElementById('dropdown-menu');
        if (activeDropdown && !activeDropdown.classList.contains('hidden')) {
            // Get the currently active app data from the dropdown's data attribute or similar
            // For now, just close the dropdown so user needs to re-open to see updated state
            closeDropdown();
        }
        
        console.log(`Refreshed dropdown data for instrument ${instrumentId}`);
    }
}

// Function to check if an instrument can be registered
function checkCanRegisterInstrument(app) {
    // For non-pending instruments, they cannot be registered
    if (app.status !== 'pending') {
        return false;
    }
    
    // For ST CofO, check if corresponding ST Assignment is registered
    if (app.instrument_type === 'Sectional Titling CofO') {
        try {
            // Check if there's a registered ST Assignment for the same file number
            const stAssignmentRegistered = serverCofoData.find(item => 
                item.fileno === app.fileno && 
                item.instrument_type === 'ST Assignment (Transfer of Title)' && 
                item.status === 'registered'
            );
            
            return !!stAssignmentRegistered;
        } catch (error) {
            // Error checking ST Assignment registration
            return false;
        }
    }
    
    // For all other instrument types, allow registration if pending
    return true;
}

// Function to show ST CofO restriction message
function showSTCofoRestrictionMessage() {
    Swal.fire({
        title: 'Registration Restriction',
        html: `
            <div class="text-left">
                <p class="mb-3"><strong>ST CofO (Sectional Titling Certificate of Occupancy)</strong> cannot be registered directly.</p>
                <p class="mb-3">To register an ST CofO, you must first ensure that the corresponding <strong>ST Assignment (Transfer of Title)</strong> has been registered.</p>
                <div class="bg-blue-50 p-3 rounded-lg mt-4">
                    <p class="text-sm text-blue-800"><i class="fas fa-info-circle mr-2"></i><strong>Registration Process:</strong></p>
                    <ol class="text-sm text-blue-700 mt-2 ml-4">
                        <li>1. Register the ST Assignment (Transfer of Title) first</li>
                        <li>2. Once registered, the ST CofO will become available for registration</li>
                    </ol>
                </div>
            </div>
        `,
        icon: 'info',
        confirmButtonText: 'I Understand',
        confirmButtonColor: '#3085d6',
        width: '500px'
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown-wrapper') && !e.target.closest('#dropdown-menu')) {
        closeDropdown();
    }
});

// Close dropdown when clicking backdrop
const backdrop = document.getElementById('dropdown-backdrop');
if (backdrop) {
    backdrop.addEventListener('click', closeDropdown);
}

// Close dropdown on scroll and resize
window.addEventListener('scroll', closeDropdown);
window.addEventListener('resize', closeDropdown);

// Close dropdown on table scroll
document.querySelector('.table-container')?.addEventListener('scroll', closeDropdown);

// Close dropdown on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDropdown();
    }
});

// Function to update checkbox states based on ST Assignment registration status
function updateCheckboxStates() {
    // updateCheckboxStates called
    const checkboxes = document.querySelectorAll('.main-table-checkbox');
    // Found checkboxes: ${checkboxes.length}
    
    // Debug: Log all available ST Assignments
    const stAssignments = serverCofoData.filter(item => 
        item.instrument_type === 'ST Assignment (Transfer of Title)' && 
        item.status === 'registered'
    );
    // Available registered ST Assignments logged
    
    let stCofoCount = 0;
    let enabledCount = 0;
    let stAssignmentCount = 0;
    
    checkboxes.forEach((checkbox, index) => {
        const instrumentType = checkbox.getAttribute('data-instrument-type');
        const status = checkbox.getAttribute('data-status');
        const fileno = checkbox.getAttribute('data-fileno');
        const id = checkbox.getAttribute('data-id');
        
        // Checkbox ${index}: ID=${id}, Type=${instrumentType}, Status=${status}, FileNo=${fileno}
        
        // Handle ST Assignment (Transfer of Title) - should be enabled when pending
        if (status === 'pending' && instrumentType === 'ST Assignment (Transfer of Title)') {
            stAssignmentCount++;
            // Processing ST Assignment: ID=${id}, FileNo=${fileno}, Status=${status}
            
            // ST Assignment should always be enabled when pending (not registered yet)
            checkbox.disabled = false;
            enabledCount++;
            // Enabled ST Assignment checkbox for ${fileno}
        }
        
        // Handle ST CofO (Sectional Titling CofO) - should be enabled only if corresponding ST Assignment is registered
        else if (status === 'pending' && instrumentType === 'Sectional Titling CofO') {
            stCofoCount++;
            // Processing ST CofO: ID=${id}, FileNo=${fileno}, Status=${status}
            
            // Check if there's a registered ST Assignment for the same file number
            const stAssignmentRegistered = serverCofoData.find(item => {
                const match = item.fileno === fileno && 
                             item.instrument_type === 'ST Assignment (Transfer of Title)' && 
                             item.status === 'registered';
                
                if (item.fileno === fileno && item.instrument_type === 'ST Assignment (Transfer of Title)') {
                    // Found matching fileno ${fileno}: Type=${item.instrument_type}, Status=${item.status}, Match=${match}
                }
                
                return match;
            });
            
            // ST Assignment found for ${fileno}: ${!!stAssignmentRegistered}
            if (stAssignmentRegistered) {
                // ST Assignment details logged
            }
            
            // Enable/disable checkbox based on ST Assignment registration status
            const shouldEnable = !!stAssignmentRegistered;
            checkbox.disabled = !shouldEnable;
            
            // Add visual indicator for disabled ST CofO checkboxes
            const row = checkbox.closest('tr');
            if (row) {
                if (!shouldEnable) {
                    row.classList.add('st-cofo-disabled');
                    // Add a tooltip or visual indicator
                    checkbox.title = 'This ST CofO cannot be registered until the corresponding ST Assignment is registered first';
                } else {
                    row.classList.remove('st-cofo-disabled');
                    checkbox.title = '';
                }
            }
            
            if (shouldEnable) {
                enabledCount++;
                // Enabled ST CofO checkbox for ${fileno}
            } else {
                // Disabled ST CofO checkbox for ${fileno} - no registered ST Assignment found
            }
            
            // If checkbox becomes disabled and was checked, uncheck it
            if (checkbox.disabled && checkbox.checked) {
                checkbox.checked = false;
                // Trigger change event to update batch registration state
                handleMainTableCheckboxChange();
            }
        }
        
        // For registered instruments, disable checkboxes (already registered)
        else if (status === 'registered') {
            checkbox.disabled = true;
            // Disabled registered instrument checkbox for ${fileno}
        }
        
        // For other pending instruments (not ST Assignment or ST CofO), enable them
        else if (status === 'pending') {
            checkbox.disabled = false;
            // Enabled other pending instrument checkbox for ${fileno}
        }
    });
    
    // ST Assignment checkboxes processed: ${stAssignmentCount}
    // ST CofO checkboxes processed: ${stCofoCount}, enabled: ${enabledCount}
    
    // Debug: Log all serverCofoData for inspection - disabled
}

// Enhanced batch registration functionality - FINAL VERSION
function handleMainTableCheckboxChange() {
    const checkedBoxes = document.querySelectorAll('.main-table-checkbox:checked:not([disabled])');
    const checkedCount = checkedBoxes.length;
    const batchBtn = document.getElementById('batchRegisterBtn');
    const batchBtnText = document.getElementById('batchBtnText');
    
    // ${checkedCount} instruments selected
    
    // Update button state and text based on selection
    if (checkedCount === 0) {
        // No selection - show default "Registration" button (enabled)
        batchBtnText.textContent = 'Registration';
        // Button stays enabled for normal batch registration
    } else if (checkedCount === 1) {
        // Single selection - show "Registration" button (enabled)
        batchBtnText.textContent = 'Registration';
        // Button stays enabled for normal batch registration
    } else {
        // Multiple selection - show "Batch Registration" button (enabled)
        batchBtnText.textContent = 'Batch Registration';
        
        // Get the selected instrument data
        const selectedInstruments = Array.from(checkedBoxes).map(checkbox => {
            const id = checkbox.getAttribute('data-id');
            const status = checkbox.getAttribute('data-status');
            
            // Find the instrument data from serverCofoData
            const instrumentData = serverCofoData.find(item => String(item.id) === String(id));
            
            if (instrumentData) {
                return {
                    id: instrumentData.id,
                    fileNo: instrumentData.fileno,
                    grantor: instrumentData.Grantor || '',
                    grantee: instrumentData.Grantee || '',
                    status: status,
                    instrumentType: instrumentData.instrument_type || '',
                    lga: instrumentData.lga || '',
                    district: instrumentData.district || '',
                    plotNumber: instrumentData.plotNumber || '',
                    plotSize: instrumentData.size || '',
                    plotDescription: instrumentData.propertyDescription || '',
                    duration: instrumentData.duration || instrumentData.leasePeriod || '',
                    deeds_date: instrumentData.deeds_date || instrumentData.instrumentDate || '',
                    deeds_time: instrumentData.deeds_time || '',
                    rootRegistrationNumber: instrumentData.rootRegistrationNumber || instrumentData.Deeds_Serial_No || '',
                    solicitorName: instrumentData.solicitorName || '',
                    solicitorAddress: instrumentData.solicitorAddress || '',
                    landUseType: instrumentData.landUseType || instrumentData.land_use || ''
                };
            }
            return null;
        }).filter(item => item !== null);
        
        // Store selected instruments for the batch modal
        if (typeof window.setSelectedInstrumentsForBatch === 'function') {
            window.setSelectedInstrumentsForBatch(selectedInstruments);
        }
    }
    
    // Reset batch modal if needed (when checkboxes are unchecked)
    if (typeof window.resetBatchModalIfNeeded === 'function') {
        window.resetBatchModalIfNeeded();
    }
}

// Update the toggleSelectAll function to work with the new checkbox class
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.main-table-checkbox:not([disabled])');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    
    // Trigger the batch registration check
    handleMainTableCheckboxChange();
}
</script>

<!-- Pagination and Search Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Pagination variables
    let currentPage = 1;
    let recordsPerPage = 25;
    let filteredData = [...serverCofoData];
    let totalRecords = filteredData.length;
    
    // Get DOM elements
    const recordsPerPageSelect = document.getElementById('recordsPerPage');
    const searchInput = document.getElementById('searchInput');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const pageNumbers = document.getElementById('pageNumbers');
    const showingStart = document.getElementById('showingStart');
    const showingEnd = document.getElementById('showingEnd');
    const showingTotal = document.getElementById('showingTotal');
    const tableBody = document.getElementById('cofoTableBody');
    
    // Initialize pagination
    function initializePagination() {
        recordsPerPage = parseInt(recordsPerPageSelect.value);
        updateTable();
        updatePaginationControls();
    }
    
    // Filter data based on search
    function filterData() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        
        if (searchTerm === '') {
            filteredData = [...serverCofoData];
        } else {
            filteredData = serverCofoData.filter(item => {
                return (item.fileno && item.fileno.toLowerCase().includes(searchTerm)) ||
                       (item.Grantor && item.Grantor.toLowerCase().includes(searchTerm)) ||
                       (item.Grantee && item.Grantee.toLowerCase().includes(searchTerm)) ||
                       (item.lga && item.lga.toLowerCase().includes(searchTerm)) ||
                       (item.district && item.district.toLowerCase().includes(searchTerm)) ||
                       (item.plotNumber && item.plotNumber.toLowerCase().includes(searchTerm)) ||
                       (item.instrument_type && item.instrument_type.toLowerCase().includes(searchTerm));
            });
        }
        
        totalRecords = filteredData.length;
        currentPage = 1; // Reset to first page when filtering
        updateTable();
        updatePaginationControls();
    }
    
    // Update table with current page data
    function updateTable() {
        const startIndex = (currentPage - 1) * recordsPerPage;
        const endIndex = startIndex + recordsPerPage;
        const pageData = filteredData.slice(startIndex, endIndex);
        
        // Clear existing table body
        tableBody.innerHTML = '';
        
        if (pageData.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="15" class="px-6 py-10 text-center text-gray-500">
                        No instrument registrations found.
                    </td>
                </tr>
            `;
            return;
        }
        
        // Populate table with page data
        pageData.forEach(app => {
            // Use the same logic as the checkCanRegisterInstrument function for checkboxes
            let isDisabled = app.status === 'registered';
            
            // For ST CofO, check if corresponding ST Assignment is registered
            if (app.status === 'pending' && app.instrument_type === 'Sectional Titling CofO') {
                // Check if there's a registered ST Assignment for the same file number
                const stAssignmentRegistered = serverCofoData.find(item => 
                    item.fileno === app.fileno && 
                    item.instrument_type === 'ST Assignment (Transfer of Title)' && 
                    item.status === 'registered'
                );
                
                // Disable checkbox if ST Assignment is not registered
                isDisabled = !stAssignmentRegistered;
            }
            
            // Format dates
            const capturedDate = app.captured_date ? 
                `<div class="flex items-center">
                    <i class="fas fa-calendar-plus text-blue-400 mr-2"></i>
                    ${new Date(app.captured_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}
                </div>` : 
                '<span class="text-gray-400">N/A</span>';
                
            const regDate = app.reg_date ? 
                `<div class="flex items-center">
                    <i class="fas fa-calendar-check text-green-400 mr-2"></i>
                    ${new Date(app.reg_date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' })}
                </div>` : 
                '<span class="text-gray-400">N/A</span>';
            
            // Format reg particulars
            let regParticulars = '<span class="text-gray-400 text-xs">N/A</span>';
            if (app.status === 'registered') {
                if (app.instrument_type === 'ST Fragmentation') {
                    regParticulars = '<span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-md font-mono text-xs">0/0/0</span>';
                } else {
                    regParticulars = `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-md font-mono text-xs">${app.Deeds_Serial_No || 'N/A'}</span>`;
                }
            }
            
            // Format parent file number
            let parentFileNo = '<span class="text-gray-400">N/A</span>';
            if (app.instrument_type === 'ST Fragmentation') {
                parentFileNo = `<span class="file-number">${app.parent_fileNo || 'N/A'}</span>`;
            } else if (['ST Assignment (Transfer of Title)', 'Sectional Titling CofO'].includes(app.instrument_type)) {
                parentFileNo = `<span class="file-number">${app.parent_fileNo || app.fileno || 'N/A'}</span>`;
            }
            
            // Format instrument type badge
            let instrumentTypeBadge = '';
            if (app.instrument_type === 'ST Fragmentation') {
                instrumentTypeBadge = '<span class="badge badge-st-fragmentation"><i class="fas fa-puzzle-piece mr-1"></i>ST Fragmentation</span>';
            } else if (app.instrument_type === 'ST Assignment (Transfer of Title)') {
                instrumentTypeBadge = '<span class="badge badge-st-assignment"><i class="fas fa-exchange-alt mr-1"></i>ST Assignment (Transfer of Title)</span>';
            } else if (app.instrument_type === 'Sectional Titling CofO') {
                instrumentTypeBadge = '<span class="badge badge-sectional-titling"><i class="fas fa-building mr-1"></i>ST CofO</span>';
            } else {
                instrumentTypeBadge = `<span class="badge badge-other-instrument"><i class="fas fa-file-alt mr-1"></i>${app.instrument_type || 'Other'}</span>`;
            }
            
            const row = `
                <tr class="cofo-row" data-status="${app.status}" data-id="${app.id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox" class="rounded main-table-checkbox" 
                               data-id="${app.id}" 
                               data-status="${app.status}"
                               data-instrument-type="${app.instrument_type}"
                               data-fileno="${app.fileno}"
                               ${isDisabled ? 'disabled' : ''}
                               onchange="handleMainTableCheckboxChange()">
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${regParticulars}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${capturedDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${regDate}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="file-number">${app.fileno || 'N/A'}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${parentFileNo}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="status-badge badge-${app.status}">${app.status.charAt(0).toUpperCase() + app.status.slice(1)}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">${instrumentTypeBadge}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${app.Grantor || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">${app.Grantee || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${app.lga || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${app.district || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${app.plotNumber || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">${app.size || 'N/A'}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <div class="dropdown-wrapper">
                            <button class="action-button text-gray-500 hover:text-gray-700 p-2 rounded-md transition-colors duration-200"
                                    onclick="toggleDropdown(this, '${app.id}')" type="button">
                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            
            tableBody.insertAdjacentHTML('beforeend', row);
        });
        
        // Re-initialize Lucide icons for new content
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Update checkbox states after rendering the table
        setTimeout(() => {
            updateCheckboxStates();
        }, 50);
        
        // Update showing information
        const start = totalRecords === 0 ? 0 : startIndex + 1;
        const end = Math.min(endIndex, totalRecords);
        
        showingStart.textContent = start;
        showingEnd.textContent = end;
        showingTotal.textContent = totalRecords;
        
        // Update total records count in header
        document.getElementById('totalRecordsCount').textContent = `${totalRecords} Total Records`;
    }
    
    // Update pagination controls
    function updatePaginationControls() {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        
        // Update previous/next buttons
        prevPageBtn.disabled = currentPage === 1;
        nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
        
        // Generate page numbers
        pageNumbers.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        // Adjust start page if we're near the end
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // Add first page and ellipsis if needed
        if (startPage > 1) {
            addPageButton(1);
            if (startPage > 2) {
                pageNumbers.insertAdjacentHTML('beforeend', '<span class="px-2 py-1 text-gray-500">...</span>');
            }
        }
        
        // Add visible page numbers
        for (let i = startPage; i <= endPage; i++) {
            addPageButton(i);
        }
        
        // Add ellipsis and last page if needed
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                pageNumbers.insertAdjacentHTML('beforeend', '<span class="px-2 py-1 text-gray-500">...</span>');
            }
            addPageButton(totalPages);
        }
    }
    
    // Add page button
    function addPageButton(pageNum) {
        const isActive = pageNum === currentPage;
        const button = document.createElement('button');
        button.className = `px-3 py-2 text-sm font-medium rounded-md ${
            isActive 
                ? 'bg-blue-600 text-white' 
                : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-50'
        }`;
        button.textContent = pageNum;
        button.onclick = () => goToPage(pageNum);
        pageNumbers.appendChild(button);
    }
    
    // Go to specific page
    function goToPage(page) {
        const totalPages = Math.ceil(totalRecords / recordsPerPage);
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            updateTable();
            updatePaginationControls();
        }
    }
    
    // Event listeners
    recordsPerPageSelect.addEventListener('change', initializePagination);
    searchInput.addEventListener('input', filterData);
    prevPageBtn.addEventListener('click', () => goToPage(currentPage - 1));
    nextPageBtn.addEventListener('click', () => goToPage(currentPage + 1));
    
    // Initialize
    initializePagination();
    
    // Update checkbox states after page loads
    setTimeout(() => {
        updateCheckboxStates();
    }, 100);
});

// Also call updateCheckboxStates when the page is fully loaded
window.addEventListener('load', function() {
    setTimeout(() => {
        updateCheckboxStates();
    }, 200);
});
</script>

@endsection

