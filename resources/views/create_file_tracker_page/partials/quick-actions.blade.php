<!-- Quick Actions Card -->
<div class="bg-white rounded-lg border border-gray-200 shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <i data-lucide="file-search" class="h-5 w-5"></i>
            Quick Actions
        </h3>
        <p class="text-sm text-gray-600 mt-1">Perform common file tracking operations</p>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- Search Files -->
            <button id="quick-search-files" class="quick-action-btn" data-action="search-files" title="Search existing file trackers">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i data-lucide="file-search" class="h-5 w-5 text-blue-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Search Files</span>
                    <span class="text-xs text-gray-500">Find trackers</span>
                </div>
            </button>

            <!-- Office List -->
            <button id="quick-office-list" class="quick-action-btn" data-action="office-list" title="View all offices and departments">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i data-lucide="building" class="h-5 w-5 text-green-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Office List</span>
                    <span class="text-xs text-gray-500">View offices</span>
                </div>
            </button>

            <!-- Track Status -->
            <button id="quick-track-status" class="quick-action-btn" data-action="track-status" title="Check file tracking status">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-2 bg-orange-100 rounded-lg">
                        <i data-lucide="activity" class="h-5 w-5 text-orange-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Track Status</span>
                    <span class="text-xs text-gray-500">Check status</span>
                </div>
            </button>

            <!-- Statistics -->
            <button id="quick-statistics" class="quick-action-btn" data-action="statistics" title="View tracking statistics">
                <div class="flex flex-col items-center gap-2">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i data-lucide="bar-chart-3" class="h-5 w-5 text-purple-600"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Statistics</span>
                    <span class="text-xs text-gray-500">View stats</span>
                </div>
            </button>
        </div>
    </div>
</div>

<!-- Quick Actions Styles -->
<style>
.quick-action-btn {
    @apply w-full p-3 border border-gray-200 rounded-lg transition-all duration-200 hover:border-blue-300 hover:shadow-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1;
}

.quick-action-btn:hover .text-gray-700 {
    @apply text-blue-700;
}

.quick-action-btn:hover .text-gray-500 {
    @apply text-blue-600;
}

.quick-action-btn:active {
    @apply transform scale-95;
}
</style>