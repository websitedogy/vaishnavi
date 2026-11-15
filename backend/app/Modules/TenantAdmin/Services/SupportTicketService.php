<?php

namespace App\Modules\TenantAdmin\Services;

use App\Modules\SuperAdmin\Models\SupportTicket;
use App\Modules\SuperAdmin\Models\SupportTicketReply;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SupportTicketService
{
    public function listTickets(int $tenantId, array $filters = []): array
    {
        $query = SupportTicket::where('tenant_id', $tenantId)->with('replies');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        $tickets = $query->orderBy('created_at', 'desc')->get();

        return [
            'tickets' => $tickets,
            'open_count' => $tickets->where('status', 'open')->count(),
        ];
    }

    public function createTicket(int $tenantId, array $data, ?int $userId = null): SupportTicket
    {
        $ticketNumber = 'TICK-' . date('Ymd') . '-' . rand(1000, 9999);

        $ticket = SupportTicket::create([
            'tenant_id' => $tenantId,
            'ticket_number' => $ticketNumber,
            'subject' => $data['subject'],
            'category' => $data['category'] ?? 'Technical Support',
            'priority' => $data['priority'] ?? 'medium',
            'status' => 'open',
            'last_reply_at' => Carbon::now(),
        ]);

        // Add first message
        if (!empty($data['description'])) {
            SupportTicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $userId,
                'message' => $data['description'],
                'is_internal_note' => false,
            ]);
        }

        TenantAuditHelper::log($tenantId, $userId, 'ticket_created', 'support', "Opened support ticket {$ticket->ticket_number}.");

        return $ticket->load('replies');
    }

    public function getTicketDetails(int $tenantId, int $ticketId): SupportTicket
    {
        return SupportTicket::where('tenant_id', $tenantId)
            ->where('id', $ticketId)
            ->with(['replies' => function ($q) {
                // Tenant staff can only view public replies (never staff internal notes!)
                $q->where('is_internal_note', false)->orderBy('created_at', 'asc');
            }])
            ->firstOrFail();
    }

    public function replyToTicket(int $tenantId, int $ticketId, string $message, ?int $userId = null): SupportTicketReply
    {
        $ticket = SupportTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);

        $reply = SupportTicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $message,
            'is_internal_note' => false,
        ]);

        $ticket->status = 'open'; // Re-open or mark as awaiting support reply
        $ticket->last_reply_at = Carbon::now();
        $ticket->save();

        TenantAuditHelper::log($tenantId, $userId, 'ticket_reply', 'support', "Replied to support ticket {$ticket->ticket_number}.");

        return $reply;
    }

    public function closeTicket(int $tenantId, int $ticketId, ?int $userId = null): SupportTicket
    {
        $ticket = SupportTicket::where('tenant_id', $tenantId)->findOrFail($ticketId);
        $ticket->status = 'closed';
        $ticket->save();

        TenantAuditHelper::log($tenantId, $userId, 'ticket_closed', 'support', "Closed support ticket {$ticket->ticket_number}.");

        return $ticket;
    }
}
