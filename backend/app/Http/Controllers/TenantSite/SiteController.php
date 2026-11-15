<?php

namespace App\Http\Controllers\TenantSite;

use App\Enums\AdPlacement;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Models\Category;
use App\Models\NewsPost;
use App\Models\Tenant;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function home(Tenant $tenant)
    {
        $this->ensureSiteAvailable($tenant);

        $published = fn () => $this->publishedPosts($tenant)->with(['category', 'author']);

        $featured = $published()
            ->where('is_featured', true)
            ->latest('published_at')
            ->limit(4)
            ->get();

        if ($featured->isEmpty()) {
            $featured = $published()->latest('published_at')->limit(4)->get();
        }

        $featuredIds = $featured->pluck('id');

        $latestNews = $published()
            ->when($featuredIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $featuredIds))
            ->latest('published_at')
            ->limit(8)
            ->get();

        $topStories = $published()->latest('published_at')->limit(6)->get();

        $popularNews = $this->publishedPosts($tenant)
            ->with(['category'])
            ->orderByDesc('views')
            ->limit(6)
            ->get();

        $trendingTopics = $this->publishedPosts($tenant)
            ->where('is_trending', true)
            ->latest('published_at')
            ->limit(10)
            ->get();

        if ($trendingTopics->isEmpty()) {
            $trendingTopics = $latestNews->take(8);
        }

        $breaking = $this->publishedPosts($tenant)
            ->where('is_breaking', true)
            ->latest('published_at')
            ->limit(5)
            ->get();

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_visible', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->where('is_visible', true)->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $sidebarAd = $this->activeAd($tenant, AdPlacement::Sidebar);

        return view('tenant-site.home', compact(
            'tenant',
            'featured',
            'latestNews',
            'topStories',
            'popularNews',
            'trendingTopics',
            'breaking',
            'categories',
            'sidebarAd'
        ));
    }

    public function search(Request $request, Tenant $tenant)
    {
        $this->ensureSiteAvailable($tenant);

        $q = trim((string) $request->get('q', ''));

        $posts = $this->publishedPosts($tenant)
            ->with(['category', 'author'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('title', 'like', "%{$q}%")
                        ->orWhere('custom_title', 'like', "%{$q}%")
                        ->orWhere('excerpt', 'like', "%{$q}%");
                });
            })
            ->latest('published_at')
            ->paginate(12);

        return view('tenant-site.search', compact('tenant', 'posts', 'q'));
    }

    public function article(Tenant $tenant, string $slug)
    {
        $this->ensureSiteAvailable($tenant);

        $post = $this->publishedPosts($tenant)
            ->with(['category', 'author', 'tags'])
            ->where('slug', $slug)
            ->firstOrFail();

        $post->increment('views');

        $related = $this->publishedPosts($tenant)
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->limit(4)
            ->get();

        return view('tenant-site.article', compact('tenant', 'post', 'related'));
    }

    public function category(Tenant $tenant, string $slug)
    {
        $this->ensureSiteAvailable($tenant);

        $category = Category::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_visible', true)
            ->firstOrFail();

        $subcategories = Category::where('tenant_id', $tenant->id)
            ->where('parent_id', $category->id)
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $parentCategory = $category->parent_id
            ? Category::where('tenant_id', $tenant->id)->where('is_visible', true)->find($category->parent_id)
            : null;

        $siblingSubcategories = $parentCategory
            ? Category::where('tenant_id', $tenant->id)
                ->where('parent_id', $parentCategory->id)
                ->where('is_visible', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
            : collect();

        $categoryIds = Category::where('tenant_id', $tenant->id)
            ->where(function ($q) use ($category) {
                $q->where('id', $category->id)->orWhere('parent_id', $category->id);
            })
            ->pluck('id');

        $posts = $this->publishedPosts($tenant)
            ->whereIn('category_id', $categoryIds)
            ->with(['category', 'author'])
            ->latest('published_at')
            ->paginate(12);

        return view('tenant-site.category', compact(
            'tenant',
            'category',
            'posts',
            'subcategories',
            'parentCategory',
            'siblingSubcategories'
        ));
    }

    protected function publishedPosts(Tenant $tenant)
    {
        return NewsPost::where('tenant_id', $tenant->id)
            ->where('status', 'published')
            ->where('approval_status', 'approved')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    protected function activeAd(Tenant $tenant, AdPlacement $placement): ?Advertisement
    {
        return Advertisement::query()
            ->where('tenant_id', $tenant->id)
            ->where('placement', $placement)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest('id')
            ->first();
    }

    protected function ensureSiteAvailable(Tenant $tenant): void
    {
        abort_if($tenant->status === TenantStatus::Suspended, 403, 'This news site is temporarily unavailable.');

        abort_unless(
            in_array($tenant->status, [TenantStatus::Active, TenantStatus::Trial], true),
            404
        );
    }
}
