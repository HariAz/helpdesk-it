<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\SendNewTicketNotification;
use App\Jobs\SendTicketAssignedNotification;
use App\Jobs\SendTicketResolvedNotification;
use App\Jobs\SendTicketStatusChangedNotification;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\RatingToken;
use App\Models\SlaConfig;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Ticket::with(['user', 'assignee', 'category']);

        if ($user->isUser()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isTeknisi()) {
            $query->where('assigned_to', $user->id);
        }

        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->category_id) $query->where('category_id', $request->category_id);
        if ($request->assigned_to) $query->where('assigned_to', $request->assigned_to);
        if ($request->date_from) $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->date_to) $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->tab === 'active') $query->whereNotIn('status', ['closed', 'cancelled', 'resolved']);
        if ($request->tab === 'pending') $query->where('status', 'pending_user');
        if ($request->tab === 'resolved') $query->where('status', 'resolved');
        if ($request->tab === 'closed') $query->whereIn('status', ['closed', 'cancelled']);

        $sort = $request->sort ?? 'created_at';
        $dir = $request->dir ?? 'desc';
        $tickets = $query->orderBy($sort, $dir)->paginate(20)->withQueryString();

        $categories = Category::whereNull('parent_id')->with('children')->get();
        $teknisi = User::where('role', 'teknisi')->get();

        return view('tickets.index', compact('tickets', 'categories', 'teknisi'));
    }

    public function create()
    {
        $categories = Category::whereNull('parent_id')->where('is_active', true)->with('children')->get();
        return view('tickets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'subcategory_id' => 'nullable|exists:categories,id',
            'priority' => 'required|in:kritis,tinggi,sedang,rendah',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx',
        ]);

        $ticketNumber = 'TKT-' . now()->format('Ymd') . '-' . str_pad(
            Ticket::whereDate('created_at', today())->count() + 1, 4, '0', STR_PAD_LEFT
        );

        $ticket = Ticket::create([
            ...$data,
            'user_id' => auth()->id(),
            'ticket_number' => $ticketNumber,
            'status' => 'open',
            'sla_deadline' => SlaConfig::deadlineFor($data['priority']),
        ]);

        TicketStatusLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'from_status' => null,
            'to_status' => 'open',
            'note' => 'Tiket dibuat',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $stored = $file->store('tickets/' . now()->format('Y/m'), 'local');
                $ticket->attachments()->create([
                    'uploaded_by' => auth()->id(),
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => basename($stored),
                    'path' => $stored,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        ActivityLog::record('ticket_created', $ticket, ['ticket_number' => $ticket->ticket_number]);

        // In-app notification to supervisors
        User::where('role', 'supervisor')->where('is_active', true)->each(
            fn($s) => $s->notify(new TicketNotification('new_ticket', $ticket,
                "Tiket baru {$ticket->ticket_number}: {$ticket->title}", 'bi-plus-circle', 'primary'))
        );

        SendNewTicketNotification::dispatch($ticket);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Tiket berhasil dibuat: ' . $ticketNumber);
    }

    public function show(Ticket $ticket)
    {
        $user = auth()->user();
        if ($user->isUser() && $ticket->user_id !== $user->id) abort(403);
        if ($user->isTeknisi() && $ticket->assigned_to !== $user->id) abort(403);

        $ticket->load([
            'user', 'assignee', 'category', 'subcategory',
            'comments.user', 'comments.attachments',
            'attachments.uploader',
            'statusLogs.user',
            'assignments.assignee', 'assignments.assigner',
            'rating',
        ]);

        $teknisi = $user->isSupervisor() ? User::where('role', 'teknisi')->get() : collect();

        return view('tickets.show', compact('ticket', 'teknisi'));
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:assigned,in_progress,pending_user,resolved,closed,cancelled,reopened',
            'note' => 'required|string',
        ]);

        $oldStatus = $ticket->status;
        $ticket->update(['status' => $data['status']]);

        if ($data['status'] === 'resolved') {
            $ticket->update(['resolved_at' => now()]);
            $token = RatingToken::create([
                'ticket_id' => $ticket->id,
                'token' => Str::random(64),
                'is_used' => false,
                'expires_at' => now()->addDays(7),
            ]);
            SendTicketResolvedNotification::dispatch($ticket->fresh());
        }

        if ($data['status'] === 'closed') $ticket->update(['closed_at' => now()]);

        TicketStatusLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'from_status' => $oldStatus,
            'to_status' => $data['status'],
            'note' => $data['note'],
        ]);

        ActivityLog::record('ticket_status_updated', $ticket, ['from' => $oldStatus, 'to' => $data['status']]);

        // In-app notification to ticket owner
        $statusLabel = \App\Models\Ticket::STATUS_LABELS[$data['status']] ?? $data['status'];
        $ticket->user->notify(new TicketNotification('status_changed', $ticket,
            "Status tiket {$ticket->ticket_number} berubah menjadi {$statusLabel}", 'bi-arrow-repeat', 'info'));

        SendTicketStatusChangedNotification::dispatch($ticket->fresh(), $oldStatus, $data['status'], $data['note']);

        return back()->with('success', 'Status tiket diperbarui.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'note' => 'nullable|string',
        ]);

        $oldAssignee = $ticket->assigned_to;
        $ticket->update(['assigned_to' => $data['assigned_to'], 'status' => 'assigned']);

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'assigned_to' => $data['assigned_to'],
            'assigned_by' => auth()->id(),
            'unassigned_from' => $oldAssignee,
            'note' => $data['note'] ?? null,
        ]);

        TicketStatusLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'from_status' => $ticket->getOriginal('status'),
            'to_status' => 'assigned',
            'note' => 'Tiket di-assign ke teknisi',
        ]);

        ActivityLog::record('ticket_assigned', $ticket);

        // In-app notification to assigned teknisi
        $assignee = User::find($data['assigned_to']);
        if ($assignee) {
            $assignee->notify(new TicketNotification('assigned', $ticket,
                "Tiket {$ticket->ticket_number} di-assign kepada Anda", 'bi-person-check', 'success'));
        }

        SendTicketAssignedNotification::dispatch($ticket->fresh());

        return back()->with('success', 'Tiket berhasil di-assign.');
    }

    public function updatePriority(Request $request, Ticket $ticket)
    {
        $request->validate(['priority' => 'required|in:kritis,tinggi,sedang,rendah']);
        $ticket->update(['priority' => $request->priority]);
        ActivityLog::record('ticket_priority_updated', $ticket, ['priority' => $request->priority]);
        return back()->with('success', 'Prioritas tiket diperbarui.');
    }

    public function destroy(Ticket $ticket)
    {
        $ticket->delete();
        ActivityLog::record('ticket_deleted', $ticket);
        return redirect()->route('tickets.index')->with('success', 'Tiket dihapus.');
    }

    public function trash()
    {
        $tickets = Ticket::onlyTrashed()->with('user')->paginate(20);
        return view('tickets.trash', compact('tickets'));
    }

    public function restore($id)
    {
        $ticket = Ticket::onlyTrashed()->findOrFail($id);
        $ticket->restore();
        return back()->with('success', 'Tiket dipulihkan.');
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = Ticket::with(['user', 'assignee', 'category']);
        if ($user->isUser()) $query->where('user_id', $user->id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);

        $tickets = $query->get();

        $headers = ['Content-Type' => 'text/csv; charset=UTF-8'];
        $filename = 'tickets-' . now()->format('Ymd') . '.csv';

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No. Tiket', 'Judul', 'Status', 'Prioritas', 'Pelapor', 'Teknisi', 'Kategori', 'Dibuat', 'SLA Deadline']);
            foreach ($tickets as $t) {
                fputcsv($file, [
                    $t->ticket_number, $t->title, $t->status, $t->priority,
                    $t->user->name, $t->assignee?->name ?? '-',
                    $t->category?->name ?? '-',
                    $t->created_at->format('Y-m-d H:i'),
                    $t->sla_deadline?->format('Y-m-d H:i') ?? '-',
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
