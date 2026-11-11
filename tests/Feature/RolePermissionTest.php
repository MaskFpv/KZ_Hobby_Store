<?php

namespace Tests\Feature;

use App\Models\PreOrder;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Hak akses 6 role (lihat matriks di PermissionsSeeder & docs/specs). */
class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $role): self
    {
        return $this->actingAs(User::role($role)->firstOrFail());
    }

    private function trxBy(User $user): Transaction
    {
        return Transaction::create([
            'id' => Transaction::generateInvoiceNumber(),
            'date' => now()->toDateString(),
            'user_id' => $user->id,
            'subtotal' => 100000, 'total_price' => 100000, 'paid_amount' => 100000,
            'payment_method' => 'cash',
        ]);
    }

    public function test_each_role_has_a_seeded_account(): void
    {
        foreach (['super-admin', 'admin', 'keuangan', 'gudang', 'cs', 'kasir'] as $role) {
            $this->assertSame(1, User::role($role)->count(), "akun {$role}");
        }
    }

    public function test_admin_runs_operations_but_cannot_manage_access(): void
    {
        $this->as('admin');
        foreach (['dashboard.index', 'transaction.laporan', 'transaction.index', 'stock-opnames.create', 'products.create', 'users.index'] as $r) {
            $this->get(route($r))->assertOk();
        }
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();
    }

    public function test_keuangan_sees_reports_and_profit_only(): void
    {
        $this->as('keuangan');
        $this->get(route('dashboard.index'))->assertOk()->assertSee('Laba kotor');
        $this->get(route('transaction.laporan'))->assertOk()->assertSee('Laba kotor');
        $this->get(route('stock-ins.index'))->assertOk();
        $this->get(route('products.show', Product::first()))->assertOk()->assertSee('Harga Modal');

        $this->get(route('transaction.index'))->assertForbidden(); // bukan kasir
        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('stock-ins.create'))->assertForbidden();
        $this->get(route('stock-opnames.index'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_keuangan_can_cancel_pre_order(): void
    {
        $po = PreOrder::create([
            'customer_id' => \App\Models\Customer::where('email', 'rizky@example.com')->value('id'),
            'product_id' => Product::where('type', 'pre_order')->value('id'),
            'quantity' => 1, 'unit_price' => 1000000, 'total_price' => 1000000, 'order_date' => now(),
        ]);

        $this->as('keuangan')->post(route('pre-orders.cancel', $po), ['reason' => 'Refund'])->assertSessionHas('success');
        $this->assertSame('cancelled', $po->fresh()->status);
    }

    public function test_gudang_manages_stock_without_sales_data(): void
    {
        $this->as('gudang');
        $this->get(route('dashboard.index'))->assertOk()
            ->assertSee('Stok menipis')->assertDontSee('Laba kotor')->assertDontSee('Penjualan Bulanan');
        foreach (['products.create', 'brands.create', 'stock-ins.create', 'stock-opnames.create', 'pre-orders.index'] as $r) {
            $this->get(route($r))->assertOk();
        }
        $this->get(route('products.show', Product::first()))->assertSee('Harga Modal');

        $this->get(route('transaction.laporan'))->assertForbidden();
        $this->get(route('transaction.index'))->assertForbidden();
        $this->get(route('customers.index'))->assertForbidden();
        $this->get(route('pre-orders.create'))->assertForbidden();
    }

    public function test_cs_handles_members_and_pre_orders(): void
    {
        $this->as('cs');
        $this->get(route('dashboard.index'))->assertOk()->assertDontSee('Penjualan Bulanan');
        foreach (['customers.create', 'pre-orders.create', 'products.index'] as $r) {
            $this->get(route($r))->assertOk();
        }
        $this->get(route('products.show', Product::first()))->assertDontSee('Harga Modal');

        $this->get(route('products.create'))->assertForbidden();
        $this->get(route('transaction.index'))->assertForbidden();
        $this->get(route('transaction.laporan'))->assertForbidden();
        $this->get(route('stock-ins.index'))->assertForbidden();
    }

    public function test_kasir_is_sales_only_and_sees_own_transactions(): void
    {
        $kasir = User::role('kasir')->firstOrFail();
        $other = User::role('admin')->firstOrFail();
        $mine = $this->trxBy($kasir);
        $theirs = $this->trxBy($other);

        $this->actingAs($kasir);
        $this->get(route('home'))->assertOk()
            ->assertDontSee(route('dashboard.index'))
            ->assertDontSee(route('transaction.laporan'))
            ->assertDontSee(route('stock-ins.index'));

        $this->get(route('transaction.index'))->assertOk();
        $this->get(route('transaction.data'))->assertOk()
            ->assertSee((string) $mine->id)
            ->assertDontSee((string) $theirs->id);
        $this->get(route('transaction.invoice', $mine->id))->assertOk();
        $this->get(route('transaction.invoice', $theirs->id))->assertForbidden();

        foreach (['dashboard.index', 'transaction.laporan', 'stock-ins.index', 'stock-opnames.index', 'products.create', 'list-export'] as $r) {
            $this->get(route($r))->assertForbidden();
        }
    }

    public function test_new_permissions_are_locked_system_permissions(): void
    {
        $this->as('super-admin')->get(route('permissions.index'))
            ->assertOk()->assertSee('view reports')->assertSee('handover orders');
        $this->assertTrue(\App\Http\Controllers\PermissionController::isSystem(
            \Spatie\Permission\Models\Permission::findByName('view profit')
        ));
    }
}
