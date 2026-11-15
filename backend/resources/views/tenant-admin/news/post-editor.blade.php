@extends('layouts.tenant-admin')

@php
    $editing = $article->exists;
    $selectedCategories = old('category_ids', $article->exists && $article->category_id ? [$article->category_id] : []);
    if (!is_array($selectedCategories)) {
        $selectedCategories = [];
    }
    $tagNames = old('tags', $article->exists ? $article->tags->pluck('name')->join(', ') : '');
    $primaryCategoryId = old('primary_category_id', $article->category_id);
@endphp

@section('title', $editing ? 'Edit Post' : 'Add Post')
@section('page-title', '')
@section('page-subtitle', '')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant-admin/post-editor.css') }}">
@endpush

@section('content')
<form method="POST"
      action="{{ $editing ? route('tenant-admin.news.update', $article) : route('tenant-admin.news.store') }}"
      enctype="multipart/form-data"
      id="postForm"
      class="ta-post-editor">
    @csrf
    @if ($editing) @method('PUT') @endif

    <h1 class="text-2xl font-semibold text-slate-900 mb-4">{{ $editing ? 'Edit Post' : 'Add Post' }}</h1>

    <input type="text"
           name="title"
           id="postTitle"
           class="ta-post-editor__title"
           value="{{ old('title', $article->title) }}"
           placeholder="Add title"
           required
           autocomplete="off">

    <input type="text"
           name="subtitle"
           id="postSubtitle"
           class="ta-post-editor__subtitle"
           value="{{ old('subtitle', $article->subtitle) }}"
           placeholder="Enter sub title here">

    @error('title')<p class="text-red-600 text-sm mb-2">{{ $message }}</p>@enderror
    @error('subtitle')<p class="text-red-600 text-sm mb-2">{{ $message }}</p>@enderror

    <div class="ta-post-editor__layout">
        <div class="ta-post-editor__main">
            <div class="ta-post-editor__media-bar">
                <button type="button" class="ta-post-editor__media-btn" id="addMediaBtn">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Add Media
                </button>
                <input type="file" name="featured_image_file" id="featuredFile" accept="image/*" class="hidden">
            </div>

            <div class="ta-post-editor__editor-wrap">
                <textarea name="content" id="content" class="ta-post-editor__content-field">{!! old('content', $article->content) !!}</textarea>
            </div>
            <div class="ta-post-editor__wordcount">Word count: <span id="wordCount">0</span></div>

            {{-- Post settings (below editor) --}}
            <div class="ta-post-settings">
                <nav class="ta-post-settings__nav" aria-label="Post settings">
                    <button type="button" class="ta-post-settings__nav-btn is-active" data-settings-tab="general">
                        <span class="ta-post-settings__nav-icon">⚙</span> General
                    </button>
                </nav>
                <div class="ta-post-settings__content">
                    <div class="ta-post-settings__panel is-active" id="settingsPanelGeneral">
                        <div class="ta-post-field">
                            <label for="primary_category_id">Primary Category</label>
                            <select name="primary_category_id" id="primary_category_id">
                                <option value="">— Select a Category —</option>
                            </select>
                            <p class="ta-post-field__hint">If the post has multiple categories, the one selected here is used for labels and site settings.</p>
                        </div>
                        <div class="ta-post-field">
                            <label for="custom_title">Custom Title</label>
                            <input type="text" name="custom_title" id="custom_title"
                                   value="{{ old('custom_title', $article->custom_title) }}"
                                   placeholder="">
                            <p class="ta-post-field__hint">Shown on the single post page instead of the main post title (optional).</p>
                        </div>
                        <div class="ta-post-field ta-post-field--row">
                            <label for="is_trending">Trending Post</label>
                            <label class="ta-post-toggle">
                                <input type="checkbox" name="is_trending" id="is_trending" value="1"
                                       @checked(old('is_trending', $article->is_trending))>
                                <span class="ta-post-toggle__slider"></span>
                            </label>
                        </div>
                        <div class="ta-post-field">
                            <label for="views">Post Views</label>
                            <input type="number" name="views" id="views" min="0" step="1"
                                   value="{{ old('views', $article->views ?? 0) }}"
                                   class="ta-post-field__input-narrow">
                            <p class="ta-post-field__hint">Starting view count (real views add on top when readers open the post).</p>
                        </div>
                    </div>
                </div>
            </div>

            @include('tenant-admin.news.partials.rank-math-seo')
        </div>

        <div class="ta-post-editor__sidebar">
            {{-- Publish --}}
            <div class="ta-post-box">
                <div class="ta-post-box__head">Publish</div>
                <div class="ta-post-box__body">
                    <input type="hidden" name="status" id="postStatus" value="{{ old('status', $article->status ?? 'draft') }}">
                    <div class="ta-post-publish__actions">
                        <button type="button" class="ta-post-publish__btn" data-status="draft">Save Draft</button>
                        @if(auth('tenant_admin')->user()->hasPermission('publish_news'))
                            <button type="button" class="ta-post-publish__btn ta-post-publish__btn--primary" data-status="published">Publish</button>
                            <button type="button" class="ta-post-publish__btn" data-status="scheduled" id="btnSchedule">Schedule</button>
                        @endif
                    </div>
                    <div id="scheduleWrap" class="mb-2 {{ old('status', $article->status) === 'scheduled' ? '' : 'hidden' }}">
                        <label class="block text-xs text-slate-600 mb-1">Publish date & time</label>
                        <input type="datetime-local" name="scheduled_at" class="w-full text-sm border border-slate-300 rounded mb-2"
                               value="{{ old('scheduled_at', $article->scheduled_at?->format('Y-m-d\TH:i')) }}">
                        <button type="button" class="ta-post-publish__btn ta-post-publish__btn--primary w-full" id="confirmSchedule">Schedule post</button>
                    </div>
                    <div class="ta-post-publish__save-row mt-2">
                        <button type="submit" class="ta-post-publish__btn ta-post-publish__btn--flex">{{ $editing ? 'Update post' : 'Save post' }}</button>
                        <button type="button" class="ta-post-publish__btn ta-post-publish__btn--flex ta-post-publish__btn--preview" id="btnPreviewSite">Preview site</button>
                    </div>
                    <label class="flex items-center gap-2 text-sm mb-2">
                        <input type="checkbox" name="is_breaking" value="1" @checked(old('is_breaking', $article->is_breaking))>
                        Breaking news
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $article->is_featured))>
                        Featured post
                    </label>
                </div>
            </div>

            {{-- Categories --}}
            <div class="ta-post-box">
                <div class="ta-post-box__head">Categories</div>
                <div class="ta-post-box__body">
                    <ul class="ta-post-cat-list" id="taCatList">
                        @foreach ($parentCategories as $cat)
                            <li data-cat-id="{{ $cat->id }}" data-parent-id="">
                                <label>
                                    <input type="checkbox" name="category_ids[]" value="{{ $cat->id }}"
                                           @checked(in_array($cat->id, $selectedCategories))>
                                    {{ $cat->name }}
                                </label>
                            </li>
                            @foreach ($cat->children as $sub)
                                <li class="sub" data-cat-id="{{ $sub->id }}" data-parent-id="{{ $cat->id }}">
                                    <label>
                                        <input type="checkbox" name="category_ids[]" value="{{ $sub->id }}"
                                               @checked(in_array($sub->id, $selectedCategories))>
                                        {{ $sub->name }}
                                    </label>
                                </li>
                            @endforeach
                        @endforeach
                    </ul>
                    <p class="text-xs text-slate-500 hidden" id="taCatEmpty">No categories yet.</p>

                    <div class="ta-post-cat-add hidden" id="taCatAddForm">
                        <input type="text" id="taCatName" class="ta-post-cat-add__input" placeholder="Category name" maxlength="255">
                        <select id="taCatParent" class="ta-post-cat-add__select">
                            <option value="">— Main category —</option>
                            @foreach ($parentCategories as $cat)
                                <option value="{{ $cat->id }}">Sub under: {{ $cat->name }}</option>
                            @endforeach
                        </select>
                        <div class="ta-post-cat-add__actions">
                            <button type="button" class="ta-post-tags__add" id="taCatSaveBtn">Add</button>
                            <button type="button" class="ta-post-tags__add" id="taCatCancelBtn">Cancel</button>
                        </div>
                        <p class="text-xs text-red-600 hidden" id="taCatError"></p>
                    </div>
                    <button type="button" class="ta-post-link" id="taCatAddToggle">+ Add Category</button>
                </div>
            </div>

            {{-- Tags --}}
            <div class="ta-post-box">
                <div class="ta-post-box__head">Tags</div>
                <div class="ta-post-box__body">
                    <div class="ta-post-tags__row">
                        <input type="text" id="tagInput" placeholder="Add tag">
                        <button type="button" class="ta-post-tags__add" id="tagAddBtn">Add</button>
                    </div>
                    <p class="ta-post-tags__hint">Separate tags with commas</p>
                    <div class="ta-post-tags__pills" id="tagPills"></div>
                    <input type="hidden" name="tags" id="tagsHidden" value="{{ $tagNames }}">
                </div>
            </div>

            {{-- Featured image --}}
            <div class="ta-post-box">
                <div class="ta-post-box__head">Featured image</div>
                <div class="ta-post-box__body">
                    <input type="hidden" name="featured_image" id="featuredUrl" value="{{ old('featured_image', $article->featured_image) }}">
                    <button type="button" class="ta-post-link" onclick="document.getElementById('featuredFile').click()">Set featured image</button>
                    <div class="ta-post-featured-preview" id="featuredPreview">
                        @php $featuredPreview = \App\Models\MediaFile::normalizeStoredUrl(old('featured_image', $article->featured_image)); @endphp
                        @if($featuredPreview)
                            <img src="{{ $featuredPreview }}" alt="">
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="text-xs text-slate-500 mt-3" id="autosaveStatus"></p>
</form>

{{-- Media library picker (TinyMCE + Add Media) --}}
<div class="ta-media-modal" id="taMediaModal" aria-hidden="true">
    <div class="ta-media-modal__backdrop" id="taMediaBackdrop"></div>
    <div class="ta-media-modal__panel" role="dialog" aria-labelledby="taMediaTitle">
        <div class="ta-media-modal__head">
            <span id="taMediaTitle">Insert media</span>
            <button type="button" class="ta-media-modal__close" id="taMediaClose" aria-label="Close">&times;</button>
        </div>
        <div class="ta-media-modal__tabs">
            <button type="button" class="ta-media-modal__tab is-active" data-tab="library">Media Library</button>
            <button type="button" class="ta-media-modal__tab" data-tab="upload">Upload</button>
        </div>
        <div class="ta-media-modal__body">
            <div id="taMediaTabLibrary">
                <div class="ta-media-modal__search">
                    <input type="search" id="taMediaSearch" placeholder="Search images…">
                    <button type="button" class="ta-post-publish__btn" id="taMediaSearchBtn">Search</button>
                </div>
                <div class="ta-media-modal__grid" id="taMediaGrid"></div>
                <p class="ta-media-modal__empty hidden" id="taMediaEmpty">No images yet. Upload one in the Upload tab.</p>
            </div>
            <div id="taMediaTabUpload" class="hidden">
                <div class="ta-media-modal__upload" id="taMediaDropZone">
                    <p class="text-sm text-slate-600 mb-2">Drop an image here or click to choose</p>
                    <input type="file" id="taMediaUploadInput" accept="image/*" class="hidden">
                    <button type="button" class="ta-post-publish__btn ta-post-publish__btn--primary" id="taMediaChooseBtn">Select file</button>
                    <p class="text-xs text-slate-500 mt-2" id="taMediaUploadStatus"></p>
                </div>
            </div>
        </div>
        <div class="ta-media-modal__footer">
            <div class="ta-media-modal__pager">
                <button type="button" id="taMediaPrev" disabled>&laquo; Prev</button>
                <span id="taMediaPageInfo">Page 1</span>
                <button type="button" id="taMediaNext" disabled>Next &raquo;</button>
            </div>
            <button type="button" class="ta-media-modal__insert" id="taMediaInsert" disabled>Insert into post</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@php
    $tinymceApiKey = config('tinymce.api_key');
@endphp
@if (empty($tinymceApiKey))
    <p class="text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm mb-4">
        TinyMCE API key missing. Add your key in <code>config/tinymce.php</code> or set <code>TINYMCE_API_KEY</code> in <code>.env</code>.
    </p>
@else
<script src="https://cdn.tiny.cloud/1/{{ $tinymceApiKey }}/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    const csrf = '{{ csrf_token() }}';
    const mediaBrowserUrl = '{{ route('tenant-admin.media.browser') }}';
    const mediaUploadUrl = '{{ route('tenant-admin.media.upload-editor') }}';

    let mediaPickerCallback = null;
    let selectedMedia = null;
    let mediaPage = 1;
    let mediaLastPage = 1;

    function getEditorContent() {
        const ed = tinymce.get('content');
        return ed ? ed.getContent() : document.getElementById('content').value;
    }

    function updateWordCount() {
        const ed = tinymce.get('content');
        const text = ed ? ed.getContent({ format: 'text' }).trim() : '';
        const words = text ? text.split(/\s+/).filter(Boolean).length : 0;
        document.getElementById('wordCount').textContent = words;
    }

    function insertImageIntoEditor(url, alt) {
        const ed = tinymce.get('content');
        const safeAlt = (alt || '').replace(/"/g, '&quot;');
        const html = '<img src="' + url + '" alt="' + safeAlt + '">';
        if (ed) {
            ed.insertContent(html);
            ed.focus();
        }
    }

    window.taOpenMediaPicker = function (callback) {
        mediaPickerCallback = typeof callback === 'function' ? callback : null;
        selectedMedia = null;
        document.getElementById('taMediaInsert').disabled = true;
        document.getElementById('taMediaModal').classList.add('is-open');
        switchMediaTab('library');
        loadMediaLibrary(1);
    };

    function closeMediaPicker() {
        document.getElementById('taMediaModal').classList.remove('is-open');
        mediaPickerCallback = null;
        selectedMedia = null;
    }

    function switchMediaTab(tab) {
        document.querySelectorAll('.ta-media-modal__tab').forEach(btn => {
            btn.classList.toggle('is-active', btn.dataset.tab === tab);
        });
        document.getElementById('taMediaTabLibrary').classList.toggle('hidden', tab !== 'library');
        document.getElementById('taMediaTabUpload').classList.toggle('hidden', tab !== 'upload');
    }

    function loadMediaLibrary(page) {
        mediaPage = page;
        const q = document.getElementById('taMediaSearch').value.trim();
        const url = new URL(mediaBrowserUrl, window.location.origin);
        url.searchParams.set('page', page);
        if (q) url.searchParams.set('q', q);

        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => {
                if (!r.ok) throw new Error('Could not load media library');
                return r.json();
            })
            .then(res => {
                const grid = document.getElementById('taMediaGrid');
                const empty = document.getElementById('taMediaEmpty');
                grid.innerHTML = '';
                selectedMedia = null;
                document.getElementById('taMediaInsert').disabled = true;

                if (!res.data || res.data.length === 0) {
                    empty.classList.remove('hidden');
                    return;
                }
                empty.classList.add('hidden');

                res.data.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'ta-media-modal__item';
                    div.dataset.url = item.url;
                    div.dataset.alt = item.alt || item.name;
                    div.innerHTML = '<img src="' + item.thumb + '" alt=""><span>' + item.name + '</span>';
                    div.addEventListener('click', () => {
                        grid.querySelectorAll('.ta-media-modal__item').forEach(el => el.classList.remove('is-selected'));
                        div.classList.add('is-selected');
                        selectedMedia = { url: item.url, alt: item.alt || item.name };
                        document.getElementById('taMediaInsert').disabled = false;
                    });
                    grid.appendChild(div);
                });

                mediaLastPage = res.meta?.last_page || 1;
                document.getElementById('taMediaPageInfo').textContent = 'Page ' + mediaPage + ' of ' + mediaLastPage;
                document.getElementById('taMediaPrev').disabled = mediaPage <= 1;
                document.getElementById('taMediaNext').disabled = mediaPage >= mediaLastPage;
            })
            .catch(() => {
                document.getElementById('taMediaEmpty').textContent = 'Failed to load media. Check permissions or try again.';
                document.getElementById('taMediaEmpty').classList.remove('hidden');
            });
    }

    function uploadMediaFile(file) {
        const status = document.getElementById('taMediaUploadStatus');
        status.textContent = 'Uploading…';
        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', csrf);

        return fetch(mediaUploadUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok || !data.location) {
                    throw new Error(data.message || 'Upload failed');
                }
                status.textContent = 'Uploaded: ' + (data.name || 'image');
                selectedMedia = { url: data.location, alt: data.name || '' };
                document.getElementById('taMediaInsert').disabled = false;
                switchMediaTab('library');
                loadMediaLibrary(1);
                return data;
            })
            .catch(err => {
                status.textContent = err.message || 'Upload failed';
                throw err;
            });
    }

    document.getElementById('taMediaClose').addEventListener('click', closeMediaPicker);
    document.getElementById('taMediaBackdrop').addEventListener('click', closeMediaPicker);
    document.querySelectorAll('.ta-media-modal__tab').forEach(btn => {
        btn.addEventListener('click', () => switchMediaTab(btn.dataset.tab));
    });
    document.getElementById('taMediaSearchBtn').addEventListener('click', () => loadMediaLibrary(1));
    document.getElementById('taMediaSearch').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); loadMediaLibrary(1); }
    });
    document.getElementById('taMediaPrev').addEventListener('click', () => loadMediaLibrary(mediaPage - 1));
    document.getElementById('taMediaNext').addEventListener('click', () => loadMediaLibrary(mediaPage + 1));
    document.getElementById('taMediaInsert').addEventListener('click', () => {
        if (!selectedMedia) return;
        if (mediaPickerCallback) {
            mediaPickerCallback(selectedMedia.url, { alt: selectedMedia.alt });
        } else {
            insertImageIntoEditor(selectedMedia.url, selectedMedia.alt);
        }
        closeMediaPicker();
    });
    document.getElementById('taMediaChooseBtn').addEventListener('click', () => document.getElementById('taMediaUploadInput').click());
    document.getElementById('taMediaUploadInput').addEventListener('change', function () {
        if (this.files[0]) uploadMediaFile(this.files[0]);
        this.value = '';
    });
    const dropZone = document.getElementById('taMediaDropZone');
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('is-dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('is-dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('is-dragover');
        if (e.dataTransfer.files[0]) uploadMediaFile(e.dataTransfer.files[0]);
    });

    tinymce.init({
        selector: '#content',
        height: 420,
        menubar: false,
        branding: false,
        promotion: false,
        plugins: 'anchor autolink charmap codesample emoticons image link lists searchreplace table visualblocks wordcount fullscreen code',
        toolbar: 'blocks | bold italic underline strikethrough | bullist numlist blockquote | alignleft aligncenter alignright | link image medialibrary | table codesample code fullscreen',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 16px; line-height: 1.6; }',
        automatic_uploads: true,
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('file', blobInfo.blob(), blobInfo.filename());
            fd.append('_token', csrf);
            fetch(mediaUploadUrl, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    if (ok && data.location) resolve(data.location);
                    else reject(data.message || 'Upload failed');
                })
                .catch(() => reject('Upload failed'));
        }),
        file_picker_types: 'image',
        file_picker_callback: (callback, value, meta) => {
            if (meta.filetype === 'image') {
                taOpenMediaPicker((url, metaInfo) => {
                    callback(url, { alt: metaInfo?.alt || '' });
                });
            }
        },
        setup(editor) {
            editor.on('init change keyup undo redo SetContent', () => {
                updateWordCount();
                document.dispatchEvent(new Event('ta-content-change'));
            });
            editor.ui.registry.addButton('medialibrary', {
                tooltip: 'Media library',
                icon: 'gallery',
                onAction: () => taOpenMediaPicker(),
            });
            editor.ui.registry.addMenuItem('medialibrary', {
                text: 'Media library',
                icon: 'gallery',
                onAction: () => taOpenMediaPicker(),
            });
        },
    });

    document.getElementById('addMediaBtn').addEventListener('click', function () {
        taOpenMediaPicker();
    });

    document.getElementById('postForm').addEventListener('submit', function () {
        tinymce.triggerSave();
    });

    document.querySelectorAll('[data-status]').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('postStatus').value = this.dataset.status;
            document.getElementById('scheduleWrap').classList.toggle('hidden', this.dataset.status !== 'scheduled');
            if (this.dataset.status === 'published' || this.dataset.status === 'draft') {
                document.getElementById('postForm').requestSubmit();
            }
        });
    });

    document.getElementById('btnSchedule')?.addEventListener('click', function () {
        document.getElementById('postStatus').value = 'scheduled';
        document.getElementById('scheduleWrap').classList.remove('hidden');
    });

    document.getElementById('confirmSchedule')?.addEventListener('click', function () {
        document.getElementById('postStatus').value = 'scheduled';
        document.getElementById('postForm').requestSubmit();
    });

    const tagsHidden = document.getElementById('tagsHidden');
    const tagPills = document.getElementById('tagPills');
    let tags = (tagsHidden.value || '').split(',').map(t => t.trim()).filter(Boolean);

    function renderTags() {
        tagPills.innerHTML = '';
        tags.forEach((tag, i) => {
            const span = document.createElement('span');
            span.className = 'ta-post-tag-pill';
            span.innerHTML = tag + ' <button type="button" data-i="' + i + '">&times;</button>';
            tagPills.appendChild(span);
        });
        tagsHidden.value = tags.join(', ');
    }
    renderTags();

    tagPills.addEventListener('click', e => {
        if (e.target.tagName === 'BUTTON') {
            tags.splice(parseInt(e.target.dataset.i, 10), 1);
            renderTags();
        }
    });

    function addTagsFromInput(val) {
        val.split(',').forEach(t => {
            t = t.trim();
            if (t && !tags.includes(t)) tags.push(t);
        });
        renderTags();
    }

    document.getElementById('tagAddBtn').addEventListener('click', () => {
        const input = document.getElementById('tagInput');
        addTagsFromInput(input.value);
        input.value = '';
    });
    document.getElementById('tagInput').addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('tagAddBtn').click(); }
    });

    document.getElementById('featuredFile').addEventListener('change', function () {
        if (this.files && this.files[0]) {
            const url = URL.createObjectURL(this.files[0]);
            document.getElementById('featuredPreview').innerHTML = '<img src="' + url + '" alt="">';
        }
    });

    @if($editing)
    setInterval(() => {
        const fd = new FormData();
        fd.append('title', document.getElementById('postTitle').value);
        fd.append('subtitle', document.getElementById('postSubtitle').value);
        fd.append('content', getEditorContent());
        fd.append('_token', '{{ csrf_token() }}');
        fetch('{{ route('tenant-admin.news.autosave', $article) }}', { method: 'POST', body: fd })
            .then(() => {
                document.getElementById('autosaveStatus').textContent = 'Draft auto-saved ' + new Date().toLocaleTimeString();
            });
    }, 45000);
    @endif
})();
</script>
@endif
<script>
(function () {
    const catList = document.getElementById('taCatList');
    const catEmpty = document.getElementById('taCatEmpty');
    const catForm = document.getElementById('taCatAddForm');
    const catToggle = document.getElementById('taCatAddToggle');
    const catParentSelect = document.getElementById('taCatParent');
    const catQuickStoreUrl = '{{ route('tenant-admin.categories.quick-store') }}';
    const csrf = '{{ csrf_token() }}';

    function syncCatEmpty() {
        if (!catList || !catEmpty) return;
        catEmpty.classList.toggle('hidden', catList.querySelectorAll('li').length > 0);
    }
    syncCatEmpty();

    function appendCategory(cat) {
        const li = document.createElement('li');
        li.dataset.catId = cat.id;
        li.dataset.parentId = cat.parent_id || '';
        if (cat.is_sub) li.className = 'sub';

        const label = document.createElement('label');
        const input = document.createElement('input');
        input.type = 'checkbox';
        input.name = 'category_ids[]';
        input.value = cat.id;
        input.checked = true;
        label.appendChild(input);
        label.appendChild(document.createTextNode(' ' + cat.name));
        li.appendChild(label);

        if (cat.is_sub && cat.parent_id) {
            const after = catList.querySelector('li[data-cat-id="' + cat.parent_id + '"]');
            let sibling = after;
            while (sibling && sibling.nextElementSibling && sibling.nextElementSibling.classList.contains('sub') &&
                   sibling.nextElementSibling.dataset.parentId === String(cat.parent_id)) {
                sibling = sibling.nextElementSibling;
            }
            if (sibling) sibling.after(li);
            else catList.appendChild(li);
        } else {
            catList.appendChild(li);
            if (!cat.is_sub && catParentSelect) {
                const opt = document.createElement('option');
                opt.value = cat.id;
                opt.textContent = 'Sub under: ' + cat.name;
                catParentSelect.appendChild(opt);
            }
        }
        syncCatEmpty();
        window.taSyncPrimaryCategories?.();
    }

    catToggle?.addEventListener('click', () => {
        catForm.classList.remove('hidden');
        catToggle.classList.add('hidden');
        document.getElementById('taCatName').focus();
    });

    document.getElementById('taCatCancelBtn')?.addEventListener('click', () => {
        catForm.classList.add('hidden');
        catToggle.classList.remove('hidden');
        document.getElementById('taCatName').value = '';
        catParentSelect.value = '';
        document.getElementById('taCatError').classList.add('hidden');
    });

    document.getElementById('taCatSaveBtn')?.addEventListener('click', () => {
        const name = document.getElementById('taCatName').value.trim();
        const parentId = catParentSelect.value;
        const errEl = document.getElementById('taCatError');
        errEl.classList.add('hidden');

        if (!name) {
            errEl.textContent = 'Enter a category name.';
            errEl.classList.remove('hidden');
            return;
        }

        const fd = new FormData();
        fd.append('name', name);
        fd.append('_token', csrf);
        if (parentId) fd.append('parent_id', parentId);

        fetch(catQuickStoreUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    const msg = data.message || (data.errors && Object.values(data.errors).flat()[0]) || 'Could not add category.';
                    throw new Error(msg);
                }
                appendCategory(data.category);
                document.getElementById('taCatName').value = '';
                catParentSelect.value = '';
                catForm.classList.add('hidden');
                catToggle.classList.remove('hidden');
            })
            .catch(e => {
                errEl.textContent = e.message || 'Could not add category.';
                errEl.classList.remove('hidden');
            });
    });

    document.getElementById('taCatName')?.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('taCatSaveBtn').click(); }
    });

    catList?.addEventListener('change', e => {
        if (e.target.matches('input[name="category_ids[]"]')) window.taSyncPrimaryCategories?.();
    });
})();
</script>
<script>
(function () {
    const primarySelect = document.getElementById('primary_category_id');
    const savedPrimary = '{{ $primaryCategoryId }}';

    window.taSyncPrimaryCategories = function () {
        if (!primarySelect) return;
        const checked = document.querySelectorAll('#taCatList input[name="category_ids[]"]:checked');
        const current = primarySelect.value;
        primarySelect.innerHTML = '<option value="">— Select a Category —</option>';
        checked.forEach(cb => {
            const li = cb.closest('li');
            const opt = document.createElement('option');
            opt.value = cb.value;
            opt.textContent = li?.querySelector('label')?.textContent?.trim() || ('Category ' + cb.value);
            primarySelect.appendChild(opt);
        });
        if (current && [...primarySelect.options].some(o => o.value === current)) {
            primarySelect.value = current;
        } else if (savedPrimary && [...primarySelect.options].some(o => o.value === savedPrimary)) {
            primarySelect.value = savedPrimary;
        } else if (checked.length === 1) {
            primarySelect.value = checked[0].value;
        }
    };
    taSyncPrimaryCategories();

})();
</script>
<script>
(function () {
    const previewUrl = @json($editing ? route('tenant-admin.news.preview-draft', $article) : route('tenant-admin.news.preview-draft'));
    document.getElementById('btnPreviewSite')?.addEventListener('click', function () {
        if (typeof tinymce !== 'undefined') tinymce.triggerSave();
        const form = document.getElementById('postForm');
        const fd = new FormData(form);
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Loading…';
        fetch(previewUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
            .then(r => r.json().then(data => ({ ok: r.ok, data })))
            .then(({ ok, data }) => {
                if (ok && data.url) window.open(data.url, '_blank');
                else alert(data.message || 'Could not open preview.');
            })
            .catch(() => alert('Could not open preview.'))
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Preview site';
            });
    });
})();
</script>
<script>
    window.taRankMath = { host: @json(parse_url(config('app.url'), PHP_URL_HOST) ?: request()->getHost()) };
</script>
<script src="{{ asset('js/tenant-admin/rank-math-seo.js') }}"></script>
@endpush
