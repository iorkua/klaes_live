<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @if($type === 'initial')
            Initial Application Bill
        @elseif($type === 'betterment')
            Betterment Bill
        @else
            Final Bill Balance
        @endif
        - {{ $bill->primary_fileno ?? $bill->unit_fileno ?? 'N/A' }}
    </title>
    <style>
    body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 15px;
            color: #333;
            line-height: 1.4;
            font-size: 13px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            position: relative;
        }
        .logo-left {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }
        .logo-right {
            width: 45px;
            height: 45px;
            object-fit: contain;
        }
        .ministry-center {
            flex: 1;
            text-align: center;
            padding: 0 15px;
        }
        .ministry-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            line-height: 1.1;
        }
        .ministry-subtitle {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 0;
        }
        .bill-title {
            font-size: 16px;
            font-weight: bold;
            margin: 8px 0 5px 0;
        }
        .bill-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .info-section {
            width: 48%;
        }
        .info-section h3 {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 12px;
        }
        .info-label {
            font-weight: 500;
            color: #6b7280;
        }
        .info-value {
            font-weight: 600;
        }
        .bill-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .bill-table th,
        .bill-table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        .bill-table th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
        }
        .bill-table .amount {
            text-align: right;
            font-weight: 600;
        }
        .total-row {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 13px;
        }
        .status-section {
            margin-bottom: 15px;
        }
        .status-section h3 {
            font-size: 13px;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 11px;
        }
        .status-paid {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        @media print {
            @page {
                margin: 0.5in;
                size: A4;
            }
            body {
                margin: 0;
                padding: 10px;
                font-size: 12px;
                line-height: 1.3;
            }
            .header {
                margin-bottom: 10px;
                padding-bottom: 8px;
            }
            .bill-info {
                margin-bottom: 10px;
            }
            .bill-table {
                margin-bottom: 10px;
            }
            .status-section {
                margin-bottom: 10px;
            }
            .footer {
                margin-top: 15px;
                padding-top: 8px;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <!-- Header Top with Logos and Ministry Title -->
        <div class="header-top">
            <img src="{{ asset('assets/logo/logo1.jpg') }}" alt="Kano State Logo" class="logo-left" onerror="this.style.display='none'">
            
            <div class="ministry-center">
                <div class="ministry-title">KANO STATE MINISTRY OF LAND AND PHYSICAL PLANNING</div>
                <div class="ministry-subtitle">Sectional Title Unit</div>
            </div>
            
            <img src="{{ asset('assets/logo/logo3.jpeg') }}" alt="Ministry Logo" class="logo-right" onerror="this.style.display='none'">
        </div>
        <!-- Bill Title -->
        <div class="bill-title">
            @if($type === 'initial')
                INITIAL APPLICATION BILL
            @elseif($type === 'betterment')
                BETTERMENT BILL
            @else
                FINAL BILL BALANCE
            @endif
        </div>
        <div>Bill ID: #{{ $bill->id }}</div>
    </div>

    <!-- Bill Information -->
    <div class="bill-info">
        <div class="info-section">
            <h3>Application Details</h3>
            <div class="info-row">
                <span class="info-label">File Number:</span>
                <span class="info-value">{{ $bill->primary_fileno ?? $bill->unit_fileno ?? 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Application Type:</span>
                <span class="info-value">{{ $bill->primary_fileno ? 'Primary Application' : 'Unit Application' }}</span>
            </div>
            @if($bill->primary_property_street || $bill->unit_property_location)
            <div class="info-row">
                <span class="info-label">Property Location:</span>
                <span class="info-value">{{ $bill->primary_property_street ?? $bill->unit_property_location }}</span>
            </div>
            @endif
            @if($bill->primary_property_lga)
            <div class="info-row">
                <span class="info-label">LGA:</span>
                <span class="info-value">{{ $bill->primary_property_lga }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="info-label">Date Generated:</span>
                <span class="info-value">{{ $bill->created_at ? \Carbon\Carbon::parse($bill->created_at)->format('M d, Y') : 'N/A' }}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Applicant Information</h3>
            @php
                $ownerName = '';
                if ($bill->primary_corporate_name) {
                    $ownerName = $bill->primary_corporate_name;
                } elseif ($bill->unit_corporate_name) {
                    $ownerName = $bill->unit_corporate_name;
                } else {
                    $firstName = $bill->primary_first_name ?? $bill->unit_first_name ?? '';
                    $surname = $bill->primary_surname ?? $bill->unit_surname ?? '';
                    $ownerName = trim($firstName . ' ' . $surname);
                }
            @endphp
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value">{{ $ownerName ?: 'N/A' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Type:</span>
                <span class="info-value">{{ $bill->primary_corporate_name || $bill->unit_corporate_name ? 'Corporate' : 'Individual' }}</span>
            </div>
        </div>
    </div>

    <!-- Bill Breakdown Table -->
    <table class="bill-table">
        <thead>
            <tr>
                <th>Description</th>
                <th style="text-align: right;">Amount (₦)</th>
            </tr>
        </thead>
        <tbody>
            @if($type === 'initial')
                @if($bill->Scheme_Application_Fee)
                <tr>
                    <td>Scheme Application Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->Scheme_Application_Fee), 2) }}</td>
                </tr>
                @endif
                @if($bill->Site_Plan_Fee)
                <tr>
                    <td>Site Plan Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->Site_Plan_Fee), 2) }}</td>
                </tr>
                @endif
                @if($bill->Unit_Application_Fees)
                <tr>
                    <td>Application Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->Unit_Application_Fees), 2) }}</td>
                </tr>
                @endif
            @elseif($type === 'betterment')
                @if($bill->Betterment_Charges)
                <tr>
                    <td>Betterment Charges</td>
                    <td class="amount">{{ number_format(floatval($bill->Betterment_Charges), 2) }}</td>
                </tr>
                @endif
                @if($bill->Land_Use_Charge)
                <tr>
                    <td>Land Use Charge</td>
                    <td class="amount">{{ number_format(floatval($bill->Land_Use_Charge), 2) }}</td>
                </tr>
                @endif
                @if($bill->Processing_Fee)
                <tr>
                    <td>Processing Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->Processing_Fee), 2) }}</td>
                </tr>
                @endif
            @else
                @if($bill->processing_fee)
                <tr>
                    <td>Processing Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->processing_fee), 2) }}</td>
                </tr>
                @endif
                @if($bill->survey_fee)
                <tr>
                    <td>Survey Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->survey_fee), 2) }}</td>
                </tr>
                @endif
                @if($bill->assignment_fee)
                <tr>
                    <td>Assignment Fee</td>
                    <td class="amount">{{ number_format(floatval($bill->assignment_fee), 2) }}</td>
                </tr>
                @endif
                @if($bill->bill_balance)
                <tr>
                    <td>Bill Balance</td>
                    <td class="amount">{{ number_format(floatval($bill->bill_balance), 2) }}</td>
                </tr>
                @endif
            @endif
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>TOTAL AMOUNT</td>
                <td class="amount">
                    @php
                        $total = 0;
                        if ($type === 'initial') {
                            $total = floatval($bill->Scheme_Application_Fee ?? 0) + 
                                   floatval($bill->Site_Plan_Fee ?? 0) + 
                                   floatval($bill->Unit_Application_Fees ?? 0);
                        } elseif ($type === 'betterment') {
                            $total = floatval($bill->Betterment_Charges ?? 0) + 
                                   floatval($bill->Land_Use_Charge ?? 0) + 
                                   floatval($bill->Processing_Fee ?? 0);
                        } else {
                            $total = floatval($bill->total_amount ?? 0);
                            if ($total == 0) {
                                $total = floatval($bill->processing_fee ?? 0) + 
                                       floatval($bill->survey_fee ?? 0) + 
                                       floatval($bill->assignment_fee ?? 0) + 
                                       floatval($bill->bill_balance ?? 0);
                            }
                        }
                    @endphp
                    ₦{{ number_format($total, 2) }}
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- Payment Status -->
    <div class="status-section">
        <h3>Payment Status</h3>
        @php
            $status = $type === 'balance' ? ($bill->bill_status ?? 'Unknown') : ($bill->Payment_Status ?? 'Unknown');
            $statusClass = '';
            $statusText = '';
            
            if ($type === 'balance') {
                switch($status) {
                    case 'paid':
                        $statusClass = 'status-paid';
                        $statusText = 'Paid';
                        break;
                    case 'generated':
                    case 'sent':
                        $statusClass = 'status-pending';
                        $statusText = ucfirst($status);
                        break;
                    default:
                        $statusClass = 'status-overdue';
                        $statusText = $status;
                }
            } else {
                switch($status) {
                    case 'Complete':
                        $statusClass = 'status-paid';
                        $statusText = 'Paid';
                        break;
                    case 'Incomplete':
                        $statusClass = 'status-pending';
                        $statusText = 'Pending';
                        break;
                    default:
                        $statusClass = 'status-overdue';
                        $statusText = $status;
                }
            }
        @endphp
        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>This is a computer-generated bill. For inquiries, please contact the Kano State Ministry of Land and Physical Planning.</p>
        <p>Generated on {{ now()->format('M d, Y \a\t H:i A') }}</p>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>