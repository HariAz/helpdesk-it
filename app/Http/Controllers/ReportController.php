<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $query = Ticket::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

        $stats = [
            'total' => $query->count(),
            'resolved' => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated' => (clone $query)->where('is_escalated', true)->count(),
            'avg_hours' => (clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        $byPriority = (clone $query)->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority');
        $byStatus = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $byCategory = (clone $query)->with('category')->selectRaw('category_id, COUNT(*) as count')->groupBy('category_id')->get();

        $teknisiStats = User::where('role', 'teknisi')->withCount([
            'assignedTickets as total_assigned' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
            'assignedTickets as resolved_count' => fn($q) => $q->whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$dateFrom, $dateTo]),
        ])->get();

        return view('reports.index', compact('stats', 'byPriority', 'byStatus', 'byCategory', 'teknisiStats', 'dateFrom', 'dateTo'));
    }

    public function export(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');
        $tickets = Ticket::with(['user', 'assignee', 'category'])
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->get();

        $headers = ['Content-Type' => 'text/csv; charset=UTF-8'];
        $filename = "laporan-{$dateFrom}-{$dateTo}.csv";

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No. Tiket', 'Judul', 'Status', 'Prioritas', 'Pelapor', 'Teknisi', 'Kategori', 'Dibuat', 'Diselesaikan', 'Waktu Resolusi (Jam)']);
            foreach ($tickets as $t) {
                $resolveHours = $t->resolved_at ? $t->created_at->diffInHours($t->resolved_at) : '-';
                fputcsv($file, [
                    $t->ticket_number, $t->title, $t->status, $t->priority,
                    $t->user->name, $t->assignee?->name ?? '-',
                    $t->category?->name ?? '-',
                    $t->created_at->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i') ?? '-',
                    $resolveHours,
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
