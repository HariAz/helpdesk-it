<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewCommentNotification;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatusLog;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request, Ticket $ticket)
    {
        $user = auth()->user();
        $data = $request->validate([
            'body'        => 'required|string',
            'is_internal' => 'boolean',
            'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip',
        ]);

        if ($data['is_internal'] ?? false) {
            if (!$user->isTeknisi() && !$user->isSupervisor()) abort(403);
        }

        $comment = $ticket->comments()->create([
            'user_id'     => $user->id,
            'body'        => $data['body'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Handle attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $stored = $file->store('tickets/' . now()->format('Y/m'), 'local');
                $ticket->attachments()->create([
                    'comment_id'    => $comment->id,
                    'uploaded_by'   => $user->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name'   => basename($stored),
                    'path'          => $stored,
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        // Parse @mention — match @Name Surname patterns
        if (preg_match_all('/@([\w\s]{2,40})(?=\s|$|[^a-zA-Z\s])/u', $data['body'], $matches)) {
            $mentioned = array_unique($matches[1]);
            foreach ($mentioned as $nameFragment) {
                $nameFragment = trim($nameFragment);
                $mentionedUser = User::where('is_active', true)
                    ->where('name', 'like', "%{$nameFragment}%")
                    ->where('id', '!=', $user->id)
                    ->first();

                if ($mentionedUser) {
                    $mentionedUser->notify(new TicketNotification(
                        'mention',
                        $ticket,
                        "{$user->name} menyebut Anda di komentar tiket {$ticket->ticket_number}",
                        'bi-at',
                        'warning'
                    ));
                }
            }
        }

        // User reply changes Pending User → In Progress
        if ($ticket->status === 'pending_user' && $user->isUser()) {
            $ticket->update(['status' => 'in_progress']);
            TicketStatusLog::create([
                'ticket_id'   => $ticket->id,
                'user_id'     => $user->id,
                'from_status' => 'pending_user',
                'to_status'   => 'in_progress',
                'note'        => 'User memberikan balasan',
            ]);
        }

        ActivityLog::record('comment_added', $ticket);

        SendNewCommentNotification::dispatch($ticket->fresh(), $comment->fresh());

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function destroy($id)
    {
        $comment = TicketComment::findOrFail($id);
        $user = auth()->user();
        if ($comment->user_id !== $user->id && !$user->isSupervisor()) abort(403);

        // Delete attached files
        foreach ($comment->attachments as $attachment) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($attachment->path);
            $attachment->delete();
        }

        $comment->delete();
        return back()->with('success', 'Komentar dihapus.');
    }
}
