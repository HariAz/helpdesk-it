<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Category;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');

        $query = Ticket::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59']);

        $stats = [
            'total'     => $query->count(),
            'resolved'  => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'escalated' => (clone $query)->where('is_escalated', true)->count(),
            'avg_hours' => (clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        $byPriority = (clone $query)->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority');
        $byStatus   = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $byCategory = (clone $query)->with('category')->selectRaw('category_id, COUNT(*) as count')->groupBy('category_id')->get();

        $teknisiStats = User::where('role', 'teknisi')->withCount([
            'assignedTickets as total_assigned' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
            'assignedTickets as resolved_count' => fn($q) => $q->whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$dateFrom, $dateTo]),
        ])->get();

        // Chart: ticket trend across the selected period (daily)
        $from = Carbon::parse($dateFrom);
        $to   = Carbon::parse($dateTo);
        $days = collect();
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days->push($d->format('Y-m-d'));
        }
        $days = $days->take(90); // cap at 90 labels
        $newByDay = Ticket::whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(created_at) as date, COUNT(*) as cnt')->groupBy('date')->pluck('cnt', 'date');
        $resolvedByDay = Ticket::whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->selectRaw('DATE(resolved_at) as date, COUNT(*) as cnt')->groupBy('date')->pluck('cnt', 'date');
        $chartTrend = [
            'labels'   => $days->map(fn($d) => Carbon::parse($d)->format('d M'))->values()->toArray(),
            'new'      => $days->map(fn($d) => (int)($newByDay[$d] ?? 0))->values()->toArray(),
            'resolved' => $days->map(fn($d) => (int)($resolvedByDay[$d] ?? 0))->values()->toArray(),
        ];

        // Category pie for chart
        $categoryPie = [
            'labels' => $byCategory->map(fn($r) => $r->category?->name ?? 'Tanpa Kategori')->values()->toArray(),
            'data'   => $byCategory->pluck('count')->values()->toArray(),
        ];

        // SLA compliance per teknisi for selected month
        $slaMonth = $request->sla_month ?? now()->format('Y-m');
        [$slaYear, $slaMon] = explode('-', $slaMonth);
        $slaCompliance = User::where('role', 'teknisi')->get()->map(function ($tek) use ($slaYear, $slaMon) {
            $resolved = Ticket::where('assigned_to', $tek->id)
                ->whereNotNull('resolved_at')
                ->whereNotNull('sla_deadline')
                ->whereYear('resolved_at', $slaYear)
                ->whereMonth('resolved_at', $slaMon)
                ->get(['resolved_at', 'sla_deadline']);
            $total  = $resolved->count();
            $onTime = $resolved->filter(fn($t) => $t->resolved_at->lte($t->sla_deadline))->count();
            return [
                'name'    => $tek->name,
                'total'   => $total,
                'on_time' => $onTime,
                'late'    => $total - $onTime,
                'rate'    => $total > 0 ? round(($onTime / $total) * 100) : null,
            ];
        })->filter(fn($r) => $r['total'] > 0)->values();

        return view('reports.index', compact(
            'stats', 'byPriority', 'byStatus', 'byCategory', 'teknisiStats',
            'dateFrom', 'dateTo', 'chartTrend', 'categoryPie',
            'slaCompliance', 'slaMonth'
        ));
    }

    public function exportPdf(Request $request)
    {
        $month    = $request->month ?? now()->format('Y-m');
        [$year, $mon] = explode('-', $month);
        $dateFrom = Carbon::create($year, $mon, 1)->startOfMonth();
        $dateTo   = $dateFrom->copy()->endOfMonth();

        $query = Ticket::whereBetween('created_at', [$dateFrom, $dateTo]);

        $stats = [
            'total'     => $query->count(),
            'resolved'  => (clone $query)->whereIn('status', ['resolved', 'closed'])->count(),
            'open'      => (clone $query)->whereNotIn('status', ['resolved', 'closed', 'cancelled'])->count(),
            'escalated' => (clone $query)->where('is_escalated', true)->count(),
            'avg_hours' => (clone $query)->whereNotNull('resolved_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
                ->value('avg_hours'),
        ];

        $byPriority = (clone $query)->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority');
        $byStatus   = (clone $query)->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status');
        $byCategory = (clone $query)->with('category:id,name')->selectRaw('category_id, COUNT(*) as count')->groupBy('category_id')->get();

        $teknisiStats = User::where('role', 'teknisi')->withCount([
            'assignedTickets as total_assigned' => fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]),
            'assignedTickets as resolved_count' => fn($q) => $q->whereIn('status', ['resolved', 'closed'])->whereBetween('created_at', [$dateFrom, $dateTo]),
        ])->orderByDesc('resolved_count')->get();

        $slaCompliance = User::where('role', 'teknisi')->get()->map(function ($tek) use ($year, $mon) {
            $resolved = Ticket::where('assigned_to', $tek->id)
                ->whereNotNull('resolved_at')->whereNotNull('sla_deadline')
                ->whereYear('resolved_at', $year)->whereMonth('resolved_at', $mon)
                ->get(['resolved_at', 'sla_deadline']);
            $total  = $resolved->count();
            $onTime = $resolved->filter(fn($t) => $t->resolved_at->lte($t->sla_deadline))->count();
            return ['name' => $tek->name, 'total' => $total, 'on_time' => $onTime,
                    'rate' => $total > 0 ? round(($onTime / $total) * 100) : null];
        })->filter(fn($r) => $r['total'] > 0)->values();

        $monthLabel = Carbon::create($year, $mon, 1)->isoFormat('MMMM YYYY');

        $pdf = Pdf::loadView('reports.pdf', compact(
            'stats', 'byPriority', 'byStatus', 'byCategory', 'teknisiStats', 'slaCompliance', 'monthLabel', 'month'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("laporan-helpdesk-{$month}.pdf");
    }

    public function export(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo   = $request->date_to   ?? now()->format('Y-m-d');
        $tickets  = Ticket::with(['user', 'assignee', 'category'])
            ->whereBetween('created_at', [$dateFrom, $dateTo . ' 23:59:59'])
            ->get();

        $headers  = ['Content-Type' => 'text/csv; charset=UTF-8'];
        $filename = "laporan-{$dateFrom}-{$dateTo}.csv";

        $callback = function () use ($tickets) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No. Tiket', 'Judul', 'Status', 'Prioritas', 'Pelapor', 'Teknisi', 'Kategori', 'Dibuat', 'Diselesaikan', 'Waktu Resolusi (Jam)', 'SLA Tepat Waktu']);
            foreach ($tickets as $t) {
                $resolveHours = $t->resolved_at ? $t->created_at->diffInHours($t->resolved_at) : '-';
                $slaOk = ($t->sla_deadline && $t->resolved_at) ? ($t->resolved_at->lte($t->sla_deadline) ? 'Ya' : 'Tidak') : '-';
                fputcsv($file, [
                    $t->ticket_number, $t->title, $t->status, $t->priority,
                    $t->user->name, $t->assignee?->name ?? '-',
                    $t->category?->name ?? '-',
                    $t->created_at->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i') ?? '-',
                    $resolveHours, $slaOk,
                ]);
            }
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, $headers);
    }
}
