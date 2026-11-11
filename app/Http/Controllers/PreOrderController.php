<?php

namespace App\Http\Controllers;

use App\Exports\PreOrderExport;
use App\Http\Requests\PreOrderRequest;
use App\Models\Customer;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\PreOrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class PreOrderController extends Controller
{
    public const FILTERS = ['status', 'product_id'];

    public function __construct(private PreOrderService $service)
    {
    }

    public static function filteredQuery(array $filters): Builder
    {
        return PreOrder::with(['customer:id,nama,kode_member,telepon', 'product:id,name,sku,stock'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['product_id'] ?? null, fn ($q, $v) => $q->where('product_id', $v))
            ->orderByRaw("FIELD(status, 'arrived', 'waiting', 'completed', 'cancelled')")
            ->latest('order_date')
            ->latest('id');
    }

    public function index(Request $request): View
    {
        $this->authorize('view-any', PreOrder::class);

        $filters = $request->only(self::FILTERS);

        return view('app.pre_orders.index', [
            'preOrders' => self::filteredQuery($filters)->get(),
            'filters' => $filters,
            'summary' => PreOrder::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'outstanding' => (float) PreOrder::whereIn('status', ['waiting', 'arrived'])->selectRaw('SUM(total_price - paid_amount) as s')->value('s'),
            'products' => Product::whereIn('id', PreOrder::select('product_id'))->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PreOrder::class);

        return view('app.pre_orders.create', [
            'customers' => Customer::where('kode_member', '!=', CustomerController::WALK_IN_CODE)->orderBy('nama')->get(['id', 'nama', 'kode_member', 'telepon']),
            // Produk Pre-Order di urutan atas
            'products' => Product::orderByRaw("type = 'pre_order' DESC")->orderBy('name')->get(['id', 'name', 'sku', 'selling_price', 'type', 'stock']),
            'selectedProduct' => $request->integer('product_id') ?: null,
            'selectedCustomer' => $request->integer('customer_id') ?: null,
        ]);
    }

    public function store(PreOrderRequest $request): RedirectResponse
    {
        $this->authorize('create', PreOrder::class);

        $data = $request->safe()->only(['customer_id', 'product_id', 'quantity', 'unit_price', 'order_date', 'estimated_arrival', 'notes']);

        $po = $this->service->create(
            $data,
            (float) ($request->dp_amount ?: 0),
            $request->payment_method,
            $request->user()->id
        );

        return redirect()->route('pre-orders.show', $po)
            ->withSuccess("Pre-Order {$po->code} dibuat." . ($po->status === 'arrived' ? ' Stok tersedia, status langsung "Barang Tiba".' : ''));
    }

    public function show(PreOrder $preOrder): View
    {
        $this->authorize('view', $preOrder);

        $preOrder->load(['customer', 'product', 'user', 'payments.user', 'transaction']);

        return view('app.pre_orders.show', compact('preOrder'));
    }

    public function edit(PreOrder $preOrder): View|RedirectResponse
    {
        $this->authorize('update', $preOrder);

        if (! $preOrder->isOpen()) {
            return redirect()->route('pre-orders.show', $preOrder)->with('error', 'PO yang sudah selesai / batal tidak bisa diedit.');
        }

        return view('app.pre_orders.edit', compact('preOrder'));
    }

    public function update(PreOrderRequest $request, PreOrder $preOrder): RedirectResponse
    {
        $this->authorize('update', $preOrder);

        if (! $preOrder->isOpen()) {
            return redirect()->route('pre-orders.show', $preOrder)->with('error', 'PO yang sudah selesai / batal tidak bisa diedit.');
        }

        $preOrder->update($request->safe()->only(['estimated_arrival', 'notes']));

        return redirect()->route('pre-orders.show', $preOrder)->withSuccess(__('crud.common.saved'));
    }

    /** Cicilan / DP tambahan. */
    public function storePayment(Request $request, PreOrder $preOrder): RedirectResponse
    {
        $this->authorize('update', $preOrder);

        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', Rule::in(array_keys(Transaction::POS_METHODS))],
            'payment_notes' => ['nullable', 'string', 'max:255'],
        ], [], ['amount' => 'nominal', 'payment_method' => 'metode pembayaran']);

        $payment = $this->service->addPayment(
            $preOrder,
            (float) $request->amount,
            $request->payment_method,
            $request->payment_notes,
            $request->user()->id
        );

        return redirect()->route('pre-orders.show', $preOrder)
            ->withSuccess("Pembayaran Rp " . number_format($payment->amount, 0, ',', '.') . ' tercatat.');
    }

    public function markArrived(PreOrder $preOrder): RedirectResponse
    {
        $this->authorize('update', $preOrder);

        $this->service->markArrived($preOrder);

        return redirect()->route('pre-orders.show', $preOrder)->withSuccess("PO {$preOrder->code} ditandai Barang Tiba.");
    }

    /** Pelunasan + serah terima barang. */
    public function complete(Request $request, PreOrder $preOrder): RedirectResponse
    {
        $this->authorize('update', $preOrder);

        $request->validate([
            'payment_method' => ['required', Rule::in(array_keys(Transaction::POS_METHODS))],
        ]);

        $transaction = $this->service->complete($preOrder, $request->payment_method, $request->user()->id);

        return redirect()->route('pre-orders.show', $preOrder)
            ->withSuccess("PO {$preOrder->code} lunas & selesai. Tercatat sebagai transaksi #{$transaction->id}.");
    }

    public function cancel(Request $request, PreOrder $preOrder): RedirectResponse
    {
        // Pembatalan hanya untuk admin (permission "delete preorders")
        $this->authorize('delete', $preOrder);

        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $this->service->cancel($preOrder, $request->reason);

        return redirect()->route('pre-orders.show', $preOrder)
            ->withSuccess("PO {$preOrder->code} dibatalkan." . ($preOrder->paid_amount > 0 ? ' Jangan lupa proses pengembalian dana pelanggan.' : ''));
    }

    /** Nota / bukti PO untuk pelanggan (DP, cicilan, pelunasan). */
    public function receipt(PreOrder $preOrder): View
    {
        $this->authorize('view', $preOrder);

        $preOrder->load(['customer', 'product', 'payments', 'user']);

        return view('app.pre_orders.receipt', compact('preOrder'));
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', PreOrder::class);

        return Excel::download(new PreOrderExport($request->only(self::FILTERS)), date('Ymd') . '__Pre-Order.xlsx');
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-any', PreOrder::class);

        $preOrders = self::filteredQuery($request->only(self::FILTERS))->get();

        return Pdf::loadView('app.pre_orders.data', compact('preOrders'))
            ->setPaper('a4', 'landscape')
            ->download('pre-order.pdf');
    }
}
