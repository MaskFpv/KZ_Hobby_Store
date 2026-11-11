<?php

namespace App\Http\Controllers;

use App\Exports\OnlinePaymentExport;
use App\Models\OnlinePayment;
use App\Services\OrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/** Panel: daftar pembayaran online untuk rekonsiliasi Keuangan (read-only). */
class OnlinePaymentController extends Controller
{
    public const FILTERS = ['status', 'method', 'from', 'to'];

    public static function filteredQuery(array $filters): Builder
    {
        return OnlinePayment::with('payable')
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['method'] ?? null, fn ($q, $v) => $q->where('method', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id');
    }

    public function index(Request $request): View
    {
        $this->authorize('view-any', OnlinePayment::class);
        app(OrderService::class)->expireOverdue();

        $filters = $request->only(self::FILTERS);
        $payments = self::filteredQuery($filters)->get();

        return view('app.payments.index', [
            'payments' => $payments,
            'filters' => $filters,
            'paidTotal' => (float) $payments->where('status', 'paid')->sum('amount'),
            'refundTotal' => (float) $payments->where('status', 'refunded')->sum('amount'),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('view-any', OnlinePayment::class);

        return Excel::download(new OnlinePaymentExport($request->only(self::FILTERS)), date('Ymd') . '__Pembayaran-Online.xlsx');
    }
}
