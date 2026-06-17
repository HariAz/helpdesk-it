<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewCommentNotification;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketStatusLog;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $user = auth()->user();
        $data = $request->validate([
            'body' => 'required|string',
            'is_internal' => 'boolean',
        ]);

        if ($data['is_internal'] ?? false) {
            if (!$user->isTeknisi() && !$user->isSupervisor()) abort(403);
        }

        $comment = $ticket->comments()->create([
            'user_id' => $user->id,
            'body' => $data['body'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // User reply changes Pending User → In Progress
        if ($ticket->status === 'pending_user' && $user->isUser()) {
            $ticket->update(['status' => 'in_progress']);
            TicketStatusLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'from_status' => 'pending_user',
                'to_status' => 'in_progress',
                'note' => 'User memberikan balasan',
            ]);
        }

        ActivityLog::record('comment_added', $ticket);

        SendNewCommentNotification::dispatch($ticket->fresh(), $comment->fresh());

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function destroy($id)
    {
        $comment = \App\Models\TicketComment::findOrFail($id);
        $user = auth()->user();
        if ($comment->user_id !== $user->id && !$user->isSupervisor()) abort(403);
        $comment->delete();
        return back()->with('success', 'Komentar dihapus.');
    }
}
