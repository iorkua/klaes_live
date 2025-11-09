<div class="form-section">
    <div class="flex justify-between items-start mb-4">
        <div class="flex items-center gap-2">
            <i data-lucide="id-card" class="h-5 w-5 text-blue-600"></i>
            <h3 class="form-section-title" style="margin-bottom: 0;">File Identification</h3>
        </div>
        <div class="tracking-id-container" style="text-align: right;">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Tracking ID</label>
                <div id="grouping-loading-indicator" class="hidden flex items-center justify-end gap-2 text-sm text-blue-600">
                    <span class="inline-flex h-4 w-4 items-center justify-center">
                        <span class="loading-spinner"></span>
                    </span>
                    <span>Fetching grouping record...</span>
                </div>
                <input type="text" id="tracking-id"
                       value=""
                       class="w-full px-4 py-3 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 font-mono text-base font-bold text-red-600"
                       readonly placeholder="Will be loaded from grouping record">
            </div>
        </div>
    </div>

    <hr class="mb-6 border-t border-gray-200">

    <div class="form-group">
        <label for="file-number-display" class="block text-sm font-medium text-gray-700 mb-2 required">File Number</label>

        <div class="flex">
            <input type="text" id="file-number-display" class="flex-grow mr-2 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-50" readonly style="color: #6b7280;">
            <input type="hidden" id="fileno" name="fileno" value="">
            <input type="hidden" id="grouping-id" name="grouping_id" value="">
            <button type="button" id="select-file-number-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" style="white-space: nowrap;">
                Select
            </button>
        </div>
    </div>

    <div class="flex flex-wrap gap-4 items-end">
        <div class="form-group flex-1 min-w-[240px]">
            <label for="file-title" class="block text-sm font-medium text-gray-700 mb-2 required">File Title</label>
            <input type="text" id="file-title" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div class="form-group flex-1 min-w-[240px]">
            <label for="related-file-number-display" class="block text-sm font-medium text-gray-700 mb-2">Related File Number</label>
            <div class="flex">
                <input type="text" id="related-file-number-display" class="flex-grow mr-2 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-50" readonly style="color: #6b7280;">
                <input type="hidden" id="related-fileno" name="related_fileno" value="">
                <button type="button" id="select-related-file-number-btn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" style="white-space: nowrap;">
                    Select
                </button>
            </div>
        </div>
    </div>
</div>
