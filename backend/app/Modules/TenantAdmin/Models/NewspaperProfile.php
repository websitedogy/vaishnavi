<?php

namespace App\Modules\TenantAdmin\Models;

use Illuminate\Database\Eloquent\Model;
use App\Modules\SuperAdmin\Models\Tenant;

class NewspaperProfile extends Model
{
    protected $table = 'newspaper_profiles';

    protected $fillable = [
        'tenant_id',
        'newspaper_name',
        'company_name',
        'publisher_name',
        'editor_name',
        'contact_person',
        'email',
        'phone',
        'whatsapp',
        'address',
        'state',
        'district',
        'mandal',
        'pincode',
        'language',
        'logo_url',
        'favicon_url',
        'about_newspaper',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }
}
