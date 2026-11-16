<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Website: daftar & isi artikel. Filter kategori/pencarian memakai fetch yang sama dengan katalog. */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $categories = ArticleCategory::withCount(['articles' => fn ($q) => $q->published()])->orderBy('sort_order')->orderBy('name')->get();

        $filters = $request->validate([
            'kategori' => ['nullable', Rule::in($categories->pluck('slug'))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $current = $categories->firstWhere('slug', $filters['kategori'] ?? null);

        $articles = Article::published()->with(['author:id,name', 'category'])
            ->when($current, fn ($q) => $q->where('article_category_id', $current->id))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('title', 'like', "%{$v}%")->orWhere('excerpt', 'like', "%{$v}%")))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        // Sorotan + terpopuler hanya di halaman pertama tanpa filter
        $showcase = $articles->onFirstPage() && ! $current && empty($filters['q']);
        $spotlight = $showcase ? (Article::published()->with(['author:id,name', 'category'])->where('is_pinned', true)->first() ?? $articles->first()) : null;

        $data = [
            'articles' => $articles,
            'filters' => $filters,
            'categories' => $categories,
            'current' => $current,
            'spotlight' => $spotlight,
            'popular' => $spotlight
                ? Article::published()->with('category')->whereKeyNot($spotlight->id)->orderByDesc('views')->latest('published_at')->limit(3)->get()
                : collect(),
        ];

        return view($request->hasHeader('X-Catalog') ? 'store.partials.articles-body' : 'store.articles', $data);
    }

    public function show(string $slug): View
    {
        $article = Article::published()->with(['author:id,name', 'category'])->where('slug', $slug)->firstOrFail();
        $article->increment('views');

        return view('store.article', [
            'article' => $article,
            'related' => Article::published()->with('category')->whereKeyNot($article->id)
                ->orderByRaw('article_category_id <=> ? desc', [$article->article_category_id])->latest('published_at')->limit(3)->get(),
        ]);
    }
}
