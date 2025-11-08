<div class="form-section" id="step5">
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
          <div class="step-circle inactive cursor-pointer" onclick="goToStep(1)">1</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive cursor-pointer" onclick="goToStep(2)">2</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive cursor-pointer" onclick="goToStep(3)">3</div>
        </div>
        <div class="flex items-center mr-4">
          <div class="step-circle inactive cursor-pointer" onclick="goToStep(4)">4</div>
        </div>
        <div class="flex items-center">
          <div class="step-circle active cursor-pointer" onclick="goToStep(5)">5</div>
        </div>
        <div class="ml-4">Step 5 - Summary</div>
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
            <tr>
              <td class="py-1 text-gray-600">Date Captured:</td>
              <td class="py-1 font-medium" id="summary-date-captured">-</td>
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
            <button type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md" id="backStep5">Back</button>
            <button type="button" class="px-4 py-2 bg-white border border-gray-300 rounded-md flex items-center" id="printApplicationSlip">
              <i data-lucide="printer" class="w-4 h-4 mr-2"></i>
              Print Application Slip
            </button>
          </div>
          <div class="flex items-center">
            <span class="text-sm text-gray-500 mr-4">Step 5 of 5</span>
            <button type="button" class="px-4 py-2 bg-black text-white rounded-md" onclick="confirmSubmission()">Submit Application</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Add event listener for the back button
      const backStep5Button = document.getElementById('backStep5');
      if (backStep5Button) {
        backStep5Button.addEventListener('click', function() {
          document.getElementById('step5').classList.remove('active');
          document.getElementById('step4').classList.add('active');
        });
      }
      
      // Initialize Print Application Slip functionality
      function initializePrintFunctionality() {
        const printButton = document.getElementById('printApplicationSlip');
        
        if (printButton) {
          printButton.addEventListener('click', function() {
            // Collect all application data from the summary
            const data = {
              applicationId: 'APP-' + Math.floor(Math.random() * 100000),
              landUse: '{{ request()->query("landuse", "Residential") }}',
              applicantType: document.getElementById('summary-applicant-type').textContent,
              applicantName: document.getElementById('summary-name').textContent,
              applicantEmail: document.getElementById('summary-email').textContent,
              applicantPhone: document.getElementById('summary-phone').textContent,
              applicantAddress: document.getElementById('summary-full-address').textContent,
              residenceType: document.getElementById('summary-residence-type').textContent,
              units: document.getElementById('summary-units').textContent,
              blocks: document.getElementById('summary-blocks').textContent,
              sections: document.getElementById('summary-sections').textContent,
              fileNumber: document.getElementById('summary-file-number').textContent,
              propertyHouseNo: document.getElementById('summary-property-house-no').textContent,
              propertyPlotNo: document.getElementById('summary-property-plot-no').textContent,
              propertyStreet: document.getElementById('summary-property-street-name').textContent,
              propertyDistrict: document.getElementById('summary-property-district').textContent,
              propertyLGA: document.getElementById('summary-property-lga').textContent,
              propertyState: document.getElementById('summary-property-state').textContent,
              propertyFullAddress: document.getElementById('summary-property-full-address').textContent,
              applicationFee: document.getElementById('summary-application-fee').textContent,
              processingFee: document.getElementById('summary-processing-fee').textContent,
              sitePlanFee: document.getElementById('summary-site-plan-fee').textContent,
              totalFee: document.getElementById('summary-total-fee').textContent,
              receiptNumber: document.getElementById('summary-receipt-number').textContent,
              paymentDate: document.getElementById('summary-payment-date').textContent
            };

            // Collect documents
            const uploadedDocs = document.getElementById('summary-documents').children;
            const documents = [];
            for (let i = 0; i < uploadedDocs.length; i++) {
              const docName = uploadedDocs[i].querySelector('span:last-child')?.textContent;
              if (docName) {
                documents.push(docName);
              }
            }
            data.documents = documents;

            // Build URL with parameters
            const params = new URLSearchParams();
            Object.keys(data).forEach(key => {
              if (Array.isArray(data[key])) {
                data[key].forEach(item => params.append(key + '[]', item));
              } else {
                params.append(key, data[key]);
              }
            });

            // Open print page in new window
            const printUrl = '{{ route("primaryform.print") }}?' + params.toString();
            window.open(printUrl, '_blank', 'width=1024,height=768');
          });
        }
      }
      
      // Initialize print functionality
      initializePrintFunctionality();
    });

    // AJAX Confirmation function for final submission
    function confirmSubmission() {
      console.log('🚀 Confirm submission called');
      
      Swal.fire({
        title: 'Submit Application?',
        html: '<div style="text-align: left;"><strong>Please confirm that:</strong><br><br>' +
              '• All information provided is accurate<br>' +
              '• All required documents have been uploaded<br>' +
              '• You have reviewed the application summary<br><br>' +
              '<strong>The application will be submitted via AJAX without page reload.</strong></div>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#dc3545',
        confirmButtonText: 'Yes, Submit Application',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          // Force AJAX submission ONLY - no fallback
          console.log('✅ User confirmed submission - using AJAX only');
          
          // Wait a bit for FormSubmission to be available
          setTimeout(() => {
            if (typeof window.FormSubmission !== 'undefined' && window.FormSubmission.submitForm) {
              console.log('🎯 Using AJAX form submission');
              window.FormSubmission.submitForm();
            } else if (typeof FormSubmission !== 'undefined' && FormSubmission.submitForm) {
              console.log('🎯 Using global FormSubmission object');
              FormSubmission.submitForm();
            } else {
              console.error('❌ AJAX handler not loaded');
              Swal.fire({
                title: 'Error',
                text: 'AJAX form submission handler is not loaded. Please refresh the page and try again.',
                icon: 'error'
              });
            }
          }, 100); // Small delay to ensure scripts are loaded
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

<!-- Print Application Slip Template (hidden by default) -->
<div id="printTemplate" class="hidden">
  <div class="print-container">
    <div class="print-header">
      <div class="header-with-logos">
        <div class="logo-left">
          <img src="{{ asset('assets/logo/logo1.jpg') }}" alt="Nigeria Coat of Arms" class="logo-image">
        </div>
        <div class="header-text">
          <h1 class="text-xl font-bold mb-1">MINISTRY OF LAND AND PHYSICAL PLANNING</h1>
          <h2 class="text-lg font-semibold mb-4">APPLICATION FOR SECTIONAL TITLING - PRIMARY APPLICATION</h2>
        </div>
        <div class="logo-right">
          <img src="{{ asset('assets/logo/logo3.jpeg') }}" alt="Ministry Logo" class="logo-image">
        </div>
      </div>
      <div class="border-b-2 border-black mb-6"></div>
    </div>

    <div class="print-body">
      <div class="mb-4">
        <h3 class="text-lg font-bold mb-2">Application Receipt</h3>
        <div class="flex justify-between mb-2">
          <span>Application ID: <span id="print-app-id"></span></span>
          <span>Date: <span id="print-date"></span></span>
        </div>
        <div class="flex justify-between">
          <span>File Number: <span id="print-file-number"></span></span>
          <span>Land Use: 
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

      <div class="grid grid-cols-2 gap-6 mb-4">
        <div>
          <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Applicant Information</h4>
          <table class="w-full text-sm">
            <tr>
              <td class="py-1 text-gray-600 w-1/3">Applicant Type:</td>
              <td class="py-1 font-medium" id="print-applicant-type"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Name:</td>
              <td class="py-1 font-medium" id="print-name"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Email:</td>
              <td class="py-1 font-medium" id="print-email"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Phone:</td>
              <td class="py-1 font-medium" id="print-phone"></td>
            </tr>
          </table>
        </div>
        
        <div>
          <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Unit Information</h4>
          <table class="w-full text-sm">
            <tr>
              <td class="py-1 text-gray-600 w-1/2">Residence Type:</td>
              <td class="py-1 font-medium" id="print-residence-type"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Block No:</td>
              <td class="py-1 font-medium" id="print-blocks"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Section (Floor) No:</td>
              <td class="py-1 font-medium" id="print-sections"></td>
            </tr>
            <tr>
              <td class="py-1 text-gray-600">Unit No:</td>
              <td class="py-1 font-medium" id="print-units"></td>
            </tr>
          </table>
        </div>
      </div>

      <div class="mb-4">
        <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Contact Address</h4>
        <table class="w-full text-sm">
          <tr>
            <td class="py-1 text-gray-600 w-1/4">Complete Address:</td>
            <td class="py-1 font-medium" id="print-address"></td>
          </tr>
        </table>
      </div>

      <div class="mb-4">
        <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Property Address</h4>
        <table class="w-full text-sm">
          <tr>
            <td class="py-1 text-gray-600 w-1/4">House No:</td>
            <td class="py-1 font-medium" id="print-property-house-no"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Plot No:</td>
            <td class="py-1 font-medium" id="print-property-plot-no"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Street Name:</td>
            <td class="py-1 font-medium" id="print-property-street-name"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">District:</td>
            <td class="py-1 font-medium" id="print-property-district"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">LGA:</td>
            <td class="py-1 font-medium" id="print-property-lga"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">State:</td>
            <td class="py-1 font-medium" id="print-property-state"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Complete Address:</td>
            <td class="py-1 font-medium" id="print-property-full-address"></td>
          </tr>
        </table>
      </div>
      
      <div class="mb-6">
        <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Payment Information</h4>
        <table class="w-full text-sm">
          <tr>
            <td class="py-1 text-gray-600 w-1/4">Application Fee:</td>
            <td class="py-1 font-medium" id="print-application-fee"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Processing Fee:</td>
            <td class="py-1 font-medium" id="print-processing-fee"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Site Plan Fee:</td>
            <td class="py-1 font-medium" id="print-site-plan-fee"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600 font-medium">Total:</td>
            <td class="py-1 font-bold" id="print-total-fee"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Receipt Number:</td>
            <td class="py-1 font-medium" id="print-receipt-number"></td>
          </tr>
          <tr>
            <td class="py-1 text-gray-600">Payment Date:</td>
            <td class="py-1 font-medium" id="print-payment-date"></td>
          </tr>
        </table>
      </div>

      <div class="mb-6 grid grid-cols-2 gap-4">
        <div>
          <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">Required Documents</h4>
          <div id="print-documents" class="text-sm">
            <!-- Documents will be populated dynamically -->
          </div>
        </div>
        <div>
          <h4 class="font-medium mb-2 border-b border-gray-300 pb-1">For Official Use Only</h4>
          <div class="mt-4">
            <div class="border-t border-gray-300 pt-4 mt-4">
              <div class="text-center">
                <p>Signature & Stamp</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="print-footer mt-6 text-center text-sm">
      <p>This is an official application receipt. Please keep for your records.</p>
      <p>Application submitted on: <span id="print-submission-date"></span></p>
    </div>

    
  </div>
</div>

<style>
@page {
    size: A4 landscape;
    margin: 8mm;
}

.header-with-logos {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.logo-left, .logo-right {
    flex: 0 0 60px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.header-text {
    flex: 1;
    text-align: center;
    padding: 0 15px;
}

/* Remove any text-shadow from logo-image and header-text */
.logo-image {
    width: 50px;
    height: 50px;
    object-fit: contain;
    /* text-shadow: none; */ /* Not needed */
}

.header-text, .header-text h1, .header-text h2 {
    text-shadow: none !important;
}

@media print {
    html, body {
        margin: 0;
        padding: 0;
        width: 297mm;
        height: 210mm;
        overflow: hidden;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* Hide everything first using display to avoid layered ghost rendering */
    body > * { display: none !important; }

    /* Only show the print template */
    #printTemplate { 
        display: block !important; 
        position: static !important; 
        width: 100%;
        height: auto; 
        text-shadow: none !important;
    }
    #printTemplate * { 
        text-shadow: none !important; 
        -webkit-font-smoothing: antialiased !important; 
        -moz-osx-font-smoothing: grayscale !important; 
    }

    /* Container layout */
    .print-container {
      padding: 6mm;
      width: 100%;
      min-height: 100%;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      page-break-inside: avoid;
    }
    
    /* Ensure all text elements have clean rendering */
    .print-container * {
        text-shadow: none !important;
        -webkit-text-shadow: none !important;
        -moz-text-shadow: none !important;
        -ms-text-shadow: none !important;
        -o-text-shadow: none !important;
        -webkit-font-smoothing: antialiased !important;
        -moz-osx-font-smoothing: grayscale !important;
        text-rendering: optimizeLegibility !important;
    }
    .print-header {
        flex-shrink: 0;
        margin-bottom: 6px;
    }
  .print-body {
    flex: 1;
    font-size: 9px;
    line-height: 1.15;
    overflow: hidden;
  }
    .print-footer {
        flex-shrink: 0;
        margin-top: 6px;
        font-size: 8px;
    }
    .header-with-logos {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
        width: 100%;
    }
    .logo-left, .logo-right {
        flex: 0 0 60px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .header-text {
        flex: 1;
        text-align: center;
        padding: 0 15px;
        text-shadow: none !important;
    }
    .header-text h1 {
        font-size: 14px;
        margin-bottom: 2px;
        font-weight: bold;
        text-shadow: none !important;
        -webkit-text-shadow: none !important;
        -moz-text-shadow: none !important;
    }
    .header-text h2 {
        font-size: 12px;
        margin-bottom: 0;
        font-weight: 600;
        text-shadow: none !important;
        -webkit-text-shadow: none !important;
        -moz-text-shadow: none !important;
    }
  .logo-image {
    width: 50px;
    height: 50px;
    object-fit: contain;
    display: block;
  }
    .no-print {
      display: none;
    }
    
    /* Compact layout for single page */
    .grid.grid-cols-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 4px;
    }
    
    .print-body h3 {
        font-size: 11px;
        margin-bottom: 4px;
        font-weight: bold;
        text-shadow: none !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    .print-body h4 {
        font-size: 10px;
        margin-bottom: 3px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 1px;
        font-weight: 600;
        text-shadow: none !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
    .print-body table {
        font-size: 9px;
        line-height: 1.0;
        width: 100%;
        text-shadow: none !important;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    
  .print-body table td {
    padding: 1px 0;
    vertical-align: top;
    text-shadow: none !important;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
  }
    
    .print-body .mb-4 {
        margin-bottom: 4px;
    }
    
    .print-body .mb-6 {
        margin-bottom: 6px;
    }
    
    .border-b-2 {
        border-bottom: 1px solid #000;
        margin-bottom: 4px;
    }
    
    /* Reduce spacing for compact layout */
    .print-body > div {
        margin-bottom: 3px;
    }
    
    /* Flex layout for better space utilization */
    .print-body .mb-6.grid.grid-cols-2 {
        display: flex;
        gap: 10px;
    }
    
    .print-body .mb-6.grid.grid-cols-2 > div {
        flex: 1;
    }
    
    /* Compact the main content sections */
    .print-body > .mb-4:first-child {
        margin-bottom: 3px;
    }
    
    .print-body > .grid.grid-cols-2.gap-6.mb-4 {
        margin-bottom: 3px;
        gap: 8px;
    }
    
    .print-body > .mb-4:nth-child(3) {
        margin-bottom: 3px;
    }
    
    .print-body > .mb-6:last-of-type {
        margin-bottom: 3px;
    }
    
    /* Ensure content fits in available space */
    .print-body .flex.justify-between {
        font-size: 9px;
    }
    
  .print-body span {
    font-size: inherit;
    text-shadow: none !important;
  }
    
    /* Compact document list */
    #print-documents {
        font-size: 8px;
        line-height: 1.0;
        text-shadow: none !important;
    }
    
  #print-documents > div { margin-bottom: 2px; }
}

/* Screen-only helper to ensure print template stays hidden normally */
@media screen {
  #printTemplate { display: none; }
}
</style>
