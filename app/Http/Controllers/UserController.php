<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Ticket;

class UserController extends Controller
{
    public function index()
    {
        $users = User::withCount([
            'assignedTickets as total_assigned',
            'assignedTickets as resolved_count' => fn($q) => $q->where('status', 'closed'),
        ])->orderBy('name')->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:supervisor,teknisi,user',
            'department' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
        ]);

        User::create([...$data, 'password' => bcrypt($data['password'])]);
        return redirect()->route('users.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $stats = [
            'total' => Ticket::where('assigned_to', $user->id)->count(),
            'resolved' => Ticket::where('assigned_to', $user->id)->whereIn('status', ['resolved', 'closed'])->count(),
            'avg_rating' => \App\Models\TicketRating::whereHas('ticket', fn($q) => $q->where('assigned_to', $user->id))->avg('rating'),
        ];
        return view('users.edit', compact('user', 'stats'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:supervisor,teknisi,user',
            'department' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($data['password']) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'Data pengguna diperbarui.');
    }

    public function toggle(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menonaktifkan akun sendiri.');
        }
        $user->update(['is_active' => !$user->is_active]);
        $msg = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun berhasil $msg.");
    }
}
