// Shared Applicant Information JavaScript Module

// Handle applicant type changes for any tab (using prefix)
function handleApplicantTypeChange(prefix, type) {
    console.log(`🎯 ${prefix || 'Default'} Applicant type changed to:`, type);
    
    // Wait a moment for Alpine.js to finish any transitions
    setTimeout(() => {
        const detailsDiv = document.getElementById(`${prefix}applicant_details`);
        const individualFields = document.getElementById(`${prefix}individual_fields`);
        const corporateFields = document.getElementById(`${prefix}corporate_fields`);
        const multipleFields = document.getElementById(`${prefix}multiple_fields`);
        
        console.log('🔍 Elements found:', {
            detailsDiv: !!detailsDiv,
            individualFields: !!individualFields,
            corporateFields: !!corporateFields,
            multipleFields: !!multipleFields
        });
        
        if (detailsDiv) {
            console.log('🔍 Current styles:', {
                detailsDivDisplay: detailsDiv.style.display,
                detailsDivVisible: detailsDiv.offsetParent !== null,
                detailsDivClientHeight: detailsDiv.clientHeight
            });
        }
        
        // Show the details section - use both display and visibility for Alpine.js compatibility
        if (detailsDiv) {
            detailsDiv.style.display = type ? 'block' : 'none';
            detailsDiv.style.visibility = type ? 'visible' : 'hidden';
            console.log(`✅ ${prefix}applicant_details display set to:`, type ? 'block' : 'none');
        } else {
            console.error(`❌ Element ${prefix}applicant_details not found!`);
            return; // Exit early if main container not found
        }
        
        // Hide all field sections first
        [individualFields, corporateFields, multipleFields].forEach(field => {
            if (field) {
                field.style.display = 'none';
                field.style.visibility = 'hidden';
            }
        });
        
        // Show relevant fields based on type
        let targetField = null;
        switch(type) {
            case 'Individual':
                targetField = individualFields;
                break;
            case 'Corporate':
                targetField = corporateFields;
                break;
            case 'Multiple':
                targetField = multipleFields;
                break;
        }
        
        if (targetField) {
            targetField.style.display = 'block';
            targetField.style.visibility = 'visible';
            console.log(`✅ ${prefix}${type.toLowerCase()}_fields shown`);
            
            // Force a reflow to ensure the element is properly displayed
            targetField.offsetHeight;
            
            // Scroll the element into view if needed
            targetField.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            console.error(`❌ Element ${prefix}${type.toLowerCase()}_fields not found!`);
        }
        
        // Update hidden applicantType field for PRIMARY tab (when prefix is empty)
        if (!prefix || prefix === '') {
            const hiddenApplicantTypeField = document.getElementById('applicantType');
            if (hiddenApplicantTypeField) {
                hiddenApplicantTypeField.value = type.toLowerCase();
                console.log(`✅ Updated hidden applicantType field to: ${type.toLowerCase()}`);
            }
        }
    }, 100); // Small delay to allow Alpine.js transitions
}

// Validate applicant information for a specific prefix
function validateApplicantInfo(prefix = '') {
    const applicantType = document.querySelector(`input[name="${prefix}applicant_type"]:checked`);
    
    if (!applicantType) {
        showValidationError('Please select an applicant type');
        return false;
    }
    
    switch(applicantType.value) {
        case 'Individual':
            const firstName = document.getElementById(`${prefix}first_name`);
            const lastName = document.getElementById(`${prefix}last_name`);
            
            if (!firstName?.value.trim()) {
                showValidationError('First name is required for individual applicants');
                firstName?.focus();
                return false;
            }
            if (!lastName?.value.trim()) {
                showValidationError('Last name is required for individual applicants');
                lastName?.focus();
                return false;
            }
            break;
            
        case 'Corporate':
            const corporateName = document.getElementById(`${prefix}corporate_name`);
            
            if (!corporateName?.value.trim()) {
                showValidationError('Company name is required for corporate applicants');
                corporateName?.focus();
                return false;
            }
            break;
            
        case 'Multiple':
            const primaryApplicantName = document.getElementById(`${prefix}primary_applicant_name`);
            
            if (!primaryApplicantName?.value.trim()) {
                showValidationError('Primary applicant name is required for multiple applicants');
                primaryApplicantName?.focus();
                return false;
            }
            break;
    }
    
    return true;
}

// Get applicant data for a specific prefix
function getApplicantData(prefix = '') {
    const applicantType = document.querySelector(`input[name="${prefix}applicant_type"]:checked`);
    
    if (!applicantType) {
        return null;
    }
    
    const baseData = {
        type: applicantType.value
    };
    
    switch(applicantType.value) {
        case 'Individual':
            return {
                ...baseData,
                title: document.getElementById(`${prefix}title`)?.value || '',
                first_name: document.getElementById(`${prefix}first_name`)?.value || '',
                last_name: document.getElementById(`${prefix}last_name`)?.value || ''
            };
            
        case 'Corporate':
            return {
                ...baseData,
                corporate_name: document.getElementById(`${prefix}corporate_name`)?.value || '',
                rc_number: document.getElementById(`${prefix}rc_number`)?.value || ''
            };
            
        case 'Multiple':
            return {
                ...baseData,
                primary_applicant_name: document.getElementById(`${prefix}primary_applicant_name`)?.value || '',
                additional_applicants_count: document.getElementById(`${prefix}additional_applicants_count`)?.value || '1'
            };
    }
    
    return baseData;
}

// Clear applicant form for a specific prefix
function clearApplicantForm(prefix = '') {
    // Clear radio buttons
    const radioButtons = document.querySelectorAll(`input[name="${prefix}applicant_type"]`);
    radioButtons.forEach(radio => radio.checked = false);
    
    // Clear all input fields
    const inputs = document.querySelectorAll(`input[id^="${prefix}"], select[id^="${prefix}"]`);
    inputs.forEach(input => {
        if (input.type === 'radio' || input.type === 'checkbox') {
            input.checked = false;
        } else {
            input.value = '';
        }
    });
    
    // Hide details section
    const detailsDiv = document.getElementById(`${prefix}applicant_details`);
    if (detailsDiv) {
        detailsDiv.style.display = 'none';
    }
}

// Utility function to show validation errors
function showValidationError(message) {
    // Create a toast notification for validation errors
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300';
    toast.innerHTML = `
        <div class="flex items-center gap-2">
            <i data-lucide="alert-circle" class="h-5 w-5"></i>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Initialize Lucide icons
    if (window.lucide) {
        window.lucide.createIcons();
    }
    
    // Remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

// Initialize applicant handling for Alpine.js tabs
function initializeApplicantHandling() {
    console.log('🔧 Initializing applicant handling for Alpine.js tabs...');
    
    // Wait for Alpine.js to be ready
    document.addEventListener('alpine:init', () => {
        console.log('🎯 Alpine.js initialized, setting up applicant form handlers');
        
        // Add event listeners for tab changes to re-initialize form handlers
        const tabButtons = document.querySelectorAll('[x-data] button[\\@click*="activeTab"]');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const tabName = e.target.textContent.trim();
                console.log(`📋 Tab switched to: ${tabName}`);
                
                // Re-initialize form handlers after tab switch
                setTimeout(() => {
                    reinitializeFormHandlers();
                }, 200);
            });
        });
    });
    
    // Fallback initialization if Alpine.js event doesn't fire
    setTimeout(() => {
        reinitializeFormHandlers();
    }, 1000);
}

// Re-initialize form handlers for all tabs
function reinitializeFormHandlers() {
    console.log('🔄 Re-initializing form handlers...');
    
    // Check all applicant type radio buttons and ensure they have proper event handlers
    const applicantRadios = document.querySelectorAll('input[name*="applicant_type"]');
    console.log(`Found ${applicantRadios.length} applicant type radio buttons`);
    
    applicantRadios.forEach(radio => {
        if (!radio.hasAttribute('data-handler-attached')) {
            const prefix = radio.name.replace('applicant_type', '');
            const type = radio.value;
            
            radio.addEventListener('change', function() {
                if (this.checked) {
                    console.log(`🎯 Radio change detected: ${prefix}${type}`);
                    handleApplicantTypeChange(prefix, type);
                }
            });
            
            radio.setAttribute('data-handler-attached', 'true');
            console.log(`✅ Handler attached to: ${radio.name} - ${type}`);
        }
    });
}

// Make functions globally available
window.handleApplicantTypeChange = handleApplicantTypeChange;
window.validateApplicantInfo = validateApplicantInfo;
window.getApplicantData = getApplicantData;
window.clearApplicantForm = clearApplicantForm;
window.initializeApplicantHandling = initializeApplicantHandling;
window.reinitializeFormHandlers = reinitializeFormHandlers;

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApplicantHandling);
} else {
    initializeApplicantHandling();
}

console.log('Shared Applicant JavaScript module loaded successfully');