@extends('layouts.tenant-admin')

@php
    $isSub = ($type ?? 'parent') === 'subcategory' || $category->parent_id;
    $editing = $category->exists;
    $previewSlug = old('name', $category->name) ? \Illuminate\Support\Str::slug(old('name', $category->name)) : ($category->slug ?? 'your-category');
@endphp

@section('title', $editing ? ($isSub ? 'Edit subcategory' : 'Edit category') : ($isSub ? 'New subcategory' : 'New category'))
@section('page-title', '')
@section('page-subtitle', '')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant-admin/categories.css') }}">
@endpush

@section('content')
<div class="ta-cat-page" style="max-width: 32rem">
    <div class="ta-cat-page__header">
        <div>
            <h1 class="ta-cat-page__title">{{ $editing ? 'Edit' : 'Add' }} {{ $isSub ? 'Subcategory' : 'Category' }}</h1>
            <p class="ta-cat-page__subtitle">
                {{ $isSub ? 'Choose parent category and enter details below' : 'Fill in the details for your news category' }}
            </p>
        </div>
    </div>

    <a href="{{ $isSub ? route('tenant-admin.categories.subcategories.index', ['parent_id' => $category->parent_id]) : route('tenant-admin.categories.index') }}"
       class="text-sm text-violet-600 hover:underline mb-4 inline-block">
        ← Back to list
    </a>

    <form method="POST"
          action="{{ $editing ? route('tenant-admin.categories.update', $category) : route('tenant-admin.categories.store') }}"
          enctype="multipart/form-data"
          class="ta-cat-table-wrap ta-cat-form">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="is_subcategory" value="{{ $isSub ? '1' : '0' }}">

        @if ($isSub)
            <div class="ta-cat-form__section">
                <p class="ta-cat-form__section-title">Parent</p>
                <div class="ta-cat-field">
                    <label for="parent_id">Parent category <span class="required">*</span></label>
                    <select name="parent_id" id="parent_id" required>
                        <option value="">— Select main category —</option>
                        @foreach ($parents as $p)
                            <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('parent_id')<p class="error">{{ $message }}</p>@enderror
                    @if ($parents->isEmpty())
                        <p class="hint"><a href="{{ route('tenant-admin.categories.create') }}" class="text-violet-600 font-medium">Create a main category</a> first.</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="ta-cat-form__section">
            <p class="ta-cat-form__section-title">Basic info</p>

            <div class="ta-cat-field mb-4">
                <label for="name">{{ $isSub ? 'Subcategory' : 'Category' }} name <span class="required">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $category->name) }}" required
                       placeholder="e.g. Morning edition" autocomplete="off">
                @error('name')<p class="error">{{ $message }}</p>@enderror
                <div class="ta-cat-slug-preview">
                    URL: <code id="slugPreview">{{ $previewSlug }}</code>
                </div>
            </div>

            <div class="ta-cat-field">
                <label for="description">Description <span style="font-weight:400;color:#94a3b8">(optional)</span></label>
                <textarea name="description" id="description" rows="3"
                          placeholder="Short note about this category…">{{ old('description', $category->description) }}</textarea>
                @error('description')<p class="error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="ta-cat-form__section">
            <p class="ta-cat-form__section-title">Display</p>
            <div class="ta-cat-field">
                <label for="sort_order">Display order</label>
                <input type="number" name="sort_order" id="sort_order" min="0" step="1"
                       value="{{ old('sort_order', $category->sort_order ?? 0) }}">
                <p class="hint">Lower numbers show first in menus (0, 1, 2…)</p>
            </div>
        </div>

        <div class="ta-cat-form__section">
            <p class="ta-cat-form__section-title">Options</p>
            <div class="ta-cat-options">
                <label>
                    <input type="checkbox" name="is_visible" value="1" @checked((bool) old('is_visible', $category->is_visible ?? true))>
                    Active — show on website
                </label>
                <p class="hint">Uncheck to hide this category from the public site menu and listings.</p>
                @if ($editing)
                    <label>
                        <input type="checkbox" name="regenerate_slug" value="1">
                        Update URL slug when name changes
                    </label>
                @endif
            </div>
        </div>

        <button type="submit" class="ta-cat-btn-add w-full justify-center border-0 cursor-pointer" style="margin-top: 0.5rem">
            {{ $editing ? 'Update' : 'Save' }} {{ $isSub ? 'Subcategory' : 'Category' }}
        </button>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const nameInput = document.getElementById('name');
    const slugPreview = document.getElementById('slugPreview');
    if (!nameInput || !slugPreview) return;

    nameInput.addEventListener('input', function () {
        slugPreview.textContent = this.value
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            || 'your-category';
    });
})();
</script>
@endpush
