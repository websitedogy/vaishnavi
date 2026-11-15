<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = ['tenant_id', 'name', 'slug'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(NewsPost::class, 'article_tag', 'tag_id', 'news_post_id');
    }

    public static function uniqueSlug(int $tenantId, string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $n = 1;
        while (static::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$n++;
        }

        return $slug;
    }
}
