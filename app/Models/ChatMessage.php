<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu pesan chat. sender_type: customer | staff (user_id = staf pengirim).
 * attachable: Product, Order, atau PreOrder yang dilampirkan.
 */
class ChatMessage extends Model
{
    protected $fillable = ['conversation_id', 'sender_type', 'user_id', 'body', 'image_path', 'attachable_type', 'attachable_id', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function isFromStaff(): bool
    {
        return $this->sender_type === 'staff';
    }

    /** URL gambar lewat route berizin (file disimpan privat). */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? route('chat.image', $this) : null;
    }
}
