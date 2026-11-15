<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class TenantDomain extends Model
{
    protected $table = 'tenant_domains';

    protected $fillable = [
        'tenant_id',
        'type',
        'host',
        'is_primary',
        'verification_status',
        'verification_token',
        'txt_record_host',
        'txt_record_value',
        'cname_host',
        'cname_target',
        'ssl_status',
        'ssl_auto',
        'acm_certificate_arn',
        'cloudfront_distribution_id',
        'ssl_issued_at',
        'ssl_expires_at',
        'dns_checked_at',
        'dns_error',
        'health_status',
        'last_health_check_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'ssl_auto' => 'boolean',
        'ssl_issued_at' => 'datetime',
        'ssl_expires_at' => 'datetime',
        'dns_checked_at' => 'datetime',
        'last_health_check_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function dnsRecords()
    {
        return $this->hasMany(DomainDnsRecord::class, 'domain_id');
    }

    public function sslCertificates()
    {
        return $this->hasMany(DomainSslCertificate::class, 'domain_id');
    }
}
