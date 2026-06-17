<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\TicketTemplate;
use Illuminate\Http\Request;

class TicketTemplateController extends Controller
{
    public function index()
    {
        $templates = TicketTemplate::with('category', 'subcategory', 'creator')
            ->orderByDesc('created_at')
            ->paginate(20);

        $categories = Category::whereNull('parent_id')->where('is_active', true)->with('children')->get();

        return view('ticket-templates.index', compact('templates', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:150',
            'category_id'          => 'required|exists:categories,id',
            'subcategory_id'       => 'nullable|exists:categories,id',
            'priority'             => 'required|in:kritis,tinggi,sedang,rendah',
            'description_template' => 'required|string',
        ]);

        TicketTemplate::create([...$data, 'created_by' => auth()->id()]);

        return back()->with('success', 'Template tiket berhasil disimpan.');
    }

    public function update(Request $request, TicketTemplate $ticketTemplate)
    {
        $data = $request->validate([
            'name'                 => 'required|string|max:150',
            'category_id'          => 'required|exists:categories,id',
            'subcategory_id'       => 'nullable|exists:categories,id',
            'priority'             => 'required|in:kritis,tinggi,sedang,rendah',
            'description_template' => 'required|string',
            'is_active'            => 'boolean',
        ]);

        $ticketTemplate->update($data);

        return back()->with('success', 'Template diperbarui.');
    }

    public function destroy(TicketTemplate $ticketTemplate)
    {
        $ticketTemplate->delete();
        return back()->with('success', 'Template dihapus.');
    }

    public function apiList(Request $request)
    {
        $templates = TicketTemplate::where('is_active', true)
            ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
            ->get(['id', 'name', 'priority', 'description_template', 'subcategory_id']);

        return response()->json($templates);
    }
}
