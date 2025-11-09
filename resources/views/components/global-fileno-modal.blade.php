<!-- Global File Number Modal -->
<div id="global-fileno-modal" class="fixed inset-0 bg-black bg-opacity-60 z-[100] hidden items-center justify-center p-4" data-modal="global-fileno" onclick="if(event.target === this) GlobalFileNoModal.close()">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="relative px-6 py-4 bg-gradient-to-br from-blue-500 via-blue-600 to-indigo-700 text-white">
                <div class="absolute inset-0 bg-gradient-to-r from-blue-400/10 to-transparent"></div>
                <div class="relative flex justify-between items-start">
                    <div>
                        <h3 class="text-xl font-bold flex items-center">
                            <div class="p-2 bg-white/20 rounded-lg mr-3 backdrop-blur-sm">
                                <i data-lucide="file-text" class="w-5 h-5"></i>
                            </div>
                            File Number Selector
                        </h3>
                    </div>
                    <button type="button" class="p-2 hover:bg-white/20 rounded transition-colors" id="global-fileno-modal-close" onclick="event.stopPropagation(); GlobalFileNoModal.close();">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-4 bg-gray-50">
                <!-- Tab Navigation -->
                <div class="flex space-x-1 mb-4 bg-white p-1 rounded border border-gray-200">
                    <button type="button" 
                            class="fileno-tab-btn flex-1 px-4 py-3 text-sm font-medium rounded-md transition-all duration-200 bg-white text-blue-600 shadow-sm" 
                            data-tab="mls"
                            onclick="GlobalFileNoModal.switchTab('mls'); return false;">
                        <div class="flex items-center justify-center space-x-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-blue-600"></i>
                            <span>MLS</span>
                        </div>
                    </button>
                    <button type="button" 
                            class="fileno-tab-btn flex-1 px-4 py-3 text-sm font-medium rounded-md transition-all duration-200 text-gray-700 hover:text-gray-900" 
                            data-tab="kangis"
                            onclick="GlobalFileNoModal.switchTab('kangis'); return false;">
                        <div class="flex items-center justify-center space-x-2">
                            <i data-lucide="map" class="w-4 h-4 text-green-600"></i>
                            <span>KANGIS</span>
                        </div>
                    </button>
                    <button type="button" 
                            class="fileno-tab-btn flex-1 px-4 py-3 text-sm font-medium rounded-md transition-all duration-200 text-gray-700 hover:text-gray-900" 
                            data-tab="newkangis"
                            onclick="GlobalFileNoModal.switchTab('newkangis'); return false;">
                        <div class="flex items-center justify-center space-x-2">
                            <i data-lucide="map-pin" class="w-4 h-4 text-purple-600"></i>
                            <span>New KANGIS</span>
                        </div>
                    </button>
                </div>

                <!-- Tab Content Container -->
                <div class="tab-content-container">
                    
                    <!-- MLS Tab Content -->
                    <div class="fileno-tab-content bg-white rounded p-4 border border-gray-200" data-tab="mls" style="display: block;">

                        <!-- Input Method Toggle -->
                        <div class="flex p-1 bg-gray-100 rounded-lg mb-4">
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="mls-input-method" value="smart" class="sr-only peer" checked>
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-blue-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-blue-600">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    <span>Smart Selector</span>
                                </span>
                            </label>
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="mls-input-method" value="manual" class="sr-only peer">
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-blue-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-blue-600">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                    <span>Manual Entry</span>
                                </span>
                            </label>
                        </div>

                        <!-- Smart Selector Section -->
                        <div class="mls-input-section" data-method="smart">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Existing MLS Files</label>
                                <select id="mls-smart-selector" class="w-full p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">🔍 Search and select MLS file number...</option>
                                </select>
                                <div id="mls-loading" class="hidden mt-2">
                                    <div class="flex items-center p-2 bg-blue-50 rounded text-sm">
                                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-blue-600 border-t-transparent mr-2"></div>
                                        <span class="text-blue-700">Loading MLS file numbers...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="mls-input-section hidden" data-method="manual">
                            <div class="mb-3">
                                <!-- File Type -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">File Type</label>
                                    <select id="mls-file-type" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                                        <option value="regular">Regular</option>
                                        <option value="temporary">Temporary</option>
                                        <option value="extension">Extension</option>
                                        <option value="miscellaneous">Miscellaneous</option>
                                        <option value="old_mls">Old MLS</option>
                                        <option value="sltr">SLTR</option>
                                        <option value="sit">SIT</option>
                                    </select>
                                </div>
                                
                                <!-- Regular/Temporary Fields -->
                                <div class="mls-type-fields mt-2" data-type="regular temporary">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Prefix</label>
                                            <select id="mls-prefix" class="w-full p-2 text-sm border border-gray-300 rounded">
                                                <option value="">Select prefix</option>
                                                <optgroup label="Standard">
                                                    <option value="RES">RES - Residential</option>
                                                    <option value="COM">COM - Commercial</option>
                                                    <option value="IND">IND - Industrial</option>
                                                    <option value="AG">AG - Agricultural</option>
                                                </optgroup>
                                                <optgroup label="Conversion">
                                                    <option value="CON-RES">CON-RES - Con. Residential</option>
                                                    <option value="CON-COM">CON-COM - Con. Commercial</option>
                                                    <option value="CON-IND">CON-IND - Con. Industrial</option>
                                                    <option value="CON-AG">CON-AG - Con. Agricultural</option>
                                                </optgroup>
                                                <optgroup label="RC Options">
                                                    <option value="RES-RC">RES-RC</option>
                                                    <option value="COM-RC">COM-RC</option>
                                                    <option value="AG-RC">AG-RC</option>
                                                    <option value="IND-RC">IND-RC</option>
                                                    <option value="CON-RES-RC">CON-RES-RC</option>
                                                    <option value="CON-COM-RC">CON-COM-RC</option>
                                                    <option value="CON-AG-RC">CON-AG-RC</option>
                                                    <option value="CON-IND-RC">CON-IND-RC</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                                            <input type="text" id="mls-year" class="w-full p-2 text-sm border border-gray-300 rounded" 
                                                   placeholder="2025" maxlength="4">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                            <input type="text" id="mls-serial" class="w-full p-2 text-sm border border-gray-300 rounded" 
                                                   placeholder="0001">
                                        </div>
                                    </div>
                                </div>

                                <!-- Extension Fields -->
                                <div class="mls-type-fields hidden mt-2" data-type="extension">
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Prefix</label>
                                            <select id="mls-extension-prefix" class="w-full p-2 text-sm border border-gray-300 rounded">
                                                <option value="">Select prefix</option>
                                                <optgroup label="Standard">
                                                    <option value="RES">RES - Residential</option>
                                                    <option value="COM">COM - Commercial</option>
                                                    <option value="IND">IND - Industrial</option>
                                                    <option value="AG">AG - Agricultural</option>
                                                </optgroup>
                                                <optgroup label="Conversion">
                                                    <option value="CON-RES">CON-RES - Con. Residential</option>
                                                    <option value="CON-COM">CON-COM - Con. Commercial</option>
                                                    <option value="CON-IND">CON-IND - Con. Industrial</option>
                                                    <option value="CON-AG">CON-AG - Con. Agricultural</option>
                                                </optgroup>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                                            <input type="text" id="mls-extension-year" class="w-full p-2 text-sm border border-gray-300 rounded" 
                                                   placeholder="2025" maxlength="4">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                            <input type="text" id="mls-extension-serial" class="w-full p-2 text-sm border border-gray-300 rounded" 
                                                   placeholder="0001">
                                        </div>
                                    </div>
                                </div>

                                <!-- Miscellaneous Fields -->
                                <div class="mls-type-fields hidden mt-2" data-type="miscellaneous">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Middle Prefix</label>
                                            <input type="text" id="mls-middle-prefix" class="w-full p-2 border border-gray-300 rounded" 
                                                   placeholder="KN" value="KN">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                            <input type="text" id="mls-misc-serial" class="w-full p-2 border border-gray-300 rounded" 
                                                   placeholder="001">
                                        </div>
                                    </div>
                                </div>

                                <!-- Special Fields (SIT) -->
                                <div class="mls-type-fields hidden mt-2" data-type="sit">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Year</label>
                                            <input type="text" id="mls-sit-year" class="w-full p-2 border border-gray-300 rounded" 
                                                   placeholder="2025" maxlength="4">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                            <input type="text" id="mls-sit-serial" class="w-full p-2 border border-gray-300 rounded" 
                                                   placeholder="001">
                                        </div>
                                    </div>
                                </div>

                                <!-- Special Fields (SLTR) -->
                                <div class="mls-type-fields hidden mt-2" data-type="sltr">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                        <input type="text" id="mls-sltr-serial" class="w-full p-2 border border-gray-300 rounded" 
                                               placeholder="001">
                                    </div>
                                </div>

                                <!-- Old MLS Fields -->
                                <div class="mls-type-fields hidden mt-2" data-type="old_mls">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Serial</label>
                                        <input type="text" id="mls-old-serial" class="w-full p-2 border border-gray-300 rounded" 
                                               placeholder="12345">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-gray-50 border border-gray-200 rounded p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-800 mb-2">Preview</label>
                                    <div id="mls-preview" class="text-lg font-mono font-bold text-blue-900 bg-white border border-blue-200 px-3 py-2 rounded h-[48px] flex items-center">
                                        <span class="text-gray-400 font-normal">No file number generated</span>
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <button type="button" id="mls-copy-btn" class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                        <div class="flex items-center space-x-1">
                                            <i data-lucide="copy" class="w-4 h-4"></i>
                                            <span class="text-sm">Copy</span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KANGIS Tab Content -->
                    <div class="fileno-tab-content hidden bg-white rounded p-4 border border-gray-200" data-tab="kangis" style="display: none;">
                        <div class="bg-green-600 text-white p-3 rounded mb-3">
                            <div class="flex items-center">
                                <i data-lucide="map" class="w-4 h-4 mr-2"></i>
                                <h4 class="font-medium text-sm">KANGIS (Legacy)</h4>
                            </div>
                        </div>

                        <!-- Input Method Selection -->
                        <div class="flex p-1 bg-gray-100 rounded-lg mb-4">
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="kangis-input-method" value="smart" class="sr-only peer" checked>
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-green-600">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    <span>Smart Selector</span>
                                </span>
                            </label>
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="kangis-input-method" value="manual" class="sr-only peer">
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-green-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-green-600">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                    <span>Manual Entry</span>
                                </span>
                            </label>
                        </div>

                        <!-- Smart Selector Section -->
                        <div class="kangis-input-section" data-method="smart">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Existing KANGIS Files</label>
                                <select id="kangis-smart-selector" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500">
                                    <option value="">Search and select KANGIS file number...</option>
                                </select>
                                <div id="kangis-loading" class="hidden mt-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-green-600 mr-2"></div>
                                        Loading file numbers...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="kangis-input-section hidden" data-method="manual">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">File Prefix</label>
                                    <select id="kangis-prefix" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500">
                                        <option value="">Select Prefix</option>
                                        <option value="KNML">KNML</option>
                                        <option value="MNKL">MNKL</option>
                                        <option value="MLKN">MLKN</option>
                                        <option value="KNGP">KNGP</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Serial Number</label>
                                    <input type="text" id="kangis-number" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-green-500" 
                                           placeholder="e.g., 0001 or 2500">
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-gray-50 border rounded p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                                    <div id="kangis-preview" class="text-lg font-mono font-bold text-green-900 bg-white border px-3 py-2 rounded h-[48px] flex items-center">
                                        No file number generated
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <button type="button" id="kangis-copy-btn" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50" disabled>
                                        <i data-lucide="copy" class="w-4 h-4 mr-1"></i>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- New KANGIS Tab Content -->
                    <div class="fileno-tab-content hidden bg-white rounded p-4 border border-gray-200" data-tab="newkangis" style="display: none;">
                        <!-- Tab Header -->
                        <div class="bg-purple-600 text-white p-3 rounded mb-3">
                            <div class="flex items-center">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                                <div>
                                    <h4 class="font-medium text-sm">New KANGIS Format</h4>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Input Method Selection -->
                        <div class="flex p-1 bg-gray-100 rounded-lg mb-4">
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="newkangis-input-method" value="smart" class="sr-only peer" checked>
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-purple-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-purple-600">
                                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                                    <span>Smart Selector</span>
                                </span>
                            </label>
                            <label class="flex-1 flex items-center justify-center cursor-pointer group">
                                <input type="radio" name="newkangis-input-method" value="manual" class="sr-only peer">
                                <span class="flex items-center space-x-2 px-4 py-2 rounded-md font-medium text-sm transition-all duration-200 
                                             text-gray-600 peer-checked:bg-purple-500 peer-checked:text-white peer-checked:shadow-sm
                                             hover:text-gray-800 peer-checked:hover:bg-purple-600">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                    <span>Manual Entry</span>
                                </span>
                            </label>
                        </div>

                        <!-- Smart Selector Section -->
                        <div class="newkangis-input-section" data-method="smart">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Search Existing New KANGIS Files</label>
                                <select id="newkangis-smart-selector" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500">
                                    <option value="">Search and select New KANGIS file number...</option>
                                </select>
                                <div id="newkangis-loading" class="hidden mt-2">
                                    <div class="flex items-center text-sm text-gray-600">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-600 mr-2"></div>
                                        Loading file numbers...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manual Entry Section -->
                        <div class="newkangis-input-section hidden" data-method="manual">
                            <div class="grid grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">File Prefix</label>
                                    <select id="newkangis-prefix" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500">
                                        <option value="">Select Prefix</option>
                                        <option value="KN">KN</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Serial Number</label>
                                    <input type="text" id="newkangis-number" class="w-full p-2 border border-gray-300 rounded focus:ring-2 focus:ring-purple-500" 
                                           placeholder="e.g., 1586">
                                </div>
                            </div>
                        </div>

                        <!-- Preview -->
                        <div class="bg-gray-50 border rounded p-3">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Preview</label>
                                    <div id="newkangis-preview" class="text-lg font-mono font-bold text-purple-900 bg-white border px-3 py-2 rounded h-[48px] flex items-center">
                                        No file number generated
                                    </div>
                                </div>
                                <div class="ml-3">
                                    <button type="button" id="newkangis-copy-btn" class="px-3 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:opacity-50" disabled>
                                        <i data-lucide="copy" class="w-4 h-4 mr-1"></i>
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                                @once
                                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
                                    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
                                @endonce

            <!-- Modal Footer -->
            <div class="flex justify-between items-center px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="flex items-center text-sm text-gray-600">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-2 text-green-500"></i>
                        <span>Format validated</span>
                    </div>
                    <span id="validation-message" class="text-sm"></span>
                </div>
                <div class="flex space-x-3">
                    <button type="button" class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-100" onclick="GlobalFileNoModal.close()">
                        Cancel
                    </button>
                    <button type="button" id="apply-fileno-btn" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed" disabled onclick="GlobalFileNoModal.apply()">
                        Apply File Number
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
