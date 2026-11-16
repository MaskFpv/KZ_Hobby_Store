<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ronde 3: filter katalog tanpa reload, galeri foto produk, halaman PO interaktif. */
class CatalogGalleryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_catalog_filter_returns_partial_with_results_only(): void
    {
        $po = Product::where('sku', 'GSC-SCL-0711')->firstOrFail();
        $ready = Product::where('sku', 'GSC-NEN-2367')->firstOrFail();

        $res = $this->get(route('store.catalog', ['tipe' => 'pre_order']), ['X-Catalog' => '1'])->assertOk();
        $this->assertStringNotContainsString('<html', $res->getContent());
        $res->assertSee($po->name)->assertDontSee($ready->name)->assertSee('data-catalog-link', false)->assertSee('data-total=', false);

        // Tanpa header: halaman penuh (tetap jalan tanpa JavaScript)
        $this->get(route('store.catalog', ['tipe' => 'pre_order']))->assertOk()->assertSee('<html', false)->assertSee('data-catalog', false);
    }

    public function test_admin_manages_gallery_and_website_shows_it(): void
    {
        Storage::fake('public');
        $product = Product::where('sku', 'GSC-NEN-2367')->firstOrFail();
        $this->actingAs(User::role('admin')->firstOrFail());

        $fields = $product->only(['category_id', 'brand_id', 'name', 'sku', 'purchase_price', 'selling_price', 'condition', 'type', 'description'])
            + ['is_published' => '1'];
        $this->put(route('products.update', $product), $fields + [
            'image' => UploadedFile::fake()->image('utama.jpg'),
            'gallery' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')],
        ])->assertSessionHasNoErrors();
        $this->assertCount(2, $product->images()->get());

        $page = $this->get(route('store.product', $product->slug))->assertOk();
        $page->assertSee('data-gallery-thumb="2"', false)->assertSee('data-lightbox', false);

        $first = $product->images()->first();
        $this->put(route('products.update', $product), $fields + ['remove_gallery' => [$first->id]])->assertSessionHasNoErrors();
        $this->assertCount(1, $product->images()->get());
        Storage::disk('public')->assertMissing($first->path);

        // Galeri produk lain tidak bisa dihapus lewat produk ini
        $other = Product::where('sku', 'BAN-HG-0230')->firstOrFail();
        $foreign = $other->images()->create(['path' => 'products/gallery/x.jpg']);
        $this->put(route('products.update', $product), $fields + ['remove_gallery' => [$foreign->id]]);
        $this->assertModelExists($foreign);
    }

    public function test_pre_order_page_has_spotlight_and_calculator(): void
    {
        $po = Product::where('sku', 'GSC-SCL-0711')->firstOrFail();

        $this->get(route('store.preorders'))->assertOk()
            ->assertSee('data-spot="' . $po->id . '"', false)
            ->assertSee('data-calc-range', false)
            ->assertSee('data-po-filter="soon"', false)
            ->assertSee($po->name);

        // Kalkulator mengisi form PO lewat query
        $this->actingAs($this->member(), 'customer')
            ->get(route('store.po.create', ['slug' => $po->slug, 'qty' => 2, 'dp' => 2000000]))->assertOk()
            ->assertSee('value="2"', false)->assertSee('value="2000000"', false);
    }

    private function member()
    {
        $c = \App\Models\Customer::factory()->create(['email' => 'po@example.com']);
        $c->forceFill(['password' => 'rahasia123'])->save();

        return $c;
    }
}
