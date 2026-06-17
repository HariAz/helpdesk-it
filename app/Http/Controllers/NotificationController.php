<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? route('dashboard');
        return redirect($url);
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }
}
