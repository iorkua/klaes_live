<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final ST Conveyance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Source+Sans+Pro:wght@300;400;600;700&display=swap');
        
        body {
            font-family: 'Source Sans Pro', sans-serif;
            background-color: #f8f9fa;
            font-size: 12px;
            line-height: 1.2;
        }
        
        .download-mode .no-print {
            display: none !important;
        }

        .download-mode .document-container,
        .download-mode .document-page {
            height: auto !important;
            min-height: unset !important;
        }

        .document-container {
            width: 21cm;
            height: 29.7cm;
            background: white;
            margin: 0 auto;
            padding: 1cm;
            position: relative;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header-line {
            height: 2px;
            background: linear-gradient(90deg, #1a56db, #1e3a8a);
            margin: 8px 0;
        }
        
        .signature-line {
            border-bottom: 1px solid #cbd5e1;
            display: inline-block;
            width: 180px;
            margin: 0 5px;
        }
        
        .short-line {
            width: 100px;
        }
        
        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
            margin: 10px 0;
        }
        
        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        
        th {
            background-color: #f1f5f9;
            font-weight: 600;
            text-align: center;
        }
        
        .reference {
            background-color: #f1f5f9;
            padding: 10px;
            border-left: 4px solid #1a56db;
            margin-bottom: 12px;
            font-size: 12px;
        }
        
        .underline {
            text-decoration: underline;
        }
        
        /* Compact styling for single page */
        .compact-section {
            margin-bottom: 12px;
        }
        
        /* Logo styling */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .logo-left, .logo-right {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        
        .header-text {
            flex: 1;
            text-align: center;
        }
        
        .document-page {
            position: relative;
            min-height: 27cm;
        }

        /* Page break for buyers section */
        .page-break {
            page-break-before: always;
            break-before: page;
        }
        
        .buyers-section {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        /* Footer styling for all pages */
        .document-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 10px 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 1000;
        }
        
    /* Hide print button when printing */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
                width: 21cm;
                height: 29.7cm;
            }
            
            .document-container {
                width: 100%;
                height: auto;
                min-height: 29.7cm;
                padding: 1cm;
                margin: 0;
                box-shadow: none;
            }
            
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-before: always;
                break-before: page;
            }
            
            .buyers-section {
                page-break-inside: avoid;
                break-inside: avoid;
            }
          
            @page {
                size: A4 portrait;
                margin: 0.1cm 0 2cm 0;
                margin-top: 1%;
                @bottom-center {
                    content: "Generated on: " attr(data-date) " by " attr(data-user);
                    font-size: 10px;
                    color: #666;
                }
            }
            

            
            .document-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                padding: 5px 0;
                font-size: 10px;
                color: #666;
                background: white;
            }
            
            /* Ensure content doesn't overlap footer */
            .document-container {
                padding-bottom: 3cm;
            }


            
        }
    </style>
@php
    use Illuminate\Support\Carbon;

    $authUser = auth()->user();

    $generatedBy = trim(collect([
        optional($authUser)->first_name,
        optional($authUser)->last_name,
    ])->filter()->implode(' '));

    if ($generatedBy === '') {
        $generatedBy = optional($authUser)->name ?? 'System';
    }

    if ($generatedBy === '') {
        $generatedBy = 'System';
    }

    $generatedTimestamp = Carbon::now();
    $generatedAtDisplay = $generatedTimestamp->format('F j, Y \\a\\t g:i A');

    $rawFileNumber = $application->fileno ?? ($application->np_fileno ?? 'application');
    $sanitizedFileNumber = preg_replace('/[^A-Za-z0-9_-]+/', '_', $rawFileNumber);
@endphp
</head>
<body class="py-2" data-file-number="{{ $sanitizedFileNumber }}" data-date="{{ $generatedAtDisplay }}" data-user="{{ $generatedBy }}">
    <div class="no-print flex justify-center gap-3 mb-2">
        <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded text-sm flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m4 4h6a2 2 0 002-2v-4a2 2 0 00-2-2h-6a2 2 0 00-2 2v4a2 2 0 002 2z" />
            </svg>
            Print Document
        </button>
        <button id="download-pdf-btn" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-1 px-4 rounded text-sm flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
            </svg>
            Download PDF
        </button>
    </div>

    <!-- Document Container -->
    <div id="document-container" class="document-container" data-date="{{ $generatedAtDisplay }}" data-user="{{ $generatedBy }}">
        <div class="document-page page-1">
        <!-- Letter Head with Logos -->
        <div class="logo-container">
            <img src="{{ asset('assets/logo/ministry1.jpg') }}" alt="Ministry Logo Left" class="logo-left">
            <div class="header-text">
            <h1 class="text-lg font-bold text-blue-800">MINISTRY OF LAND AND PHYSICAL PLANNING</h1>
            <p class="text-lg font-bold text-blue-800">KANO STATE, NIGERIA</p>
            <p class="text-base font-semibold text-blue-700 mt-1">SECTIONAL TITLING DEPARTMENT</p>
            <p class="text-xs text-gray-700 mt-1">No2 Dr Bala Muhammad Road, Nassarawa GRA, Kano State.</p>
            </div>
            <img src="{{ asset('assets/logo/ministry2.jpeg') }}" alt="Ministry Logo Right" class="logo-right">
        </div>
        
        
        
        <!-- Recipient Address -->
        <div class="compact-section">
            @php
                // Get applicant name based on type
                $applicantName = '';
                if($application->applicant_type == 'individual') {
                    $applicantName = ($application->applicant_title ?? '') . ' ' . ($application->first_name ?? '') . ' ' . ($application->surname ?? '');
                } elseif($application->applicant_type == 'corporate') {
                    $applicantName = $application->corporate_name ?? '';
                } elseif($application->applicant_type == 'multiple') {
                    $applicantName = $application->multiple_owners_names ?? '';
                }
                $applicantName = trim($applicantName);
            @endphp
            <p>{{ strtoupper($application->address ?? '-') }}</p>
            <p class="font-bold">{{ strtoupper($applicantName ?: '______') }}</p>
        </div>
        
        <!-- Title -->
        <h2 class="text-lg font-bold text-center my-3 text-blue-800">SECTIONAL TITLING CONVEYANCE</h2>
        
        <!-- Reference -->
        <div class="reference">
            @php
                // Build property location from individual fields
                $propertyLocationParts = [];
                if (!empty($application->property_house_no)) {
                    $propertyLocationParts[] = 'House No: ' . $application->property_house_no;
                }
                if (!empty($application->property_plot_no)) {
                    $propertyLocationParts[] = 'Plot No: ' . $application->property_plot_no;
                }
                if (!empty($application->property_street_name)) {
                    $propertyLocationParts[] = $application->property_street_name;
                }
                if (!empty($application->property_district)) {
                    $propertyLocationParts[] = $application->property_district;
                }
                if (!empty($application->property_lga)) {
                    $propertyLocationParts[] = $application->property_lga;
                }
                $propertyLocation = !empty($propertyLocationParts) ? implode(', ', $propertyLocationParts) : ($application->layout_name ?? $application->property_location ?? '______');
            @endphp
            @php
                // Get CofO number from database
                $cofoNumber = DB::connection('sqlsrv')
                    ->table('CofO')
                    ->where('application_id', $application->id)
                    ->value('cofo_no');
                $fileno = $application->fileno ?? '______';
            @endphp
            <p class="font-bold underline">
                RE: APPLICATION FOR FRAGMENTATION IN RESPECT OF PROPERTY
                @if($cofoNumber)
                    WITH C-OF-O NO:
                    <span class="underline font-bold">
                        {{ strtoupper($cofoNumber) }}
                    </span>
                @else
                    WITH OLD STATUTORY FILE NO: {{ strtoupper($fileno) }}
                @endif
                LOCATED AT
                <span class="underline font-bold">{{ strtoupper($propertyLocation) }}</span>
                IN THE NAME OF
                <span class="underline font-bold">{{ strtoupper($applicantName ?: '______') }}</span>
            </p>
        </div>
        
        @php
            $sharedUtilities = DB::connection('sqlsrv')
                ->table('shared_utilities')
                ->where('application_id', $application->id)
                ->select('id', 'application_id', 'utility_type', 'dimension')
                ->get();

            $sharedUtilitiesSummary = '-';

            if ($sharedUtilities->count() > 0) {
                $summaryString = $sharedUtilities
                    ->map(function ($utility) {
                        $name = strtoupper(trim($utility->utility_type ?? ''));
                        $dimension = trim($utility->dimension ?? '');

                        if ($name === '' && $dimension === '') {
                            return null;
                        }

                        if ($name !== '' && $dimension !== '') {
                            return $name . ' (' . $dimension . ')';
                        }

                        return $name !== '' ? $name : $dimension;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->implode(', ');

                if ($summaryString !== '') {
                    $sharedUtilitiesSummary = '“' . $summaryString . '”';
                } else {
                    $sharedUtilitiesSummary = '-';
                }
            }
        @endphp

        <!-- Main Content -->
        <div class="compact-section">
            <p style="font-size: 13px;">
                Reference to your application for sectional titling dated <span class="font-semibold">{{ isset($application->application_date) ? \Carbon\Carbon::parse($application->application_date)->format('d/m/Y') : (isset($application->created_at) ? \Carbon\Carbon::parse($application->created_at)->format('d/m/Y') : '-') }}</span>, 
                am directed to convey the approval of Honorable Commissioner regarding the above caption, 
                in line with the Sectional Titling Law under the provisions of the Kano State Sectional and Systematic Land Titling and Registration Law, 2024; Relevant State Development and Planning regulations.
            </p>
            <p class="mt-2">
                Meanwhile you may please be informed that, your title is now sectioned into 
                <span class="font-semibold">{{ $application->NoOfSections ?? '0' }}</span> section{{ ($application->NoOfSections ?? 0) != 1 ? 's' : '' }},
                <span class="font-semibold">{{ $application->NoOfUnits ?? '0' }}</span> unit{{ ($application->NoOfUnits ?? 0) != 1 ? 's' : '' }} described below and with
                <span class="font-semibold">{{ $sharedUtilitiesSummary }}</span> as shared Utilities / Common Areas.
            </p>
        </div>

        <!-- Shared Properties Table -->
        @if(count($sharedUtilities) > 0)
            <table class="compact-section">
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>DESCRIPTION</th>
                       
                        <th>DIMENSION (m²)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sharedUtilities as $index => $utility)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ strtoupper($utility->utility_type ?? '-') }}</td>
                           
                            <td>{{ $utility->dimension ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

            <!-- Buyers List Table -->
            <table >
                <thead>
                    <tr>
                        <th>SN</th>
                        <th>BUYER NAME</th>
                        <th>UNIT NO</th>
                        <th>SECTION</th>
                        <th>MEASUREMENT M²</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Query buyers from database with measurements and dimensions
                        $buyers = DB::connection('sqlsrv')
                            ->table('buyer_list as bl')
                            ->leftJoin('st_unit_measurements as sum', function($join) use ($application) {
                                $join->on('bl.id', '=', 'sum.buyer_id')
                                     ->where('sum.application_id', '=', $application->id);
                            })
                            ->where('bl.application_id', $application->id)
                            ->select(
                                'bl.buyer_title',
                                'bl.buyer_name',
                                'bl.unit_no',
                                'bl.section_number',
                                'sum.measurement'
                            )
                            ->distinct()
                            ->get();
                    @endphp
                    
                    @if(count($buyers) > 0)
                        @foreach($buyers as $index => $buyer)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ strtoupper($buyer->buyer_title ? $buyer->buyer_title . ' ' : '') }}{{ strtoupper($buyer->buyer_name) }}</td>
                                <td>{{ $buyer->unit_no }}</td>
                                <td>
                                    {{ ($buyer->section_number ?? '') !== '' ? strtoupper($buyer->section_number) : '-' }}
                                </td>
                                <td>{{ $buyer->measurement ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5">No buyers found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
            
            <div class="compact-section mt-4">
                <p>
                    In view of this, you are expected to write an acceptance letter, Submit the Original Title document in your possession for cancellation and Commissioning of new sectional units file(s).
                    You may also refer to the table above for the list of buyers, Units number, section and dimensions / measurement of each unit in square meters (SQM) for further guidance.
                </p>
            </div>

            <!-- Closing -->
            <div class="compact-section mt-4">
                <p>Above is for your information.</p>
                <p class="mt-2">Best Regards.</p>
            </div>

            <!-- Signature Section -->
            <div class="mt-4">
               
                <p class="text-sm">Assistant Chief Land Officer</p>
                <p class="text-sm italic">For: Hon. Commissioner</p>
                
                <div class="flex mt-3">
                    <div class="mr-6">
                        <p class="text-sm">Sign: <span class="signature-line"></span></p>
                    </div>
                    <div>
                        <p class="text-sm">Date: <span class="signature-line short-line"></span></p>
                    </div>
                </div>
            </div>
            
        </div>

     
    </div> <!-- End of Document Container -->
    
    <!-- Fixed Footer for all pages -->
 

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script>
        (function syncPrintMeta() {
            const meta = {
                date: @json($generatedAtDisplay),
                user: @json($generatedBy),
            };

            [document.documentElement, document.body, document.getElementById('document-container')] 
                .filter(Boolean)
                .forEach((el) => {
                    el.setAttribute('data-date', meta.date);
                    el.setAttribute('data-user', meta.user);
                });
        })();

        // Download PDF handler
        const downloadButton = document.getElementById('download-pdf-btn');
        if (downloadButton) {
            const loadHtml2Pdf = () => {
                if (typeof window.html2pdf !== 'undefined') {
                    return Promise.resolve();
                }

                if (window.__html2pdfLoadingPromise) {
                    return window.__html2pdfLoadingPromise;
                }

                window.__html2pdfLoadingPromise = new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.3/html2pdf.bundle.min.js';
                    script.crossOrigin = 'anonymous';
                    script.referrerPolicy = 'no-referrer';
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('Failed to load html2pdf library.'));
                    document.head.appendChild(script);
                });

                return window.__html2pdfLoadingPromise;
            };

            downloadButton.addEventListener('click', async () => {
                const documentContainer = document.getElementById('document-container');
                if (!documentContainer) {
                    console.error('Document container not found for PDF export.');
                    return;
                }

                downloadButton.disabled = true;
                downloadButton.classList.add('opacity-60', 'cursor-not-allowed');

                try {
                    await loadHtml2Pdf();

                    if (typeof window.html2pdf === 'undefined') {
                        throw new Error('html2pdf library is unavailable.');
                    }

                    const fileNumber = document.body.dataset.fileNumber || 'application';
                    const filename = `${fileNumber}_finalconveyance.pdf`;

                    const options = {
                        margin: [0.4, 0.4, 0.4, 0.4],
                        filename,
                        image: { type: 'jpeg', quality: 0.98 },
                        html2canvas: {
                            scale: 2,
                            useCORS: true,
                            scrollY: 0
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        },
                        pagebreak: {
                            mode: ['css', 'legacy'],
                            before: '.page-break'
                        }
                    };

                    document.body.classList.add('download-mode');

                    await window.html2pdf().set(options).from(documentContainer).save();
                } catch (error) {
                    console.error('PDF download failed:', error);
                    alert('Unable to generate the PDF at the moment. Please try again.');
                } finally {
                    document.body.classList.remove('download-mode');
                    downloadButton.disabled = false;
                    downloadButton.classList.remove('opacity-60', 'cursor-not-allowed');
                }
            });
        }
    </script>
</body>
</html>
