@extends('layouts.app')
@section('page-title')
    {{ __('File Digital Archive') }}
@endsection

@section('content')
    @include('filearchive.assets.style')
    <!-- Main Content -->
    <div class="flex-1 overflow-auto">
        <!-- Header -->
        @include('admin.header')
        <!-- Dashboard Content -->
        <div class="p-6">
            <div class="flex flex-col min-h-screen">
                <!-- Page Header -->
                @include('filearchive.partials.header')

                <div class="flex-1 p-4 sm:p-6 container mx-auto space-y-6">
                    <!-- Stats Cards -->
                    @include('filearchive.partials.stats')

                    <!-- Search Card -->
                    @include('filearchive.partials.search')

                    <!-- Files Display Area -->
                    @include('filearchive.partials.files_grid')
                </div>
            </div>

            <!-- File Details Dialog -->
            @include('filearchive.partials.file_details_modal')

            <!-- Document Viewer Dialog -->
            @include('filearchive.partials.document_viewer_modal')
        </div>
        <!-- Footer -->
        @include('admin.footer')
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Scripts --> 
    @include('filearchive.assets.js')
@endsection
