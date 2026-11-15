<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Category;
use App\Models\MediaFile;
use App\Models\NewsPost;
use App\Models\Tag;
use App\Support\CategoryParentColors;
use App\Services\StaffActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsArticleController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $tenantId = $this->tenantId();
        $query = NewsPost::with(['category.parent', 'author'])
            ->where('tenant_id', $tenantId);

        if ($search = $request->get('search')) {
            $query->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('custom_title', 'like', "%{$search}%"));
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($request->boolean('breaking')) {
            $query->where('is_breaking', true);
        }

        $categoryId = $request->get('category_id');
        $selectedCategory = null;

        if ($request->boolean('uncategorized')) {
            $query->whereNull('category_id');
        } elseif ($categoryId) {
            $selectedCategory = Category::where('tenant_id', $tenantId)->find($categoryId);
            if ($selectedCategory) {
                $query->whereIn('category_id', $this->categoryIdsForFilter($selectedCategory));
            }
        }

        $articles = $query->latest('updated_at')->paginate(20)->withQueryString();

        $parents = Category::where('tenant_id', $tenantId)->parents()->orderBy('name')->get();
        $parentColorMap = CategoryParentColors::map($parents);
        $parentPostCounts = $this->parentPostCounts($parents, $tenantId);

        $totalPosts = NewsPost::where('tenant_id', $tenantId)->count();
        $publishedCount = NewsPost::where('tenant_id', $tenantId)->where('status', 'published')->count();
        $draftCount = NewsPost::where('tenant_id', $tenantId)->where('status', 'draft')->count();
        $uncategorizedCount = NewsPost::where('tenant_id', $tenantId)->whereNull('category_id')->count();

        return view('tenant-admin.news.index', compact(
            'articles',
            'parents',
            'parentColorMap',
            'parentPostCounts',
            'selectedCategory',
            'totalPosts',
            'publishedCount',
            'draftCount',
            'uncategorizedCount'
        ));
    }

    public function create()
    {
        return view('tenant-admin.news.post-editor', [
            'article' => new NewsPost(['status' => 'draft', 'locale' => 'en', 'approval_status' => 'approved']),
            'parentCategories' => $this->parentCategories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('add_news');
        $data = $this->validateArticle($request);
        $data = $this->handleFeaturedImage($request, $data);
        $data['tenant_id'] = $this->tenantId();
        $data['author_id'] = $this->user()->id;
        $data['slug'] = $this->resolveSlug($request, $data['title']);
        $data = $this->applyPublishState($data, $request);

        if (! $this->user()->hasPermission('publish_news')) {
            $data['status'] = 'draft';
            $data['approval_status'] = 'pending';
        }

        $article = NewsPost::create($data);
        $this->syncTags($article, $request->input('tags'));

        StaffActivityLogger::log('article_created', "Created article: {$article->title}", $article);

        return redirect()->route('tenant-admin.news.index')->with('success', 'Article saved.');
    }

    public function edit(NewsPost $news)
    {
        $this->ensureTenant($news);

        return view('tenant-admin.news.post-editor', [
            'article' => $news->load('tags'),
            'parentCategories' => $this->parentCategories(),
        ]);
    }

    public function update(Request $request, NewsPost $news)
    {
        $this->ensureTenant($news);
        $this->authorizePermission('add_news');
        $data = $this->validateArticle($request, $news->id);
        $data = $this->handleFeaturedImage($request, $data);
        if ($request->boolean('regenerate_slug') || $request->filled('slug')) {
            $data['slug'] = $this->resolveSlug($request, $data['title'], $news->id);
        }
        $data = $this->applyPublishState($data, $request);

        if (! $this->user()->hasPermission('publish_news') && $data['status'] === 'published') {
            $data['status'] = 'draft';
            $data['approval_status'] = 'pending';
        }

        $news->update($data);
        $this->syncTags($news, $request->input('tags'));

        StaffActivityLogger::log('article_updated', "Updated article: {$news->title}", $news);

        return redirect()->route('tenant-admin.news.index')->with('success', 'Article updated.');
    }

    public function destroy(NewsPost $news)
    {
        $this->ensureTenant($news);
        $this->authorizePermission('delete_news');
        $title = $news->title;
        $news->delete();
        StaffActivityLogger::log('article_deleted', "Deleted article: {$title}");

        return back()->with('success', 'Article deleted.');
    }

    public function preview(NewsPost $news)
    {
        $this->ensureTenant($news);

        return view('tenant-admin.news.preview', compact('news'));
    }

    public function previewDraft(Request $request, ?NewsPost $news = null)
    {
        if ($news) {
            $this->ensureTenant($news);
        }

        session(['tenant_post_preview_'.$this->tenantId() => $this->previewPayloadFromRequest($request, $news)]);

        return response()->json(['url' => route('tenant-admin.news.preview-session')]);
    }

    public function previewSession()
    {
        $data = session('tenant_post_preview_'.$this->tenantId());
        abort_unless($data, 404);

        $news = new NewsPost($data);
        if (! empty($data['id'])) {
            $news->id = $data['id'];
            $news->exists = true;
        }

        return view('tenant-admin.news.preview', compact('news'));
    }

    protected function previewPayloadFromRequest(Request $request, ?NewsPost $news = null): array
    {
        $featured = $request->input('featured_image');
        if ($featured) {
            $featured = MediaFile::normalizeStoredUrl($featured);
        }

        return [
            'id' => $news?->id,
            'title' => $request->input('title', 'Untitled'),
            'subtitle' => $request->input('subtitle'),
            'custom_title' => $request->input('custom_title'),
            'content' => $request->input('content', ''),
            'featured_image' => $featured,
            'is_breaking' => $request->boolean('is_breaking'),
            'is_trending' => $request->boolean('is_trending'),
            'locale' => $request->input('locale', $news?->locale ?? 'en'),
            'published_at' => $news?->published_at,
            'status' => $request->input('status', $news?->status ?? 'draft'),
        ];
    }

    public function duplicate(NewsPost $news)
    {
        $this->ensureTenant($news);
        $this->authorizePermission('add_news');
        $copy = $news->replicate(['views', 'published_at']);
        $copy->title = $news->title.' (Copy)';
        $copy->slug = NewsPost::uniqueSlug($this->tenantId(), $copy->title);
        $copy->status = 'draft';
        $copy->approval_status = 'pending';
        $copy->duplicated_from_id = $news->id;
        $copy->save();
        $copy->tags()->sync($news->tags->pluck('id'));

        return redirect()->route('tenant-admin.news.edit', $copy)->with('success', 'Article duplicated.');
    }

    public function autosave(Request $request, ?NewsPost $news = null)
    {
        $payload = $request->only(['title', 'subtitle', 'content', 'excerpt']);
        if ($news) {
            $this->ensureTenant($news);
            $news->update(['autosave_data' => $payload + ['saved_at' => now()->toIso8601String()]]);
        }

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function bulk(Request $request)
    {
        $request->validate([
            'action' => 'required|in:publish,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $posts = NewsPost::where('tenant_id', $this->tenantId())->whereIn('id', $request->ids)->get();

        if ($request->action === 'delete') {
            $this->authorizePermission('delete_news');
            foreach ($posts as $post) {
                $post->delete();
            }

            return back()->with('success', count($posts).' articles deleted.');
        }

        $this->authorizePermission('publish_news');
        foreach ($posts as $post) {
            $post->update(['status' => 'published', 'published_at' => now(), 'approval_status' => 'approved']);
        }

        return back()->with('success', count($posts).' articles published.');
    }

    public function approve(NewsPost $news)
    {
        $this->ensureTenant($news);
        $this->authorizePermission('approve_news');
        $news->update(['approval_status' => 'approved', 'status' => 'published', 'published_at' => now()]);
        StaffActivityLogger::log('article_approved', "Approved: {$news->title}", $news);

        return back()->with('success', 'Article approved and published.');
    }

    protected function validateArticle(Request $request, ?int $exceptId = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'excerpt' => 'nullable|string|max:500',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'status' => 'required|in:draft,scheduled,published,archived',
            'is_breaking' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'scheduled_at' => 'nullable|date',
            'featured_image' => 'nullable|string|max:500',
            'tags' => 'nullable|string|max:2000',
            'primary_category_id' => 'nullable|integer|exists:categories,id',
            'custom_title' => 'nullable|string|max:255',
            'is_trending' => 'sometimes|boolean',
            'views' => 'nullable|integer|min:0',
            'slug' => 'nullable|string|max:255',
            'focus_keyword' => 'nullable|string|max:100',
            'meta_description' => 'nullable|string|max:500',
            'meta_title' => 'nullable|string|max:255',
            'is_pillar_content' => 'sometimes|boolean',
            'og_title' => 'nullable|string|max:255',
            'og_description' => 'nullable|string|max:500',
            'og_image' => 'nullable|string|max:500',
            'schema_type' => 'nullable|in:article,newsarticle,blogposting',
            'seo_noindex' => 'sometimes|boolean',
            'canonical_url' => 'nullable|url|max:500',
        ]);

        $categoryIds = array_map('intval', $request->input('category_ids', []));
        $primaryId = $request->input('primary_category_id') ? (int) $request->input('primary_category_id') : null;

        $data['category_id'] = null;
        if ($primaryId && Category::where('tenant_id', $this->tenantId())->where('id', $primaryId)->exists()) {
            $data['category_id'] = $primaryId;
        } else {
            foreach ($categoryIds as $id) {
                if (Category::where('tenant_id', $this->tenantId())->where('id', $id)->exists()) {
                    $data['category_id'] = $id;
                    break;
                }
            }
        }
        $data['locale'] = $request->input('locale', 'en');
        unset($data['category_ids'], $data['primary_category_id']);

        return $data;
    }

    protected function applyPublishState(array $data, Request $request): array
    {
        $data['is_breaking'] = $request->boolean('is_breaking');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_pillar_content'] = $request->boolean('is_pillar_content');
        $data['seo_noindex'] = $request->boolean('seo_noindex');
        if (! $request->filled('views')) {
            unset($data['views']);
        }

        if ($data['status'] === 'scheduled' && empty($data['scheduled_at'])) {
            $data['status'] = 'draft';
        } elseif ($data['status'] === 'published') {
            $data['published_at'] = now();
            $data['approval_status'] = 'approved';
        }

        return $data;
    }

    protected function syncTags(NewsPost $article, ?string $tagsInput): void
    {
        if ($tagsInput === null) {
            return;
        }
        $names = array_filter(array_map('trim', explode(',', $tagsInput)));
        $ids = [];
        foreach ($names as $name) {
            $tag = Tag::firstOrCreate(
                ['tenant_id' => $this->tenantId(), 'slug' => Str::slug($name)],
                ['name' => $name]
            );
            $ids[] = $tag->id;
        }
        $article->tags()->sync($ids);
    }

    protected function parentCategories()
    {
        return Category::with(['children' => fn ($q) => $q->where('is_visible', true)->orderBy('sort_order')->orderBy('name')])
            ->where('tenant_id', $this->tenantId())
            ->whereNull('parent_id')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function resolveSlug(Request $request, string $title, ?int $exceptId = null): string
    {
        $base = $request->filled('slug') ? $request->input('slug') : $title;

        return NewsPost::uniqueSlug($this->tenantId(), $base, $exceptId);
    }

    protected function handleFeaturedImage(Request $request, array $data): array
    {
        if ($request->hasFile('featured_image_file')) {
            $path = $request->file('featured_image_file')->store(
                'tenant-'.$this->tenantId().'/posts',
                'public'
            );
            $data['featured_image'] = MediaFile::publicPath($path);
        }

        return $data;
    }

    /**
     * @return array<int>
     */
    protected function categoryIdsForFilter(Category $category): array
    {
        if ($category->parent_id) {
            return [$category->id];
        }

        return Category::where('tenant_id', $category->tenant_id)
            ->where(fn ($q) => $q->where('id', $category->id)->orWhere('parent_id', $category->id))
            ->pluck('id')
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Category>  $parents
     * @return array<int, int>
     */
    protected function parentPostCounts($parents, int $tenantId): array
    {
        $counts = [];
        foreach ($parents as $parent) {
            $ids = Category::where('tenant_id', $tenantId)
                ->where(fn ($q) => $q->where('id', $parent->id)->orWhere('parent_id', $parent->id))
                ->pluck('id');
            $counts[$parent->id] = NewsPost::where('tenant_id', $tenantId)
                ->whereIn('category_id', $ids)
                ->count();
        }

        return $counts;
    }

    protected function ensureTenant(NewsPost $news): void
    {
        abort_unless($news->tenant_id === $this->tenantId(), 404);
    }
}
