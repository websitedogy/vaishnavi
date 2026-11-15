@extends('layouts.tenant-admin')

@section('title', 'Subcategories')
@section('page-title', '')
@section('page-subtitle', '')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant-admin/categories.css') }}">
@endpush

@section('content')
<div class="ta-cat-page">
    <div class="ta-cat-page__header">
        <div>
            <p class="ta-cat-breadcrumb">
                <a href="{{ route('tenant-admin.categories.index') }}">Categories</a>
                <span aria-hidden="true">/</span>
                <span>Subcategories</span>
            </p>
            <h1 class="ta-cat-page__title">Subcategories</h1>
            <p class="ta-cat-page__subtitle">
                @if ($selectedParent)
                    Showing subcategories under <strong>{{ $selectedParent->name }}</strong>
                @else
                    All subcategories — sorted A to Z by parent, then name
                @endif
            </p>
        </div>
        <a href="{{ route('tenant-admin.categories.subcategories.create', request()->only('parent_id')) }}" class="ta-cat-btn-add">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Subcategory
        </a>
    </div>

    <div class="ta-cat-stats">
        <div class="ta-cat-stat ta-cat-stat--highlight">
            <span class="ta-cat-stat__value">{{ number_format($totalPosts) }}</span>
            <span class="ta-cat-stat__label">Total posts</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ $totalSubcategories }}</span>
            <span class="ta-cat-stat__label">Total subcategories</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ $subcategories->total() }}</span>
            <span class="ta-cat-stat__label">{{ $selectedParent ? 'In this parent' : 'Matching filter' }}</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ $parents->count() }}</span>
            <span class="ta-cat-stat__label">Parent categories</span>
        </div>
    </div>

    @if ($parents->isNotEmpty())
        <div class="ta-cat-parent-legend">
            <span class="ta-cat-parent-legend__title">Parent colours (A–Z)</span>
            <div class="ta-cat-parent-legend__list">
                <a href="{{ route('tenant-admin.categories.subcategories.index') }}"
                   class="ta-cat-parent-link ta-cat-parent-link--all @if(! $selectedParent) is-active @endif">
                    All
                </a>
                @foreach ($parents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $parent)
                    @php $color = $parentColorMap[$parent->id] ?? 'blue'; @endphp
                    <a href="{{ route('tenant-admin.categories.subcategories.index', ['parent_id' => $parent->id]) }}"
                       class="ta-cat-parent-link ta-cat-parent-link--{{ $color }} @if($selectedParent && $selectedParent->id === $parent->id) is-active @endif">
                        {{ $parent->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="ta-cat-toolbar">
        <form method="GET" class="ta-cat-toolbar__form">
            <div class="ta-cat-toolbar__field">
                <label for="parent_id">Filter by parent</label>
                <select name="parent_id" id="parent_id" class="ta-cat-toolbar__select" onchange="this.form.submit()">
                    <option value="">All parent categories</option>
                    @foreach ($parents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $parent)
                        <option value="{{ $parent->id }}" @selected((string) request('parent_id') === (string) $parent->id)>
                            {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @if (request('parent_id'))
                <a href="{{ route('tenant-admin.categories.subcategories.index') }}" class="ta-cat-toolbar__clear">Clear filter</a>
            @endif
        </form>
        <a href="{{ route('tenant-admin.categories.index') }}" class="ta-cat-toolbar__back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Back to Categories
        </a>
    </div>

    <div class="ta-cat-table-wrap ta-cat-table-wrap--desktop">
        <table class="ta-cat-table ta-cat-table--subs">
            <thead>
                <tr>
                    <th style="width: 4rem">#</th>
                    <th>Name</th>
                    <th>Parent Category</th>
                    <th>Slug</th>
                    <th style="width: 5rem">Posts</th>
                    <th style="width: 8rem">Date</th>
                    <th style="width: 7rem">Status</th>
                    <th style="width: 7rem; text-align: right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subcategories as $index => $sub)
                    @php
                        $parentColor = $sub->parent_id ? ($parentColorMap[$sub->parent_id] ?? 'blue') : 'blue';
                    @endphp
                    <tr>
                        <td class="text-slate-500">{{ $subcategories->firstItem() ? $subcategories->firstItem() + $index : $index + 1 }}</td>
                        <td><span class="ta-cat-table__name">{{ $sub->name }}</span></td>
                        <td>
                            @if ($sub->parent)
                                <a href="{{ route('tenant-admin.categories.subcategories.index', ['parent_id' => $sub->parent_id]) }}"
                                   class="ta-cat-parent-link ta-cat-parent-link--{{ $parentColor }}">
                                    {{ $sub->parent->name }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td><span class="ta-cat-table__slug">{{ $sub->slug }}</span></td>
                        <td><span class="ta-cat-posts-count">{{ $sub->posts_count }}</span></td>
                        <td>{{ $sub->created_at->format('d-m-Y') }}</td>
                        <td>
                            @if ($sub->is_visible)
                                <span class="ta-cat-badge ta-cat-badge--active">Active</span>
                            @else
                                <span class="ta-cat-badge ta-cat-badge--inactive">Hidden</span>
                            @endif
                        </td>
                        <td>
                            <div class="ta-cat-actions" style="justify-content: flex-end">
                                <a href="{{ route('tenant-admin.categories.edit', $sub) }}" class="ta-cat-action-btn ta-cat-action-btn--edit" title="Edit">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('tenant-admin.categories.destroy', $sub) }}" class="inline" onsubmit="return confirm('Delete this subcategory?')">
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
                        <td colspan="8" class="ta-cat-empty">
                            <div class="ta-cat-empty__inner">
                                <p>No subcategories found.</p>
                                @if ($parents->isNotEmpty())
                                    <a href="{{ route('tenant-admin.categories.subcategories.create', request()->only('parent_id')) }}" class="ta-cat-btn-add ta-cat-btn-add--sm">+ Add Subcategory</a>
                                @else
                                    <p class="ta-cat-empty__hint">Create a <a href="{{ route('tenant-admin.categories.create') }}">main category</a> first.</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="ta-cat-cards ta-cat-cards--mobile">
        @forelse ($subcategories as $index => $sub)
            @php $parentColor = $sub->parent_id ? ($parentColorMap[$sub->parent_id] ?? 'blue') : 'blue'; @endphp
            <article class="ta-cat-card-item">
                <div class="ta-cat-card-item__top">
                    <span class="ta-cat-card-item__num">#{{ $subcategories->firstItem() ? $subcategories->firstItem() + $index : $index + 1 }}</span>
                    @if ($sub->is_visible)
                        <span class="ta-cat-badge ta-cat-badge--active">Active</span>
                    @else
                        <span class="ta-cat-badge ta-cat-badge--inactive">Hidden</span>
                    @endif
                </div>
                <h3 class="ta-cat-card-item__name">{{ $sub->name }}</h3>
                @if ($sub->parent)
                    <a href="{{ route('tenant-admin.categories.subcategories.index', ['parent_id' => $sub->parent_id]) }}"
                       class="ta-cat-parent-link ta-cat-parent-link--{{ $parentColor }}">
                        {{ $sub->parent->name }}
                    </a>
                @endif
                <p class="ta-cat-card-item__slug"><code>{{ $sub->slug }}</code></p>
                <div class="ta-cat-card-item__meta">
                    <span>{{ $sub->posts_count }} posts</span>
                    <span>{{ $sub->created_at->format('d-m-Y') }}</span>
                </div>
                <div class="ta-cat-card-item__actions">
                    <a href="{{ route('tenant-admin.categories.edit', $sub) }}" class="ta-cat-action-btn ta-cat-action-btn--edit" title="Edit">Edit</a>
                    <form method="POST" action="{{ route('tenant-admin.categories.destroy', $sub) }}" onsubmit="return confirm('Delete?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="ta-cat-action-btn ta-cat-action-btn--delete" title="Delete">Delete</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="ta-cat-empty ta-cat-card-item--empty">No subcategories found.</div>
        @endforelse
    </div>

    @if ($subcategories->total() > 0)
        <p class="ta-cat-pagination__info">
            Showing {{ $subcategories->firstItem() }}–{{ $subcategories->lastItem() }} of {{ $subcategories->total() }}
            · Page {{ $subcategories->currentPage() }} of {{ $subcategories->lastPage() }}
        </p>
    @endif

    @if ($subcategories->hasPages())
        <div class="ta-cat-pagination">{{ $subcategories->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
