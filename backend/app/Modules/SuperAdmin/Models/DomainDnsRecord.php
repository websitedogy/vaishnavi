<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class DomainDnsRecord extends Model
{
    protected $table = 'domain_dns_records';

    protected $fillable = [
        'domain_id',
        'record_type',
        'host',
        'expected_value',
        'current_value',
        'status',
        'last_verified_at',
    ];

    protected $casts = [
        'last_verified_at' => 'datetime',
    ];

    public function domain()
    {
        return $this->belongsTo(TenantDomain::class, 'domain_id');
    }
}
