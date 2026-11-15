<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'path', 'disk', 'type',
        'mime_type', 'size', 'thumbnail_path', 'alt_text',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        return self::publicUrl($this->path, $this->disk ?? 'public');
    }

    /** Relative public path, e.g. /storage/tenants/1/media/photo.jpg */
    public static function publicPath(string $path, string $disk = 'public'): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');

        if ($disk !== 'public') {
            return Storage::disk($disk)->url($path);
        }

        return '/storage/'.$path;
    }

    /**
     * Full URL for browsers (uses current request host so Laragon vhosts work).
     */
    public static function publicUrl(string $path, string $disk = 'public'): string
    {
        $relative = self::publicPath($path, $disk);

        if (! str_starts_with($relative, '/')) {
            return $relative;
        }

        if (! app()->runningInConsole() && request()->getHttpHost()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/').$relative;
        }

        return asset(ltrim($relative, '/'));
    }

    /** Fix old records saved as http://localhost/storage/... */
    public static function normalizeStoredUrl(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('#^https?://[^/]+/(storage/.+)$#i', $value, $m)) {
            $value = '/'.$m[1];
        }

        if (str_starts_with($value, '/storage/') && request()->getHttpHost()) {
            return rtrim(request()->getSchemeAndHttpHost(), '/').$value;
        }

        return $value;
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }
}
