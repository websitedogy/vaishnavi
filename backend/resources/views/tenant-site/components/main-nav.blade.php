@php
    $menuCategories = ($navCategories ?? collect())->isNotEmpty()
        ? $navCategories
        : collect([
            (object) ['name' => 'Andhra Pradesh', 'slug' => 'andhra-pradesh'],
            (object) ['name' => 'Telangana', 'slug' => 'telangana'],
            (object) ['name' => 'National', 'slug' => 'national'],
            (object) ['name' => 'World', 'slug' => 'world'],
            (object) ['name' => 'Cinema', 'slug' => 'cinema'],
            (object) ['name' => 'Politics', 'slug' => 'politics'],
            (object) ['name' => 'Business', 'slug' => 'business'],
            (object) ['name' => 'Technology', 'slug' => 'technology'],
        ]);
    $currentSlug = request()->route('slug');
@endphp
<nav class="ts-main-nav" id="ts-main-nav">
    <div class="ts-container ts-main-nav__inner">
        <button type="button" class="ts-main-nav__toggle" id="ts-nav-toggle" aria-expanded="false" aria-controls="ts-nav-menu">
            <span></span><span></span><span></span>
        </button>
        <ul class="ts-main-nav__list" id="ts-nav-menu">
            <li class="ts-nav-item">
                <a href="{{ route('tenant-site.home', $tenant->slug) }}"
                   class="ts-nav-link @if(request()->routeIs('tenant-site.home')) is-active @endif">Home</a>
            </li>
            @foreach($menuCategories as $category)
                @php
                    $children = $category instanceof \App\Models\Category
                        ? $category->children
                        : collect();
                    $hasChildren = $children->isNotEmpty();
                    $isCatActive = request()->routeIs('tenant-site.category') && (
                        $currentSlug === $category->slug
                        || $children->contains('slug', $currentSlug)
                    );
                @endphp
                <li class="ts-nav-item @if($hasChildren) ts-nav-item--parent @endif @if($isCatActive) is-active is-open @endif">
                    @if($hasChildren)
                        <div class="ts-nav-parent-row">
                            <a href="{{ route('tenant-site.category', [$tenant->slug, $category->slug]) }}"
                               class="ts-nav-link @if($currentSlug === $category->slug) is-active @endif">
                                {{ $category->name }}
                            </a>
                            <button type="button"
                                    class="ts-nav-expand"
                                    aria-expanded="{{ $isCatActive ? 'true' : 'false' }}"
                                    aria-label="Show {{ $category->name }} subcategories">
                                <svg class="ts-nav-chevron" width="10" height="10" viewBox="0 0 10 10" aria-hidden="true">
                                    <path d="M2 3.5L5 6.5L8 3.5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                </svg>
                            </button>
                        </div>
                        <ul class="ts-nav-dropdown" @if(! $isCatActive) hidden @endif>
                            <li>
                                <a href="{{ route('tenant-site.category', [$tenant->slug, $category->slug]) }}"
                                   class="ts-nav-dropdown__all @if($currentSlug === $category->slug) is-active @endif">
                                    All {{ $category->name }}
                                </a>
                            </li>
                            @foreach($children as $child)
                                <li>
                                    <a href="{{ route('tenant-site.category', [$tenant->slug, $child->slug]) }}"
                                       @if($currentSlug === $child->slug) class="is-active" @endif>
                                        {{ $child->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ route('tenant-site.category', [$tenant->slug, $category->slug]) }}"
                           class="ts-nav-link @if($isCatActive) is-active @endif">
                            {{ $category->name }}
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
</nav>
