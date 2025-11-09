<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Commissioning Sheet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }
        
        .header h3 {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 0 0;
        }
        
        .logo-section {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 80px;
            height: 80px;
            border: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            text-align: center;
        }
        
        .header-logos {
            position: absolute;
            top: 15px;
            width: 60px;
            height: 60px;
        }
        
        .header-logo-left {
            left: 20px;
        }
        
        .header-logo-right {
            right: 20px;
        }
        
        .header-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .form-section {
            margin: 30px 0;
        }
        
        .form-row {
            margin-bottom: 20px;
            display: flex;
            align-items: baseline;
        }
        
        .form-row.full-width {
            flex-direction: column;
        }
        
        .form-label {
            font-weight: bold;
            min-width: 120px;
            margin-right: 10px;
        }
        
        .form-value {
            border-bottom: 1px solid #333;
            flex-grow: 1;
            padding: 2px 5px;
            min-height: 18px;
        }
        
        .form-value.wide {
            width: 100%;
            margin-top: 5px;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            height: 60px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 5px;
        }
        
        .signature-label {
            font-weight: bold;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 48px;
            color: rgba(0, 0, 0, 0.1);
            z-index: -1;
            font-weight: bold;
        }
        
        .background-watermark {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('/storage/upload/images/cor_bg.jpeg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.1;
            z-index: -2;
        }
        
        .footer-logos {
            position: fixed;
            bottom: 30px;
            right: 30px;
            display: flex;
            gap: 10px;
            z-index: 1;
        }
        
        .footer-logo {
            width: 25px;
            height: 25px;
            object-fit: contain;
        }
        
        @media print {
            body {
                padding: 15px;
            }
            
            .header {
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Watermark -->
    <div class="background-watermark"></div>
    
    <!-- Text Watermark -->
    <div class="watermark">COMMISSIONING SHEET</div>
    
    <!-- Logo placeholder -->
    <div class="logo-section">
        Logo
    </div>
    
    <!-- Header Logos -->
    <div class="header-logos header-logo-left">
        <img src="/storage/upload/logo/1.jpeg" alt="Left Header Logo" class="header-logo" onerror="this.style.display='none'">
    </div>
    
    <div class="header-logos header-logo-right">
        <img src="/storage/upload/logo/las.jpeg" alt="Right Header Logo" class="header-logo" onerror="this.style.display='none'">
    </div>
    
    <!-- Header -->
    <div class="header">
        <h1>MINISTRY OF LAND & PHYSICAL PLANNING</h1>
        <h2>DEPT. OF LANDS</h2>
        <h3>FILE COMMISSIONING SHEET</h3>
    </div>
    
    <!-- Form Section -->
    <div class="form-section">
        <!-- File Number -->
        <div class="form-row">
            <span class="form-label">File No:</span>
            <span class="form-value">{{ $data['file_number'] ?? '' }}</span>
        </div>
        
        <!-- File Name -->
        <div class="form-row full-width">
            <span class="form-label">File Name:</span>
            <span class="form-value wide">{{ $data['file_name'] ?? '' }}</span>
        </div>
        
        <!-- Name or Allottee -->
        <div class="form-row full-width">
            <span class="form-label">Name or Allottee:</span>
            <span class="form-value wide">{{ $data['name_or_allottee'] ?? '' }}</span>
        </div>
        
        <!-- Plot Number -->
        <div class="form-row">
            <span class="form-label">Plot No:</span>
            <span class="form-value">{{ $data['plot_number'] ?? '' }}</span>
        </div>
        
        <!-- TP Number -->
        <div class="form-row">
            <span class="form-label">TP No:</span>
            <span class="form-value">{{ $data['tp_number'] ?? '' }}</span>
        </div>
        
        <!-- Location -->
        <div class="form-row full-width">
            <span class="form-label">Location:</span>
            <span class="form-value wide">{{ $data['location'] ?? '' }}</span>
        </div>
        
        <!-- Date Created -->
        <div class="form-row">
            <span class="form-label">Date Created:</span>
            <span class="form-value">{{ $data['date_created'] ?? '' }}</span>
        </div>
        
        <!-- Created By -->
        <div class="form-row">
            <span class="form-label">Created by:</span>
            <span class="form-value">{{ $data['created_by'] ?? '' }}</span>
        </div>
    </div>
    
    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Created by Signature</div>
        </div>
        
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">Approved by Signature</div>
        </div>
    </div>
    
    <!-- Footer information -->
    <div style="margin-top: 40px; text-align: center; font-size: 10px; color: #666;">
        Generated on {{ date('d/m/Y H:i:s') }} | File Commissioning Sheet System
    </div>
    
    <!-- Footer Logos -->
    <div class="footer-logos">
        <img src="/storage/upload/logo/1.jpeg" alt="Logo 1" class="footer-logo" onerror="this.style.display='none'">
        <img src="/storage/upload/logo/las.jpeg" alt="Logo 2" class="footer-logo" onerror="this.style.display='none'">
    </div>
</body>
</html>
