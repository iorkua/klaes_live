{{-- Property Record Transaction Modal for File Indexing --}}
<!-- Alpine.js is already loaded in the parent view -->

<div id="property-transaction-dialog" class="property-transaction-overlay hidden" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 10000; display: none; align-items: center; justify-content: center; background-color: rgba(0, 0, 0, 0.5);">
    <div class="dialog-content property-form-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
        <div class="flex justify-between items-center mb-4">
            <h2 id="transaction-form-title" class="text-xl font-bold">Add Property Transaction Details</h2>
            <button id="close-property-transaction-form" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div x-data="{
            // Transaction fields
            transactions: [{
                id: 1,
                transactionType: '',
                transactionDate: '',
                serialNo: '',
                pageNo: '',
                volumeNo: '',
                regDate: new Date().toISOString().split('T')[0],
                regTime: '09:00',
                landUse: '',
                period: '',
                periodUnit: 'Years',
                firstParty: '',
                secondParty: ''
            }],

            // Party labels for different transaction types
            partyLabels: {
                'Power of Attorney': { first: 'Grantor', second: 'Grantee' },
                'Deed of Assignment': { first: 'Assignor', second: 'Assignee' },
                'ST Assignment': { first: 'Assignor', second: 'Assignee' },
                'Deed of Mortgage': { first: 'Mortgagor', second: 'Mortgagee' },
                'Tripartite Mortgage': { first: 'Mortgagor', second: 'Mortgagee' },
                'Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
                'ST Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
                'SLTR Certificate of Occupancy': { first: 'Grantor', second: 'Grantee' },
                'Customary Right of Occupancy': { first: 'Grantor', second: 'Grantee' },
                'Deed of Transfer': { first: 'Transferor', second: 'Transferee' },
                'Deed of Gift': { first: 'Donor', second: 'Donee' },
                'Deed of Lease': { first: 'Lessor', second: 'Lessee' },
                'Deed of Sub Lease': { first: 'Lessor', second: 'Lessee' },
                'Deed of Sub Under Lease': { first: 'Lessor', second: 'Lessee' },
                'Indenture of Lease': { first: 'Lessor', second: 'Lessee' },
                'Quarry Lease': { first: 'Lessor', second: 'Lessee' },
                'Private Lease': { first: 'Lessor', second: 'Lessee' },
                'Building Lease': { first: 'Lessor', second: 'Lessee' },
                'Tenancy Agreement': { first: 'Landlord', second: 'Tenant' },
                'Deed of Release': { first: 'Releasor', second: 'Releasee' },
                'Deed of Surrender': { first: 'Surrenderor', second: 'Surrenderee' },
                'Letter of Administration': { first: 'Administrator', second: 'Beneficiary' },
                'Certificate of Purchase': { first: 'Vendor', second: 'Purchaser' }
            },

            // File indexing data (will be populated from outside)
            fileIndexingData: {},

            // Add new transaction
            addTransaction() {
                this.transactions.push({
                    id: this.transactions.length + 1,
                    transactionType: '',
                    transactionDate: '',
                    serialNo: '',
                    pageNo: '',
                    volumeNo: '',
                    regDate: new Date().toISOString().split('T')[0],
                    regTime: '09:00',
                    landUse: '',
                    period: '',
                    periodUnit: 'Years',
                    firstParty: '',
                    secondParty: ''
                });
            },

            // Remove transaction
            removeTransaction(index) {
                if (this.transactions.length > 1) {
                    this.transactions.splice(index, 1);
                }
            },

            // Get party labels for a transaction
            getPartyLabels(transactionType) {
                return this.partyLabels[transactionType] || { first: 'Grantor', second: 'Grantee' };
            },

            // Check if transaction type is government
            isGovernmentTransaction(transactionType) {
                const govTypes = ['Certificate of Occupancy', 'ST Certificate of Occupancy', 'SLTR Certificate of Occupancy', 'Customary Right of Occupancy', 'Occupation Permit'];
                return govTypes.includes(transactionType);
            },

            // Get registration number preview
            getRegNumberPreview(transaction) {
                const parts = [transaction.serialNo, transaction.pageNo, transaction.volumeNo].filter(Boolean);
                return parts.length > 0 ? parts.join('/') : '';
            },

            // Auto-sync page number with serial number
            syncPageNo(transaction) {
                transaction.pageNo = transaction.serialNo;
            },

            // Get property description handling both indexing and edit page formats
            getPropertyDescription(data) {
                // Edit page format: uses property_description (district + lga)
                if (data.property_description) {
                    return data.property_description;
                }
                
                // Indexing page format: uses location field
                if (data.location) {
                    return data.location;
                }
                
                // Fallback: construct from district and lga if available
                const district = data.district || '';
                const lga = data.lga || '';
                if (district || lga) {
                    return [district, lga].filter(Boolean).join(', ');
                }
                
                return 'N/A';
            },

            // Auto-fill first party for government transactions
            handleTransactionTypeChange(transaction) {
                const govTypes = ['Certificate of Occupancy', 'ST Certificate of Occupancy', 'SLTR Certificate of Occupancy', 'Customary Right of Occupancy', 'Occupation Permit'];
                if (govTypes.includes(transaction.transactionType)) {
                    transaction.firstParty = 'KANO STATE GOVERNMENT';
                } else {
                    if (transaction.firstParty === 'KANO STATE GOVERNMENT') {
                        transaction.firstParty = '';
                    }
                }
            },

            // Submit form
            submitTransactions() {
                console.log('Submitting transactions:', this.transactions);
                console.log('File indexing data:', this.fileIndexingData);

                // Validate that at least one transaction has required fields
                const hasValidTransaction = this.transactions.some(t => 
                    t.transactionType && t.transactionDate
                );

                if (!hasValidTransaction) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error',
                            text: 'Please fill in at least one transaction with Transaction Type and Date.',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Please fill in at least one transaction with Transaction Type and Date.');
                    }
                    return;
                }

                // Call the global submission handler
                if (typeof submitPropertyTransactions === 'function') {
                    submitPropertyTransactions(this.transactions, this.fileIndexingData);
                } else {
                    console.error('submitPropertyTransactions function not found');
                }
            }
        }" x-init="
            // Watch for changes and sync page numbers
            $watch('transactions', (value) => {
                value.forEach(t => {
                    if (t.serialNo && !t.pageNo) {
                        t.pageNo = t.serialNo;
                    }
                });
            }, { deep: true });
        ">
            <form id="property-transaction-form" @submit.prevent="submitTransactions">
                @csrf

                <div class="space-y-4 py-2 flex-1">
                    <!-- Info Box showing file indexing details -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-blue-800 mb-2">File Indexing Information</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm text-blue-700">
                                    <div><strong>File Number:</strong> <span x-text="fileIndexingData.file_number || 'N/A'"></span></div>
                                    <div><strong>LGA:</strong> <span x-text="fileIndexingData.lga || 'N/A'"></span></div>
                                    <div><strong>Plot No:</strong> <span x-text="fileIndexingData.plot_no || fileIndexingData.plot_number || 'N/A'"></span></div>
                                    <div><strong>TP No:</strong> <span x-text="fileIndexingData.tp_no || 'N/A'"></span></div>
                                    <div class="col-span-2"><strong>Property Description:</strong> <span x-text="getPropertyDescription(fileIndexingData)"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Transactions Container -->
                    <template x-for="(transaction, index) in transactions" :key="transaction.id">
                        <div class="border border-gray-300 rounded-lg p-4 mb-4 bg-white shadow-sm">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="text-lg font-semibold text-gray-700">
                                    Transaction <span x-text="index + 1"></span>
                                </h3>
                                <button type="button" 
                                        @click="removeTransaction(index)" 
                                        x-show="transactions.length > 1"
                                        class="text-red-500 hover:text-red-700 text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <div class="space-y-3">
                                <!-- Transaction Type and Date - 2x2 Grid -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Type <span class="text-red-500">*</span></label>
                                        <select x-model="transaction.transactionType" 
                                                @change="handleTransactionTypeChange(transaction)"
                                                :name="'transactions[' + index + '][transaction_type]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors bg-white">
                                                <option value="">Select type</option>
                                                <option value="Deed of Transfer">Deed of Transfer</option>
                                                <option value="Certificate of Occupancy">Certificate of Occupancy</option>
                                                <option value="ST Certificate of Occupancy">ST Certificate of Occupancy</option>
                                                <option value="SLTR Certificate of Occupancy">SLTR Certificate of Occupancy</option>
                                                <option value="Irrevocable Power of Attorney">Irrevocable Power of Attorney</option>
                                                <option value="Deed of Release">Deed of Release</option>
                                                <option value="Deed of Assignment">Deed of Assignment</option>
                                                <option value="ST Assignment">ST Assignment</option>
                                                <option value="Deed of Mortgage">Deed of Mortgage</option>
                                                <option value="Tripartite Mortgage">Tripartite Mortgage</option>
                                                <option value="Deed of Sub Lease">Deed of Sub Lease</option>
                                                <option value="Power of Attorney">Power of Attorney</option>
                                                <option value="Deed of Surrender">Deed of Surrender</option>
                                                <option value="Indenture of Lease">Indenture of Lease</option>
                                                <option value="Deed of Variation">Deed of Variation</option>
                                                <option value="Customary Right of Occupancy">Customary Right of Occupancy</option>
                                                <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction/Certificate Date <span class="text-red-500">*</span></label>
                                        <input type="date" 
                                               x-model="transaction.transactionDate"
                                               :name="'transactions[' + index + '][transaction_date]'"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors">
                                    </div>
                                </div>

                                <!-- Registration Number -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
                                    <div class="grid grid-cols-5 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Serial No.</label>
                                            <input type="text" 
                                                   x-model="transaction.serialNo" 
                                                   @input="syncPageNo(transaction)"
                                                   :name="'transactions[' + index + '][serial_no]'"
                                                   class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors" 
                                                   placeholder="e.g. 1">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Page No. (Auto-filled)</label>
                                            <input type="text" 
                                                   x-model="transaction.pageNo"
                                                   :name="'transactions[' + index + '][page_no]'"
                                                   readonly 
                                                   class="w-full px-2 py-1.5 text-xs border border-gray-200 rounded-md bg-gray-50 text-gray-500 cursor-not-allowed" 
                                                   placeholder="Same as Serial">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Volume No.</label>
                                            <input type="text" 
                                                   x-model="transaction.volumeNo"
                                                   :name="'transactions[' + index + '][volume_no]'"
                                                   class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors" 
                                                   placeholder="e.g. 2">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Reg Date</label>
                                            <input type="date" 
                                                   x-model="transaction.regDate"
                                                   :name="'transactions[' + index + '][reg_date]'"
                                                   class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-600 mb-1">Reg Time</label>
                                            <input type="time" 
                                                   x-model="transaction.regTime"
                                                   :name="'transactions[' + index + '][reg_time]'"
                                                   class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors">
                                        </div>
                                    </div>

                                    <!-- Registration Number Preview -->
                                    <div x-show="getRegNumberPreview(transaction)" 
                                         x-transition 
                                         class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded">
                                        <span class="text-sm font-semibold text-blue-700">Registration Number:</span>
                                        <span class="text-sm font-bold text-blue-800 ml-2" x-text="getRegNumberPreview(transaction)"></span>
                                    </div>
                                </div>

                                <!-- Land Use and Period -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Land Use</label>
                                        <select x-model="transaction.landUse"
                                                :name="'transactions[' + index + '][land_use]'"
                                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors bg-white">
                                            <option value="">Select land use</option>
                                            <option value="RESIDENTIAL">RESIDENTIAL</option>
                                            <option value="AGRICULTURAL">AGRICULTURAL</option>
                                            <option value="COMMERCIAL">COMMERCIAL</option>
                                            <option value="INDUSTRIAL">INDUSTRIAL</option>
                                            <option value="RESIDENTIAL/COMMERCIAL">RESIDENTIAL/COMMERCIAL</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Period/Tenancy</label>
                                        <div class="flex space-x-2">
                                            <input type="number" 
                                                   x-model="transaction.period"
                                                   :name="'transactions[' + index + '][period]'"
                                                   class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors" 
                                                   placeholder="Period">
                                            <select x-model="transaction.periodUnit"
                                                    :name="'transactions[' + index + '][period_unit]'"
                                                    class="w-24 px-2 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors bg-white">
                                                <option value="Days">Days</option>
                                                <option value="Months">Months</option>
                                                <option value="Years">Years</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transaction Parties -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="getPartyLabels(transaction.transactionType).first"></label>
                                        <input type="text" 
                                               x-model="transaction.firstParty"
                                               :name="'transactions[' + index + '][first_party]'"
                                               :class="isGovernmentTransaction(transaction.transactionType) ? 'w-full px-3 py-2 text-sm border border-gray-200 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-not-allowed' : 'w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors'"
                                               :readonly="isGovernmentTransaction(transaction.transactionType)"
                                               :placeholder="isGovernmentTransaction(transaction.transactionType) ? 'KANO STATE GOVERNMENT' : 'Enter name'">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" x-text="getPartyLabels(transaction.transactionType).second"></label>
                                        <input type="text" 
                                               x-model="transaction.secondParty"
                                               :name="'transactions[' + index + '][second_party]'"
                                               class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-gray-400 transition-colors" 
                                               placeholder="Enter name">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Add Transaction Button -->
                    <div class="flex justify-center mt-4">
                        <button type="button" 
                                @click="addTransaction()" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 hover:border-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add Another Transaction
                        </button>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 mt-6">
                        <button type="button" 
                                id="cancel-property-transaction" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors shadow-sm">
                            Save Transaction Details
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Property Transaction Modal overlay styles - Use unique class name to avoid conflicts */
.property-transaction-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.property-transaction-overlay.hidden {
    display: none !important;
}

.property-transaction-overlay .dialog-content {
    background: white;
    border-radius: 0.5rem;
    padding: 1.5rem;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    position: relative;
    z-index: 10001;
}

.property-form-content {
    width: 100%;
    max-width: 900px;
}

/* Ensure SweetAlert appears above this modal */
.swal2-container {
    z-index: 20000 !important;
}
</style>

<script>
// Global function to open the property transaction modal
// IMPORTANT: Defined outside DOMContentLoaded to be globally accessible immediately
function openPropertyTransactionModal(fileIndexingData) {
    console.log('Opening property transaction modal with data:', fileIndexingData);
    
    const modal = document.getElementById('property-transaction-dialog');
    if (!modal) {
        console.error('Property transaction modal not found!');
        return;
    }
    
    console.log('Modal element found:', modal);
    
    // Try to get Alpine component and set file indexing data
    try {
        const alpineElement = modal.querySelector('[x-data]');
        console.log('Alpine element:', alpineElement);
        
        if (alpineElement && typeof Alpine !== 'undefined') {
            // Wait for Alpine to be ready
            setTimeout(() => {
                try {
                    const alpineComponent = Alpine.$data(alpineElement);
                    if (alpineComponent) {
                        console.log('Alpine component found, setting data...');
                        alpineComponent.fileIndexingData = fileIndexingData;
                        
                        // Check if existing records exist and populate transactions
                        if (fileIndexingData.existing_records && fileIndexingData.existing_records.length > 0) {
                            console.log('Found existing records, populating transactions:', fileIndexingData.existing_records.length);
                            console.log('Sample existing record structure:', fileIndexingData.existing_records[0]);
                            
                            // Helper function to extract party names from database record
                            function extractPartyNames(record) {
                                // Check for specific party fields based on transaction type
                                const partyFields = [
                                    ['Grantor', 'Grantee'],
                                    ['Assignor', 'Assignee'], 
                                    ['Mortgagor', 'Mortgagee'],
                                    ['Surrenderor', 'Surrenderee'],
                                    ['Lessor', 'Lessee'],
                                    ['Landlord', 'Tenant'],
                                    ['Releasor', 'Releasee'],
                                    ['Transferor', 'Transferee'],
                                    ['Donor', 'Donee'],
                                    ['Administrator', 'Beneficiary'],
                                    ['Vendor', 'Purchaser']
                                ];
                                
                                let firstParty = '';
                                let secondParty = '';
                                
                                // Check each possible party field combination
                                for (let [first, second] of partyFields) {
                                    if (record[first] || record[second]) {
                                        firstParty = record[first] || '';
                                        secondParty = record[second] || '';
                                        break;
                                    }
                                }
                                
                                // Fallback to generic fields if available
                                if (!firstParty && !secondParty) {
                                    firstParty = record.first_party || record.firstParty || '';
                                    secondParty = record.second_party || record.secondParty || '';
                                }
                                
                                return { firstParty, secondParty };
                            }
                            
                            // Convert existing records to transaction format
                            alpineComponent.transactions = fileIndexingData.existing_records.map((record, index) => {
                                const parties = extractPartyNames(record);
                                
                                return {
                                    id: index + 1,
                                    transactionType: record.transaction_type || record.transactionType || '',
                                    transactionDate: record.transaction_date || record.transactionDate || '',
                                    serialNo: record.serialNo || record.serial_no || '',
                                    pageNo: record.pageNo || record.page_no || '',
                                    volumeNo: record.volumeNo || record.volume_no || '',
                                    regDate: record.regDate || record.reg_date || new Date().toISOString().split('T')[0],
                                    regTime: record.regTime || record.reg_time || '09:00',
                                    landUse: record.landUse || record.land_use || fileIndexingData.land_use_type || '',
                                    period: record.period || '',
                                    periodUnit: record.periodUnit || record.period_unit || 'Years',
                                    firstParty: parties.firstParty,
                                    secondParty: parties.secondParty
                                };
                            });
                            
                            console.log('Populated transactions from existing records:', alpineComponent.transactions);
                            
                            // Update modal title to "Update"
                            const titleElement = document.getElementById('transaction-form-title');
                            if (titleElement) {
                                titleElement.textContent = 'Update Property Transaction Details';
                            }
                        } else {
                            console.log('No existing records, creating empty transaction');
                            
                            // Update modal title to "Add"
                            const titleElement = document.getElementById('transaction-form-title');
                            if (titleElement) {
                                titleElement.textContent = 'Add Property Transaction Details';
                            }
                            
                            // Create single empty transaction
                            alpineComponent.transactions = [{
                                id: 1,
                                transactionType: '',
                                transactionDate: '',
                                serialNo: '',
                                pageNo: '',
                                volumeNo: '',
                                regDate: new Date().toISOString().split('T')[0],
                                regTime: '09:00',
                                landUse: fileIndexingData.land_use_type || '',
                                period: '',
                                periodUnit: 'Years',
                                firstParty: '',
                                secondParty: ''
                            }];
                        }
                        console.log('Data set successfully');
                    } else {
                        console.warn('Alpine component not found, modal will still open');
                    }
                } catch (e) {
                    console.error('Error setting Alpine data:', e);
                }
            }, 100);
        } else {
            console.warn('Alpine not ready or element not found, modal will still open');
        }
    } catch (e) {
        console.error('Error accessing Alpine:', e);
    }
    
    // Show the modal regardless
    modal.classList.remove('hidden');
    modal.style.display = 'flex';
    console.log('Modal should now be visible');
}

// Global function to close the property transaction modal
function closePropertyTransactionModal() {
    const modal = document.getElementById('property-transaction-dialog');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Make functions globally accessible
window.openPropertyTransactionModal = openPropertyTransactionModal;
window.closePropertyTransactionModal = closePropertyTransactionModal;

// Unified property description function for form submission
function getUnifiedPropertyDescription(data) {
    // Edit page format: uses property_description (district + lga)
    if (data.property_description) {
        return data.property_description;
    }
    
    // Indexing page format: uses location field
    if (data.location) {
        return data.location;
    }
    
    // Fallback: construct from district and lga if available
    const district = data.district || '';
    const lga = data.lga || '';
    if (district || lga) {
        return [district, lga].filter(Boolean).join(', ');
    }
    
    return '';
}

// Global function to submit property transactions
function submitPropertyTransactions(transactions, fileIndexingData) {
    console.log('=== SUBMITTING PROPERTY TRANSACTIONS ===');
    console.log('1. File Indexing Data received:', fileIndexingData);
    console.log('2. File Number:', fileIndexingData?.file_number);
    console.log('3. Original transactions:', transactions);
    
    // Validate file indexing data
    if (!fileIndexingData || !fileIndexingData.file_number) {
        console.error('ERROR: File indexing data or file number is missing!');
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'File number is missing. Please close the modal and try again.',
                confirmButtonText: 'OK'
            });
        } else {
            alert('File number is missing. Please close the modal and try again.');
        }
        return;
    }
    
    // Convert camelCase field names to snake_case for backend
    const convertedTransactions = transactions.map(t => ({
        transaction_type: t.transactionType || '',
        instrument_type: t.instrumentType || '',
        transaction_date: t.transactionDate || '',
        serial_no: t.serialNo || '',
        page_no: t.pageNo || '',
        volume_no: t.volumeNo || '',
        reg_date: t.regDate || '',
        reg_time: t.regTime || '',
        land_use: t.landUse || '',
        period: t.period || '',
        period_unit: t.periodUnit || 'Years',
        first_party: t.firstParty || '',
        second_party: t.secondParty || ''
    }));
    
    console.log('4. Converted transactions:', convertedTransactions);
    
    // Prepare data for submission with unified field handling
    const formData = {
        file_number: fileIndexingData.file_number,
        file_title: fileIndexingData.file_title,
        plot_no: fileIndexingData.plot_no || fileIndexingData.plot_number, // Handle both formats
        tp_no: fileIndexingData.tp_no,
        lpkn_no: fileIndexingData.lpkn_no,
        lga: fileIndexingData.lga,
        district: fileIndexingData.district,
        property_description: getUnifiedPropertyDescription(fileIndexingData),
        transactions: convertedTransactions
    };
    
    console.log('5. Final form data to submit:', formData);
    console.log('6. Form data as JSON:', JSON.stringify(formData, null, 2));

    // Submit to server
    $.ajax({
        url: '{{ route("property-records.storeFromIndexing") }}',
        method: 'POST',
        data: formData,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            console.log('Success:', response);
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Property transaction details saved successfully!',
                    confirmButtonText: 'OK'
                }).then(() => {
                    closePropertyTransactionModal();
                });
            } else {
                alert(response.message || 'Property transaction details saved successfully!');
                closePropertyTransactionModal();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            console.error('Response:', xhr.responseText);
            console.error('Full XHR:', xhr);
            
            let errorMessage = 'An error occurred while saving transaction details.';
            let errorDetails = '';
            
            if (xhr.responseJSON) {
                console.error('Response JSON:', xhr.responseJSON);
                
                if (xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                // Show validation errors if present
                if (xhr.responseJSON.errors) {
                    errorDetails = '<ul style="text-align: left; margin-top: 10px;">';
                    Object.keys(xhr.responseJSON.errors).forEach(field => {
                        const messages = xhr.responseJSON.errors[field];
                        messages.forEach(msg => {
                            errorDetails += `<li>${msg}</li>`;
                        });
                    });
                    errorDetails += '</ul>';
                }
            }
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    html: errorMessage + errorDetails,
                    confirmButtonText: 'OK',
                    width: '600px'
                });
            } else {
                alert(errorMessage + (errorDetails ? '\n\nValidation Errors:\n' + errorDetails.replace(/<[^>]*>/g, '') : ''));
            }
        }
    });
}



// Initialize close button handlers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const propertyTransactionDialog = document.getElementById('property-transaction-dialog');
    const closePropertyTransactionBtn = document.getElementById('close-property-transaction-form');
    const cancelPropertyTransactionBtn = document.getElementById('cancel-property-transaction');

    // Close modal handlers
    if (closePropertyTransactionBtn) {
        closePropertyTransactionBtn.addEventListener('click', function() {
            closePropertyTransactionModal();
        });
    }

    if (cancelPropertyTransactionBtn) {
        cancelPropertyTransactionBtn.addEventListener('click', function() {
            closePropertyTransactionModal();
        });
    }

    // Close on overlay click
    if (propertyTransactionDialog) {
        propertyTransactionDialog.addEventListener('click', function(e) {
            if (e.target === propertyTransactionDialog) {
                closePropertyTransactionModal();
            }
        });
    }
});
</script>

