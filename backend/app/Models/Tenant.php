<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'domain',
        'logo',
        'subscription',
        'subscription_amount',
        'status',
        'trial_ends_at',
        'subscription_ends_at',
        'suspended_at',
    ];

    protected $casts = [
        'status' => TenantStatus::class,
        'subscription_amount' => 'decimal:2',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $original = $slug;
        $count = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function newsPosts(): HasMany
    {
        return $this->hasMany(NewsPost::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function theme(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(TenantTheme::class);
    }

    public function advertisements(): HasMany
    {
        return $this->hasMany(Advertisement::class);
    }

    public function trafficStats(): HasMany
    {
        return $this->hasMany(TenantTrafficStat::class);
    }

    public function subdomainHost(): string
    {
        return $this->slug.'.'.config('platform.base_domain');
    }

    /** Path-based public site URL (dev & until custom domain is wired). */
    public function publicSiteUrl(): string
    {
        return route('tenant-site.home', $this->slug);
    }

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::Suspended;
    }

    public function suspend(): void
    {
        $this->update([
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status' => TenantStatus::Active,
            'suspended_at' => null,
        ]);
    }
}
