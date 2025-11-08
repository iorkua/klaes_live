{{-- Primary Application Form CSS Styles --}}
<link rel="stylesheet" href="{{ asset('css/primaryform/primary-form.css') }}">

{{-- Inline styles for form layout --}}
<style>
/* Additional inline styles that need to be in Blade context */
div.form-section {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    height: 0 !important;
    overflow: hidden !important;
}

div.form-section.active {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
    height: auto !important;
    overflow: visible !important;
}

/* Specific targeting for step sections */
#step1, #step2, #step3, #step4, #step5 {
    display: none !important;
}

#step1.active, #step2.active, #step3.active, #step4.active, #step5.active {
    display: block !important;
}

.step-circle {
    cursor: pointer;
}

/* Progress indicator */
.progress-indicator {
    background: linear-gradient(90deg, #10b981 0%, #10b981 {{ (1/5) * 100 }}%, #e5e7eb {{ (1/5) * 100 }}%, #e5e7eb 100%);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .step-circle {
        width: 1.5rem;
        height: 1.5rem;
        font-size: 0.75rem;
    }
    
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* Print styles */
@media print {
    .no-print {
        display: none !important;
    }
    
    .form-section {
        display: block !important;
        page-break-after: always;
    }
    
    .step-circle {
        border: 1px solid #000;
    }
}

/* Custom validation styles */
.has-error {
    border-color: #ef4444;
}

.has-error:focus {
    outline-color: #ef4444;
    border-color: #ef4444;
}

.error-text {
    color: #ef4444;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

/* Loading states */
.btn-loading {
    position: relative;
    color: transparent;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    margin: auto;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
</style>