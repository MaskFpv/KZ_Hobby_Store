<?php

namespace Tests\Feature;

use App\Http\Livewire\Transaction\Index as PosIndex;
use App\Models\Customer;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOpname;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/** Fase 3: Stok Masuk, Stok Opname, Pre-Order. */
class InventoryPreOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kasir;
    private Customer $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@gmail.com')->firstOrFail();
        $this->member = Customer::where('email', 'rizky@example.com')->firstOrFail();
    }

    private function sku(string $sku): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    private function createPo(Product $product, array $override = []): PreOrder
    {
        $this->actingAs($this->admin)->post(route('pre-orders.store'), array_merge([
            'customer_id' => $this->member->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 1000000,
            'order_date' => now()->toDateString(),
            'dp_amount' => 300000,
            'payment_method' => 'transfer',
        ], $override))->assertSessionHasNoErrors();

        return PreOrder::latest('id')->firstOrFail();
    }

    /* ---------------- Stok Masuk ---------------- */

    public function test_stock_in_lifecycle(): void
    {
        $nendo = $this->sku('GSC-NEN-2367'); // stok 6, modal 620.000

        $this->actingAs($this->admin)->post(route('stock-ins.store'), [
            'product_id' => $nendo->id,
            'supplier' => 'AmiAmi',
            'quantity' => 4,
            'purchase_price' => 640000,
            'ordered_at' => now()->toDateString(),
            'status' => 'ordered',
        ])->assertRedirect(route('stock-ins.index'));

        $si = StockIn::latest('id')->first();
        $this->assertSame('SI-' . now()->format('Ymd') . '-0001', $si->code);
        $this->assertSame(6, $nendo->fresh()->stock, 'status dipesan belum menambah stok');

        $this->post(route('stock-ins.status', $si), ['status' => 'shipped'])->assertSessionHas('success');
        $this->post(route('stock-ins.status', $si), ['status' => 'received', 'update_cost' => 1])->assertSessionHas('success');

        $nendo->refresh();
        $this->assertSame(10, $nendo->stock);
        $this->assertEquals(640000, $nendo->purchase_price);
        $this->assertNotNull($si->fresh()->received_at);

        // Tidak bisa diterima dua kali, diedit, atau dihapus
        $this->post(route('stock-ins.status', $si), ['status' => 'received'])->assertSessionHasErrors('status');
        $this->assertSame(10, $nendo->fresh()->stock);
        $this->get(route('stock-ins.edit', $si))->assertRedirect(route('stock-ins.show', $si));
        $this->delete(route('stock-ins.destroy', $si))->assertSessionHas('error');
        $this->assertModelExists($si);
    }

    public function test_stock_in_created_as_received_adds_stock_and_cancelled_cannot_be_received(): void
    {
        $hg = $this->sku('BAN-HG-0230'); // stok 15

        $this->actingAs($this->admin)->post(route('stock-ins.store'), [
            'product_id' => $hg->id, 'quantity' => 5, 'purchase_price' => 180000,
            'ordered_at' => now()->toDateString(), 'status' => 'received',
        ]);
        $this->assertSame(20, $hg->fresh()->stock);

        $this->post(route('stock-ins.store'), [
            'product_id' => $hg->id, 'quantity' => 2, 'purchase_price' => 180000,
            'ordered_at' => now()->toDateString(), 'status' => 'ordered',
        ]);
        $si = StockIn::latest('id')->first();
        $this->post(route('stock-ins.status', $si), ['status' => 'cancelled'])->assertSessionHas('success');
        $this->post(route('stock-ins.status', $si), ['status' => 'received'])->assertSessionHasErrors('status');
        $this->assertSame(20, $hg->fresh()->stock);

        // Yang belum diterima boleh dihapus
        $this->delete(route('stock-ins.destroy', $si))->assertSessionHas('success');
    }

    public function test_stock_in_import_template(): void
    {
        $file = new UploadedFile(public_path('import/import-stok-masuk.xlsx'), 'import-stok-masuk.xlsx', null, null, true);

        $this->actingAs($this->admin)->post(route('stock-ins-import'), ['file' => $file])->assertSessionHas('success');

        $this->assertSame(3, StockIn::count());
        $this->assertSame(11, $this->sku('GSC-NEN-2367')->stock);   // diterima +5
        $this->assertSame(0, $this->sku('BAN-MG-0255')->stock);     // dikirim, belum masuk
        $this->assertSame(15, $this->sku('BAN-HG-0230')->stock);    // dipesan
        $this->assertEquals(180000, StockIn::where('product_id', $this->sku('BAN-HG-0230')->id)->value('purchase_price'));
    }

    /* ---------------- Stok Opname ---------------- */

    public function test_bulk_stock_opname(): void
    {
        $nendo = $this->sku('GSC-NEN-2367'); // 6
        $hg = $this->sku('BAN-HG-0230');     // 15

        $this->actingAs($this->admin)->post(route('stock-opnames.store'), [
            'opname_date' => now()->toDateString(),
            'notes' => 'Opname akhir bulan',
            'counts' => [
                $nendo->id => ['physical' => 5, 'notes' => '1 box rusak'],
                $hg->id => ['physical' => 15],
                $this->sku('BAN-RG-0032')->id => ['physical' => ''], // tidak diisi: dilewati
            ],
        ])->assertRedirect(route('stock-opnames.index'))->assertSessionHas('success');

        $this->assertSame(2, StockOpname::count());
        $this->assertSame(5, $nendo->fresh()->stock);
        $this->assertDatabaseHas('stock_opnames', ['product_id' => $nendo->id, 'system_stock' => 6, 'physical_stock' => 5, 'difference' => -1]);
        $this->assertDatabaseHas('stock_opnames', ['product_id' => $hg->id, 'difference' => 0]);

        $this->post(route('stock-opnames.store'), ['opname_date' => now()->toDateString(), 'counts' => [$hg->id => ['physical' => '']]])
            ->assertSessionHas('error');
        $this->post(route('stock-opnames.store'), ['opname_date' => now()->toDateString(), 'counts' => [$hg->id => ['physical' => -3]]])
            ->assertSessionHasErrors("counts.{$hg->id}.physical");
    }

    /* ---------------- Pre-Order ---------------- */

    public function test_full_pre_order_flow(): void
    {
        $makima = $this->sku('GSC-SCL-0711'); // pre_order, stok 0

        $po = $this->createPo($makima, ['unit_price' => 3200000, 'dp_amount' => 960000]);
        $this->assertSame('PO-' . now()->format('Ymd') . '-0001', $po->code);
        $this->assertSame('waiting', $po->status);
        $this->assertEquals(960000, $po->paid_amount);
        $this->assertSame('dp', $po->payments->first()->type);

        // Cicilan
        $this->post(route('pre-orders.payments.store', $po), ['amount' => 1000000, 'payment_method' => 'cash'])->assertSessionHas('success');
        $this->assertEquals(1240000, $po->fresh()->remaining_amount);
        $this->post(route('pre-orders.payments.store', $po), ['amount' => 5000000, 'payment_method' => 'cash'])->assertSessionHasErrors('amount');

        // Belum ada stok: tidak bisa ditandai tiba / dilunasi
        $this->post(route('pre-orders.arrive', $po))->assertSessionHasErrors('status');
        $this->post(route('pre-orders.complete', $po), ['payment_method' => 'cash'])->assertSessionHasErrors('status');

        // Barang datang lewat Stok Masuk -> PO otomatis "Barang Tiba"
        $this->post(route('stock-ins.store'), [
            'product_id' => $makima->id, 'quantity' => 1, 'purchase_price' => 2600000,
            'ordered_at' => now()->toDateString(), 'status' => 'received',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '1 Pre-Order'));
        $this->assertSame('arrived', $po->fresh()->status);

        // Pelunasan
        $this->post(route('pre-orders.complete', $po), ['payment_method' => 'qris'])->assertSessionHas('success');
        $po->refresh();
        $this->assertSame('completed', $po->status);
        $this->assertEquals(0, $po->remaining_amount);
        $this->assertSame(['dp', 'installment', 'settlement'], $po->payments()->orderBy('id')->pluck('type')->all());
        $this->assertSame(0, $makima->fresh()->stock);

        $trx = Transaction::findOrFail($po->transaction_id);
        $this->assertEquals(3200000, $trx->total_price);
        $this->assertSame($this->member->id, $trx->customer_id);
        $this->get(route('transaction.invoice', $trx->id))->assertOk()->assertSee($po->code)->assertSee('Makima');
        $this->get(route('pre-orders.receipt', $po))->assertOk()->assertSee('Pelunasan');

        // Sudah selesai: tidak bisa bayar / batal lagi
        $this->post(route('pre-orders.payments.store', $po), ['amount' => 1, 'payment_method' => 'cash'])->assertSessionHasErrors('status');
        $this->post(route('pre-orders.cancel', $po))->assertSessionHasErrors('status');
    }

    public function test_pre_order_allocation_is_fifo_and_cancel_releases_stock(): void
    {
        $mg = $this->sku('BAN-MG-0255'); // pre_order, stok 0

        $first = $this->createPo($mg, ['quantity' => 2, 'order_date' => now()->subDays(3)->toDateString()]);
        $second = $this->createPo($mg, ['quantity' => 1, 'customer_id' => Customer::where('email', 'ayu@example.com')->value('id')]);

        // Datang 1 unit: PO pertama butuh 2, jadi antrean berhenti (tidak dilompati)
        $this->post(route('stock-ins.store'), [
            'product_id' => $mg->id, 'quantity' => 1, 'purchase_price' => 950000,
            'ordered_at' => now()->toDateString(), 'status' => 'received',
        ]);
        $this->assertSame('waiting', $first->fresh()->status);
        $this->assertSame('waiting', $second->fresh()->status);

        // Datang 2 lagi (total 3): dua-duanya tiba
        $this->post(route('stock-ins.store'), [
            'product_id' => $mg->id, 'quantity' => 2, 'purchase_price' => 950000,
            'ordered_at' => now()->toDateString(), 'status' => 'received',
        ]);
        $this->assertSame('arrived', $first->fresh()->status);
        $this->assertSame('arrived', $second->fresh()->status);

        // Kasir tidak boleh membatalkan PO
        $this->actingAs($this->kasir)->post(route('pre-orders.cancel', $first))->assertForbidden();

        // Admin batalkan PO pertama -> PO baru yang menunggu ikut kebagian stok
        $third = $this->createPo($mg, ['quantity' => 2]);
        $this->assertSame('waiting', $third->status);
        $this->actingAs($this->admin)->post(route('pre-orders.cancel', $first), ['reason' => 'Customer batal'])->assertSessionHas('success');
        $this->assertSame('cancelled', $first->fresh()->status);
        $this->assertStringContainsString('Customer batal', $first->fresh()->notes);
        $this->assertSame('arrived', $third->fresh()->status);
    }

    public function test_pos_cannot_sell_units_reserved_for_pre_orders(): void
    {
        $levi = $this->sku('KTB-ARJ-0145'); // ready stock, stok 1

        $po = $this->createPo($levi, ['unit_price' => 2350000, 'dp_amount' => 0]);
        $this->assertSame('arrived', $po->status, 'stok ada: PO langsung dialokasikan');
        $this->assertSame(0, $levi->fresh()->available_stock);

        Livewire::actingAs($this->kasir)->test(PosIndex::class)
            ->call('pilihProduk', $levi->id)
            ->assertSet('produk_detail', []);
    }

    public function test_pre_order_validation(): void
    {
        $walkIn = Customer::where('kode_member', 'MBR00000')->first();

        $this->actingAs($this->admin)->post(route('pre-orders.store'), [
            'customer_id' => $walkIn->id,
            'product_id' => $this->sku('BAN-MG-0255')->id,
            'quantity' => 0,
            'unit_price' => 1000,
            'order_date' => now()->addDay()->toDateString(),
            'estimated_arrival' => now()->subMonth()->toDateString(),
            'payment_method' => 'bitcoin',
        ])->assertSessionHasErrors(['customer_id', 'quantity', 'order_date', 'payment_method']);

        $this->post(route('pre-orders.store'), [
            'customer_id' => $this->member->id, 'product_id' => $this->sku('BAN-MG-0255')->id,
            'quantity' => 1, 'unit_price' => 1000000, 'order_date' => now()->toDateString(),
            'dp_amount' => 2000000, 'payment_method' => 'cash',
        ])->assertSessionHasErrors('dp_amount');
        $this->assertSame(0, PreOrder::count());
    }

    /* ---------------- Export & hak akses ---------------- */

    public function test_exports_and_pages_render(): void
    {
        $po = $this->createPo($this->sku('BAN-MG-0255'));
        $si = StockIn::create(['product_id' => $po->product_id, 'quantity' => 1, 'ordered_at' => now()]);
        StockOpname::create(['product_id' => $po->product_id, 'opname_date' => now(), 'system_stock' => 0, 'physical_stock' => 0, 'difference' => 0]);

        $this->actingAs($this->admin);
        foreach ([
            route('stock-ins.index'), route('stock-ins.create'), route('stock-ins.show', $si), route('stock-ins.edit', $si),
            route('stock-opnames.index'), route('stock-opnames.create'),
            route('pre-orders.index'), route('pre-orders.create'), route('pre-orders.show', $po), route('pre-orders.edit', $po),
            route('products.show', $po->product_id), route('customers.show', $po->customer_id), route('dashboard.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
        foreach (['stock-ins-export', 'stock-opnames-export', 'pre-orders-export'] as $r) {
            $this->get(route($r))->assertOk()->assertDownload();
        }
        foreach (['stock-ins-exportPdf', 'stock-opnames-exportPdf', 'pre-orders-exportPdf'] as $r) {
            $this->assertSame('application/pdf', $this->get(route($r))->assertOk()->headers->get('content-type'));
        }
    }

    public function test_kasir_permissions(): void
    {
        $this->actingAs($this->kasir);

        $this->get(route('stock-ins.index'))->assertOk();
        $this->get(route('stock-ins.create'))->assertForbidden();
        $this->get(route('stock-opnames.index'))->assertForbidden();
        $this->get(route('stock-opnames.create'))->assertForbidden();
        $this->get(route('pre-orders.create'))->assertOk();

        $this->post(route('pre-orders.store'), [
            'customer_id' => $this->member->id, 'product_id' => $this->sku('BAN-MG-0255')->id,
            'quantity' => 1, 'unit_price' => 1250000, 'order_date' => now()->toDateString(),
            'dp_amount' => 500000, 'payment_method' => 'cash',
        ])->assertRedirect();
        $po = PreOrder::latest('id')->first();
        $this->assertSame($this->kasir->id, $po->user_id);
        $this->post(route('pre-orders.payments.store', $po), ['amount' => 100000, 'payment_method' => 'cash'])->assertSessionHas('success');
    }
}
