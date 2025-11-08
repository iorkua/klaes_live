@php
    $userRoles = array_map('trim', explode(',', auth()->user()->assign_role?? ''));
    $hasRole = function($role) use ($userRoles) {
        // Check for Super Admin first - gives full access
        if (in_array('Supper Admin', $userRoles)) {
            return true;
        }
        
        // Direct, case-insensitive, trimmed comparison
        foreach ($userRoles as $userRole) {
            if (strcasecmp(trim($userRole), trim($role)) === 0) {
                return true;
            }
        }
        return false;
    };
@endphp
<div class="sidebar border-r border-gray-200 bg-white">
  <!-- Sidebar Header -->
  <div class="sidebar-header border-b border-gray-200 h-16 flex items-center px-6 bg-gradient-to-r from-white via-blue-100 to-purple-200">
    <div class="flex items-center gap-2">
      <div class="relative">
        <img
          src="{{ asset('storage/upload/logo/logo.png') }}"
          alt="KLAES Logo"
          class="h-10 w-auto object-contain rounded"
        />
      </div>
       
    </div>
  </div>

  <!-- Sidebar Content -->
  <div class="sidebar-content p-2 overflow-y-auto max-h-[calc(100vh-8rem)] scroll-smooth">
    <!-- 0. Dashboard -->
    @if($hasRole('Dashboard'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="dashboard">
        <div class="flex items-center gap-2">
          <i data-lucide="layout-dashboard" class="h-5 w-5 text-blue-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Dashboard</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="dashboard"></i>
      </div>
      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="dashboard">
        <a href="{{ route('dashboard') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
          <i data-lucide="home" class="h-4 w-4 text-blue-500"></i>
          <span>Dashboard</span>
        </a>
      </div>
    </div>
    @endif

    <!-- 1. Customer Relationship Management --@if(
      $hasRole('Person') || $hasRole('Corporate') || $hasRole('Customer Manager')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="customer">
        <div class="flex items-center gap-2">
          <i data-lucide="user-plus" class="h-6 w-6 module-icon-customer text-green-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Customer Relationship Management</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="customer"></i>
      </div>
      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="customer">
        @if($hasRole('Person Management'))
        <a href="/person/individual" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="users" class="h-4 w-4 text-green-500"></i>
          <span>Person Management</span>
        </a>
        @endif
        @if($hasRole('Corporate Management'))
        <a href="/person/corporate" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="building" class="h-4 w-4 text-green-500"></i>
          <span>Corporate Management</span>
        </a>
        @endif
        @if($hasRole('Customer Manager'))
        <a href="/appointment" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="calendar-clock" class="h-4 w-4 text-green-500"></i>
          <span>Customer Manager</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 2. Programmes -->
    @if(
      $hasRole('Allocation') || $hasRole('Governors List') || $hasRole('Commissioners List') ||
      $hasRole('Compensation/Resettlement') || $hasRole('Recertification') || $hasRole('Conversion/Regularization') ||
      $hasRole('Land Property Enumeration') || $hasRole('Data Repository') || $hasRole('Migrate Data')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="programmes">
        <div class="flex items-center gap-2">
          <i data-lucide="briefcase" class="h-5 w-5 module-icon-programmes text-purple-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Programmes</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="programmes"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="programmes">
        @if($hasRole('Allocation') || $hasRole('Governors List') || $hasRole('Commissioners List'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="allocation">
          <div class="flex items-center gap-2">
            <i data-lucide="building" class="h-4 w-4 text-purple-500"></i>
            <span>Allocation</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="allocation"></i>
        </div>

        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="allocation">
          @if($hasRole('Governors List'))
          <a href="/programmes/allocation/governors-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="list" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Governors List</span>
          </a>
          @endif
          @if($hasRole('Commissioners List'))
          <a href="/programmes/allocation/commissioners-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="list-checks" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Commissioners List</span>
          </a>
          @endif
        </div>
        @endif

        @if($hasRole('Compensation/Resettlement') || $hasRole('Recertification') || $hasRole('Conversion/Regularization'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="resettlement">
          <div class="flex items-center gap-2">
            <i data-lucide="home" class="h-4 w-4 text-purple-500"></i>
            <span>Resettlement</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="resettlement"></i>
        </div>

        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="resettlement">
          @if($hasRole('Governors List'))
          <a href="/programmes/resettlement/governors-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="list" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Governors List</span>
          </a>
          @endif
          @if($hasRole('Commissioners List'))
          <a href="/programmes/resettlement/commissioners-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="list-checks" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Commissioners List</span>
          </a>
          @endif
        </div>
        @endif

        @if($hasRole('Recertification'))
        <a href="{{route('recertification.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.index') ? 'active' : '' }}">
          <i data-lucide="file-cog" class="h-4 w-4 text-purple-500"></i>
          <span>Recertification</span>
        </a>
        @endif
        @if($hasRole('Conversion/Regularization'))
        <a href="/programmes/regularization" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-down" class="h-4 w-4 text-purple-500 flex-shrink-0"></i>
          <span class="truncate">Conversion/Regularization</span>
        </a>
        @endif
        
        @if($hasRole('Land Property Enumeration') || $hasRole('Data Repository') || $hasRole('Migrate Data'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="enumeration">
          <div class="flex items-center gap-2">
            <i data-lucide="file-down" class="h-4 w-4 text-purple-500"></i>
            <span>Land Property Enumeration</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="enumeration"></i>
        </div>

        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="enumeration">
          @if($hasRole('Data Repository'))
          <a href="/programmes/enumeration/data-repository" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="database" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Data Repository</span>
          </a>
          @endif
          @if($hasRole('Migrate Data'))
          <a href="/programmes/enumeration/migrate-data" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-input" class="h-3.5 w-3.5 text-purple-400"></i>
            <span>Migrate Data</span>
          </a>
          @endif
        </div>
        @endif
      </div>
    </div>
    @endif

    <!-- 3. Information Products -->
    @if(
      $hasRole('Letter of Administration/Grant/Offer Letter') || $hasRole('Occupancy Permit (OP)') || $hasRole('Site Plan/Parcel Plan') ||
      $hasRole('Right of Occupancy') || $hasRole('Certificate of Occupancy')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="infoProducts">
        <div class="flex items-center gap-2"> 
          <i data-lucide="file-output" class="6-5 w-6 module-icon-info-products text-indigo-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Information Products</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="infoProducts"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="infoProducts">
        @if($hasRole('Letter of Administration/Grant/Offer Letter'))
        <a href="/documents/letter-of-administration" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-plus-2" class="h-4 w-4 text-indigo-500"></i>
          <span>Letter of Administration/Grant/Offer Letter</span>
        </a>
        @endif
        @if($hasRole('Occupancy Permit (OP)'))
        <a href="/documents/occupancy-permit" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-warning" class="h-4 w-4 text-indigo-500"></i>
          <span>Occupancy Permit (OP)</span>
        </a>
        @endif
        @if($hasRole('Site Plan/Parcel Plan'))
        <a href="/documents/site-plan" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-text" class="h-4 w-4 text-indigo-500"></i>
          <span>Site Plan/Parcel Plan</span>
        </a>
        @endif
        @if($hasRole('Right of Occupancy'))
        <a href="/documents/right-of-occupancy" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-check" class="h-4 w-4 text-indigo-500"></i>
          <span>Right of Occupancy</span>
        </a>
        @endif
        @if($hasRole('Certificate of Occupancy'))
        <a href="/documents/certificate-of-occupancy" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-text" class="h-4 w-4 text-indigo-500"></i>
          <span>Certificate of Occupancy</span>
        </a>
        @endif
      </div>
    </div>
    @endif
 <!-- 6. Revenue Management -->
 
 @if(
  $hasRole('Billing') || $hasRole('Generate Receipt') ||
  $hasRole('Land Use Charge (LUC)') || $hasRole('Bill Balance')
)
<div class="py-1 px-3 mb-0.5 border-t border-slate-100">
  <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="revenue">
    <div class="flex items-center gap-2"> 
      <i data-lucide="banknote" class="h-5 w-5 text-emerald-600"></i>
      <span class="text-sm font-bold uppercase tracking-wider">Revenue Management</span>
    </div>
    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="revenue"></i>
  </div>

  <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="revenue">
    @if($hasRole('Automated Billing') || $hasRole('Legacy Billing'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="billing">
      <div class="flex items-center gap-2">
        <i data-lucide="receipt" class="h-4 w-4 text-emerald-500"></i>
        <span>Billing</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="billing"></i>
    </div>

    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="billing">
      @if($hasRole('Automated Billing'))
      <a href="/revenue/billing/automated" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="cpu" class="h-3.5 w-3.5 text-emerald-400"></i>
        <span>Automated Billing</span>
      </a>
      @endif
      @if($hasRole('Legacy Billing'))
      <a href="/revenue/billing/legacy" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="history" class="h-3.5 w-3.5 text-emerald-400"></i>
        <span>Legacy Billing</span>
      </a>
      @endif
    </div>
    @endif
    
    @if($hasRole('Generate Receipt') || $hasRole('Land Use Charge (LUC)') || $hasRole('Bill Balance'))
    <a href="/revenue/generate-receipt" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="receipt" class="h-4 w-4 text-emerald-500"></i>
      <span>Generate Receipt</span>
    </a>
    @endif
    @if($hasRole('Land Use Charge (LUC)'))
    <a href="/revenue/land-use-charge" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="tag" class="h-4 w-4 text-emerald-500"></i>
      <span>Land Use Charge (LUC)</span>
    </a>
    @endif
    @if($hasRole('Bill Balance'))
    <a href="/revenue/bill-balance" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="calculator" class="h-4 w-4 text-emerald-500"></i>
      <span>Bill Balance</span>
    </a>
    @endif
  </div>
</div>
@endif
    <!-- 4. Deeds -->
    @if(
      $hasRole('Deeds - Property Records Assistant (Legacy Records)') || $hasRole('Deeds - Instrument Capture (New Records)') ||
      $hasRole('Deeds - Instrument Registration (New Registration)') || $hasRole('Deeds - Instrument Registration Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="deeds">
      <div class="flex items-center gap-2"> 
        <i data-lucide="book-open" class="h-6 w-6 module-icon-instrument text-amber-600"></i>
        <span class="text-sm font-bold uppercase tracking-wider">Deeds</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="deeds"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="deeds">
      @if($hasRole('Deeds - Property Records Assistant (Legacy Records)'))
      <a href="{{route('propertycard.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('propertycard.index') ? 'active' : '' }}">
        <i data-lucide="sparkles" class="h-4 w-4"></i>
        <span>Property Records Assistant (Legacy Records)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Capture (New Records)'))
      <a href="{{route('instruments.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('instruments.index') ? 'active' : '' }}">
        <i data-lucide="file-input" class="h-4 w-4 text-amber-500"></i>
        <span>Instrument Capture (New Records)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Registration (New Registration)'))
      <a href="{{route('instrument_registration.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('instrument_registration.index') ? 'active' : '' }}">
        <i data-lucide="book-open" class="h-4 w-4 text-amber-500"></i>
        <span>Instrument Registration (New Registration)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Registration Reports'))
      <a href="/instrument-registration-reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="file-bar-chart" class="h-4 w-4 text-amber-500"></i>
        <span>Instrument Registration Reports</span>
      </a>
      @endif
      </div>
    </div>
    @endif

    <!-- 5. Search -->
    @if(
      $hasRole('Deeds - Official (for filing purpose)') || $hasRole('Deeds - On-Premise (Pay-Per-Search)') || $hasRole('Deeds - Legal Search Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="search">
      <div class="flex items-center gap-2"> 
        <i data-lucide="file-search" class="h-6 w-6 module-icon-legal-search text-cyan-600"></i>
        <span class="text-sm font-bold uppercase tracking-wider">Search</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="search"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="search">
      @if($hasRole('Deeds - Official (for filing purpose)') || $hasRole('Deeds - On-Premise (Pay-Per-Search)'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="legalSearch">
        <div class="flex items-center gap-2">
        <i data-lucide="scale" class="h-4 w-4 text-cyan-500"></i>
        <span>Legal Search</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="legalSearch"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="legalSearch">
        @if($hasRole('Deeds - Official (for filing purpose)'))
        <a href="{{route('legal_search.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('legal_search.index') ? 'active' : '' }}">
        <i data-lucide="file-check-2" class="h-3.5 w-3.5 text-cyan-400"></i>
        <span>Official (for filing purpose)</span>
        </a>
        @endif
        @if($hasRole('Deeds - On-Premise (Pay-Per-Search)'))
        <a href="{{route('onpremise.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('onpremise.index') ? 'active' : '' }}">
        <i data-lucide="building" class="h-3.5 w-3.5 text-cyan-400"></i>
        <span>On-Premise - Pay-per-Search</span>
        </a>
        @endif
      </div>
      @endif
      @if($hasRole('Deeds - Legal Search Reports'))
      <a href="{{route('legalsearchreports.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('legalsearchreports.index') ? 'active' : '' }}">
        <i data-lucide="file-bar-chart" class="h-3.5 w-3.5 text-cyan-400"></i>
        <span>Legal Search Reports</span>
      </a>
      @endif
      </div>
    </div>
    @endif

   

 
    <!-- 7. Lands -->
    @if(
      $hasRole('Lands - File Tracker/Tracking - RFID') || $hasRole('Lands - File Digital Archive – Doc-WARE') || $hasRole('EDMS - Indexing') ||
      $hasRole('EDMS - File Indexing Assistant') || $hasRole('EDMS - Print File Labels') || $hasRole('EDMS - Scanning') ||
      $hasRole('EDMS - Upload') || $hasRole('EDMS - Download') || $hasRole('EDMS - PageTyping')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="lands">
      <div class="flex items-center gap-2">
        <i data-lucide="landmark" class="h-5 w-5"></i>
        <span class="text-sm font-bold uppercase tracking-wider">Lands</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="lands"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="lands">
      <!-- File Tracker Section -->
   @if($hasRole('Lands - File Tracker/Tracking - RFID'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="fileTracker">
        <a href="{{ route('filetracker.index') }}" class="flex items-center gap-2 w-full {{ request()->routeIs('filetracker.index') ? 'active' : '' }}">
          <i data-lucide="radio-tower" class="h-4 w-4 text-blue-500"></i>
          <span>File Tracker/Tracking - RFID</span>
        </a>
      </div>
      @endif

      <!-- File Digital Archive Section -->
      @if($hasRole('Lands - File Digital Archive – Doc-WARE')) 
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="fileArchive">
        <a href="{{ route('filearchive.index') }}" class="flex items-center gap-2 w-full {{ request()->routeIs('filearchive.index') ? 'active' : '' }}">
          <i data-lucide="archive" class="h-4 w-4 text-blue-500"></i>
          <span>File Digital Library – Doc-WARE</span>
        </a>
      </div>
      @endif

      <!-- EDMS Section -->
      @if($hasRole('EDMS - Indexing'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="edms">
        <div class="flex items-center gap-2">
        <i data-lucide="database" class="h-4 w-4 text-blue-500"></i>
        <span>EDMS</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="edms"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="edms">
        <!-- Indexing -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="indexing">
        <div class="flex items-center gap-2">
          <i data-lucide="list" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>Indexing</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="indexing"></i>
        </div>

        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="indexing">
        @if($hasRole('EDMS - File Indexing Assistant'))
        <a href="{{route('fileindexing.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('fileindexing.index') ? 'active' : '' }}">
          <i data-lucide="file-search" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>File Indexing Assistant</span>
        </a>
        @endif

        @if($hasRole('EDMS - Print File Labels'))
        <a href="{{route('printlabel.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('printlabel.index') ? 'active' : '' }}">
          <i data-lucide="printer" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>Print File Labels</span>
        </a>
        @endif
        </div>

        <!-- Scanning -->
        @if($hasRole('EDMS - Scanning'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="scanning">
        <div class="flex items-center gap-2">
          <i data-lucide="scan" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>Scanning</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="scanning"></i>
        </div>

        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="scanning">
        @if($hasRole('EDMS - Upload'))
        <a href="{{route('scanning.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('scanning.index') ? 'active' : '' }}">
          <i data-lucide="upload" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>Upload</span>
        </a>
        @endif

        @if($hasRole('EDMS - Download'))
        <a href="/file-digital-registry/download" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="download" class="h-3.5 w-3.5 text-blue-400"></i>
          <span>Download</span>
        </a>
        @endif
        </div>
        @endif

        <!-- PageTyping -->
        @if($hasRole('EDMS - PageTyping'))
        <a href="{{route('pagetyping.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('pagetyping.index') ? 'active' : '' }}">
        <i data-lucide="type" class="h-3.5 w-3.5 text-blue-400"></i>
        <span>PageTyping</span>
        </a>
        @endif
      </div>
      @endif
      </div>
    </div>
    @endif

    <!-- 8. Physical Planning -->
    @if(
      $hasRole('PP - Regular Applications') || $hasRole('PP - SLTR Applications') || $hasRole('PP Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="physicalPlanning">
        <div class="flex items-center gap-2">
          <i data-lucide="ruler" class="h-5 w-5"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Physical Planning</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="physicalPlanning"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="physicalPlanning">
        @if($hasRole('PP - Regular Applications'))
        <!-- a. Regular Applications -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="regularApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
            <span>Regular Applications</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="regularApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="regularApplications">
       
          <a href="/physical-planning/regular/planning-recommendation" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
            <span>Planning Recommendation</span>
          </a>

             <a href="/physical-planning/regular/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
            <span>Memo</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('PP - SLTR Applications'))
        <!-- b. ST Applications -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
            <span>ST Applications</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stApplications">
        
          <a href="{{route('programmes.approvals.planning_recomm')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.approvals.planning_recomm') && request()->query('url') !== 'view' ? 'active' : '' }}">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
            <span>Planning Recommendation</span>
          </a>

            <a href="{{route('stmemo.siteplan')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200" {{ request()->routeIs('stmemo.siteplan') ? 'active' : '' }}>
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
            <span>Memo</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('PP - SLTR Applications'))
        <!-- c. SLTR Applications -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4"></i>
            <span>SLTR Applications</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrApplications">
          <a href="/physical-planning/sltr/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
            <span>Memo</span>
          </a>
          <a href="/physical-planning/sltr/planning-recommendation" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
            <span>Planning Recommendation</span>
          </a>
        </div>
        @endif
        
        <!-- d. PP Reports -->
        @if($hasRole('PP Reports'))
        <a href="/physical-planning/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
          <span>PP Reports</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 9. Survey -->

     
    @if(
      $hasRole('Survey - Records') || $hasRole('Survey – AI Digital Assistant') || 
      $hasRole('Survey - GIS') || $hasRole('Survey - Approvals') || 
      $hasRole('Survey - E-Registry') || $hasRole('Survey Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="survey">
        <div class="flex items-center gap-2">
          <i data-lucide="compass" class="h-5 w-5"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Survey</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="survey"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="survey">
        @if($hasRole('Survey - Records'))
        <a href="{{route('survey_record.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('survey_record.index') ? 'active' : '' }}">
          <i data-lucide="clipboard" class="h-4 w-4"></i>
          <span>Records</span>
        </a>
        @endif
        @if($hasRole('Survey - GIS'))
        <a href="/survey/gis" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="map" class="h-4 w-4"></i>
          <span>GIS Reports</span>
        </a>
        @endif
        @if($hasRole('Survey - Approvals'))
        <a href="/survey/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="check-circle" class="h-4 w-4"></i>
          <span>Approvals</span>
        </a>
        @endif
        @if($hasRole('Survey - E-Registry'))
        <a href="/survey/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4"></i>
          <span>E-Registry</span>
        </a>
        @endif


        @if($hasRole('Survey Reports'))
        <a href="{{ route('survey_plan_extraction.index') }}?url=survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200  {{ request()->routeIs('survey_plan_extraction.index') && request()->query('url') === 'survey' ? 'active' : '' }}">
          <i data-lucide="sparkles" class="h-4 w-4" style="color:#E7C117FF"></i>
          <span>Survey Plan Extraction</span>
        </a>
        @endif  
        
        @if($hasRole('Survey Reports'))
        <a href="/survey/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
          <span>Survey Reports</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 10. Cadastral -->
    @if(
      $hasRole('Cad - Records') || $hasRole('Cad - GIS') || $hasRole('Cad - Approvals') ||
      $hasRole('Cad - E-Registry') || $hasRole('Cadastral Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="cadastral">
        <div class="flex items-center gap-2">
          <i data-lucide="map" class="h-5 w-5"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Cadastral</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="cadastral"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="cadastral">
        @if($hasRole('Cad - Records'))
        <a href="{{route('survey_cadastral.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('survey_cadastral.index') ? 'active' : '' }}">
          <i data-lucide="clipboard" class="h-4 w-4"></i>
          <span>Records</span>
        </a>
        @endif
        @if($hasRole('Cad - GIS'))
        <a href="/cadastral/gis" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="map" class="h-4 w-4"></i>
          <span>GIS Reports</span>
        </a>
        @endif
        @if($hasRole('Cad - Approvals'))
        <a href="/cadastral/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="check-circle" class="h-4 w-4"></i>
          <span>Approvals</span>
        </a>
        @endif
        @if($hasRole('Cad - E-Registry'))
        <a href="/cadastral/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4"></i>
          <span>E-Registry</span>
        </a>
        @endif
        @if($hasRole('Cadastral Reports'))
        <a href="{{ route('survey_plan_extraction.index') }}?url=survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200  {{ request()->routeIs('survey_plan_extraction.index') && request()->query('url') === 'survey' ? 'active' : '' }}">
          <i data-lucide="sparkles" class="h-4 w-4" style="color:#E7C117FF"></i>
          <span>Survey Plan Extraction</span>
        </a>
        @endif 
        
        @if($hasRole('Cadastral Reports'))
        <a href="/cadastral/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
          <span>Cadastral Reports</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 11. GIS -->
    @if(
      $hasRole('GIS - Records') || $hasRole('GIS – AI Digital Assistant') || $hasRole('GIS - GIS') ||
      $hasRole('GIS - Approvals') || $hasRole('GIS - e-Registry') || $hasRole('GIS Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="gis">
      <div class="flex items-center gap-2"> 
        <i data-lucide="map" class="h-5 w-5"></i>
        <span class="text-sm font-bold uppercase tracking-wider">GIS</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="gis"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="gis">
      @if($hasRole('GIS - Records'))
      <a href="{{route('gis_record.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('gis_record.index') ? 'active' : '' }}">
        <i data-lucide="clipboard" class="h-4 w-4"></i>
        <span>Records</span>
      </a>
      @endif
      @if($hasRole('GIS – AI Digital Assistant'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="bot" class="h-4 w-4"></i>
        <span>AI Digital Assistant</span>
      </a>
      @endif
      @if($hasRole('GIS - GIS'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="map" class="h-4 w-4"></i>
        <span>GIS</span>
      </a>
      @endif
      @if($hasRole('GIS - Approvals'))
      <a href="/gis/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="check-circle" class="h-4 w-4"></i>
        <span>Approvals</span>
      </a>
      @endif
      @if($hasRole('GIS - e-Registry'))
      <a href="/gis/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="database" class="h-4 w-4"></i>
        <span>E-Registry</span>
      </a>
      @endif
      @if($hasRole('GIS Reports'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
        <span>GIS Reports</span>
      </a>
      @endif
      </div>
    </div>
    @endif

  <!-- 12. Sectional Titling -->
 
@if(
  $hasRole('ST - Overview') || $hasRole('ST - Primary Application') || $hasRole('ST - Unit Application') ||
  $hasRole('ST - Field Data') || $hasRole('ST - Payments') || $hasRole('ST - Approvals') ||
  $hasRole('ST - Memo') || $hasRole('ST - Certificate') || $hasRole('ST - e-Registry') || $hasRole('ST - GIS') || $hasRole('ST - Survey') ||
  $hasRole('ST - Reports')
)
<div class="py-1 px-3 mb-0.5 border-t border-slate-100">
  <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="sectionalTitling">
    <div class="flex items-center gap-2">
      <i data-lucide="building-2" class="h-5 w-5"></i>
      <span class="text-sm font-bold uppercase tracking-wider">Sectional Titling</span>
    </div>
    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sectionalTitling"></i>
  </div>

  <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="sectionalTitling">
    @if($hasRole('ST - Overview'))
    <a href="{{ route('sectionaltitling.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.index') ? 'active' : '' }}">
      <i data-lucide="file-text" class="h-4 w-4"></i>
      <span>Overview</span>
    </a>
    @endif

    @if($hasRole('ST - Primary Application') || $hasRole('ST - Unit Application'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="applications">
      <div class="flex items-center gap-2">
        <i data-lucide="file-plus" class="h-4 w-4"></i>
        <span>Applications</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="applications"></i>
    </div>
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="applications">
      @if($hasRole('ST - Primary Application'))
      <a href="{{ route('sectionaltitling.primary') }}?url=infopro" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.primary') && request()->query('url') === 'infopro' ? 'active' : '' }}">
        <i data-lucide="file-plus" class="h-3.5 w-3.5"></i>
        <span>Primary Applications</span>
      </a>
      @endif
      @if($hasRole('ST - Unit Application'))
      <a href="{{ route('sectionaltitling.units') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.units') && !in_array(request()->query('url'), ['recommendation', 'phy_planning']) ? 'active' : '' }}">
        <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
        <span>Unit Applications</span>
      </a>
      @endif
    </div>
    @endif

    @if($hasRole('ST - Field Data'))
    <a href="{{route('programmes.field-data')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.field-data') ? 'active' : '' }}">
      <i data-lucide="clipboard-list" class="h-4 w-4"></i>
      <span>Field Data Integration</span>
    </a>
    @endif

    @if($hasRole('ST - Payments'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stPayments">
      <div class="flex items-center gap-2">
        <i data-lucide="credit-card" class="h-4 w-4"></i>
        <span>Bills & Payments</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stPayments"></i>
    </div>
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stPayments">
      <a href="{{route('programmes.bills')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.bills') ? 'active' : '' }}">
        <i data-lucide="receipt" class="h-3.5 w-3.5"></i>
        <span>Bills</span>
      </a>
<a href="{{route('programmes.payments')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payments') && !request()->query('url') ? 'active' : '' }}">
        <i data-lucide="credit-card" class="h-3.5 w-3.5"></i>
        <span>Payments</span>
      </a>
      <a href="{{route('programmes.payments')}}?url=report" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payments') && request()->query('url') === 'report' ? 'active' : '' }}">
        <i data-lucide="file-bar-chart" class="h-3.5 w-3.5"></i>
        <span>Payments Report</span>
      </a>
 
    </div>
    @endif
    
    @if($hasRole('ST - Approvals'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stApprovals">
      <div class="flex items-center gap-2">
        <i data-lucide="check-circle" class="h-4 w-4"></i>
        <span>Approvals (Other Departments)</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stApprovals"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stApprovals">
      <a href="{{route('st_deeds.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('st_deeds.index') ? 'active' : '' }}">
        <i data-lucide="file-check" class="h-3.5 w-3.5"></i>
        <span>ST Deeds Registration View</span>
      </a>
      
      <a href="{{route('other_departments.survey_primary')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('other_departments.survey_primary') ? 'active' : '' }}">
        <i data-lucide="building-2" class="h-3.5 w-3.5"></i>
        <span>Other Departments</span>
      </a>
      
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="physicalPlanningApprovals">
        <div class="flex items-center gap-2">
          <i data-lucide="ruler" class="h-3.5 w-3.5"></i>
          <span>Physical Planning</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="physicalPlanningApprovals"></i>
      </div>
      
      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="physicalPlanningApprovals">
        <a href="{{ route('programmes.approvals.planning_recomm', ['url' => 'view']) }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.approvals.planning_recomm') && request()->query('url') === 'view' ? 'active' : '' }}">
          <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
          <span>Planning Recommendation</span>
        </a>
        
        <a href="{{route('programmes.memo')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.memo') ? 'active' : '' }}">
          <i data-lucide="clipboard-list" class="h-3.5 w-3.5"></i>
          <span>ST Memo</span>
        </a>
      </div>
      
     
    </div>
    <a href="{{route('programmes.approvals.director')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.approvals.director') ? 'active' : '' }}">
      <i data-lucide="stamp" class="h-3.5 w-3.5"></i>
      <span>Director's Approval</span>
    </a>
    @endif

    {{-- @if($hasRole('ST - Memo'))
    <a href="{{route('programmes.memo')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.memo') ? 'active' : '' }}">
      <i data-lucide="clipboard-list" class="h-4 w-4"></i>
      <span>ST Memo</span>
    </a>
    @endif --}}

    @if($hasRole('ST - Certificate'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="certificate">
      <div class="flex items-center gap-2">
        <i data-lucide="award" class="h-4 w-4"></i>
        <span>Certificate</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="certificate"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="certificate">
      @if($hasRole('ST - RofO'))
      <a href="{{route('programmes.rofo')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.rofo') ? 'active' : '' }}">
        <i data-lucide="folder" class="h-3.5 w-3.5"></i>
        <span>RofO</span>
      </a>
      @endif
      @if($hasRole('ST - CofO'))
      <a href="{{route('programmes.certificates')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.certificates') ? 'active' : '' }}">
        <i data-lucide="file-cog" class="h-3.5 w-3.5"></i>
        <span>CofO</span>
      </a>
      @endif
    </div>
    @endif

    @if($hasRole('ST - e-Registry'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="eRegistry">
      <div class="flex items-center gap-2">
        <i data-lucide="database" class="h-4 w-4"></i>
        <span>e-Registry</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="eRegistry"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="eRegistry">
      @if($hasRole('ST - Files'))
      <a href="{{route('programmes.eRegistry')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.eRegistry') ? 'active' : '' }}">
        <i data-lucide="folder" class="h-3.5 w-3.5"></i>
        <span>Files</span>
      </a>
      @endif
    </div>
    @endif
    
    @if($hasRole('ST - GIS'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stGis">
      <div class="flex items-center gap-2">
        <i data-lucide="map" class="h-4 w-4"></i>
        <span>GIS</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stGis"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stGis">
      @if($hasRole('ST - GIS Attribution') || $hasRole('ST - Map'))
      <a href="{{route('gis.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('gis.index') ? 'active' : '' }}">
        <i data-lucide="database" class="h-3.5 w-3.5"></i>
        <span>Attribution</span>
      </a>
      
      @endif
    </div>
    @endif
    
    @if($hasRole('ST - Survey'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stSurvey">
      <div class="flex items-center gap-2">
        <i data-lucide="land-plot" class="h-4 w-4"></i>
        <span>Survey</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stSurvey"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stSurvey">
      <a href="{{route('attribution.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('attribution.index') ? 'active' : '' }}">
        <i data-lucide="land-plot" class="h-3.5 w-3.5"></i>
        <span>Attribution</span>
      </a>
    </div>
    @endif

    @if($hasRole('ST - Reports'))
    <a href="{{ route('map.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('map.index') ? 'active' : '' }}">
        <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
        <span>Sectional Titling BaseMap</span>
      </a>
    <a href="{{route('programmes.report')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.report') ? 'active' : '' }}">
      <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
      <span>Reports</span>
    </a>
    @endif
  </div>
</div>
@endif
    <!-- 13. SLTR/First Registration -->
    @if(
      $hasRole('SLTR - Overview') || $hasRole('SLTR - Application') || $hasRole('SLTR - Claimants') ||
      $hasRole('SLTR - Legacy Data') || $hasRole('SLTR - Field Data') || $hasRole('SLTR - Payments') ||
      $hasRole('SLTR - Approvals') || $hasRole('SLTR - Other Departments') || $hasRole('SLTR - Memo') ||
      $hasRole('SLTR - Certificate') || $hasRole('SLTR - e-Registry') || $hasRole('SLTR - GIS') || $hasRole('SLTR - Survey') ||
      $hasRole('SLTR - Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="sltr">
        <div class="flex items-center gap-2">
          <i data-lucide="file-search" class="h-5 w-5"></i>
          <span class="text-sm font-bold uppercase tracking-wider">SLTR/First Registration</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltr"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="sltr">
        @if($hasRole('SLTR - Overview'))
        <a href="{{route('sltroverview.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltroverview.index') ? 'active' : '' }}">
          <i data-lucide="file-text" class="h-4 w-4"></i>
          <span>Overview</span>
        </a>
        @endif
        @if($hasRole('SLTR - Application'))
        <a href="{{route('sltrapplication.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrapplication.index') ? 'active' : '' }}">
          <i data-lucide="file-plus" class="h-4 w-4"></i>
          <span>Application</span>
        </a>
        @endif
        @if($hasRole('SLTR - Claimants'))
        <a href="/programmes/sltr/claimants" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="users" class="h-4 w-4"></i>
          <span>Claimants</span>
        </a>
        @endif
        @if($hasRole('SLTR - Legacy Data'))
        <a href="/programmes/sltr/legacy-data" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrlegacydata.index') ? 'active' : '' }}">
          <i data-lucide="history" class="h-4 w-4"></i>
          <span>Legacy Data</span>
        </a>
        @endif
        @if($hasRole('SLTR - Field Data'))
        <a href="{{route('sltr_field_data.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="clipboard-list" class="h-4 w-4"></i>
          <span>Field Data</span>
        </a>
        @endif
        @if($hasRole('SLTR - Payments'))
        <a href="/programmes/sltr/payments" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="credit-card" class="h-4 w-4"></i>
          <span>Payments</span>
        </a>
        @endif
        
        @if($hasRole('SLTR - Approvals'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrApprovals">
          <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="h-4 w-4"></i>
            <span>Approvals</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrApprovals"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrApprovals">
          <a href="/programmes/sltr/approvals/planning" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
            <span>Planning Recommendation</span>
          </a>
          <a href="/programmes/sltr/approvals/director" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="stamp" class="h-3.5 w-3.5"></i>
            <span>Director SLTR</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Other Departments'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrDepartments">
          <div class="flex items-center gap-2">
            <i data-lucide="building-2" class="h-4 w-4"></i>
            <span>Other Departments</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrDepartments"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrDepartments">
          <a href="/programmes/sltr/departments/lands" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
            <span>Lands</span>
          </a>
          <a href="{{route('sltrapproval.deeds')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrapproval.deeds') ? 'active' : '' }}">
            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
            <span>Deeds</span>
          </a>
          <a href="/programmes/sltr/departments/survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
            <span>Survey</span>
          </a>
          <a href="/programmes/sltr/departments/cadastral" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
            <span>Cadastral</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Memo'))
        <a href="/programmes/sltr/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="clipboard-list" class="h-4 w-4"></i>
          <span>SLTR Memo</span>
        </a>
        @endif        
        @if($hasRole('SLTR - Certificate') || $hasRole('SLTR - e-Registry') || $hasRole('SLTR - GIS') || $hasRole('SLTR - Survey') || $hasRole('SLTR - Reports'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrCertificate">
          <div class="flex items-center gap-2">
            <i data-lucide="file-badge" class="h-4 w-4"></i>
            <span>Certificate</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrCertificate"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrCertificate">
          <a href="/programmes/sltr/certificate/rofo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="folder" class="h-3.5 w-3.5"></i>
            <span>RofO</span>
          </a>
          <a href="/programmes/sltr/certificate/cofo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-badge" class="h-3.5 w-3.5"></i>
            <span>CofO</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - e-Registry') || $hasRole('SLTR - GIS') || $hasRole('SLTR - Survey') || $hasRole('SLTR - Reports'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrERegistry">
          <div class="flex items-center gap-2">
            <i data-lucide="database" class="h-4 w-4"></i>
            <span>e-Registry</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrERegistry"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrERegistry">
          <a href="/programmes/sltr/e-registry/files" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="folder" class="h-3.5 w-3.5"></i>
            <span>Files</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - GIS'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrGis">
          <div class="flex items-center gap-2">
            <i data-lucide="map" class="h-4 w-4"></i>
            <span>GIS</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrGis"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrGis">
          <a href="/programmes/sltr/gis/attribution" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="database" class="h-3.5 w-3.5"></i>
            <span>Attribution</span>
          </a>
          <a href="/programmes/sltr/gis/map" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="map-pin" class="h-3.5 w-3.5"></i>
            <span>Map</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Survey'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrSurvey">
          <div class="flex items-center gap-2">
            <i data-lucide="land-plot" class="h-4 w-4"></i>
            <span>Survey</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrSurvey"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrSurvey">
          <a href="/programmes/sltr/survey/attribution" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="land-plot" class="h-3.5 w-3.5"></i>
            <span>Attribution</span>
          </a>
        </div>
        @endif

        <a href="/programmes/sltr/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4"></i>
          <span>Reports</span>
        </a>
      </div>
    </div>
    @endif

    <!-- 14. Systems -->
    @if($hasRole('Caveat') || $hasRole('Encumbrance'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="systems">
      <div class="flex items-center gap-2"> 
        <i data-lucide="shield" class="h-5 w-5 module-icon-systems"></i>
        <span class="text-sm font-bold uppercase tracking-wider">Systems</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="systems"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="systems">
        @if($hasRole('Caveat'))
        <a href="/systems/caveat" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="shield-alert" class="h-4 w-4"></i>
          <span>Caveat</span>
        </a>
        @endif
        @if($hasRole('Encumbrance'))
        <a href="/systems/encumbrance" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="lock" class="h-4 w-4"></i>
          <span>Encumbrance</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 15. Legacy Systems -->
    @if($hasRole('Legacy System'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="legacy">
        <div class="flex items-center gap-2">
          <i data-lucide="hard-drive" class="h-5 w-5"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Legacy Systems</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="legacy"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="legacy">
        <a href="/legacy-systems" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4"></i>
          <span>Legacy Systems</span>
        </a>
      </div>
    </div>
    @endif

    <!-- 16. System Admin -->
    @if($hasRole('User Account') || $hasRole('Departments') || $hasRole('User Roles') || $hasRole('System Settings'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="admin">
        <div class="flex items-center gap-2"> 
          <i data-lucide="cog" class="h-5 w-5 module-icon-admin"></i>
          <span class="text-sm font-bold uppercase tracking-wider">System Admin</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="admin"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="admin">
        @if($hasRole('User Account'))
        <a href="{{route('users.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('users.index') ? 'active' : '' }}">
          <i data-lucide="user-cog" class="h-4 w-4"></i>
          <span>User Account</span>
        </a>
        @endif

        <!-- New menu items for departments and roles -->
        @if($hasRole('Departments'))
        <a href="{{ route('departments.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('departments.index') ? 'active' : '' }}">
          <i data-lucide="building" class="h-4 w-4"></i>
          <span>Departments</span>
        </a>
        @endif

        @if($hasRole('User Roles'))
        <a href="{{ route('user-roles.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('user-roles.index') ? 'active' : '' }}">
          <i data-lucide="shield" class="h-4 w-4"></i>
          <span>User Roles</span>
        </a>
        @endif
        
        @if($hasRole('System Settings'))
        <a href="/admin/system-settings" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="settings" class="h-4 w-4"></i>
          <span>System Settings</span>
        </a>
        @endif
      </div>
    </div>
    @endif
  </div>

  <!-- Sidebar Footer -->
  <div class="sidebar-footer border-t border-gray-200 p-4">
    <div class="flex items-center gap-3">
      <div class="relative">
        <div class="h-10 w-10 rounded-full border-2 border-blue-600 cursor-pointer hover:scale-105 transition-transform overflow-hidden">
          <img src="https://img.freepik.com/free-vector/blue-circle-with-white-user_78370-4707.jpg?semt=ais_hybrid&w=740" alt="User" class="h-full w-full object-cover" />
        </div>
      </div>
      <div class="flex flex-col">
        @if(strtolower(trim(auth()->user()->email)) =='ict_director@klas.com.ng')
          <span class="text-sm font-medium">Supper Admin</span>
        @else
          <span class="text-sm font-medium">User</span>
        @endif
        <span class="text-xs text-gray-500">{{ auth()->user()->email }}</span>
      </div>
      <div class="relative ml-auto">
        <button class="p-1.5 rounded-md hover:bg-gray-100" id="userMenuButton">
          <i data-lucide="settings" class="h-4 w-4"></i>
        </button>
        <div class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 hidden" id="userMenu">
          <div class="py-1">
            <div class="px-4 py-2 text-sm font-medium border-b border-gray-100">My Account</div>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              <div class="flex items-center">
                <i data-lucide="user-circle" class="mr-2 h-4 w-4"></i>
                <span>Profile</span>
              </div>
            </a>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              <div class="flex items-center">
                <i data-lucide="settings" class="mr-2 h-4 w-4"></i>
                <span>Settings</span>
              </div>
            </a>
            <div class="border-t border-gray-100"></div>
            <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
              <div class="flex items-center">
                <i data-lucide="lock" class="mr-2 h-4 w-4"></i>
                <span>Logout</span>
              </div>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Loading Spinner Overlay -->
 
</div>

<style>
/* Enhanced active item styling */
.sidebar-item.active {
  background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
  color: #1e40af;
  font-weight: 500;
  box-shadow: 0 1px 3px rgba(59, 130, 246, 0.1);
  border-left: 2px solid #3b82f6;
  border-radius: 6px;
  margin: 2px 8px;
}

/* Alternative option - even more subtle */
.sidebar-item.active.subtle {
  background: rgba(59, 130, 246, 0.08);
  color: #1e40af;
  font-weight: 500;
  box-shadow: none;
  border-left: 3px solid #3b82f6;
  border-radius: 4px;
}

/* Hover state for better interaction */
.sidebar-item.active:hover {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.15);
}

.sidebar-item.active i {
  color: white !important;
}

/* Smooth scrolling for sidebar */
.sidebar-content {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

.sidebar-content::-webkit-scrollbar {
  width: 6px;
}

.sidebar-content::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Hover effects for better UX */
.sidebar-item:hover {
  background-color: #f8fafc;
  transform: translateX(2px);
}

.sidebar-item.active:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
  transform: translateX(0);
}

/* Animation for expanding sections */
[data-content] {
  transition: all 0.3s ease;
}

/* Highlight animation keyframes */
@keyframes activeHighlight {
  0% { 
    transform: scale(1);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
  }
  50% { 
    transform: scale(1.02);
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.4);
  }
  100% { 
    transform: scale(1);
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
  }
}

.sidebar-item.active.highlight {
  animation: activeHighlight 0.6s ease-in-out;
}
</style>

<script>
  // Initialize Lucide icons
  lucide.createIcons();
  
  // Function to scroll sidebar to active item
  function scrollToActiveItem() {
    const activeItem = document.querySelector('.sidebar-item.active');
    const sidebarContent = document.querySelector('.sidebar-content');
    
    if (activeItem && sidebarContent) {
      // Calculate the position of the active item relative to the sidebar content
      const activeItemRect = activeItem.getBoundingClientRect();
      const sidebarRect = sidebarContent.getBoundingClientRect();
      
      // Calculate the offset from the top of the sidebar content
      const offsetTop = activeItem.offsetTop;
      const sidebarHeight = sidebarContent.clientHeight;
      const itemHeight = activeItem.offsetHeight;
      
      // Calculate the ideal scroll position to center the active item
      const idealScrollTop = offsetTop - (sidebarHeight / 2) + (itemHeight / 2);
      
      // Smooth scroll to the active item
      sidebarContent.scrollTo({
        top: Math.max(0, idealScrollTop),
        behavior: 'smooth'
      });
      
      // Add a subtle highlight animation to the active item
      activeItem.style.transition = 'all 0.3s ease';
      activeItem.style.transform = 'scale(1.02)';
      activeItem.style.boxShadow = '0 2px 8px rgba(59, 130, 246, 0.3)';
      
      // Remove the highlight after animation
      setTimeout(() => {
        activeItem.style.transform = '';
        activeItem.style.boxShadow = '';
      }, 1000);
    }
  }
  
  // Toggle modules and sections
  document.addEventListener('DOMContentLoaded', function() {
    // Auto-expand menu sections with active items
    const activeItems = document.querySelectorAll('.sidebar-item.active');
    
    activeItems.forEach(item => {
      // Find parent sections and modules
      let parent = item.closest('[data-content]');
      if (parent) {
        // Show this content section
        parent.classList.remove('hidden');
        
        // Rotate the chevron
        const sectionName = parent.getAttribute('data-content');
        const chevron = document.querySelector(`[data-chevron="${sectionName}"]`);
        if (chevron) {
          chevron.classList.add('rotate-90');
        }
        
        // Now check if this section is inside another section
        const grandParent = parent.parentElement.closest('[data-content]');
        if (grandParent) {
          grandParent.classList.remove('hidden');
          
          const parentSectionName = grandParent.getAttribute('data-content');
          const parentChevron = document.querySelector(`[data-chevron="${parentSectionName}"]`);
          if (parentChevron) {
            parentChevron.classList.add('rotate-90');
          }
        }
      }
    });
    
    // Set dashboard as open by default if no active items
    if (activeItems.length === 0) {
      toggleModule('dashboard');
    }
    
    // Scroll to active item after DOM is fully loaded and sections are expanded
    setTimeout(() => {
      scrollToActiveItem();
    }, 100);
    
    // Module toggle handlers
    const moduleHeaders = document.querySelectorAll('[data-module]');
    moduleHeaders.forEach(header => {
      header.addEventListener('click', function() {
        const moduleName = this.getAttribute('data-module');
        toggleModule(moduleName);
      });
    });
    
    // Section toggle handlers
    const sectionHeaders = document.querySelectorAll('[data-section]');
    sectionHeaders.forEach(header => {
      header.addEventListener('click', function(e) {
        e.stopPropagation();
        const sectionName = this.getAttribute('data-section');
        toggleSection(sectionName);
      });
    });
    
    // Add click handlers to sidebar items for dynamic scrolling
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
      item.addEventListener('click', function(e) {
        // Remove active class from all items
        sidebarItems.forEach(i => i.classList.remove('active'));
        // Add active class to clicked item
        this.classList.add('active');
        
        // Scroll to the newly active item after a short delay
        setTimeout(() => {
          scrollToActiveItem();
        }, 50);
      });
    });
    
    // User menu toggle
    const userMenuButton = document.getElementById('userMenuButton');
    const userMenu = document.getElementById('userMenu');
    
    userMenuButton.addEventListener('click', function() {
      userMenu.classList.toggle('hidden');
    });
    
    // Close user menu when clicking outside
    document.addEventListener('click', function(e) {
      if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
        userMenu.classList.add('hidden');
      }
    });
    
    // Handle window resize to maintain active item visibility
    window.addEventListener('resize', function() {
      setTimeout(() => {
        scrollToActiveItem();
      }, 100);
    });
  });
  
  function toggleModule(moduleName) {
    const content = document.querySelector(`[data-content="${moduleName}"]`);
    const chevron = document.querySelector(`[data-chevron="${moduleName}"]`);
    
    if (content.classList.contains('hidden')) {
      content.classList.remove('hidden');
      chevron.classList.add('rotate-90');
    } else {
      content.classList.add('hidden');
      chevron.classList.remove('rotate-90');
    }
  }
  
  function toggleSection(sectionName) {
    const content = document.querySelector(`[data-content="${sectionName}"]`);
    const chevron = document.querySelector(`[data-chevron="${sectionName}"]`);
    
    if (content.classList.contains('hidden')) {
      content.classList.remove('hidden');
      chevron.classList.add('rotate-90');
    } else {
      content.classList.add('hidden');
      chevron.classList.remove('rotate-90');
    }
  }
 
  
  function toggleSection(sectionName) {
    const content = document.querySelector(`[data-content="${sectionName}"]`);
    const chevron = document.querySelector(`[data-chevron="${sectionName}"]`);
    
    if (content.classList.contains('hidden')) {
      content.classList.remove('hidden');
      chevron.classList.add('rotate-90');
    } else {
      content.classList.add('hidden');
      chevron.classList.remove('rotate-90');
    }
  }
 
   
</script>

