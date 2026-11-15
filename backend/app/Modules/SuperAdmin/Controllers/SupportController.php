<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\SupportTicket;
use App\Modules\SuperAdmin\Models\SupportTicketReply;
use App\Modules\SuperAdmin\Models\NotificationTemplate;
use App\Modules\SuperAdmin\Models\NotificationLog;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Carbon\Carbon;

class SupportController extends Controller
{
    use ApiResponseTrait;

    public function tickets(Request $request)
    {
        $query = SupportTicket::with(['tenant', 'assignedAdmin']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($tickets);
    }

    public function showTicket(int $id)
    {
        $ticket = SupportTicket::with(['tenant', 'assignedAdmin', 'replies'])->findOrFail($id);
        return $this->successResponse($ticket);
    }

    public function replyTicket(Request $request, int $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'message' => 'required|string',
            'is_internal_note' => 'nullable|boolean',
            'attachments' => 'nullable|array',
        ]);

        $admin = $request->user();

        $reply = SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'user_id' => $admin ? $admin->id : 1,
            'sender_type' => 'super_admin',
            'message' => $validated['message'],
            'is_internal_note' => $validated['is_internal_note'] ?? false,
            'attachments' => $validated['attachments'] ?? null,
        ]);

        $ticket->update([
            'last_reply_at' => Carbon::now(),
            'status' => ($validated['is_internal_note'] ?? false) ? $ticket->status : 'waiting_on_tenant',
        ]);

        AuditHelper::log($admin ? $admin->id : null, 'TICKET_REPLIED', 'SupportTicket', $ticket->id, "Reply posted on ticket #{$ticket->ticket_number}.");

        return $this->successResponse($reply, 'Reply posted successfully.', 201);
    }

    public function updateTicketStatus(Request $request, int $id)
    {
        $ticket = SupportTicket::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|string|in:open,in_progress,waiting_on_tenant,resolved,closed',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'assigned_admin_id' => 'nullable|integer|exists:super_admins,id',
        ]);

        $ticket->update($validated);

        if (isset($validated['status']) && in_array($validated['status'], ['resolved', 'closed'])) {
            $ticket->update(['closed_at' => Carbon::now()]);
        }

        $admin = $request->user();
        AuditHelper::log($admin ? $admin->id : null, 'TICKET_STATUS_UPDATED', 'SupportTicket', $ticket->id, "Ticket status updated.");

        return $this->successResponse($ticket, 'Ticket updated.');
    }

    public function templates()
    {
        $templates = NotificationTemplate::all();
        return $this->successResponse($templates);
    }

    public function sendAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'channel' => 'required|string|in:email,sms,in_app',
            'content' => 'required|string',
            'recipient_scope' => 'required|string|in:all_tenants,active_tenants',
        ]);

        $admin = $request->user();

        NotificationLog::create([
            'recipient' => 'BROADCAST: ' . $validated['recipient_scope'],
            'channel' => $validated['channel'],
            'subject' => $validated['subject'],
            'content' => $validated['content'],
            'status' => 'sent',
            'sent_at' => Carbon::now(),
        ]);

        AuditHelper::log($admin ? $admin->id : null, 'PLATFORM_ANNOUNCEMENT_SENT', 'NotificationLog', null, "Platform announcement broadcasted: {$validated['subject']}.");

        return $this->successResponse(null, 'Platform announcement dispatched to tenants.');
    }
}
