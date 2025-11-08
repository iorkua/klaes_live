@extends('layouts.app')

@section('page-title')
    {{ $PageTitle ?? __('KLAES') }}
@endsection

@section('content')
<style>
    /* Minimal, clean table styles */
    .wrap { padding: 16px; }
    .controls { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .tabs { display: flex; gap: 8px; }
    .tab-btn { padding: 8px 12px; border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 6px; cursor: pointer; font-weight: 600; }
    .tab-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
    .search { max-width: 320px; flex: 1; }
    .search input { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; }
 
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
    .card-header { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 700; color: #111827; }
    .table-container { overflow-x: auto; }
    table.simple { width: 100%; border-collapse: collapse; font-size: 14px; }
    thead th { background: #f9fafb; text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    tbody td { padding: 10px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; white-space: nowrap; }
    tr:hover td { background: #fafafa; }

    .actions a { color: #2563eb; text-decoration: none; margin-right: 10px; }
    .actions a:last-child { margin-right: 0; }
    .muted { color: #6b7280; }

    /* Simple dropdown */
    .dd { position: relative; display: inline-block; }
    .dd-btn { border: 1px solid #d1d5db; background: #fff; color: #374151; border-radius: 6px; padding: 4px 8px; cursor: pointer; font-weight: 700; }
    .dd-btn:hover { background: #f3f4f6; }
    .dd-menu { display: none; position: fixed; min-width: 180px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 10px 20px rgba(0,0,0,0.08); z-index: 9999; overflow: hidden; }
    .dd-menu.show { display: block; }
    .dd-item { display: block; padding: 10px 12px; color: #374151; text-decoration: none; font-size: 14px; }
    .dd-item:hover { background: #f3f4f6; }
    .dd-item.disabled { color: #9ca3af; pointer-events: none; }

    @media (max-width: 640px) {
        .search { max-width: none; width: 100%; }
    }
</style>

<div class="flex-1 overflow-auto">
    @include($headerPartial ?? 'admin.header')

    <div class="wrap">
        <div class="card">
            <div class="card-header">RoFO Applications</div>
            <div class="controls">
                <div class="tabs">
                    <button id="btn-not" class="tab-btn active" onclick="setTab('not')">Not Generated</button>
                    <button id="btn-gen" class="tab-btn" onclick="setTab('gen')">Generated</button>
                </div>
                <div class="search">
                    <input id="search" type="text" placeholder="Search..." oninput="applySearch()" />
                </div>
            </div>

            <!-- Not Generated Table -->
            <div id="wrap-not" class="table-container">
                <table id="table-not" class="simple">
                    <thead>
                        <tr>
                            <th>ST FileNo</th>
                            <th>Scheme No</th>
                            <th>Unit Owner</th>
                            <th>LGA</th>
                            <th>Unit/Section/Block</th>
                            <th>Land Use</th>
                            <th>ST Memo Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subapplications->filter(function($app) { return empty($app->rofo_no); }) as $unitApplication)
                        <tr>
                            <td>{{ $unitApplication->fileno ?? 'N/A' }}</td>
                            <td>{{ $unitApplication->scheme_no ?? 'N/A' }}</td>
                            <td>
                                @if(!empty($unitApplication->multiple_owners_names) && json_decode($unitApplication->multiple_owners_names))
                                    @php
                                        $owners = json_decode($unitApplication->multiple_owners_names);
                                        $firstOwner = isset($owners[0]) ? $owners[0] : 'N/A';
                                    @endphp
                                    <span title="Click to view all owners" style="cursor:pointer" onclick='showOwners(@json($owners))'>{{ $firstOwner }}</span>
                                @else
                                    {{ $unitApplication->owner_name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $unitApplication->property_lga ?? 'N/A' }}</td>
                            <td>{{ $unitApplication->unit_number ?? '' }}-{{ $unitApplication->floor_number ?? '' }}-{{ $unitApplication->block_number ?? '' }}</td>
                            <td>{{ $unitApplication->land_use ?? 'N/A' }}</td>
                            <td>
                                @if($unitApplication->has_st_memo ?? false)
                                    <span>Generated</span>
                                @else
                                    <span class="muted">Not Generated</span>
                                @endif
                            </td>
                            <td>{{ $unitApplication->created_at ? date('d-m-Y', strtotime($unitApplication->created_at)) : 'N/A' }}</td>
                            <td class="actions">
                                <div class="dd">
                                    <button type="button" class="dd-btn" onclick="toggleDD(this)">⋮</button>
                                    <div class="dd-menu">
                                        <a href="{{ route('sectionaltitling.viewrecorddetail_sub', $unitApplication->id) }}" class="dd-item">View Application</a>
                                        @if($unitApplication->has_st_memo ?? false)
                                            <a href="{{ route('programmes.generate_rofo', $unitApplication->id) }}" class="dd-item">Generate RoFO</a>
                                        @else
                                            <span class="dd-item disabled">Generate RoFO</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="muted">No records pending RoFO generation</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Generated Table -->
            <div id="wrap-gen" class="table-container" style="display:none;">
                <table id="table-gen" class="simple">
                    <thead>
                        <tr>
                            <th>ST FileNo</th>
                            <th>RoFO No</th>
                            <th>Scheme No</th>
                            <th>Unit Owner</th>
                            <th>LGA</th>
                            <th>Unit/Section/Block</th>
                            <th>Land Use</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subapplications->filter(function($app) { return !empty($app->rofo_no); }) as $unitApplication)
                        <tr>
                            <td>{{ $unitApplication->fileno ?? 'N/A' }}</td>
                            <td><strong>{{ $unitApplication->rofo_no ?? 'N/A' }}</strong></td>
                            <td>{{ $unitApplication->scheme_no ?? 'N/A' }}</td>
                            <td>
                                @if(!empty($unitApplication->multiple_owners_names) && json_decode($unitApplication->multiple_owners_names))
                                    @php
                                        $owners = json_decode($unitApplication->multiple_owners_names);
                                        $firstOwner = isset($owners[0]) ? $owners[0] : 'N/A';
                                    @endphp
                                    <span title="Click to view all owners" style="cursor:pointer" onclick='showOwners(@json($owners))'>{{ $firstOwner }}</span>
                                @else
                                    {{ $unitApplication->owner_name ?? 'N/A' }}
                                @endif
                            </td>
                            <td>{{ $unitApplication->property_lga ?? 'N/A' }}</td>
                            <td>{{ $unitApplication->unit_number ?? '' }}-{{ $unitApplication->floor_number ?? '' }}-{{ $unitApplication->block_number ?? '' }}</td>
                            <td>{{ $unitApplication->land_use ?? 'N/A' }}</td>
                            <td>{{ $unitApplication->created_at ? date('d-m-Y', strtotime($unitApplication->created_at)) : 'N/A' }}</td>
                            <td class="actions">
                                <div class="dd">
                                    <button type="button" class="dd-btn" onclick="toggleDD(this)">⋮</button>
                                    <div class="dd-menu">
                                        <a href="{{ route('sectionaltitling.viewrecorddetail_sub', $unitApplication->id) }}" class="dd-item">View Application</a>
                                        <a href="{{ route('programmes.view_rofo', $unitApplication->id) }}" class="dd-item">View RoFO</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="muted">No generated RoFO applications found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include($footerPartial ?? 'admin.footer')
</div>

<script>
    let currentTab = 'not';

    function setTab(tab) {
        currentTab = tab;
        document.getElementById('btn-not').classList.toggle('active', tab === 'not');
        document.getElementById('btn-gen').classList.toggle('active', tab === 'gen');
        document.getElementById('wrap-not').style.display = tab === 'not' ? 'block' : 'none';
        document.getElementById('wrap-gen').style.display = tab === 'gen' ? 'block' : 'none';
        applySearch();
        closeAllDD();
    }

    function applySearch() {
        const q = (document.getElementById('search').value || '').toLowerCase();
        const tableId = currentTab === 'not' ? 'table-not' : 'table-gen';
        const rows = document.querySelectorAll(`#${tableId} tbody tr`);
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    }

    function showOwners(owners) {
        alert('Owners:\n\n' + owners.join('\n'));
    }

    function closeAllDD() {
        document.querySelectorAll('.dd-menu.show').forEach(m => m.classList.remove('show'));
    }

    function toggleDD(btn) {
        const menu = btn.nextElementSibling;
        if (!menu) return;
        const wasOpen = menu.classList.contains('show');
        closeAllDD();
        if (wasOpen) return;
        // Position fixed to avoid clipping by overflow containers
        const r = btn.getBoundingClientRect();
        const mW = 200; // approx width
        const leftPref = r.right - mW;
        let left = leftPref;
        if (left < 8) left = r.left; // shift inside viewport
        const maxLeft = window.innerWidth - mW - 8;
        if (left > maxLeft) left = maxLeft;
        let top = r.bottom + 6;
        const scrollY = window.scrollY || document.documentElement.scrollTop;
        const scrollX = window.scrollX || document.documentElement.scrollLeft;
        menu.style.left = (left + scrollX) + 'px';
        menu.style.top = (top + scrollY) + 'px';
        menu.classList.add('show');
    }

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dd')) closeAllDD();
    });
    window.addEventListener('scroll', closeAllDD, true);
    window.addEventListener('resize', closeAllDD);
</script>
@endsection