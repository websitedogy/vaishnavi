@php
    $items = $subcategories ?? collect();
    if ($items->isEmpty() && ! empty($siblingSubcategories) && $siblingSubcategories->isNotEmpty()) {
        $items = $siblingSubcategories;
    }
@endphp
@if($items->isNotEmpty())
<section class="ts-subcats" aria-label="Subcategories">
    <h2 class="ts-subcats__title">
        @if(! empty($parentCategory))
            {{ $parentCategory->name }} — Subcategories
        @else
            Subcategories
        @endif
    </h2>
    <div class="ts-subcats__grid">
        @foreach($items as $sub)
            <a href="{{ route('tenant-site.category', [$tenant->slug, $sub->slug]) }}"
               class="ts-subcats__card @if(isset($category) && $category->id === $sub->id) is-active @endif">
                <span class="ts-subcats__name">{{ $sub->name }}</span>
                <span class="ts-subcats__arrow" aria-hidden="true">→</span>
            </a>
        @endforeach
    </div>
</section>
@endif
