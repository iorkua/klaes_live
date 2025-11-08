<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <!-- Total Archived Files -->
    <div class="card">
        <div class="p-4 pb-2">
            <h3 class="text-sm font-medium">Total Archived Files</h3>
        </div>
        <div class="p-4 pt-0">
            <div class="text-2xl font-bold">{{ number_format($stats['total_archived']) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Completed page typed files</p>
        </div>
    </div>

    <!-- Recently Added -->
    <div class="card">
        <div class="p-4 pb-2">
            <h3 class="text-sm font-medium">Recently Added</h3>
        </div>
        <div class="p-4 pt-0">
            <div class="text-2xl font-bold">{{ number_format($stats['recently_added']) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Added in the last 30 days</p>
        </div>
    </div>

    <!-- Total Pages -->
    <div class="card">
        <div class="p-4 pb-2">
            <h3 class="text-sm font-medium">Total Pages</h3>
        </div>
        <div class="p-4 pt-0">
            <div class="text-2xl font-bold">{{ number_format($stats['total_pages']) }}</div>
            <p class="text-xs text-muted-foreground mt-1">Digitally classified pages</p>
        </div>
    </div>

    <!-- Storage Used -->
    <div class="card">
        <div class="p-4 pb-2">
            <h3 class="text-sm font-medium">Storage Used</h3>
        </div>
        <div class="p-4 pt-0">
            <div class="text-2xl font-bold">{{ $stats['storage_used'] }}</div>
            <p class="text-xs text-muted-foreground mt-1">Of archived documents</p>
        </div>
    </div>
</div>