<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Category;
use App\Models\SlaConfig;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function __construct(private WebhookService $webhooks) {}

    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Ticket::with(['user', 'assignee', 'category'])->withCount(['comments', 'attachments']);

        if ($user->isUser())    $query->where('user_id', $user->id);
        if ($user->isTeknisi()) $query->where('assigned_to', $user->id);

        if ($request->status)      $query->where('status', $request->status);
        if ($request->priority)    $query->where('priority', $request->priority);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->assigned_to) $query->where('assigned_to', $request->assigned_to);
        if ($request->date_from)   $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to)     $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->q)           $query->where(fn($q) => $q->where('title', 'like', "%{$request->q}%")->orWhere('ticket_number', 'like', "%{$request->q}%"));

        $perPage = min((int)($request->per_page ?? 20), 100);
        $tickets = $query->orderByDesc('created_at')->paginate($perPage);

        return TicketResource::collection($tickets);
    }

    public function show(Request $request, string $ticketNumber)
    {
        $user   = $request->user();
        $ticket = Ticket::with(['user', 'assignee', 'category', 'subcategory', 'comments.user', 'attachments', 'rating'])
            ->where('ticket_number', $ticketNumber)->firstOrFail();

        if ($user->isUser() && $ticket->user_id !== $user->id) abort(403);
        if ($user->isTeknisi() && $ticket->assigned_to !== $user->id) abort(403);

        return new TicketResource($ticket);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user->isUser() && !$user->isSupervisor()) {
            return response()->json(['message' => 'Hanya user yang dapat membuat tiket.'], 403);
        }

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'required|string',
            'priority'       => 'required|in:rendah,sedang,tinggi,kritis',
            'category_id'    => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
        ]);

        $slaDeadline = SlaConfig::deadlineFor($data['priority'], null, $data['category_id']);

        $ticket = Ticket::create([
            ...$data,
            'user_id'       => $user->id,
            'status'        => 'open',
            'ticket_number' => 'TKT-' . now()->format('Y') . '-' . str_pad(Ticket::withTrashed()->count() + 1, 4, '0', STR_PAD_LEFT),
            'sla_deadline'  => $slaDeadline,
        ]);

        $this->webhooks->fire('ticket.created', $ticket);

        return (new TicketResource($ticket->load(['user', 'category'])))
            ->response()->setStatusCode(201);
    }

    public function updateStatus(Request $request, string $ticketNumber)
    {
        $user   = $request->user();
        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

        if ($user->isUser() && $ticket->user_id !== $user->id) abort(403);

        $data = $request->validate([
            'status' => 'required|in:assigned,in_progress,pending_user,resolved,closed,cancelled,reopened',
            'note'   => 'nullable|string|max:1000',
        ]);

        $oldStatus = $ticket->status;
        $updates   = ['status' => $data['status']];
        if ($data['status'] === 'resolved' && !$ticket->resolved_at) $updates['resolved_at'] = now();
        if ($data['status'] === 'closed')                             $updates['closed_at']   = now();

        $ticket->update($updates);
        TicketStatusLog::create(['ticket_id' => $ticket->id, 'user_id' => $user->id,
            'old_status' => $oldStatus, 'new_status' => $data['status'], 'note' => $data['note'] ?? '']);

        $this->webhooks->fire('ticket.status_changed', $ticket, ['old_status' => $oldStatus, 'new_status' => $data['status']]);

        return response()->json(['message' => 'Status diperbarui.', 'data' => new TicketResource($ticket)]);
    }

    public function assign(Request $request, string $ticketNumber)
    {
        $user   = $request->user();
        if (!$user->isSupervisor()) return response()->json(['message' => 'Tidak diizinkan.'], 403);

        $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();
        $data   = $request->validate(['assigned_to' => 'required|exists:users,id']);

        $ticket->update(['assigned_to' => $data['assigned_to'], 'status' => 'assigned']);
        $this->webhooks->fire('ticket.assigned', $ticket);

        return response()->json(['message' => 'Tiket berhasil ditugaskan.', 'data' => new TicketResource($ticket->load('assignee'))]);
    }
}
