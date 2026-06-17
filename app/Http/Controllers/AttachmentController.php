<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function store(Request $request, \App\Models\Ticket $ticket)
    {
        $request->validate(['attachments.*' => 'required|file|max:5120']);
        foreach ($request->file('attachments', []) as $file) {
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
        return back()->with('success', 'Lampiran berhasil diupload.');
    }

    public function download(TicketAttachment $attachment)
    {
        $user = auth()->user();
        if ($user->isUser() && $attachment->ticket->user_id !== $user->id) abort(403);
        if (!Storage::disk('local')->exists($attachment->path)) abort(404);
        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    public function destroy(TicketAttachment $attachment)
    {
        $user = auth()->user();
        if ($attachment->uploaded_by !== $user->id && !$user->isSupervisor()) abort(403);
        Storage::disk('local')->delete($attachment->path);
        $attachment->delete();
        return back()->with('success', 'Lampiran dihapus.');
    }
}
