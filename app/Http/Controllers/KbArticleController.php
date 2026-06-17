<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\KbArticle;
use App\Models\Ticket;
use Illuminate\Http\Request;

class KbArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = KbArticle::with('category', 'author')->orderByDesc('updated_at');

        $user = auth()->user();
        if ($user->isUser()) {
            $query->where('is_published', true);
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('content', 'like', "%{$request->search}%");
            });
        }
        if ($request->category_id) $query->where('category_id', $request->category_id);

        $articles = $query->paginate(15)->withQueryString();
        $categories = Category::whereNull('parent_id')->get();

        return view('knowledge-base.index', compact('articles', 'categories'));
    }

    public function create()
    {
        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();
        return view('knowledge-base.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'category_id'  => 'nullable|exists:categories,id',
            'tags'         => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $data['slug'] = KbArticle::generateSlug($data['title']);
        $data['created_by'] = auth()->id();
        $data['tags'] = $data['tags']
            ? array_map('trim', explode(',', $data['tags']))
            : null;

        $article = KbArticle::create($data);

        return redirect()->route('knowledge-base.show', $article)
            ->with('success', 'Artikel berhasil disimpan.');
    }

    public function show(KbArticle $knowledgeBase)
    {
        if (!$knowledgeBase->is_published && auth()->user()->isUser()) abort(404);

        $knowledgeBase->increment('views');
        $knowledgeBase->load('category', 'author');

        $related = KbArticle::where('is_published', true)
            ->where('id', '!=', $knowledgeBase->id)
            ->where('category_id', $knowledgeBase->category_id)
            ->limit(5)
            ->get();

        return view('knowledge-base.show', compact('knowledgeBase', 'related'));
    }

    public function edit(KbArticle $knowledgeBase)
    {
        $categories = Category::whereNull('parent_id')->where('is_active', true)->get();
        return view('knowledge-base.edit', compact('knowledgeBase', 'categories'));
    }

    public function update(Request $request, KbArticle $knowledgeBase)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'category_id'  => 'nullable|exists:categories,id',
            'tags'         => 'nullable|string',
            'is_published' => 'boolean',
        ]);

        $data['updated_by'] = auth()->id();
        $data['tags'] = $data['tags']
            ? array_map('trim', explode(',', $data['tags']))
            : null;

        $knowledgeBase->update($data);

        return redirect()->route('knowledge-base.show', $knowledgeBase)
            ->with('success', 'Artikel diperbarui.');
    }

    public function destroy(KbArticle $knowledgeBase)
    {
        $knowledgeBase->delete();
        return redirect()->route('knowledge-base.index')
            ->with('success', 'Artikel dihapus.');
    }

    public function attachToTicket(Request $request, Ticket $ticket)
    {
        $request->validate(['kb_article_id' => 'required|exists:kb_articles,id']);

        $ticket->kbArticles()->syncWithoutDetaching([
            $request->kb_article_id => ['attached_by' => auth()->id()],
        ]);

        return back()->with('success', 'Artikel KB dilampirkan ke tiket.');
    }

    public function detachFromTicket(Ticket $ticket, KbArticle $knowledgeBase)
    {
        $ticket->kbArticles()->detach($knowledgeBase->id);
        return back()->with('success', 'Artikel KB dilepas dari tiket.');
    }

    public function search(Request $request)
    {
        $results = KbArticle::where('is_published', true)
            ->where(function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->q}%")
                  ->orWhere('content', 'like', "%{$request->q}%");
            })
            ->limit(5)
            ->get(['id', 'title', 'slug']);

        return response()->json($results);
    }
}
