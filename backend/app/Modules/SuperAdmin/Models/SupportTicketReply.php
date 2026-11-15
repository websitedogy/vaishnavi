<?php

namespace App\Modules\SuperAdmin\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketReply extends Model
{
    protected $table = 'support_ticket_replies';

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'sender_type',
        'message',
        'is_internal_note',
        'attachments',
    ];

    protected $casts = [
        'is_internal_note' => 'boolean',
        'attachments' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }
}
