@extends('layouts.tenant-admin')

@section('page-title', 'Media Library')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant-admin/media.css') }}">
@endpush

@section('content')
<div class="ta-media-page">
    <div class="ta-media-page__top">
        <p class="text-sm text-slate-600 m-0">Storage used: <strong>{{ number_format($storageUsed / 1048576, 2) }} MB</strong></p>
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input name="q" value="{{ request('q') }}" placeholder="Search files…" class="rounded-lg border-slate-300 text-sm">
            <select name="type" class="rounded-lg border-slate-300 text-sm">
                <option value="">All types</option>
                <option value="image" @selected(request('type') === 'image')>Images</option>
                <option value="video" @selected(request('type') === 'video')>Videos</option>
                <option value="pdf" @selected(request('type') === 'pdf')>PDF</option>
            </select>
            <button type="submit" class="px-3 py-1.5 bg-slate-800 text-white text-sm rounded-lg">Search</button>
        </form>
    </div>

    @if (session('success'))
        <p class="mb-4 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg px-3 py-2">{{ session('success') }}</p>
    @endif

    <div class="ta-media-grid" id="mediaGrid">
        {{-- Add image tile --}}
        <button type="button" class="ta-media-add" id="openUploadModal" aria-label="Add images">
            <span class="ta-media-add__icon">+</span>
            <span class="ta-media-add__label">Image</span>
        </button>

        @foreach ($files as $f)
            <article class="ta-media-card" data-media-id="{{ $f->id }}">
                <div class="ta-media-card__thumb">
                    @if ($f->type === 'image')
                        <img src="{{ \App\Models\MediaFile::normalizeStoredUrl($f->url()) }}?v={{ $f->updated_at?->timestamp }}" alt="{{ $f->name }}" data-media-img>
                    @else
                        <div class="ta-media-card__thumb--file w-full h-full">{{ $f->type }}</div>
                    @endif
                    <div class="ta-media-card__actions">
                        <label class="ta-media-card__btn ta-media-card__btn--upload" title="Re-upload">
                            <input type="file"
                                   class="hidden ta-reupload-input"
                                   accept="image/*,video/*,.pdf"
                                   data-url="{{ route('tenant-admin.media.reupload', $f) }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        </label>
                        <button type="button"
                                class="ta-media-card__btn ta-media-card__btn--danger ta-delete-media"
                                title="Remove"
                                data-url="{{ route('tenant-admin.media.destroy', $f) }}"
                                data-name="{{ $f->name }}">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m2 4h10M9 7h6m-6 0V5a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                        </button>
                    </div>
                </div>
                <div class="ta-media-card__meta">
                    <p title="{{ $f->name }}">{{ $f->name }}</p>
                    <p class="size">{{ $f->humanSize() }}</p>
                </div>
            </article>
        @endforeach
    </div>

    @if ($files->isEmpty())
        <p class="text-center text-slate-500 text-sm mt-6 col-span-full">No files yet. Click <strong>+ Image</strong> to upload.</p>
    @endif

    <div class="mt-4">{{ $files->links() }}</div>
</div>

{{-- Bulk upload modal --}}
<div class="ta-media-modal" id="uploadModal" aria-hidden="true">
    <div class="ta-media-modal__backdrop" id="uploadModalBackdrop"></div>
    <div class="ta-media-modal__panel" role="dialog" aria-labelledby="uploadModalTitle">
        <div class="ta-media-modal__head">
            <span id="uploadModalTitle">Upload images</span>
            <button type="button" class="ta-media-modal__close" id="closeUploadModal" aria-label="Close">&times;</button>
        </div>
        <div class="ta-media-modal__body">
            <div class="ta-media-drop" id="modalDropZone">
                <div class="ta-media-drop__icon">🖼</div>
                <p class="text-sm text-slate-600 mb-1">Drag & drop images here</p>
                <p class="text-xs text-slate-500">or <label for="modalFileInput" class="ta-media-drop__browse">browse files</label> (bulk upload)</p>
                <input type="file" id="modalFileInput" multiple accept="image/*,video/*,.pdf">
            </div>
            <div class="ta-media-modal__files" id="selectedFilesList"></div>
            <p class="ta-media-modal__status" id="uploadStatus"></p>
        </div>
        <div class="ta-media-modal__foot">
            <button type="button" class="px-4 py-2 text-sm border border-slate-300 rounded-lg" id="cancelUpload">Cancel</button>
            <button type="button" class="px-4 py-2 text-sm bg-brand-600 text-white font-semibold rounded-lg" id="submitUpload" disabled>Upload</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const storeUrl = '{{ route('tenant-admin.media.store') }}';

    /* ---- Upload modal ---- */
    const modal = document.getElementById('uploadModal');
    const dropZone = document.getElementById('modalDropZone');
    const fileInput = document.getElementById('modalFileInput');
    const filesList = document.getElementById('selectedFilesList');
    const uploadStatus = document.getElementById('uploadStatus');
    const submitBtn = document.getElementById('submitUpload');
    let pendingFiles = null;

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        pendingFiles = null;
        fileInput.value = '';
        filesList.textContent = '';
        uploadStatus.textContent = '';
        uploadStatus.className = 'ta-media-modal__status';
        submitBtn.disabled = true;
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    document.getElementById('openUploadModal').addEventListener('click', openModal);
    document.getElementById('closeUploadModal').addEventListener('click', closeModal);
    document.getElementById('uploadModalBackdrop').addEventListener('click', closeModal);
    document.getElementById('cancelUpload').addEventListener('click', closeModal);

    function setFiles(fileList) {
        if (!fileList || !fileList.length) {
            pendingFiles = null;
            filesList.textContent = '';
            submitBtn.disabled = true;
            return;
        }
        pendingFiles = fileList;
        filesList.innerHTML = Array.from(fileList).map(f => '• ' + f.name + ' (' + Math.round(f.size / 1024) + ' KB)').join('<br>');
        submitBtn.disabled = false;
    }

    fileInput.addEventListener('change', () => setFiles(fileInput.files));

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('is-dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('is-dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('is-dragover');
        setFiles(e.dataTransfer.files);
    });

    submitBtn.addEventListener('click', () => {
        if (!pendingFiles || !pendingFiles.length) return;
        const fd = new FormData();
        fd.append('_token', csrf);
        Array.from(pendingFiles).forEach(f => fd.append('files[]', f));

        submitBtn.disabled = true;
        uploadStatus.textContent = 'Uploading…';
        uploadStatus.className = 'ta-media-modal__status';

        fetch(storeUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) throw new Error(data.message || 'Upload failed');
                uploadStatus.textContent = data.message || 'Uploaded!';
                uploadStatus.className = 'ta-media-modal__status is-success';
                setTimeout(() => window.location.reload(), 600);
            })
            .catch(err => {
                uploadStatus.textContent = err.message || 'Upload failed';
                uploadStatus.className = 'ta-media-modal__status is-error';
                submitBtn.disabled = false;
            });
    });

    /* ---- Delete (DB + storage) ---- */
    document.querySelectorAll('.ta-delete-media').forEach(btn => {
        btn.addEventListener('click', function () {
            const name = this.dataset.name || 'this file';
            if (!confirm('Remove "' + name + '" from library and server?')) return;

            const card = this.closest('.ta-media-card');
            const url = this.dataset.url;
            const deleteBtn = this;
            deleteBtn.disabled = true;

            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then(async function (r) {
                    let data = {};
                    try {
                        data = await r.json();
                    } catch (e) {
                        if (r.ok) return { ok: true, data: {} };
                    }
                    return { ok: r.ok, data };
                })
                .then(({ ok, data }) => {
                    if (!ok) throw new Error(data.message || 'Delete failed');
                    if (card) card.remove();
                    else window.location.reload();
                })
                .catch(function () {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = '<input type="hidden" name="_token" value="' + csrf + '"><input type="hidden" name="_method" value="DELETE">';
                    document.body.appendChild(form);
                    form.submit();
                });
        });
    });

    /* ---- Re-upload ---- */
    document.querySelectorAll('.ta-reupload-input').forEach(input => {
        input.addEventListener('change', function () {
            if (!this.files || !this.files[0]) return;
            const card = this.closest('.ta-media-card');
            const img = card?.querySelector('[data-media-img]');
            const fd = new FormData();
            fd.append('_token', csrf);
            fd.append('file', this.files[0]);

            fetch(this.dataset.url, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (!ok) throw new Error(data.message || 'Re-upload failed');
                    if (img && data.url) {
                        img.src = data.url + '?v=' + Date.now();
                    } else {
                        window.location.reload();
                    }
                    const nameEl = card?.querySelector('.ta-media-card__meta p');
                    if (nameEl && data.name) nameEl.textContent = data.name;
                })
                .catch(err => alert(err.message || 'Re-upload failed'))
                .finally(() => { this.value = ''; });
        });
    });
})();
</script>
@endpush
