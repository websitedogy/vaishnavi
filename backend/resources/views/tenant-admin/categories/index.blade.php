@extends('layouts.tenant-admin')

@section('title', 'Categories')
@section('page-title', '')
@section('page-subtitle', '')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant-admin/categories.css') }}">
@endpush

@section('content')
<div class="ta-cat-page">
    <div class="ta-cat-page__header">
        <div>
            <h1 class="ta-cat-page__title">Categories</h1>
            <p class="ta-cat-page__subtitle">Manage your news site categories</p>
        </div>
        <div class="ta-cat-page__header-actions">
            <a href="{{ route('tenant-admin.news.index') }}" class="ta-cat-stat ta-cat-stat--highlight ta-cat-stat--link">
                <span class="ta-cat-stat__value">{{ number_format($totalPosts) }}</span>
                <span class="ta-cat-stat__label">Total Posts →</span>
            </a>
            <a href="{{ route('tenant-admin.categories.create') }}" class="ta-cat-btn-add">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Category
        </a>
        </div>
    </div>

    <div class="ta-cat-table-wrap">
        <table class="ta-cat-table">
            <thead>
                <tr>
                    <th style="width: 4rem">#</th>
                    <th>Category Name</th>
                    <th>Slug</th>
                    <th style="width: 7rem">Subcategories</th>
                    <th style="width: 8rem">Date</th>
                    <th style="width: 7rem">Status</th>
                    <th style="width: 7rem; text-align: right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $index => $category)
                    @php
                        $subPayload = $category->children->map(fn ($c) => [
                            'name' => $c->name,
                            'slug' => $c->slug,
                            'date' => $c->created_at->format('d-m-Y'),
                            'visible' => $c->is_visible,
                            'edit_url' => route('tenant-admin.categories.edit', $c),
                        ])->values();
                    @endphp
                    <tr>
                        <td class="text-slate-500">{{ $categories->firstItem() ? $categories->firstItem() + $index : $index + 1 }}</td>
                        <td><span class="ta-cat-table__name">{{ $category->name }}</span></td>
                        <td><span class="ta-cat-table__slug">{{ $category->slug }}</span></td>
                        <td>
                            @if ($category->children_count > 0)
                                <button type="button"
                                        class="ta-cat-sub-count"
                                        data-parent-name="{{ $category->name }}"
                                        data-parent-id="{{ $category->id }}"
                                        data-add-url="{{ route('tenant-admin.categories.subcategories.create', ['parent_id' => $category->id]) }}"
                                        data-subs="{{ $subPayload->toJson() }}"
                                        aria-haspopup="dialog">
                                    {{ $category->children_count }}
                                </button>
                            @else
                                <span class="ta-cat-sub-count ta-cat-sub-count--zero">0</span>
                            @endif
                        </td>
                        <td>{{ $category->created_at->format('d-m-Y') }}</td>
                        <td>
                            @if ($category->is_visible)
                                <span class="ta-cat-badge ta-cat-badge--active">Active</span>
                            @else
                                <span class="ta-cat-badge ta-cat-badge--inactive">Hidden</span>
                            @endif
                        </td>
                        <td>
                            <div class="ta-cat-actions" style="justify-content: flex-end">
                                <a href="{{ route('tenant-admin.categories.edit', $category) }}" class="ta-cat-action-btn ta-cat-action-btn--edit" title="Edit">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('tenant-admin.categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ta-cat-action-btn ta-cat-action-btn--delete" title="Delete">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="ta-cat-empty">
                            No categories yet.
                            <a href="{{ route('tenant-admin.categories.create') }}" class="text-violet-600 font-semibold">Add Category</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($categories->hasPages())
        <div class="mt-4">{{ $categories->links() }}</div>
    @endif
</div>

<div id="ta-subcat-modal" class="ta-cat-modal" hidden aria-hidden="true">
    <div class="ta-cat-modal__backdrop" data-close-modal></div>
    <div class="ta-cat-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ta-subcat-modal-title">
        <div class="ta-cat-modal__header">
            <div>
                <h2 id="ta-subcat-modal-title" class="ta-cat-modal__title">Subcategories</h2>
                <p id="ta-subcat-modal-sub" class="ta-cat-modal__sub"></p>
            </div>
            <button type="button" class="ta-cat-modal__close" data-close-modal aria-label="Close">&times;</button>
        </div>
        <div id="ta-subcat-modal-body" class="ta-cat-modal__body"></div>
        <div class="ta-cat-modal__footer">
            <a id="ta-subcat-modal-add" href="#" class="ta-cat-btn-add ta-cat-btn-add--sm">+ Add Subcategory</a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('ta-subcat-modal');
    const titleEl = document.getElementById('ta-subcat-modal-title');
    const subEl = document.getElementById('ta-subcat-modal-sub');
    const bodyEl = document.getElementById('ta-subcat-modal-body');
    const addLink = document.getElementById('ta-subcat-modal-add');

    function closeModal() {
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ta-cat-modal-open');
    }

    function openModal(btn) {
        const parentName = btn.dataset.parentName || 'Category';
        const subs = JSON.parse(btn.dataset.subs || '[]');

        titleEl.textContent = 'Subcategories';
        subEl.textContent = parentName + ' — ' + subs.length + ' subcategor' + (subs.length === 1 ? 'y' : 'ies');
        addLink.href = btn.dataset.addUrl || '#';

        if (!subs.length) {
            bodyEl.innerHTML = '<p class="ta-cat-modal__empty">No subcategories yet.</p>';
        } else {
            bodyEl.innerHTML = '<ul class="ta-cat-modal__list">' + subs.map(function (s) {
                const status = s.visible
                    ? '<span class="ta-cat-badge ta-cat-badge--active">Active</span>'
                    : '<span class="ta-cat-badge ta-cat-badge--inactive">Hidden</span>';
                return '<li class="ta-cat-modal__item">' +
                    '<div class="ta-cat-modal__item-main">' +
                    '<strong>' + escapeHtml(s.name) + '</strong>' +
                    '<code class="ta-cat-table__slug">' + escapeHtml(s.slug) + '</code>' +
                    '<span class="ta-cat-modal__date">' + escapeHtml(s.date) + '</span>' +
                    '</div>' +
                    '<div class="ta-cat-modal__item-meta">' + status +
                    '<a href="' + escapeAttr(s.edit_url) + '" class="ta-cat-modal__edit">Edit</a>' +
                    '</div></li>';
            }).join('') + '</ul>';
        }

        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ta-cat-modal-open');
    }

    function escapeHtml(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    document.querySelectorAll('.ta-cat-sub-count:not(.ta-cat-sub-count--zero)').forEach(function (btn) {
        btn.addEventListener('click', function () { openModal(this); });
    });

    modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
</script>
@endpush
