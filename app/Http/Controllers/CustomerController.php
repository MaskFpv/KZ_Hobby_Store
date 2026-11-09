<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Http\Controllers\Concerns\HandlesExcelImport;
use App\Http\Requests\CustomerRequest;
use App\Imports\CustomerImport;
use App\Models\Customer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    use HandlesExcelImport;

    /** Pelanggan default untuk pembeli tanpa member. Tidak boleh dihapus. */
    public const WALK_IN_CODE = 'MBR00000';

    public static function listQuery()
    {
        return Customer::withCount('transactions')
            ->withSum('transactions as total_belanja', 'total_price')
            ->orderBy('kode_member');
    }

    public function index(): View
    {
        $this->authorize('view-any', Customer::class);

        $customers = self::listQuery()->get();

        return view('app.customers.index', compact('customers'));
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('app.customers.create');
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::create($request->validated());

        return redirect()->route('customers.index')
            ->withSuccess("Member {$customer->nama} ({$customer->kode_member}) berhasil ditambahkan");
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        $transactions = $customer->transactions()->latest('date')->latest('id')->limit(15)->get();
        $preOrders = $customer->preOrders()->with('product:id,name')->latest('id')->limit(15)->get();
        $stats = [
            'count' => $customer->transactions()->count(),
            'total' => (float) $customer->transactions()->sum('total_price'),
            'last' => $customer->transactions()->max('date'),
        ];

        return view('app.customers.show', compact('customer', 'transactions', 'preOrders', 'stats'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('app.customers.edit', compact('customer'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update($request->validated());

        return redirect()->route('customers.index')->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        if ($customer->kode_member === self::WALK_IN_CODE) {
            return redirect()->route('customers.index')
                ->with('error', 'Pelanggan Umum dipakai sebagai default kasir, tidak bisa dihapus.');
        }

        if ($customer->preOrders()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', "Member {$customer->nama} masih punya data Pre-Order, tidak bisa dihapus.");
        }

        // Riwayat transaksi tetap ada (customer_id -> NULL, tampil sebagai Pelanggan Umum)
        $customer->delete();

        return redirect()->route('customers.index')->withSuccess(__('crud.common.removed'));
    }

    public function export()
    {
        $this->authorize('view-any', Customer::class);

        return Excel::download(new CustomerExport, date('Ymd') . '__Pelanggan.xlsx');
    }

    public function exportpdf()
    {
        $this->authorize('view-any', Customer::class);

        $customers = self::listQuery()->get();

        return Pdf::loadView('app.customers.data', compact('customers'))
            ->setPaper('a4', 'landscape')
            ->download('pelanggan.pdf');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', Customer::class);

        return $this->runImport($request, new CustomerImport, 'customers.index');
    }
}
