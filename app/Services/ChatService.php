<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PreOrder;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

/**
 * Logika chat yang dipakai widget storefront dan inbox panel:
 * kirim pesan, lampiran yang sah, dan status dibaca.
 */
class ChatService
{
    /** Tipe lampiran yang bisa dipilih dari UI. */
    public const ATTACHMENTS = ['product', 'order', 'preorder'];

    /** Gambar chat: maks 2 MB. */
    public const IMAGE_RULES = ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048'];

    /** Percakapan dibuat saat pesan pertama, agar inbox tidak berisi percakapan kosong. */
    public function conversationFor(Customer $customer): Conversation
    {
        return Conversation::firstOrCreate(['customer_id' => $customer->id]);
    }

    public function findFor(Customer $customer): ?Conversation
    {
        return Conversation::where('customer_id', $customer->id)->first();
    }

    /**
     * Lampiran yang boleh dikirim di percakapan ini: produk yang tampil di website,
     * atau pesanan/PO milik pelanggan percakapan itu sendiri. Selain itu null.
     */
    public function attachable(Conversation $conversation, ?string $type, $id): ?Model
    {
        if (! $id) {
            return null;
        }

        return match ($type) {
            'product' => Product::where('is_published', true)->find($id),
            'order' => Order::where('customer_id', $conversation->customer_id)->find($id),
            'preorder' => PreOrder::where('customer_id', $conversation->customer_id)->find($id),
            default => null,
        };
    }

    public function send(Conversation $conversation, Customer|User $sender, ?string $body, ?UploadedFile $image = null, ?Model $attachment = null): ChatMessage
    {
        $body = trim((string) $body);

        if ($body === '' && ! $image && ! $attachment) {
            throw ValidationException::withMessages(['body' => 'Tulis pesan, kirim gambar, atau lampirkan sesuatu.']);
        }

        $isStaff = $sender instanceof User;

        $message = $conversation->messages()->create([
            'sender_type' => $isStaff ? 'staff' : 'customer',
            'user_id' => $isStaff ? $sender->id : null,
            'body' => $body !== '' ? $body : null,
            'image_path' => $image?->store('chat/' . $conversation->id, 'local'),
            'attachable_type' => $attachment?->getMorphClass(),
            'attachable_id' => $attachment?->getKey(),
        ]);

        $conversation->update(['last_message_at' => $message->created_at]);

        return $message;
    }

    /** Tandai pesan dari pihak lawan sudah dibaca. $readerSide: customer | staff. */
    public function markRead(Conversation $conversation, string $readerSide): int
    {
        return $conversation->messages()
            ->where('sender_type', $readerSide === 'customer' ? 'staff' : 'customer')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Pesan staf yang belum dibaca pelanggan (badge widget). */
    public function unreadForCustomer(Customer $customer): int
    {
        return ChatMessage::where('sender_type', 'staff')->whereNull('read_at')
            ->whereHas('conversation', fn ($q) => $q->where('customer_id', $customer->id))
            ->count();
    }

    /** Pesan terbaru (urut lama → baru) beserta lampirannya. */
    public function thread(Conversation $conversation, int $limit): \Illuminate\Support\Collection
    {
        return $conversation->messages()
            ->with(['user:id,name', 'attachable' => fn ($morph) => $morph->morphWith([PreOrder::class => ['product:id,name']])])
            ->latest('id')->limit($limit)->get()->reverse()->values();
    }
}
