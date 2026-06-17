<?php

namespace App\Http\Controllers;

use App\Jobs\SendRatingReceivedNotification;
use App\Models\RatingToken;
use App\Models\TicketRating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function show(string $token)
    {
        $ratingToken = RatingToken::where('token', $token)->firstOrFail();
        if (!$ratingToken->isValid()) {
            return view('ratings.expired');
        }
        $ratingToken->load('ticket.user', 'ticket.assignee');
        return view('ratings.show', compact('ratingToken'));
    }

    public function store(Request $request, string $token)
    {
        $ratingToken = RatingToken::where('token', $token)->firstOrFail();
        if (!$ratingToken->isValid()) {
            return redirect()->back()->with('error', 'Token tidak valid atau sudah digunakan.');
        }

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating = TicketRating::create([
            'ticket_id' => $ratingToken->ticket_id,
            'user_id' => $ratingToken->ticket->user_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $ratingToken->update(['is_used' => true]);
        $ratingToken->ticket->update(['status' => 'closed', 'closed_at' => now()]);

        SendRatingReceivedNotification::dispatch($rating);

        return view('ratings.thanks');
    }
}
