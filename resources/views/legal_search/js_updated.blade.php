<script>
  // Updated JavaScript for Legal Search with ST Assignment and Sectional Titling CofO support
  
  // Helper function to get file number display based on record type
  const getFileNumberDisplay = (file) => {
    // For ST Assignment and Sectional Titling CofO records
    if (file.record_type === 'ST_Instrument' || file.instrument_type === 'ST Assignment (Transfer of Title)' || file.instrument_type === 'Sectional Titling CofO') {
      return {
        stFileNo: file.STFileNo || file.StFileNo || 'N/A',
        parentFileNo: file.ParentFileNo || file.parent_fileNo || file.mother_np_fileno || 'N/A',
        mlsFileNo: file.MLSFileNo || file.mlsFNo || 'N/A',
        kangisFileNo: file.KANGISFileNo || file.kangisFileNo || file.KAGISFileNO || 'N/A',
        newKangisFileNo: file.NewKANGISFileNo || file.NewKANGISFileno || 'N/A'
      };
    }
    
    // For regular records (property_records, other instruments, CofO)
    return {
      stFileNo: 'N/A',
      parentFileNo: file.ParentFileNo || file.np_fileno || 'N/A',
      mlsFileNo: file.MLSFileNo || file.mlsFNo || file.fileNo || file.fileno || 'N/A',
      kangisFileNo: file.KANGISFileNo || file.kangisFileNo || file.KAGISFileNO || 'N/A',
      newKangisFileNo: file.NewKANGISFileNo || file.NewKANGISFileno || 'N/A'
    };
  };

  // Updated renderTableResults function
  const renderTableResults = () => {
    const tableResultsBody = document.getElementById('table-results-body');
    tableResultsBody.innerHTML = '';
    
    searchResults.forEach((file, index) => {
      const fileNumbers = getFileNumberDisplay(file);
      
      const row = document.createElement('tr');
      row.className = 'hover:bg-gray-50 transition-colors';
      row.innerHTML = `
        <td class="p-2 text-sm">${fileNumbers.stFileNo}</td>
        <td class="p-2 text-sm">${fileNumbers.parentFileNo}</td>
        <td class="p-2 text-sm">${fileNumbers.mlsFileNo}</td>
        <td class="p-2 text-sm">${fileNumbers.kangisFileNo}</td>
        <td class="p-2 text-sm">${fileNumbers.newKangisFileNo}</td>
        <td class="p-2 text-sm">${toProperCase(getMappedValue(file, 'grantor'))}</td>
        <td class="p-2 text-sm">${toProperCase(getMappedValue(file, 'grantee'))}</td>
        <td class="p-2 text-sm">${toProperCase(getMappedValue(file, 'lga'))}</td>
        <td class="p-2 text-sm">${file.property_house_no && file.property_plot_no && file.property_street_name && file.property_district && file.property_lga ? toProperCase(`${file.property_house_no},${file.property_plot_no},${file.property_street_name},${file.property_district},${file.property_lga}`) : toProperCase(getMappedValue(file, 'location'))}</td>
        <td class="p-2 text-sm">${getMappedValue(file, 'plotNo')}</td>
        <td class="p-2 text-sm">${toProperCase(getMappedValue(file, 'transactionType'))}</td>
        <td class="p-2 text-sm">${getMappedValue(file, 'size')}</td>
        <td class="p-2 text-sm font-medium ${file.caveat === 'Yes' ? 'text-red-600' : ''}">${file.caveat || 'N/A'}</td>
        <td class="p-2 text-sm">
          <button class="view-file-btn inline-flex items-center px-2 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50" data-index="${index}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0  5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
            </svg>
            View Records
          </button>
        </td>
      `;
      
      tableResultsBody.appendChild(row);
    });
    
    // Add event listeners to view buttons
    document.querySelectorAll('.view-file-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const index = parseInt(btn.getAttribute('data-index'));
        console.log('View button clicked for index:', index);
        
        selectedFile = searchResults[index];
        console.log('Selected file:', selectedFile);
        
        // Close search modal
        searchModal.classList.add('hidden');
        
        // Show file history view directly instead of file details
        dashboardView.classList.add('hidden');
        fileHistoryView.classList.remove('hidden');
        
        // Populate file details
        renderFileHistory();
      });
    });
  };

  // Updated renderCardResults function
  const renderCardResults = () => {
    const cardResults = document.getElementById('card-results');
    cardResults.innerHTML = '';
    
    searchResults.forEach((file, index) => {
      const fileNumbers = getFileNumberDisplay(file);
      
      const card = document.createElement('div');
      card.className = 'bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow cursor-pointer';
      card.setAttribute('data-index', index);
      
      // Build file number display for card
      let fileNumberDisplay = '';
      if (fileNumbers.stFileNo !== 'N/A') {
        fileNumberDisplay = `ST: ${fileNumbers.stFileNo} | Parent: ${fileNumbers.parentFileNo} | MLS: ${fileNumbers.mlsFileNo}`;
      } else {
        fileNumberDisplay = `MLS: ${fileNumbers.mlsFileNo} | Parent: ${fileNumbers.parentFileNo}`;
      }
      
      card.innerHTML = `
        <div class="p-4">
          <div class="flex justify-between items-start mb-3">
            <div>
              <div class="font-medium flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 21h7a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v11m0 5l4.879-4.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242z" />
                </svg>
                ${fileNumberDisplay}
              </div>
              <div class="text-sm text-gray-500 mt-1">
                KANGIS: ${fileNumbers.kangisFileNo} | New KANGIS: ${fileNumbers.newKangisFileNo}
              </div>
            </div>
            <div>
              <span class="text-gray-500">Guarantee:</span> ${getMappedValue(file, 'grantee')}
            </div>
            <div>
              <span class="text-gray-500">LGA:</span> ${getMappedValue(file, 'lga')}
            </div>
            <div>
              <span class="text-gray-500">Location:</span> ${file.property_house_no && file.property_plot_no && file.property_street_name && file.property_district && file.property_lga ? `${file.property_house_no},${file.property_plot_no},${file.property_street_name},${file.property_district},${file.property_lga}` : getMappedValue(file, 'location')}
            </div>
            <div>
              <span class="text-gray-500">Plot No:</span> ${getMappedValue(file, 'plotNo')}
            </div>
            <div>
              <span class="text-gray-500">Size:</span> ${getMappedValue(file, 'size')}
            </div>
            <div class="col-span-2">
              <span class="text-gray-500">Transaction Type:</span> ${getMappedValue(file, 'transactionType')}
            </div>
          </div>
        </div>
      `;
      
      card.addEventListener('click', () => {
        const cardIndex = parseInt(card.getAttribute('data-index'));
        console.log('Card clicked for index:', cardIndex);
        
        selectedFile = searchResults[cardIndex];
        console.log('Selected file from card:', selectedFile);
        
        // Close search modal
        searchModal.classList.add('hidden');
        
        // Show file history view directly
        dashboardView.classList.add('hidden');
        fileHistoryView.classList.remove('hidden');
        
        // Populate file details
        renderFileHistory();
      });
      
      cardResults.appendChild(card);
    });
  };

  // Updated renderFileHistory function
  const renderFileHistory = () => {
    if (!selectedFile) {
      console.log('No selected file in renderFileHistory');
      return;
    }
    
    console.log('Rendering file history for:', selectedFile);
    
    const fileNumbers = getFileNumberDisplay(selectedFile);
    
    // Update file reference in subtitle
    let fileRef = fileNumbers.mlsFileNo;
    if (typeof fileRef === 'number' && fileRef % 1 === 0) {
      fileRef = Math.floor(fileRef).toString();
    } else if (typeof fileRef === 'string' && fileRef.endsWith('.0')) {
      fileRef = fileRef.replace('.0', '');
    }
    document.getElementById('file-reference').textContent = fileRef;
    
    // Update file information fields with new structure
    document.getElementById('file-number-value').textContent = fileNumbers.mlsFileNo;
    document.getElementById('kangis-file-number-value').textContent = fileNumbers.kangisFileNo;
    document.getElementById('new-kangis-file-number-value').textContent = fileNumbers.newKangisFileNo;
    
    // Enhanced guarantor/guarantee mapping for mother and sub applications
    const guarantorValue = selectedFile.owner_fullname || selectedFile.mother_owner_fullname || 
                         selectedFile.first_name || selectedFile.corporate_name || 
                         selectedFile.multiple_owners_names || selectedFile.Assignor || 
                         selectedFile.Grantor || selectedFile.Mortgagor || selectedFile.Lessor || 
                         selectedFile.Surrenderor || selectedFile.originalAllottee || 'N/A';
    
    const guaranteeValue = selectedFile.sub_owner_fullname || selectedFile.multiple_owners_names || 
                         selectedFile.owner_fullname || selectedFile.Assignee || 
                         selectedFile.Grantee || selectedFile.Mortgagee || selectedFile.Lessee || 
                         selectedFile.Surrenderee || selectedFile.currentAllottee || 'N/A';
    
    document.getElementById('current-guarantor-value').textContent = guarantorValue;
    document.getElementById('current-guarantee-value').textContent = guaranteeValue;
    
    // Enhanced LGA mapping
    const lgaValue = selectedFile.property_lga || selectedFile.address_lga || 
                    selectedFile.lgsaOrCity || selectedFile.lga || selectedFile.lgaName || 'N/A';
    document.getElementById('lga-value').textContent = lgaValue;
    
    // Enhanced district mapping
    const districtValue = selectedFile.property_district || selectedFile.address_district || 
                         selectedFile.district || selectedFile.districtName || 'N/A';
    document.getElementById('district-value').textContent = districtValue;
    
    // Enhanced property type mapping - prioritize land_use
    const propertyTypeValue = selectedFile.land_use || selectedFile.landUse || 
                             selectedFile.landUseType || selectedFile.title_type || 
                             selectedFile.instrument_type || selectedFile.Type || 
                             selectedFile.residential_type || selectedFile.commercial_type || 
                             selectedFile.industrial_type || selectedFile.mixed_type || 'N/A';
    document.getElementById('property-type-value').textContent = propertyTypeValue;
    
    // Enhanced last transaction mapping
    const lastTransactionValue = selectedFile.transaction_type || selectedFile.instrument_type || 
                                selectedFile.application_status || selectedFile.deeds_status || 
                                selectedFile.planning_recommendation_status || 'N/A';
    document.getElementById('last-transaction-value').textContent = lastTransactionValue;
    
    // Render the transactions tables
    renderTransactionTables();
    
    // Default to property history tab
    switchTab('property-history');
  };

  // Updated legal search report with new file number structure
  const renderLegalSearchReport = () => {
    if (!selectedFile) return;

    // Get related transactions for the selected file
    const relatedTransactions = getRelatedTransactions(selectedFile);
    const fileNumbers = getFileNumberDisplay(selectedFile);

    // Helper to get Registration Particulars for each transaction
    function getRegistrationParticulars(transaction) {
      // property_records table
      if (
        transaction.hasOwnProperty('serialNo') &&
        transaction.hasOwnProperty('pageNo') &&
        transaction.hasOwnProperty('volumeNo')
      ) {
        return `${cleanNumericValue(transaction.serialNo)}/${cleanNumericValue(transaction.pageNo)}/${cleanNumericValue(transaction.volumeNo)}`;
      }
      // registered_instruments table
      if (
        transaction.hasOwnProperty('instrument_type') ||
        transaction.hasOwnProperty('rootRegistrationNumber')
      ) {
        return `${cleanNumericValue(transaction.volume_no)}/${cleanNumericValue(transaction.page_no)}/${cleanNumericValue(transaction.serial_no)}`;
      }
      return 'N/A/N/A/N/A';
    }

    // Create combined array of all transactions
    const allTransactions = [];

    relatedTransactions.forEach(transaction => {
      let regNo = getRegistrationParticulars(transaction);
      
      allTransactions.push({
        date: transaction.transaction_date || transaction.deeds_date || transaction.certificateDate || transaction.approval_date || 'N/A',
        time: transaction.deeds_time || generateRandomTime(),
        transactionType: transaction.transaction_type || transaction.instrument_type || transaction.title_type || 'Record',
        grantor: transaction.owner_fullname || transaction.mother_owner_fullname || transaction.Assignor || transaction.Grantor || transaction.originalAllottee || transaction.first_name || transaction.corporate_name || 'N/A',
        grantee: transaction.sub_owner_fullname || transaction.multiple_owners_names || transaction.Assignee || transaction.Grantee || transaction.currentAllottee || 'N/A',
        regNo: regNo,
        size: transaction.size || transaction.plot_size || 'N/A',
        caveat: transaction.caveat === 'Yes' ? 'Yes' : 'NO',
        comments: transaction.comments || transaction.additional_comments || 'N/A',
        originalRecord: transaction
      });
    });

    // Sort by date (newest first)
    allTransactions.sort((a, b) => new Date(b.date) - new Date(a.date));

    // Build file number display for report
    let fileNumberDisplay = '';
    if (fileNumbers.stFileNo !== 'N/A') {
      fileNumberDisplay = `STFileNo: ${fileNumbers.stFileNo} | ParentFileNo: ${fileNumbers.parentFileNo} | MLSFileNo: ${fileNumbers.mlsFileNo} | KANGISFileNo: ${fileNumbers.kangisFileNo} | NewKANGISFileNo: ${fileNumbers.newKangisFileNo}`;
    } else {
      fileNumberDisplay = `ParentFileNo: ${fileNumbers.parentFileNo} | MLSFileNo: ${fileNumbers.mlsFileNo} | KANGISFileNo: ${fileNumbers.kangisFileNo} | NewKANGISFileNo: ${fileNumbers.newKangisFileNo}`;
    }

    // Create the report HTML using the better template format
    const reportHtml = `
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KANO STATE GEOGRAPHIC INFORMATION SYSTEM - OFFICIAL SEARCH REPORT</title>
        <style type="text/css">
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: white;
            display: flex;
            justify-content: center;
          }
          
          .report-container {
            width: 1000px;
            background-color: white;
            padding: 20px;
            position: relative;
            box-shadow: none;
            border: none;
          }
          
          @media print {
            body {
              background-color: white !important;
              background: white !important;
            }
            
            .report-container {
              background-color: white !important;
              background: white !important;
              box-shadow: none !important;
              border: none !important;
              margin: 0 !important;
              padding: 20px !important;
              width: 100% !important;
              max-width: none !important;
            }
          }
          
          .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
          }
          
          .logo {
            width: 80px;
            height: 80px;
          }
          
          .header-text {
            text-align: center;
            flex-grow: 1;
            margin: 0 20px;
          }
          
          .header-title {
            color: #0066cc;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
          }
          
          .header-subtitle {
            font-size: 14px;
            font-weight: bold;
            margin: 5px 0;
          }
          
          .header-purpose {
            font-size: 14px;
            font-weight: bold;
          }
          
          .date-section {
            text-align: right;
            margin: 10px 0 20px 0;
            font-size: 12px;
          }
          
          .section-title {
            border: 1px solid black;
            padding: 2px 5px;
            font-size: 12px;
            font-weight: bold;
            background-color: #f5f5f5;
            display: inline-block;
            margin-bottom: 5px;
          }
          
          .property-details {
            border-top: 1px solid black;
            margin-top: 0;
            padding-top: 10px;
          }
          
          .property-row {
            display: flex;
            margin-bottom: 5px;
            font-size: 12px;
          }
          
          .property-label {
            width: 150px;
            font-weight: bold;
          }
          
          .transaction-history {
            margin-top: 20px;
            border-top: 1px solid black;
            padding-top: 10px;
          }
          
          table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
          }
          
          th, td {
            padding: 3px;
            text-align: left;
            vertical-align: top;
          }
          
          th {
            background-color: white;
            font-weight: bold;
          }
          
          .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(200, 200, 200, 0.1);
            opacity: 0.1;
            z-index: -1;
            white-space: nowrap;
            pointer-events: none;
            font-weight: bold;
            font-family: Arial, sans-serif;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
          }
          
          @media print {
            * {
              background-color: white !important;
              background: white !important;
              color-adjust: exact !important;
              -webkit-print-color-adjust: exact !important;
            }
            
            body, html {
              background-color: white !important;
              background: white !important;
              margin: 0 !important;
              padding: 0 !important;
            }
            
            @page {
              size: A4 landscape;
              margin: 10mm 8mm !important;
            }
            
            .report-container {
              background-color: white !important;
              background: white !important;
              box-shadow: none !important;
              border: none !important;
              margin: 0 !important;
              padding: 15px !important;
              width: 100% !important;
              max-width: none !important;
              page-break-inside: avoid !important;
              break-inside: avoid !important;
            }
            
            .watermark {
              position: absolute !important;
              top: 50% !important;
              left: 50% !important;
              transform: translate(-50%, -50%) rotate(-45deg) !important;
              font-size: 60px !important;
              color: rgba(200, 200, 200, 0.05) !important;
              opacity: 0.05 !important;
              z-index: -1 !important;
              background: none !important;
              background-color: transparent !important;
              border: none !important;
              box-shadow: none !important;
              text-shadow: none !important;
            }
          }
          
          .col-sn { width: 20px; }
          .col-grantor { width: 120px; }
          .col-grantee { width: 120px; }
          .col-instrument { width: 100px; }
          .col-date { width: 70px; }
          .col-reg { width: 60px; }
          .col-size { width: 50px; }
          .col-caveat { width: 50px; }
          .col-comments { width: 150px; }

          .footer {
            font-family: Arial, sans-serif;
            margin-top: 30px;
            padding: 10px 0;
            border-top: 1px solid #eee;
            width: 100%;
          }
          
          .timestamp {
            font-size: 12px;
            font-weight: bold;
            text-align: left;
            margin-bottom: 20px;
          }
          
          .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
          }
          
          .signature-block {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
          }
          
          .signature-text {
            font-size: 12px;
            margin-bottom: 5px;
          }
          
          .signature-image {
            border: 2px solid #0047AB;
            padding: 5px;
            transform: rotate(-20deg);
            width: 200px;
            height: 45px;
          }
          
          .print-info {
            font-size: 12px;
            text-align: right;
          }
          
          .barcode {
            text-align: center;
            margin: 15px 0;
          }
          
          .disclaimer {
            font-size: 11px;
            text-align: center;
            margin-bottom: 10px;
          }
          
          .contact-info {
            font-size: 11px;
            text-align: center;
            margin-bottom: 10px;
          }
          
          .geo-info {
            font-size: 11px;
            text-align: center;
          }
        </style>
      </head>
      <body>
        <div class="report-container">
          <div class="watermark">FOR OFFICE USE ONLY</div>  
           
          <div class="header">
            <img src="{{ asset('assets/logo/logo1.jpg') }}" alt="Kano State Logo" width="80" height="80">
            <div class="header-text">
              <div class="header-title">KANO STATE GEOGRAPHIC INFORMATION SYSTEM</div>
              <div class="header-subtitle">MINISTRY OF LAND AND PHYSICAL PLANNING</div>
              <div class="header-purpose">LEGAL SEARCH REPORT</div>  
              <div class="header-purpose">OFFICIAL SEARCH REPORT FOR FILING PURPOSES</div>   
            </div>
            <img src="{{ asset('assets/logo/logo2.jpg') }}" alt="GIS Logo" width="80" height="80">
          </div>
          
          <div class="date-section">
            Date: ${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
          </div>
         
          <div class="section-title">Property Details</div>
          
          <div class="property-details">
            <div class="property-row">
              <div class="property-label">File Numbers:</div>
              <div>${fileNumberDisplay}</div>
            </div>
            
            <div class="property-row">       
              <div class="property-label">Schedule:</div>
              <div>Kano</div>
            </div>
            
            <div class="property-row">
              <div class="property-label">Plot Number:</div>
              <div>${selectedFile.plot_no || selectedFile.plotNo || "GP No. 1067/1 & 1067/2"}</div>
            </div>
            
            <div class="property-row">
              <div class="property-label">Plan Number:</div>
              <div>${selectedFile.planNumber || "LKN/RES/2021/3006"}</div>
            </div>
            
            <div class="property-row">
              <div class="property-label">Plot Description:</div>
              <div>${selectedFile.district || selectedFile.districtName || "Niger Street Nassarawa District"}, ${selectedFile.lgsaOrCity || selectedFile.lga || selectedFile.lgaName || "Nassarawa"} LGA</div>
            </div>
          </div>
          <br>
          <br>
          <br>
          <div class="section-title">Transaction History</div> 
          
          <div class="transaction-history">
            <table>
              <thead>
                <tr>
                  <th class="col-sn">S/N</th>
                  <th class="col-grantor">Grantor</th>
                  <th class="col-grantee">Grantee</th>
                  <th class="col-instrument">Transaction Type</th>
                  <th class="col-date">Date/Time</th>
                  <th class="col-reg">Registration Particulars</th>
                  <th class="col-size">Size</th>
                  <th class="col-caveat">Caveat</th>
                  <th class="col-comments">Comments</th>
                </tr>
              </thead>
              <tbody>
                ${allTransactions.map((transaction, index) => `
                  <tr>
                    <td>${index + 1}</td>
                    <td>${transaction.grantor}</td>
                    <td>${transaction.grantee}</td>
                    <td>${transaction.transactionType}</td>
                    <td>${transaction.date}<br><small>${transaction.time}</small></td>
                    <td>${transaction.regNo}</td>
                    <td>${transaction.size}</td>
                    <td>${transaction.caveat}</td>
                    <td>${transaction.comments}</td>
                  </tr>
                `).join('')}
              </tbody>
            </table>
          </div>
          
          <div class="footer">
            <!-- Timestamp -->
            <div class="timestamp">
              These details are as at ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}
            </div>
            
            <!-- Signature and Print Info -->
            <div class="footer-content">
              <div class="signature-block">
                <div class="signature-text">Yours Faithfully,</div>
                <div class="signature-image">
                  <svg width="200" height="45" viewBox="0 0 200 45" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10,35 C30,5 50,40 70,15 C90,30 110,10 130,25 C150,15 170,30 190,20" 
                          stroke="#000080" fill="none" stroke-width="2"/>
                  </svg>
                </div>
                .......Director Deeds.........
              </div>
              <div class="print-info">
                Generated by: {{ auth()->user()->first_name }}
              </div>
            </div>
            
            <!--qrcode -->
            <div class="barcode">
              <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent('File Numbers: ' + fileNumberDisplay)}" alt="QR Code" width="100" height="100">
            </div>
            
            <!-- Disclaimer -->
            <div class="disclaimer">
              Disclaimer: This Search Report does not represent consent to any transaction and is without prejudice to subsequent disclosures.
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info">
              For enquiries, please call +234 (0) 8023456789
            </div>
            
            <!-- Geographic Info -->
            <div class="geo-info">
              KANO STATE GEOGRAPHIC INFORMATION SYSTEM, Plot P/123, Secretariat Kano, Kano State
            </div>
          </div>
        </div>
      </body>
      </html>
    `;

    // Open the report in a new window for printing
    const printWindow = window.open('', '_blank');
    printWindow.document.write(reportHtml);
    printWindow.document.close();
    
    // Auto-print after a short delay
    setTimeout(() => {
      printWindow.print();
    }, 500);
  };

  // Helper function to generate random time strings
  const generateRandomTime = () => {
    const hours = Math.floor(Math.random() * 12) + 1; // 1-12
    const minutes = Math.floor(Math.random() * 60); // 0-59
    const ampm = Math.random() > 0.5 ? 'AM' : 'PM';
    return `${hours}:${minutes.toString().padStart(2, '0')} ${ampm}`;
  };

  // Helper function to remove .0 from values
  const cleanNumericValue = (value) => {
    if (!value || value === 'N/A') return value;
    
    // Convert to string if it's a number
    let stringValue = value.toString();
    
    // Remove .0 from the end if present
    if (stringValue.endsWith('.0')) {
      stringValue = stringValue.replace('.0', '');
    }
    
    return stringValue;
  };

  // Helper function to get mapped field value
  const getMappedValue = (item, fieldType) => {
    const fieldMappings = {
      // Date fields - enhanced for applications
      date: [
        'transaction_date', 'deeds_date', 'certificateDate', 
        'instrumentDate', 'approval_date', 'planning_approval_date',
        'receipt_date', 'payment_date', 'accountant_signature_date',
        'created_at', 'updated_at'
      ],
      
      // Transaction type fields - enhanced for applications
      transactionType: [
        'transaction_type', 'instrument_type', 'title_type', 'typeForm', 
        'landUseType', 'application_status', 'deeds_status',
        'planning_recommendation_status', 'land_use', 'landUse'
      ],
      
      // Grantor/From party fields - enhanced for applications
      grantor: [
        'owner_fullname', 'mother_owner_fullname', 
        'first_name', 'corporate_name', 'multiple_owners_names',
        'Assignor', 'assignor', 'assignorName',
        'Grantor', 'Mortgagor', 'mortgagor', 
        'Lessor', 'lessor', 'Surrenderor', 'surrenderor',
        'originalAllottee', 'surrenderingPartyName',
        'applicant_title'
      ],
      
      // Grantee/To party fields - enhanced for applications
      grantee: [
        'sub_owner_fullname', 'multiple_owners_names', 'owner_fullname',
        'Assignee', 'assignee', 'Grantee', 
        'Mortgagee', 'mortgagee', 'Lessee', 'lessee',
        'Surrenderee', 'surrenderee', 'currentAllottee',
        'receivingPartyName', 'releaseeName',
        'first_name', 'corporate_name'
      ],
      
      // Registration number fields - enhanced
      serialNo: [
        'serialNo', 'serial_no', 'oldTitleSerialNo', 
        'rootRegistrationNumber', 'particularsRegistrationNumber',
        'volume_no', 'page_no'
      ],
      pageNo: ['pageNo', 'page_no', 'oldTitlePageNo'],
      volumeNo: ['volumeNo', 'volume_no', 'oldTitleVolumeNo'],
      
      // Size fields - enhanced
      size: ['size', 'plot_size', 'NoOfUnits', 'NoOfSections', 'NoOfBlocks'],
      
      // Comments fields - enhanced for applications
      comments: [
        'comments', 'additional_comments', 'recomm_comments', 
        'director_comments', 'application_comment', 'planning_recomm_comments'
      ],
      
      // Time fields
      time: ['deeds_time', 'transaction_time'],
      
      // Plot number fields - enhanced for applications
      plotNo: [
        'plot_no', 'plotNo', 'plotNumber', 'property_plot_no', 
        'address_plot_no', 'scheme_no'
      ],
      
      // LGA fields - enhanced for applications
      lga: [
        'property_lga', 'address_lga', 'lga', 'lgaName', 
        'lgsaOrCity'
      ],
      
      // District fields - enhanced for applications
      district: [
        'property_district', 'address_district', 'district', 
        'districtName'
      ],
      
      // Location/Address fields - enhanced for applications
      location: [
        'location', 'propertyAddress', 'propertyDescription', 
        'plotDescription', 'property_location', 'address',
        'property_street_name', 'address_street_name',
        'property_house_no', 'address_house_no'
      ],
      
      // Land use fields - enhanced for applications
      landUse: [
        'land_use', 'landUse', 'landUseType', 'residential_type',
        'commercial_type', 'industrial_type', 'mixed_type'
      ]
    };
    
    const fields = fieldMappings[fieldType] || [];
    for (const field of fields) {
      if (item[field] && item[field] !== null && item[field] !== '') {
        let value = item[field];
        // Remove .0 from numeric values that are actually integers
        if (typeof value === 'number' && value % 1 === 0) {
          value = Math.floor(value).toString();
        } else if (typeof value === 'string' && value.endsWith('.0')) {
          value = value.replace('.0', '');
        }
        return value;
      }
    }
    return 'N/A';
  };

  // Helper function to convert text to proper case
  const toProperCase = (text) => {
    if (!text || text === 'N/A') return text;
    return text.toString().toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
  };

</script>