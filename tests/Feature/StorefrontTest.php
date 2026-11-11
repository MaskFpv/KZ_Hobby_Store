<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Support\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Fase B: website pelanggan, akun member, captcha, field produk website. */
class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function registerPayload(array $override = []): array
    {
        return array_merge([
            'nama' => 'Mikasa Ackerman',
            'email' => 'mikasa@example.com',
            'telepon' => '081211112222',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'terms' => '1',
            'captcha' => 'ABCDE',
        ], $override);
    }

    public function test_public_pages_render(): void
    {
        $nendo = Product::where('sku', 'GSC-NEN-2367')->first();
        $po = Product::where('sku', 'GSC-SCL-0711')->first();

        $this->get('/')->assertOk()->assertSee('Koleksi impianmu')->assertSee($nendo->name)->assertSee('Pre-Order Dibuka')
            ->assertSee('data-theme-toggle', false)->assertSee('data-motion-toggle', false)->assertSee("localStorage.getItem('kz-theme')", false);
        $this->get(route('store.catalog'))->assertOk()->assertSee($nendo->name);
        $this->get(route('store.product', $nendo->slug))->assertOk()->assertSee($nendo->name)->assertSee('Rp 850.000');
        $this->get(route('store.product', $po->slug))->assertOk()->assertSee('DP minimal')->assertSee('Juni 2027');
        $this->get(route('store.preorders'))->assertOk()->assertSee($po->name);
        $this->get(route('store.terms'))->assertOk()->assertSee('Syarat');
        $this->get(route('store.about'))->assertOk();
    }

    public function test_catalog_filters_and_search(): void
    {
        $this->get(route('store.catalog', ['tipe' => 'pre_order']))
            ->assertOk()->assertSee('Scale Figure Makima')->assertDontSee('Nendoroid Frieren');
        $this->get(route('store.catalog', ['kategori' => 'gunpla']))
            ->assertSee('HG 1/144 Gundam Aerial')->assertDontSee('Acrylic Stand Bocchi');
        $this->get(route('store.catalog', ['q' => 'bocchi']))
            ->assertSee('Acrylic Stand Bocchi')->assertDontSee('Gundam');
        $this->get(route('store.catalog', ['max' => 200000]))
            ->assertSee('Acrylic Stand Bocchi')->assertDontSee('figma Gojo');
        $this->get(route('store.catalog', ['tipe' => 'lelang']))->assertSessionHasErrors('tipe');
    }

    public function test_unpublished_product_is_hidden(): void
    {
        $p = Product::where('sku', 'BAN-APP-0019')->first();
        $p->update(['is_published' => false]);

        $this->get(route('store.catalog'))->assertDontSee($p->name);
        $this->get(route('store.product', $p->slug))->assertNotFound();
    }

    public function test_register_requires_captcha_and_terms(): void
    {
        $this->post(route('store.register'), $this->registerPayload(['terms' => null, 'captcha' => 'WRONG']))
            ->assertSessionHasErrors('terms');

        $this->withSession([Captcha::SESSION_KEY => 'ABCDE'])
            ->post(route('store.register'), $this->registerPayload(['captcha' => 'zzzzz']))
            ->assertSessionHasErrors('captcha');
        $this->assertGuest('customer');

        // Kode captcha sekali pakai & tidak peka huruf besar/kecil
        $this->withSession([Captcha::SESSION_KEY => 'ABCDE'])
            ->post(route('store.register'), $this->registerPayload(['captcha' => 'abcde']))
            ->assertRedirect(route('store.account'));

        $customer = Customer::where('email', 'mikasa@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertTrue($customer->hasWebAccount());
        $this->assertNotNull($customer->terms_accepted_at);
        $this->assertMatchesRegularExpression('/^MBR\d{5}$/', $customer->kode_member);
        $this->assertNotSame('rahasia123', $customer->getAttributes()['password']); // ter-hash
        $this->assertNull(session(Captcha::SESSION_KEY));
    }

    public function test_cannot_register_existing_member_email(): void
    {
        // rizky@example.com = member yang didaftarkan kasir (tanpa akun web)
        $this->withSession([Captcha::SESSION_KEY => 'ABCDE'])
            ->post(route('store.register'), $this->registerPayload(['email' => 'rizky@example.com']))
            ->assertSessionHasErrors('email');
    }

    public function test_captcha_image_is_png(): void
    {
        $res = $this->get(route('store.captcha'))->assertOk();
        $this->assertSame('image/png', $res->headers->get('content-type'));
        $this->assertSame(5, strlen(session(Captcha::SESSION_KEY)));
    }

    public function test_customer_login_logout_and_account(): void
    {
        $customer = Customer::factory()->create(['email' => 'eren@example.com']);
        $customer->forceFill(['password' => 'rahasia123'])->save();

        $this->get(route('store.account'))->assertRedirect(route('store.login'));

        $this->post(route('store.login'), ['email' => 'eren@example.com', 'password' => 'salah123'])->assertSessionHasErrors('email');
        $this->post(route('store.login'), ['email' => 'eren@example.com', 'password' => 'rahasia123'])->assertRedirect(route('store.account'));

        $this->get(route('store.account'))->assertOk()->assertSee($customer->kode_member);
        $this->get(route('store.login'))->assertRedirect(route('store.account')); // sudah login

        $this->put(route('store.account.profile'), ['nama' => 'Eren Y', 'email' => 'eren@example.com', 'telepon' => '0812'])
            ->assertSessionHas('store_success');
        $this->assertSame('Eren Y', $customer->fresh()->nama);

        $this->post(route('store.logout'))->assertRedirect(route('store.home'));
        $this->assertGuest('customer');
    }

    public function test_staff_login_is_not_a_member_session(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first();

        $this->actingAs($admin)->get(route('store.account'))->assertRedirect(route('store.login'));
    }

    public function test_member_login_cannot_open_admin_panel(): void
    {
        $customer = Customer::factory()->create();

        $this->actingAs($customer, 'customer')->get(route('home'))->assertRedirect(route('login'));
        $this->get(route('products.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_set_storefront_fields(): void
    {
        $admin = User::where('email', 'admin@gmail.com')->first();
        $p = Product::where('sku', 'BAN-MG-0255')->first();

        $this->actingAs($admin)->put(route('products.update', $p), [
            'category_id' => $p->category_id, 'brand_id' => $p->brand_id, 'name' => $p->name, 'sku' => $p->sku,
            'purchase_price' => 950000, 'selling_price' => 1250000, 'condition' => 'new', 'type' => 'pre_order',
            // is_published tidak dicentang -> false
            'is_featured' => '1', 'po_deadline' => '2027-01-31', 'release_estimate' => 'April 2027',
        ])->assertRedirect();

        $p->refresh();
        $this->assertFalse($p->is_published);
        $this->assertTrue($p->is_featured);
        $this->assertSame('2027-01-31', $p->po_deadline->toDateString());
        $this->assertSame('April 2027', $p->release_estimate);
    }

    public function test_closed_pre_order_is_hidden_from_po_page(): void
    {
        $p = Product::where('sku', 'BAN-MG-0255')->first();
        $p->update(['po_deadline' => now()->subDay()->toDateString()]);

        $this->get(route('store.preorders'))->assertDontSee($p->name);
        $this->get(route('store.product', $p->slug))->assertOk()->assertSee('PO sudah ditutup');
    }
}
