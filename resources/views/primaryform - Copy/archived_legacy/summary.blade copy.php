<div class="form-section" id="step6">
    <div class="p-6">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-center text-gray-800">MINISTRY OF LAND AND PHYSICAL PLANNING</h2>
        <button id="closeModal4" class="text-gray-500 hover:text-gray-700">
          <i data-lucide="x" class="w-5 h-5"></i>
        </button>
      </div>
       
      <div class="mb-6">
        <div class="flex items-center mb-2">
          <i data-lucide="file-text" class="w-5 h-5 mr-2 text-green-600"></i>
          <h3 class="text-lg font-bold">Application for Sectional Titling - Main Application</h3>
          <div class="ml-auto flex items-center">
            <span class="text-gray-600 mr-2">Land Use:</span>
            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-sm">
              @if (request()->query('landuse') === 'Commercial')
                Commercial
              @elseif (request()->query('landuse') === 'Residential')
                Residential
              @elseif (request()->query('landuse') === 'Industrial')
                Industrial
              @else
                Mixed Use
              @endif
            </span>
          </div>
        </div>
        <p class="text-gray-600">Complete the form below to submit a new primary application for sectional titling</p>
      </div>

      <div class="flex items-center mb-8">
        <div class="flex items-center mr-4">
          <div class="step-circle inactive">1</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive">2</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive">3</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive">4</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive">5</div>
        </div>
        <div class="flex items-center">
          <div class="step-circle active">6</div>
        </div>
        <div class="ml-4">Step 6 - Summary</div>
      </div>

      <div class="mb-6" id="application-summary">
        <div class="flex items-start mb-4">
          <i data-lucide="file-text" class="w-5 h-5 mr-2 text-green-600"></i>
          <span class="font-medium">Application Summary</span>
        </div>
        
        <div class="border border-gray-200 rounded-md p-6 mb-6">
          <div class="grid grid-cols-2 gap-6">
            <div>
              <h4 class="font-medium mb-4">Applicant Information</h4>
              <table class="w-full text-sm" id="main-owner-summary-table">
                <tr>
                  <td class="py-1 text-gray-600">Applicant Type:</td>
                  <td class="py-1 font-medium" id="summary-applicant-type">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Name:</td>
                  <td class="py-1 font-medium" id="summary-name">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Email:</td>
                  <td class="py-1 font-medium" id="summary-email">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Phone:</td>
                  <td class="py-1 font-medium" id="summary-phone">-</td>
                </tr>
              </table>
              <div id="multiple-owners-summary" class="hidden">
                <h5 class="font-medium mt-4 mb-2">Multiple Owners</h5>
                <div id="multiple-owners-list" class="space-y-2"></div>
              </div>
            </div>
            
            <div>
              <h4 class="font-medium mb-4">Unit Information</h4>
              <table class="w-full text-sm">
                <tr>
                  <td class="py-1 text-gray-600">Type of Residence:</td>
                  <td class="py-1 font-medium" id="summary-residence-type">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Block No:</td>
                  <td class="py-1 font-medium" id="summary-blocks">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Section (Floor) No:</td>
                  <td class="py-1 font-medium" id="summary-sections">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Unit No:</td>
                  <td class="py-1 font-medium" id="summary-units">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">File Number:</td>
                  <td class="py-1 font-medium" id="summary-file-number">-</td>
                </tr>
                <tr>
                  <td class="py-1 text-gray-600">Land Use:</td>
                  <td class="py-1 font-medium">
                    @if (request()->query('landuse') === 'Commercial')
                      Commercial
                    @elseif (request()->query('landuse') === 'Residential')
                      Residential
                    @elseif (request()->query('landuse') === 'Industrial')
                      Industrial
                    @else
                      Mixed Use
                    @endif
                  </td>
                </tr>
              </table>
            </div>
          </div>
        </div>
        
        <div class="mb-6">
          <h4 class="font-medium mb-4">Address Information</h4>
          <table class="w-full text-sm">
            <tr>
              <td class="py-1 text-gray-600 w-1/4">House No:</td>
              <td class="py-1 font-medium" id="summary-house-no">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Street Name:</td>
              <td class="py-1 font-medium" id="summary-street-name">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">District:</td>
              <td class="py-1 font-medium" id="summary-district">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">LGA:</td>
              <td class="py-1 font-medium" id="summary-lga">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">State:</td>
              <td class="py-1 font-medium" id="summary-state">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Complete Address:</td>
              <td class="py-1 font-medium" id="summary-full-address">-</td>
            </tr>
          </table>
        </div>
        
        <div class="mb-6">
          <div class="flex items-start mb-4">
            <i data-lucide="file-text" class="w-5 h-5 mr-2 text-green-600"></i>
            <span class="font-medium">Payment Information</span>
          </div>
          <table class="w-full text-sm">
            <tr>
              <td class="py-1 text-gray-600 w-1/4">Application Fee:</td>
              <td class="py-1 font-medium" id="summary-application-fee">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Processing Fee:</td>
              <td class="py-1 font-medium" id="summary-processing-fee">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Site Plan Fee:</td>
              <td class="py-1 font-medium" id="summary-site-plan-fee">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600 font-medium">Total:</td>
              <td class="py-1 font-bold" id="summary-total-fee">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Receipt Number:</td>
              <td class="py-1 font-medium" id="summary-receipt-number">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Payment Date:</td>
              <td class="py-1 font-medium" id="summary-payment-date">-</td>
            </tr>
          </table>
        </div>
        
        <div class="mb-6">
          <h4 class="font-medium mb-4">Property Address</h4>
          <table class="w-full text-sm">
            <tr>
              <td class="py-1 text-gray-600">House No:</td>
              <td class="py-1 font-medium" id="summary-property-house-no">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Plot No:</td>
              <td class="py-1 font-medium" id="summary-property-plot-no">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Street Name:</td>
              <td class="py-1 font-medium" id="summary-property-street-name">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">District:</td>
              <td class="py-1 font-medium" id="summary-property-district">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">LGA:</td>
              <td class="py-1 font-medium" id="summary-property-lga">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">State:</td>
              <td class="py-1 font-medium" id="summary-property-state">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Complete Address:</td>
              <td class="py-1 font-medium" id="summary-property-full-address">-</td>
            </tr>
          </table>
        </div>
        
        <div class="mb-6">
          <h4 class="font-medium mb-4">Identification</h4>
          <table class="w-full text-sm mb-4">
            <tr>
              <td class="py-1 text-gray-600 w-1/4">ID Type:</td>
              <td class="py-1 font-medium" id="summary-id-type">-</td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">ID Document:</td>
              <td class="py-1 font-medium" id="summary-id-document">-</td>
            </tr>
          </table>
        </div>
        
        <div class="mb-6">
          <h4 class="font-medium mb-4">Uploaded Documents</h4>
          <div class="grid grid-cols-2 gap-4" id="summary-documents">
            <!-- Documents will be populated dynamically -->
          </div>
        </div>
        
        <div class="flex justify-between mt-8">
          <div class="flex space-x-4">
            <button type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md" id="backStep6">Back</button>
            <button type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md flex items-center" id="printApplicationSlip">
              <i data-lucide="printer" class="w-4 h-4 mr-2"></i>
              Print Application Slip
            </button>
          </div>
          <div class="flex items-center">
            <span class="text-sm text-gray-500 mr-4">Step 6 of 6</span>
            <button type="button" class="px-4 py-2 bg-black text-white rounded-md" onclick="confirmSubmission()">Submit Application</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add event listener for the back button
      const backStep6Button = document.getElementById('backStep6');
      if (backStep6Button) {
        backStep6Button.addEventListener('click', function() {
          document.getElementById('step6').classList.remove('active');
          document.getElementById('step5').classList.add('active');
        });
      }
      
      // Initialize Print Application Slip functionality
      function initializePrintFunctionality() {
        const printButton = document.getElementById('printApplicationSlip');
        
        if (printButton) {
          printButton.addEventListener('click', function() {
            // Copy summary data to print template
            document.getElementById('print-app-id').textContent = 'APP-' + Math.floor(Math.random() * 100000);
            document.getElementById('print-date').textContent = new Date().toLocaleDateString();
            document.getElementById('print-applicant-type').textContent = document.getElementById('summary-applicant-type').textContent;
            document.getElementById('print-name').textContent = document.getElementById('summary-name').textContent;
            document.getElementById('print-email').textContent = document.getElementById('summary-email').textContent;
            document.getElementById('print-phone').textContent = document.getElementById('summary-phone').textContent;
            document.getElementById('print-address').textContent = document.getElementById('summary-full-address').textContent;
            document.getElementById('print-residence-type').textContent = document.getElementById('summary-residence-type').textContent;
            document.getElementById('print-units').textContent = document.getElementById('summary-units').textContent;
            document.getElementById('print-blocks').textContent = document.getElementById('summary-blocks').textContent;
            document.getElementById('print-sections').textContent = document.getElementById('summary-sections').textContent;
            document.getElementById('print-file-number').textContent = document.getElementById('summary-file-number').textContent;
            document.getElementById('print-application-fee').textContent = document.getElementById('summary-application-fee').textContent;
            document.getElementById('print-processing-fee').textContent = document.getElementById('summary-processing-fee').textContent;
            document.getElementById('print-site-plan-fee').textContent = document.getElementById('summary-site-plan-fee').textContent;
            document.getElementById('print-total-fee').textContent = document.getElementById('summary-total-fee').textContent;
            document.getElementById('print-receipt-number').textContent = document.getElementById('summary-receipt-number').textContent;
            document.getElementById('print-payment-date').textContent = document.getElementById('summary-payment-date').textContent;
            
            // Copy property address fields to print template
            document.getElementById('print-property-house-no').textContent = document.getElementById('summary-property-house-no').textContent;
            document.getElementById('print-property-plot-no').textContent = document.getElementById('summary-property-plot-no').textContent;
            document.getElementById('print-property-street-name').textContent = document.getElementById('summary-property-street-name').textContent;
            document.getElementById('print-property-district').textContent = document.getElementById('summary-property-district').textContent;
            document.getElementById('print-property-lga').textContent = document.getElementById('summary-property-lga').textContent;
            document.getElementById('print-property-state').textContent = document.getElementById('summary-property-state').textContent;
            document.getElementById('print-property-full-address').textContent = document.getElementById('summary-property-full-address').textContent;
            
            // Copy documents
            const printDocsContainer = document.getElementById('print-documents');
            printDocsContainer.innerHTML = '';
            
            const uploadedDocs = document.getElementById('summary-documents').children;
            for (let i = 0; i < uploadedDocs.length; i++) {
              const docDiv = document.createElement('div');
              docDiv.style.marginBottom = '5px';
              
              const docStatus = uploadedDocs[i].querySelector('span:first-child').classList.contains('bg-green-500') ? '✓' : '✗';
              const docName = uploadedDocs[i].querySelector('span:last-child').textContent;
              
              docDiv.innerHTML = `<span style="display:inline-block; width:20px; text-align:center;">${docStatus}</span> ${docName}`;
              printDocsContainer.appendChild(docDiv);
            }
            
            // Open print dialog
            const printContent = document.getElementById('printTemplate').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
              <html>
                <head>
                  <title>Application Slip</title>
                  <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    @media print {
                      body { margin: 0; padding: 0; }
                    }
                  </style>
                </head>
                <body>${printContent}</body>
              </html>
            `);
            
            printWindow.document.close();
            setTimeout(() => {
              printWindow.print();
            }, 500);
          });
        }
      }
      
      // Initialize print functionality
      initializePrintFunctionality();
    });

    // Confirmation function for final submission
    function confirmSubmission() {
      Swal.fire({
        title: 'Submit Application?',
        html: '<div style="text-align: left;"><strong>Please confirm that:</strong><br><br>' +
              '• All information provided is accurate<br>' +
              '• All required documents have been uploaded<br>' +
              '• You have reviewed the application summary<br><br>' +
              '<strong></strong></div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Submit Application',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Submit the form
          document.getElementById('primaryForm').submit();
        }
      });
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Patch: update summary for multiple owners
      function updateMultipleOwnersSummary() {
        const applicantTypeEl = document.querySelector('input[name="applicantType"]:checked');
        const applicantType = applicantTypeEl ? applicantTypeEl.value : (document.getElementById('applicantType')?.value || '');
        const mainOwnerTable = document.getElementById('main-owner-summary-table');
        const multipleOwnersDiv = document.getElementById('multiple-owners-summary');
        const multipleOwnersList = document.getElementById('multiple-owners-list');

        if (applicantType === 'multiple') {
          // Hide main owner email/phone, show multiple owners
          if (mainOwnerTable) mainOwnerTable.style.display = 'none';
          if (multipleOwnersDiv) multipleOwnersDiv.classList.remove('hidden');
          if (multipleOwnersList) {
            multipleOwnersList.innerHTML = '';
            // Collect all multiple owners from the form
            const names = document.querySelectorAll('input[name="multiple_owners_names[]"]');
            const addresses = document.querySelectorAll('textarea[name="multiple_owners_address[]"]');
            const emails = document.querySelectorAll('input[name="multiple_owners_email[]"]');
            const phones = document.querySelectorAll('input[name="multiple_owners_phone[]"]');
            const idTypes = document.querySelectorAll('input[type="radio"][name^="multiple_owners_identification_type"]');
            const idImages = document.querySelectorAll('input[name="multiple_owners_identification_image[]"]');
            // Group by index
            for (let i = 0; i < names.length; i++) {
              const ownerName = names[i]?.value || '-';
              const ownerAddress = addresses[i]?.value || '-';
              const ownerEmail = emails[i]?.value || '-';
              const ownerPhone = phones[i]?.value || '-';
              // Find checked idType for this owner
              let ownerIdType = '-';
              const idTypeRadios = document.getElementsByName(`multiple_owners_identification_type[${i}]`);
              if (idTypeRadios && idTypeRadios.length) {
                for (let r = 0; r < idTypeRadios.length; r++) {
                  if (idTypeRadios[r].checked) {
                    ownerIdType = idTypeRadios[r].value.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    break;
                  }
                }
              }
              // Get file name for ID image
              let ownerIdImage = '-';
              if (idImages[i] && idImages[i].files && idImages[i].files.length > 0) {
                ownerIdImage = idImages[i].files[0].name;
              }
              // Render owner summary
              const ownerDiv = document.createElement('div');
              ownerDiv.className = 'border border-gray-100 rounded p-2 bg-gray-50';
              ownerDiv.innerHTML = `
                <div class="font-semibold text-gray-700 mb-1">Owner ${i + 1}</div>
                <div class="text-xs"><span class="font-medium">Name:</span> ${ownerName}</div>
                <div class="text-xs"><span class="font-medium">Address:</span> ${ownerAddress}</div>
                <div class="text-xs"><span class="font-medium">Email:</span> ${ownerEmail}</div>
                <div class="text-xs"><span class="font-medium">Phone:</span> ${ownerPhone}</div>
                <div class="text-xs"><span class="font-medium">ID Type:</span> ${ownerIdType}</div>
                <div class="text-xs"><span class="font-medium">ID Document:</span> ${ownerIdImage}</div>
              `;
              multipleOwnersList.appendChild(ownerDiv);
            }
          }
        } else {
          // Show main owner, hide multiple owners
          if (mainOwnerTable) mainOwnerTable.style.display = '';
          if (multipleOwnersDiv) multipleOwnersDiv.classList.add('hidden');
        }
      }

      // Patch into summary update
      if (window.updateApplicationSummary) {
        const origUpdate = window.updateApplicationSummary;
        window.updateApplicationSummary = function() {
          origUpdate();
          updateMultipleOwnersSummary();
        }
      } else {
        updateMultipleOwnersSummary();
      }
    });
  </script>