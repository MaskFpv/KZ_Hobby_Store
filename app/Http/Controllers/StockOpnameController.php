<?php

namespace App\Http\Controllers;

use App\Exports\StockOpnameExport;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockOpname;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Stok opname bersifat riwayat (audit trail): bisa dibuat & dilihat,
 * tidak bisa diedit / dihapus.
 */
class StockOpnameController extends Controller
{
    public const FILTERS = ['from', 'to', 'only_diff'];

    public function __construct(private StockService $stock)
    {
    }

    public static function filteredQuery(array $filters): Builder
    {
        return StockOpname::with(['product:id,name,sku', 'user:id,name'])
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('opname_date', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('opname_date', '<=', $v))
            ->when(! empty($filters['only_diff']), fn ($q) => $q->where('difference', '!=', 0))
            ->latest('opname_date')
            ->latest('id');
    }

    public function index(Request $request): View
    {
        $this->authorize('view-any', StockOpname::class);

        $filters = $request->only(self::FILTERS);

        return view('app.stock_opnames.index', [
            'opnames' => self::filteredQuery($filters)->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockOpname::class);

        $categoryId = $request->integer('category_id') ?: null;

        $products = Product::with('category:id,name')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock', 'category_id']);

        return view('app.stock_opnames.create', [
            'products' => $products,
            'categories' => Category::orderBy('name')->pluck('name', 'id'),
            'categoryId' => $categoryId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockOpname::class);

        $request->validate([
            'opname_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
            'counts' => ['required', 'array'],
            'counts.*.physical' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'counts.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'counts.*.physical.integer' => 'Stok fisik harus angka bulat.',
            'counts.*.physical.min' => 'Stok fisik tidak boleh minus.',
        ]);

        // Hanya baris yang diisi yang diproses
        $counts = collect($request->input('counts'))
            ->filter(fn ($row) => isset($row['physical']) && $row['physical'] !== '')
            ->all();

        if (empty($counts)) {
            return back()->withInput()->with('error', 'Isi stok fisik minimal satu produk.');
        }

        $changed = $this->stock->opname($counts, $request->opname_date, $request->user()->id, $request->notes);

        return redirect()->route('stock-opnames.index')
            ->withSuccess(count($counts) . " produk dicek, {$changed} produk stoknya disesuaikan.");
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', StockOpname::class);

        return Excel::download(new StockOpnameExport($request->only(self::FILTERS)), date('Ymd') . '__Stok-Opname.xlsx');
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-any', StockOpname::class);

        $filters = $request->only(self::FILTERS);
        $opnames = self::filteredQuery($filters)->get();

        return Pdf::loadView('app.stock_opnames.data', compact('opnames', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download('stok-opname.pdf');
    }
}
