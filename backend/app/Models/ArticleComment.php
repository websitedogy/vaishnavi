<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleComment extends Model
{
    protected $fillable = [
        'tenant_id', 'news_post_id', 'author_name', 'author_email', 'body',
        'is_approved', 'is_spam', 'ip_address',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_spam' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(NewsPost::class, 'news_post_id');
    }
}
