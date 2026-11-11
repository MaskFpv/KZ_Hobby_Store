<?php

namespace App\Http\Controllers;

use App\Exports\OrderExport;
use App\Models\Order;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/** Panel: pesanan online dari website (proses -> siap -> serah terima). */
class OnlineOrderController extends Controller
{
    public const FILTERS = ['status', 'q', 'from', 'to'];

    public function __construct(private OrderService $orders)
    {
    }

    public static function filteredQuery(array $filters): Builder
    {
        return Order::with(['customer:id,nama,kode_member', 'handler:id,name'])->withCount('items')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($q) => $q->where('code', 'like', "%{$v}%")
                ->orWhere('name', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->orderByRaw("FIELD(status, 'ready_pickup', 'paid', 'processing', 'pending_payment', 'completed', 'cancelled', 'expired')")
            ->latest('id');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('view-any', Order::class);
        $this->orders->expireOverdue();

        $filters = $request->only(self::FILTERS);

        // Cari kode persis (mis. dari scan QR pelanggan): langsung buka detail
        if (! empty($filters['q']) && ($exact = Order::where('code', strtoupper(trim($filters['q'])))->first())) {
            return redirect()->route('online-orders.show', $exact);
        }

        return view('app.orders.index', [
            'orders' => self::filteredQuery($filters)->get(),
            'filters' => $filters,
            'summary' => Order::selectRaw('status, COUNT(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('app.orders.show', [
            'order' => $order->load(['items.product', 'customer', 'handler', 'payments' => fn ($q) => $q->latest('id'), 'transaction']),
        ]);
    }

    public function process(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->orders->process($order, $request->user());

        return back()->withSuccess("Pesanan {$order->code} sedang disiapkan.");
    }

    public function ready(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);
        $this->orders->markReady($order, $request->user());

        return back()->withSuccess("Pesanan {$order->code} siap diambil. Kabari pelanggan via WhatsApp.");
    }

    public function handover(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('handover-orders');
        $transaction = $this->orders->handover($order, $request->user());

        return back()->withSuccess("Pesanan {$order->code} diserahkan. Tercatat sebagai transaksi #{$transaction->id}.");
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('delete', $order);
        $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        $paid = $order->wasPaid();
        $this->orders->cancel($order, $request->user(), $request->reason);

        return back()->withSuccess("Pesanan {$order->code} dibatalkan, stok dikembalikan." . ($paid ? ' Proses pengembalian dana ke pelanggan.' : ''));
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', Order::class);

        return Excel::download(new OrderExport($request->only(self::FILTERS)), date('Ymd') . '__Pesanan-Online.xlsx');
    }

    public function exportpdf(Request $request)
    {
        $this->authorize('view-any', Order::class);

        $orders = self::filteredQuery($request->only(self::FILTERS))->get();

        return Pdf::loadView('app.orders.data', compact('orders'))->setPaper('a4', 'landscape')->download('pesanan-online.pdf');
    }
}
