<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('CofO page loaded');
        
        // Initialize CofO DataTable immediately
        let cofoTable = null;
        
        function initializeCofOTable() {
            if (cofoTable) {
                console.log('CofO table already initialized');
                return;
            }
            
            console.log('Initializing CofO DataTable...');
            
            try {
                cofoTable = $('#cofo-records-table').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route("propertycard.getCofOData") }}',
                        type: 'GET',
                        error: function(xhr, error, thrown) {
                            console.error('CofO DataTables Ajax error:', error, thrown);
                            console.error('Response text:', xhr.responseText);
                        }
                    },
                    columns: [
                        {
                            data: null,
                            name: 'file_number',
                            render: function(data, type, row) {
                                if (row.kangisFileNo) {
                                    return row.kangisFileNo;
                                } else if (row.mlsFNo) {
                                    return row.mlsFNo;
                                } else if (row.NewKANGISFileno) {
                                    return row.NewKANGISFileno;
                                } else {
                                    return 'No File Number';
                                }
                            }
                        },
                        {
                            data: 'property_description',
                            name: 'property_description',
                            render: function(data, type, row) {
                                if (data && data.length > 30) {
                                    return data.substring(0, 30) + '...';
                                }
                                return data || 'No description';
                            }
                        },
                        {
                            data: 'location',
                            name: 'location',
                            render: function(data, type, row) {
                                return data || 'N/A';
                            }
                        },
                        {
                            data: 'regNo',
                            name: 'regNo',
                            render: function(data, type, row) {
                                return data || 'N/A';
                            }
                        },
                      
                        {
                            data: 'land_use',
                            name: 'land_use',
                            render: function(data, type, row) {
                                return data || 'N/A';
                            }
                        },
                        {
                            data: 'instrument_type',
                            name: 'instrument_type',
                            render: function(data, type, row) {
                                return data || 'N/A';
                            }
                        },
                        {
                            data: 'created_at',
                            name: 'created_at',
                            render: function(data, type, row) {
                                if (type === 'sort' || type === 'type') {
                                    return data ? new Date(data).getTime() : 0;
                                }
                                if (data) {
                                    const date = new Date(data);
                                    const formattedDate = date.toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'short',
                                        day: 'numeric'
                                    });
                                    const formattedTime = date.toLocaleTimeString('en-US', {
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });
                                    return `<div class="flex flex-col text-sm">
                                                <span class="font-medium">${formattedDate}</span>
                                                <span class="text-xs text-gray-500">${formattedTime}</span>
                                            </div>`;
                                }
                                return '<span class="text-gray-400">N/A</span>';
                            }
                        },
                        {
                            data: 'id',
                            name: 'actions',
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                return `
                                    <div class="flex items-center gap-2">
                                        <button class="text-blue-500 hover:text-blue-700 transition-colors view-cofo" data-id="${data}">
                                            <i data-lucide="eye" class="h-4 w-4 text-blue-500"></i>
                                        </button>
                                        <button class="text-green-500 hover:text-green-700 transition-colors edit-cofo" data-id="${data}">
                                            <i data-lucide="pencil" class="h-4 w-4 text-green-500"></i>
                                        </button>
                                        <button class="text-red-500 hover:text-red-700 transition-colors delete-cofo" data-id="${data}">
                                            <i data-lucide="trash-2" class="h-4 w-4 text-red-500"></i>
                                        </button>
                                    </div>
                                `;
                            }
                        }
                    ],
                    order: [[7, 'desc']], // Order by Date Captured column (index 7) in descending order
                    pageLength: 25,
                    responsive: true,
                    language: {
                        processing: "Loading CofO records...",
                        emptyTable: "No CofO records found",
                        zeroRecords: "No matching CofO records found"
                    },
                    drawCallback: function(settings) {
                        // Re-initialize Lucide icons after table redraw
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                        
                        // Re-attach event listeners to action buttons
                        setupCofOActions();
                    }
                });
                
                console.log('CofO DataTable initialized successfully');
            } catch (error) {
                console.error('Error initializing CofO DataTable:', error);
            }
        }

        // Setup CofO action buttons
        function setupCofOActions() {
            // Remove existing event listeners to prevent duplicates
            document.querySelectorAll('.view-cofo, .edit-cofo, .delete-cofo').forEach(button => {
                button.replaceWith(button.cloneNode(true));
            });

            // View CofO details
            document.querySelectorAll('.view-cofo').forEach(button => {
                button.addEventListener('click', function() {
                    const cofoId = this.getAttribute('data-id');
                    viewCofODetails(cofoId);
                });
            });

            // Edit CofO
            document.querySelectorAll('.edit-cofo').forEach(button => {
                button.addEventListener('click', function() {
                    const cofoId = this.getAttribute('data-id');
                    editCofO(cofoId);
                });
            });

            // Delete CofO
            document.querySelectorAll('.delete-cofo').forEach(button => {
                button.addEventListener('click', function() {
                    const cofoId = this.getAttribute('data-id');
                    deleteCofO(cofoId);
                });
            });
        }

        // Initialize the table immediately
        initializeCofOTable();

        // Delegate row click to load selected CofO details card
        $(document).on('click', '#cofo-records-table tbody tr', function(e) {
            // Ignore clicks on action buttons
            if ($(e.target).closest('.view-cofo, .edit-cofo, .delete-cofo').length) {
                return;
            }
            const $btn = $(this).find('.view-cofo');
            const cofoId = $btn.data('id');
            if (cofoId && typeof loadCofODetailsInCards === 'function') {
                // Highlight selection
                $('#cofo-records-table tbody tr').removeClass('selected-row');
                $(this).addClass('selected-row');
                // Load details into the selected-cofo-detail-card
                loadCofODetailsInCards(cofoId);
            }
        });

        // Removed add button functionality as requested

        // Search functionality
        const cofoSearch = document.getElementById('cofo-search');
        if (cofoSearch) {
            cofoSearch.addEventListener('input', function() {
                if (cofoTable) {
                    cofoTable.search(this.value).draw();
                }
            });
        }
    });

    // Function to open property form for CofO
    function openPropertyFormForCofO() {
        // Redirect to property form with CofO context
        window.location.href = '{{ route("propertycard.index") }}#add-cofo';
    }

    // Function to load CofO details in cards
    function loadCofODetailsInCards(cofoId) {
        console.log('Load CofO details in cards for ID:', cofoId);
        
        // Show loading state
        const detailCard = document.getElementById('selected-cofo-detail-card');
        if (detailCard) {
            detailCard.innerHTML = `
                <div class="border rounded-lg shadow-lg overflow-hidden bg-gray-50 border-gray-200 p-8 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mb-3"></div>
                        <p class="text-gray-500">Loading CofO details...</p>
                    </div>
                </div>
            `;
        }
        
        // Fetch CofO data (you can implement this endpoint later)
        // For now, just reload the page to show the selected record
        // fetch(`/cofo-records/${cofoId}`)...
    }

    // Placeholder functions for CofO operations
    function viewCofODetails(id) {
        console.log('View CofO details for ID:', id);
        // TODO: Implement view CofO details modal
        alert('View CofO details functionality will be implemented soon.');
    }

    function editCofO(id) {
        console.log('Edit CofO for ID:', id);
        // TODO: Implement edit CofO modal
        alert('Edit CofO functionality will be implemented soon.');
    }

    function deleteCofO(id) {
        console.log('Delete CofO for ID:', id);
        // TODO: Implement delete CofO functionality
        if (confirm('Are you sure you want to delete this CofO record?')) {
            alert('Delete CofO functionality will be implemented soon.');
        }
    }
</script>