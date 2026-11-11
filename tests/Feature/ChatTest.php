<?php

namespace Tests\Feature;

use App\Http\Livewire\Admin\ChatInbox;
use App\Http\Livewire\Store\ChatWidget;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Fase D: chat member ↔ CS (widget storefront & inbox panel). */
class ChatTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
        $this->product = Product::where('is_published', true)->firstOrFail();
    }

    private function staff(string $role): User
    {
        return User::role($role)->firstOrFail();
    }

    private function member(string $email = 'nezuko@example.com'): Customer
    {
        $c = Customer::factory()->create(['email' => $email]);
        $c->forceFill(['password' => 'rahasia123'])->save();

        return $c;
    }

    /** Member mengirim satu pesan teks; mengembalikan percakapannya. */
    private function memberSays(Customer $customer, string $text): Conversation
    {
        $this->actingAs($customer, 'customer');
        Livewire::test(ChatWidget::class)->call('toggle')->set('body', $text)->call('send')->assertHasNoErrors();

        return Conversation::where('customer_id', $customer->id)->firstOrFail();
    }

    public function test_guest_gets_member_only_prompt_and_member_gets_widget(): void
    {
        $this->get(route('store.product', $this->product->slug))->assertOk()
            ->assertSee('data-chat-guest', false)
            ->assertSee('Chat dengan CS khusus member')
            ->assertSee('data-chat-attach="product:' . $this->product->id . '"', false)
            ->assertDontSee('livewire.js', false);

        $this->actingAs($this->member(), 'customer')->get(route('store.home'))->assertOk()
            ->assertSeeLivewire('store.chat-widget')
            ->assertDontSee('data-chat-guest', false);
    }

    public function test_member_sends_text_image_and_product(): void
    {
        $this->actingAs($this->member(), 'customer');

        Livewire::test(ChatWidget::class)
            ->call('openWith', 'product', $this->product->id)
            ->assertSet('open', true)
            ->assertSee($this->product->name)
            ->set('body', 'Masih ada stok?')
            ->set('image', UploadedFile::fake()->image('foto.jpg', 400, 300))
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('body', '')
            ->assertSet('attachId', null)
            ->assertSee('Masih ada stok?');

        $message = ChatMessage::firstOrFail();
        $this->assertSame('customer', $message->sender_type);
        $this->assertTrue($message->attachable->is($this->product));
        Storage::disk('local')->assertExists($message->image_path);
        $this->assertNotNull($message->conversation->last_message_at);
    }

    public function test_empty_message_oversized_image_and_foreign_attachment_are_rejected(): void
    {
        $other = $this->member('zenitsu@example.com');
        $foreignOrder = Order::create([
            'customer_id' => $other->id, 'name' => 'Zenitsu', 'email' => 'zenitsu@example.com', 'phone' => '0812',
            'total' => 100000, 'payment_method' => 'qris', 'payment_due_at' => now()->addDay(),
        ]);
        $this->actingAs($this->member(), 'customer');

        Livewire::test(ChatWidget::class)->call('toggle')->call('send')->assertHasErrors('body');
        Livewire::test(ChatWidget::class)->set('image', UploadedFile::fake()->image('besar.jpg')->size(3000))
            ->assertHasErrors('image')->assertSet('image', null);
        $this->assertSame(0, ChatMessage::count());

        // Pesanan member lain tidak ikut terlampir
        Livewire::test(ChatWidget::class)->call('openWith', 'order', $foreignOrder->id)
            ->assertDontSee($foreignOrder->code)
            ->set('body', 'Halo')->call('send')->assertHasNoErrors();
        $this->assertNull(ChatMessage::firstOrFail()->attachable_id);
    }

    public function test_cs_replies_and_both_sides_see_read_status(): void
    {
        $customer = $this->member();
        $conversation = $this->memberSays($customer, 'Halo CS, PO saya kapan tiba?');

        $cs = $this->staff('cs');
        $this->actingAs($cs, 'web');
        $this->assertSame(1, Conversation::awaitingStaff()->count());
        $this->get(route('home'))->assertSee('Chat Pelanggan')->assertSee('side-badge', false);
        $this->get(route('chats.index'))->assertOk()->assertSeeLivewire('admin.chat-inbox')->assertSee($customer->nama);

        Livewire::test(ChatInbox::class)
            ->call('select', $conversation->id)
            ->assertSee('PO saya kapan tiba?')
            ->call('attach', 'product', $this->product->id)
            ->set('body', 'Estimasi bulan depan, kak!')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSee('Estimasi bulan depan, kak!');

        // CS membuka percakapan = pesan member dibaca, badge sidebar hilang
        $this->assertNotNull(ChatMessage::where('sender_type', 'customer')->first()->read_at);
        $this->assertSame(0, Conversation::awaitingStaff()->count());
        $reply = ChatMessage::where('sender_type', 'staff')->firstOrFail();
        $this->assertSame($cs->id, $reply->user_id);
        $this->assertTrue($reply->attachable->is($this->product));

        // Member: badge 1 saat tertutup, dibaca saat widget dibuka
        $this->actingAs($customer, 'customer');
        Livewire::test(ChatWidget::class)
            ->assertSeeHtml('title="1 pesan belum dibaca"')
            ->call('toggle')
            ->assertSee('Estimasi bulan depan, kak!')
            ->assertDontSeeHtml('pesan belum dibaca');
        $this->assertNotNull($reply->fresh()->read_at);
    }

    public function test_inbox_and_chat_images_are_protected(): void
    {
        $owner = $this->member();
        $this->actingAs($owner, 'customer');
        Livewire::test(ChatWidget::class)->call('toggle')->set('image', UploadedFile::fake()->image('a.png'))->call('send')->assertHasNoErrors();
        $url = route('chat.image', ChatMessage::firstOrFail());

        $this->get($url)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->get($url)->assertForbidden();
        $this->actingAs($this->member('zenitsu@example.com'), 'customer')->get($url)->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->staff('cs'), 'web')->get($url)->assertOk();

        $this->app['auth']->forgetGuards();
        $this->actingAs($this->staff('kasir'), 'web');
        $this->get($url)->assertForbidden();
        $this->get(route('chats.index'))->assertForbidden();
        $this->get(route('home'))->assertDontSee('Chat Pelanggan');
    }
}
