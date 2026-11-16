<?php

namespace App\Http\Controllers;

use App\Models\ArticleCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Panel: kategori artikel (tambah/ubah/hapus). Satu halaman: daftar + form tambah, edit di halaman sendiri. */
class ArticleCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('view-any', ArticleCategory::class);

        return view('app.article-categories.index', [
            'categories' => ArticleCategory::withCount('articles')->orderBy('sort_order')->orderBy('name')->get(),
            'category' => new ArticleCategory(['color' => '#ff2e88']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ArticleCategory::class);

        $category = ArticleCategory::create($this->validated($request) + ['sort_order' => (int) ArticleCategory::max('sort_order') + 1]);

        return redirect()->route('article-categories.index')->withSuccess("Kategori \"{$category->name}\" ditambahkan.");
    }

    public function edit(ArticleCategory $articleCategory): View
    {
        $this->authorize('update', $articleCategory);

        return view('app.article-categories.edit', ['category' => $articleCategory]);
    }

    public function update(Request $request, ArticleCategory $articleCategory): RedirectResponse
    {
        $this->authorize('update', $articleCategory);

        $articleCategory->update($this->validated($request, $articleCategory));

        return redirect()->route('article-categories.index')->withSuccess("Kategori \"{$articleCategory->name}\" diperbarui.");
    }

    public function destroy(ArticleCategory $articleCategory): RedirectResponse
    {
        $this->authorize('delete', $articleCategory);

        if ($articleCategory->articles()->exists()) {
            return back()->with('error', "Kategori \"{$articleCategory->name}\" masih dipakai artikel. Pindahkan artikelnya dulu.");
        }
        $articleCategory->delete();

        return redirect()->route('article-categories.index')->withSuccess('Kategori dihapus.');
    }

    private function validated(Request $request, ?ArticleCategory $category = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('article_categories', 'name')->ignore($category)],
            'kanji' => ['nullable', 'string', 'max:4'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'description' => ['nullable', 'string', 'max:200'],
        ], [], ['name' => 'nama kategori', 'color' => 'warna', 'description' => 'deskripsi']);
    }
}
