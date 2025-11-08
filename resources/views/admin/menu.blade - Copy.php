@php
    $userRoles = array_map('trim', explode(',', auth()->user()->assign_role?? ''));
    $hasRole = function($role) use ($userRoles) {
        // normalize helper
        $normalize = function($s) {
            $s = trim((string)$s);
            $s = str_replace(['–','—'], '-', $s); // en/em dash -> hyphen
            $s = preg_replace('/\s*-\s*/u', ' - ', $s); // normalize spaces around hyphen
            $s = preg_replace('/\s+/u', ' ', $s);       // collapse spaces
            return mb_strtolower($s, 'UTF-8');
        };

        // Super Admin (accept both spellings)
        foreach ($userRoles as $r) {
            $nr = $normalize($r);
            if ($nr === 'supper admin' || $nr === 'super admin') {
                return true;
            }
        }

        $target = $normalize($role);
        foreach ($userRoles as $userRole) {
            if ($normalize($userRole) === $target) {
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
  <div class="sidebar-content p-2 overflow-y-auto max-h-[calc(100vh-8rem)] scroll-smooth scrollbar-visible">
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

    <!-- 1. Customer Relationship Management -->
    @if(
      $hasRole('CRM - Person') || $hasRole('CRM - Corporate') || $hasRole('CRM - Customer Manager')
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
      @if($hasRole('CRM - Person'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="person">
        <div class="flex items-center gap-2">
        <i data-lucide="users" class="h-4 w-4 text-green-500"></i>
        <span>Person</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="person"></i>
      </div>
      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="person">
        <a href="/person/individual" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="user" class="h-4 w-4 text-green-500"></i>
        <span>Individual</span>
        </a>
        <a href="/person/group" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="users" class="h-4 w-4 text-green-500"></i>
        <span>Group</span>
        </a>
        <a href="/person/family" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="users-2" class="h-4 w-4 text-green-500"></i>
        <span>Family</span>
        </a>
      </div>
      @endif
      @if($hasRole('CRM - Corporate'))
      <a href="/person/corporate" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="building" class="h-4 w-4 text-green-500"></i>
        <span>Corporate</span>
      </a>
      @endif
      @if($hasRole('CRM - Customer Manager'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="customerManager">
        <div class="flex items-center gap-2">
        <i data-lucide="calendar-clock" class="h-4 w-4 text-green-500"></i>
        <span>Customer Manager</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="customerManager"></i>
      </div>
      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="customerManager">
        @if($hasRole('Appointment'))
        <a href="/appointment" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="calendar" class="h-4 w-4 text-green-500"></i>
        <span>Appointment</span>
        </a>
        @endif
        @if($hasRole('Appointment Calendar'))
        <a href="/appointment/calendar" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="calendar-days" class="h-4 w-4 text-green-500"></i>
        <span>Appointment Calendar</span>
        </a>
        @endif
      </div>
      @endif
      </div>
    </div>
    @endif


      @if($hasRole('Indexing') || $hasRole('Print File Labels') || $hasRole('Print Sign In & Out Sheet') || $hasRole('Blind Scanning') || $hasRole('Scanning') || $hasRole('Document Page Types') || $hasRole('PT Quality Control') || $hasRole('EDMS Update'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="edms">
      <div class="flex items-center gap-2">
      <i data-lucide="database" class="h-5 w-5 text-emerald-600"></i>
      <span class="text-sm font-bold uppercase tracking-wider">EDMS</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="edms"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="edms">
      <!-- a. Indexing -->
      @if($hasRole('Indexing'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="indexing">
      <div class="flex items-center gap-2">
      <i data-lucide="file-search" class="h-4 w-4 text-emerald-500"></i>
      <span>Indexing</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="indexing"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="indexing">
      <a href="{{route('fileindexing.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('fileindexing.index') ? 'active' : '' }}">
      <i data-lucide="file-search" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>File Indexing Assistant</span>
      </a>
      </div>
      @endif

      <!-- b. Print File Labels -->
      @if($hasRole('Print File Labels'))
      <a href="{{route('printlabel.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('printlabel.index') ? 'active' : '' }}">
      <i data-lucide="printer" class="h-4 w-4 text-emerald-500"></i>
      <span>Print File Labels</span>
      </a>
      @endif

      <!-- c. Print Sign In & Out Sheet -->
      @if($hasRole('Print Sign In & Out Sheet'))
      <a href="{{ route('fileindexing.signin') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('fileindexing.signin') ? 'active' : '' }}">
      <i data-lucide="clipboard-list" class="h-4 w-4 text-emerald-500"></i>
      <span>Print Sign In & Out Sheet</span>
      </a>
      @endif

      <!-- d. Blind Scanning -->
      @if($hasRole('Blind Scanning'))
      <a href="{{route('blind-scanning.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="eye-off" class="h-4 w-4 text-emerald-500"></i>
      <span>Blind Scanning</span>
      </a>
      @endif

      <!-- e. Scanning -->
      @if($hasRole('Scanning'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="scanning">
      <div class="flex items-center gap-2">
      <i data-lucide="scan" class="h-4 w-4 text-emerald-500"></i>
      <span>Scanning</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="scanning"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="scanning">
      <a href="{{route('scanning.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('scanning.index') ? 'active' : '' }}">
      <i data-lucide="upload" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>Upload Indexed Files</span>
      </a>

      <a href="http://klaes.com.ng/scanning/unindexed" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="upload" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>Upload Unindexed Files</span>
      </a>
      </div>
      @endif
      
      <!-- f. Document Page Types -->
      @if($hasRole('Document Page Types'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="documentPageTypes">
      <div class="flex items-center gap-2">
      <i data-lucide="file-type" class="h-4 w-4 text-emerald-500"></i>
      <span>Document Page Types</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="documentPageTypes"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="documentPageTypes">
      <a href="{{route('pagetyping.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('pagetyping.index') ? 'active' : '' }}">
      <i data-lucide="type" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>PageTyping</span>
      </a>
      </div>
      @endif



      <!-- g. PT Quality Control -->
      @if($hasRole('PT Quality Control'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('ptq-control.index') ? 'active' : '' }}">
      <i data-lucide="shield-check" class="h-4 w-4 text-emerald-500"></i>
      <span>PT Quality Control</span>
      </a>
      @endif


            <!-- h. EDMS Update -->
      @if($hasRole('EDMS Update'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="edmsUpdate">
      <div class="flex items-center gap-2">
      <i data-lucide="refresh-cw" class="h-4 w-4 text-emerald-500"></i>
      <span>EDMS Update</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="edmsUpdate"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="edmsUpdate">
      <a href="/scanning?url=scmore" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="scan-line" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>Scan More</span>
      </a>

      <a href="/pagetyping?url=ptmore" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
      <i data-lucide="file-text" class="h-3.5 w-3.5 text-emerald-400"></i>
      <span>Type More</span>
      </a>
      </div>
      @endif
      </div>
    </div>
    @endif
    <!-- 2. Digital Files -->
    @if(
      $hasRole('Create a File Tracker') || $hasRole('File Tracker/Tracking - RFID') || $hasRole('File Digital Library - Doc-WARE')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="digitalFiles">
        <div class="flex items-center gap-2">
          <i data-lucide="folder" class="h-5 w-5 text-blue-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Digital Files</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="digitalFiles"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="digitalFiles">
        @if($hasRole('Create a File Tracker'))
        <a href="{{ route('create-file-tracker.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('create-file-tracker.index') ? 'active' : '' }}">
          <i data-lucide="file-plus" class="h-4 w-4 text-blue-500"></i>
          <span>Log a File</span>
        </a>
        @endif

        @if($hasRole('File Tracker/Tracking - RFID'))
        <a href="{{ route('filetracker.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('filetracker.index') ? 'active' : '' }}">
          <i data-lucide="radio-tower" class="h-4 w-4 text-blue-500"></i>
          <span>File Tracker/Tracking</span>
        </a>
        @endif

        @if($hasRole('File Digital Library - Doc-WARE'))
        <a href="{{ route('filearchive.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('filearchive.index') ? 'active' : '' }}">
          <i data-lucide="archive" class="h-4 w-4 text-blue-500"></i>
          <span>File Digital Library – Doc-WARE</span>
        </a>
        @endif
      </div>
    </div>
    @endif

    <!-- 3. EDMS -->


    <!-- 4. Programmes -->
    @if(
      $hasRole('Allocation') || $hasRole('Compensation/Resettlement') || 
      $hasRole('Recertification - Application') || $hasRole('Recertification - Bills & Payments') || 
      $hasRole('Recertification - Migrate Data') || $hasRole('Recertification - Verification Sheet') || 
      $hasRole('GIS - Data Capture') || $hasRole('Recertification - Vetting Sheet') ||
      $hasRole('Recertification - EDMS') || $hasRole('Recertification - Certification') || 
      $hasRole("Recertification - DG's List") || $hasRole('Recertification - Governors List') || 
      $hasRole('Conversion/Regularization') || $hasRole('Land Property Enumeration - Data Repository') || 
      $hasRole('Land Property Enumeration - Migrate Data')
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
      <!-- Allocation Section -->
      @if($hasRole('Allocation'))
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

      <!-- Compensation/Resettlement Section -->
      @if($hasRole('Compensation/Resettlement'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="resettlement">
        <div class="flex items-center gap-2">
          <i data-lucide="home" class="h-4 w-4 text-purple-500"></i>
          <span>Resettlement</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="resettlement"></i>
      </div>
      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="resettlement">
        <a href="/programmes/resettlement/governors-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="list" class="h-3.5 w-3.5 text-purple-400"></i>
          <span>Governors List</span>
        </a>
        <a href="/programmes/resettlement/commissioners-list" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="list-checks" class="h-3.5 w-3.5 text-purple-400"></i>
          <span>Commissioners List</span>
        </a>
      </div>
      @endif

      <!-- Recertification Section --> 
      @if($hasRole('Recertification - Application') || $hasRole('Recertification - Bills & Payments') || 
          $hasRole('Recertification - Migrate Data') || $hasRole('Recertification - Verification Sheet') || 
          $hasRole('GIS - Data Capture') || $hasRole('Recertification - Vetting Sheet') ||
          $hasRole('Recertification - EDMS') || $hasRole('Recertification - Certification') || 
          $hasRole("Recertification - DG's List") || $hasRole('Recertification - Governors List'))
  <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="recertification">
    <div class="flex items-center gap-2">
      <i data-lucide="file-cog" class="h-4 w-4 text-purple-500"></i>
      <span>Recertification</span>
    </div>
    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="recertification"></i>
  </div>

  <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="recertification">
    <a href="{{ route('recertification.index') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.index') ? 'active' : '' }}">
      <i data-lucide="file-plus" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Application</span>
    </a>
  
    <a href="{{ route('recertification.bills-payments') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.bills-payments') ? 'active' : '' }}">
      <i data-lucide="dollar-sign" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Bills & Payments</span>
    </a>

    <a href="{{ route('recertification.migrate') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.migrate') ? 'active' : '' }}">
      <i data-lucide="database-backup" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Application Migrate Data</span>
    </a>



    <a href="{{ route('recertification.verification-sheet') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.verification-sheet') ? 'active' : '' }}">
      <i data-lucide="check-square" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Verification Sheet</span>
    </a>

    <a href="{{ route('recertification.gis-data-capture') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.gis-data-capture') ? 'active' : '' }}">
      <i data-lucide="map" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>GIS Data Capture</span>
    </a>

    <a href="{{ route('recertification.vetting-sheet') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.vetting-sheet') ? 'active' : '' }}">
      <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Vetting Sheet</span>
    </a>

    <a href="{{ route('recertification.edms') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.edms') ? 'active' : '' }}">
      <i data-lucide="hard-drive" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>EDMS</span>
    </a>

    <a href="{{ route('recertification.certification') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.certification') ? 'active' : '' }}">
      <i data-lucide="award" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Certification</span>
    </a>

    <a href="{{ route('recertification.dg-list') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.dg-list') ? 'active' : '' }}">
      <i data-lucide="list-end" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>DG's List</span>
    </a>

    <a href="{{ route('recertification.governors-list') }}" 
       class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('recertification.governors-list') ? 'active' : '' }}">
      <i data-lucide="list" class="h-3.5 w-3.5 text-purple-400"></i>
      <span>Governors List</span>
    </a>
  </div>
@endif


      <!-- Conversion/Regularization Section -->
      @if($hasRole('Conversion/Regularization'))
    <a href="/programmes/regularization" 
       class="sidebar-item flex items-center gap-2 py-2 rounded-md transition-all duration-200">
        <i data-lucide="arrow-left-right" class="h-4 w-4 text-purple-500"></i>
        <span class="truncate">Conversion/Regularization</span>
    </a>
@endif

      <!-- Land Property Enumeration Section -->
      @if($hasRole('Land Property Enumeration - Data Repository') || $hasRole('Land Property Enumeration - Migrate Data'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="enumeration">
        <div class="flex items-center gap-2">
        <i data-lucide="clipboard-list" class="h-4 w-4 text-purple-500"></i>
        <span>Land Property Enumeration</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="enumeration"></i>
      </div>

      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="enumeration">
        @if($hasRole('Land Property Enumeration - Data Repository'))
        <a href="/programmes/enumeration/data-repository" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="database" class="h-3.5 w-3.5 text-purple-400"></i>
        <span>Data Repository</span>
        </a>
        @endif
        @if($hasRole('Land Property Enumeration - Migrate Data'))
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
      $hasRole('Letter of Grant/RofO') || $hasRole('Occupancy Permit (OP)') || $hasRole('Site Plan/Parcel Plan') ||
      $hasRole('Change of Purpose') || $hasRole('Certificate of Occupancy')
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
        @if($hasRole('Letter of Grant/RofO'))
        <a href="/documents/letter-of-administration" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-plus" class="h-4 w-4 text-indigo-500"></i>
          <span class="text-sm">Letter of Grant/RofO</span>
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
        @if($hasRole('Change of Purpose'))
        <a href="/documents/change-of-purpose" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="repeat" class="h-4 w-4 text-indigo-500"></i>
          <span>Change of Purpose</span>
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
      $hasRole('Deeds - Instrument Registration (New Registration)') || $hasRole('Deeds - Instrument Registration Reports') || $hasRole('Encumbrance') || $hasRole('Caveat')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="deeds">
      <div class="flex items-center gap-2"> 
        <i data-lucide="book-open" class="h-6 w-6 text-teal-600"></i>
        <span class="text-sm font-bold uppercase tracking-wider">Deeds</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="deeds"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="deeds">
      @if($hasRole('Deeds - Property Records Assistant (Legacy Records)'))
      <a href="{{route('propertycard.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('propertycard.index') ? 'active' : '' }}">
        <i data-lucide="sparkles" class="h-4 w-4 text-teal-500"></i>
        <span>Property Records Assistant (Legacy Records)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Capture (New Records)'))
      <a href="{{route('instruments.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('instruments.index') ? 'active' : '' }}">
        <i data-lucide="file-input" class="h-4 w-4 text-teal-500"></i>
        <span>Instrument Capture (New Records)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Registration (New Registration)'))
      <a href="{{route('instrument_registration.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('instrument_registration.index') ? 'active' : '' }}">
        <i data-lucide="book-open" class="h-4 w-4 text-teal-500"></i>
        <span>Instrument Registration (New Registration)</span>
      </a>
      @endif
      @if($hasRole('Deeds - Instrument Registration Reports'))
      <a href="/instrument-registration-reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="file-bar-chart" class="h-4 w-4 text-teal-500"></i>
        <span>Instrument Registration Reports</span>
      </a>
      @endif

      @if($hasRole('Encumbrance') || $hasRole('Caveat'))
      <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="encumbrance">
        <div class="flex items-center gap-2">
          <i data-lucide="shield" class="h-4 w-4 text-teal-500"></i>
          <span>Encumbrance</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="encumbrance"></i>
      </div>
      <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="encumbrance">
        @if($hasRole('Caveat'))
        <a href="{{route('caveat.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('caveat.index') ? 'active' : '' }}">
          <i data-lucide="shield-alert" class="h-3.5 w-3.5 text-teal-400"></i>
          <span>Caveat</span>
        </a>
        @endif
      </div>
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
        <a href="/legal-search/online" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="globe" class="h-3.5 w-3.5 text-cyan-400"></i>
          <span>Online</span>
        </a>
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
  $hasRole('Lands - File Tracker/Tracking - RFID') || 
  $hasRole('Lands - File Digital Archive – Doc-WARE') || 
  $hasRole('Lands - Generate New FileNo (MLSFileNo)') ||
  $hasRole('Lands - Capture an Existing File') ||
  $hasRole('Lands - File Decommissioning')
)
<div class="py-1 px-3 mb-0.5 border-t border-slate-100">
  <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="lands">
  <div class="flex items-center gap-2">
    <i data-lucide="landmark" class="h-5 w-5 text-orange-600"></i>
    <span class="text-sm font-bold uppercase tracking-wider">Lands</span>
  </div>
  <i data-lucide="chevron-right" class="h-4 w-4 text-black transition-transform duration-200" data-chevron="lands"></i>
  </div>

  <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="lands">
  <!-- Generate New FileNo (MLSFileNo) -->
  @if($hasRole('Lands - Generate New FileNo (MLSFileNo)') || $hasRole('Supper Admin'))
  <a href="{{route('file-numbers.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('file-numbers.index') ? 'active' : '' }}">
    <i data-lucide="hash" class="h-4 w-4 text-orange-500"></i>
    <span>Generate New FileNo (MLSFileNo)</span>
  </a>
  @endif


    
  <!--Capture an Existing FileNo -->
  @if($hasRole('Lands - Capture an Existing File') || $hasRole('Supper Admin'))
  <a href="{{route('existing-file-numbers.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('existing-file-numbers.index') ? 'active' : '' }}">
    <i data-lucide="folder-plus" class="h-4 w-4 text-orange-500"></i>
    <span>Capture an Existing FileNo</span>
  </a>

 
   @endif
{{-- c.	Manage MLSFileNo --}}
  <a href="{{route('mls-fileno.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('mls-fileno.index') ? 'active' : '' }}">
    <i data-lucide="folder-plus" class="h-4 w-4 text-orange-500"></i>
    <span>Manage MLSFileNo</span>
  </a>


     @if($hasRole('Lands - File Decommissioning') || $hasRole('Supper Admin'))
 

  <a href="{{route('file-decommissioning.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200  {{ request()->routeIs('file-decommissioning.index') ? 'active' : '' }}">
    <i data-lucide="folder-plus" class="h-4 w-4 text-orange-500"></i>
    <span>File Decommissioning </span>
  </a>
   @endif

  </div>
</div>
@endif

    <!-- 8. Physical Planning -->
    @if(
      $hasRole('PHYSICAL PLANNING') || 
      $hasRole('PP - Regular Applications') || 
      $hasRole('Planning Recommendation') || 
      $hasRole('ST eRegistry') || 
      $hasRole('ST Applications for Memo') || 
      $hasRole('Bills & Payments') || 
      $hasRole('PP Director') || 
      $hasRole('PP - SLTR Applications') || 
      $hasRole('PP Reports')
    )
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="physicalPlanning">
        <div class="flex items-center gap-2">
          <i data-lucide="ruler" class="h-5 w-5 text-red-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Physical Planning</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="physicalPlanning"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="physicalPlanning">
        @if($hasRole('PP - Regular Applications') || $hasRole('Planning Recommendation'))
        <!-- a. Regular Applications -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="regularApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-red-500"></i>
            <span>Regular Applications</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="regularApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="regularApplications">
          @if($hasRole('Planning Recommendation'))
          <a href="/physical-planning/regular/planning-recommendation" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-red-400"></i>
            <span>Planning Recommendation</span>
          </a>
          @endif

          @if($hasRole('PP - Regular Applications'))
          <a href="/physical-planning/regular/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-red-400"></i>
            <span>Memo</span>
          </a>
          @endif
        </div>
        @endif
        
        @if($hasRole('Planning Recommendation') || $hasRole('ST Applications for Memo') || $hasRole('Bills & Payments') || $hasRole('ST eRegistry'))
        <!-- b. ST One Stop Shop -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-red-500"></i>
            <span>ST One Stop Shop</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stApplications">
          @if($hasRole('Planning Recommendation'))
          <!-- i. Planning Recommendation -->
          <a href="{{ route('programmes.approvals.planning_recomm') }}?url=view"
             class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.approvals.planning_recomm') && request()->query('url') === 'view' ? 'active' : '' }}">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-red-400"></i>
            <span>Planning Recommendation</span>
          </a>
          @endif

          @if($hasRole('ST Applications for Memo'))
          <!-- ii. ST Applications for Memo -->
          <a href="{{ route('stmemo.siteplan') }}?url=applications"
             class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('stmemo.siteplan') && request()->query('url') === 'applications' ? 'active' : '' }}">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-red-400"></i>
            <span>ST Applications for Memo</span>
          </a>
          @endif

          @if($hasRole('Bills & Payments'))
          <!-- iii. Bills & Payments -->
          <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stBillsPayments">
            <div class="flex items-center gap-2">
              <i data-lucide="credit-card" class="h-4 w-4 text-red-500"></i>
              <span>Bills & Payments</span>
            </div>
            <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stBillsPayments"></i>
          </div>

          <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stBillsPayments">
            <!-- 1. Bills -->
            <a href="{{ route('programmes.bills') }}?url=physical_planning"
               class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.bills') && request()->query('url') === 'physical_planning' ? 'active' : '' }}">
              <i data-lucide="credit-card" class="h-3.5 w-3.5 text-red-400"></i>
              <span>Bills</span>
            </a>

            <!-- 2. Payments -->
            <a href="{{ route('programmes.payments') }}?url=physical_planning"
               class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payments') && request()->query('url') === 'physical_planning' ? 'active' : '' }}">
              <i data-lucide="banknote" class="h-3.5 w-3.5 text-red-400"></i>
              <span>Payments</span>
            </a>

            <!-- 3. Payment Reports -->
            <a href="{{ route('programmes.payments') }}?url=report"
               class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payment-history') && request()->query('url') === 'report' ? 'active' : '' }}">
              <i data-lucide="history" class="h-3.5 w-3.5 text-red-400"></i>
              <span>Payment Reports</span>
            </a>
          </div>
          @endif

          @if($hasRole('ST eRegistry'))
          <!-- iv. ST eRegistry -->
          <a href="{{ route('programmes.eRegistry') }}?url=pp"
             class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.eRegistry') && request()->query('url') === 'pp' ? 'active' : '' }}">
            <i data-lucide="folder" class="h-3.5 w-3.5 text-red-400"></i>
            <span>ST eRegistry</span>
          </a>
          @endif
        </div>
        @endif
        
        @if($hasRole('PP - SLTR Applications') || $hasRole('Planning Recommendation'))
        <!-- c. SLTR Applications -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrApplications">
          <div class="flex items-center gap-2">
            <i data-lucide="clipboard-list" class="h-4 w-4 text-red-500"></i>
            <span>SLTR Applications</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrApplications"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrApplications">
          @if($hasRole('Planning Recommendation'))
          <a href="/physical-planning/sltr/planning-recommendation" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-red-400"></i>
            <span>Planning Recommendation</span>
          </a>
          @endif
          
          @if($hasRole('PP - SLTR Applications'))
          <a href="/physical-planning/sltr/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-red-400"></i>
            <span>Memo</span>
          </a>
          @endif
        </div>
        @endif
        
        @if($hasRole('PP Director'))
        <!-- d. PP Director -->
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="ppDirector">
          <div class="flex items-center gap-2">
            <i data-lucide="user-check" class="h-4 w-4 text-red-500"></i>
            <span>PP Director</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="ppDirector"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="ppDirector">
          <!-- i. Sectional Titling -->
          <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="ppDirectorST">
            <div class="flex items-center gap-2">
              <i data-lucide="building" class="h-4 w-4 text-red-500"></i>
              <span>Sectional Titling</span>
            </div>
            <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="ppDirectorST"></i>
          </div>
          
          <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="ppDirectorST">
            <!-- 1. Planning Recommendation Approval -->
            <a href="{{ route('programmes.approvals.planning_recomm') }}?url=approval"
               class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.approvals.planning_recomm') && request()->query('url') === 'approval' ? 'active' : '' }}">
              <i data-lucide="check-circle" class="h-3.5 w-3.5 text-red-400"></i>
              <span>Planning Recommendation Approval</span>
            </a>

            <!-- 2. Physical Planning Memo -->
            <a href="{{ route('stmemo.siteplan') }}?url=memo&view"
               class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('stmemo.siteplan') && request()->query('url') === 'memo' ? 'active' : '' }}">
              <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-red-400"></i>
              <span>Physical Planning Memo</span>
            </a>
          </div>
        </div>
        @endif
        
        <!-- e. PP Reports -->
        @if($hasRole('PP Reports'))
        <a href="/physical-planning/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4 text-red-500"></i>
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
          <i data-lucide="compass" class="h-5 w-5 text-pink-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Survey</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="survey"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="survey">
        @if($hasRole('Survey - Records'))
        <a href="{{route('survey_record.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('survey_record.index') ? 'active' : '' }}">
          <i data-lucide="clipboard" class="h-4 w-4 text-pink-500"></i>
          <span>Records</span>
        </a>
        @endif
        @if($hasRole('Survey - GIS'))
        <a href="/survey/gis" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="map" class="h-4 w-4 text-pink-500"></i>
          <span>GIS</span>
        </a>
        @endif
        @if($hasRole('Survey - Approvals'))
        <a href="/survey/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="check-circle" class="h-4 w-4 text-pink-500"></i>
          <span>Approvals</span>
        </a>
        @endif
        @if($hasRole('Survey - E-Registry'))
        <a href="/survey/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4 text-pink-500"></i>
          <span>E-Registry</span>
        </a>
        @endif

        @if($hasRole('Survey Reports'))
        <a href="{{ route('survey_plan_extraction.index') }}?url=survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200  {{ request()->routeIs('survey_plan_extraction.index') && request()->query('url') === 'survey' ? 'active' : '' }}">
          <i data-lucide="sparkles" class="h-4 w-4 text-pink-500"></i>
          <span>Survey Plan Extraction</span>
        </a>
        @endif  
        
        @if($hasRole('Survey Reports'))
        <a href="/survey/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4 text-pink-500"></i>
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
          <i data-lucide="map" class="h-5 w-5 text-rose-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Cadastral</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="cadastral"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="cadastral">
        @if($hasRole('Cad - Records'))
        <a href="{{route('survey_cadastral.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('survey_cadastral.index') ? 'active' : '' }}">
          <i data-lucide="clipboard" class="h-4 w-4 text-rose-500"></i>
          <span>Records</span>
        </a>
        @endif
        @if($hasRole('Cad - GIS'))
        <a href="/cadastral/gis" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="map" class="h-4 w-4 text-rose-500"></i>
          <span>GIS</span>
        </a>
        @endif
        @if($hasRole('Cad - Approvals'))
        <a href="/cadastral/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="check-circle" class="h-4 w-4 text-rose-500"></i>
          <span>Approvals</span>
        </a>
        @endif
        @if($hasRole('Cad - E-Registry'))
        <a href="/cadastral/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4 text-rose-500"></i>
          <span>E-Registry</span>
        </a>
        @endif
        @if($hasRole('Cadastral Reports'))
        <a href="{{ route('survey_plan_extraction.index') }}?url=survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200  {{ request()->routeIs('survey_plan_extraction.index') && request()->query('url') === 'survey' ? 'active' : '' }}">
          <i data-lucide="sparkles" class="h-4 w-4 text-rose-500"></i>
          <span>Survey Plan Extraction</span>
        </a>
        @endif 
        
        @if($hasRole('Cadastral Reports'))
        <a href="/cadastral/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4 text-rose-500"></i>
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
        <i data-lucide="map" class="h-5 w-5 text-yellow-600"></i>
        <span class="text-sm font-bold uppercase tracking-wider">GIS</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="gis"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="gis">
      @if($hasRole('GIS - Records'))
      <a href="{{route('gis_record.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('gis_record.index') ? 'active' : '' }}">
        <i data-lucide="clipboard" class="h-4 w-4 text-yellow-500"></i>
        <span>Records</span>
      </a>
      @endif
      @if($hasRole('GIS – AI Digital Assistant'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="bot" class="h-4 w-4 text-yellow-500"></i>
        <span>AI Digital Assistant</span>
      </a>
      @endif
      @if($hasRole('GIS - GIS'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="map" class="h-4 w-4 text-yellow-500"></i>
        <span>GIS</span>
      </a>
      @endif
      @if($hasRole('GIS Reports'))
      <a href="{{ route('survey_plan_extraction.index') }}?url=gis" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="sparkles" class="h-4 w-4 text-yellow-500"></i>
        <span>Survey Plan Extraction</span>
      </a>
      @endif
      @if($hasRole('GIS - Approvals'))
      <a href="/gis/approvals" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="check-circle" class="h-4 w-4 text-yellow-500"></i>
        <span>Approvals</span>
      </a>
      @endif
      @if($hasRole('GIS - e-Registry'))
      <a href="/gis/e-registry" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="database" class="h-4 w-4 text-yellow-500"></i>
        <span>E-Registry</span>
      </a>
      @endif
      @if($hasRole('GIS Reports'))
      <a href="#" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
        <i data-lucide="file-bar-chart" class="h-4 w-4 text-yellow-500"></i>
        <span>GIS Reports</span>
      </a>
      @endif
      </div>
    </div>
    @endif

  <!-- 12. Sectional Titling -->
 
@if(
  $hasRole('ST - Overview') || $hasRole('ST - Primary Application') || $hasRole('ST - Unit Application') ||
  $hasRole('ST - Applications') || $hasRole('ST - Field Data Integration') ||
  $hasRole('ST - Bills & Payments') ||
  $hasRole('ST - Approvals') || $hasRole('ST - Approvals (Other Departments)') ||
  $hasRole('ST - ST Memo') || $hasRole('ST - Certificate') || $hasRole('ST - e-Registry') ||
  $hasRole('ST - GIS') || $hasRole('ST - Survey') || $hasRole('ST - Reports') || $hasRole('ST - Sectional Titling BaseMap')
)
<div class="py-1 px-3 mb-0.5 border-t border-slate-100">
  <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="sectionalTitling">
    <div class="flex items-center gap-2">
      <i data-lucide="building-2" class="h-5 w-5 text-lime-400"></i>
      <span class="text-sm font-bold uppercase tracking-wider">Sectional Titling</span>
    </div>
    <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sectionalTitling"></i>
  </div>

  <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="sectionalTitling">
    @if($hasRole('ST - Overview'))
    <a href="{{ route('sectionaltitling.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.index') ? 'active' : '' }}">
      <i data-lucide="file-text" class="h-4 w-4 text-lime-500"></i>
      <span>Overview</span>
    </a>
    @endif

  @if($hasRole('New ST FileNo'))
  <a href="{{ route('st-file-numbers.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('st-file-numbers.index') ? 'active' : '' }}">
    <i data-lucide="file-plus-2" class="h-4 w-4 text-lime-500"></i>
    <span>New ST FileNo</span>
  </a>
  @endif

    <!-- a. Applications -->
    @if($hasRole('ST - Primary Application') || $hasRole('ST - Unit Application') || $hasRole('ST - Applications'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="applications">
      <div class="flex items-center gap-2">
        <i data-lucide="file-plus" class="h-4 w-4 text-lime-500"></i>
        <span>Applications</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="applications"></i>
    </div>
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="applications">
      @if($hasRole('ST - Primary Application') || $hasRole('ST - Applications'))
      <a href="{{ route('sectionaltitling.primary') }}?url=infopro" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.primary') && request()->query('url') === 'infopro' ? 'active' : '' }}">
        <i data-lucide="file-plus" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Primary Applications</span>
      </a>
      @endif
    <!-- Unit Applications -->
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="unitApplications">
      <div class="flex items-center gap-2">
        <i data-lucide="building" class="h-4 w-4 text-lime-500"></i>
        <span>Unit Applications</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="unitApplications"></i>
    </div>

    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="unitApplications">
      @if($hasRole('ST - Unit Application') || $hasRole('ST - Applications'))
        <a href="{{ route('sectionaltitling.units') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sectionaltitling.units') && !in_array(request()->query('url'), ['recommendation', 'phy_planning']) ? 'active' : '' }}">
          <i data-lucide="file-plus-2" class="h-3.5 w-3.5 text-lime-400"></i>
          <span>Parented Units</span>
        </a>

        <a href="{{ route('sua.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sua.index') ? 'active' : '' }}">
          <i data-lucide="building" class="h-3.5 w-3.5 text-lime-400"></i>
          <span>Standalone Unit</span>
        </a>
      @endif
    </div>
    @endif
    </div>

   

    @if($hasRole('ST - Field Data') || $hasRole('ST - Field Data Integration'))
    <a href="{{route('programmes.field-data')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.field-data') ? 'active' : '' }}">
      <i data-lucide="clipboard-list" class="h-4 w-4 text-lime-500"></i>
      <span>Field Data Integration</span>
    </a>
    @endif

    @if($hasRole('ST - Bills & Payments'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="stPayments">
      <div class="flex items-center gap-2">
        <i data-lucide="credit-card" class="h-4 w-4 text-lime-500"></i>
        <span>Bills & Payments</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="stPayments"></i>
    </div>
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stPayments">
      <a href="{{route('programmes.bills')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.bills') ? 'active' : '' }}">
        <i data-lucide="receipt" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Bills</span>
      </a>
<a href="{{route('programmes.payments')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payments') && !request()->query('url') ? 'active' : '' }}">
        <i data-lucide="credit-card" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Payments</span>
      </a>
      <a href="{{route('programmes.payments')}}?url=report" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('programmes.payments') && request()->query('url') === 'report' ? 'active' : '' }}">
        <i data-lucide="file-bar-chart" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Payments Report</span>
      </a>
 
    </div>
    @endif
    
    @if($hasRole('ST - Approvals') || $hasRole('ST - Approvals (Other Departments)'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="stApprovals">
      <div class="flex items-center gap-2">
      <i data-lucide="check-circle" class="h-4 w-4 text-lime-500"></i>
      <span>Approvals (Other Departments)</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="stApprovals"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stApprovals">
      <a href="{{route('st_deeds.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('st_deeds.index') ? 'active' : '' }}">
      <i data-lucide="file-check" class="h-3.5 w-3.5 text-lime-500"></i>
      <span>ST Deeds Registration View</span>
      </a>
      <a href="{{ route('programmes.approvals.planning_recomm', ['url' => 'view']) }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.approvals.planning_recomm') && request()->query('url') === 'view' ? 'active' : '' }}">
        <i data-lucide="ruler" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Planning Recommendation</span>
        </a>
      <a href="{{route('other_departments.survey_primary')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('other_departments.survey_primary') ? 'active' : '' }}">
      <i data-lucide="building-2" class="h-3.5 w-3.5 text-lime-500"></i>
      <span>Other Departments</span>
      </a>
      
     
      
   
    </div>
    @if($hasRole("ST - Director's Approval"))
    <a href="{{route('programmes.approvals.director')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.approvals.director') ? 'active' : '' }}">
      <i data-lucide="stamp" class="h-3.5 w-3.5 text-lime-500"></i>
      <span>Director's Approval</span>
    </a>
    @endif
    
    

    @if($hasRole('ST - ST Memo'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="stMemo">
      <div class="flex items-center gap-2">
        <i data-lucide="clipboard-list" class="h-4 w-4 text-lime-500"></i>
        <span>ST Memo</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="stMemo"></i>
    </div>

    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stMemo">
      <a href="{{route('programmes.memo')}}?type=primary" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.memo') && request()->query('type') === 'primary' ? 'active' : '' }}">
        <i data-lucide="file-text" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Primary</span>
      </a>
      
      <a href="{{ route('programmes.unit_scheme_memo') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.unit_scheme_memo') ? 'active' : '' }}">
        <i data-lucide="building" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Unit (Scheme)</span>
      </a>

      <a href="{{ route('programmes.unit_nonscheme_memo') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.unit_nonscheme_memo') ? 'active' : '' }}">
        <i data-lucide="building-2" class="h-3.5 w-3.5 text-lime-400"></i>
        <span>Unit (Non-Scheme)</span>
      </a>
    </div>
    @endif
   @if($hasRole('ST - Final Conveyance'))
      <a href="/sectionaltitling/conveyance" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->is('sectionaltitling/conveyance') ? 'active' : '' }}">
        <i data-lucide="clipboard-list" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Final Conveyance</span>
      </a>
    @endif
    @endif
 
    @if($hasRole('ST - Certificate'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="certificate">
      <div class="flex items-center gap-2">
        <i data-lucide="award" class="h-4 w-4 text-lime-500"></i>
        <span>Certificate</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="certificate"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="certificate">
      @if($hasRole('ST - Certificate'))
      <a href="{{route('programmes.rofo')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.rofo') ? 'active' : '' }}">
        <i data-lucide="folder" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>RofO</span>
      </a>
      @endif
      @if($hasRole('ST - Certificate'))
      <a href="{{route('programmes.certificates')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.certificates') ? 'active' : '' }}">
        <i data-lucide="file-cog" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>CofO</span>
      </a>
      @endif
    </div>
    @endif

    @if($hasRole('ST - e-Registry'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="eRegistry">
      <div class="flex items-center gap-2">
        <i data-lucide="database" class="h-4 w-4 text-lime-500"></i>
        <span>e-Registry</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="eRegistry"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="eRegistry">
      @if($hasRole('ST - e-Registry'))
      <a href="{{route('programmes.eRegistry')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.eRegistry') ? 'active' : '' }}">
        <i data-lucide="folder" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Files</span>
      </a>
            {{-- ii.	Print File Label --}}
      <a href="{{route('printlabel.index')}}?url=st" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('printlabel.index') ? 'active' : '' }}">
        <i data-lucide="printer" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Print File Label</span>
      </a>
      {{-- iii.	File Movement --}}
      @endif
    </div>
    @endif
        
    @if($hasRole('ST - Survey'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="stSurvey">
      <div class="flex items-center gap-2">
        <i data-lucide="land-plot" class="h-4 w-4 text-lime-500"></i>
        <span>Survey</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="stSurvey"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stSurvey">
      <a href="{{route('attribution.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('attribution.index') ? 'active' : '' }}">
        <i data-lucide="land-plot" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Attribution</span>
      </a>
    </div>
    @endif
    @if($hasRole('ST - GIS'))
    <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md text-black" data-section="stGis">
      <div class="flex items-center gap-2">
        <i data-lucide="map" class="h-4 w-4 text-lime-500"></i>
        <span>GIS</span>
      </div>
      <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200 text-black" data-chevron="stGis"></i>
    </div>
    
    <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="stGis">
   
      <a href="{{route('gis.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('gis.index') ? 'active' : '' }}">
        <i data-lucide="database" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Attribution</span>
      </a>
      
      @endif
    </div>
   

    @if($hasRole('ST - Reports') || $hasRole('ST - Sectional Titling BaseMap'))
    <a href="{{ route('map.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('map.index') ? 'active' : '' }}">
        <i data-lucide="map-pin" class="h-3.5 w-3.5 text-lime-500"></i>
        <span>Sectional Titling BaseMap</span>
      </a>
    @endif
    @if($hasRole('ST - Reports'))
    <a href="{{route('programmes.report')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 text-black {{ request()->routeIs('programmes.report') ? 'active' : '' }}">
      <i data-lucide="file-bar-chart" class="h-4 w-4 text-lime-500"></i>
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
          <i data-lucide="file-search" class="h-5 w-5 text-violet-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">SLTR/First Registration</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltr"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="sltr">
        @if($hasRole('SLTR - Overview'))
        <a href="{{route('sltroverview.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltroverview.index') ? 'active' : '' }}">
          <i data-lucide="file-text" class="h-4 w-4 text-violet-500"></i>
          <span>Overview</span>
        </a>
        @endif
        @if($hasRole('SLTR - Application'))
        <a href="{{route('sltrapplication.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrapplication.index') ? 'active' : '' }}">
          <i data-lucide="file-plus" class="h-4 w-4 text-violet-500"></i>
          <span>Application</span>
        </a>
        @endif
        @if($hasRole('SLTR - Claimants'))
        <a href="/programmes/sltr/claimants" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="users" class="h-4 w-4 text-violet-500"></i>
          <span>Claimants</span>
        </a>
        @endif
        @if($hasRole('SLTR - Legacy Data'))
        <a href="/programmes/sltr/legacy-data" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrlegacydata.index') ? 'active' : '' }}">
          <i data-lucide="history" class="h-4 w-4 text-violet-500"></i>
          <span>Legacy Data</span>
        </a>
        @endif
        @if($hasRole('SLTR - Field Data'))
        <a href="{{route('sltr_field_data.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="clipboard-list" class="h-4 w-4 text-violet-500"></i>
          <span>Field Data</span>
        </a>
        @endif
        @if($hasRole('SLTR - Payments'))
        <a href="/programmes/sltr/payments" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="credit-card" class="h-4 w-4 text-violet-500"></i>
          <span>Payments</span>
        </a>
        @endif
        
        @if($hasRole('SLTR - Approvals'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrApprovals">
          <div class="flex items-center gap-2">
            <i data-lucide="check-circle" class="h-4 w-4 text-violet-500"></i>
            <span>Approvals</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrApprovals"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrApprovals">
          <a href="/programmes/sltr/approvals/planning" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="clipboard-check" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Planning Recommendation</span>
          </a>
          <a href="/programmes/sltr/approvals/director" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="stamp" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Director SLTR</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Other Departments'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrDepartments">
          <div class="flex items-center gap-2">
            <i data-lucide="building-2" class="h-4 w-4 text-violet-500"></i>
            <span>Other Departments</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrDepartments"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrDepartments">
          <a href="/programmes/sltr/departments/lands" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Lands</span>
          </a>
          <a href="{{route('sltrapproval.deeds')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('sltrapproval.deeds') ? 'active' : '' }}">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Deeds</span>
          </a>
          <a href="/programmes/sltr/departments/survey" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Survey</span>
          </a>
          <a href="/programmes/sltr/departments/cadastral" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Cadastral</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Memo'))
        <a href="/programmes/sltr/memo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="clipboard-list" class="h-4 w-4 text-violet-500"></i>
          <span>SLTR Memo</span>
        </a>
        @endif        
        @if($hasRole('SLTR - Certificate') || $hasRole('SLTR - e-Registry') || $hasRole('SLTR - GIS') || $hasRole('SLTR - Survey') || $hasRole('SLTR - Reports'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrCertificate">
          <div class="flex items-center gap-2">
            <i data-lucide="file-badge" class="h-4 w-4 text-violet-500"></i>
            <span>Certificate</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrCertificate"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrCertificate">
          <a href="/programmes/sltr/certificate/rofo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="folder" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>RofO</span>
          </a>
          <a href="/programmes/sltr/certificate/cofo" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="file-badge" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>CofO</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - e-Registry') || $hasRole('SLTR - GIS') || $hasRole('SLTR - Survey') || $hasRole('SLTR - Reports'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrERegistry">
          <div class="flex items-center gap-2">
            <i data-lucide="database" class="h-4 w-4 text-violet-500"></i>
            <span>e-Registry</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrERegistry"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrERegistry">
          <a href="/programmes/sltr/e-registry/files" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="folder" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Files</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - GIS'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrGis">
          <div class="flex items-center gap-2">
            <i data-lucide="map" class="h-4 w-4 text-violet-500"></i>
            <span>GIS</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrGis"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrGis">
          <a href="/programmes/sltr/gis/attribution" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="database" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Attribution</span>
          </a>
          <a href="/programmes/sltr/gis/map" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="map-pin" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Map</span>
          </a>
        </div>
        @endif
        
        @if($hasRole('SLTR - Survey'))
        <div class="sidebar-submodule-header flex items-center justify-between py-1.5 px-3 cursor-pointer rounded-md" data-section="sltrSurvey">
          <div class="flex items-center gap-2">
            <i data-lucide="land-plot" class="h-4 w-4 text-violet-500"></i>
            <span>Survey</span>
          </div>
          <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="sltrSurvey"></i>
        </div>
        
        <div class="pl-4 mt-1 mb-1 space-y-0.5 hidden" data-content="sltrSurvey">
          <a href="/programmes/sltr/survey/attribution" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
            <i data-lucide="land-plot" class="h-3.5 w-3.5 text-violet-400"></i>
            <span>Attribution</span>
          </a>
        </div>
        @endif

        <a href="/programmes/sltr/reports" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="file-bar-chart" class="h-4 w-4 text-violet-500"></i>
          <span>Reports</span>
        </a>
      </div>
    </div>
    @endif

    <!-- 14. Systems -->
    @if(false)
    <!-- removed Systems section; Encumbrance/Caveat moved under Deeds per menu.md -->
    @endif

    <!-- 15. Legacy Systems -->
    @if($hasRole('Legacy System'))
    <div class="py-1 px-3 mb-0.5 border-t border-slate-100">
      <div class="sidebar-module-header flex items-center justify-between py-2 px-3 mb-0.5 cursor-pointer hover:bg-slate-50 rounded-md" data-module="legacy">
        <div class="flex items-center gap-2">
          <i data-lucide="hard-drive" class="h-5 w-5 text-stone-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">Legacy Systems</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="legacy"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="legacy">
        <a href="/legacy-systems" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="database" class="h-4 w-4 text-stone-500"></i>
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
          <i data-lucide="cog" class="h-5 w-5 text-slate-600"></i>
          <span class="text-sm font-bold uppercase tracking-wider">System Admin</span>
        </div>
        <i data-lucide="chevron-right" class="h-4 w-4 transition-transform duration-200" data-chevron="admin"></i>
      </div>

      <div class="pl-4 mt-1 space-y-0.5 hidden" data-content="admin">
        @if($hasRole('User Account'))
        <a href="{{ route('user-activity-logs.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('user-activity-log.index') ? 'active' : '' }}">
          <i data-lucide="users" class="h-4 w-4 text-slate-500"></i>
          <span>Activity Logs</span>
        </a>
        <a href="{{route('users.index')}}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('users.index') ? 'active' : '' }}">
          <i data-lucide="user-cog" class="h-4 w-4 text-slate-500"></i>
          <span>User Account</span>
        </a>
        @endif

        <!-- New menu items for departments and roles -->
        @if($hasRole('Departments'))
        <a href="{{ route('departments.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('departments.index') ? 'active' : '' }}">
          <i data-lucide="building" class="h-4 w-4 text-slate-500"></i>
          <span>Departments</span>
        </a>
        @endif

        @if($hasRole('User Roles'))
        <a href="{{ route('user-roles.index') }}" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200 {{ request()->routeIs('user-roles.index') ? 'active' : '' }}">
          <i data-lucide="shield" class="h-4 w-4 text-slate-500"></i>
          <span>User Roles</span>
        </a>
        @endif
        
        @if($hasRole('System Settings'))
        <a href="/admin/system-settings" class="sidebar-item flex items-center gap-2 py-2 px-3 rounded-md transition-all duration-200">
          <i data-lucide="settings" class="h-4 w-4 text-slate-500"></i>
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
  // Initialize Lucide icons safely
  if (window.lucide && typeof lucide.createIcons === 'function') {
    lucide.createIcons();
  }

  function scrollToActiveItem() {
    const activeItem = document.querySelector('.sidebar-item.active');
    const sidebarContent = document.querySelector('.sidebar-content');
    if (activeItem && sidebarContent) {
      const offsetTop = activeItem.offsetTop;
      const sidebarHeight = sidebarContent.clientHeight;
      const itemHeight = activeItem.offsetHeight;
      const idealScrollTop = offsetTop - (sidebarHeight / 2) + (itemHeight / 2);
      sidebarContent.scrollTo({ top: Math.max(0, idealScrollTop), behavior: 'smooth' });
    }
  }

  function toggleModule(moduleName) {
    const content = document.querySelector(`[data-content="${moduleName}"]`);
    const chevron = document.querySelector(`[data-chevron="${moduleName}"]`);
    if (!content) return;
    const willOpen = content.classList.contains('hidden');
    content.classList.toggle('hidden');
    if (chevron) chevron.classList.toggle('rotate-90', willOpen);
  }

  function toggleSection(sectionName) {
    const content = document.querySelector(`[data-content="${sectionName}"]`);
    const chevron = document.querySelector(`[data-chevron="${sectionName}"]`);
    if (!content) return;
    const willOpen = content.classList.contains('hidden');
    content.classList.toggle('hidden');
    if (chevron) chevron.classList.toggle('rotate-90', willOpen);
  }

  document.addEventListener('DOMContentLoaded', function() {
    // No auto-open. Only open on user click.

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

    // Sidebar item active class + gentle scroll (on click only)
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    sidebarItems.forEach(item => {
      item.addEventListener('click', function() {
        sidebarItems.forEach(i => i.classList.remove('active'));
        this.classList.add('active');
        setTimeout(scrollToActiveItem, 50);
      });
    });

    // User menu toggle
    const userMenuButton = document.getElementById('userMenuButton');
    const userMenu = document.getElementById('userMenu');
    if (userMenuButton && userMenu) {
      userMenuButton.addEventListener('click', function() {
        userMenu.classList.toggle('hidden');
      });
      document.addEventListener('click', function(e) {
        if (!userMenuButton.contains(e.target) && !userMenu.contains(e.target)) {
          userMenu.classList.add('hidden');
        }
      });
    }
  });
  
  // Enhanced scroll indicators and feedback
  const sidebarContent = document.querySelector('.sidebar-content');
  
  if (sidebarContent) {
    function updateScrollIndicators() {
      const { scrollTop, scrollHeight, clientHeight } = sidebarContent;
      const isScrolledToTop = scrollTop <= 5;
      const isScrolledToBottom = scrollTop + clientHeight >= scrollHeight - 5;
      
      // Update CSS custom properties for gradient indicators
      sidebarContent.style.setProperty('--scroll-top-opacity', isScrolledToTop ? '0' : '1');
      sidebarContent.style.setProperty('--scroll-bottom-opacity', isScrolledToBottom ? '0' : '1');
      
      // Update scroll position data attribute for styling
      if (isScrolledToTop) {
        sidebarContent.setAttribute('data-scroll', 'top');
      } else if (isScrolledToBottom) {
        sidebarContent.setAttribute('data-scroll', 'bottom');
      } else {
        sidebarContent.setAttribute('data-scroll', 'middle');
      }
    }
    
    // Add scroll event listener with throttling for performance
    let scrollTimeout;
    sidebarContent.addEventListener('scroll', function() {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(updateScrollIndicators, 10);
    });
    
    // Initial call
    updateScrollIndicators();
    
    // Add smooth scroll behavior for menu items
    const menuItems = document.querySelectorAll('.sidebar-item, .sidebar-module-header');
    menuItems.forEach(item => {
      item.addEventListener('click', function(e) {
        // Add a subtle animation feedback
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
          this.style.transform = '';
        }, 100);
      });
    });
    
    // Add keyboard navigation support
    sidebarContent.addEventListener('keydown', function(e) {
      if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
        e.preventDefault();
        const scrollAmount = 50;
        if (e.key === 'ArrowUp') {
          this.scrollTop -= scrollAmount;
        } else {
          this.scrollTop += scrollAmount;
        }
      }
    });
    
    // Make sidebar content focusable for keyboard navigation
    sidebarContent.setAttribute('tabindex', '0');
  }
</script>

<style>
/* Enhanced scrollbar visibility */
.scrollbar-visible {
  scrollbar-width: thin;
  scrollbar-color: #94a3b8 #f1f5f9;
}

.scrollbar-visible::-webkit-scrollbar {
  width: 10px;
}

.scrollbar-visible::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 6px;
  margin: 4px 0;
  border: 1px solid #e2e8f0;
}

.scrollbar-visible::-webkit-scrollbar-thumb {
  background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
  border-radius: 6px;
  border: 1px solid #cbd5e1;
  box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.scrollbar-visible::-webkit-scrollbar-thumb:hover {
  background: linear-gradient(180deg, #64748b 0%, #475569 100%);
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.2);
}

.scrollbar-visible::-webkit-scrollbar-thumb:active {
  background: linear-gradient(180deg, #475569 0%, #334155 100%);
}

/* Add subtle gradient indicators for scrollable content */
.sidebar-content {
  position: relative;
}

.sidebar-content::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 8px;
  height: 12px;
  background: linear-gradient(to bottom, rgba(255,255,255,0.95), transparent);
  pointer-events: none;
  z-index: 10;
  opacity: var(--scroll-top-opacity, 0);
  transition: opacity 0.3s ease;
}

.sidebar-content::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 8px;
  height: 12px;
  background: linear-gradient(to top, rgba(255,255,255,0.95), transparent);
  pointer-events: none;
  z-index: 10;
  opacity: var(--scroll-bottom-opacity, 1);
  transition: opacity 0.3s ease;
}

/* Add scroll indicator shadow */
.sidebar-content {
  box-shadow: inset 0 4px 6px -1px rgba(0, 0, 0, 0.05);
}

/* Enhance hover effects for better UX */
.sidebar-item:hover {
  background-color: #f8fafc;
  transform: translateX(2px);
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.sidebar-module-header:hover {
  background-color: #f8fafc;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Add visual feedback for scrollable areas */
@media (hover: hover) {
  .sidebar-content:hover::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #64748b 0%, #475569 100%);
  }
}

/* Mobile scrollbar enhancement */
@media (max-width: 768px) {
  .scrollbar-visible::-webkit-scrollbar {
    width: 8px;
  }
}

/* Add a subtle border to indicate scrollable content */
.sidebar-content {
  border-left: 2px solid transparent;
  transition: border-color 0.3s ease;
}

.sidebar-content:hover {
  border-left-color: #e2e8f0;
}

/* Scroll position indicators */
.sidebar-content[data-scroll="top"] {
  border-top: 2px solid #3b82f6;
}

.sidebar-content[data-scroll="bottom"] {
  border-bottom: 2px solid #3b82f6;
}

.sidebar-content[data-scroll="middle"] {
  border-left-color: #3b82f6;
}
</style>

