@php
    $isApproved = $PrimaryApplication->application_status === 'Approved' || 
                  $PrimaryApplication->planning_recommendation_status === 'Approved';
@endphp

<div class="relative dropdown-container">
   <!-- Dropdown Toggle Button -->
   <button type="button" class="dropdown-toggle p-2 hover:bg-gray-100 focus:outline-none rounded-full" onclick="customToggleDropdown(this, event)">
      <i data-lucide="more-horizontal" class="w-5 h-5"></i>
   </button>
   <!-- Dropdown Menu -->
   <ul class="fixed action-menu z-50 bg-white border rounded-lg shadow-lg hidden w-56">

      <li class="{{ $isApproved ? 'opacity-50 cursor-not-allowed' : '' }}">
         <a href="{{ route('sectionaltitling.viewrecorddetail')}}?id={{$PrimaryApplication->id}}&url=view" 
            class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2 {{ $isApproved ? 'pointer-events-none' : '' }}">
          <i data-lucide="edit" class="w-4 h-4 text-blue-600"></i>
          <span>Edit Application</span>
         </a>
      </li>

      <li class="{{ $isApproved ? 'opacity-50 cursor-not-allowed' : '' }}">
        <button type="button"
          onclick="{{ $isApproved ? 'return false;' : 'deleteApplication()' }}"
          class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2 {{ $isApproved ? 'bg-gray-300 text-gray-500 cursor-not-allowed' : 'text-red-600 hover:text-red-700' }}"
          {{ $isApproved ? 'aria-disabled=true tabindex=-1 disabled' : '' }}
          title="{{ $isApproved ? 'Cannot delete - Both Application Status and Planning Recommendation have been approved' : 'Delete Application' }}">
          <i data-lucide="trash-2" class="w-4 h-4"></i>
          <span>Delete Application</span>
        </button>
      </li>

      <hr class="my-2 border-gray-200">

            @php
                $ackExists = DB::connection('sqlsrv')->table('dbo.st_acknowledgement_tracking')
                    ->where('application_id', $PrimaryApplication->id)
                    ->exists();
            @endphp

            <!-- Generate Acknowledgement Sheet -->
            <li class="{{ $ackExists ? 'opacity-50 cursor-not-allowed' : '' }}">
                <button type="button"
                        class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2 {{ $ackExists ? 'pointer-events-none' : '' }}"
                        onclick="{{ $ackExists ? 'return false;' : 'generateAcknowledgement('.$PrimaryApplication->id.')' }}">
                    <i data-lucide="file-plus" class="w-4 h-4 text-purple-600"></i>
                    <span>Generate Acknowledgement Sheet</span>
                </button>
            </li>

            <!-- View Acknowledgement Sheet -->
            <li class="{{ $ackExists ? '' : 'opacity-50 cursor-not-allowed' }}">
                <a href="{{ $ackExists ? route('sectionaltitling.primary.acknowledgement', ['id' => $PrimaryApplication->id]) : '#' }}" target="_blank"
                     class="w-full text-left px-4 py-2 flex items-center space-x-2 {{ $ackExists ? 'hover:bg-gray-100' : '' }}"
                     title="{{ $ackExists ? '' : 'Generate Acknowledgement Sheet first' }}">
                    <i data-lucide="printer" class="w-4 h-4 {{ $ackExists ? 'text-indigo-600' : 'text-gray-400' }}"></i>
                    <span>View Acknowledgement Sheet</span>
                </a>
            </li>

            <hr class="my-2 border-gray-200">

  @php
    $edmsId = $PrimaryApplication->id;
    
    // Count total scannings for this application via file_indexings join
    $totalScannings = DB::connection('sqlsrv')
        ->table('scannings')
        ->join('file_indexings', 'scannings.file_indexing_id', '=', 'file_indexings.id')
        ->where('file_indexings.main_application_id', $PrimaryApplication->id)
        ->count();
    
    // Count page typings with scanning_id for this application
    $completedPageTypings = DB::connection('sqlsrv')
        ->table('pagetypings')
        ->join('file_indexings', 'pagetypings.file_indexing_id', '=', 'file_indexings.id')
        ->where('file_indexings.main_application_id', $PrimaryApplication->id)
        ->whereNotNull('pagetypings.scanning_id')
        ->count();
    
    // Page typing is completed when all scannings have been typed
    $pageTypingCompleted = ($totalScannings > 0 && $totalScannings == $completedPageTypings);
@endphp

@if($pageTypingCompleted)
    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2 opacity-50 cursor-not-allowed" disabled title="All scanned documents have been typed ({{ $completedPageTypings }}/{{ $totalScannings }})">
        <i data-lucide="folder-open" class="w-4 h-4 text-gray-500"></i>
        <span>Capture  Initial DMS</span>
    </button>
@else
    <a href="{{ route('edms.index', $edmsId) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2" title="{{ $totalScannings > 0 ? 'Page typing in progress ('.$completedPageTypings.'/'.$totalScannings.')' : 'Start document scanning and page typing' }}">
        <i data-lucide="folder-open" class="w-4 h-4 text-blue-500"></i>
        <span>Capture  Initial DMS</span>
    </a>
@endif

 
    <hr class="my-2 border-gray-200">
    @php
        $fileExists = DB::connection('sqlsrv')
            ->table('Cofo')
            ->where('mlsFNo', $PrimaryApplication->fileno)
            ->orWhere('kangisFileNo', $PrimaryApplication->fileno)
            ->orWhere('NewKANGISFileno', $PrimaryApplication->fileno)
            ->exists();
    @endphp

    <li class="{{ $fileExists ? 'opacity-50 cursor-not-allowed' : '' }}">
        <button type="button" 
            class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2"
            {{ $fileExists ? 'disabled' : '' }}
            {{ $fileExists ? 'title="File already exists in legacy CofO records"' : '' }}
            @if(!$fileExists)
            onclick="openCofoDetailsModal(
                '{{ $PrimaryApplication->id }}', 
                '{{ $PrimaryApplication->fileno}}',
                '{{ $PrimaryApplication->np_fileno ?? '' }}',
                '{{ $PrimaryApplication->applicant_type }}', 
                @if($PrimaryApplication->applicant_type == 'individual')
                    {{ json_encode(['applicant_title' => $PrimaryApplication->applicant_title ?? '', 'first_name' => $PrimaryApplication->first_name ?? '', 'middle_name' => $PrimaryApplication->middle_name ?? '', 'surname' => $PrimaryApplication->surname ?? '']) }}
                @elseif($PrimaryApplication->applicant_type == 'corporate')
                    {{ json_encode(['corporate_name' => $PrimaryApplication->corporate_name ?? '']) }}
                @elseif($PrimaryApplication->applicant_type == 'multiple')
                    {{ $PrimaryApplication->multiple_owners_names ?? '[]' }}
                @else
                    null
                @endif,
                {{ json_encode([
                    'property_house_no' => $PrimaryApplication->property_house_no ?? '',
                    'property_plot_no' => $PrimaryApplication->property_plot_no ?? '',
                    'property_street_name' => $PrimaryApplication->property_street_name ?? '',
                    'property_district' => $PrimaryApplication->property_district ?? '',
                    'property_lga' => $PrimaryApplication->property_lga ?? '',
                    'property_state' => $PrimaryApplication->property_state ?? 'Kano',
                    'land_use' => $PrimaryApplication->land_use ?? '',
                    'property_description' => $PrimaryApplication->property_description ?? ''
                ]) }}
            )"
            @endif
        >
            <i data-lucide="file-text" class="w-4 h-4 {{ $fileExists ? 'text-gray-500' : 'text-green-500' }}"></i>
            <span>Capture Extant CofO Details</span>
        </button>
    </li>
    
        
    
    

      <hr class="my-2 border-gray-200">

     @if ($PrimaryApplication->application_status == 'Approved' && 
          $PrimaryApplication->planning_recommendation_status == 'Approved' && 
          !is_null($PrimaryApplication->final_conveyance_generated) && 
          $PrimaryApplication->final_conveyance_generated == 1)
       <li>
           <a href="{{ route('sectionaltitling.sub_application', [
             'application_id' => $PrimaryApplication->id,
             'land_use' => $PrimaryApplication->land_use,
             ]) }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
           <i data-lucide="plus-square" class="w-4 h-4 text-green-500"></i>
           <span>Create Unit Application</span>
           </a>
       </li>
       @else
       <li class="opacity-50 cursor-not-allowed">
          <a href="#" class="w-full text-left px-4 py-2 flex items-center space-x-2"
             title="Final Conveyance must be generated before creating unit applications">
          <i data-lucide="plus-square" class="w-4 h-4 text-gray-500"></i>
          <span>Create Unit Application</span>
          </a>
       </li>
       @endif  

       @php
        // Check if there are sub_applications with this main_application_id in the sub_applications table
        $hasSubApplications = \DB::connection('sqlsrv')
          ->table('subapplications')
          ->where('main_application_id', $PrimaryApplication->id)
          ->exists();
      @endphp

      @if(
        ($PrimaryApplication->planning_recommendation_status == 'Pending' && $PrimaryApplication->application_status == 'Pending')
        || !$hasSubApplications
      )
        <li class="opacity-50 cursor-not-allowed">
          <a href="#" class="w-full text-left px-4 py-2 flex items-center space-x-2">
            <i data-lucide="list" class="w-4 h-4 text-gray-500"></i>
            <span>View Unit Application(s)</span>
          </a>
        </li>
      @else
        <li>
          <a href="{{ route('sectionaltitling.units') }}?main_application_id={{ $PrimaryApplication->id }}" class="w-full text-left px-4 py-2 hover:bg-gray-100 flex items-center space-x-2">
            <i data-lucide="list" class="w-4 h-4 text-blue-600"></i>
            <span>View Unit Application(s)</span>
          </a>
        </li>
      @endif  
   </ul>
 </div>
 <script>
   function customToggleDropdown(button, event) {
      event.stopPropagation();
      const currentDropdown = button.closest('.dropdown-container').querySelector('.action-menu');
      const isCurrentlyHidden = currentDropdown.classList.contains('hidden');
      
      // Close all other dropdowns first
      const allDropdowns = document.querySelectorAll('.action-menu');
      allDropdowns.forEach(dropdown => {
         dropdown.classList.add('hidden');
      });
      
      // If the current dropdown was hidden, show it
      if (isCurrentlyHidden) {
         currentDropdown.classList.remove('hidden');
         
         // Get button position
         const rect = button.getBoundingClientRect();
         
         // Position dropdown above the button
         currentDropdown.style.top = (rect.top - currentDropdown.offsetHeight - 5) + 'px';
         currentDropdown.style.left = (rect.left - currentDropdown.offsetWidth + rect.width) + 'px';
         
         // Check if dropdown would appear off the top of the screen
         if (rect.top - currentDropdown.offsetHeight < 0) {
            // If so, position it below the button instead
            currentDropdown.style.top = (rect.bottom + 5) + 'px';
         }
      }
   }
   
   // Close dropdown when clicking outside
   document.addEventListener('click', function (event) {
      const dropdowns = document.querySelectorAll('.action-menu');
      dropdowns.forEach(dropdown => {
         if (!dropdown.contains(event.target) && 
            !dropdown.closest('.dropdown-container').querySelector('.dropdown-toggle').contains(event.target)) {
            dropdown.classList.add('hidden');
         }
      });
   });

    // Delete Application Function
    function deleteApplication() {
        const applicationId = {{ $PrimaryApplication->id ?? 'null' }};
        
        Swal.fire({
            title: 'Delete Application',
            text: 'Are you sure you want to delete this application? This action cannot be undone!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Deleting...',
                    html: 'Please wait while we delete the application',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Send delete request
                fetch(`{{ route('sectionaltitling.delete', '') }}/${applicationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Application has been deleted successfully.',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Redirect to applications list
                            window.location.href = '{{ route("sectionaltitling.index") }}';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete application'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred while deleting the application.'
                    });
                });
            }
        });
    }

   </script>
   <script>
    function generateAcknowledgement(appId) {
        const base = `{{ url('/sectionaltitling/primary/acknowledgement/generate') }}`;
        fetch(`${base}/${appId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({})
        })
        .then(resp => resp.json())
        .then(data => {
            if (data.success) {
                // Open the print page for the first print, then refresh the list to update menu state
                const viewBase = `{{ url('/sectionaltitling/primary/acknowledgement') }}`;
                window.open(`${viewBase}/${appId}`, '_blank');
                setTimeout(() => window.location.reload(), 600);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to generate Acknowledgement' });
            }
        })
        .catch(err => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Unexpected error generating Acknowledgement' });
        });
    }
   </script>