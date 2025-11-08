@extends('layouts.app')
@section('page-title')
    {{ __('Confirmation Of Instrument Registration') }}
@endsection

@section('content')

@php
    use BaconQrCode\Renderer\ImageRenderer;
    use BaconQrCode\Renderer\Image\SvgImageBackEnd;
    use BaconQrCode\Renderer\RendererStyle\RendererStyle;
    use BaconQrCode\Writer;
    // Get the fileno parameter from the URL
    $fileno = request()->get('fileno');
    $stmRef = request()->get('STM_Ref');

    // If url param is present, try to extract STM_Ref from it if not already set
    $urlParam = request()->get('url');
    if (!$stmRef && $urlParam) {
        // Try to extract STM_Ref from the url param
        if (preg_match('/STM_Ref=([A-Za-z0-9\-]+)/', $urlParam, $matches)) {
            $stmRef = $matches[1];
        }
    }

    // Initialize data variable
    $dbData = null;

    if (!empty($fileno)) {
        try {
            // Query the database directly for fileno
            $dbData = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('MLSFileNo', $fileno)
                ->orWhere('KAGISFileNO', $fileno)
                ->orWhere('NewKANGISFileNo', $fileno)
                ->orWhere('StFileNo', $fileno)
                ->first();
            
            // If no exact match, try LIKE search
            if (!$dbData) {
                $dbData = DB::connection('sqlsrv')->table('registered_instruments')
                    ->where('MLSFileNo', 'LIKE', '%' . $fileno . '%')
                    ->orWhere('KAGISFileNO', 'LIKE', '%' . $fileno . '%')
                    ->orWhere('NewKANGISFileNo', 'LIKE', '%' . $fileno . '%')
                    ->orWhere('StFileNo', 'LIKE', '%' . $fileno . '%')
                    ->first();
            }
        } catch (\Exception $e) {
            // Log error but continue
            \Log::error('Direct DB query error: ' . $e->getMessage());
        }
    } elseif (!empty($stmRef)) {
        try {
            // Query by STM_Ref
            $dbData = DB::connection('sqlsrv')->table('registered_instruments')
                ->where('STM_Ref', $stmRef)
                ->first();
        } catch (\Exception $e) {
            \Log::error('STM_Ref query error: ' . $e->getMessage());
        }
    }
    
    // Format the data if found
    if ($dbData) {
        // Format date
        $formatted_date = $dbData->deeds_date ? date('jS F Y', strtotime($dbData->deeds_date)) : 
                         ($dbData->instrumentDate ? date('jS F Y', strtotime($dbData->instrumentDate)) : date('jS F Y'));
        
        // Format time
        $time_source = $dbData->deeds_time ?: ($dbData->instrumentDate ? date('H:i:s', strtotime($dbData->instrumentDate)) : '12:00:00');
        $formatted_time = date('g:i A', strtotime($time_source));
        $hour_part = date('g', strtotime($time_source));
        $time_part = date('A', strtotime($time_source));
        
        // Use database data
        $displayData = (object)[
            'Applicant_Name' => $dbData->Grantor ?: 'N/A',
            'instrument_type' => $dbData->instrument_type ?: 'INSTRUMENT',
            'volume_no' => $dbData->volume_no ?: '1',
            'page_no' => $dbData->page_no ?: '1',
            'serial_no' => $dbData->serial_no ?: '1',
            'formatted_date' => $formatted_date,
            'hour_part' => $hour_part,
            'time_part' => $time_part,
            'STM_Ref' => $dbData->STM_Ref ?: 'STM-' . date('Y') . '-001',
            'MLSFileNo' => $dbData->MLSFileNo,
            'KAGISFileNO' => $dbData->KAGISFileNO,
            'NewKANGISFileNo' => $dbData->NewKANGISFileNo,
            'StFileNo' => $dbData->StFileNo,
            'data_source' => 'database'
        ];
    } else {
        // Use mock data if no database record found
        $year = date('Y');
        $displayData = (object)[
            'Applicant_Name' => 'DEFAULT APPLICANT',
            'instrument_type' => 'DEFAULT INSTRUMENT',
            'volume_no' => '1',
            'page_no' => '1',
            'serial_no' => '1',
            'formatted_date' => date('jS F Y'),
            'hour_part' => '12',
            'time_part' => 'PM',
            'STM_Ref' => "STM-{$year}-001",
            'MLSFileNo' => $fileno ?: null,
            'KAGISFileNO' => null,
            'NewKANGISFileNo' => null,
            'StFileNo' => null,
            'data_source' => 'mock'
        ];
    }
    
    // Override the $data variable
    $data = $displayData;
    
    // Generate QR code for verification
    $qrCodeData = "STM_Ref: " . $data->STM_Ref . "\nVerification URL: " . url('/coroi?STM_Ref=' . $data->STM_Ref);
    
    $renderer = new ImageRenderer(
        new RendererStyle(100), // Size
        new SvgImageBackEnd()
    );
    $writer = new Writer($renderer);
    $qrCodeSvg = $writer->writeString($qrCodeData);
@endphp
    <style>
        .ck-editor__editable {
            min-height: 200px;
        }

        input[type="text"],
        input[type="number"],
        input[type="date"],
        textarea {
            min-height: 40px;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }
            .print-area, .print-area * {
                visibility: visible !important;
            }
            .print-area {
                position: absolute !important;
                left: 0; 
                top: 0; 
                width: 100vw;
                height: 100vh;
                margin: 0 !important;
                padding: 15px !important;
                box-shadow: none !important;
                background: white !important;
                z-index: 9999;
            }
            .print-button, .print-button * {
                display: none !important;
            }
            
            /* A4 Portrait page optimization */
            @page {
                size: A4 portrait;
                margin: 0.4in;
            }
            
            .certificate-container {
                max-width: 100% !important;
                height: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .certificate-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                grid-template-rows: 1fr 1fr !important;
                gap: 15px !important;
                width: 100% !important;
                height: 100% !important;
                max-height: 100% !important;
            }
            
            .certificate-item {
                font-size: 9px !important;
                line-height: 1.2 !important;
                padding: 8px !important;
                border: 2px solid #d1d5db !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                height: 100% !important;
            }
            
            .certificate-item img {
                width: 16px !important;
                height: 16px !important;
            }
            
            .logo-container img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .title {
                font-size: 11px !important;
                margin-bottom: 6px !important;
            }
            
            .red-box-compact {
                padding: 6px !important;
                margin-bottom: 6px !important;
                font-size: 8px !important;
                line-height: 1.3 !important;
            }
            
            .footer-info {
                font-size: 7px !important;
                margin-top: 4px !important;
            }
            
            .footer-logo img {
                width: 14px !important;
                height: 14px !important;
            }
            
            .reg-number p {
                font-size: 10px !important;
            }
        }

        .red-box {
            border: 1px solid #c41e3a;
            color: #c41e3a;
        }

        .print-button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        /* Compact layout for screen view - optimized for portrait preview */
        .certificate-container {
            max-width: 210mm; /* A4 portrait width */
            max-height: 297mm; /* A4 portrait height */
            margin: 0 auto;
            padding: 20px;
        }
        
        .certificate-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 15px;
            height: 100%;
            min-height: 500px;
        }
        
        .certificate-item {
            border: 2px solid #d1d5db;
            padding: 10px;
            background: white;
            background-image: url('{{ asset("storage/upload/images/cor_bg.jpeg") }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            font-size: 9px;
            line-height: 1.3;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }
        
        /* Optional: Add a semi-transparent overlay to ensure text readability */
        .certificate-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 1;
            pointer-events: none;
        }
        
        /* Ensure all content appears above the overlay */
        .certificate-item > * {
            position: relative;
            z-index: 2;
        }
        
        .certificate-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        
        .logo-container {
            width: 35px;
            display: flex;
            justify-content: center;
        }
        
        .logo-container img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }
        
        .seal-container {
            width: 20px;
            display: flex;
            justify-content: center;
        }
        
        .seal {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1px solid #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .seal img {
            width: 12px;
            height: 12px;
            object-fit: contain;
        }
        
        .reg-number {
            text-align: center;
            flex: 1;
        }
        
        .title {
            text-align: center;
            margin-bottom: 6px;
            font-weight: bold;
            font-size: 11px;
        }
        
        .red-box-compact {
            border: 1px solid #c41e3a;
            color: #c41e3a;
            padding: 6px;
            margin-bottom: 6px;
            font-size: 8px;
            line-height: 1.3;
            flex-grow: 1;
        }
        
        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 7px;
            margin-top: 4px;
        }
        
        .footer-logo {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #b91c1c;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .footer-logo img {
            width: 12px;
            height: 12px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .registrar-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }
        
        .registrar-text {
            flex: 1;
        }
        
        .qr-code-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 15px;
            margin-right: 5px;
        }
        
        .qr-code-container svg {
            width: 50px;
            height: 50px;
        }
        
        @media print {
            .qr-code-container svg {
                width: 45px !important;
                height: 45px !important;
            }
        }
    </style>
    
    <div class="flex-1 overflow-auto">
        <!-- Header -->
        @include('admin.header')
        
        <!-- Print button -->
    @if(!request()->has('is_unit'))
    <div class="p-4 flex justify-center">
        <button class="print-button" onclick="window.print()">Print</button>
    </div>
    @endif
        
        <!-- Dashboard Content -->
        <div class="print-area">
            <div class="certificate-container p-4">
                <!-- 2x2 Grid of Certificates -->
                <div class="certificate-grid">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="certificate-item">
                            <!-- Header with logos and registration number -->
                            <div class="certificate-header">
                                <!-- Nigerian Coat of Arms (local logo1.jpg) -->
                                <div class="logo-container">
                                    <img src="{{ asset('assets/logo/logo1.jpg') }}" alt="Nigerian Coat of Arms">
                                </div>

                                <!-- Registration Number -->
                                <div class="reg-number">
                                    <p class="font-bold text-[8px]">
                                    @if(isset($data) && isset($data->STM_Ref))
                                        {{ $data->STM_Ref }}
                                    @else
                                        @php
                                            $year = date('Y');
                                            echo "STM-{$year}-001";
                                        @endphp
                                    @endif
                                    </p>
                                </div>

                                <!-- KANGIS Logo (logo2.jpg) -->
                                <div class="logo-container">
                                    <img src="{{ asset('assets/logo/logo2.jpg') }}" alt="KANGIS Logo">
                                </div>

                              
                            </div>

                            <!-- Title -->
                            <div class="title">
                                <h2>CONFIRMATION OF REGISTRATION OF INSTRUMENT</h2>
                            </div>

                            <!-- Red Box 1 -->
                            <div class="red-box-compact">
                                <p>THIS {{ isset($data) && isset($data->instrument_type) ? strtoupper($data->instrument_type) : 'INSTRUMENT' }} WAS DELIVERED TO ME FOR REGISTRATION BY</p>
                                <p class="font-bold">{{ isset($data) && isset($data->Applicant_Name) ? strtoupper($data->Applicant_Name) : 'APPLICANT NAME' }}</p>
                                <p>AT {{ isset($data) && isset($data->hour_part) ? $data->hour_part : '12' }} O'CLOCK IN THE {{ isset($data) && isset($data->time_part) ? $data->time_part : 'AFTERNOON' }}</p>
                                <p>ON THE {{ isset($data) && isset($data->formatted_date) ? strtoupper($data->formatted_date) : strtoupper(date('jS \of F Y')) }}</p>
                                
                                @if(isset($data) && (isset($data->MLSFileNo) || isset($data->KAGISFileNO) || isset($data->NewKANGISFileNo) || isset($data->StFileNo)))
                                    <!-- <div class="mt-1" style="font-size: 7px;">
                                        <p><strong>FILE REFERENCE:</strong></p>
                                        @if(isset($data->MLSFileNo) && !empty($data->MLSFileNo))
                                            <p>MLS File No: {{ $data->MLSFileNo }}</p>
                                        @endif
                                        @if(isset($data->KAGISFileNO) && !empty($data->KAGISFileNO))
                                            <p>KAGIS File No: {{ $data->KAGISFileNO }}</p>
                                        @endif
                                        @if(isset($data->NewKANGISFileNo) && !empty($data->NewKANGISFileNo))
                                            <p>New KANGIS File No: {{ $data->NewKANGISFileNo }}</p>
                                        @endif
                                        @if(isset($data->StFileNo) && !empty($data->StFileNo))
                                            <p>ST File No: {{ $data->StFileNo }}</p>
                                        @endif
                                    </div> -->
                                @endif
                                
                                <!-- Registrar section with QR code -->
                                <div class="registrar-section">
                                    <div class="registrar-text">
                                        <p class="text-center">REGISTRAR OF DEEDS</p>
                                    </div>
                                    <div class="qr-code-container">
                                        {!! $qrCodeSvg !!}
                                    </div>
                                </div>
                                
                                <div class="mt-1">
                                    <p>Signature: ________________________________</p>
                                    <p style="margin-top: 4px;">Date: ____________________________________</p>
                                </div>

                                <!-- Land Deeds Registry Office -->
                                <div class="text-center mt-2" style="color:black">
                                    <p class="font-bold">DEEDS REGISTRY</p>
                                    <p class="font-bold">DEEDS DEPARTMENT</p>
                                    <p class="font-bold">MINISTRY OF LANDS AND PHYSICAL PLANNING</p>
                                    <p class="font-bold">KANO STATE</p>
                                </div>
                            </div>

                            <!-- Red Box 2 -->
                            <div class="red-box-compact">
                                <p>THIS {{ isset($data) && isset($data->instrument_type) ? strtoupper($data->instrument_type) : 'INSTRUMENT' }} IS REGISTERED AS</p>
                                <p style="margin-top: 2px;">NO <strong>{{ isset($data) && isset($data->serial_no) ? $data->serial_no : '1' }}</strong> AT PAGE <strong>{{ isset($data) && isset($data->page_no) ? $data->page_no : '1' }}</strong> IN VOLUME <strong>{{ isset($data) && isset($data->volume_no) ? $data->volume_no : '1' }}</strong></p>
                                <p style="margin-top: 2px;">OF THE MINISTRY OF LAND AND PHYSICAL PLANNING</p>
                                <p style="margin-top: 2px;">AT KANO STATE</p>
                            </div>

                            <!-- Footer -->
                            <div class="footer-info">
                                <p>Generated by Kano State Land Administration Enterprise 
System (KLAES)</p>
                                <div class="footer-logo">
                                    <img src="http://klas.com.ng/storage/upload/logo/1.jpeg" alt="Kano State Logo">
                                </div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        @include('admin.footer')
    </div>
@endsection