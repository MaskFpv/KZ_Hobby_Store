<?php

namespace App\Http\Livewire\Store;

use App\Models\Conversation;
use App\Models\Customer;
use App\Services\ChatService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Widget chat mengambang di storefront (khusus member login).
 * Polling 3 detik hanya saat panel terbuka; badge belum dibaca diperbarui tiap 20 detik.
 */
class ChatWidget extends Component
{
    use WithFileUploads;

    public bool $open = false;
    public bool $picker = false;
    public int $limit = 40;
    public string $body = '';
    /** @var \Livewire\TemporaryUploadedFile|null gambar yang sedang diunggah */
    public $image;
    public ?string $attachType = null;
    public ?int $attachId = null;

    protected $listeners = ['chat-open' => 'openWith'];

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->picker = false;
        $this->refresh();
    }

    /** Dibuka dari tombol "Tanya CS" di halaman lain, opsional dengan lampiran. */
    public function openWith(?string $type = null, int|string|null $id = null): void
    {
        $this->open = true;

        if (in_array($type, ChatService::ATTACHMENTS, true) && $id) {
            $this->attach($type, $id);
        }

        $this->refresh();
    }

    /** Dipanggil polling: tandai balasan CS sudah dibaca selama panel terbuka. */
    public function refresh(): void
    {
        if ($this->open && ($conversation = $this->conversation())) {
            $this->chat()->markRead($conversation, 'customer');
        }
    }

    /** Cek gambar begitu dipilih; file yang ditolak langsung dibuang. */
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
        $this->picker = false;
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
        $customer = $this->customer();
        abort_unless($customer, 403);

        $this->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'image' => ChatService::IMAGE_RULES,
        ], [], ['body' => 'pesan', 'image' => 'gambar']);

        $chat = $this->chat();
        $conversation = $chat->conversationFor($customer);
        $chat->send($conversation, $customer, $this->body, $this->image, $chat->attachable($conversation, $this->attachType, $this->attachId));

        $this->reset(['body', 'image', 'attachType', 'attachId', 'picker']);
        $this->dispatchBrowserEvent('chat-sent');
    }

    public function render()
    {
        $customer = $this->customer();

        if (! $customer) {
            return view('livewire.store.chat-widget', ['customer' => null]);
        }

        $chat = $this->chat();
        // Lampiran dicek terhadap percakapan (yang mungkin belum ada) milik member ini
        $conversation = $this->conversation() ?? new Conversation(['customer_id' => $customer->id]);
        $total = $this->open && $conversation->exists ? $conversation->messages()->count() : 0;

        return view('livewire.store.chat-widget', [
            'customer' => $customer,
            'unread' => $this->open ? 0 : $chat->unreadForCustomer($customer),
            'messages' => $total ? $chat->thread($conversation, $this->limit) : collect(),
            'hasMore' => $total > $this->limit,
            'attachment' => $chat->attachable($conversation, $this->attachType, $this->attachId),
            'myOrders' => $this->picker ? $customer->orders()->latest()->limit(5)->get() : collect(),
            'myPreOrders' => $this->picker ? $customer->preOrders()->with('product:id,name')->latest()->limit(5)->get() : collect(),
        ]);
    }

    private function customer(): ?Customer
    {
        return auth('customer')->user();
    }

    private function conversation(): ?Conversation
    {
        $customer = $this->customer();

        return $customer ? $this->chat()->findFor($customer) : null;
    }

    private function chat(): ChatService
    {
        return app(ChatService::class);
    }
}
