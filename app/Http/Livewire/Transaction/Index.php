<?php

namespace App\Http\Livewire\Transaction;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Halaman kasir (POS). Keranjang disimpan di state komponen ($produk_detail).
 * Hanya produk Ready Stock yang dijual di sini; Pre-Order punya modul sendiri.
 */
class Index extends Component
{
    use LivewireAlert;
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filter katalog
    public $search = '';
    public $kategori_id = 0;
    public $brand_id = 0;

    // Keranjang
    public $produk_detail = [];
    public $stokHabis;

    // Checkout
    public $subtotal = 0;
    public $discount = 0;
    public $total_price = 0;
    public $no_faktur;
    public $date;
    public $payment_method = 'cash';
    public $total_pembayaran;
    public $kembalian = 0;
    public $keterangan;
    public $customer_id;

    protected $queryString = ['search' => ['except' => '']];

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->customer_id = Customer::where('kode_member', 'MBR00000')->value('id');
    }

    public function render()
    {
        $products = Product::with(['brand', 'category'])
            ->withReserved()
            ->readyStock()
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($q) => $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"));
            })
            ->when($this->kategori_id, fn ($q) => $q->where('category_id', $this->kategori_id))
            ->when($this->brand_id, fn ($q) => $q->where('brand_id', $this->brand_id))
            ->orderByDesc('stock')
            ->orderBy('name')
            ->paginate(9);

        return view('livewire.transaction.index', [
            'categories' => Category::orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
            'products' => $products,
            'customers' => Customer::orderBy('nama')->get(),
        ]);
    }

    /* ---------------- Filter ---------------- */

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function changeCategory($id)
    {
        $this->kategori_id = (int) $this->kategori_id === (int) $id ? 0 : (int) $id;
        $this->resetPage();
    }

    public function changeBrand($id)
    {
        $this->brand_id = (int) $this->brand_id === (int) $id ? 0 : (int) $id;
        $this->resetPage();
    }

    /* ---------------- Keranjang ---------------- */

    private function cartIndex($id)
    {
        return collect($this->produk_detail)->pluck('id')->search((int) $id);
    }

    public function pilihProduk($id)
    {
        $product = Product::readyStock()->withReserved()->find($id);

        if (! $product || $product->available_stock < 1) {
            $this->stokHabis = (int) $id;
            return;
        }

        $index = $this->cartIndex($id);

        if ($index !== false) {
            $this->increaseQuantity($id);
            return;
        }

        $this->produk_detail[] = [
            'id' => $product->id,
            'photo' => $product->image,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (float) $product->selling_price,
            'sub_total' => (float) $product->selling_price,
            'quantity' => 1,
        ];

        $this->getTotalHarga();
    }

    public function increaseQuantity($id)
    {
        $index = $this->cartIndex($id);
        if ($index === false) {
            return;
        }

        $stock = (int) Product::withReserved()->find($id)?->available_stock;

        if ($this->produk_detail[$index]['quantity'] >= $stock) {
            $this->stokHabis = (int) $id;
        } else {
            $this->produk_detail[$index]['quantity']++;
            $this->subTotalChange($index);
        }

        $this->getTotalHarga();
    }

    public function decreaseQuantity($id)
    {
        $index = $this->cartIndex($id);
        if ($index === false) {
            return;
        }

        $this->produk_detail[$index]['quantity']--;

        if ($this->produk_detail[$index]['quantity'] <= 0) {
            unset($this->produk_detail[$index]);
            $this->produk_detail = array_values($this->produk_detail);
        } else {
            $this->subTotalChange($index);
        }

        $this->getTotalHarga();
    }

    public function subTotalChange($index)
    {
        $item = $this->produk_detail[$index];
        $this->produk_detail[$index]['sub_total'] = $item['price'] * $item['quantity'];
    }

    public function clearPesan()
    {
        $this->produk_detail = [];
        $this->getTotalHarga();
    }

    public function hideStockMessage()
    {
        $this->reset('stokHabis');
    }

    /* ---------------- Perhitungan ---------------- */

    public function getTotalHarga()
    {
        $this->subtotal = collect($this->produk_detail)->sum('sub_total');

        // Diskon nominal (Rp), dibatasi 0..subtotal
        $discount = max(0, (float) ($this->discount ?: 0));
        $this->discount = min($discount, $this->subtotal);

        $this->total_price = $this->subtotal - $this->discount;
        $this->functionKembalian();
    }

    public function updatedDiscount()
    {
        $this->getTotalHarga();
    }

    public function updatedTotalPembayaran()
    {
        $this->functionKembalian();
    }

    public function functionKembalian()
    {
        $paid = (float) ($this->total_pembayaran ?: 0);

        $this->kembalian = $paid >= $this->total_price
            ? $paid - $this->total_price
            : 'Pembayaran Kurang!';
    }

    public function setupNoFaktur()
    {
        $this->getTotalHarga();
        // Hanya pratinjau. Nomor final dikunci ulang saat checkout.
        $this->no_faktur = Transaction::generateInvoiceNumber();
    }

    /* ---------------- Checkout ---------------- */

    public function checkout()
    {
        $this->getTotalHarga();

        $rules = [
            'date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', array_keys(Transaction::POS_METHODS)),
            'discount' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:1000',
            'customer_id' => 'required|exists:customers,id',
        ];

        if ($this->payment_method === 'cash') {
            $rules['total_pembayaran'] = 'required|numeric|min:' . $this->total_price;
        }

        $this->validate($rules);

        if (empty($this->produk_detail)) {
            $this->alert('error', 'Keranjang masih kosong');
            return;
        }

        try {
            $transaction = DB::transaction(function () {
                $products = Product::whereIn('id', collect($this->produk_detail)->pluck('id'))
                    ->withReserved()
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // Cek ulang stok di dalam lock (cegah stok minus bila 2 kasir checkout bersamaan).
                // Stok jatah PO "Barang Tiba" tidak boleh terjual di kasir.
                foreach ($this->produk_detail as $item) {
                    $product = $products->get($item['id']);
                    if (! $product || $product->available_stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'stok-habis' => "Stok {$item['name']} tidak mencukupi.",
                        ]);
                    }
                }

                $paid = $this->payment_method === 'cash'
                    ? (float) $this->total_pembayaran
                    : (float) $this->total_price;

                $transaction = Transaction::create([
                    'id' => Transaction::generateInvoiceNumber(),
                    'date' => $this->date,
                    'customer_id' => $this->customer_id,
                    'user_id' => Auth::id(),
                    'subtotal' => $this->subtotal,
                    'discount' => $this->discount,
                    'total_price' => $this->total_price,
                    'paid_amount' => $paid,
                    'change_amount' => max(0, $paid - $this->total_price),
                    'payment_method' => $this->payment_method,
                    'keterangan' => $this->keterangan,
                ]);

                foreach ($this->produk_detail as $item) {
                    $product = $products->get($item['id']);

                    TransactionDetail::create([
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'qty' => $item['quantity'],
                        'purchase_price' => $product->purchase_price,
                        'unit_price' => $item['price'],
                        'sub_total' => $item['sub_total'],
                    ]);

                    $product->decrement('stock', $item['quantity']);
                }

                return $transaction;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $this->alert('error', 'Transaksi gagal disimpan. Coba lagi.');
            return;
        }

        $this->no_faktur = $transaction->id;

        $this->alert('success', 'Transaksi Berhasil', [
            'position' => 'center',
            'toast' => true,
            'timer' => 9999999,
            'showConfirmButton' => true,
            'onConfirmed' => 'transactionDialogTrue',
            'confirmButtonText' => 'Nota Faktur',
            'showCancelButton' => true,
            'onDismissed' => 'onOk',
            'cancelButtonText' => 'Ok',
            'onClose' => 'onOk',
        ]);
    }

    protected function getListeners()
    {
        return ['onOk' => 'redirectToIndex', 'transactionDialogTrue' => 'transactionDialogTrue'];
    }

    public function transactionDialogTrue()
    {
        return redirect()->route('transaction.invoice', $this->no_faktur);
    }

    public function redirectToIndex()
    {
        return redirect()->route('transaction.index');
    }
}
