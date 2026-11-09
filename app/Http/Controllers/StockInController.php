<?php

namespace App\Http\Controllers;

use App\Exports\StockInExport;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\StockInRequest;
use App\Imports\StockInImport;
use App\Models\Product;
use App\Models\StockIn;
use App\Services\StockService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class StockInController extends Controller
{
    use HandlesExcelImport;

    public const FILTERS = ['status', 'from', 'to'];

    public function __construct(private StockService $stock)
    {
    }

    public static function filteredQuery(array $filters): Builder
    {
        return StockIn::with(['product:id,name,sku', 'user:id,name'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('ordered_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('ordered_at', '<=', $v))
            ->latest('ordered_at')
            ->latest('id');
    }

    public function index(Request $request): View
    {
        $this->authorize('view-any', StockIn::class);

        $filters = $request->only(self::FILTERS);

        return view('app.stock_ins.index', [
            'stockIns' => self::filteredQuery($filters)->get(),
            'filters' => $filters,
            'pendingCount' => StockIn::whereIn('status', ['ordered', 'shipped'])->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StockIn::class);

        return view('app.stock_ins.create', [
            'products' => $this->productOptions(),
            'selectedProduct' => $request->integer('product_id') ?: null,
        ]);
    }

    public function store(StockInRequest $request): RedirectResponse
    {
        $this->authorize('create', StockIn::class);

        $data = $request->safe()->except(['status', 'update_cost']);

        $arrived = DB::transaction(function () use ($data, $request) {
            $stockIn = StockIn::create($data + ['status' => 'ordered', 'user_id' => $request->user()->id]);

            // Status awal selalu `ordered`, lalu dinaikkan lewat service agar stok & PO ikut terupdate
            return $request->status === 'ordered'
                ? 0
                : $this->stock->changeStatus($stockIn, $request->status, $request->boolean('update_cost'));
        });

        return redirect()->route('stock-ins.index')
            ->withSuccess('Stok masuk tercatat.' . $this->arrivedMessage($arrived));
    }

    public function show(StockIn $stockIn): View
    {
        $this->authorize('view', $stockIn);

        $stockIn->load(['product.category', 'product.brand', 'user']);

        return view('app.stock_ins.show', compact('stockIn'));
    }

    public function edit(StockIn $stockIn): View|RedirectResponse
    {
        $this->authorize('update', $stockIn);

        if (! $this->isEditable($stockIn)) {
            return redirect()->route('stock-ins.show', $stockIn)
                ->with('error', 'Stok masuk yang sudah diterima / dibatalkan tidak bisa diedit.');
        }

        return view('app.stock_ins.edit', ['stockIn' => $stockIn, 'products' => $this->productOptions()]);
    }

    public function update(StockInRequest $request, StockIn $stockIn): RedirectResponse
    {
        $this->authorize('update', $stockIn);

        if (! $this->isEditable($stockIn)) {
            return redirect()->route('stock-ins.show', $stockIn)
                ->with('error', 'Stok masuk yang sudah diterima / dibatalkan tidak bisa diedit.');
        }

        $stockIn->update($request->safe()->except(['status', 'update_cost']));

        return redirect()->route('stock-ins.show', $stockIn)->withSuccess(__('crud.common.saved'));
    }

    /** Ubah status: shipped / received / cancelled. */
    public function changeStatus(Request $request, StockIn $stockIn): RedirectResponse
    {
        $this->authorize('update', $stockIn);

        $request->validate([
            'status' => ['required', Rule::in(['shipped', 'received', 'cancelled'])],
            'update_cost' => ['nullable', 'boolean'],
        ]);

        $arrived = $this->stock->changeStatus($stockIn, $request->status, $request->boolean('update_cost'));

        $message = match ($request->status) {
            'shipped' => "Stok masuk {$stockIn->code} ditandai dalam pengiriman.",
            'received' => "Barang diterima, stok {$stockIn->product->name} bertambah {$stockIn->quantity}." . $this->arrivedMessage($arrived),
            'cancelled' => "Stok masuk {$stockIn->code} dibatalkan.",
        };

        return back()->withSuccess($message);
    }

    public function destroy(StockIn $stockIn): RedirectResponse
    {
        $this->authorize('delete', $stockIn);

        if ($stockIn->status === 'received') {
            return redirect()->route('stock-ins.index')
                ->with('error', 'Stok masuk yang sudah diterima tidak bisa dihapus (sudah menambah stok). Koreksi lewat Stok Opname.');
        }

        $stockIn->delete();

        return redirect()->route('stock-ins.index')->withSuccess(__('crud.common.removed'));
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', StockIn::class);

        return Excel::download(new StockInExport($request->only(self::FILTERS)), date('Ymd') . '__Stok-Masuk.xlsx');
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-any', StockIn::class);

        $filters = $request->only(self::FILTERS);
        $stockIns = self::filteredQuery($filters)->get();

        return Pdf::loadView('app.stock_ins.data', compact('stockIns', 'filters'))
            ->setPaper('a4', 'landscape')
            ->download('stok-masuk.pdf');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', StockIn::class);

        return $this->runImport($request, new StockInImport($this->stock, $request->user()->id), 'stock-ins.index');
    }

    private function isEditable(StockIn $stockIn): bool
    {
        return in_array($stockIn->status, ['ordered', 'shipped'], true);
    }

    private function productOptions()
    {
        return Product::orderBy('name')->get(['id', 'name', 'sku', 'stock', 'purchase_price', 'type']);
    }

    private function arrivedMessage(int $arrived): string
    {
        return $arrived > 0 ? " {$arrived} Pre-Order otomatis ditandai \"Barang Tiba\"." : '';
    }
}
