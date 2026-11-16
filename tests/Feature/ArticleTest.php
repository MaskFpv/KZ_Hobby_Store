<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Ronde 3: artikel website & role Admin Sosial Media. */
class ArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function sosmed(): User
    {
        return User::role('sosmed')->firstOrFail();
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Review Nendoroid Terbaru',
            'category' => 'hobi',
            'excerpt' => 'Ringkasan singkat.',
            'body' => '<h2>Bagian satu</h2><p>Isi artikel.</p>',
            'status' => 'published',
        ], $override);
    }

    public function test_sosmed_only_manages_articles(): void
    {
        $this->actingAs($this->sosmed());
        $this->get(route('home'))->assertOk()->assertSee('広報')->assertSee('Artikel')->assertSee('Tulis Artikel');
        $this->get(route('articles.index'))->assertOk();
        $this->get(route('articles.create'))->assertOk()->assertSee('tinymce');
        foreach (['dashboard.index', 'transaction.index', 'stock-ins.index', 'online-orders.index', 'chats.index'] as $r) {
            $this->get(route($r))->assertForbidden();
        }

        $this->actingAs(User::role('kasir')->firstOrFail());
        $this->get(route('articles.index'))->assertForbidden();
        $this->post(route('articles.store'), $this->payload())->assertForbidden();
    }

    public function test_body_is_sanitized_and_article_published(): void
    {
        $this->actingAs($this->sosmed())->post(route('articles.store'), $this->payload([
            'body' => '<p onclick="steal()">Halo <strong>kolektor</strong></p><script>alert(1)</script>'
                . '<iframe src="https://evil.example/x"></iframe>'
                . '<iframe src="https://www.youtube.com/embed/abc123" allowfullscreen></iframe>'
                . '<a href="javascript:alert(1)">klik</a>',
        ]))->assertRedirect();

        $article = Article::where('title', 'Review Nendoroid Terbaru')->firstOrFail();
        $this->assertStringNotContainsString('<script', $article->body);
        $this->assertStringNotContainsString('onclick', $article->body);
        $this->assertStringNotContainsString('evil.example', $article->body);
        $this->assertStringNotContainsString('javascript:', $article->body);
        $this->assertStringContainsString('<strong>kolektor</strong>', $article->body);
        $this->assertStringContainsString('youtube.com/embed/abc123', $article->body);
        $this->assertSame('review-nendoroid-terbaru', $article->slug);
        $this->assertNotNull($article->published_at);
        $this->assertSame($this->sosmed()->id, $article->user_id);

        $this->get(route('store.article', $article->slug))->assertOk()->assertSee('kolektor')->assertSee('data-toc', false);
        $this->assertSame(1, $article->fresh()->views);
    }

    public function test_empty_body_is_rejected(): void
    {
        $this->actingAs($this->sosmed())->post(route('articles.store'), $this->payload(['body' => '<p><script>x</script></p>']))
            ->assertSessionHasErrors('body');
    }

    public function test_drafts_and_scheduled_articles_stay_hidden(): void
    {
        $draft = Article::create($this->payload(['title' => 'Masih Draf', 'status' => 'draft']));
        $future = Article::create($this->payload(['title' => 'Terbit Besok', 'published_at' => now()->addDay()]));

        $this->get(route('store.articles'))->assertOk()->assertDontSee('Masih Draf')->assertDontSee('Terbit Besok');
        $this->get(route('store.article', $draft->slug))->assertNotFound();
        $this->get(route('store.article', $future->slug))->assertNotFound();
    }

    public function test_category_filter_returns_partial_for_instant_loading(): void
    {
        $res = $this->get(route('store.articles', ['kategori' => 'hobi']), ['X-Catalog' => '1'])->assertOk();
        $this->assertStringNotContainsString('<html', $res->getContent());
        $res->assertSee('Merawat Action Figure')->assertDontSee('Panduan Pre-Order');

        $this->get(route('store.articles'))->assertOk()->assertSee('<html', false)->assertSee('data-catalog-body', false);
        $this->get('/')->assertSee('Kabar Terbaru');
    }

    public function test_editor_image_upload_and_cover(): void
    {
        Storage::fake('public');
        $this->actingAs($this->sosmed());

        $res = $this->postJson(route('articles.upload-image'), ['file' => UploadedFile::fake()->image('isi.jpg')])->assertOk();
        $this->assertStringContainsString('/storage/articles/inline/', $res->json('location'));

        $this->post(route('articles.store'), $this->payload(['cover' => UploadedFile::fake()->image('cover.jpg', 1600, 900)]))->assertRedirect();
        $article = Article::latest('id')->firstOrFail();
        Storage::disk('public')->assertExists($article->cover);

        $this->put(route('articles.update', $article), $this->payload(['title' => 'Judul Baru', 'remove_cover' => '1']))->assertRedirect();
        $this->assertNull($article->fresh()->cover);
        $this->assertSame('Judul Baru', $article->fresh()->title);
        Storage::disk('public')->assertMissing($article->cover);

        $this->delete(route('articles.destroy', $article))->assertRedirect(route('articles.index'));
        $this->assertModelMissing($article);
    }
}
