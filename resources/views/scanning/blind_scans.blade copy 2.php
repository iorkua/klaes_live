@extends('layouts.app')
@section('page-title')
{{ __('Blind Scannings') }}
@endsection

@section('content')
  <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
  <style>
    body { background:#ffffff; color:#0f172a; }
    .text-muted-foreground { color:#64748b; }
    .bg-card { background:#ffffff; }
    .bg-muted { background:#f1f5f9; }
    .border { border-color:#e2e8f0; }
    .progress-bar { transition: width .3s ease; }
    .row:hover { background:#f8fafc; }
    .pill{font-size:11px;border:1px solid #e2e8f0;background:#f8fafc;border-radius:999px;padding:.15rem .5rem}
    .btn { border:1px solid #e2e8f0; background:#fff; padding:.5rem .75rem; border-radius:.5rem; font-size:.9rem; }
    .btn:hover { background:#f8fafc; }
    .btn-primary{ background:#4f46e5; border-color:#4f46e5; color:#fff; }
    .btn-primary:hover{ background:#4338ca; }
    .btn-danger{ background:#dc2626; border-color:#dc2626; color:#fff; }
    .preview-box { min-height: 520px; } /* bigger preview */
  </style>
@php
// This page uses Laravel controllers for backend functionality
@endphp

<div class="flex-1 overflow-auto">
    <!-- Header -->
    @include('admin.header')
    <!-- Dashboard Content -->
    <div class="p-4 sm:p-6">

       <div class="container mx-auto py-6 space-y-6">
    <div>
      <h1 class="text-3xl font-bold">Blind Scanning Workflow — Upload & Migrate</h1>
      <p class="text-muted-foreground">
        Step 1: Enter the <b>File Number</b>. Step 2: Pick the whole <b>local folder</b> you scanned (it must include A4/A3 subfolders).
        Step 3: Click <b>Migrate</b>. The server will create <code>storage\app\public\EDMS\BLIND_SCAN\[FileNo]</code> and extract all contents.
      </p>
      <p class="text-sm mt-1">Destination: <code>storage\app\public\EDMS\BLIND_SCAN\[FileNo]</code></p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
      <!-- Left: Upload + Migrate (reduced width) -->
      <div class="bg-card border rounded-lg p-6 lg:col-span-2">
        <h2 class="text-xl font-bold mb-4"><i class="fa-solid fa-cloud-arrow-up mr-2"></i>Upload & Migrate</h2>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">File Number (target on server)</label>
            <input id="fileNo" type="text" class="w-full border rounded-md px-3 py-2" placeholder="e.g., RES-2025-0001" />
          </div>

          <div>
            <label class="block text-sm font-medium mb-1">Pick local folder</label>
            <input id="folderInput" type="file" webkitdirectory directory multiple class="border rounded-md px-3 py-2"/>
            <p class="text-xs text-muted-foreground mt-1">
              Use Chrome/Edge on desktop. The folder name should match the File Number (but we will allow migrate if the browser can't expose that name).
            </p>
          </div>

          <div id="status" class="text-sm text-muted-foreground">No folder selected.</div>

          <button id="migrateBtn" class="btn btn-primary w-full disabled:opacity-50" disabled>
            <i class="fa-solid fa-paper-plane mr-2"></i>Migrate to Server
          </button>
        </div>
      </div>

      <!-- Right: Server Browser & Logs (increased width) -->
      <div class="bg-card border rounded-lg p-0 lg:col-span-3">
        <div class="p-6 pb-2">
          <h2 class="text-xl font-bold">Server Storage & Logs</h2>
          <p class="text-muted-foreground">Browse <code>storage\app\public\EDMS\BLIND_SCAN</code>, preview files, and review migration logs.</p>
        </div>

        <div class="px-6 pb-0">
          <div class="flex gap-2">
            <button id="tabServer" class="btn">Server Browser</button>
            <button id="tabLogs" class="btn">Migration Logs</button>
          </div>
        </div>

        <!-- Server Browser -->
        <div id="serverPanel" class="p-6">
          <div class="flex items-center justify-between mb-3">
            <div>
              <div class="text-sm"><span class="mr-2">Path:</span><span id="srvPath" class="font-mono">/storage</span></div>
              <div id="srvCrumbs" class="text-xs text-muted-foreground mt-1">Root</div>
            </div>
            <button id="srvRefresh" class="btn"><i class="fa-solid fa-rotate mr-2"></i>Refresh</button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
            <!-- File list (reduced width) -->
            <div class="lg:col-span-2 border rounded-lg overflow-hidden">
              <table class="w-full text-sm">
                <thead class="bg-muted">
                  <tr class="border-b">
                    <th class="text-left p-3 w-10"></th>
                    <th class="text-left p-3">Name</th>
                    <th class="text-left p-3">Modified</th>
                    <th class="text-left p-3">Actions</th>
                  </tr>
                </thead>
                <tbody id="srvRows"></tbody>
              </table>
            </div>
            
            <!-- Preview (increased width) -->
            <div class="lg:col-span-3">
              <div class="border rounded-lg p-3">
                <div class="font-medium mb-2">Preview</div>
                <div id="previewBox" class="preview-box text-sm text-muted-foreground">
                  Select a PDF or image to preview.
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Logs -->
        <div id="logsPanel" class="p-6 hidden">
          <div id="logsContent"></div>
        </div>
      </div>
    </div>

    <!-- Progress Modal -->
    <div id="progressModal" class="fixed inset-0 bg-black/30 hidden items-center justify-center">
      <div class="bg-white border rounded-lg w-full max-w-md mx-4">
        <div class="p-6">
          <h3 id="progressTitle" class="text-xl font-bold">Working...</h3>
          <p id="progressSub" class="text-muted-foreground">Please wait</p>
        </div>
        <div class="px-6 pb-6">
          <div class="space-y-4">
            <div class="w-full bg-muted rounded-full h-2"><div class="progress-bar bg-indigo-600 h-2 rounded-full" style="width:0%"></div></div>
            <div id="progressText" class="text-sm text-center text-muted-foreground">0%</div>
            <div id="progressDone" class="flex items-center justify-center gap-2 text-green-700 hidden">
              <i class="fa-solid fa-circle-check"></i><span>Done!</span>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
    </div>

    <!-- Footer -->
    @include('admin.footer')
</div>


  <script>
    // ---- Config
    const MIGRATE_ENDPOINT = window.location.pathname;
    const API_LIST = '{{ route("blind_scan.api.list") }}';
    const API_LOGS = '{{ route("blind_scan.api.logs") }}';

    // ---- DOM
    const fileNoEl   = document.getElementById('fileNo');
    const folderEl   = document.getElementById('folderInput');
    const statusEl   = document.getElementById('status');
    const migrateBtn = document.getElementById('migrateBtn');

    // Progress modal helpers
    function openProgress(title, sub, pct=0){
      document.getElementById('progressTitle').textContent = title;
      document.getElementById('progressSub').textContent = sub || '';
      document.querySelector('#progressModal .progress-bar').style.width = pct+'%';
      document.getElementById('progressText').textContent = pct+'%';
      document.getElementById('progressDone').classList.add('hidden');
      const m = document.getElementById('progressModal');
      m.classList.remove('hidden'); m.classList.add('flex');
    }
    function setProgress(pct){
      document.querySelector('#progressModal .progress-bar').style.width = pct+'%';
      document.getElementById('progressText').textContent = pct+'%';
    }
    function doneProgress(){
      document.getElementById('progressDone').classList.remove('hidden');
      setTimeout(()=> {
        const m = document.getElementById('progressModal');
        m.classList.add('hidden'); m.classList.remove('flex');
      }, 600);
    }

    // ---- Upload state
    let selectedFiles = [];
    let ready = false;
    let localParentName = null; // may be null when not detectable

    const norm = s => (s || '').trim();

    // Try to infer a root name from the picked files (may return null)
    function detectSelectedRoot(files){
      const segs = [];
      for (const f of files) {
        const rel = f.webkitRelativePath || f.name;
        const first = (rel.split('/')[0] || '').trim();
        if (first) segs.push(first.toLowerCase());
      }
      if (!segs.length) return null;
      const unique = Array.from(new Set(segs));
      // If the first segment is consistent AND not "a3"/"a4", assume it's the parent
      if (unique.length === 1 && unique[0] !== 'a3' && unique[0] !== 'a4') {
        return unique[0];
      }
      // Common case: browser only exposes A3/A4 as first segment → cannot infer
      return null;
    }

    function validateUploadSection(){
      const typedRaw = norm(fileNoEl.value);
      const typed = typedRaw.toLowerCase();
      const hasFiles = selectedFiles.length > 0;

      if (!hasFiles){
        statusEl.textContent = 'No folder selected.';
        migrateBtn.disabled = true; ready = false; return;
      }

      const inferred = detectSelectedRoot(selectedFiles); // may be null
      localParentName = inferred;

      if (!typed){
        statusEl.textContent = inferred
          ? `Detected local folder "${inferred}". Enter the File Number to proceed.`
          : 'Folder selected. Enter the File Number to proceed.';
        migrateBtn.disabled = true; ready = false; return;
      }

      if (inferred){
        const match = (typed === inferred);
        if (!match){
          statusEl.innerHTML = `Folder appears to be <code>${inferred}</code> but you typed <code>${typedRaw}</code>.`;
          migrateBtn.disabled = true; ready = false; return;
        }
        statusEl.innerHTML = `Ready: <b>${typedRaw}</b> will be migrated with all subfolders and files (${selectedFiles.length} items).`;
        migrateBtn.disabled = false; ready = true; return;
      } else {
        statusEl.innerHTML = `Ready: <b>${typedRaw}</b> (parent name not detectable by browser). `
          + `All selected items (${selectedFiles.length}) will be zipped and migrated.`;
        migrateBtn.disabled = false; ready = true; return;
      }
    }

    folderEl.addEventListener('change', () => {
      selectedFiles = Array.from(folderEl.files || []);
      validateUploadSection();
    });
    fileNoEl.addEventListener('input', validateUploadSection);

    // ---- Upload files directly without zipping
    async function migrateNow(){
      if (!ready) return;

      const folderName = norm(fileNoEl.value);
      openProgress('Preparing Upload', `${folderName} → storage\\app\\public\\EDMS\\BLIND_SCAN`, 5);

      try {
        const form = new FormData();
        form.append('_token', '{{ csrf_token() }}');
        form.append('folderName', folderName);
        form.append('uploadMethod', 'direct');

        // Add all files with their relative paths
        let fileCount = 0;
        for (const file of selectedFiles) {
          const relativePath = file.webkitRelativePath || file.name;
          form.append(`files[${fileCount}]`, file);
          form.append(`paths[${fileCount}]`, relativePath);
          fileCount++;
        }

        setProgress(20);

        const res = await fetch(MIGRATE_ENDPOINT, { 
          method: 'POST', 
          body: form,
          // Add progress tracking
          onUploadProgress: (progressEvent) => {
            if (progressEvent.lengthComputable) {
              const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
              setProgress(20 + (percentCompleted * 0.7)); // 20% to 90%
            }
          }
        });

        setProgress(95);

        const data = await res.json().catch(()=>({}));
        if (res.ok && data.ok) {
          setProgress(100);
          doneProgress();
          // refresh server view & logs
          await Promise.all([ fetchServerList(currentSrvPath), fetchLogs() ]);
          statusEl.innerHTML = `<span class="text-green-700">Migration complete.</span> Saved to <code>${data.serverPath || '/storage/'+folderName}</code>.`;
          // Clear selection
          folderEl.value = '';
          selectedFiles = [];
          ready = false;
          migrateBtn.disabled = true;
        } else {
          document.getElementById('progressModal').classList.add('hidden');
          const msg = (data && (data.error || data.message)) || `HTTP ${res.status}`;
          alert('Migration failed: ' + msg);
        }
      } catch (error) {
        document.getElementById('progressModal').classList.add('hidden');
        alert('Migration failed: ' + error.message);
      }
    }

    migrateBtn.addEventListener('click', migrateNow);

    // ----------------------------
    // Server browser + logs (GET)
    // ----------------------------
    let currentSrvPath = ''; // '' means /storage

    function srvRow(html){ const tr=document.createElement('tr'); tr.className='border-b row'; tr.innerHTML=html; return tr; }
    function fmtBytes(n){
      if (!n && n!==0) return '-';
      if (n < 1024) return n + ' B';
      if (n < 1024*1024) return (n/1024).toFixed(1)+' KB';
      return (n/(1024*1024)).toFixed(1)+' MB';
    }
    function fmtDate(ts){
      if (!ts) return '-';
      const d = new Date(ts*1000);
      return d.toLocaleString();
    }
    function clearPreview(){
      document.getElementById('previewBox').innerHTML =
        `<div class="text-sm text-muted-foreground">Select a PDF or image to preview.</div>`;
    }
    function previewFile(href, name){
      const box = document.getElementById('previewBox');
      const ext = (name.split('.').pop() || '').toLowerCase();
      box.innerHTML = '';
      if (['png','jpg','jpeg','gif','webp'].includes(ext)) {
        const img = document.createElement('img');
        img.src = href; img.alt = name; img.className = 'max-w-full rounded border';
        box.appendChild(img);
      } else if (ext === 'pdf') {
        const iframe = document.createElement('iframe');
        iframe.src = href; iframe.className='w-full'; iframe.style.minHeight='520px';
        box.appendChild(iframe);
      } else {
        box.innerHTML = `<div class="text-sm">No inline preview for <code>${ext||'unknown'}</code>. <a class="text-blue-700 underline" href="${href}" target="_blank" rel="noopener">Open</a></div>`;
      }
    }

    async function fetchServerList(subPath=''){
      const url = new URL(API_LIST, window.location.href);
      if (subPath) url.searchParams.set('path', subPath);
      const res = await fetch(url);
      const data = await res.json();
      if (!res.ok || !data.ok) { alert(data.error || ('HTTP '+res.status)); return; }

      currentSrvPath = data.sub || '';
      document.getElementById('srvPath').textContent = '/storage/EDMS/BLIND_SCAN' + (currentSrvPath ? '/'+currentSrvPath : '');

      const crumbs = data.crumbs || [];
      const crumbsEl = document.getElementById('srvCrumbs');
      if (!crumbs.length) {
        crumbsEl.textContent = 'Root';
      } else {
        crumbsEl.innerHTML = crumbs.map((c,i)=>(
          `<button class="text-blue-700 hover:underline" data-path="${c.path}">${c.name}</button>${i<crumbs.length-1?' / ':''}`
        )).join('');
        crumbsEl.querySelectorAll('button').forEach(b=>{
          b.addEventListener('click', ()=> fetchServerList(b.dataset.path));
        });
      }

      const tbody = document.getElementById('srvRows');
      tbody.innerHTML = '';

      if (currentSrvPath){
        const up = currentSrvPath.split('/').slice(0,-1).join('/');
        const trUp = srvRow(`
          <td class="p-3"><i class="fa-regular fa-folder"></i></td>
          <td class="p-3"><button class="srv-nav text-blue-700 hover:underline" data-path="${up}">Go Back</button></td>
          <td class="p-3">${fmtDate(null)}</td>
          <td class="p-3"></td>
        `);
        trUp.querySelector('.srv-nav').addEventListener('click', ()=> fetchServerList(up));
        tbody.appendChild(trUp);
      }

      const items = data.items || [];
      if (!items.length){
        tbody.appendChild(srvRow(`<td class="p-3 text-muted-foreground" colspan="4">Empty</td>`));
      } else {
        for (const item of items){
          if (item.type === 'dir'){
            const tr = srvRow(`
              <td class="p-3"><i class="fa-regular fa-folder"></i></td>
              <td class="p-3"><button class="srv-nav text-blue-700 hover:underline" data-path="${(currentSrvPath?currentSrvPath+'/':'')+item.name}">${item.name}</button></td>
              <td class="p-3">${fmtDate(item.mtime)}</td>
              <td class="p-3"></td>
            `);
            tr.querySelector('.srv-nav').addEventListener('click', ()=> fetchServerList((currentSrvPath?currentSrvPath+'/':'')+item.name));
            tbody.appendChild(tr);
          } else {
            const href = item.href || '#';
            const tr = srvRow(`
              <td class="p-3"><i class="fa-regular fa-file"></i></td>
              <td class="p-3">${item.name}</td>
              <td class="p-3">${fmtDate(item.mtime)}</td>
              <td class="p-3">
                <div class="flex gap-2">
                  <button class="btn srv-preview text-xs" data-href="${href}" data-name="${item.name}">Preview</button>
                  <a class="btn text-xs" href="${href}" target="_blank" rel="noopener">Open</a>
                </div>
              </td>
            `);
            tr.querySelector('.srv-preview').addEventListener('click', (e)=> previewFile(e.currentTarget.dataset.href, e.currentTarget.dataset.name));
            tbody.appendChild(tr);
          }
        }
      }
      clearPreview();
    }

    async function fetchLogs(){
      const res = await fetch(API_LOGS);
      const data = await res.json();
      if (!res.ok || !data.ok) { alert(data.error || ('HTTP '+res.status)); return; }
      const logs = data.logs || [];
      const root = document.getElementById('logsContent');
      if (!logs.length) {
        root.innerHTML = `
          <div class="text-center py-8">
            <i class="fa-solid fa-clock-rotate-left text-3xl text-muted-foreground mx-auto mb-4"></i>
            <p class="text-muted-foreground">No migrations yet</p>
            <p class="text-sm text-muted-foreground">After migrating, entries will appear here.</p>
          </div>`;
        return;
      }
      root.innerHTML = `
        <div class="border rounded-lg overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-muted">
              <tr class="border-b">
                <th class="text-left p-3">When</th>
                <th class="text-left p-3">Parent Folder</th>
                <th class="text-left p-3">Server Path</th>
              </tr>
            </thead>
            <tbody>
              ${logs.map(m=>`
                <tr class="border-b">
                  <td class="p-3">${m.when || '-'}</td>
                  <td class="p-3 font-mono">${m.folder || '-'}</td>
                  <td class="p-3 font-mono"><a class="text-blue-700 hover:underline" href="${m.serverPath||'#'}" target="_blank" rel="noopener">${m.serverPath||'-'}</a></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>`;
    }

    // Tabs
    const serverPanel = document.getElementById('serverPanel');
    const logsPanel   = document.getElementById('logsPanel');
    document.getElementById('tabServer').addEventListener('click', ()=>{ serverPanel.classList.remove('hidden'); logsPanel.classList.add('hidden'); });
    document.getElementById('tabLogs').addEventListener('click', ()=>{ logsPanel.classList.remove('hidden'); serverPanel.classList.add('hidden'); });
    document.getElementById('srvRefresh').addEventListener('click', ()=> fetchServerList(currentSrvPath));

    // Boot
    (async function(){
      await fetchServerList('');
      await fetchLogs();
    })();
  </script>
@endsection