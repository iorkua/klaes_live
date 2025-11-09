/**
 * Applicant Type Fix for All Tabs
 * Specific fix for Primary, SuA, and PuA tab applicant type form display issues
 */

// Generate title options from available titles in the form
function generateTitleOptions() {
    // Try to get titles from existing select element
    const existingTitleSelect = document.querySelector('select[name*="title"]');
    if (existingTitleSelect) {
        const options = Array.from(existingTitleSelect.options);
        return options.map(option => 
            `<option value="${option.value}">${option.text}</option>`
        ).join('');
    }
    
    // Fallback options if no existing select found
    return `
        <option value="">Select Title</option>
        <option value="Mr">Mr</option>
        <option value="Mrs">Mrs</option>
        <option value="Miss">Miss</option>
        <option value="Ms">Ms</option>
        <option value="Dr">Dr</option>
        <option value="Prof">Prof</option>
        <option value="Eng">Eng</option>
        <option value="Arch">Arch</option>
    `;
}

// Primary tab applicant type handler
function handlePrimaryApplicantTypeChange(type) {
    console.log('🎯 Primary Applicant type clicked:', type);
    
    // Force-find elements with polling approach for Alpine.js compatibility
    function findAndShowElements() {
        const detailsDiv = document.getElementById('applicant_details');
        const individualFields = document.getElementById('individual_fields');
        const corporateFields = document.getElementById('corporate_fields');
        const multipleFields = document.getElementById('multiple_fields');
        
        console.log('🔍 Primary Elements found:', {
            detailsDiv: !!detailsDiv,
            individualFields: !!individualFields,
            corporateFields: !!corporateFields,
            multipleFields: !!multipleFields
        });
        
        if (!detailsDiv) {
            console.warn('⚠️  Primary applicant_details not found, retrying...');
            return false;
        }
        
        // Show the main details section
        detailsDiv.style.display = 'block';
        detailsDiv.style.visibility = 'visible';
        detailsDiv.style.opacity = '1';
        
        // Hide all field sections first
        [individualFields, corporateFields, multipleFields].forEach(field => {
            if (field) {
                field.style.display = 'none';
                field.style.visibility = 'hidden';
            }
        });
        
        // Show the selected type's fields
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
            targetField.style.opacity = '1';
            
            // Force reflow
            targetField.offsetHeight;
            
            console.log(`✅ Primary ${type} fields displayed successfully`);
            
            // Initialize multiple owners functionality if Multiple is selected
            if (type === 'Multiple') {
                initializeMultipleOwners('');
            }
            
            // Scroll into view
            setTimeout(() => {
                detailsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
            
            return true;
        }
        
        return false;
    }
    
    // Try immediately, then retry if needed
    if (!findAndShowElements()) {
        // Retry after a short delay for Alpine.js
        setTimeout(() => {
            if (!findAndShowElements()) {
                console.error('❌ Failed to find Primary applicant form elements after retry');
            }
        }, 300);
    }
}

// SuA-specific applicant type handler
function handleSuaApplicantTypeChange(type) {
    console.log('🎯 SuA Applicant type clicked:', type);
    
    // Force-find elements with polling approach for Alpine.js compatibility
    function findAndShowElements() {
        const detailsDiv = document.getElementById('sua_applicant_details');
        const individualFields = document.getElementById('sua_individual_fields');
        const corporateFields = document.getElementById('sua_corporate_fields');
        const multipleFields = document.getElementById('sua_multiple_fields');
        
        console.log('🔍 SuA Elements found:', {
            detailsDiv: !!detailsDiv,
            individualFields: !!individualFields,
            corporateFields: !!corporateFields,
            multipleFields: !!multipleFields
        });
        
        if (!detailsDiv) {
            console.warn('⚠️  SuA applicant_details not found, retrying...');
            return false;
        }
        
        // Show the main details section
        detailsDiv.style.display = 'block';
        detailsDiv.style.visibility = 'visible';
        detailsDiv.style.opacity = '1';
        
        // Hide all field sections first
        [individualFields, corporateFields, multipleFields].forEach(field => {
            if (field) {
                field.style.display = 'none';
                field.style.visibility = 'hidden';
            }
        });
        
        // Show the selected type's fields
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
            targetField.style.opacity = '1';
            
            // Force reflow
            targetField.offsetHeight;
            
            console.log(`✅ SuA ${type} fields displayed successfully`);
            
            // Initialize multiple owners functionality if Multiple is selected
            if (type === 'Multiple') {
                initializeMultipleOwners('sua_');
            }
            
            // Scroll into view
            setTimeout(() => {
                detailsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
            
            return true;
        }
        
        return false;
    }
    
    // Try immediately, then retry if needed
    if (!findAndShowElements()) {
        // Retry after a short delay for Alpine.js
        setTimeout(() => {
            if (!findAndShowElements()) {
                console.error('❌ Failed to find SuA applicant form elements after retry');
            }
        }, 300);
    }
}

// PuA-specific applicant type handler
function handlePuaApplicantTypeChange(type) {
    console.log('🎯 PuA Applicant type clicked:', type);
    
    // Force-find elements with polling approach for Alpine.js compatibility
    function findAndShowElements() {
        const detailsDiv = document.getElementById('pua_applicant_details');
        const individualFields = document.getElementById('pua_individual_fields');
        const corporateFields = document.getElementById('pua_corporate_fields');
        const multipleFields = document.getElementById('pua_multiple_fields');
        
        console.log('🔍 PuA Elements found:', {
            detailsDiv: !!detailsDiv,
            individualFields: !!individualFields,
            corporateFields: !!corporateFields,
            multipleFields: !!multipleFields
        });
        
        if (!detailsDiv) {
            console.warn('⚠️  PuA applicant_details not found, retrying...');
            return false;
        }
        
        // Show the main details section
        detailsDiv.style.display = 'block';
        detailsDiv.style.visibility = 'visible';
        detailsDiv.style.opacity = '1';
        
        // Hide all field sections first
        [individualFields, corporateFields, multipleFields].forEach(field => {
            if (field) {
                field.style.display = 'none';
                field.style.visibility = 'hidden';
            }
        });
        
        // Show the selected type's fields
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
            targetField.style.opacity = '1';
            
            // Force reflow
            targetField.offsetHeight;
            
            console.log(`✅ PuA ${type} fields displayed successfully`);
            
            // Initialize multiple owners functionality if Multiple is selected
            if (type === 'Multiple') {
                initializeMultipleOwners('pua_');
            }
            
            // Scroll into view
            setTimeout(() => {
                detailsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 100);
            
            return true;
        }
        
        return false;
    }
    
    // Try immediately, then retry if needed
    if (!findAndShowElements()) {
        // Retry after a short delay for Alpine.js
        setTimeout(() => {
            if (!findAndShowElements()) {
                console.error('❌ Failed to find PuA applicant form elements after retry');
            }
        }, 300);
    }
}

// Multiple Owners Dynamic Management
function initializeMultipleOwners(prefix) {
    console.log('🔧 Initializing multiple owners for prefix:', prefix);
    
    const multipleFieldsContainer = document.getElementById(`${prefix}multiple_fields`);
    if (!multipleFieldsContainer) {
        console.warn('⚠️  Multiple fields container not found');
        return;
    }
    
    // Add dynamic owners container if it doesn't exist
    let dynamicContainer = document.getElementById(`${prefix}dynamic_owners_container`);
    if (!dynamicContainer) {
        dynamicContainer = document.createElement('div');
        dynamicContainer.id = `${prefix}dynamic_owners_container`;
        dynamicContainer.className = 'mt-4 space-y-4';
        
        // Add "Add More Owner" button
        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200';
        addButton.innerHTML = `
            <i data-lucide="user-plus" class="w-4 h-4 mr-2"></i>
            Add More Owner
        `;
        addButton.onclick = () => addMoreOwner(prefix);
        
        // Container for dynamic owner fields
        const ownersContainer = document.createElement('div');
        ownersContainer.id = `${prefix}owners_list`;
        ownersContainer.className = 'space-y-4';
        
        dynamicContainer.appendChild(addButton);
        dynamicContainer.appendChild(ownersContainer);
        multipleFieldsContainer.appendChild(dynamicContainer);
        
        // Initialize Lucide icons
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }
}

function addMoreOwner(prefix) {
    console.log('➕ Adding new owner for prefix:', prefix);
    
    const ownersContainer = document.getElementById(`${prefix}owners_list`);
    if (!ownersContainer) {
        console.error('❌ Owners container not found');
        return;
    }
    
    const ownerCount = ownersContainer.children.length + 2; // +1 for primary, +1 for current
    const ownerId = `${prefix}owner_${ownerCount}`;
    
    const ownerDiv = document.createElement('div');
    ownerDiv.className = 'bg-white border border-gray-200 rounded-lg p-4 relative';
    ownerDiv.id = ownerId;
    
    ownerDiv.innerHTML = `
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-gray-600"></i>
                Owner ${ownerCount}
            </h4>
            <button type="button" onclick="removeOwner('${ownerId}')" 
                    class="text-red-600 hover:text-red-800 transition-colors duration-200">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Title
                </label>
                <select name="${prefix}owner_${ownerCount}_title" 
                        class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    ${generateTitleOptions()}
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="${prefix}owner_${ownerCount}_first_name" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="First name" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" name="${prefix}owner_${ownerCount}_last_name" 
                       class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="Last name" required>
            </div>
        </div>
    `;
    
    ownersContainer.appendChild(ownerDiv);
    
    // Initialize Lucide icons for the new elements
    if (window.lucide) {
        window.lucide.createIcons();
    }
    
    // Scroll the new owner into view
    ownerDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    console.log(`✅ Added owner ${ownerCount} successfully`);
}

function removeOwner(ownerId) {
    console.log('🗑️ Removing owner:', ownerId);
    
    const ownerDiv = document.getElementById(ownerId);
    if (ownerDiv) {
        ownerDiv.remove();
        console.log(`✅ Removed owner ${ownerId} successfully`);
    }
}

// Initialize both Primary and SuA applicant handlers when DOM is ready
function initializeAllApplicantHandlers() {
    console.log('🔧 Initializing applicant handlers for all tabs...');
    
    // Wait for elements to be available
    function attachHandlers() {
        // Primary tab handlers
        const primaryIndividualLabel = document.querySelector('label[for="individual"]');
        const primaryCorporateLabel = document.querySelector('label[for="corporate"]');
        const primaryMultipleLabel = document.querySelector('label[for="multiple"]');
        
        // SuA tab handlers
        const suaIndividualLabel = document.querySelector('label[for="sua_individual"]');
        const suaCorporateLabel = document.querySelector('label[for="sua_corporate"]');
        const suaMultipleLabel = document.querySelector('label[for="sua_multiple"]');
        
        let handlersAttached = 0;
        
        // Attach Primary tab handlers
        if (primaryIndividualLabel && primaryCorporateLabel && primaryMultipleLabel) {
            console.log('✅ Found Primary applicant labels, attaching click handlers');
            
            primaryIndividualLabel.addEventListener('click', function() {
                console.log('🔘 Primary Individual label clicked');
                setTimeout(() => handlePrimaryApplicantTypeChange('Individual'), 50);
            });
            
            primaryCorporateLabel.addEventListener('click', function() {
                console.log('🔘 Primary Corporate label clicked');
                setTimeout(() => handlePrimaryApplicantTypeChange('Corporate'), 50);
            });
            
            primaryMultipleLabel.addEventListener('click', function() {
                console.log('🔘 Primary Multiple label clicked');
                setTimeout(() => handlePrimaryApplicantTypeChange('Multiple'), 50);
            });
            
            // Also attach to radio buttons as backup
            const primaryIndividualRadio = document.getElementById('individual');
            const primaryCorporateRadio = document.getElementById('corporate');
            const primaryMultipleRadio = document.getElementById('multiple');
            
            if (primaryIndividualRadio) {
                primaryIndividualRadio.addEventListener('change', function() {
                    if (this.checked) handlePrimaryApplicantTypeChange('Individual');
                });
            }
            
            if (primaryCorporateRadio) {
                primaryCorporateRadio.addEventListener('change', function() {
                    if (this.checked) handlePrimaryApplicantTypeChange('Corporate');
                });
            }
            
            if (primaryMultipleRadio) {
                primaryMultipleRadio.addEventListener('change', function() {
                    if (this.checked) handlePrimaryApplicantTypeChange('Multiple');
                });
            }
            
            handlersAttached++;
            console.log('✅ Primary applicant handlers attached successfully');
        }
        
        // Attach SuA tab handlers
        if (suaIndividualLabel && suaCorporateLabel && suaMultipleLabel) {
            console.log('✅ Found SuA applicant labels, attaching click handlers');
            
            suaIndividualLabel.addEventListener('click', function() {
                console.log('🔘 SuA Individual label clicked');
                setTimeout(() => handleSuaApplicantTypeChange('Individual'), 50);
            });
            
            suaCorporateLabel.addEventListener('click', function() {
                console.log('🔘 SuA Corporate label clicked');
                setTimeout(() => handleSuaApplicantTypeChange('Corporate'), 50);
            });
            
            suaMultipleLabel.addEventListener('click', function() {
                console.log('🔘 SuA Multiple label clicked');
                setTimeout(() => handleSuaApplicantTypeChange('Multiple'), 50);
            });
            
            // Also attach to radio buttons as backup
            const suaIndividualRadio = document.getElementById('sua_individual');
            const suaCorporateRadio = document.getElementById('sua_corporate');
            const suaMultipleRadio = document.getElementById('sua_multiple');
            
            if (suaIndividualRadio) {
                suaIndividualRadio.addEventListener('change', function() {
                    if (this.checked) handleSuaApplicantTypeChange('Individual');
                });
            }
            
            if (suaCorporateRadio) {
                suaCorporateRadio.addEventListener('change', function() {
                    if (this.checked) handleSuaApplicantTypeChange('Corporate');
                });
            }
            
            if (suaMultipleRadio) {
                suaMultipleRadio.addEventListener('change', function() {
                    if (this.checked) handleSuaApplicantTypeChange('Multiple');
                });
            }
            
            handlersAttached++;
            console.log('✅ SuA applicant handlers attached successfully');
        }
        
        // PuA tab handlers
        const puaIndividualLabel = document.querySelector('label[for="pua_individual"]');
        const puaCorporateLabel = document.querySelector('label[for="pua_corporate"]');
        const puaMultipleLabel = document.querySelector('label[for="pua_multiple"]');
        
        if (puaIndividualLabel && puaCorporateLabel && puaMultipleLabel) {
            console.log('✅ Found PuA applicant labels, attaching click handlers');
            
            puaIndividualLabel.addEventListener('click', function() {
                console.log('🔘 PuA Individual label clicked');
                setTimeout(() => handlePuaApplicantTypeChange('Individual'), 50);
            });
            
            puaCorporateLabel.addEventListener('click', function() {
                console.log('🔘 PuA Corporate label clicked');
                setTimeout(() => handlePuaApplicantTypeChange('Corporate'), 50);
            });
            
            puaMultipleLabel.addEventListener('click', function() {
                console.log('🔘 PuA Multiple label clicked');
                setTimeout(() => handlePuaApplicantTypeChange('Multiple'), 50);
            });
            
            // Also attach to radio buttons as backup
            const puaIndividualRadio = document.getElementById('pua_individual');
            const puaCorporateRadio = document.getElementById('pua_corporate');
            const puaMultipleRadio = document.getElementById('pua_multiple');
            
            if (puaIndividualRadio) {
                puaIndividualRadio.addEventListener('change', function() {
                    if (this.checked) handlePuaApplicantTypeChange('Individual');
                });
            }
            
            if (puaCorporateRadio) {
                puaCorporateRadio.addEventListener('change', function() {
                    if (this.checked) handlePuaApplicantTypeChange('Corporate');
                });
            }
            
            if (puaMultipleRadio) {
                puaMultipleRadio.addEventListener('change', function() {
                    if (this.checked) handlePuaApplicantTypeChange('Multiple');
                });
            }
            
            handlersAttached++;
            console.log('✅ PuA applicant handlers attached successfully');
        }
        
        return handlersAttached > 0;
    }
    
    // Try to attach handlers with retries
    if (!attachHandlers()) {
        console.log('⏳ Elements not ready, retrying...');
        setTimeout(() => {
            if (!attachHandlers()) {
                setTimeout(attachHandlers, 1000); // Final retry after 1 second
            }
        }, 500);
    }
}

// Make functions globally available
window.handlePrimaryApplicantTypeChange = handlePrimaryApplicantTypeChange;
window.handleSuaApplicantTypeChange = handleSuaApplicantTypeChange;
window.handlePuaApplicantTypeChange = handlePuaApplicantTypeChange;
window.initializeMultipleOwners = initializeMultipleOwners;
window.addMoreOwner = addMoreOwner;
window.removeOwner = removeOwner;
window.initializeAllApplicantHandlers = initializeAllApplicantHandlers;

// Auto-initialize
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAllApplicantHandlers);
} else {
    initializeAllApplicantHandlers();
}

console.log('All Tabs Applicant Fix JavaScript loaded');