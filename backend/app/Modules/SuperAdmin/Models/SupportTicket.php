<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $table = 'support_tickets';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'assigned_admin_id',
        'last_reply_at',
        'closed_at',
    ];

    protected $casts = [
        'last_reply_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(SuperAdmin::class, 'assigned_admin_id');
    }

    public function replies()
    {
        return $this->hasMany(SupportTicketReply::class, 'support_ticket_id');
    }
}
