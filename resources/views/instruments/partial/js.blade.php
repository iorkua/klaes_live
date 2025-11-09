<script>
    //initiat  lucide
    document.addEventListener("DOMContentLoaded", function() {
      lucide.createIcons();
    });

    // DOM Elements
    const searchInputs = document.querySelectorAll('input[placeholder="Search instruments..."]');
    const captureNewBtn = document.querySelector('.btn-primary');
    const exportBtn = document.querySelector('.btn-outline');
    const actionBtns = document.querySelectorAll('td button');
    const paginationBtns = document.querySelectorAll('.p-4.border-t button');

    // Modal Elements
    const instrumentTypeModal = document.getElementById('instrument-type-modal');
    const closeModalBtn = document.getElementById('close-modal-btn');
    const cancelBtn = document.getElementById('cancel-btn');
    const continueBtn = document.getElementById('continue-btn');
    const instrumentOptions = document.querySelectorAll('.instrument-option');
    
    // Power of Attorney Form Modal Elements
    const poaFormModal = document.getElementById('poa-form-modal');
    const closePoaFormBtn = document.getElementById('close-poa-form-btn');
    const cancelFormBtn = document.getElementById('cancel-form-btn');
    const poaForm = document.getElementById('poa-form');
    const generateParticularsBtn = document.getElementById('generate-particulars-btn');

    // State variables
    let selectedInstrumentType = null;

    // Event Listeners
    searchInputs.forEach(input => {
      input.addEventListener('input', (e) => {
        console.log('Searching for:', e.target.value);
        // In a real app, you would filter the table based on the search term
      });
    });

    captureNewBtn.addEventListener('click', () => {
      console.log('Capture New Instrument clicked');
      // Show the instrument type selection modal
      instrumentTypeModal.classList.remove('hidden');
    });

    exportBtn.addEventListener('click', () => {
      console.log('Export clicked');
      // In a real app, you would export the data to CSV or Excel
    });

    actionBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        const row = e.target.closest('tr');
        const fileNo = row.querySelector('td').textContent;
        console.log('Actions for:', fileNo);
        // In a real app, you would show a dropdown menu with actions
      });
    });

    paginationBtns.forEach(btn => {
      btn.addEventListener('click', (e) => {
        if (!btn.disabled) {
          console.log('Pagination clicked');
          // In a real app, you would navigate to the next/previous page
        }
      });
    });

    // Modal Event Listeners
    closeModalBtn.addEventListener('click', () => {
      instrumentTypeModal.classList.add('hidden');
      resetModalSelection();
    });

    cancelBtn.addEventListener('click', () => {
      instrumentTypeModal.classList.add('hidden');
      resetModalSelection();
    });

    // Close modal when clicking outside
    instrumentTypeModal.addEventListener('click', (e) => {
      if (e.target === instrumentTypeModal) {
        instrumentTypeModal.classList.add('hidden');
        resetModalSelection();
      }
    });

    // Handle instrument type selection
    instrumentOptions.forEach(option => {
      option.addEventListener('click', () => {
        // Remove selected class from all options
        instrumentOptions.forEach(opt => {
          opt.classList.remove('bg-blue-50', 'border-blue-300');
        });
        
        // Add selected class to clicked option
        option.classList.add('bg-blue-50', 'border-blue-300');
        
        // Get the instrument type
        const instrumentType = option.querySelector('h3').textContent;
        selectedInstrumentType = instrumentType;
        
        // Enable the continue button
        continueBtn.classList.remove('bg-gray-500', 'hover:bg-gray-600');
        continueBtn.classList.add('bg-black', 'hover:bg-black/90');
        continueBtn.disabled = false;
      });
    });

    // Handle continue button click
    continueBtn.addEventListener('click', () => {
      if (selectedInstrumentType) {
        console.log('Selected instrument type:', selectedInstrumentType);
        instrumentTypeModal.classList.add('hidden');
        
        // If Power of Attorney or Irrevocable Power of Attorney is selected, show the Power of Attorney form
        if (selectedInstrumentType === 'Power of Attorney' || selectedInstrumentType === 'Irrevocable Power of Attorney') {
          poaFormModal.classList.remove('hidden');
          
          // Update the form title based on the selected instrument type
          const formTitle = document.querySelector('#poa-form-modal h2');
          formTitle.textContent = `Register ${selectedInstrumentType}`;
        } else {
          // For other instrument types, show an alert (in a real app, you would show the appropriate form)
          alert(`You selected: ${selectedInstrumentType}. In a real app, this would proceed to the instrument details form.`);
        }
        
        resetModalSelection();
      }
    });

    // Handle particulars registration number generation
    if (generateParticularsBtn) {
      generateParticularsBtn.addEventListener('click', async function() {
        try {
          // Show a loading indicator or disable the button
          generateParticularsBtn.disabled = true;
          generateParticularsBtn.innerHTML = 'Generating...';
          
          // Get CSRF token from meta tag
          const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
          console.log('CSRF Token:', csrfToken); // Debug
          
          const response = await fetch('{{ url("api/instruments/generate-particulars") }}', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
              'Accept': 'application/json'
            },
            // Adding empty body as POST requests should have a body
            body: JSON.stringify({})
          });
          
          if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', response.status, errorText);
            throw new Error(`Request failed with status ${response.status}: ${errorText.substring(0, 100)}...`);
          }
          
          const data = await response.json();
          console.log('Response data:', data);
          
          if (data.success) {
            document.getElementById('rootRegistrationNumber').value = data.rootRegistrationNumber;
            
            // Store the particulars details in hidden fields
            const serialNoInput = document.getElementById('serial_no') || document.createElement('input');
            serialNoInput.type = 'hidden';
            serialNoInput.id = 'serial_no';
            serialNoInput.name = 'serial_no';
            serialNoInput.value = data.serial_no;
            
            const pageNoInput = document.getElementById('page_no') || document.createElement('input');
            pageNoInput.type = 'hidden';
            pageNoInput.id = 'page_no';
            pageNoInput.name = 'page_no';
            pageNoInput.value = data.page_no;
            
            const volumeNoInput = document.getElementById('volume_no') || document.createElement('input');
            volumeNoInput.type = 'hidden';
            volumeNoInput.id = 'volume_no';
            volumeNoInput.name = 'volume_no';
            volumeNoInput.value = data.volume_no;
            
            // Append hidden fields to form if they don't exist
            const form = document.getElementById('poa-form');
            if (!document.getElementById('serial_no')) form.appendChild(serialNoInput);
            if (!document.getElementById('page_no')) form.appendChild(pageNoInput);
            if (!document.getElementById('volume_no')) form.appendChild(volumeNoInput);
            
            // Keep the button disabled to prevent multiple generations
            generateParticularsBtn.innerHTML = 'Number Generated';
            generateParticularsBtn.disabled = true;
            generateParticularsBtn.classList.add('bg-gray-500', 'hover:bg-gray-500');
            generateParticularsBtn.classList.remove('bg-black', 'hover:bg-black/90');
          } else {
            alert(data.message || 'Failed to generate root particulars registration number');
            // Reset button state to allow retry
            generateParticularsBtn.disabled = false;
            generateParticularsBtn.innerHTML = 'Generate Number';
          }
        } catch (error) {
          console.error('Error generating root particulars registration number:', error);
          alert('An error occurred while generating the particulars registration number: ' + error.message);
          // Reset button state to allow retry
          generateParticularsBtn.disabled = false;
          generateParticularsBtn.innerHTML = 'Generate Number';
        } finally {
          // Reset button state
          generateParticularsBtn.disabled = false;
          generateParticularsBtn.innerHTML = 'Generate Number';
        }
      });
    }

    // Power of Attorney Form Event Listeners
    closePoaFormBtn.addEventListener('click', () => {
      poaFormModal.classList.add('hidden');
    });

    cancelFormBtn.addEventListener('click', () => {
      poaFormModal.classList.add('hidden');
    });

    // Close form modal when clicking outside
    poaFormModal.addEventListener('click', (e) => {
      if (e.target === poaFormModal) {
        poaFormModal.classList.add('hidden');
      }
    });

    // Simple form pre-submit handler to update file numbers
    if (poaForm) {
      poaForm.addEventListener('submit', function(e) {
        // Check if registration number is generated
        const rootRegistrationNumber = document.getElementById('rootRegistrationNumber').value;
        if (rootRegistrationNumber === '0/0/0') {
          e.preventDefault();
          alert('Please generate a registration number before submitting the form.');
          return false;
        }
        
        // Make sure file number data is updated before submission
        if (typeof updateFormFileData === 'function') {
          updateFormFileData();
        }
        
        // Continue with normal form submission
        return true;
      });
    }

    // Reset modal selection
    function resetModalSelection() {
      selectedInstrumentType = null;
      instrumentOptions.forEach(opt => {
        opt.classList.remove('bg-blue-50', 'border-blue-300');
      });
      continueBtn.classList.remove('bg-black', 'hover:bg-black/90');
      continueBtn.classList.add('bg-gray-500', 'hover:bg-gray-600');
      continueBtn.disabled = true;
    }
  </script>