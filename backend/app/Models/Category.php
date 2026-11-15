<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'tenant_id', 'parent_id', 'name', 'slug', 'description',
        'seo_title', 'seo_description', 'icon', 'image', 'sort_order', 'is_visible',
    ];

    protected $casts = ['is_visible' => 'boolean'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeParents($query)
    {
        return $query->whereNull($query->qualifyColumn('parent_id'));
    }

    public function scopeSubcategories($query)
    {
        return $query->whereNotNull($query->qualifyColumn('parent_id'));
    }

    public function isParent(): bool
    {
        return $this->parent_id === null;
    }

    public function isSubcategory(): bool
    {
        return $this->parent_id !== null;
    }

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'category_id');
    }

    public static function uniqueSlug(int $tenantId, string $name, ?int $exceptId = null): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $n = 1;
        while (static::where('tenant_id', $tenantId)->where('slug', $slug)->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->exists()) {
            $slug = $original.'-'.$n++;
        }

        return $slug;
    }
}
