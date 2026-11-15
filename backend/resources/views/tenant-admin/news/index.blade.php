@extends('layouts.tenant-admin')

@section('title', 'Total Posts')
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
                <a href="{{ route('tenant-admin.dashboard') }}">Dashboard</a>
                <span aria-hidden="true">/</span>
                <span>Total Posts</span>
            </p>
            <h1 class="ta-cat-page__title">Total Posts</h1>
            <p class="ta-cat-page__subtitle">
                @if ($selectedCategory)
                    Posts in <strong>{{ $selectedCategory->name }}</strong>
                @elseif(request()->boolean('uncategorized'))
                    Posts without a category
                @else
                    All posts — filter by category below
                @endif
            </p>
        </div>
        <a href="{{ route('tenant-admin.news.create') }}" class="ta-cat-btn-add">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Post
        </a>
    </div>

    <div class="ta-cat-stats">
        <div class="ta-cat-stat ta-cat-stat--highlight">
            <span class="ta-cat-stat__value">{{ number_format($totalPosts) }}</span>
            <span class="ta-cat-stat__label">Total posts</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ number_format($publishedCount) }}</span>
            <span class="ta-cat-stat__label">Published</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ number_format($draftCount) }}</span>
            <span class="ta-cat-stat__label">Drafts</span>
        </div>
        <div class="ta-cat-stat">
            <span class="ta-cat-stat__value">{{ $articles->total() }}</span>
            <span class="ta-cat-stat__label">Matching filter</span>
        </div>
    </div>

    @if ($parents->isNotEmpty())
        <div class="ta-cat-parent-legend">
            <span class="ta-cat-parent-legend__title">Posts by category (A–Z)</span>
            <div class="ta-cat-parent-legend__list">
                <a href="{{ route('tenant-admin.news.index') }}"
                   class="ta-cat-parent-link ta-cat-parent-link--all @if(! $selectedCategory && ! request()->boolean('uncategorized')) is-active @endif">
                    All <span class="ta-cat-legend-count">{{ $totalPosts }}</span>
                </a>
                @if ($uncategorizedCount > 0)
                    <a href="{{ route('tenant-admin.news.index', ['uncategorized' => 1]) }}"
                       class="ta-cat-parent-link ta-cat-parent-link--slate @if(request()->boolean('uncategorized')) is-active @endif">
                        Uncategorized <span class="ta-cat-legend-count">{{ $uncategorizedCount }}</span>
                    </a>
                @endif
                @foreach ($parents->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $parent)
                    @php $color = $parentColorMap[$parent->id] ?? 'blue'; @endphp
                    <a href="{{ route('tenant-admin.news.index', ['category_id' => $parent->id]) }}"
                       class="ta-cat-parent-link ta-cat-parent-link--{{ $color }} @if($selectedCategory && ($selectedCategory->id === $parent->id || $selectedCategory->parent_id === $parent->id)) is-active @endif">
                        {{ $parent->name }}
                        <span class="ta-cat-legend-count">{{ $parentPostCounts[$parent->id] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="ta-cat-toolbar">
        <form method="GET" class="ta-cat-toolbar__form ta-cat-toolbar__form--wide">
            <div class="ta-cat-toolbar__field">
                <label for="search">Search</label>
                <input type="search" name="search" id="search" class="ta-cat-toolbar__input"
                       value="{{ request('search') }}" placeholder="Title or slug…">
            </div>
            <div class="ta-cat-toolbar__field">
                <label for="status">Status</label>
                <select name="status" id="status" class="ta-cat-toolbar__select">
                    <option value="">All statuses</option>
                    @foreach (['draft', 'scheduled', 'published', 'archived'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ta-cat-toolbar__field ta-cat-toolbar__field--check">
                <label class="ta-cat-toolbar__checkbox">
                    <input type="checkbox" name="breaking" value="1" @checked(request()->boolean('breaking'))>
                    Breaking only
                </label>
            </div>
            @if (request()->hasAny(['search', 'status', 'breaking', 'category_id', 'uncategorized']))
                <a href="{{ route('tenant-admin.news.index') }}" class="ta-cat-toolbar__clear">Clear filters</a>
            @endif
            <button type="submit" class="ta-cat-btn-add ta-cat-btn-add--sm">Filter</button>
        </form>
        <a href="{{ route('tenant-admin.categories.index') }}" class="ta-cat-toolbar__back">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Categories
        </a>
    </div>

    <form method="POST" action="{{ route('tenant-admin.news.bulk') }}" id="bulkForm">
        @csrf
        <div class="ta-cat-table-wrap ta-cat-table-wrap--desktop">
            <table class="ta-cat-table ta-cat-table--posts">
                <thead>
                    <tr>
                        <th style="width: 2.5rem"><input type="checkbox" id="checkAll" aria-label="Select all"></th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th style="width: 5rem">Views</th>
                        <th style="width: 8rem">Updated</th>
                        <th style="width: 7rem; text-align: right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($articles as $article)
                        @php
                            $cat = $article->category;
                            $parent = $cat?->parent ?? ($cat && ! $cat->parent_id ? $cat : null);
                            $parentColor = $parent ? ($parentColorMap[$parent->id] ?? 'blue') : 'slate';
                        @endphp
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $article->id }}" class="row-check" aria-label="Select"></td>
                            <td>
                                <a href="{{ route('tenant-admin.news.edit', $article) }}" class="ta-cat-table__name ta-cat-table__name--link">
                                    {{ $article->displayTitle() }}
                                </a>
                                @if ($article->is_breaking)<span class="ta-cat-mini-badge ta-cat-mini-badge--breaking">Breaking</span>@endif
                                @if ($article->is_featured)<span class="ta-cat-mini-badge ta-cat-mini-badge--featured">Featured</span>@endif
                                @if ($article->is_trending)<span class="ta-cat-mini-badge ta-cat-mini-badge--trending">Trending</span>@endif
                            </td>
                            <td>
                                @if ($cat)
                                    <span class="ta-cat-parent-link ta-cat-parent-link--{{ $parentColor }} ta-cat-parent-link--static">
                                        {{ $cat->name }}
                                    </span>
                                @else
                                    <span class="ta-cat-parent-link ta-cat-parent-link--slate ta-cat-parent-link--static">Uncategorized</span>
                                @endif
                            </td>
                            <td><span class="ta-cat-badge ta-cat-badge--{{ $article->status === 'published' ? 'active' : 'inactive' }}">{{ ucfirst($article->status) }}</span></td>
                            <td><span class="ta-cat-posts-count">{{ number_format($article->views) }}</span></td>
                            <td>{{ $article->updated_at->format('d-m-Y') }}</td>
                            <td>
                                <div class="ta-cat-actions" style="justify-content: flex-end">
                                    <a href="{{ route('tenant-admin.news.edit', $article) }}" class="ta-cat-action-btn ta-cat-action-btn--edit" title="Edit">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                    <a href="{{ route('tenant-admin.news.preview', $article) }}" target="_blank" class="ta-cat-action-btn" title="Preview">👁</a>
                                    <form method="POST" action="{{ route('tenant-admin.news.destroy', $article) }}" class="inline" onsubmit="return confirm('Delete this post?')">
                                        @csrf @method('DELETE')
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
                                <div class="ta-cat-empty__inner">
                                    <p>No posts found.</p>
                                    <a href="{{ route('tenant-admin.news.create') }}" class="ta-cat-btn-add ta-cat-btn-add--sm">+ Add Post</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ta-cat-cards ta-cat-cards--mobile">
            @forelse ($articles as $article)
                @php
                    $cat = $article->category;
                    $parent = $cat?->parent ?? ($cat && ! $cat->parent_id ? $cat : null);
                    $parentColor = $parent ? ($parentColorMap[$parent->id] ?? 'blue') : 'slate';
                @endphp
                <article class="ta-cat-card-item">
                    <div class="ta-cat-card-item__top">
                        <label class="ta-cat-toolbar__checkbox">
                            <input type="checkbox" name="ids[]" value="{{ $article->id }}" class="row-check">
                            Select
                        </label>
                        <span class="ta-cat-badge ta-cat-badge--{{ $article->status === 'published' ? 'active' : 'inactive' }}">{{ ucfirst($article->status) }}</span>
                    </div>
                    <h3 class="ta-cat-card-item__name">
                        <a href="{{ route('tenant-admin.news.edit', $article) }}">{{ $article->displayTitle() }}</a>
                    </h3>
                    @if ($cat)
                        <span class="ta-cat-parent-link ta-cat-parent-link--{{ $parentColor }} ta-cat-parent-link--static">{{ $cat->name }}</span>
                    @endif
                    <div class="ta-cat-card-item__meta">
                        <span>{{ number_format($article->views) }} views</span>
                        <span>{{ $article->updated_at->format('d-m-Y') }}</span>
                    </div>
                    <div class="ta-cat-card-item__actions">
                        <a href="{{ route('tenant-admin.news.edit', $article) }}" class="ta-cat-action-btn ta-cat-action-btn--edit">Edit</a>
                        <a href="{{ route('tenant-admin.news.preview', $article) }}" target="_blank" class="ta-cat-action-btn">Preview</a>
                    </div>
                </article>
            @empty
                <div class="ta-cat-empty ta-cat-card-item--empty">No posts found.</div>
            @endforelse
        </div>

        <div class="ta-cat-bulk-bar">
            <select name="action" class="ta-cat-toolbar__select">
                <option value="publish">Bulk publish</option>
                <option value="delete">Bulk delete</option>
            </select>
            <button type="submit" class="ta-cat-btn-add ta-cat-btn-add--sm">Apply to selected</button>
        </div>
    </form>

    @if ($articles->total() > 0)
        <p class="ta-cat-pagination__info">
            Showing {{ $articles->firstItem() }}–{{ $articles->lastItem() }} of {{ $articles->total() }}
            · Page {{ $articles->currentPage() }} of {{ $articles->lastPage() }}
        </p>
    @endif

    @if ($articles->hasPages())
        <div class="ta-cat-pagination">{{ $articles->withQueryString()->links() }}</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.getElementById('checkAll')?.addEventListener('change', function () {
    document.querySelectorAll('.row-check').forEach(function (c) { c.checked = this.checked; }.bind(this));
});
</script>
@endpush
