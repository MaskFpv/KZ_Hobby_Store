<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\ProductRequest;
use App\Imports\ProductImport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    use HandlesExcelImport;

    /** Filter yang bisa dipakai di index, export XLS & PDF. */
    public const FILTERS = ['category_id', 'brand_id', 'type', 'condition', 'stock_status'];

    public static function filteredQuery(array $filters): Builder
    {
        return Product::with(['category', 'brand'])
            ->when($filters['category_id'] ?? null, fn ($q, $v) => $q->where('category_id', $v))
            ->when($filters['brand_id'] ?? null, fn ($q, $v) => $q->where('brand_id', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($filters['condition'] ?? null, fn ($q, $v) => $q->where('condition', $v))
            ->when(($filters['stock_status'] ?? null) === 'out', fn ($q) => $q->where('stock', 0))
            ->when(($filters['stock_status'] ?? null) === 'low', fn ($q) => $q->where('stock', '>', 0)->lowStock())
            ->orderBy('name');
    }

    public function index(Request $request): View
    {
        $this->authorize('view-any', Product::class);

        $filters = $request->only(self::FILTERS);
        $products = self::filteredQuery($filters)->get();

        return view('app.products.index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        return view('app.products.create', $this->formOptions());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return redirect()->route('products.show', $product)->withSuccess(__('crud.common.created'));
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);

        $product->load(['category', 'brand']);

        $recentSales = $product->transactionDetails()
            ->with('transaction:id,date')
            ->latest('id')
            ->limit(10)
            ->get();

        $totalSold = (int) $product->transactionDetails()->sum('qty');

        // Riwayat stok gabungan: stok masuk + opname, terbaru di atas
        $stockHistory = $product->stockIns()->latest('id')->limit(10)->get()
            ->map(fn ($s) => [
                'date' => $s->received_at ?? $s->ordered_at,
                'type' => 'Stok Masuk',
                'ref' => $s->code,
                'url' => route('stock-ins.show', $s),
                'qty' => $s->status === 'received' ? '+' . $s->quantity : '(' . $s->status_label . ')',
                'note' => $s->supplier,
            ])
            ->concat($product->stockOpnames()->latest('id')->limit(10)->get()->map(fn ($o) => [
                'date' => $o->opname_date,
                'type' => 'Opname',
                'ref' => "{$o->system_stock} → {$o->physical_stock}",
                'url' => null,
                'qty' => ($o->difference > 0 ? '+' : '') . $o->difference,
                'note' => $o->notes,
            ]))
            ->sortByDesc('date')
            ->take(10)
            ->values();

        $openPreOrders = $product->preOrders()->with('customer:id,nama,kode_member')
            ->whereIn('status', ['waiting', 'arrived'])
            ->orderBy('order_date')
            ->get();

        return view('app.products.show', compact('product', 'recentSales', 'totalSold', 'stockHistory', 'openPreOrders'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        return view('app.products.edit', ['product' => $product] + $this->formOptions());
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $data = $request->safe()->except(['image', 'remove_image', 'stock']);

        if ($request->hasFile('image') || $request->boolean('remove_image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->hasFile('image')
                ? $request->file('image')->store('products', 'public')
                : null;
        }

        // SKU dikosongkan saat edit -> pertahankan SKU lama
        if (empty($data['sku'])) {
            unset($data['sku']);
        }

        $product->update($data);

        return redirect()->route('products.show', $product)->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        // FK restrict dari stok masuk / opname / pre-order
        if ($product->stockIns()->exists() || $product->stockOpnames()->exists() || $product->preOrders()->exists()) {
            return redirect()->route('products.index')
                ->with('error', "Produk {$product->name} punya riwayat stok / pre-order, tidak bisa dihapus.");
        }

        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Riwayat penjualan tetap aman: transaction_details.product_id -> NULL, nama tersimpan sebagai snapshot
        $product->delete();

        return redirect()->route('products.index')->withSuccess(__('crud.common.removed'));
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', Product::class);

        return Excel::download(
            new ProductExport($request->only(self::FILTERS)),
            date('Ymd') . '__Katalog-Produk.xlsx'
        );
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-any', Product::class);

        $products = self::filteredQuery($request->only(self::FILTERS))->get();

        return Pdf::loadView('app.products.data', compact('products'))
            ->setPaper('a4', 'landscape')
            ->download('katalog-produk.pdf');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        return $this->runImport($request, new ProductImport, 'products.index');
    }

    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'brands' => Brand::orderBy('name')->pluck('name', 'id'),
        ];
    }
}
