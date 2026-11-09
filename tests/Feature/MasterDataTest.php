<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Fase 2: CRUD Brand, Produk, Pelanggan + export/import + hak akses kasir.
 * Jalan di DB kz_coffee_test (lihat phpunit.xml).
 */
class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed per test (di dalam transaksi RefreshDatabase), tidak bergantung urutan test class lain
        $this->seed();
        Storage::fake('public');
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@gmail.com')->firstOrFail();
    }

    private function productPayload(array $override = []): array
    {
        return array_merge([
            'category_id' => Category::first()->id,
            'brand_id' => Brand::first()->id,
            'name' => 'Figma Frieren',
            'sku' => 'mxf-fig-0700',
            'purchase_price' => 1200000,
            'selling_price' => 1550000,
            'stock' => 3,
            'condition' => 'new',
            'type' => 'ready_stock',
            'description' => 'Test',
        ], $override);
    }

    /* ---------------- Brand ---------------- */

    public function test_admin_can_crud_brand_with_logo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('brands.store'), [
                'name' => 'Aniplex',
                'country' => 'Jepang',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])->assertRedirect(route('brands.index'));

        $brand = Brand::where('name', 'Aniplex')->firstOrFail();
        $this->assertSame('aniplex', $brand->slug);
        Storage::disk('public')->assertExists($brand->logo);

        $old = $brand->logo;
        $this->put(route('brands.update', $brand), ['name' => 'Aniplex Inc', 'remove_logo' => 1])
            ->assertRedirect(route('brands.index'));
        $this->assertNull($brand->fresh()->logo);
        Storage::disk('public')->assertMissing($old);
        $this->assertSame('aniplex-inc', $brand->fresh()->slug);

        $this->delete(route('brands.destroy', $brand))->assertRedirect(route('brands.index'));
        $this->assertModelMissing($brand);
    }

    public function test_brand_used_by_product_cannot_be_deleted(): void
    {
        $brand = Product::first()->brand;

        $this->actingAs($this->admin)
            ->delete(route('brands.destroy', $brand))
            ->assertSessionHas('error');

        $this->assertModelExists($brand);
    }

    public function test_brand_name_must_be_unique(): void
    {
        $this->actingAs($this->admin)
            ->post(route('brands.store'), ['name' => 'Bandai'])
            ->assertSessionHasErrors('name');
    }

    /* ---------------- Produk ---------------- */

    public function test_admin_can_create_product_with_image_and_uppercase_sku(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productPayload([
                'image' => UploadedFile::fake()->image('fig.jpg', 400, 400),
            ]))->assertRedirect();

        $product = Product::where('sku', 'MXF-FIG-0700')->firstOrFail();
        $this->assertSame(3, $product->stock);
        $this->assertSame('figma-frieren', $product->slug);
        Storage::disk('public')->assertExists($product->image);
        $this->assertStringContainsString('/storage/products/', $product->image_url);

        $this->get(route('products.show', $product))->assertOk()->assertSee('Figma Frieren');
    }

    public function test_sku_auto_generated_when_empty(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productPayload(['sku' => '']))
            ->assertRedirect();

        $this->assertMatchesRegularExpression('/^HB-[A-Z0-9]{8}$/', Product::latest('id')->value('sku'));
    }

    public function test_invalid_product_input_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products.store'), $this->productPayload([
                'sku' => 'GSC-NEN-2367',        // duplikat seeder
                'selling_price' => -1,
                'condition' => 'rusak',
                'type' => 'lelang',
                'stock' => -5,
            ]))
            ->assertSessionHasErrors(['sku', 'selling_price', 'condition', 'type', 'stock']);
    }

    public function test_stock_cannot_be_changed_from_edit_form(): void
    {
        $product = Product::where('sku', 'GSC-NEN-2367')->first();

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), $this->productPayload(['sku' => $product->sku, 'stock' => 99]))
            ->assertSessionHasErrors('stock');

        $payload = $this->productPayload(['sku' => $product->sku, 'name' => 'Nendoroid Frieren (Rerun)']);
        unset($payload['stock']);
        $this->put(route('products.update', $product), $payload)->assertRedirect(route('products.show', $product));

        $this->assertSame(6, $product->fresh()->stock);
        $this->assertSame('Nendoroid Frieren (Rerun)', $product->fresh()->name);
    }

    public function test_product_with_stock_history_cannot_be_deleted_but_sold_one_can(): void
    {
        $withHistory = Product::first();
        StockIn::create(['product_id' => $withHistory->id, 'quantity' => 1, 'ordered_at' => now()]);

        $this->actingAs($this->admin)
            ->delete(route('products.destroy', $withHistory))
            ->assertSessionHas('error');
        $this->assertModelExists($withHistory);

        $free = Product::where('sku', 'BAN-APP-0019')->first();
        $this->delete(route('products.destroy', $free))->assertSessionHas('success');
        $this->assertModelMissing($free);
    }

    public function test_product_index_filters(): void
    {
        $this->actingAs($this->admin)
            ->get(route('products.index', ['type' => 'pre_order']))
            ->assertOk()
            ->assertViewHas('products', fn ($p) => $p->count() === 2 && $p->every(fn ($x) => $x->type === 'pre_order'));

        $this->get(route('products.index', ['stock_status' => 'out']))
            ->assertViewHas('products', fn ($p) => $p->every(fn ($x) => $x->stock === 0));
    }

    /* ---------------- Pelanggan ---------------- */

    public function test_customer_crud_and_walk_in_protection(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), ['nama' => 'Hana', 'telepon' => '0812 3456 7890'])
            ->assertRedirect(route('customers.index'));

        $hana = Customer::where('nama', 'Hana')->firstOrFail();
        $this->assertMatchesRegularExpression('/^MBR\d{5}$/', $hana->kode_member);
        $this->get(route('customers.show', $hana))->assertOk()->assertSee($hana->kode_member);

        $this->post(route('customers.store'), ['nama' => 'X', 'email' => 'rizky@example.com'])
            ->assertSessionHasErrors('email');

        $walkIn = Customer::where('kode_member', 'MBR00000')->first();
        $this->delete(route('customers.destroy', $walkIn))->assertSessionHas('error');
        $this->assertModelExists($walkIn);

        $this->delete(route('customers.destroy', $hana))->assertSessionHas('success');
        $this->assertModelMissing($hana);
    }

    /* ---------------- Export & Import ---------------- */

    public function test_all_exports_download(): void
    {
        $this->actingAs($this->admin);

        foreach (['brands-export', 'products-export', 'customer-export', 'categories-export'] as $route) {
            $this->get(route($route))->assertOk()->assertDownload();
        }
        foreach (['brands-exportPdf', 'products-exportPdf', 'customer-exportPdf', 'categoires-exportPdf'] as $route) {
            $res = $this->get(route($route))->assertOk();
            $this->assertSame('application/pdf', $res->headers->get('content-type'));
        }
        $this->get(route('products-export', ['type' => 'pre_order']))->assertOk()->assertDownload();
    }

    public function test_import_templates_work(): void
    {
        $this->actingAs($this->admin);
        $file = fn ($name) => new UploadedFile(public_path("import/{$name}"), $name, null, null, true);

        $this->post(route('categories-import'), ['file' => $file('import-kategori.xlsx')])->assertSessionHas('success');
        $this->post(route('brands-import'), ['file' => $file('import-brand.xlsx')])->assertSessionHas('success');
        $this->post(route('products-import'), ['file' => $file('import-produk.xlsx')])->assertSessionHas('success');
        $this->post(route('customer-import'), ['file' => $file('import-customer.xlsx')])->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => 'Scale Figure']);
        $this->assertDatabaseHas('brands', ['name' => 'Alter']);
        $this->assertDatabaseHas('products', ['sku' => 'GSC-NEN-2400', 'stock' => 5, 'type' => 'ready_stock']);
        $this->assertDatabaseHas('products', ['name' => 'POP UP PARADE Anya Forger', 'type' => 'pre_order']);
        $this->assertDatabaseHas('customers', ['email' => 'budi@example.com', 'telepon' => '081298765432']);

        // Import ulang: produk lama diperbarui, stok tidak berubah
        Product::where('sku', 'GSC-NEN-2400')->update(['stock' => 1]);
        $this->post(route('products-import'), ['file' => $file('import-produk.xlsx')])->assertSessionHas('success');
        $this->assertSame(1, Product::where('sku', 'GSC-NEN-2400')->value('stock'));
    }

    public function test_import_rejects_wrong_file_type(): void
    {
        $this->actingAs($this->admin)
            ->post(route('products-import'), ['file' => UploadedFile::fake()->create('data.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('file');
    }

    /* ---------------- Hak akses kasir ---------------- */

    public function test_kasir_permissions_on_master_data(): void
    {
        $this->actingAs($this->kasir);

        $this->get(route('products.index'))->assertOk();
        $this->get(route('brands.index'))->assertOk();
        $this->get(route('products.show', Product::first()))->assertOk()->assertDontSee('Harga Modal');
        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), $this->productPayload())->assertForbidden();
        $this->delete(route('brands.destroy', Brand::first()))->assertForbidden();
        $this->post(route('products-import'), [])->assertForbidden();

        $this->post(route('customers.store'), ['nama' => 'Member Kasir'])->assertRedirect(route('customers.index'));
        $this->delete(route('customers.destroy', Customer::where('nama', 'Member Kasir')->first()))->assertForbidden();
    }
}
