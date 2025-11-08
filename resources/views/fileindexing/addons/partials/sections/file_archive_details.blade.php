<div class="form-section">
    <div class="flex items-center gap-2 mb-4">
        <i data-lucide="archive" class="h-5 w-5 text-amber-600"></i>
        <h3 class="form-section-title" style="margin-bottom: 0;">File Archive Details</h3>
    </div>

    <hr class="mb-6 border-t border-gray-200">

    <div class="grid grid-cols-2 gap-4">
        <div class="form-group">
            <label for="archive-file-no" class="block text-sm font-medium text-gray-700 mb-2">File No</label>
            <input type="text" id="archive-file-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm bg-gray-50" readonly>
        </div>
        <div class="form-group">
            <label for="awaiting-file-no" class="block text-sm font-medium text-gray-700 mb-2">Awaiting file no</label>
            <div class="flex">
                <input type="text" id="awaiting-file-no" class="flex-grow mr-2 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <button type="button" id="refresh-archive-details-btn" class="inline-flex items-center px-3 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-500 hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Refresh archive details from API">
                    <i data-lucide="refresh-cw" class="h-3 w-3"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="form-group">
            <label for="mdc-batch-no" class="block text-sm font-medium text-gray-700 mb-2">MDC Batch No</label>
            <input type="text" id="mdc-batch-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>
        <div class="form-group">
            <label for="group-no" class="block text-sm font-medium text-gray-700 mb-2">Group No</label>
            <input type="text" id="group-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-gray-100" readonly>
        </div>
        <div class="form-group">
            <label for="sys-batch-no" class="block text-sm font-medium text-gray-700 mb-2">SYS Batch No</label>
            <input type="text" id="sys-batch-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-gray-100" readonly>
        </div>
    </div>

    <div class="grid grid-cols-4 gap-4">
        <div class="form-group">
            <label for="physical-registry" class="block text-sm font-medium text-gray-700 mb-2">Physical Registry</label>
            <select id="physical-registry" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" name="physical_registry">
                <option value="">Select Registry</option>
                <option value="Registry 1 - Lands">Registry 1 - Lands</option>
                <option value="Registry 2 - Lands">Registry 2 - Lands</option>
                <option value="Registry 3 - Lands">Registry 3 - Lands</option>
                <option value="Registry 1 - Deeds">Registry 1 - Deeds</option>
                <option value="Registry 2 - Deeds">Registry 2 - Deeds</option>
                <option value="Registry 1 - Cadastral">Registry 1 - Cadastral</option>
                <option value="Registry 2 - Cadastral">Registry 2 - Cadastral</option>
                <option value="KANGIS Registry">KANGIS Registry</option>
                <option value="SLTR Registry">SLTR Registry</option>
                <option value="ST Registry">ST Registry</option>
                <option value="DCIV Registry">DCIV Registry</option>
                <option value="New Archive">New Archive</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <div class="form-group">
            <label for="registry" class="block text-sm font-medium text-gray-700 mb-2">Registry</label>
            <input type="text" id="registry" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-gray-100" readonly>
        </div>
        <div class="form-group">
            <label for="shelf-rack-no" class="block text-sm font-medium text-gray-700 mb-2">Shelf/Rack No</label>
            <input type="text" id="shelf-rack-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-gray-100" readonly>
        </div>
        <div class="form-group col-span-2 md:col-span-1">
            <label for="serial-no" class="block text-sm font-medium text-gray-700 mb-2">Serial No</label>
            <input type="text" id="serial-no" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-gray-500 focus:border-gray-500 sm:text-sm bg-gray-100" readonly>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="form-group">
            <label for="indexed-by" class="block text-sm font-medium text-gray-700 mb-2">Indexed by</label>
            <input type="text" id="indexed-by" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none focus:ring-0 focus:border-gray-300 sm:text-sm" value="{{ Auth::user()->name ?? 'Current User' }}" placeholder="Indexer name" disabled readonly>
        </div>
        <div class="form-group">
            <label for="indexed-date" class="block text-sm font-medium text-gray-700 mb-2">Indexed Date</label>
            <input type="date" id="indexed-date" class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ date('Y-m-d') }}">
        </div>
    </div>
</div>
