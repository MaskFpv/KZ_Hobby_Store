<?php

namespace App\Http\Livewire\Admin;

use App\Models\Conversation;
use App\Models\Order;
use App\Models\PreOrder;
use App\Models\Product;
use App\Services\ChatService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Inbox "Chat Pelanggan" di panel (CS & Admin). Polling 3 detik:
 * daftar percakapan + thread aktif; pesan pelanggan di thread aktif otomatis ditandai dibaca.
 */
class ChatInbox extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public ?int $activeId = null;
    public string $search = '';
    public bool $picker = false;
    public string $productSearch = '';
    public int $limit = 40;
    public string $body = '';
    /** @var \Livewire\TemporaryUploadedFile|null gambar yang sedang diunggah */
    public $image;
    public ?string $attachType = null;
    public ?int $attachId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Conversation::class);
        $this->activeId = request()->integer('c') ?: null;
        $this->refresh();
    }

    /** Cek ulang izin di setiap request Livewire (middleware route hanya jalan saat halaman dibuka). */
    public function hydrate(): void
    {
        $this->authorize('viewAny', Conversation::class);
    }

    public function select(int $id): void
    {
        $this->activeId = $id;
        $this->limit = 40;
        $this->reset(['body', 'image', 'attachType', 'attachId', 'picker', 'productSearch']);
        $this->refresh();
    }

    /** Kembali ke daftar (tampilan HP). */
    public function back(): void
    {
        $this->activeId = null;
    }

    /** Dipanggil polling. */
    public function refresh(): void
    {
        if ($conversation = $this->active()) {
            $this->chat()->markRead($conversation, 'staff');
        }
    }

    public function updatedImage(): void
    {
        try {
            $this->validateOnly('image', ['image' => ChatService::IMAGE_RULES], [], ['image' => 'gambar']);
        } catch (ValidationException $e) {
            $this->reset('image');
            throw $e;
        }
    }

    public function attach(string $type, int|string $id): void
    {
        $this->attachType = $type;
        $this->attachId = (int) $id;
        $this->reset(['picker', 'productSearch']);
    }

    public function clearAttachment(): void
    {
        $this->reset(['attachType', 'attachId']);
    }

    public function loadMore(): void
    {
        $this->limit += 40;
    }

    public function send(): void
    {
        $conversation = $this->active();
        abort_unless($conversation, 404);
        $this->authorize('reply', $conversation);

        $this->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'image' => ChatService::IMAGE_RULES,
        ], [], ['body' => 'pesan', 'image' => 'gambar']);

        $chat = $this->chat();
        $chat->send($conversation, auth()->user(), $this->body, $this->image, $chat->attachable($conversation, $this->attachType, $this->attachId));

        $this->reset(['body', 'image', 'attachType', 'attachId', 'picker', 'productSearch']);
    }

    public function render()
    {
        $chat = $this->chat();
        $search = trim($this->search);
        $term = trim($this->productSearch);
        $active = $this->active()?->load('customer');

        $conversations = Conversation::with(['customer:id,nama,kode_member', 'latestMessage'])
            ->withUnreadFor('staff')
            ->when($search !== '', fn ($q) => $q->whereHas('customer', fn ($c) => $c
                ->where('nama', 'like', "%{$search}%")
                ->orWhere('kode_member', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('telepon', 'like', "%{$search}%")))
            ->orderByDesc('last_message_at')
            ->limit(50)
            ->get();

        $picking = $active && $this->picker;

        return view('livewire.admin.chat-inbox', [
            'conversations' => $conversations,
            'active' => $active,
            'messages' => $active ? $chat->thread($active, $this->limit) : collect(),
            'hasMore' => $active && $active->messages()->count() > $this->limit,
            'attachment' => $active ? $chat->attachable($active, $this->attachType, $this->attachId) : null,
            'canReply' => $active && auth()->user()->can('reply', $active),
            'products' => $picking && mb_strlen($term) >= 2
                ? Product::where('is_published', true)
                    ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
                    ->limit(6)->get(['id', 'name', 'sku', 'selling_price'])
                : collect(),
            'customerOrders' => $picking ? Order::where('customer_id', $active->customer_id)->latest()->limit(5)->get() : collect(),
            'customerPreOrders' => $picking ? PreOrder::with('product:id,name')->where('customer_id', $active->customer_id)->latest()->limit(5)->get() : collect(),
        ]);
    }

    private function active(): ?Conversation
    {
        return $this->activeId ? Conversation::find($this->activeId) : null;
    }

    private function chat(): ChatService
    {
        return app(ChatService::class);
    }
}
