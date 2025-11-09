<script>
    // Initialize Lucide icons
    // Removed duplicate immediate initialization; handled in init()

    // State management
    let currentInstrumentType = null;
    let tempFileCounter = 1;

    // Complete instrument type definitions for updated types
    const instrumentTypes = {
        'power-of-attorney': {
            id: 'power-of-attorney',
            name: 'Power of Attorney',
            firstParty: 'Grantor',
            secondParty: 'Grantee',
            needsRootReg: true
        },
        'irrevocable-power-of-attorney': {
            id: 'irrevocable-power-of-attorney',
            name: 'Irrevocable Power of Attorney',
            firstParty: 'Grantor',
            secondParty: 'Grantee',
            needsRootReg: true
        },
        'deed-of-mortgage': {
            id: 'deed-of-mortgage',
            name: 'Deed of Mortgage',
            firstParty: 'Mortgagor',
            secondParty: 'Mortgagee',
            needsRootReg: true
        },
        'tripartite-mortgage': {
            id: 'tripartite-mortgage',
            name: 'Tripartite Mortgage',
            firstParty: 'Mortgagor',
            secondParty: 'Mortgagee',
            needsRootReg: true
        },
        'deed-of-assignment': {
            id: 'deed-of-assignment',
            name: 'Deed of Assignment',
            firstParty: 'Assignor',
            secondParty: 'Assignee',
            needsRootReg: true
        },
        'deed-of-lease': {
            id: 'deed-of-lease',
            name: 'Deed of Lease',
            firstParty: 'Lessor',
            secondParty: 'Lessee',
            needsRootReg: true
        },
        'deed-of-sub-lease': {
            id: 'deed-of-sub-lease',
            name: 'Deed of Sub-Lease',
            firstParty: 'Sub-Lessor',
            secondParty: 'Sub-Lessee',
            needsRootReg: true
        },
        'deed-of-sub-division': {
            id: 'deed-of-sub-division',
            name: 'Deed of Sub-Division',
            firstParty: 'Subdivider',
            secondParty: 'Beneficiary',
            needsRootReg: true
        },
        'deed-of-merger': {
            id: 'deed-of-merger',
            name: 'Deed of Merger',
            firstParty: 'Transferor',
            secondParty: 'Transferee',
            needsRootReg: true
        },
        'deed-of-surrender-release': {
            id: 'deed-of-surrender-release',
            name: 'Deed of Surrender / Release',
            firstParty: 'Surrenderer/Releasor',
            secondParty: 'Surrenderee/Releasee',
            needsRootReg: true
        },
        'devolution-order': {
            id: 'devolution-order',
            name: 'Devolution Order',
            firstParty: 'Deceased Owner',
            secondParty: 'Heir/Beneficiary',
            needsRootReg: true
        },
        'deed-of-gift': {
            id: 'deed-of-gift',
            name: 'Deed of Gift',
            firstParty: 'Donor',
            secondParty: 'Donee',
            needsRootReg: true
        },
        'occupancy-permit': {
            id: 'occupancy-permit',
            name: 'Occupancy Permit',
            firstParty: 'Grantor',
            secondParty: 'Grantee',
            needsRootReg: true,
            autoSetGrantor: true
        }
    };

    // DOM elements
    const elements = {
        registrationDialog: document.getElementById('registration-dialog'),
        dialogTitle: document.getElementById('dialog-title'),
        registrationForm: document.getElementById('registration-form'),
        cancelBtn: document.getElementById('cancel-btn'),
        submitBtn: document.getElementById('submit-btn'),
        isTemporaryFileNo: document.getElementById('isTemporaryFileNo'),
        isTemporaryRegNo: document.getElementById('isTemporaryRegNo'),
        temporaryFileNo: document.getElementById('temporaryFileNo'),
        regenerateTempBtn: document.getElementById('regenerate-temp-btn'),
        temporaryFileSection: document.getElementById('temporary-file-section'),
        regularFileSection: document.getElementById('regular-file-section'),
        regNoSection: document.getElementById('reg-no-section'),
        rootRegNoSection: document.getElementById('rootRegNoSection'),
        firstPartyTitle: document.getElementById('first-party-title'),
        firstPartyLabel: document.getElementById('first-party-label'),
        secondPartyTitle: document.getElementById('second-party-title'),
        secondPartyLabel: document.getElementById('second-party-label'),
        surveyInfo: document.getElementById('surveyInfo'),
        surveyInfoSection: document.getElementById('survey-info-section'),
        instrumentFields: document.getElementById('instrument-fields'),
        // New: generate particulars support
        generateRootBtn: document.getElementById('generate-root-btn'),
        rootRegNoInput: document.getElementById('rootRegNo'),
        generateParticularsUrlInput: document.getElementById('generateParticularsUrl')
    };

    // Helper functions
    function generateTemporaryFileNo() {
        const paddedCounter = String(tempFileCounter).padStart(4, '0');
        tempFileCounter++;
        return `TEMP-${paddedCounter}`;
    }

    // New: fetch next particulars registration number from server
    async function generateRootParticulars() {
        try {
            const url = elements.generateParticularsUrlInput?.value;
            if (!url) return;
            if (elements.generateRootBtn) {
                elements.generateRootBtn.disabled = true;
                elements.generateRootBtn.classList.add('opacity-60', 'cursor-not-allowed');
            }
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data?.success && data.rootRegistrationNumber && elements.rootRegNoInput) {
                elements.rootRegNoInput.value = data.rootRegistrationNumber;
            } else {
                console.error('Failed to generate particulars', data);
                alert(data?.message || 'Failed to generate registration particulars.');
            }
        } catch (e) {
            console.error('Error generating particulars', e);
            alert('Error generating registration particulars.');
        } finally {
            if (elements.generateRootBtn) {
                elements.generateRootBtn.disabled = false;
                elements.generateRootBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        }
    }

    function updatePartyLabels(instrumentType) {
        const type = instrumentTypes[instrumentType];
        if (!type) return;

        elements.firstPartyTitle.textContent = `${type.firstParty} Information`;
        elements.firstPartyLabel.textContent = `${type.firstParty} Name`;
        elements.secondPartyTitle.textContent = `${type.secondParty} Information`;
        elements.secondPartyLabel.textContent = `${type.secondParty} Name`;

        // Update address labels
        const firstPartyAddressTitle = document.getElementById('first-party-address-title');
        const secondPartyAddressTitle = document.getElementById('second-party-address-title');
        
        if (firstPartyAddressTitle) {
            firstPartyAddressTitle.textContent = `${type.firstParty} Address`;
        }
        if (secondPartyAddressTitle) {
            secondPartyAddressTitle.textContent = `${type.secondParty} Address`;
        }

        // Update placeholders
        document.getElementById('firstPartyName').placeholder = `Enter ${type.firstParty.toLowerCase()}'s full name`;
        document.getElementById('secondPartyName').placeholder = `Enter ${type.secondParty.toLowerCase()}'s full name`;
    }

    function renderInstrumentSpecificFields(instrumentType) {
        const fieldsContainer = elements.instrumentFields;
        fieldsContainer.innerHTML = '';

        switch (instrumentType) {
            case 'power-of-attorney':
            case 'irrevocable-power-of-attorney':
            case 'occupancy-permit':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="duration" class="label">Duration</label>
                        <input id="duration" name="duration" class="input" placeholder="Enter duration (e.g., 5 years)">
                    </div>
                `;
                break;
            case 'deed-of-mortgage':
            case 'tripartite-mortgage':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="bankName" class="label">Bank Name</label>
                        <input id="bankName" name="bankName" class="input" placeholder="Enter bank name">
                    </div>
                    <div class="space-y-2">
                        <label for="mortgageDate" class="label">Mortgage Date</label>
                        <input id="mortgageDate" name="mortgageDate" type="date" class="input">
                    </div>
                    <div class="space-y-2">
                        <label for="governorSignDate" class="label">Governor Sign Date</label>
                        <input id="governorSignDate" name="governorSignDate" type="date" class="input">
                    </div>
                `;
                break;
            case 'deed-of-assignment':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="assignmentTerm" class="label">Assignment Term</label>
                        <input id="assignmentTerm" name="assignmentTerm" class="input" placeholder="Enter assignment term">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoDate" class="label">CofO Date</label>
                        <input id="cofoDate" name="cofoDate" type="date" class="input">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoRegParticulars" class="label">CofO Reg Particulars</label>
                        <input id="cofoRegParticulars" name="cofoRegParticulars" class="input" placeholder="Enter CofO registration particulars">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoTerm" class="label">CofO Term</label>
                        <input id="cofoTerm" name="cofoTerm" class="input" placeholder="Enter CofO term">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoTermStartDate" class="label">CofO Term Start Date</label>
                        <input id="cofoTermStartDate" name="cofoTermStartDate" type="date" class="input">
                    </div>
                `;
                break;
            case 'deed-of-lease':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="leaseTerm" class="label">Lease Term</label>
                        <input id="leaseTerm" name="leaseTerm" class="input" placeholder="Enter lease term (e.g., 99 years)">
                    </div>
                    <div class="space-y-2">
                        <label for="annualRent" class="label">Annual Rent</label>
                        <input id="annualRent" name="annualRent" class="input" placeholder="Enter annual rent">
                    </div>
                    <div class="space-y-2">
                        <label for="chiefMagistrateSignDate" class="label">Chief Magistrate Sign Date</label>
                        <input id="chiefMagistrateSignDate" name="chiefMagistrateSignDate" type="date" class="input">
                    </div>
                `;
                break;
            case 'deed-of-sub-lease':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="leaseTerm" class="label">Lease Term</label>
                        <input id="leaseTerm" name="leaseTerm" class="input" placeholder="Enter lease term (e.g., 99 years)">
                    </div>
                    <div class="space-y-2">
                        <label for="subLeaseAmount" class="label">Sub-Lease Amount</label>
                        <input id="subLeaseAmount" name="subLeaseAmount" class="input" placeholder="Enter sub-lease amount">
                    </div>
                    <div class="space-y-2">
                        <label for="assignmentTerm" class="label">Assignment Term</label>
                        <input id="assignmentTerm" name="assignmentTerm" class="input" placeholder="Enter assignment term">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoDate" class="label">CofO Date</label>
                        <input id="cofoDate" name="cofoDate" type="date" class="input">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoRegParticulars" class="label">CofO Reg Particulars</label>
                        <input id="cofoRegParticulars" name="cofoRegParticulars" class="input" placeholder="Enter CofO registration particulars">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoTerm" class="label">CofO Term</label>
                        <input id="cofoTerm" name="cofoTerm" class="input" placeholder="Enter CofO term">
                    </div>
                    <div class="space-y-2">
                        <label for="cofoTermStartDate" class="label">CofO Term Start Date</label>
                        <input id="cofoTermStartDate" name="cofoTermStartDate" type="date" class="input">
                    </div>
                `;
                break;
                case 'deed-of-sub-division':
                    fieldsContainer.innerHTML = `
                        <div class="space-y-2">
                            <label for="numberOfPlots" class="label">Number of Plots</label>
                            <input id="numberOfPlots" name="numberOfPlots" type="number" class="input" placeholder="Enter number of plots">
                        </div>
                        <div class="space-y-2">
                            <label for="originalPlotSize" class="label">Original Plot Size</label>
                            <input id="originalPlotSize" name="originalPlotSize" class="input" placeholder="Enter original plot size">
                        </div>
                    `;
                    break;
                case 'deed-of-merger':
                    fieldsContainer.innerHTML = `
                        <div class="space-y-2">
                            <label for="mergerDate" class="label">Merger Date</label>
                            <input id="mergerDate" name="mergerDate" type="date" class="input">
                        </div>
                        <div class="space-y-2">
                            <label for="mergedEntities" class="label">Merged Entities</label>
                            <textarea id="mergedEntities" name="mergedEntities" class="textarea" placeholder="List entities involved in the merger"></textarea>
                        </div>
                        <div class="space-y-2">
                            <label for="newEntityName" class="label">New Entity Name</label>
                            <input id="newEntityName" name="newEntityName" class="input" placeholder="Enter name of the merged entity">
                        </div>
                        <div class="space-y-2">
                            <label for="effectiveDate" class="label">Effective Date</label>
                            <input id="effectiveDate" name="effectiveDate" type="date" class="input">
                        </div>
                    `;
                    break;
            case 'deed-of-surrender-release':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="surrenderReason" class="label">Reason for Surrender</label>
                        <input id="surrenderReason" name="surrenderReason" class="input" placeholder="Enter reason for surrender">
                    </div>
                    <div class="space-y-2">
                        <label for="compensationAmount" class="label">Compensation Amount</label>
                        <input id="compensationAmount" name="compensationAmount" class="input" placeholder="Enter compensation amount (if any)">
                    </div>
                    <div class="space-y-2">
                        <label for="bankName" class="label">Bank Name</label>
                        <input id="bankName" name="bankName" class="input" placeholder="Enter bank name">
                    </div>
                    <div class="space-y-2">
                        <label for="releaseRegParticulars" class="label">Release Reg Particulars</label>
                        <input id="releaseRegParticulars" name="releaseRegParticulars" class="input" placeholder="Enter release registration particulars">
                    </div>
                    <div class="space-y-2">
                        <label for="originalInstrumentRegParticulars" class="label">Original Instrument Reg Particulars</label>
                        <input id="originalInstrumentRegParticulars" name="originalInstrumentRegParticulars" class="input" placeholder="Enter original instrument registration particulars">
                    </div>
                    <div class="space-y-2">
                        <label for="releaseAmount" class="label">Release Amount</label>
                        <input id="releaseAmount" name="releaseAmount" class="input" placeholder="Enter release amount (if applicable)">
                    </div>
                `;
                break;
            case 'devolution-order':
                fieldsContainer.innerHTML = `
                    <div class="space-y-2">
                        <label for="deceasedName" class="label">Deceased Name</label>
                        <input id="deceasedName" name="deceasedName" class="input" placeholder="Enter deceased's full name">
                    </div>
                    <div class="space-y-2">
                        <label for="dateOfDeath" class="label">Date of Death</label>
                        <input id="dateOfDeath" name="dateOfDeath" type="date" class="input">
                    </div>
                    <div class="space-y-2">
                        <label for="willReference" class="label">Will Reference</label>
                        <input id="willReference" name="willReference" class="input" placeholder="Enter will reference number">
                    </div>
                `;
                break;
            case 'deed-of-gift':
                fieldsContainer.innerHTML = `
                    <!-- Section A - Instrument Metadata -->
                    <div class="border rounded-md p-4 bg-white mb-4">
                        <h4 class="font-medium mb-3 text-gray-800 border-b pb-2">Section A - Instrument Metadata</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="instrumentNo" class="label">Instrument No.</label>
                                <input id="instrumentNo" name="instrumentNo" class="input" placeholder="Enter instrument number">
                            </div>
                            <div class="space-y-2">
                                <label for="landUse" class="label">Land Use</label>
                                <input id="landUse" name="landUse" class="input" placeholder="Enter land use">
                            </div>
                            <div class="space-y-2">
                                <label for="dateOfExecution" class="label">Date of Execution</label>
                                <input id="dateOfExecution" name="dateOfExecution" type="date" class="input">
                            </div>
                            <div class="space-y-2">
                                <label for="dateOfRegistration" class="label">Date of Registration</label>
                                <input id="dateOfRegistration" name="dateOfRegistration" type="date" class="input">
                            </div>
                        </div>
                    </div>

                    <!-- Section B - Donor Details -->
                    <div class="border rounded-md p-4 bg-white mb-4">
                        <h4 class="font-medium mb-3 text-gray-800 border-b pb-2">Section B - Donor (Giver) Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="donorPhone" class="label">Phone/Email</label>
                                <input id="donorPhone" name="donorPhone" class="input" placeholder="Enter phone/email">
                            </div>
                            <div class="space-y-2">
                                <label for="donorNationality" class="label">Nationality</label>
                                <input id="donorNationality" name="donorNationality" class="input" placeholder="Enter nationality">
                            </div>
                            <div class="space-y-2">
                                <label for="donorIdDocument" class="label">Identification Document</label>
                                <select id="donorIdDocument" name="donorIdDocument" class="select">
                                    <option value="">Select ID Type</option>
                                    <option value="National ID">National ID</option>
                                    <option value="International Passport">International Passport</option>
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="Voter's Card">Voter's Card</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="donorIdNumber" class="label">ID Number</label>
                                <input id="donorIdNumber" name="donorIdNumber" class="input" placeholder="Enter ID number">
                            </div>
                        </div>
                    </div>

                    <!-- Section C - Donee Details -->
                    <div class="border rounded-md p-4 bg-white mb-4">
                        <h4 class="font-medium mb-3 text-gray-800 border-b pb-2">Section C - Donee (Receiver) Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="doneePhone" class="label">Phone/Email</label>
                                <input id="doneePhone" name="doneePhone" class="input" placeholder="Enter phone/email">
                            </div>
                            <div class="space-y-2">
                                <label for="doneeNationality" class="label">Nationality</label>
                                <input id="doneeNationality" name="doneeNationality" class="input" placeholder="Enter nationality">
                            </div>
                            <div class="space-y-2">
                                <label for="doneeIdDocument" class="label">Identification Document</label>
                                <select id="doneeIdDocument" name="doneeIdDocument" class="select">
                                    <option value="">Select ID Type</option>
                                    <option value="National ID">National ID</option>
                                    <option value="International Passport">International Passport</option>
                                    <option value="Driver's License">Driver's License</option>
                                    <option value="Voter's Card">Voter's Card</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label for="doneeIdNumber" class="label">ID Number</label>
                                <input id="doneeIdNumber" name="doneeIdNumber" class="input" placeholder="Enter ID number">
                            </div>
                        </div>
                    </div>

                    <!-- Section D - Gifted Property Information -->
                    <div class="border rounded-md p-4 bg-white mb-4">
                        <h4 class="font-medium mb-3 text-gray-800 border-b pb-2">Section D - Gifted Property Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="surveyPlanNo" class="label">Survey Plan No.</label>
                                <input id="surveyPlanNo" name="surveyPlanNo" class="input" placeholder="Enter survey plan number">
                            </div>
                            <div class="space-y-2">
                                <label for="propertySize" class="label">Size (m²/Ha)</label>
                                <input id="propertySize" name="propertySize" class="input" placeholder="Enter property size">
                            </div>
                            <div class="space-y-2">
                                <label for="consideration" class="label">Consideration</label>
                                <input id="consideration" name="consideration" class="input" placeholder="Enter consideration (usually 'Love and Affection')">
                            </div>
                            <div class="space-y-2">
                                <label for="encumbrances" class="label">Encumbrances (if any)</label>
                                <input id="encumbrances" name="encumbrances" class="input" placeholder="Enter any encumbrances">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label for="supportingDocs" class="label">Supporting Docs</label>
                                <textarea id="supportingDocs" name="supportingDocs" class="textarea" placeholder="List supporting documents"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Section E - Registration -->
                    <div class="border rounded-md p-4 bg-white mb-4">
                        <h4 class="font-medium mb-3 text-gray-800 border-b pb-2">Section E - Registration</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label for="registrarName" class="label">Registrar's Name</label>
                                <input id="registrarName" name="registrarName" class="input" placeholder="Enter registrar's name">
                            </div>
                            <div class="space-y-2">
                                <label for="registrarSignature" class="label">Registrar's Signature</label>
                                <input id="registrarSignature" name="registrarSignature" class="input" placeholder="Registrar's signature reference">
                            </div>
                            <div class="space-y-2">
                                <label for="registrationDate" class="label">Registration Date</label>
                                <input id="registrationDate" name="registrationDate" type="date" class="input">
                            </div>
                            <div class="space-y-2">
                                <label for="volumePageNo" class="label">Volume & Page No.</label>
                                <input id="volumePageNo" name="volumePageNo" class="input" placeholder="Enter volume and page number">
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label for="blockchainHash" class="label">Blockchain Hash (if applicable)</label>
                                <input id="blockchainHash" name="blockchainHash" class="input" placeholder="Enter blockchain hash if applicable">
                            </div>
                        </div>
                    </div>
                `;
                break;
            default:
                // For any new types not handled above, leave blank or add a comment
                break;
        }
    }

    function openRegistrationDialog(instrumentType) {
        currentInstrumentType = instrumentType;
        const type = instrumentTypes[instrumentType];
        
        elements.dialogTitle.textContent = `Register ${type.name}`;
        updatePartyLabels(instrumentType);
        renderInstrumentSpecificFields(instrumentType);
        
        // Auto-set grantor for Occupancy Permit and other instruments with autoSetGrantor
        if (type.autoSetGrantor) {
            const firstPartyNameField = document.getElementById('firstPartyName');
            if (firstPartyNameField) {
                firstPartyNameField.value = 'Kano State Government';
                firstPartyNameField.readOnly = true;
                firstPartyNameField.style.backgroundColor = '#f3f4f6'; // Light gray background
                firstPartyNameField.style.cursor = 'not-allowed';
            }
            
            // Also set the address fields for Kano State Government
            const addressFields = {
                'firstPartyStreet': 'Government House',
                'firstPartyCity': 'Kano',
                'firstPartyState': 'Kano State',
                'firstPartyPostalCode': '700001',
                'firstPartyCountry': 'Nigeria'
            };
            
            Object.entries(addressFields).forEach(([fieldId, value]) => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = value;
                    field.readOnly = true;
                    field.style.backgroundColor = '#f3f4f6';
                    field.style.cursor = 'not-allowed';
                }
            });
            
            // Hide solicitor section for Occupancy Permit
            if (instrumentType === 'occupancy-permit') {
                const solicitorSection = document.getElementById('solicitor-section');
                if (solicitorSection) {
                    solicitorSection.style.display = 'none';
                }
            }
        } else {
            // Reset fields for other instrument types
            const firstPartyNameField = document.getElementById('firstPartyName');
            if (firstPartyNameField) {
                firstPartyNameField.value = '';
                firstPartyNameField.readOnly = false;
                firstPartyNameField.style.backgroundColor = '';
                firstPartyNameField.style.cursor = '';
            }
            
            const addressFieldIds = ['firstPartyStreet', 'firstPartyCity', 'firstPartyState', 'firstPartyPostalCode', 'firstPartyCountry'];
            addressFieldIds.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.value = '';
                    field.readOnly = false;
                    field.style.backgroundColor = '';
                    field.style.cursor = '';
                }
            });
            
            // Show solicitor section for other instruments
            const solicitorSection = document.getElementById('solicitor-section');
            if (solicitorSection) {
                solicitorSection.style.display = 'block';
            }
        }
        
        // Show/hide registration number sections based on whether instrument needs Root Reg
        const registrationDetailsSection = document.getElementById('registration-details-section');
        if (type.needsRootReg) {
            // For instruments that need Root Reg, show the registration details section
            if (registrationDetailsSection) {
                registrationDetailsSection.classList.remove('hidden');
            }
            
            // Initialize the temporary reg number section visibility
            handleTemporaryRegNoChange();
            // Auto-generate root particulars if not using temporary reg no
            if (elements.isTemporaryRegNo && !elements.isTemporaryRegNo.checked) {
                // Only auto-generate if empty to avoid overwriting user input
                if (elements.rootRegNoInput && !elements.rootRegNoInput.value) {
                    generateRootParticulars();
                }
            }
        } else {
            // For instruments that don't need Root Reg, hide the entire registration details section
            if (registrationDetailsSection) {
                registrationDetailsSection.classList.add('hidden');
            }
        }

        // Always update survey info section visibility based on checkbox state
        handleSurveyInfoChange();

        elements.registrationDialog.classList.remove('hidden');
    }

    function closeRegistrationDialog() {
        elements.registrationDialog.classList.add('hidden');
        currentInstrumentType = null;
        elements.registrationForm.reset();
        
        // Reset registration details section visibility
        const registrationDetailsSection = document.getElementById('registration-details-section');
        if (registrationDetailsSection) {
            registrationDetailsSection.classList.remove('hidden');
        }
        
        // Show solicitor section
        const solicitorSection = document.getElementById('solicitor-section');
        if (solicitorSection) {
            solicitorSection.style.display = 'block';
        }
        
        // Reset checkboxes to unchecked state
        if (elements.isTemporaryFileNo) {
            elements.isTemporaryFileNo.checked = false;
        }
        if (elements.isTemporaryRegNo) {
            elements.isTemporaryRegNo.checked = false;
        }
        
        // Reset section visibility
        handleTemporaryFileNoChange();
        handleTemporaryRegNoChange();
    }

    function handleTemporaryFileNoChange() {
        const isChecked = elements.isTemporaryFileNo.checked;
        if (isChecked) {
            elements.temporaryFileSection.classList.remove('hidden');
            elements.regularFileSection.classList.add('hidden');
            if (!elements.temporaryFileNo.value) {
                elements.temporaryFileNo.value = generateTemporaryFileNo();
            }
        } else {
            elements.temporaryFileSection.classList.add('hidden');
            elements.regularFileSection.classList.remove('hidden');
            elements.temporaryFileNo.value = '';
        }
    }

    function handleTemporaryRegNoChange() {
        const isChecked = elements.isTemporaryRegNo.checked;
        if (isChecked) {
            elements.regNoSection.classList.remove('hidden');
            elements.rootRegNoSection.classList.add('hidden');
            // When using temporary reg no, rootRegNo should not be required
            if (elements.rootRegNoInput) {
                elements.rootRegNoInput.removeAttribute('required');
            }
        } else {
            elements.regNoSection.classList.add('hidden');
            elements.rootRegNoSection.classList.remove('hidden');
            // If field is empty, auto-generate on toggle
            if (elements.rootRegNoInput) {
                // Make rootRegNo required when not using temporary registration number
                elements.rootRegNoInput.setAttribute('required', '');
                if (!elements.rootRegNoInput.value) {
                generateRootParticulars();
                }
            }
        }
    }

    function handleSurveyInfoChange() {
        console.log('Survey info changed'); // Debug log
        const surveyCheckbox = document.getElementById('surveyInfo');
        const surveySection = document.getElementById('survey-info-section');
        
        if (!surveyCheckbox || !surveySection) {
            console.error('Survey elements not found'); // Debug log
            return;
        }

        if (surveyCheckbox.checked) {
            surveySection.classList.remove('hidden');
        } else {
            surveySection.classList.add('hidden');
            // Clear survey fields
            document.getElementById('lga').value = '';
            document.getElementById('district').value = '';
            document.getElementById('plotNumber').value = '';
        }
    }

    function collectFormData() {
        const formData = new FormData(elements.registrationForm);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Add instrument type
        data.instrumentType = currentInstrumentType;
        
        // Add final file number (temporary or regular)
        if (elements.isTemporaryFileNo.checked) {
            data.finalFileNo = elements.temporaryFileNo.value;
            data.isTemporary = true;
        } else {
            // Get the active file number from the file number tabs
            const activeTab = document.getElementById('activeFileTab')?.value;
            if (activeTab === 'mlsFNo') {
                data.finalFileNo = document.getElementById('mlsFNo')?.value || '';
            } else if (activeTab === 'kangisFileNo') {
                data.finalFileNo = document.getElementById('kangisFileNo')?.value || '';
            } else if (activeTab === 'NewKANGISFileno') {
                data.finalFileNo = document.getElementById('NewKANGISFileno')?.value || '';
            }
            data.isTemporary = false;
        }
        
        return data;
    }

    // Helper to set or update a form control/hidden input on the form
    function setHidden(name, value) {
        // Prefer existing control (input/select/textarea) with the name
        let control = elements.registrationForm.querySelector(`[name="${name}"]`);
        if (!control) {
            // Create hidden input if not found
            control = document.createElement('input');
            control.type = 'hidden';
            control.name = name;
            elements.registrationForm.appendChild(control);
        }
        control.value = value == null ? '' : value;
    }

    function buildAddress(prefix) {
        const street = document.getElementById(`${prefix}Street`)?.value?.trim() || '';
        const city = document.getElementById(`${prefix}City`)?.value?.trim() || '';
        const state = document.getElementById(`${prefix}State`)?.value?.trim() || '';
        const postal = document.getElementById(`${prefix}PostalCode`)?.value?.trim() || '';
        const country = document.getElementById(`${prefix}Country`)?.value?.trim() || '';
        return [street, city, state, postal, country].filter(Boolean).join(', ');
    }

    function handleSubmit() {
        try {
            if (!currentInstrumentType) {
                alert('Please select an instrument type.');
                return;
            }

            // Sync file number hidden fields from the fileno widgets if available
            if (typeof updateFormFileData === 'function') {
                try { updateFormFileData(); } catch (e) { /* ignore */ }
            }

            // Instrument type name
            const typeDef = instrumentTypes[currentInstrumentType];
            const instrumentTypeName = typeDef ? typeDef.name : currentInstrumentType;
            setHidden('instrument_type', instrumentTypeName);

            // Parties
            const grantor = document.getElementById('firstPartyName')?.value?.trim() || '';
            const grantee = document.getElementById('secondPartyName')?.value?.trim() || '';
            setHidden('Grantor', grantor);
            setHidden('Grantee', grantee);

            // Addresses (combined)
            setHidden('GrantorAddress', buildAddress('firstParty'));
            setHidden('GranteeAddress', buildAddress('secondParty'));

            // Dates: use entryDate as instrumentDate
            const entryDate = document.getElementById('entryDate')?.value || '';
            setHidden('instrumentDate', entryDate);

            // Property details
            setHidden('propertyDescription', document.getElementById('plotDescription')?.value || '');
            setHidden('size', document.getElementById('plotSize')?.value || '');
            setHidden('solicitorName', document.getElementById('solicitorName')?.value || '');
            setHidden('solicitorAddress', document.getElementById('solicitorAddress')?.value || '');

            // Survey fields
            const surveyChecked = document.getElementById('surveyInfo')?.checked;
            setHidden('lga', surveyChecked ? (document.getElementById('lga')?.value || '') : '');
            setHidden('district', surveyChecked ? (document.getElementById('district')?.value || '') : '');
            setHidden('plotNumber', surveyChecked ? (document.getElementById('plotNumber')?.value || '') : '');

            // Root registration number
            // if (elements.isTemporaryRegNo && elements.isTemporaryRegNo.checked) {
            //     setHidden('rootRegistrationNumber', '0/0/0');
            // } else {
            //     setHidden('rootRegistrationNumber', document.getElementById('rootRegNo')?.value || '');
            // }

            // Duration (instrument-specific; safe to set if present)
            const durationEl = document.getElementById('duration');
            if (durationEl) setHidden('duration', durationEl.value || '');

            // File numbers
            let mls = '';
            let kagis = '';
            let newKagis = '';
            const isTemp = !!(elements.isTemporaryFileNo && elements.isTemporaryFileNo.checked);
            if (isTemp) {
                mls = elements.temporaryFileNo?.value || '';
                // Explicitly post temp_fileno and isTemporary for backend
                setHidden('temp_fileno', mls);
                setHidden('isTemporary', '1');
            } else {
                const activeTab = document.getElementById('activeFileTab')?.value;
                mls = document.getElementById('mlsFNo')?.value || '';
                kagis = document.getElementById('kangisFileNo')?.value || '';
                newKagis = document.getElementById('NewKANGISFileno')?.value || '';
                // Zero out non-active values
                if (activeTab === 'mlsFNo') { kagis = ''; newKagis = ''; }
                else if (activeTab === 'kangisFileNo') { mls = ''; newKagis = ''; }
                else if (activeTab === 'NewKANGISFileno') { mls = ''; kagis = ''; }
                // Ensure temp flags are cleared when not temporary
                setHidden('temp_fileno', '');
                setHidden('isTemporary', '0');
            }

            // Fallback: if all are empty but a generic fileno exists (smart selector), use it as MLS
            const genericFileno = document.getElementById('fileno')?.value || '';
            if (!mls && !kagis && !newKagis && genericFileno) {
                mls = genericFileno;
            }

            setHidden('mlsFNo', mls);
            setHidden('kangisFileNo', kagis);
            setHidden('NewKANGISFileno', newKagis);

            // Set action/method and submit
            const storeUrlInput = document.getElementById('storeUrl');
            const actionUrl = storeUrlInput ? storeUrlInput.value : '/instruments/store';
            elements.registrationForm.setAttribute('method', 'POST');
            elements.registrationForm.setAttribute('action', actionUrl);

            elements.registrationForm.submit();
        } catch (err) {
            console.error('Submit failed', err);
            alert('Failed to submit. Please try again.');
        }
    }

    // Event listeners (guard to avoid double-binding)
    if (!window.__instrumentCreateInit) {
        window.__instrumentCreateInit = true;

        document.querySelectorAll('.instrument-type-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.getAttribute('data-type');
                openRegistrationDialog(type);
            });
        });

        elements.cancelBtn?.addEventListener('click', closeRegistrationDialog);
        elements.submitBtn?.addEventListener('click', handleSubmit);

        elements.isTemporaryFileNo?.addEventListener('change', handleTemporaryFileNoChange);
        elements.isTemporaryRegNo?.addEventListener('change', handleTemporaryRegNoChange);
        elements.regenerateTempBtn?.addEventListener('click', () => {
            elements.temporaryFileNo.value = generateTemporaryFileNo();
        });
        // New: generate particulars click
        elements.generateRootBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            generateRootParticulars();
        });

        // Close dialog when clicking outside content
        elements.registrationDialog?.addEventListener('click', (e) => {
            if (e.target === elements.registrationDialog) {
                closeRegistrationDialog();
            }
        });
        // Prevent backdrop-close when clicking inside content
        const dialogContent = elements.registrationDialog?.querySelector('.dialog-content');
        dialogContent?.addEventListener('click', (e) => e.stopPropagation());
    }

    // Set default dates to today
    function setDefaultDates() {
        const today = new Date().toISOString().split('T')[0];
        const reg = document.getElementById('registrationDate');
        const entry = document.getElementById('entryDate');
        if (reg) reg.value = today;
        if (entry) entry.value = today;
    }

    // Initialize the page
    function init() {
        setDefaultDates();
        lucide.createIcons();

        // Add event listener for survey info checkbox
        const surveyCheckbox = document.getElementById('surveyInfo');
        if (surveyCheckbox) {
            surveyCheckbox.addEventListener('change', handleSurveyInfoChange);
        }

        // Initialize survey info section to be hidden
        handleSurveyInfoChange();

        // Initialize registration number section visibility
        handleTemporaryRegNoChange();
        // Initialize file number section visibility
        handleTemporaryFileNoChange();
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', init);
</script>