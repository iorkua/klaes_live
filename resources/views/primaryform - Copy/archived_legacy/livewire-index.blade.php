@extends('layouts.app')

@section('page-title')
    {{ __('Primary Application Form - Livewire v2.0') }}
@endsection

@section('content')
<!-- Force no-cache for development -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<div class="min-h-screen bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="bg-blue-100 border border-blue-300 rounded-lg p-4 mb-4">
            <h4 class="font-bold text-blue-800">✅ NEW LIVEWIRE VERSION</h4>
            <p class="text-blue-700">This is the new Livewire-powered form with CSV import functionality. No more JavaScript/HTMX conflicts!</p>
        </div>
        
        @livewire('primary-form')
    </div>
</div>

@livewireStyles
@livewireScripts

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    /* Hide flash messages after 5 seconds */
    .flash-message {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    /* Auto-hide flash messages */
    .flash-message {
        animation: slideIn 0.3s ease-out, slideOut 0.3s ease-in 4.7s forwards;
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
</style>

<script>
    // Auto-hide flash messages
    setTimeout(function() {
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach(function(message) {
            message.style.display = 'none';
        });
    }, 5000);
</script>
@endsection