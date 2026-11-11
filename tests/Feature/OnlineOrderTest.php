<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OnlinePayment;
use App\Models\Order;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** Fase C: keranjang, checkout, pembayaran prototype, pesanan online, PO online. */
class OnlineOrderTest extends TestCase
{
    use RefreshDatabase;

    private Product $nendo;  // ready stock, stok 6, Rp 850.000
    private Product $hg;     // ready stock, stok 15, Rp 265.000
    private Product $makima; // pre-order, Rp 3.200.000

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->nendo = Product::where('sku', 'GSC-NEN-2367')->first();
        $this->hg = Product::where('sku', 'BAN-HG-0230')->first();
        $this->makima = Product::where('sku', 'GSC-SCL-0711')->first();
    }

    private function staff(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    private function member(): Customer
    {
        $c = Customer::factory()->create(['email' => 'nezuko@example.com']);
        $c->forceFill(['password' => 'rahasia123'])->save();

        return $c;
    }

    private function checkout(array $override = []): Order
    {
        $this->post(route('store.cart.add'), ['product_id' => $this->nendo->id, 'qty' => 2]);
        $this->post(route('store.cart.add'), ['product_id' => $this->hg->id, 'qty' => 1]);

        $this->post(route('store.checkout.store'), array_merge([
            'name' => 'Tanjiro', 'email' => 'tanjiro@example.com', 'phone' => '081234',
            'method' => 'qris', 'terms' => '1',
        ], $override))->assertRedirect();

        return Order::latest('id')->firstOrFail();
    }

    /* ---------------- Keranjang ---------------- */

    public function test_cart_rules(): void
    {
        $this->postJson(route('store.cart.add'), ['product_id' => $this->nendo->id, 'qty' => 2])
            ->assertOk()->assertJson(['count' => 2]);
        $this->postJson(route('store.cart.add'), ['product_id' => $this->nendo->id, 'qty' => 10])
            ->assertStatus(422)->assertJsonValidationErrors('qty'); // stok hanya 6
        $this->postJson(route('store.cart.add'), ['product_id' => $this->makima->id])
            ->assertStatus(422)->assertJsonValidationErrors('product_id'); // PO tidak lewat keranjang

        $this->get(route('store.cart'))->assertOk()->assertSee($this->nendo->name)->assertSee('1.700.000');

        $this->patch(route('store.cart.update', $this->nendo->id), ['qty' => 3])->assertRedirect(route('store.cart'));
        $this->assertSame([$this->nendo->id => 3], session('cart'));
        $this->delete(route('store.cart.remove', $this->nendo->id));
        $this->assertSame([], session('cart'));
    }

    /* ---------------- Checkout tamu + bayar QRIS ---------------- */

    public function test_guest_checkout_reserves_stock_and_qris_payment(): void
    {
        $order = $this->checkout();

        $this->assertSame('pending_payment', $order->status);
        $this->assertNull($order->customer_id);
        $this->assertEquals(1965000, $order->total);
        $this->assertSame(4, $this->nendo->fresh()->stock, 'stok disisihkan saat checkout');
        $this->assertSame(14, $this->hg->fresh()->stock);
        $this->assertSame([], session('cart', []));

        $payment = $order->payments()->firstOrFail();
        $this->assertSame('qris', $payment->method);

        // Tanpa tanda tangan: ditolak. Dengan signed URL: boleh.
        $this->get(route('store.pay', $payment))->assertForbidden();
        $this->get(route('store.order', $order))->assertForbidden();
        $payUrl = URL::signedRoute('store.pay', $payment);
        $this->get($payUrl)->assertOk()->assertSee('Scan QRIS')->assertSee('1.965.000');

        $this->post($payUrl)->assertRedirect()->assertSessionHas('store_success');
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame('paid', $payment->fresh()->status);

        // Dibayar dua kali: tetap aman (idempoten)
        $this->post($payUrl)->assertRedirect();
        $this->assertSame(1, OnlinePayment::where('status', 'paid')->count());

        $this->get(URL::signedRoute('store.order', $order))->assertOk()->assertSee($order->code)->assertSee('Pembayaran diterima');
    }

    public function test_track_order_with_code_and_email(): void
    {
        $order = $this->checkout();

        $this->post(route('store.track.find'), ['code' => $order->code, 'email' => 'salah@example.com'])->assertSessionHasErrors('code');
        $res = $this->post(route('store.track.find'), ['code' => strtolower($order->code), 'email' => 'TANJIRO@example.com']);
        $res->assertRedirect();
        $this->assertStringContainsString('signature=', $res->headers->get('Location'));
    }

    public function test_member_can_pay_own_guest_order_opened_from_link(): void
    {
        $order = $this->checkout(); // dibuat sebagai tamu (customer_id kosong)
        $this->actingAs($this->member(), 'customer');

        // Link "Bayar Sekarang" harus tetap bertanda tangan, karena pesanan ini bukan milik akun member
        $page = $this->get(URL::signedRoute('store.order', $order))->assertOk();
        preg_match('/href="([^"]*\/bayar\/[^"]+)"/', $page->getContent(), $m);
        $this->assertStringContainsString('signature=', html_entity_decode($m[1] ?? ''));
        $this->get(html_entity_decode($m[1]))->assertOk()->assertSee('Scan QRIS');
    }

    /* ---------------- Kartu & VA ---------------- */

    public function test_card_payment_stores_only_brand_and_last4(): void
    {
        $order = $this->checkout(['method' => 'card']);
        $payUrl = URL::signedRoute('store.pay', $order->payments()->first());

        $this->post($payUrl, ['card_name' => 'Tanjiro', 'card_number' => '4111 1111 1111 1112', 'card_expiry' => '12/30', 'card_cvc' => '123'])
            ->assertSessionHasErrors('card_number'); // gagal Luhn
        $this->post($payUrl, ['card_name' => 'Tanjiro', 'card_number' => '4111 1111 1111 1111', 'card_expiry' => '01/20', 'card_cvc' => '123'])
            ->assertSessionHasErrors('card_expiry');
        $this->assertSame('pending_payment', $order->fresh()->status);

        $this->post($payUrl, ['card_name' => 'Tanjiro', 'card_number' => '4111 1111 1111 1111', 'card_expiry' => '12/30', 'card_cvc' => '123'])
            ->assertSessionHas('store_success');

        $payment = $order->payments()->first();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('visa', $payment->card_brand);
        $this->assertSame('1111', $payment->card_last4);
        $this->assertDatabaseMissing('online_payments', ['card_last4' => '4111111111111111']);
        $this->assertStringNotContainsString('4111111111111111', json_encode(OnlinePayment::all()->toArray()));
        $this->assertNull(session()->getOldInput('card_number'));
    }

    public function test_va_requires_bank_and_generates_number(): void
    {
        $this->post(route('store.cart.add'), ['product_id' => $this->hg->id]);
        $this->post(route('store.checkout.store'), [
            'name' => 'A', 'email' => 'a@example.com', 'phone' => '0812', 'method' => 'va', 'terms' => '1',
        ])->assertSessionHasErrors('bank');

        $order = $this->checkout(['method' => 'va', 'bank' => 'bni']);
        $payment = $order->payments()->first();
        $this->assertStringStartsWith('8808', $payment->va_number);
        $this->get(URL::signedRoute('store.pay', $payment))->assertSee('BNI')->assertSee('Simulasikan transfer');
    }

    /* ---------------- Kadaluarsa ---------------- */

    public function test_unpaid_order_expires_and_releases_stock(): void
    {
        $order = $this->checkout();
        $this->assertSame(4, $this->nendo->fresh()->stock);

        $this->travel(config('store.payment_due_hours') + 1)->hours();
        $this->artisan('store:expire-payments')->assertSuccessful();

        $this->assertSame('expired', $order->fresh()->status);
        $this->assertSame(6, $this->nendo->fresh()->stock);
        $this->assertSame('expired', $order->payments()->first()->status);

        // Tagihan kadaluarsa tidak bisa dibayar
        $this->post(URL::signedRoute('store.pay', $order->payments()->first()))->assertSessionHas('store_error');
        $this->assertSame('expired', $order->fresh()->status);
    }

    /* ---------------- Panel: proses -> siap -> serah terima ---------------- */

    public function test_staff_flow_creates_online_transaction(): void
    {
        $order = $this->checkout();
        $this->post(URL::signedRoute('store.pay', $order->payments()->first()));

        // Gudang menyiapkan, tapi tidak bisa serah terima
        $this->actingAs($this->staff('gudang'));
        $this->get(route('online-orders.index'))->assertOk()->assertSee($order->code);
        $this->post(route('online-orders.process', $order))->assertSessionHas('success');
        $this->post(route('online-orders.ready', $order))->assertSessionHas('success');
        $this->post(route('online-orders.handover', $order))->assertForbidden();
        $this->assertSame('ready_pickup', $order->fresh()->status);

        // Kasir: cari kode persis -> detail -> serahkan
        $kasir = $this->staff('kasir');
        $this->actingAs($kasir)->get(route('online-orders.index', ['q' => $order->code]))->assertRedirect(route('online-orders.show', $order));
        $this->post(route('online-orders.process', $order))->assertForbidden();
        $this->post(route('online-orders.handover', $order))->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $trx = Transaction::with('transactionDetails')->findOrFail($order->transaction_id);
        $this->assertSame('online', $trx->channel);
        $this->assertSame('qris', $trx->payment_method);
        $this->assertSame($kasir->id, $trx->user_id);
        $this->assertEquals(1965000, $trx->total_price);
        $this->assertCount(2, $trx->transactionDetails);
        $this->assertSame(4, $this->nendo->fresh()->stock, 'stok tidak dipotong dua kali');

        // Kasir melihat transaksi ini di riwayatnya
        $this->get(route('transaction.data'))->assertSee((string) $trx->id)->assertSee('Online');
    }

    public function test_keuangan_cancels_paid_order_with_refund(): void
    {
        $order = $this->checkout();
        $this->post(URL::signedRoute('store.pay', $order->payments()->first()));

        $this->actingAs($this->staff('cs'))->post(route('online-orders.cancel', $order))->assertForbidden();

        $this->actingAs($this->staff('keuangan'))
            ->post(route('online-orders.cancel', $order), ['reason' => 'Stok rusak'])->assertSessionHas('success');

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame('refunded', $order->payments()->first()->status);
        $this->assertSame(6, $this->nendo->fresh()->stock);
        $this->get(route('online-payments.index'))->assertOk()->assertSee('Dikembalikan');
    }

    public function test_order_panel_pages_and_exports(): void
    {
        $order = $this->checkout();
        $this->actingAs($this->staff('admin'));

        // Badge sidebar hanya menghitung pesanan yang perlu ditindak (sudah dibayar)
        $this->get(route('home'))->assertDontSee('side-badge', false);
        $this->post(URL::signedRoute('store.pay', $order->payments()->first()));
        $this->get(route('home'))->assertSee('<b class="side-badge" title="Perlu ditindaklanjuti">1</b>', false);

        // Jumlah per status tampil bulat (alias COUNT tidak boleh kena cast decimal kolom `total`)
        $this->get(route('online-orders.index'))->assertOk()
            ->assertSee('<p class="stat-value">1</p>', false);
        $this->get(route('online-orders-export'))->assertOk()->assertDownload();
        $this->assertSame('application/pdf', $this->get(route('online-orders-exportPdf'))->assertOk()->headers->get('content-type'));
        $this->get(route('online-payments.index'))->assertOk();
        $this->get(route('online-payments-export'))->assertOk()->assertDownload();

        $this->actingAs($this->staff('kasir'))->get(route('online-payments.index'))->assertForbidden();
    }

    /* ---------------- PO online ---------------- */

    public function test_online_pre_order_with_dp_and_installment(): void
    {
        $this->get(route('store.po.create', $this->makima->slug))->assertRedirect(route('store.login'));

        $member = $this->member();
        $this->actingAs($member, 'customer');

        $this->get(route('store.po.create', $this->makima->slug))->assertOk()->assertSee('Bayar DP');
        $this->post(route('store.po.store', $this->makima->slug), ['qty' => 1, 'dp_amount' => 100000, 'method' => 'qris', 'terms' => '1'])
            ->assertSessionHasErrors('dp_amount'); // < 30%

        $this->post(route('store.po.store', $this->makima->slug), ['qty' => 1, 'dp_amount' => 960000, 'method' => 'qris', 'terms' => '1'])
            ->assertRedirect();

        $po = PreOrder::latest('id')->firstOrFail();
        $this->assertSame('pending_payment', $po->status);
        $this->assertSame('online', $po->channel);
        $payment = $po->onlinePayments()->first();

        // PO pelanggan lain tidak bisa dibuka
        $other = Customer::factory()->create();
        $this->actingAs($other, 'customer')->get(route('store.pay', $payment))->assertForbidden();
        $this->get(route('store.po.show', $po->code))->assertNotFound();

        $this->actingAs($member, 'customer');
        $this->post(route('store.pay.confirm', $payment))->assertRedirect(route('store.po.show', $po->code));

        $po->refresh();
        $this->assertSame('waiting', $po->status, 'DP dibayar: masuk antrean');
        $this->assertEquals(960000, $po->paid_amount);
        $this->assertSame('dp', $po->payments()->first()->type);

        // Cicilan dari akun
        $this->post(route('store.po.pay', $po->code), ['amount' => 9999999, 'method' => 'qris'])->assertSessionHasErrors('amount');
        $this->post(route('store.po.pay', $po->code), ['amount' => 1000000, 'method' => 'va', 'bank' => 'bca'])->assertRedirect();
        $installment = $po->onlinePayments()->latest('id')->first();
        $this->assertSame('installment', $installment->purpose);
        $this->post(route('store.pay.confirm', $installment));
        $this->assertEquals(1960000, $po->fresh()->paid_amount);

        $this->get(route('store.account'))->assertOk()->assertSee($po->code);
        $this->get(route('store.po.show', $po->code))->assertOk()->assertSee('Cicilan');
    }

    public function test_unpaid_online_po_is_cancelled_after_deadline(): void
    {
        $this->actingAs($this->member(), 'customer');
        $this->post(route('store.po.store', $this->makima->slug), ['qty' => 1, 'dp_amount' => 960000, 'method' => 'qris', 'terms' => '1']);
        $po = PreOrder::latest('id')->first();

        $this->travel(config('store.payment_due_hours') + 1)->hours();
        $this->artisan('store:expire-payments');

        $this->assertSame('cancelled', $po->fresh()->status);
    }

    public function test_pending_online_order_reduces_pos_stock(): void
    {
        $this->post(route('store.cart.add'), ['product_id' => $this->nendo->id, 'qty' => 6]);
        $this->post(route('store.checkout.store'), ['name' => 'A', 'email' => 'a@example.com', 'phone' => '08', 'method' => 'qris', 'terms' => '1']);

        // Semua unit sudah disisihkan untuk pesanan online: kasir tidak bisa menjualnya
        $this->assertSame(0, $this->nendo->fresh()->available_stock);
        $this->postJson(route('store.cart.add'), ['product_id' => $this->nendo->id])->assertStatus(422);
    }
}
