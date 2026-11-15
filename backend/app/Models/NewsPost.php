<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class NewsPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id', 'category_id', 'author_id', 'title', 'subtitle', 'custom_title', 'slug', 'content', 'excerpt',
        'featured_image', 'status', 'views', 'is_breaking', 'is_featured', 'is_trending',
        'scheduled_at', 'published_at', 'meta_title', 'meta_description', 'focus_keyword',
        'is_pillar_content', 'og_title', 'og_description', 'og_image', 'schema_type', 'seo_noindex', 'canonical_url',
        'locale', 'approval_status', 'autosave_data', 'duplicated_from_id',
    ];

    protected $casts = [
        'is_breaking' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_pillar_content' => 'boolean',
        'seo_noindex' => 'boolean',
        'views' => 'integer',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'autosave_data' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag', 'news_post_id', 'tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ArticleComment::class, 'news_post_id');
    }

    public function isScheduled(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture() && $this->status !== 'published';
    }

    public function displayTitle(): string
    {
        return $this->custom_title ?: $this->title;
    }

    public function siteUrl(Tenant $tenant): string
    {
        return route('tenant-site.article', [$tenant->slug, $this->slug]);
    }

    public static function uniqueSlug(int $tenantId, string $title, ?int $exceptId = null): string
    {
        $slug = Str::slug($title) ?: 'article';
        $original = $slug;
        $n = 1;
        while (static::where('tenant_id', $tenantId)->where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = $original.'-'.$n++;
        }

        return $slug;
    }
}
