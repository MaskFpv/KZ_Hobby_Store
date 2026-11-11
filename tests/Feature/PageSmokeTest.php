<?php

namespace Tests\Feature;

use App\Http\Livewire\Store\ChatWidget;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Crosscheck menyeluruh: setiap halaman GET dibuka oleh setiap role (panel) dan
 * oleh tamu/member (website) tanpa error server (5xx) maupun 404.
 * Route dengan parameter model diisi record pertama; data contoh dibuat lewat alur sungguhan.
 */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = ['super-admin', 'admin', 'keuangan', 'gudang', 'cs', 'kasir'];

    private Customer $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->member = Customer::factory()->create(['email' => 'smoke@example.com']);
        $this->member->forceFill(['password' => 'rahasia123'])->save();
        $this->createSampleData();
        $this->app['auth']->forgetGuards();
    }

    public function test_every_panel_page_renders_for_every_role(): void
    {
        $failures = [];

        foreach (self::ROLES as $role) {
            $this->app['auth']->forgetGuards();
            $this->actingAs(User::role($role)->firstOrFail(), 'web');

            $urls = $this->urls(fn ($uri) => str_starts_with($uri, 'admin/'))
                + ['transaction.invoice' => route('transaction.invoice', Transaction::firstOrFail()->id)];

            foreach ($urls as $name => $url) {
                $status = $this->get($url)->getStatusCode();
                if ($status >= 500 || $status === 404) {
                    $failures[] = "{$role}: {$name} -> {$status}";
                }
            }
        }

        $this->assertGreaterThan(45, count($urls), 'Crawler panel melewatkan terlalu banyak halaman.');
        $this->assertSame([], $failures);
    }

    public function test_every_storefront_page_renders_for_guest_and_member(): void
    {
        $storefront = fn ($uri) => ! str_starts_with($uri, 'admin') && ! str_starts_with($uri, 'livewire') && ! str_starts_with($uri, '_');
        $failures = [];

        foreach (['tamu' => null, 'member' => $this->member] as $who => $customer) {
            $this->app['auth']->forgetGuards();
            if ($customer) {
                $this->actingAs($customer, 'customer');
            }

            foreach ($this->urls($storefront) as $name => $url) {
                $status = $this->get($url)->getStatusCode();
                if ($status >= 500 || $status === 404) {
                    $failures[] = "{$who}: {$name} -> {$status}";
                }
            }
        }

        // Halaman dengan parameter slug/kode (bukan model)
        $product = Product::where('is_published', true)->where('type', 'pre_order')->firstOrFail();
        $this->get(route('store.product', $product->slug))->assertOk();
        $this->get(route('store.po.create', $product->slug))->assertOk();
        $order = Order::firstOrFail();
        $this->get(URL::signedRoute('store.order', $order))->assertOk();
        $this->get(URL::signedRoute('store.pay', $order->payments()->firstOrFail()))->assertOk();

        $this->assertSame([], $failures);
    }

    /**
     * URL semua route GET bernama yang lolos $filter. Parameter model diisi record pertama;
     * route yang parameternya tidak bisa diisi, serta unduhan (export/PDF/template), dilewati.
     */
    private function urls(callable $filter): array
    {
        $urls = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! in_array('GET', $route->methods(), true) || ! $filter($route->uri())
                || preg_match('/export|pdf|template|captcha|chat\.image/i', $name . ' ' . $route->uri())) {
                continue;
            }

            $models = collect($route->signatureParameters(['subClass' => UrlRoutable::class]))
                ->mapWithKeys(fn ($p) => [Str::snake($p->getName()) => $p->getType()?->getName()]);

            $params = [];
            foreach ($route->parameterNames() as $param) {
                $class = $models[Str::snake($param)] ?? null;
                $record = $class ? $class::query()->first() : null;
                if (! $record) {
                    continue 2;
                }
                $params[$param] = $record;
            }

            $urls[$name] = route($name, $params);
        }

        return $urls;
    }

    /** Pesanan online (sampai diserahkan = transaksi), PO, stok masuk, dan chat. */
    private function createSampleData(): void
    {
        $ready = Product::where('sku', 'BAN-HG-0230')->firstOrFail();
        $preorder = Product::where('sku', 'GSC-SCL-0711')->firstOrFail();
        $admin = User::role('admin')->firstOrFail();

        $this->post(route('store.cart.add'), ['product_id' => $ready->id, 'qty' => 1]);
        $this->post(route('store.checkout.store'), [
            'name' => 'Smoke', 'email' => 'smoke@example.com', 'phone' => '0812', 'method' => 'qris', 'terms' => '1',
        ])->assertRedirect();
        $order = Order::firstOrFail();
        $this->post(URL::signedRoute('store.pay', $order->payments()->firstOrFail()));

        $this->actingAs($admin, 'web');
        $this->post(route('online-orders.ready', $order))->assertSessionHasNoErrors();
        $this->post(route('online-orders.handover', $order))->assertSessionHasNoErrors();
        $this->post(route('pre-orders.store'), [
            'customer_id' => $this->member->id, 'product_id' => $preorder->id, 'quantity' => 1,
            'unit_price' => $preorder->selling_price, 'order_date' => now()->toDateString(),
            'dp_amount' => 1000000, 'payment_method' => 'transfer',
        ])->assertSessionHasNoErrors();
        $this->post(route('stock-ins.store'), [
            'product_id' => $ready->id, 'quantity' => 2, 'purchase_price' => 180000,
            'ordered_at' => now()->toDateString(), 'status' => 'ordered',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->member, 'customer');
        Livewire::test(ChatWidget::class)->call('toggle')->set('body', 'Halo')->call('send')->assertHasNoErrors();
    }
}
