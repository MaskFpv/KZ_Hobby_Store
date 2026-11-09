<?php

namespace Tests\Feature;

use App\Http\Livewire\Transaction\Index as PosIndex;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Kasir (POS): keranjang, diskon, kembalian, nomor faktur, potong stok. */
class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->kasir = User::where('email', 'kasir@gmail.com')->firstOrFail();
    }

    public function test_cash_checkout_with_discount(): void
    {
        $nendo = Product::where('sku', 'GSC-NEN-2367')->first(); // stok 6, Rp 850.000
        $hg = Product::where('sku', 'BAN-HG-0230')->first();     // stok 15, Rp 265.000
        $po = Product::where('sku', 'BAN-MG-0255')->first();     // pre-order

        $pos = Livewire::actingAs($this->kasir)->test(PosIndex::class)
            ->call('pilihProduk', $nendo->id)
            ->call('pilihProduk', $nendo->id)
            ->call('pilihProduk', $hg->id)
            ->call('pilihProduk', $po->id); // ditolak: bukan ready stock

        $this->assertCount(2, $pos->get('produk_detail'));
        $this->assertEquals(1965000, $pos->get('subtotal'));

        $pos->set('discount', 65000)->call('setupNoFaktur');
        $this->assertEquals(1900000, $pos->get('total_price'));

        $pos->set('payment_method', 'cash')->set('total_pembayaran', 1000000)->call('checkout')
            ->assertHasErrors('total_pembayaran');
        $this->assertSame(0, Transaction::count());

        $pos->set('total_pembayaran', 2000000)->call('checkout')->assertHasNoErrors();

        $trx = Transaction::with('transactionDetails')->firstOrFail();
        $this->assertSame((int) (now()->format('Ymd') . '0001'), (int) $trx->id);
        $this->assertEquals(1900000, $trx->total_price);
        $this->assertEquals(65000, $trx->discount);
        $this->assertEquals(100000, $trx->change_amount);
        $this->assertSame($this->kasir->id, $trx->user_id);
        $this->assertCount(2, $trx->transactionDetails);
        $this->assertSame(4, $nendo->fresh()->stock);
        $this->assertSame(14, $hg->fresh()->stock);

        $this->actingAs($this->kasir)->get(route('transaction.invoice', $trx->id))
            ->assertOk()->assertSee('Nendoroid Frieren')->assertSee('1.900.000');
    }

    public function test_quantity_is_capped_by_stock_and_invoice_numbers_increment(): void
    {
        $levi = Product::where('sku', 'KTB-ARJ-0145')->first(); // stok 1

        $pos = Livewire::actingAs($this->kasir)->test(PosIndex::class)
            ->call('pilihProduk', $levi->id)
            ->call('pilihProduk', $levi->id)
            ->call('pilihProduk', $levi->id);
        $this->assertSame(1, $pos->get('produk_detail')[0]['quantity']);

        $pos->set('payment_method', 'qris')->call('checkout')->assertHasNoErrors();
        $this->assertSame(0, $levi->fresh()->stock);

        $hg = Product::where('sku', 'BAN-HG-0230')->first();
        Livewire::actingAs($this->kasir)->test(PosIndex::class)
            ->call('pilihProduk', $hg->id)
            ->set('payment_method', 'debit')
            ->call('checkout')->assertHasNoErrors();

        $this->assertSame((int) (now()->format('Ymd') . '0002'), (int) Transaction::max('id'));
    }

    public function test_stock_rechecked_at_checkout(): void
    {
        $levi = Product::where('sku', 'KTB-ARJ-0145')->first(); // stok 1

        $pos = Livewire::actingAs($this->kasir)->test(PosIndex::class)->call('pilihProduk', $levi->id);

        // Kasir lain menjual barang yang sama lebih dulu
        $levi->update(['stock' => 0]);

        $pos->set('payment_method', 'qris')->call('checkout')->assertHasErrors('stok-habis');
        $this->assertSame(0, Transaction::count());
    }
}
