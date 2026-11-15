<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class DomainSslCertificate extends Model
{
    protected $table = 'domain_ssl_certificates';

    protected $fillable = [
        'domain_id',
        'certificate_arn',
        'issuer',
        'domain_name',
        'sans',
        'status',
        'valid_from',
        'valid_to',
        'auto_renew',
    ];

    protected $casts = [
        'sans' => 'array',
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
        'auto_renew' => 'boolean',
    ];

    public function domain()
    {
        return $this->belongsTo(TenantDomain::class, 'domain_id');
    }
}
