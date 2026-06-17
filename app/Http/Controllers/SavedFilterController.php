<?php

namespace App\Http\Controllers;

use App\Models\SavedFilter;
use Illuminate\Http\Request;

class SavedFilterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'    => 'required|string|max:80',
            'filters' => 'required|array',
        ]);

        SavedFilter::create([
            'user_id' => auth()->id(),
            'name'    => $data['name'],
            'filters' => $data['filters'],
        ]);

        return response()->json(['message' => 'Filter disimpan.']);
    }

    public function index()
    {
        $filters = SavedFilter::where('user_id', auth()->id())->orderByDesc('created_at')->get(['id', 'name', 'filters']);
        return response()->json($filters);
    }

    public function destroy(SavedFilter $savedFilter)
    {
        abort_if($savedFilter->user_id !== auth()->id(), 403);
        $savedFilter->delete();
        return response()->json(['message' => 'Filter dihapus.']);
    }
}
