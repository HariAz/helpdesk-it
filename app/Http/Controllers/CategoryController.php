<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get();
        return view('categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
            'icon' => 'nullable|string|max:50',
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(4);
        Category::create($data);
        return back()->with('success', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ]);
        $category->update($data);
        return back()->with('success', 'Kategori diperbarui.');
    }

    public function subcategories(Category $category)
    {
        return response()->json($category->children()->where('is_active', true)->get(['id', 'name']));
    }
}
