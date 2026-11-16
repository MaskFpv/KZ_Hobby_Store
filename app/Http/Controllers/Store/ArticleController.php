<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Website: daftar & isi artikel. Filter kategori/pencarian memakai fetch yang sama dengan katalog. */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'kategori' => ['nullable', Rule::in(array_keys(Article::CATEGORIES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $articles = Article::published()->with('author:id,name')
            ->when($filters['kategori'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        $data = ['articles' => $articles, 'filters' => $filters];

        return view($request->hasHeader('X-Catalog') ? 'store.partials.articles-body' : 'store.articles', $data);
    }

    public function show(string $slug): View
    {
        $article = Article::published()->with('author:id,name')->where('slug', $slug)->firstOrFail();
        $article->increment('views');

        return view('store.article', [
            'article' => $article,
            'related' => Article::published()->where('id', '!=', $article->id)
                ->orderByRaw('category = ? desc', [$article->category])->latest('published_at')->limit(3)->get(),
        ]);
    }
}
