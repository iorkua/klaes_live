// Function to select an instrument for single registration
function selectInstrumentForSingleRegistration(id, fileNo, grantor, grantee, instrumentType) {
  // Show loading state
  Swal.fire({
    title: 'Loading',
    text: 'Fetching instrument details...',
    icon: 'info',
    allowOutsideClick: false,
    showConfirmButton: false,
    willOpen: () => {
      Swal.showLoading();
    }
  });
  
  // Find the full application data from the stored data
  let application = null;
  if (window.singleRegistrationData) {
    application = window.singleRegistrationData.find(item => String(item.id) === String(id));
  }
  
  if (!application) {
    // Fallback: create application object from passed parameters
    application = {
      id: id,
      fileNo: fileNo,
      grantor: grantor,
      grantee: grantee,
      instrumentType: instrumentType,
      duration: '',
      lga: '',
      district: '',
      plotNumber: '',
      plotSize: '',
      plotDescription: '',
      status: 'pending'
    };
  }
  
  // Show the modal details section
  document.getElementById('unitSearchSection').style.display = 'none';
  document.getElementById('unitDetailsSection').style.display = 'block';
  
  // Close loading dialog
  Swal.close();
  
  // Set application data
  document.getElementById('selectedFileNo').textContent = application.fileno || application.fileNo || 'N/A';
  document.getElementById('selectedProperty').textContent = application.propertyDescription || application.plotDescription || 'No description available';
  
  // Populate form fields
  document.getElementById('formInstrumentId').value = application.id;
  document.getElementById('instrumentType').value = application.instrument_type || application.instrumentType || '';
  document.getElementById('duration').value = application.duration || '';
  document.getElementById('grantor').value = application.grantor || '';
  document.getElementById('grantee').value = application.grantee || '';
  document.getElementById('lga').value = application.lga || '';
  document.getElementById('district').value = application.district || '';
  document.getElementById('plotNumber').value = application.plotNumber || '';
  document.getElementById('plotSize').value = application.size || application.plotSize || '';
  document.getElementById('plotDescription').value = application.propertyDescription || application.plotDescription || '';
  
  // Set current date and time
  const today = new Date();
  document.getElementById('deedsDate').value = today.toISOString().split('T')[0];
  
  // Set current time (formatted as HH:MM AM/PM)
  const hours = today.getHours();
  const minutes = today.getMinutes();
  const ampm = hours >= 12 ? 'PM' : 'AM';
  const formattedHours = hours % 12 || 12;
  const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
  document.getElementById('deedsTime').value = `${formattedHours}:${formattedMinutes} ${ampm}`;
  
  // Fetch the next serial number with fresh data
  fetchNextSerialNumber().then(() => {
    console.log('Serial number fetched for single registration modal');
  }).catch(error => {
    console.error('Failed to fetch serial number for single registration:', error);
  });
}

// Updated Open single register modal function
function openSingleRegisterModalUpdated() {
  document.getElementById('singleRegisterModal').style.display = 'block';
  document.getElementById('unitSearchSection').style.display = 'block';
  document.getElementById('unitDetailsSection').style.display = 'none';
  
  // Show loading state
  const unitSearchResults = document.getElementById('unitSearchResults');
  unitSearchResults.innerHTML = `
    <tr>
      <td colspan="5" class="px-6 py-10 text-center text-gray-500">
        <i class="fas fa-spinner fa-spin mr-2"></i> Loading available instruments...
      </td>
    </tr>
  `;
  
  // Fetch all available instruments for single registration (including other instruments)
  const baseUrl = window.location.origin  ;
  fetch(`${baseUrl}/instrument_registration/get-batch-data?filter=batch`, {
    method: 'GET',
    credentials: 'same-origin'
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
  })
  .then(data => {
    console.log('Single registration data received:', data);
    
    // Clear loading state
    unitSearchResults.innerHTML = '';
    
    if (!Array.isArray(data) || data.length === 0) {
      unitSearchResults.innerHTML = `
        <tr>
          <td colspan="5" class="px-6 py-10 text-center text-gray-500">
          No pending applications found.
          </td>
        </tr>
      `;
      return;
    }
    
    // Filter only pending applications
    const pendingApplications = data.filter(item => item.status === 'pending');
    
    if (pendingApplications.length === 0) {
      unitSearchResults.innerHTML = `
        <tr>
          <td colspan="5" class="px-6 py-10 text-center text-gray-500">
            No pending applications found.
          </td>
        </tr>
      `;
      return;
    }
    
    // Populate the table with pending applications
    pendingApplications.forEach((item, index) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">${item.fileno || 'N/A'}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">${item.grantor || 'N/A'}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">${item.grantee || 'N/A'}</td>
        <td class="px-6 py-4 whitespace-nowrap text-sm">
          <span class="badge badge-pending">Pending</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
          <button class="bg-blue-600 text-white px-3 py-1 rounded-md text-sm" onclick="selectInstrumentForSingleRegistration('${item.id}', '${item.fileno}', '${item.grantor}', '${item.grantee}', '${item.instrument_type}')">Select</button>
        </td>
      `;
      unitSearchResults.appendChild(row);
    });
    
    // Store the data for later use
    window.singleRegistrationData = data;
  })
  .catch(error => {
    console.error('Error fetching single registration data:', error);
    unitSearchResults.innerHTML = `
      <tr>
        <td colspan="5" class="px-6 py-10 text-center text-red-500">
          Error loading instruments: ${error.message}
        </td>
      </tr>
    `;
  });
}

// Override the original function
if (typeof window !== 'undefined') {
  window.openSingleRegisterModal = openSingleRegisterModalUpdated;
  window.selectInstrumentForSingleRegistration = selectInstrumentForSingleRegistration;
}