<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Halaman publik website pelanggan. */
class StoreController extends Controller
{
    /** Produk yang boleh tampil di website. */
    public static function visible(): Builder
    {
        return Product::with(['brand:id,name,slug', 'category:id,name,slug'])
            ->withReserved()
            ->where('is_published', true);
    }

    public function home(): View
    {
        $featured = self::visible()->where('is_featured', true)->latest('id')->limit(5)->get();
        if ($featured->count() < 3) {
            $featured = $featured->concat(
                self::visible()->whereNotIn('id', $featured->pluck('id'))->orderByDesc('selling_price')->limit(5 - $featured->count())->get()
            );
        }

        return view('store.home', [
            'featured' => $featured,
            'newArrivals' => self::visible()->readyStock()->where('stock', '>', 0)->latest('id')->limit(8)->get(),
            'preOrders' => $this->openPreOrderQuery()->limit(4)->get(),
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_published', true)])->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->pluck('name'),
        ]);
    }

    public function catalog(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'tipe' => ['nullable', 'in:ready_stock,pre_order'],
            'kondisi' => ['nullable', 'in:new,second'],
            'min' => ['nullable', 'integer', 'min:0'],
            'max' => ['nullable', 'integer', 'min:0'],
            'urut' => ['nullable', 'in:terbaru,termurah,termahal,nama'],
        ]);

        $products = self::visible()
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('name', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%")))
            ->when($filters['kategori'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($c) => $c->where('slug', $v)))
            ->when($filters['brand'] ?? null, fn ($q, $v) => $q->whereHas('brand', fn ($b) => $b->where('slug', $v)))
            ->when($filters['tipe'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['kondisi'] ?? null, fn ($q, $v) => $q->where('condition', $v))
            ->when($filters['min'] ?? null, fn ($q, $v) => $q->where('selling_price', '>=', $v))
            ->when($filters['max'] ?? null, fn ($q, $v) => $q->where('selling_price', '<=', $v))
            ->when(($filters['urut'] ?? 'terbaru') === 'termurah', fn ($q) => $q->orderBy('selling_price'))
            ->when(($filters['urut'] ?? null) === 'termahal', fn ($q) => $q->orderByDesc('selling_price'))
            ->when(($filters['urut'] ?? null) === 'nama', fn ($q) => $q->orderBy('name'))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('store.catalog', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->get(['name', 'slug']),
            'brands' => Brand::orderBy('name')->get(['name', 'slug']),
        ]);
    }

    public function product(string $slug): View
    {
        $product = self::visible()->where('slug', $slug)->firstOrFail();

        $related = self::visible()
            ->where('id', '!=', $product->id)
            ->where(fn ($q) => $q->where('category_id', $product->category_id)->orWhere('brand_id', $product->brand_id))
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('store.product', compact('product', 'related'));
    }

    public function preOrders(): View
    {
        return view('store.pre-order', ['products' => $this->openPreOrderQuery()->get()]);
    }

    public function terms(): View
    {
        return view('store.terms');
    }

    public function about(): View
    {
        return view('store.about');
    }

    /** Produk PO yang deadline-nya belum lewat (atau tanpa deadline). */
    private function openPreOrderQuery(): Builder
    {
        return self::visible()->preOrder()
            ->where(fn ($q) => $q->whereNull('po_deadline')->orWhereDate('po_deadline', '>=', today()))
            ->orderByRaw('po_deadline IS NULL')
            ->orderBy('po_deadline');
    }
}
