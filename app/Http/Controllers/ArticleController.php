<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Panel: artikel website (Admin Sosial Media). Editor TinyMCE, HTML dibersihkan sebelum disimpan. */
class ArticleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('view-any', Article::class);

        $filters = $request->only(['status', 'category', 'q']);
        $articles = Article::with('author:id,name')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->latest('updated_at')
            ->get();

        return view('app.articles.index', [
            'articles' => $articles,
            'filters' => $filters,
            'summary' => [
                'published' => Article::published()->count(),
                'draft' => Article::where('status', 'draft')->count(),
                'views' => (int) Article::sum('views'),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Article::class);

        return view('app.articles.create', ['article' => new Article(['category' => 'anime', 'status' => 'draft'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Article::class);

        $article = Article::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('articles.edit', $article)->withSuccess("Artikel \"{$article->title}\" disimpan.");
    }

    public function edit(Article $article): View
    {
        $this->authorize('update', $article);

        return view('app.articles.edit', compact('article'));
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $this->authorize('update', $article);

        $data = $this->validated($request, $article);
        if ($article->cover && ($request->hasFile('cover') || $request->boolean('remove_cover'))) {
            Storage::disk('public')->delete($article->cover);
        }
        $article->update($data);

        return redirect()->route('articles.edit', $article)->withSuccess("Artikel \"{$article->title}\" diperbarui.");
    }

    public function destroy(Article $article): RedirectResponse
    {
        $this->authorize('delete', $article);

        if ($article->cover) {
            Storage::disk('public')->delete($article->cover);
        }
        $article->delete();

        return redirect()->route('articles.index')->withSuccess('Artikel dihapus.');
    }

    /** Unggah gambar dari dalam editor (TinyMCE). Balasan: {"location": url}. */
    public function uploadImage(Request $request): JsonResponse
    {
        $this->authorize('create', Article::class);
        $request->validate(['file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:3072']]);

        return response()->json(['location' => asset('storage/' . $request->file('file')->store('articles/inline', 'public'))]);
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(Article::CATEGORIES))],
            'excerpt' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string', 'max:200000'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_cover' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(array_keys(Article::STATUSES))],
            'published_at' => ['nullable', 'date'],
        ], [], ['title' => 'judul', 'category' => 'kategori', 'excerpt' => 'ringkasan', 'body' => 'isi artikel', 'cover' => 'sampul', 'published_at' => 'waktu terbit']);

        $data['body'] = HtmlSanitizer::clean($data['body']);
        if (trim(strip_tags($data['body'], '<img><iframe>')) === '') {
            throw ValidationException::withMessages(['body' => 'Isi artikel masih kosong.']);
        }

        // Terbit tanpa tanggal = sekarang; draf boleh tanpa tanggal
        $data['published_at'] = $data['published_at'] ?? ($data['status'] === 'published' ? ($article?->published_at ?? now()) : null);

        unset($data['remove_cover']);
        if ($request->hasFile('cover')) {
            $data['cover'] = $request->file('cover')->store('articles/covers', 'public');
        } elseif ($request->boolean('remove_cover')) {
            $data['cover'] = null;
        } else {
            unset($data['cover']);
        }

        return $data;
    }
}
