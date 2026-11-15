<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['tenant', 'user'])->latest('last_replied_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $tickets = $query->paginate(15)->withQueryString();

        return view('super-admin.support.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['tenant', 'user', 'replies.superAdmin', 'replies.user']);

        return view('super-admin.support.show', compact('ticket'));
    }

    public function update(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket->update(['status' => $data['status']]);

        ActivityLogger::log('support_status_updated', 'Support ticket updated', "Ticket #{$ticket->id} status changed to {$data['status']}.", $ticket->tenant);

        return back()->with('success', 'Ticket status updated.');
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        SupportTicketReply::create([
            'support_ticket_id' => $ticket->id,
            'super_admin_id' => auth('super_admin')->id(),
            'message' => $data['message'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        $ticket->update(['last_replied_at' => now()]);

        ActivityLogger::log('support_reply_added', 'Support reply added', "Replied to ticket #{$ticket->id}.", $ticket->tenant);

        return back()->with('success', 'Reply sent.');
    }
}
