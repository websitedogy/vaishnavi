<?php

namespace App\Modules\TenantAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantAdmin\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupportController extends Controller
{
    protected SupportTicketService $ticketService;

    public function __construct(SupportTicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $filters = $request->only(['status', 'priority']);
        $result = $this->ticketService->listTickets($tenantId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['tickets'],
            'open_count' => $result['open_count'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'required|string',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $ticket = $this->ticketService->createTicket($tenantId, $validated, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Support ticket submitted.',
            'data' => $ticket,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $ticket = $this->ticketService->getTicketDetails($tenantId, $id);

        return response()->json([
            'success' => true,
            'data' => $ticket,
        ]);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $reply = $this->ticketService->replyToTicket($tenantId, $id, $validated['message'], $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Reply sent.',
            'data' => $reply,
        ]);
    }

    public function close(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->attributes->get('tenant_id') ?? $request->user()->tenant_id;
        $ticket = $this->ticketService->closeTicket($tenantId, $id, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed.',
            'data' => $ticket,
        ]);
    }
}
