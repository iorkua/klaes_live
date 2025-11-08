document.addEventListener('DOMContentLoaded', function() {
    // Create modal container if it doesn't exist already
    if (!document.getElementById('modal-container')) {
        const modalContainer = document.createElement('div');
        modalContainer.id = 'modal-container';
        modalContainer.className = 'fixed inset-0 z-50 overflow-y-auto hidden';
        modalContainer.innerHTML = `
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-overlay" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div id="modal-content" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="p-6 flex justify-center items-center">
                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p>Loading...</p>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modalContainer);
    }

    // Custom modal handler for Tailwind CSS
    const modalButtons = document.querySelectorAll('.customModal');
    
    modalButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const url = this.getAttribute('data-url');
            const title = this.getAttribute('data-title');
            const size = this.getAttribute('data-size') || 'md';
            
            // Get the modal container and content elements
            const modalContainer = document.getElementById('modal-container');
            const modalContent = document.getElementById('modal-content');
            
            // Show modal container
            modalContainer.classList.remove('hidden');
            
            // Set modal size - only after we've confirmed modalContent exists
            if (size === 'sm') {
                modalContent.classList.add('sm:max-w-sm');
                modalContent.classList.remove('sm:max-w-lg', 'sm:max-w-2xl', 'sm:max-w-4xl');
            } else if (size === 'lg') {
                modalContent.classList.add('sm:max-w-2xl');
                modalContent.classList.remove('sm:max-w-sm', 'sm:max-w-lg', 'sm:max-w-4xl');
            } else if (size === 'xl') {
                modalContent.classList.add('sm:max-w-4xl');
                modalContent.classList.remove('sm:max-w-sm', 'sm:max-w-lg', 'sm:max-w-2xl');
            } else {
                modalContent.classList.add('sm:max-w-lg');
                modalContent.classList.remove('sm:max-w-sm', 'sm:max-w-2xl', 'sm:max-w-4xl');
            }
            
            // Fetch modal content
            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('modal-content').innerHTML = html;
                    
                    // Add event listener to close buttons
                    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
                    closeButtons.forEach(button => {
                        button.addEventListener('click', closeModal);
                    });
                })
                .catch(error => {
                    console.error('Error loading modal content:', error);
                    document.getElementById('modal-content').innerHTML = `
                        <div class="p-6">
                            <div class="text-red-500">Error loading content. Please try again.</div>
                        </div>
                        <div class="px-6 py-3 bg-gray-50 text-right">
                            <button type="button" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" data-dismiss="modal">
                                Close
                            </button>
                        </div>
                    `;
                    
                    // Add event listener to close buttons
                    const closeButtons = document.querySelectorAll('[data-dismiss="modal"]');
                    closeButtons.forEach(button => {
                        button.addEventListener('click', closeModal);
                    });
                });
        });
    });
    
    // Close modal function
    function closeModal() {
        const modalContainer = document.getElementById('modal-container');
        if (modalContainer) {
            modalContainer.classList.add('hidden');
        }
    }
    
    // Close modal on backdrop click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('bg-overlay')) {
            closeModal();
        }
    });
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
});
