@extends('layouts.app')

@section('page-title')
    {{ __('Edit File Index') }}
@endsection

@section('content')
<!-- Minimal Professional Styling -->
<style>
.form-input-clean {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    background: #ffffff;
}

.form-input-clean:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input-clean:read-only {
    background-color: #f9fafb;
    color: #6b7280;
    border-color: #d1d5db;
}

.form-label-clean {
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: block;
}

.form-section-clean {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.form-section-header {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 20px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 8px;
}


.form-section-header2 {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin-top: 20px;
    padding-top: 8px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    gap: 8px;
}

.checkbox-clean {
    width: 16px;
    height: 16px;
    border: 1px solid #d1d5db;
    border-radius: 3px;
    background: #ffffff;
    cursor: pointer;
}

.checkbox-clean:checked {
    background-color: #3b82f6;
    border-color: #3b82f6;
}

.btn-clean {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 20px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    text-decoration: none;
    border: 1px solid transparent;
    cursor: pointer;
}

.btn-primary-clean {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.btn-primary-clean:hover {
    background-color: #2563eb;
    border-color: #2563eb;
}

.btn-secondary-clean {
    background-color: #ffffff;
    color: #6b7280;
    border-color: #d1d5db;
}

.btn-secondary-clean:hover {
    background-color: #f9fafb;
    color: #374151;
}

.alert-clean {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.alert-success {
    background-color: #f0f9ff;
    border: 1px solid #bae6fd;
    color: #0369a1;
}

.alert-error {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    color: #dc2626;
}

.page-header-clean {
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.form-grid {
    display: grid;
    gap: 20px;
}

@media (min-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr 1fr;
    }
}

.loading-spinner-clean {
    width: 16px;
    height: 16px;
    border: 2px solid #ffffff;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div class="flex-1 overflow-auto">
    @include('admin.header')
    
    <!-- Dashboard Content -->
    <div class="p-6">
        <div class="max-w-5xl mx-auto">
            <!-- Clean Page Header -->
            <div class="page-header-clean">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-semibold text-gray-900">{{ $PageTitle }}</h1>
                        <p class="mt-2 text-gray-600">{{ $PageDescription }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Property Transaction Button -->
                        <button type="button" 
                                id="property-transaction-btn" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors gap-2">
                            <i data-lucide="file-plus" class="h-4 w-4"></i>
                            <span id="property-transaction-btn-text">Update Property Transaction Details</span>
                        </button>

                        <a href="{{ route('fileindexing.index') }}" class="btn-clean btn-secondary-clean">
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            Back to List
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alert Messages -->
            <div id="alert-container"></div>

            <!-- Clean Form -->
            <form id="edit-file-form" method="POST" action="{{ route('fileindexing.update', $fileIndexing->id) }}">
                @csrf
                @method('PUT')
                
                <div class="form-grid">
                    <!-- File Information Section -->
                    <div class="form-section-clean">
                        <div class="form-section-header">
                            <i data-lucide="file-text" class="h-5 w-5 text-gray-500"></i>
                            File Information
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="file_number" class="form-label-clean">
                                    File Number <span class="text-red-500">*</span>
                                </label>
                                <div class="flex">
                                    <!-- Display input field for selected File Number -->
                                    <input type="text" 
                                           id="file-number-display" 
                                           class="w-full form-input-clean mr-2" 
                                           value="{{ old('file_number', $fileIndexing->file_number) }}"
                                           placeholder="No file number selected" 
                                           readonly 
                                           style="background-color: #f3f4f6; color: #6b7280;">
                                    
                                    <!-- Hidden input that will actually be submitted -->
                                    <input type="hidden" 
                                           id="file_number" 
                                           name="file_number" 
                                           value="{{ old('file_number', $fileIndexing->file_number) }}">
                                    
                                    <!-- Button to open the global File Number modal -->
                                    {{-- <button type="button" 
                                            id="select-file-number-btn" 
                                            class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 mr-2"
                                            style="white-space: nowrap;">
                                        Select
                                    </button> --}}
                                    
                                    <!-- Change button to allow users to modify existing file number -->
                                    <button type="button" 
                                            id="change-file-number-btn" 
                                            class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                                            style="white-space: nowrap;"
                                            title="Change existing file number">
                                        <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i>
                                        Change
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label for="file_title" class="form-label-clean">
                                    File Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="file_title" 
                                       name="file_title" 
                                       value="{{ old('file_title', $fileIndexing->file_title) }}"
                                       class="w-full form-input-clean"
                                       placeholder="Enter file title"
                                       required>
                            </div>

                            <div>
                                <label for="land_use_type" class="form-label-clean">
                                    Land Use Type <span class="text-red-500">*</span>
                                </label>
                                <select id="land_use_type"   
                                        name="land_use_type" 
                                        class="w-full form-input-clean"
                                        required>
                                    <option value="">Select Land Use Type</option>
                                    @if(old('land_use_type', $fileIndexing->land_use_type) && !in_array(old('land_use_type', $fileIndexing->land_use_type), ['RESIDENTIAL', 'AGRICULTURAL', 'COMMERCIAL', 'COMMERCIAL ( WARE HOUSE)', 'COMMERCIAL (OFFICES)', 'COMMERCIAL (PETROL FILLING STATION)', 'COMMERCIAL (RICE PROCESSING)', 'COMMERCIAL (SCHOOL)', 'COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)', 'COMMERCIAL (SHOPS AND OFFICES)', 'COMMERCIAL (SHOPS)', 'COMMERCIAL (WAREHOUSE)', 'COMMERCIAL (WORKSHOP AND OFFICES)', 'COMMERCIAL AND RESIDENTIAL', 'INDUSTRIAL', 'INDUSTRIAL (SMALL SCALE)', 'RESIDENTIAL AND COMMERCIAL', 'RESIDENTIAL/COMMERCIAL', 'RESIDENTIAL/COMMERCIAL LAYOUT']))
                                        <option value="{{ old('land_use_type', $fileIndexing->land_use_type) }}" selected>{{ old('land_use_type', $fileIndexing->land_use_type) }}</option>
                                    @endif
                                    <option value="RESIDENTIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'RESIDENTIAL' ? 'selected' : '' }}>RESIDENTIAL</option>
                                    <option value="AGRICULTURAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'AGRICULTURAL' ? 'selected' : '' }}>AGRICULTURAL</option>
                                    <option value="COMMERCIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL' ? 'selected' : '' }}>COMMERCIAL</option>
                                    <option value="COMMERCIAL ( WARE HOUSE)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL ( WARE HOUSE)' ? 'selected' : '' }}>COMMERCIAL ( WARE HOUSE)</option>
                                    <option value="COMMERCIAL (OFFICES)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (OFFICES)' ? 'selected' : '' }}>COMMERCIAL (OFFICES)</option>
                                    <option value="COMMERCIAL (PETROL FILLING STATION)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (PETROL FILLING STATION)' ? 'selected' : '' }}>COMMERCIAL (PETROL FILLING STATION)</option>
                                    <option value="COMMERCIAL (RICE PROCESSING)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (RICE PROCESSING)' ? 'selected' : '' }}>COMMERCIAL (RICE PROCESSING)</option>
                                    <option value="COMMERCIAL (SCHOOL)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (SCHOOL)' ? 'selected' : '' }}>COMMERCIAL (SCHOOL)</option>
                                    <option value="COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)' ? 'selected' : '' }}>COMMERCIAL (SHOPS & PUBLIC CONVINIENCE)</option>
                                    <option value="COMMERCIAL (SHOPS AND OFFICES)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (SHOPS AND OFFICES)' ? 'selected' : '' }}>COMMERCIAL (SHOPS AND OFFICES)</option>
                                    <option value="COMMERCIAL (SHOPS)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (SHOPS)' ? 'selected' : '' }}>COMMERCIAL (SHOPS)</option>
                                    <option value="COMMERCIAL (WAREHOUSE)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (WAREHOUSE)' ? 'selected' : '' }}>COMMERCIAL (WAREHOUSE)</option>
                                    <option value="COMMERCIAL (WORKSHOP AND OFFICES)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL (WORKSHOP AND OFFICES)' ? 'selected' : '' }}>COMMERCIAL (WORKSHOP AND OFFICES)</option>
                                    <option value="COMMERCIAL AND RESIDENTIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'COMMERCIAL AND RESIDENTIAL' ? 'selected' : '' }}>COMMERCIAL AND RESIDENTIAL</option>
                                    <option value="INDUSTRIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'INDUSTRIAL' ? 'selected' : '' }}>INDUSTRIAL</option>
                                    <option value="INDUSTRIAL (SMALL SCALE)" {{ old('land_use_type', $fileIndexing->land_use_type) == 'INDUSTRIAL (SMALL SCALE)' ? 'selected' : '' }}>INDUSTRIAL (SMALL SCALE)</option>
                                    <option value="RESIDENTIAL AND COMMERCIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'RESIDENTIAL AND COMMERCIAL' ? 'selected' : '' }}>RESIDENTIAL AND COMMERCIAL</option>
                                    <option value="RESIDENTIAL/COMMERCIAL" {{ old('land_use_type', $fileIndexing->land_use_type) == 'RESIDENTIAL/COMMERCIAL' ? 'selected' : '' }}>RESIDENTIAL/COMMERCIAL</option>
                                    <option value="RESIDENTIAL/COMMERCIAL LAYOUT" {{ old('land_use_type', $fileIndexing->land_use_type) == 'RESIDENTIAL/COMMERCIAL LAYOUT' ? 'selected' : '' }}>RESIDENTIAL/COMMERCIAL LAYOUT</option>
 
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="plot_number" class="form-label-clean">
                                        Plot Number
                                    </label>
                                    <input type="text" 
                                           id="plot_number" 
                                           name="plot_number" 
                                           value="{{ old('plot_number', $fileIndexing->plot_number) }}"
                                           class="w-full form-input-clean"
                                           placeholder="Enter plot number">
                                </div>

                                <div>
                                    <label for="tp_no" class="form-label-clean">
                                        TP Number
                                    </label>
                                    <input type="text" 
                                           id="tp_no" 
                                           name="tp_no" 
                                           value="{{ old('tp_no', $fileIndexing->tp_no) }}"
                                           class="w-full form-input-clean"
                                           placeholder="Enter TP number">
                                </div>
                            </div>

                            <div>
                                <label for="lpkn_no" class="form-label-clean">
                                    LPKN Number
                                </label>
                                <input type="text" 
                                       id="lpkn_no" 
                                       name="lpkn_no" 
                                       value="{{ old('lpkn_no', $fileIndexing->lpkn_no) }}"
                                       class="w-full form-input-clean"
                                       placeholder="Enter LPKN number">
                            </div>
                        </div>
                    </div>

                    <!-- Location & Administrative Section -->
                    <div class="form-section-clean">
                        <div class="form-section-header">
                            <i data-lucide="map-pin" class="h-5 w-5 text-gray-500"></i>
                             Location  Details
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="district" class="form-label-clean">
                                        District
                                    </label>
                                    <input type="text" 
                                           id="district" 
                                           name="district" 
                                           value="{{ old('district', $fileIndexing->district) }}"
                                           class="w-full form-input-clean"
                                           placeholder="Enter district">
                                </div>

                                <div>
                                    <label for="lga" class="form-label-clean">
                                        LGA
                                    </label>
                                    <select id="lga" 
                                            name="lga" 
                                            class="w-full form-input-clean">
                                        <option value="">Select LGA</option>
                                        @if(old('lga', $fileIndexing->lga) && !in_array(old('lga', $fileIndexing->lga), ['Albasu', 'Bagwai', 'Dala', 'Danbatta', 'D/Tofa', 'Gaya', 'Gwale', 'Doguwa', 'Kibiya', 'Kabo', 'Gezawa', 'Kunchi', 'Karaye', 'Garum Malan', 'Madobi', 'Gabasawa', 'Rimin Gado', 'Rogo', 'Shanono', 'Municipal', 'Sumaila', 'Tarauni', 'Tsanyawa', 'Tudun Wada', 'Tofa', 'Takai', 'Kura', 'Warawa', 'Garko', 'Ajingi', 'Bichi', 'Minjinbir', 'Rano', 'Bunkure', 'Kiru', 'Gwarzo', 'Ungogo', 'Makoda', 'Wudil', 'Nassarawa', 'Bebeji', 'Faffe', 'D/Kudu', 'Kumbotso']))
                                            <option value="{{ old('lga', $fileIndexing->lga) }}" selected>{{ old('lga', $fileIndexing->lga) }}</option>
                                        @endif
                                        <option value="Albasu" {{ old('lga', $fileIndexing->lga) == 'Albasu' ? 'selected' : '' }}>Albasu</option>
                                        <option value="Bagwai" {{ old('lga', $fileIndexing->lga) == 'Bagwai' ? 'selected' : '' }}>Bagwai</option>
                                        <option value="Dala" {{ old('lga', $fileIndexing->lga) == 'Dala' ? 'selected' : '' }}>Dala</option>
                                        <option value="Danbatta" {{ old('lga', $fileIndexing->lga) == 'Danbatta' ? 'selected' : '' }}>Danbatta</option>
                                        <option value="D/Tofa" {{ old('lga', $fileIndexing->lga) == 'D/Tofa' ? 'selected' : '' }}>D/Tofa</option>
                                        <option value="Gaya" {{ old('lga', $fileIndexing->lga) == 'Gaya' ? 'selected' : '' }}>Gaya</option>
                                        <option value="Gwale" {{ old('lga', $fileIndexing->lga) == 'Gwale' ? 'selected' : '' }}>Gwale</option>
                                        <option value="Doguwa" {{ old('lga', $fileIndexing->lga) == 'Doguwa' ? 'selected' : '' }}>Doguwa</option>
                                        <option value="Kibiya" {{ old('lga', $fileIndexing->lga) == 'Kibiya' ? 'selected' : '' }}>Kibiya</option>
                                        <option value="Kabo" {{ old('lga', $fileIndexing->lga) == 'Kabo' ? 'selected' : '' }}>Kabo</option>
                                        <option value="Gezawa" {{ old('lga', $fileIndexing->lga) == 'Gezawa' ? 'selected' : '' }}>Gezawa</option>
                                        <option value="Kunchi" {{ old('lga', $fileIndexing->lga) == 'Kunchi' ? 'selected' : '' }}>Kunchi</option>
                                        <option value="Karaye" {{ old('lga', $fileIndexing->lga) == 'Karaye' ? 'selected' : '' }}>Karaye</option>
                                        <option value="Garum Malan" {{ old('lga', $fileIndexing->lga) == 'Garum Malan' ? 'selected' : '' }}>Garum Malan</option>
                                        <option value="Madobi" {{ old('lga', $fileIndexing->lga) == 'Madobi' ? 'selected' : '' }}>Madobi</option>
                                        <option value="Gabasawa" {{ old('lga', $fileIndexing->lga) == 'Gabasawa' ? 'selected' : '' }}>Gabasawa</option>
                                        <option value="Rimin Gado" {{ old('lga', $fileIndexing->lga) == 'Rimin Gado' ? 'selected' : '' }}>Rimin Gado</option>
                                        <option value="Rogo" {{ old('lga', $fileIndexing->lga) == 'Rogo' ? 'selected' : '' }}>Rogo</option>
                                        <option value="Shanono" {{ old('lga', $fileIndexing->lga) == 'Shanono' ? 'selected' : '' }}>Shanono</option>
                                        <option value="Municipal" {{ old('lga', $fileIndexing->lga) == 'Municipal' ? 'selected' : '' }}>Municipal</option>
                                        <option value="Sumaila" {{ old('lga', $fileIndexing->lga) == 'Sumaila' ? 'selected' : '' }}>Sumaila</option>
                                        <option value="Tarauni" {{ old('lga', $fileIndexing->lga) == 'Tarauni' ? 'selected' : '' }}>Tarauni</option>
                                        <option value="Tsanyawa" {{ old('lga', $fileIndexing->lga) == 'Tsanyawa' ? 'selected' : '' }}>Tsanyawa</option>
                                        <option value="Tudun Wada" {{ old('lga', $fileIndexing->lga) == 'Tudun Wada' ? 'selected' : '' }}>Tudun Wada</option>
                                        <option value="Tofa" {{ old('lga', $fileIndexing->lga) == 'Tofa' ? 'selected' : '' }}>Tofa</option>
                                        <option value="Takai" {{ old('lga', $fileIndexing->lga) == 'Takai' ? 'selected' : '' }}>Takai</option>
                                        <option value="Kura" {{ old('lga', $fileIndexing->lga) == 'Kura' ? 'selected' : '' }}>Kura</option>
                                        <option value="Warawa" {{ old('lga', $fileIndexing->lga) == 'Warawa' ? 'selected' : '' }}>Warawa</option>
                                        <option value="Garko" {{ old('lga', $fileIndexing->lga) == 'Garko' ? 'selected' : '' }}>Garko</option>
                                        <option value="Ajingi" {{ old('lga', $fileIndexing->lga) == 'Ajingi' ? 'selected' : '' }}>Ajingi</option>
                                        <option value="Bichi" {{ old('lga', $fileIndexing->lga) == 'Bichi' ? 'selected' : '' }}>Bichi</option>
                                        <option value="Minjinbir" {{ old('lga', $fileIndexing->lga) == 'Minjinbir' ? 'selected' : '' }}>Minjinbir</option>
                                        <option value="Rano" {{ old('lga', $fileIndexing->lga) == 'Rano' ? 'selected' : '' }}>Rano</option>
                                        <option value="Bunkure" {{ old('lga', $fileIndexing->lga) == 'Bunkure' ? 'selected' : '' }}>Bunkure</option>
                                        <option value="Kiru" {{ old('lga', $fileIndexing->lga) == 'Kiru' ? 'selected' : '' }}>Kiru</option>
                                        <option value="Gwarzo" {{ old('lga', $fileIndexing->lga) == 'Gwarzo' ? 'selected' : '' }}>Gwarzo</option>
                                        <option value="Ungogo" {{ old('lga', $fileIndexing->lga) == 'Ungogo' ? 'selected' : '' }}>Ungogo</option>
                                        <option value="Makoda" {{ old('lga', $fileIndexing->lga) == 'Makoda' ? 'selected' : '' }}>Makoda</option>
                                        <option value="Wudil" {{ old('lga', $fileIndexing->lga) == 'Wudil' ? 'selected' : '' }}>Wudil</option>
                                        <option value="Nassarawa" {{ old('lga', $fileIndexing->lga) == 'Nassarawa' ? 'selected' : '' }}>Nassarawa</option>
                                        <option value="Bebeji" {{ old('lga', $fileIndexing->lga) == 'Bebeji' ? 'selected' : '' }}>Bebeji</option>
                                        <option value="Faffe" {{ old('lga', $fileIndexing->lga) == 'Faffe' ? 'selected' : '' }}>Faffe</option>
                                        <option value="D/Kudu" {{ old('lga', $fileIndexing->lga) == 'D/Kudu' ? 'selected' : '' }}>D/Kudu</option>
                                        <option value="Kumbotso" {{ old('lga', $fileIndexing->lga) == 'Kumbotso' ? 'selected' : '' }}>Kumbotso</option>
                                    </select>
                                </div>
                            </div>
                            
                            
       

 

   <div class="form-section-header2">
                            

                             <i data-lucide="archive" class="h-5 w-5 text-gray-500"></i>
    Digital Registry Details

                        </div>
                            <div>
                                <label for="registry" class="form-label-clean">
                                    Registry
                                </label>
                                <select id="registry" 
                                        name="registry" 
                                        class="w-full form-input-clean">
                                    <option value="">Select Registry</option>
                                    @if(old('registry', $fileIndexing->registry) && !in_array(old('registry', $fileIndexing->registry), ['Registry 1 - Lands', 'Registry 2 - Lands', 'Registry 3 - Lands', 'Registry 1 - Deeds', 'Registry 2 - Deeds', 'Registry 1 - Cadastral', 'Registry 2 - Cadastral', 'KANGIS Registry', 'SLTR Registry', 'ST Registry', 'DCIV Registry', 'Other']))
                                        <option value="{{ old('registry', $fileIndexing->registry) }}" selected>{{ old('registry', $fileIndexing->registry) }}</option>
                                    @endif
                                    <option value="Registry 1 - Lands" {{ old('registry', $fileIndexing->registry) == 'Registry 1 - Lands' ? 'selected' : '' }}>Registry 1 - Lands</option>
                                    <option value="Registry 2 - Lands" {{ old('registry', $fileIndexing->registry) == 'Registry 2 - Lands' ? 'selected' : '' }}>Registry 2 - Lands</option>
                                    <option value="Registry 3 - Lands" {{ old('registry', $fileIndexing->registry) == 'Registry 3 - Lands' ? 'selected' : '' }}>Registry 3 - Lands</option>
                                    <option value="Registry 1 - Deeds" {{ old('registry', $fileIndexing->registry) == 'Registry 1 - Deeds' ? 'selected' : '' }}>Registry 1 - Deeds</option>
                                    <option value="Registry 2 - Deeds" {{ old('registry', $fileIndexing->registry) == 'Registry 2 - Deeds' ? 'selected' : '' }}>Registry 2 - Deeds</option>
                                    <option value="Registry 1 - Cadastral" {{ old('registry', $fileIndexing->registry) == 'Registry 1 - Cadastral' ? 'selected' : '' }}>Registry 1 - Cadastral</option>
                                    <option value="Registry 2 - Cadastral" {{ old('registry', $fileIndexing->registry) == 'Registry 2 - Cadastral' ? 'selected' : '' }}>Registry 2 - Cadastral</option>
                                    <option value="KANGIS Registry" {{ old('registry', $fileIndexing->registry) == 'KANGIS Registry' ? 'selected' : '' }}>KANGIS Registry</option>
                                    <option value="SLTR Registry" {{ old('registry', $fileIndexing->registry) == 'SLTR Registry' ? 'selected' : '' }}>SLTR Registry</option>
                                    <option value="ST Registry" {{ old('registry', $fileIndexing->registry) == 'ST Registry' ? 'selected' : '' }}>ST Registry</option>
                                    <option value="DCIV Registry" {{ old('registry', $fileIndexing->registry) == 'DCIV Registry' ? 'selected' : '' }}>DCIV Registry</option>
                                    <option value="Other" {{ old('registry', $fileIndexing->registry) == 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="batch_no" class="form-label-clean">
                                        Batch Number
                                    </label>
                                    <input type="text" 
                                           id="batch_no" 
                                           name="batch_no" 
                                           value="{{ old('batch_no', $fileIndexing->batch_no) }}"
                                           class="w-full form-input-clean" 
                                           readonly>
                                </div>
 
                                <div>
                                    <label for="tracking_id" class="form-label-clean">
                                        Tracking ID
                                    </label>
                                    <input type="text" 
                                           id="tracking_id" 
                                           name="tracking_id" 
                                           value="{{ old('tracking_id', $fileIndexing->tracking_id) }}"
                                           class="w-full form-input-clean"  style="background-color: #f3f4f6; color: #ef4444;"
                                           readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Property Description Section -->
                <div class="form-section-clean mt-6">
                    <div class="form-section-header">
                        <i data-lucide="file-text" class="h-5 w-5 text-gray-500"></i>
                        Property Description
                    </div>
                    
                    <div>
                        <label for="location" class="form-label-clean">
                            Location Details
                        </label>
                        <div id="location-preview" class="mb-2 text-sm text-gray-600 italic min-h-4 hidden"></div>
                        <textarea id="location" 
                                  name="location" 
                                  rows="4"
                                  class="w-full form-input-clean uppercase"
                                  placeholder="ENTER DETAILED PROPERTY DESCRIPTION AND LOCATION INFORMATION..."
                                  style="text-transform: uppercase;">{{ old('location', $fileIndexing->location) }}</textarea>
                    </div>
                </div>

                <!-- Add JavaScript to update Location in real-time -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const districtField = document.getElementById('district');
                    const lgaField = document.getElementById('lga');
                    const locationPreview = document.getElementById('location-preview');
                    const locationTextarea = document.getElementById('location');
                    
                    function updateLocationPreview() {
                        // Check if elements exist before accessing them
                        if (!districtField || !lgaField || !locationPreview) {
                            console.warn('Location preview elements not found');
                            return;
                        }
                        
                        const district = districtField.value.trim();
                        const lga = lgaField.value.trim();
                        
                        if (district || lga) {
                            let previewText = '';
                            
                            if (district) {
                                previewText += district;
                                
                                if (lga && lga !== district) {
                                    previewText += ', ' + lga + ' LGA';
                                }
                            } else if (lga) {
                                previewText += lga + ' LGA';
                            }
                            
                            locationPreview.textContent = previewText;
                            
                            // Auto-update the textarea in real-time as user types
                            if (locationTextarea) {
                                locationTextarea.value = previewText;
                            }
                        } else {
                            locationPreview.textContent = '';
                            if (locationTextarea) {
                                locationTextarea.value = '';
                            }
                        }
                    }
                    
                    // Add event listeners to district and LGA fields
                    if (districtField) {
                        districtField.addEventListener('input', updateLocationPreview);
                        districtField.addEventListener('keyup', updateLocationPreview);
                    }
                    
                    if (lgaField) {
                        lgaField.addEventListener('change', updateLocationPreview);
                        lgaField.addEventListener('input', updateLocationPreview);
                    }
                    
                    // Initialize on page load
                    updateLocationPreview();
                });
                </script>

                <!-- File Attributes Section  hidden for now-->
                <div class="form-section-clean mt-6 hidden">
                    <div class="form-section-header">
                        <i data-lucide="settings" class="h-5 w-5 text-gray-500"></i>
                        File Attributes
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="flex items-center gap-3">
                            <input type="hidden" name="has_cofo" value="0">
                            <input type="checkbox" 
                                   id="has_cofo" 
                                   name="has_cofo" 
                                   value="1"
                                   {{ old('has_cofo', $fileIndexing->has_cofo) ? 'checked' : '' }}
                                   class="checkbox-clean">
                            <label for="has_cofo" class="form-label-clean mb-0 cursor-pointer">
                                Has C of O
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_merged" value="0">
                            <input type="checkbox" 
                                   id="is_merged" 
                                   name="is_merged" 
                                   value="1"
                                   {{ old('is_merged', $fileIndexing->is_merged) ? 'checked' : '' }}
                                   class="checkbox-clean">
                            <label for="is_merged" class="form-label-clean mb-0 cursor-pointer">
                                Is Merged
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="has_transaction" value="0">
                            <input type="checkbox" 
                                   id="has_transaction" 
                                   name="has_transaction" 
                                   value="1"
                                   {{ old('has_transaction', $fileIndexing->has_transaction) ? 'checked' : '' }}
                                   class="checkbox-clean">
                            <label for="has_transaction" class="form-label-clean mb-0 cursor-pointer">
                                Has Transaction
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_problematic" value="0">
                            <input type="checkbox" 
                                   id="is_problematic" 
                                   name="is_problematic" 
                                   value="1"
                                   {{ old('is_problematic', $fileIndexing->is_problematic) ? 'checked' : '' }}
                                   class="checkbox-clean">
                            <label for="is_problematic" class="form-label-clean mb-0 cursor-pointer">
                                Is Problematic
                            </label>
                        </div>

                        <div class="flex items-center gap-3">
                            <input type="hidden" name="is_co_owned_plot" value="0">
                            <input type="checkbox" 
                                   id="is_co_owned_plot" 
                                   name="is_co_owned_plot" 
                                   value="1"
                                   {{ old('is_co_owned_plot', $fileIndexing->is_co_owned_plot) ? 'checked' : '' }}
                                   class="checkbox-clean">
                            <label for="is_co_owned_plot" class="form-label-clean mb-0 cursor-pointer">
                                Co-owned Plot
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-between pt-8 mt-8 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        <i data-lucide="shield-check" class="h-4 w-4 inline mr-1"></i>
                        Changes will be saved securely
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('fileindexing.index') }}" class="btn-clean btn-secondary-clean">
                            <i data-lucide="x" class="h-4 w-4"></i>
                            Cancel
                        </a>
                        <button type="submit" class="btn-clean btn-primary-clean" id="update-btn">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            <span id="btn-text">Update File</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Quick Actions -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- <a href="{{ route('fileindexing.show', $fileIndexing->id) }}" class="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm transition-all">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="eye" class="h-5 w-5 text-gray-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">View Details</p>
                        <p class="text-xs text-gray-500">View full file information</p>
                    </div>
                </a> --}}
                <a href="{{ route('fileindexing.batch-tracking-sheet') }}?files={{ $fileIndexing->id }}" 
                   id="tracking-sheet-link" 
                   class="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm transition-all">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="file-text" class="h-5 w-5 text-gray-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">Tracking Sheet</p>
                        <p class="text-xs text-gray-500" id="tracking-sheet-status">
                            @if($fileIndexing->batch_generated)
                                Last generated: {{ $fileIndexing->formatted_batch_generated_at ?? 'Unknown' }}
                            @else
                                Generate tracking document
                            @endif
                        </p>
                    </div>
                </a>
                
                <a href="{{ route('fileindexing.index') }}" class="flex items-center gap-3 p-4 bg-white border border-gray-200 rounded-lg hover:border-gray-300 hover:shadow-sm transition-all">
                    <div class="flex-shrink-0 w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="list" class="h-5 w-5 text-gray-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">All Files</p>
                        <p class="text-xs text-gray-500">Return to file list</p>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    @include('admin.footer')
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('edit-file-form');
    const updateBtn = document.getElementById('update-btn');
    const btnText = document.getElementById('btn-text');
    const alertContainer = document.getElementById('alert-container');
    
    // Check if required elements exist
    if (!form) {
        console.error('Edit form not found');
        return;
    }
    
    if (!updateBtn) {
        console.error('Update button not found');
        return;
    }
    
    if (!alertContainer) {
        console.error('Alert container not found');
        return;
    }

    // Form validation
    function validateForm() {
        if (!form) {
            console.error('Form element not found');
            return false;
        }
        
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-300');
                isValid = false;
            } else {
                field.classList.remove('border-red-300');
            }
        });
        
        return isValid;
    }

    // Show clean alert messages
    function showAlert(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const icon = type === 'success' ? 'check-circle' : 'alert-circle';
        
        alertContainer.innerHTML = `
            <div class="alert-clean ${alertClass}">
                <i data-lucide="${icon}" class="h-4 w-4"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Re-initialize icons
        if (typeof lucide !== 'undefined' && lucide.createIcons) {
            lucide.createIcons();
        }
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 4000);
        }
        
        // Scroll to alert
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (!validateForm()) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }
        
        // Show loading state
        updateBtn.disabled = true;
        if (btnText) {
            btnText.textContent = 'Updating...';
        }
        const loadingIcon = updateBtn.querySelector('i');
        if (loadingIcon) {
            loadingIcon.className = 'loading-spinner-clean';
        }

        // Submit form via AJAX
        const formData = new FormData(form);
        
        // Debug: Log form data
        console.log('Form action URL:', form.action);
        console.log('Form data entries:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ': ' + value);
        }
        
        // Add timeout to prevent hanging
        const controller = new AbortController();
        const timeoutId = setTimeout(() => {
            controller.abort();
            console.log('Request timed out after 30 seconds');
        }, 30000);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            signal: controller.signal
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);
            
            if (response.ok) {
                return response.json().catch(jsonError => {
                    console.error('JSON parsing failed:', jsonError);
                    return response.text().then(text => {
                        console.log('Response as text:', text);
                        throw new Error('Invalid JSON response: ' + text.substring(0, 200));
                    });
                });
            } else {
                // Try to get error message from response
                return response.text().then(text => {
                    console.log('Error response text:', text);
                    if (response.status === 404) {
                        throw new Error('Route not found (404). Please check if the update route exists.');
                    }
                    throw new Error(`Server error: ${response.status} - ${text.substring(0, 200)}`);
                });
            }
        })
        .then(data => {
            console.log('Received data:', data);
            
            if (data.success) {
                showAlert('File indexing updated successfully!', 'success');
                
                // Reset form state and enable tracking sheet
                hasUnsavedChanges = false;
                updateTrackingSheetState(false);
                
                // Update any dynamic content if needed
                if (data.fileIndexing) {
                    // Update tracking sheet status text with latest batch info
                    const trackingSheetStatus = document.getElementById('tracking-sheet-status');
                    if (trackingSheetStatus && data.fileIndexing.batch_generated) {
                        const batchDate = data.fileIndexing.batch_generated_at ? 
                            new Date(data.fileIndexing.batch_generated_at).toLocaleString('en-US', {
                                month: 'short', day: 'numeric', year: 'numeric', 
                                hour: 'numeric', minute: '2-digit'
                            }) : 'Unknown';
                        trackingSheetStatus.textContent = `Last generated: ${batchDate}`;
                        trackingSheetStatus.dataset.originalText = trackingSheetStatus.textContent;
                    }
                }
            } else {
                showAlert('Error: ' + (data.message || 'Failed to update file indexing'), 'error');
            }
        })
        .catch(error => {
            clearTimeout(timeoutId);
            console.error('Fetch error details:', error);
            
            if (error.name === 'AbortError') {
                showAlert('Request timed out. Please try again or check your network connection.', 'error');
            } else {
                showAlert('An error occurred while updating. Please check console for details.', 'error');
            }
        })
        .finally(() => {
            clearTimeout(timeoutId);
            console.log('Resetting button state...');
            
            // Reset button state
            if (updateBtn) {
                updateBtn.disabled = false;
                console.log('Button enabled');
            }
            
            if (btnText) {
                btnText.textContent = 'Update File';
                console.log('Button text reset');
            }
            
            const icon = updateBtn ? updateBtn.querySelector('i') : null;
            if (icon) {
                icon.className = 'h-4 w-4';
                icon.setAttribute('data-lucide', 'save');
                console.log('Icon reset');
            } else {
                console.warn('Icon element not found in button');
            }
            
            // Re-initialize icons
            if (typeof lucide !== 'undefined' && lucide.createIcons) {
                lucide.createIcons();
                console.log('Icons re-initialized');
            }
        });
    });

    // Input focus styling
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Keyboard shortcut (Ctrl+S)
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            form.dispatchEvent(new Event('submit'));
        }
    });

    // File Number Modal Integration
    const selectFileNumberBtn = document.getElementById('select-file-number-btn');
    const changeFileNumberBtn = document.getElementById('change-file-number-btn');
    const fileNumberDisplay = document.getElementById('file-number-display');
    const fileNumberHidden = document.getElementById('file_number');
    
    // Make display field editable and keep it synchronized with hidden field
    if (fileNumberDisplay && fileNumberHidden) {
        // Remove readonly attribute to allow editing
        fileNumberDisplay.removeAttribute('readonly');
        fileNumberDisplay.style.backgroundColor = '#ffffff';
        fileNumberDisplay.style.color = '#374151';
        
        // Synchronize display field changes to hidden field
        fileNumberDisplay.addEventListener('input', function() {
            fileNumberHidden.value = fileNumberDisplay.value;
            console.log('File number updated via direct input:', fileNumberDisplay.value);
        });
        
        // Also handle blur event to ensure sync
        fileNumberDisplay.addEventListener('blur', function() {
            fileNumberHidden.value = fileNumberDisplay.value;
        });
    }
    
    // Initialize file number modal integration
    function initFileNumberModal() {
        // Function to handle modal opening with current value pre-selected
        function openFileNumberModal(isChange = false) {
            try {
                const currentValue = fileNumberHidden ? fileNumberHidden.value : '';
                
                GlobalFileNoModal.open({
                    initialValue: isChange ? currentValue : null, // Pre-populate if changing
                    callback: function(fileData) {
                        console.log('File selected from modal:', fileData);
                        if (fileData && fileData.fileNumber) {
                            console.log('Updating fields with file number:', fileData.fileNumber);
                            // Update display field
                            if (fileNumberDisplay) {
                                fileNumberDisplay.value = fileData.fileNumber;
                                console.log('Updated display field');
                            }
                            // Update hidden field
                            if (fileNumberHidden) {
                                fileNumberHidden.value = fileData.fileNumber;
                                console.log('Updated hidden field');
                            }
                        } else {
                            console.log('No valid file data received');
                        }
                    }
                });
            } catch (error) {
                console.error('Error opening modal:', error);
                alert('Error opening file number modal: ' + error.message);
            }
        }
        
        if (typeof GlobalFileNoModal !== 'undefined') {
            // Add event listener for Select button
            if (selectFileNumberBtn) {
                selectFileNumberBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openFileNumberModal(false);
                });
            }
            
            // Add event listener for Change button
            if (changeFileNumberBtn) {
                changeFileNumberBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    openFileNumberModal(true);
                });
            }
        } else {
            // Fallback if GlobalFileNoModal is not available
            if (selectFileNumberBtn) {
                selectFileNumberBtn.addEventListener('click', function() {
                    alert('File number selection modal is not available. Please contact administrator.');
                });
            }
            
            if (changeFileNumberBtn) {
                changeFileNumberBtn.addEventListener('click', function() {
                    alert('File number selection modal is not available. Please contact administrator.');
                });
            }
        }
    }


    // Wait for GlobalFileNoModal to be available
    if (typeof GlobalFileNoModal !== 'undefined') {
        initFileNumberModal();
    } else {
        // Wait for the script to load
        let attempts = 0;
        setTimeout(function checkModal() {
            attempts++;
            if (typeof GlobalFileNoModal !== 'undefined') {
                initFileNumberModal();
            } else if (attempts < 50) { // Max 5 seconds
                setTimeout(checkModal, 100);
            } else {
                // Add fallback click handler if modal fails to load
                if (selectFileNumberBtn) {
                    selectFileNumberBtn.addEventListener('click', function() {
                        alert('File number modal failed to load. Please refresh the page and try again.');
                    });
                }
                if (changeFileNumberBtn) {
                    changeFileNumberBtn.addEventListener('click', function() {
                        alert('File number modal failed to load. Please refresh the page and try again.');
                    });
                }
            }
        }, 100);
    }

    // Also listen for the global modal events as backup
    $(document).on('fileno-modal:applied', function(event, data) {
        console.log('Global modal applied event received:', data);
        if (data && data.fileNumber) {
            if (fileNumberDisplay) {
                fileNumberDisplay.value = data.fileNumber;
            }
            if (fileNumberHidden) {
                fileNumberHidden.value = data.fileNumber;
            }
        }
    });

    // Tracking sheet state management
    const trackingSheetLink = document.getElementById('tracking-sheet-link');
    const trackingSheetStatus = document.getElementById('tracking-sheet-status');
    let hasUnsavedChanges = false;
    
    // Function to disable/enable tracking sheet
    function updateTrackingSheetState(isDisabled = false, customMessage = null) {
        if (trackingSheetLink && trackingSheetStatus) {
            if (isDisabled) {
                trackingSheetLink.style.opacity = '0.5';
                trackingSheetLink.style.pointerEvents = 'none';
                trackingSheetLink.classList.add('cursor-not-allowed');
                trackingSheetStatus.textContent = customMessage || 'Changes detected - auto-saving...';
            } else {
                trackingSheetLink.style.opacity = '1';
                trackingSheetLink.style.pointerEvents = 'auto';
                trackingSheetLink.classList.remove('cursor-not-allowed');
                // Keep the original status text (with batch info if exists)
                const originalText = trackingSheetStatus.dataset.originalText || 'Generate tracking document';
                trackingSheetStatus.textContent = originalText;
            }
        }
    }
    
    // Store original status text
    if (trackingSheetStatus) {
        trackingSheetStatus.dataset.originalText = trackingSheetStatus.textContent;
    }
    
    // Auto-save functionality
    let autoSaveTimeout;
    
    function autoSaveForm() {
        if (hasUnsavedChanges) {
            console.log('Auto-saving form...');
            
            // Update tracking sheet status to show saving
            updateTrackingSheetState(true, 'Saving changes...');
            
            // Trigger form submission silently
            const updateBtn = document.getElementById('updateBtn');
            if (updateBtn && !updateBtn.disabled) {
                updateBtn.click();
            }
        }
    }
    
    // Monitor form changes
    const formInputs = form.querySelectorAll('input, select, textarea');
    formInputs.forEach(input => {
        input.addEventListener('change', function() {
            hasUnsavedChanges = true;
            updateTrackingSheetState(true);
            
            // Clear existing timeout and set new one for auto-save
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSaveForm, 1500); // Auto-save after 1.5 seconds of inactivity
        });
        
        input.addEventListener('input', function() {
            hasUnsavedChanges = true;
            updateTrackingSheetState(true);
            
            // Clear existing timeout and set new one for auto-save
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSaveForm, 1500); // Auto-save after 1.5 seconds of inactivity
        });
    });
    
    // Initialize tracking sheet as disabled on page load
    updateTrackingSheetState(true);

    // Force correct selection of dropdown values
    setTimeout(function() {
        // Force Land Use Type selection
        const landUseType = document.getElementById('land_use_type');
        const landUseValue = '{{ old("land_use_type", $fileIndexing->land_use_type ?? "") }}';
        if (landUseType && landUseValue) {
            landUseType.value = landUseValue;
            console.log('Set Land Use Type to:', landUseValue);
        }
        
        // Force LGA selection
        const lga = document.getElementById('lga');
        const lgaValue = '{{ old("lga", $fileIndexing->lga ?? "") }}';
        if (lga && lgaValue) {
            lga.value = lgaValue;
            console.log('Set LGA to:', lgaValue);
        }
        
        // Force Registry selection
        const registry = document.getElementById('registry');
        const registryValue = '{{ old("registry", $fileIndexing->registry ?? "") }}';
        if (registry && registryValue) {
            registry.value = registryValue;
            console.log('Set Registry to:', registryValue);
        }
        
        // Log current selections for debugging
        console.log('Current form selections after force-set:');
        console.log('Land Use Type:', landUseType?.value);
        console.log('LGA:', lga?.value);
        console.log('Registry:', registry?.value);
    }, 100);

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined' && lucide.createIcons) {
        lucide.createIcons();
    }
});

// Debug function to show current values
function showCurrentValues() {
    const values = {
        'File Number': document.getElementById('file_number')?.value || 'N/A',
        'File Title': document.getElementById('file_title')?.value || 'N/A',
        'Land Use Type': document.getElementById('land_use_type')?.value || 'N/A',
        'Plot Number': document.getElementById('plot_number')?.value || 'N/A',
        'TP Number': document.getElementById('tp_no')?.value || 'N/A',
        'LPKN Number': document.getElementById('lpkn_no')?.value || 'N/A',
        'District': document.getElementById('district')?.value || 'N/A',
        'LGA': document.getElementById('lga')?.value || 'N/A',
        'Registry': document.getElementById('registry')?.value || 'N/A',
        'Batch Number': document.getElementById('batch_no')?.value || 'N/A',
        'Tracking ID': document.getElementById('tracking_id')?.value || 'N/A',
        'Location': document.getElementById('location')?.value || 'N/A'
    };
    
    let message = 'Current Form Values:\n\n';
    for (let [key, value] of Object.entries(values)) {
        message += `${key}: ${value}\n`;
    }
    
    // Also show backend values
    message += '\n--- Backend Values ---\n';
    message += `Land Use Type (Backend): {{ $fileIndexing->land_use_type ?? 'NULL' }}\n`;
    message += `LGA (Backend): {{ $fileIndexing->lga ?? 'NULL' }}\n`;
    message += `Registry (Backend): {{ $fileIndexing->registry ?? 'NULL' }}\n`;
    
    // Show property records info
    message += '\n--- Property Records ---\n';
    message += `Existing Records Count: ${existingPropertyRecords.length}\n`;
    if (existingPropertyRecords.length > 0) {
        message += `Sample Record Keys: ${Object.keys(existingPropertyRecords[0]).join(', ')}\n`;
        message += `Transaction Type: ${existingPropertyRecords[0].transaction_type || 'N/A'}\n`;
        message += `First Party Fields: ${Object.keys(existingPropertyRecords[0]).filter(key => key.toLowerCase().includes('grantor') || key.toLowerCase().includes('assignor') || key.toLowerCase().includes('mortgagor')).join(', ')}\n`;
    }
    
    // Test API call with transaction order verification
    const fileNumber = document.getElementById('file_number').value;
    if (fileNumber) {
        message += `\nTesting API call for file number: ${fileNumber}`;
        fetch(`/api/property-records/check/${encodeURIComponent(fileNumber)}`)
            .then(response => response.json())
            .then(apiData => {
                console.log('API Test Response:', apiData);
                if (apiData.records && apiData.records.length > 0) {
                    console.log('API Record Fields:', Object.keys(apiData.records[0]));
                    console.log('API Sample Record:', apiData.records[0]);
                    
                    // Add transaction order information to debug message
                    let orderMessage = '\n--- Transaction Order (First Added → Last Added) ---\n';
                    apiData.records.forEach((record, index) => {
                        const createdAt = record.created_at ? new Date(record.created_at).toLocaleString() : 'No date';
                        const transactionType = record.transaction_type || record.transactionType || 'No type';
                        orderMessage += `Transaction ${index + 1}: ${transactionType} (${createdAt})\n`;
                    });
                    
                    // Show complete debug info
                    alert(message + orderMessage);
                } else {
                    alert(message);
                }
            })
            .catch(error => {
                console.error('API Test Failed:', error);
                alert(message);
            });
    } else {
        alert(message);
    }
    
    console.log('Form Values:', values);
    console.log('Existing Property Records:', existingPropertyRecords);
}
</script>

<!-- Include Global File Number Modal -->
@include('components.global-fileno-modal')

<!-- Include Property Transaction Modal -->
@include('fileindexing.partial.property_transaction_modal')

<!-- Global File Number Modal JavaScript -->
<script src="{{ asset('js/global-fileno-modal.js') }}"></script>

<!-- Property Transaction Logic for Edit Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('=== Edit page DOMContentLoaded ===');
    
    const propertyTransactionBtn = document.getElementById('property-transaction-btn');
    const propertyTransactionBtnText = document.getElementById('property-transaction-btn-text');
    let existingPropertyRecords = [];
    
    console.log('Property transaction button found:', !!propertyTransactionBtn);
    console.log('Modal functions available:');
    console.log('- window.openPropertyTransactionModal:', typeof window.openPropertyTransactionModal);
    console.log('- window.closePropertyTransactionModal:', typeof window.closePropertyTransactionModal);
    
    // Wait a bit for modal functions to load
    setTimeout(() => {
        console.log('After timeout - Modal functions available:');
        console.log('- window.openPropertyTransactionModal:', typeof window.openPropertyTransactionModal);
        console.log('- window.closePropertyTransactionModal:', typeof window.closePropertyTransactionModal);
    }, 500);
    
    // Check if property records exist for this file number
    async function checkExistingPropertyRecords() {
        const fileNumber = document.getElementById('file_number').value;
        
        if (!fileNumber) {
            console.log('No file number available');
            return;
        }
        
        try {
            // Make API call to check for existing property records
            const response = await fetch(`/api/property-records/check/${encodeURIComponent(fileNumber)}`);
            const data = await response.json();
            
            if (data.success && data.records && data.records.length > 0) {
                // Records exist - show "Update" button
                propertyTransactionBtnText.textContent = 'Update Property Transaction Details';
                existingPropertyRecords = data.records;
                console.log('Found existing property records:', data.records.length);
                console.log('Records data:', data.records);
                // Log first record structure for debugging
                if (data.records.length > 0) {
                    console.log('First record structure:', Object.keys(data.records[0]));
                    console.log('First record values:', data.records[0]);
                }
            } else {
                // No records - show "Add" button
                propertyTransactionBtnText.textContent = 'Add Property Transaction Details';
                existingPropertyRecords = [];
                console.log('No existing property records found');
                console.log('API Response:', data);
            }
        } catch (error) {
            console.error('Error checking property records:', error);
            // Default to "Add" on error
            propertyTransactionBtnText.textContent = 'Add Property Transaction Details';
            existingPropertyRecords = [];
        }
    }
    
    // Property transaction button click handler
    if (propertyTransactionBtn) {
        propertyTransactionBtn.addEventListener('click', function() {
            console.log('=== Property transaction button clicked ===');
            
            // Check if modal element exists
            const modal = document.getElementById('property-transaction-dialog');
            console.log('Modal element found:', !!modal);
            
            if (!modal) {
                alert('Property transaction modal not found in the page. Please refresh and try again.');
                return;
            }
            
            // Gather file indexing data safely with correct field IDs
            const district = (document.getElementById('district') && document.getElementById('district').value) || '';
            const lga = (document.getElementById('lga') && document.getElementById('lga').value) || '';
            
            // Construct property description from district and lga
            const propertyDescription = [district, lga].filter(Boolean).join(', ');
            
            const fileIndexingData = {
                id: {{ $fileIndexing->id }},
                file_number: (document.getElementById('file_number') && document.getElementById('file_number').value) || '',
                file_title: (document.getElementById('file_title') && document.getElementById('file_title').value) || '',
                plot_no: (document.getElementById('plot_number') && document.getElementById('plot_number').value) || '',
                tp_no: (document.getElementById('tp_no') && document.getElementById('tp_no').value) || '',
                lpkn_no: (document.getElementById('lpkn_no') && document.getElementById('lpkn_no').value) || '',
                lga: lga,
                district: district,
                property_description: propertyDescription,
                land_use_type: (document.getElementById('land_use_type') && document.getElementById('land_use_type').value) || '',
                existing_records: existingPropertyRecords
            };
            
            console.log('File indexing data prepared:', fileIndexingData);
            
            // Try using the global function first
            if (typeof window.openPropertyTransactionModal === 'function') {
                // Add existing records to fileIndexingData
                fileIndexingData.existing_records = existingPropertyRecords;
                console.log('Opening modal with existing records:', existingPropertyRecords.length);
                console.log('Using global openPropertyTransactionModal function');
                try {
                    window.openPropertyTransactionModal(fileIndexingData);
                    console.log('Modal opened successfully via global function');
                } catch (error) {
                    console.error('Error calling global openPropertyTransactionModal:', error);
                    // Fallback to manual display
                    console.log('Falling back to manual modal display');
                    modal.classList.remove('hidden');
                    modal.style.display = 'flex';
                    modal.style.zIndex = '10000';
                }
            } else {
                console.error('Global openPropertyTransactionModal function not found');
                console.log('Available window functions:', Object.keys(window).filter(key => key.toLowerCase().includes('property')));
                
                // Fallback: manually show modal
                console.log('Manually opening modal...');
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.style.zIndex = '10000';
                
                console.log('Modal display style:', modal.style.display);
                console.log('Modal classes:', modal.className);
            }
        });
    } else {
        console.error('Property transaction button not found');
    }
    
    // Check for existing records on page load
    checkExistingPropertyRecords();
    
    // Re-check when file number changes
    const fileNumberInput = document.getElementById('file_number');
    if (fileNumberInput) {
        fileNumberInput.addEventListener('change', checkExistingPropertyRecords);
    }
    
    // DEBUG: Test modal button
    const debugModalBtn = document.getElementById('debug-modal-btn');
    if (debugModalBtn) {
        debugModalBtn.addEventListener('click', function() {
            console.log('=== MODAL DEBUG TEST ===');
            
            const modal = document.getElementById('property-transaction-dialog');
            console.log('1. Modal element:', modal);
            console.log('2. Modal classes:', modal ? modal.className : 'N/A');
            console.log('3. Modal style display:', modal ? modal.style.display : 'N/A');
            
            console.log('4. Window functions:');
            console.log('   - openPropertyTransactionModal:', typeof window.openPropertyTransactionModal);
            console.log('   - closePropertyTransactionModal:', typeof window.closePropertyTransactionModal);
            
            if (modal) {
                console.log('5. Forcing modal display...');
                modal.classList.remove('hidden');
                modal.style.display = 'flex';
                modal.style.zIndex = '10000';
                console.log('6. Modal should be visible now');
                console.log('7. Final modal style:', modal.style.cssText);
            } else {
                console.error('Modal element not found!');
                alert('Modal element not found! Check browser console for details.');
            }
        });
    }
});
</script>

@endsection