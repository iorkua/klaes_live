@extends('layouts.app')

@section('page-title', 'User Activity Logs')

@section('content')
<div class="flex-1 overflow-auto">
        <!-- Header -->
        @include('admin.header')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">User Activity Logs</h1>
                    <p class="mt-2 text-gray-600">Monitor user login activities, sessions, and online status</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="exportLogs()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-download mr-2"></i>
                        Export
                    </button>
                    <button onclick="openCleanupModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-broom mr-2"></i>
                        Cleanup
                    </button>
                    <button onclick="openSettingsModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-cog mr-2"></i>
                        Settings
                    </button>
                    <button onclick="refreshData()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i class="fas fa-sync-alt mr-2"></i>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                  
                    <button onclick="switchTab('activity-logs')" id="activity-logs-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm active">
                        <i class="fas fa-list mr-2"></i>
                        Activity Logs
                    </button>
                    <button onclick="switchTab('online-users')" id="online-users-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                        <i class="fas fa-users mr-2"></i>
                        Online Users
                        <span class="ml-2 bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full" id="online-count-badge">{{ count($onlineUsers) }}</span>
                    </button>
                    
                </nav>
            </div>
        </div>

        <!-- Tab Content -->
        <div>
           

            <!-- Activity Logs Tab -->
            <div id="activity-logs-content" class="tab-content  ">
                <!-- Filters -->
                <div class="bg-white shadow rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div>
                            <label for="user_filter" class="block text-sm font-medium text-gray-700 mb-1">User</label>
                            <select id="user_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Users</option>
                            </select>
                        </div>
                        <div>
                            <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="online">Online</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>
                        <div>
                            <label for="device_filter" class="block text-sm font-medium text-gray-700 mb-1">Device</label>
                            <select id="device_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Devices</option>
                                <option value="desktop">Desktop</option>
                                <option value="mobile">Mobile</option>
                                <option value="tablet">Tablet</option>
                            </select>
                        </div>
                        <div>
                            <label for="browser_filter" class="block text-sm font-medium text-gray-700 mb-1">Browser</label>
                            <select id="browser_filter" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Browsers</option>
                                <option value="Chrome">Chrome</option>
                                <option value="Firefox">Firefox</option>
                                <option value="Safari">Safari</option>
                                <option value="Edge">Edge</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                            <input type="date" id="date_from" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                            <input type="date" id="date_to" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-3">
                        <button onclick="applyFilters()" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                            <i class="fas fa-filter mr-2"></i>
                            Apply Filters
                        </button>
                        <button onclick="clearFilters()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </button>
                        
                    </div>
                </div>

                <!-- Activity Logs Table -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Activity Logs</h3>
                            <div class="flex items-center space-x-3">
                                <button onclick="bulkDelete()" id="bulk-delete-btn" class="hidden inline-flex items-center px-3 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete Selected Logs
                                </button>
                                <span class="text-sm text-gray-500" id="selected-count"></span>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="activity-logs-table" class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                   
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">IP Address</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Device Info</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Login Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logout Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <!-- Data will be populated by DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Online Users Tab -->
            <div id="online-users-content" class="tab-content hidden">
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Currently Online Users</h3>
                    <div class="flex items-center space-x-3">
                        <button onclick="refreshOnlineUsers()" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Refresh
                        </button>
                        <span class="text-sm text-gray-500">Auto-refresh: <span id="auto-refresh-status">ON</span></span>
                    </div>
                </div>

                <div id="online-users-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @if($onlineUsers->count() > 0)
                        @foreach($onlineUsers as $user)
                            <div class="bg-white shadow rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center relative">
                                            <span class="text-white font-medium">
                                                {{ substr($user->user->first_name ?? 'U', 0, 1) }}{{ substr($user->user->last_name ?? 'U', 0, 1) }}
                                            </span>
                                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white animate-pulse"></span>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $user->user->name ?? 'Unknown User' }}
                                        </p>
                                        <p class="text-xs text-gray-500 truncate">
                                            {{ $user->user->email ?? 'No email' }}
                                        </p>
                                        <div class="mt-2 flex items-center space-x-2 text-xs text-gray-500">
                                            <span class="flex items-center">
                                                <i class="fas fa-{{ $user->device_type == 'mobile' ? 'mobile-alt' : ($user->device_type == 'tablet' ? 'tablet-alt' : 'desktop') }} mr-1"></i>
                                                {{ ucfirst($user->device_type) }}
                                            </span>
                                            <span>•</span>
                                            <span>{{ $user->ip_address }}</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Online for {{ $user->login_time->diffForHumans(null, true) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No users currently online</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Security Tab -->
            <div id="security-content" class="tab-content hidden">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Suspicious Activities -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                            Suspicious Activities
                        </h3>
                        <div id="suspicious-activities" class="space-y-3">
                            <!-- Suspicious activities will be loaded here -->
                        </div>
                    </div>

                    <!-- IP Address Analysis -->
                    <div class="bg-white shadow rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">IP Address Analysis</h3>
                        <div id="ip-analysis" class="space-y-3">
                            <!-- IP analysis will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Security Settings</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Auto-logout inactive sessions</label>
                                <p class="text-sm text-gray-500">Automatically logout users after 30 minutes of inactivity</p>
                            </div>
                            <input type="checkbox" id="auto-logout" class="rounded border-gray-300 text-blue-600 shadow-sm">
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">Track failed login attempts</label>
                                <p class="text-sm text-gray-500">Monitor and log failed authentication attempts</p>
                            </div>
                            <input type="checkbox" id="track-failed-logins" class="rounded border-gray-300 text-blue-600 shadow-sm">
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <label class="text-sm font-medium text-gray-900">IP-based alerts</label>
                                <p class="text-sm text-gray-500">Send alerts for logins from new IP addresses</p>
                            </div>
                            <input type="checkbox" id="ip-alerts" class="rounded border-gray-300 text-blue-600 shadow-sm">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Details Modal -->
<div id="activity-details-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Activity Details</h3>
            <button onclick="closeActivityModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="activity-details-content">
            <!-- Content will be populated via AJAX -->
        </div>
    </div>
 
  <!-- Footer -->
  @include('admin.footer')
  
<!-- Cleanup Modal -->
<div id="cleanup-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Cleanup Old Logs</h3>
            <button onclick="closeCleanupModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="mb-4">
            <label for="cleanup-days" class="block text-sm font-medium text-gray-700 mb-2">Delete logs older than:</label>
            <select id="cleanup-days" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                <option value="30">30 days</option>
                <option value="60">60 days</option>
                <option value="90">90 days</option>
                <option value="180">180 days</option>
                <option value="365">1 year</option>
            </select>
        </div>
        <div class="flex justify-end space-x-3">
            <button onclick="closeCleanupModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </button>
            <button onclick="performCleanup()" class="px-4 py-2 bg-red-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-red-700">
                Delete Logs
            </button>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div id="settings-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Activity Logs Settings</h3>
            <button onclick="closeSettingsModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="space-y-6">
            <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Data Retention</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-700">Automatic cleanup interval</label>
                        <select id="cleanup-interval" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-700">Keep logs for</label>
                        <select id="retention-days" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="30">30 days</option>
                            <option value="60">60 days</option>
                            <option value="90">90 days</option>
                            <option value="180">180 days</option>
                            <option value="365">1 year</option>
                        </select>
                    </div>
                </div>
            </div>
            <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Display Options</h4>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-700">Auto-refresh interval (seconds)</label>
                        <input type="number" id="refresh-interval" value="30" min="10" max="300" class="w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="text-sm text-gray-700">Records per page</label>
                        <select id="records-per-page" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 flex justify-end space-x-3">
            <button onclick="closeSettingsModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                Cancel
            </button>
            <button onclick="saveSettings()" class="px-4 py-2 bg-blue-600 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                Save Settings
            </button>
        </div>
    </div>
    
</div>

@endsection

@section('footer-scripts')
<script>
let activityTable;
let selectedRows = [];
let currentTab = 'activity-logs';
let autoRefreshInterval;

$(document).ready(function() {
    loadUsers();
    setupEventListeners();
    setDatesToTodayAndDisable();
    initializeDataTable(); // Initialize DataTable since Activity Logs is the default tab
    startAutoRefresh();
    loadSettings();
    setupOfflineDetection(); // Add offline detection
});

// Tab Management
function switchTab(tabName) {
    // Hide all tab contents
    $('.tab-content').addClass('hidden');
    $('.tab-button').removeClass('active border-blue-500 text-blue-600').addClass('border-transparent text-gray-500');
    
    // Show selected tab content
    $('#' + tabName + '-content').removeClass('hidden');
    $('#' + tabName + '-tab').removeClass('border-transparent text-gray-500').addClass('active border-blue-500 text-blue-600');
    
    currentTab = tabName;
    
    // Load tab-specific data
    switch(tabName) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'activity-logs':
            if (!activityTable) {
                initializeDataTable();
            } else {
                activityTable.ajax.reload();
            }
            break;
        case 'online-users':
            refreshOnlineUsers();
            break;
        case 'security':
            loadSecurityData();
            break;
    }
}

function initializeDataTable() {
    if (currentTab !== 'activity-logs') return;
    
    activityTable = $('#activity-logs-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("user-activity-logs.index") }}',
            data: function(d) {
                d.user_id = $('#user_filter').val();
                d.status = $('#status_filter').val();
                d.device_type = $('#device_filter').val();
                d.browser = $('#browser_filter').val();
                // Enforce showing only today's logs in the table
                d.date_from = getTodayDate();
                d.date_to = getTodayDate();
            }
        },
        columns: [
          
            {
                data: null,
                name: 'user_name',
                render: function(data, type, row) {
                    return '<div>' +
                           '<div class="text-sm font-medium text-gray-900">' + row.user_name + '</div>' +
                           '<div class="text-sm text-gray-500">' + row.user_email + '</div>' +
                           '</div>';
                }
            },
            {data: 'ip_address', name: 'ip_address'},
            {data: 'device_info', name: 'device_info', orderable: false},
            {data: 'login_time', name: 'login_time'},
            {data: 'logout_time', name: 'logout_time'},
            {data: 'session_duration', name: 'session_duration', orderable: false},
            {data: 'online_status', name: 'online_status', orderable: false},
            {data: 'actions', name: 'actions', orderable: false, searchable: false}
        ],
        order: [[4, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            'copy', 'csv', 'excel', 'pdf', 'print'
        ]
    });
}

function setupEventListeners() {
    // Select all checkbox
    $('#select-all').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('.row-checkbox').prop('checked', isChecked);
        updateSelectedRows();
    });

    // Individual row checkboxes
    $(document).on('change', '.row-checkbox', function() {
        updateSelectedRows();
    });
}

// Helper: return today's date in YYYY-MM-DD (local time)
function getTodayDate() {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// Helper: set date inputs to today and disable them to avoid confusion
function setDatesToTodayAndDisable() {
    const today = getTodayDate();
    $('#date_from').val(today).prop('disabled', true);
    $('#date_to').val(today).prop('disabled', true);
}

function updateSelectedRows() {
    selectedRows = [];
    $('.row-checkbox:checked').each(function() {
        selectedRows.push($(this).val());
    });

    if (selectedRows.length > 0) {
        $('#bulk-delete-btn').removeClass('hidden');
        $('#selected-count').text(selectedRows.length + ' selected');
    } else {
        $('#bulk-delete-btn').addClass('hidden');
        $('#selected-count').text('');
    }
}

function loadUsers() {
    // Load users for filter dropdown
    $.get('{{ route("users.index") }}', function(data) {
        const userSelect = $('#user_filter');
        userSelect.empty().append('<option value="">All Users</option>');
        
        if (data.data) {
            data.data.forEach(function(user) {
                userSelect.append('<option value="' + user.id + '">' + user.name + '</option>');
            });
        }
    }).fail(function() {
        console.log('Failed to load users');
    });
}

function loadDashboardData() {
    // Load recent activities
    $.get('{{ route("user-activity-logs.chart-data") }}?days=1', function(response) {
        if (response.success) {
            updateRecentActivities(response.data.recent_activities);
        }
    });
}

function updateRecentActivities(activities) {
    const container = $('#recent-activities');
    container.empty();
    
    if (activities && activities.length > 0) {
        activities.forEach(function(activity) {
            const activityHtml = `
                <div class="flex items-center p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="fas fa-${activity.activity_type === 'login' ? 'sign-in-alt' : 'sign-out-alt'} text-white text-xs"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">${activity.user_name}</p>
                        <p class="text-xs text-gray-500">${activity.activity_description}</p>
                        <p class="text-xs text-gray-400">${activity.time_ago}</p>
                    </div>
                </div>
            `;
            container.append(activityHtml);
        });
    } else {
        container.html('<p class="text-gray-500 text-center py-4">No recent activities</p>');
    }
}

function startAutoRefresh() {
    autoRefreshInterval = setInterval(function() {
        if (currentTab === 'dashboard') {
            loadDashboardData();
        } else if (currentTab === 'online-users') {
            refreshOnlineUsers();
        }
        
        // Update statistics
        updateStatistics();
    }, 30000); // 30 seconds
}

function updateStatistics() {
    $.get('{{ route("user-activity-logs.stats") }}', function(response) {
        if (response.success) {
            $('#total-sessions').text(response.data.total_sessions.toLocaleString());
            $('#unique-users').text(response.data.unique_users.toLocaleString());
            $('#online-users').text(response.data.online_users.toLocaleString());
            $('#avg-session').text(response.data.avg_session_duration + ' min');
            $('#online-count-badge').text(response.data.online_users);
        }
    });
}

function refreshOnlineUsers() {
    $.get('{{ route("user-activity-logs.online-users") }}', function(response) {
        if (response.success) {
            updateOnlineUsersGrid(response.data);
            // Update offline status for newly created buttons
            setTimeout(function() {
                updateOfflineStatus();
            }, 100);
        }
    });
}

function updateOnlineUsersGrid(users) {
    const container = $('#online-users-grid');
    container.empty();
    
    if (users && users.length > 0) {
        users.forEach(function(user) {
            const deviceIcon = user.device_type === 'mobile' ? 'mobile-alt' : 
                              user.device_type === 'tablet' ? 'tablet-alt' : 'desktop';
            
            const isOffline = !navigator.onLine;
            const logoutButtonClass = isOffline ? 
                'logout-btn-offline bg-gray-300 text-gray-500 cursor-not-allowed' : 
                'logout-btn-online bg-red-600 hover:bg-red-700 text-white cursor-pointer';
            
            const userHtml = `
                <div class="bg-white shadow rounded-lg p-4 hover:shadow-md transition-shadow duration-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center relative">
                                <span class="text-white font-medium">
                                    ${user.user_name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                                </span>
                                <span class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full border-2 border-white animate-pulse"></span>
                            </div>
                        </div>
                        <div class="ml-4 flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">${user.user_name}</p>
                            <p class="text-xs text-gray-500 truncate">${user.user_email}</p>
                            <div class="mt-2 flex items-center space-x-2 text-xs text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-${deviceIcon} mr-1"></i>
                                    ${user.device_type.charAt(0).toUpperCase() + user.device_type.slice(1)}
                                </span>
                                <span>•</span>
                                <span>${user.ip_address}</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Online since ${user.login_time}</p>
                        </div>
                        <div class="ml-3 flex-shrink-0">
                            <button 
                                onclick="${isOffline ? 'return false;' : `logoutUser('${user.user_id}')`}" 
                                class="logout-user-btn px-3 py-1 text-xs font-medium rounded-md transition-colors duration-200 ${logoutButtonClass}"
                                ${isOffline ? 'disabled title="Cannot logout user while offline"' : 'title="Logout user"'}
                                data-user-id="${user.user_id}">
                                <i class="fas fa-sign-out-alt mr-1"></i>
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.append(userHtml);
        });
    } else {
        container.html(`
            <div class="col-span-full text-center py-12">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No users currently online</p>
            </div>
        `);
    }
}

function loadSecurityData() {
    // Load suspicious activities and IP analysis
    $.get('{{ route("user-activity-logs.chart-data") }}?security=1', function(response) {
        if (response.success) {
            updateSuspiciousActivities(response.data.suspicious_activities);
            updateIPAnalysis(response.data.ip_analysis);
        }
    });
}

function updateSuspiciousActivities(activities) {
    const container = $('#suspicious-activities');
    container.empty();
    
    if (activities && activities.length > 0) {
        activities.forEach(function(activity) {
            const activityHtml = `
                <div class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">${activity.description}</p>
                        <p class="text-xs text-gray-500">${activity.details}</p>
                        <p class="text-xs text-gray-400">${activity.time}</p>
                    </div>
                </div>
            `;
            container.append(activityHtml);
        });
    } else {
        container.html('<p class="text-gray-500 text-center py-4">No suspicious activities detected</p>');
    }
}

function updateIPAnalysis(analysis) {
    const container = $('#ip-analysis');
    container.empty();
    
    if (analysis && analysis.length > 0) {
        analysis.forEach(function(item) {
            const itemHtml = `
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">${item.ip_address}</p>
                        <p class="text-xs text-gray-500">${item.location || 'Unknown location'}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">${item.login_count} logins</p>
                        <p class="text-xs text-gray-500">${item.users_count} users</p>
                    </div>
                </div>
            `;
            container.append(itemHtml);
        });
    } else {
        container.html('<p class="text-gray-500 text-center py-4">No IP data available</p>');
    }
}

function applyFilters() {
    if (activityTable) {
        activityTable.ajax.reload();
    }
}

function clearFilters() {
    $('#user_filter').val('');
    $('#status_filter').val('');
    $('#device_filter').val('');
    $('#browser_filter').val('');
    // Keep the table constrained to today's logs
    setDatesToTodayAndDisable();
    if (activityTable) {
        activityTable.ajax.reload();
    }
}

function saveFilters() {
    const filters = {
        user_id: $('#user_filter').val(),
        status: $('#status_filter').val(),
        device_type: $('#device_filter').val(),
        browser: $('#browser_filter').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val()
    };
    
    localStorage.setItem('activity_logs_filters', JSON.stringify(filters));
    Swal.fire('Success', 'Filters saved successfully', 'success');
}

function refreshData() {
    switch(currentTab) {
        case 'dashboard':
            loadDashboardData();
            break;
        case 'activity-logs':
            if (activityTable) {
                activityTable.ajax.reload();
            }
            break;
        case 'online-users':
            refreshOnlineUsers();
            break;
        case 'security':
            loadSecurityData();
            break;
    }
    
    updateStatistics();
    Swal.fire('Success', 'Data refreshed successfully', 'success');
}

function viewActivityDetails(id) {
    $.get('{{ route("user-activity-logs.show", ":id") }}'.replace(':id', id))
        .done(function(response) {
            if (response.success) {
                const data = response.data;
                const content = `
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">User Information</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Name:</span> ${data.user_name}</div>
                                <div><span class="font-medium">Email:</span> ${data.user_email}</div>
                                <div><span class="font-medium">IP Address:</span> ${data.ip_address}</div>
                                <div><span class="font-medium">Location:</span> ${data.location}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Device Information</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Device:</span> ${data.device_type}</div>
                                <div><span class="font-medium">Browser:</span> ${data.browser}</div>
                                <div><span class="font-medium">Platform:</span> ${data.platform}</div>
                                <div><span class="font-medium">User Agent:</span> <span class="text-xs break-all">${data.user_agent}</span></div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Session Information</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Login Time:</span> ${data.login_time}</div>
                                <div><span class="font-medium">Logout Time:</span> ${data.logout_time}</div>
                                <div><span class="font-medium">Duration:</span> ${data.session_duration}</div>
                                <div><span class="font-medium">Status:</span> ${data.is_online ? 'Online' : 'Offline'}</div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900 mb-3">Activity Information</h4>
                            <div class="space-y-2 text-sm">
                                <div><span class="font-medium">Activity Type:</span> ${data.activity_type}</div>
                                <div><span class="font-medium">Session ID:</span> <span class="text-xs">${data.session_id}</span></div>
                                <div><span class="font-medium">Created:</span> ${data.created_at}</div>
                                ${data.activity_description ? '<div><span class="font-medium">Description:</span> ' + data.activity_description + '</div>' : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                $('#activity-details-content').html(content);
                $('#activity-details-modal').removeClass('hidden');
            }
        })
        .fail(function() {
            Swal.fire('Error', 'Failed to load activity details', 'error');
        });
}

function closeActivityModal() {
    $('#activity-details-modal').addClass('hidden');
}

function logoutUser(userId) {
    console.log('Logout user called with userId:', userId);
    
    // Validate userId before proceeding
    if (!userId || userId === 'undefined' || userId === 'null' || userId === '' || isNaN(userId)) {
        console.error('Invalid user ID provided:', userId);
        Swal.fire({
            title: 'Error',
            text: 'Invalid user ID. Please refresh the page and try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Check if user is offline
    if (!navigator.onLine) {
        Swal.fire({
            title: 'Offline',
            text: 'Cannot logout user while you are offline. Please check your internet connection.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    Swal.fire({
        title: 'Are you sure?',
        text: 'This will log out the user from all their active sessions.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, logout user!'
    }).then((result) => {
        if (result.isConfirmed) {
            const url = '{{ route("user-activity-logs.logout-user", ":userId") }}'.replace(':userId', userId);
            console.log('Making request to:', url);
            
            $.ajax({
                url: url,
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    console.log('Sending logout request...');
                },
                success: function(response) {
                    console.log('Success response:', response);
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        if (activityTable) {
                            activityTable.ajax.reload();
                        }
                        // Also refresh online users if on that tab
                        if (currentTab === 'online-users') {
                            refreshOnlineUsers();
                        }
                        updateStatistics();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    
                    let errorMessage = 'Failed to logout user';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            errorMessage = response.message || errorMessage;
                        } catch (e) {
                            errorMessage = 'Server error: ' + xhr.status + ' ' + xhr.statusText;
                        }
                    }
                    
                    Swal.fire('Error', errorMessage, 'error');
                }
            });
        }
    });
}

function bulkDelete() {
    if (selectedRows.length === 0) {
        Swal.fire('Warning', 'Please select at least one activity log to delete', 'warning');
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: `${selectedRows.length} activity logs will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("user-activity-logs.bulk-delete") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    ids: selectedRows
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Deleted!', response.message, 'success');
                        if (activityTable) {
                            activityTable.ajax.reload();
                        }
                        selectedRows = [];
                        $('#select-all').prop('checked', false);
                        updateSelectedRows();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to delete activity logs', 'error');
                }
            });
        }
    });
}

// Offline Detection Functions
function setupOfflineDetection() {
    // Initial status check
    updateOfflineStatus();
    
    // Listen for online/offline events
    window.addEventListener('online', function() {
        updateOfflineStatus();
        showNetworkStatusMessage('online');
    });
    
    window.addEventListener('offline', function() {
        updateOfflineStatus();
        showNetworkStatusMessage('offline');
    });
}

function updateOfflineStatus() {
    const isOffline = !navigator.onLine;
    
    // Update main logout button in header
    updateMainLogoutButton(isOffline);
    
    // Update logout buttons in online users grid
    updateLogoutButtons(isOffline);
    
    // Update other action buttons that require internet
    updateActionButtons(isOffline);
}

function updateMainLogoutButton(isOffline) {
    const logoutForm = document.getElementById('autoLogoutForm');
    const logoutButton = logoutForm ? logoutForm.querySelector('button[type="submit"]') : null;
    
    if (logoutButton) {
        if (isOffline) {
            logoutButton.disabled = true;
            logoutButton.classList.add('opacity-50', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-500');
            logoutButton.classList.remove('hover:bg-gray-100', 'text-gray-700');
            logoutButton.setAttribute('title', 'Cannot logout while offline - Please check your internet connection');
            // Prevent form submission when offline
            logoutForm.addEventListener('submit', preventOfflineSubmit);
            
            // Add visual indicator for offline state
            const icon = logoutButton.querySelector('svg');
            if (icon) {
                icon.classList.add('text-gray-400');
                icon.classList.remove('text-gray-500');
            }
        } else {
            logoutButton.disabled = false;
            logoutButton.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-gray-300', 'text-gray-500');
            logoutButton.classList.add('hover:bg-gray-100', 'text-gray-700');
            logoutButton.setAttribute('title', 'Logout');
            // Remove the offline submit prevention
            logoutForm.removeEventListener('submit', preventOfflineSubmit);
            
            // Restore normal icon styling
            const icon = logoutButton.querySelector('svg');
            if (icon) {
                icon.classList.remove('text-gray-400');
                icon.classList.add('text-gray-500');
            }
        }
    }
}

function preventOfflineSubmit(event) {
    if (!navigator.onLine) {
        event.preventDefault();
        Swal.fire({
            title: 'Offline',
            text: 'Cannot logout while you are offline. Please check your internet connection.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
    }
}

function updateLogoutButtons(isOffline) {
    // Update logout buttons in online users grid
    const logoutButtons = document.querySelectorAll('.logout-user-btn');
    
    logoutButtons.forEach(function(button) {
        if (isOffline) {
            button.disabled = true;
            button.classList.remove('logout-btn-online', 'text-orange-600', 'hover:text-orange-900', 'text-white', 'cursor-pointer');
            button.classList.add('logout-btn-offline', 'text-gray-400', 'cursor-not-allowed', 'opacity-50');
            button.setAttribute('title', 'Cannot logout user while offline');
            button.onclick = function() { return false; };
        } else {
            const userStatus = button.getAttribute('data-user-status');
            // Only enable if user is online and admin is online
            if (userStatus === 'online') {
                button.disabled = false;
                button.classList.remove('logout-btn-offline', 'text-gray-400', 'cursor-not-allowed', 'opacity-50');
                button.classList.add('logout-btn-online', 'text-orange-600', 'hover:text-orange-900', 'cursor-pointer');
                button.setAttribute('title', 'Logout user');
                const userId = button.getAttribute('data-user-id');
                button.onclick = function() { logoutUser(userId); };
            }
        }
    });
    
    // Also update logout buttons in DataTable (if table exists)
    if (activityTable) {
        // Redraw the table to update action buttons with offline status
        setTimeout(function() {
            updateDataTableLogoutButtons(isOffline);
        }, 100);
    }
}

function updateDataTableLogoutButtons(isOffline) {
    // Update logout buttons in the DataTable
    const tableLogoutButtons = document.querySelectorAll('#activity-logs-table .logout-user-btn');
    
    tableLogoutButtons.forEach(function(button) {
        if (isOffline) {
            button.disabled = true;
            button.classList.remove('text-orange-600', 'hover:text-orange-900');
            button.classList.add('text-gray-400', 'cursor-not-allowed', 'opacity-50');
            button.setAttribute('title', 'Cannot logout user while offline');
            button.onclick = function() { 
                Swal.fire({
                    title: 'Offline',
                    text: 'Cannot logout user while you are offline. Please check your internet connection.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return false; 
            };
        } else {
            const userStatus = button.getAttribute('data-user-status');
            // Only enable if user is online
            if (userStatus === 'online') {
                button.disabled = false;
                button.classList.remove('text-gray-400', 'cursor-not-allowed', 'opacity-50');
                button.classList.add('text-orange-600', 'hover:text-orange-900');
                button.setAttribute('title', 'Logout user');
                const userId = button.getAttribute('data-user-id');
                button.onclick = function() { logoutUser(userId); };
            }
        }
    });
}

function updateActionButtons(isOffline) {
    // Update other buttons that require network access
    const networkButtons = [
        '#bulk-delete-btn',
        'button[onclick="exportLogs()"]',
        'button[onclick="refreshData()"]',
        'button[onclick="refreshOnlineUsers()"]',
        'button[onclick="applyFilters()"]'
    ];
    
    networkButtons.forEach(function(selector) {
        const button = document.querySelector(selector);
        if (button) {
            if (isOffline) {
                button.disabled = true;
                button.classList.add('opacity-50', 'cursor-not-allowed');
                const originalTitle = button.getAttribute('title') || '';
                button.setAttribute('title', originalTitle + ' (Offline - feature disabled)');
            } else {
                button.disabled = false;
                button.classList.remove('opacity-50', 'cursor-not-allowed');
                const title = button.getAttribute('title') || '';
                button.setAttribute('title', title.replace(' (Offline - feature disabled)', ''));
            }
        }
    });
}

function showNetworkStatusMessage(status) {
    const message = status === 'online' ? 
        'Connection restored! All features are now available.' : 
        'You are offline. Some features may be disabled.';
    
    const icon = status === 'online' ? 'success' : 'warning';
    
    // Show a brief toast notification
    Swal.fire({
        title: status === 'online' ? 'Back Online' : 'Offline',
        text: message,
        icon: icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

function exportLogs() {
    const params = new URLSearchParams({
        user_id: $('#user_filter').val(),
        status: $('#status_filter').val(),
        device_type: $('#device_filter').val(),
        browser: $('#browser_filter').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val()
    });

    window.location.href = '{{ route("user-activity-logs.export") }}?' + params.toString();
}

function openCleanupModal() {
    $('#cleanup-modal').removeClass('hidden');
}

function closeCleanupModal() {
    $('#cleanup-modal').addClass('hidden');
}

function performCleanup() {
    const days = $('#cleanup-days').val();
    
    Swal.fire({
        title: 'Are you sure?',
        text: `All activity logs older than ${days} days will be permanently deleted.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, delete them!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("user-activity-logs.clean-old") }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    days: days
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Success!', response.message, 'success');
                        closeCleanupModal();
                        if (activityTable) {
                            activityTable.ajax.reload();
                        }
                        updateStatistics();
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to cleanup old logs', 'error');
                }
            });
        }
    });
}

function openSettingsModal() {
    $('#settings-modal').removeClass('hidden');
}

function closeSettingsModal() {
    $('#settings-modal').addClass('hidden');
}

function loadSettings() {
    $.get('{{ route("user-activity-logs.settings.get") }}', function(response) {
        if (response.success) {
            const settings = response.data;
            $('#cleanup-interval').val(settings.cleanup_interval || 'weekly');
            $('#retention-days').val(settings.retention_days || '90');
            $('#refresh-interval').val(settings.refresh_interval || '30');
            $('#records-per-page').val(settings.records_per_page || '25');
        }
    }).fail(function() {
        console.log('Failed to load settings, using defaults');
    });
}

function saveSettings() {
    const settings = {
        cleanup_interval: $('#cleanup-interval').val(),
        retention_days: $('#retention-days').val(),
        refresh_interval: $('#refresh-interval').val(),
        records_per_page: $('#records-per-page').val()
    };
    
    $.ajax({
        url: '{{ route("user-activity-logs.settings.save") }}',
        type: 'POST',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        data: settings,
        success: function(response) {
            if (response.success) {
                // Apply settings
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
                
                const refreshInterval = parseInt(settings.refresh_interval) * 1000;
                autoRefreshInterval = setInterval(function() {
                    if (currentTab === 'dashboard') {
                        loadDashboardData();
                    } else if (currentTab === 'online-users') {
                        refreshOnlineUsers();
                    }
                    updateStatistics();
                }, refreshInterval);
                
                closeSettingsModal();
                Swal.fire('Success', 'Settings saved successfully', 'success');
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        },
        error: function() {
            Swal.fire('Error', 'Failed to save settings', 'error');
        }
    });
}

// Add CSS for tab styling and offline states
const style = document.createElement('style');
style.textContent = `
    .tab-button.active {
        color: #2563eb !important;
        border-color: #2563eb !important;
    }
    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    /* Offline button styling */
    .logout-btn-offline {
        background-color: #d1d5db !important;
        color: #6b7280 !important;
        cursor: not-allowed !important;
        opacity: 0.6 !important;
        transition: all 0.2s ease-in-out;
    }
    
    .logout-btn-offline:hover {
        background-color: #d1d5db !important;
        color: #6b7280 !important;
        transform: none !important;
    }
    
    /* Main logout button offline styling */
    button[disabled].offline-logout {
        background-color: #f3f4f6 !important;
        color: #9ca3af !important;
        border-color: #e5e7eb !important;
        cursor: not-allowed !important;
        opacity: 0.7 !important;
    }
    
    button[disabled].offline-logout:hover {
        background-color: #f3f4f6 !important;
        color: #9ca3af !important;
    }
    
    /* Offline indicator animation */
    .offline-indicator {
        animation: pulse-red 2s infinite;
    }
    
    @keyframes pulse-red {
        0%, 100% { 
            background-color: #fca5a5; 
            opacity: 1; 
        }
        50% { 
            background-color: #ef4444; 
            opacity: 0.7; 
        }
    }
    
    /* Network status indicator */
    .network-status {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .network-status.offline {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    
    .network-status.online {
        background-color: #f0fdf4;
        color: #16a34a;
        border: 1px solid #bbf7d0;
    }
`;
document.head.appendChild(style);
</script>
@endsection