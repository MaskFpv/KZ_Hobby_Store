<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Percakapan chat satu member dengan tim CS toko. */
class Conversation extends Model
{
    protected $fillable = ['customer_id', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    /** Percakapan yang punya pesan pelanggan belum dibaca staf. */
    public function scopeAwaitingStaff(Builder $query): Builder
    {
        return $query->whereHas('messages', fn ($q) => $q->where('sender_type', 'customer')->whereNull('read_at'));
    }

    /** Tambah kolom unread_count: pesan dari pihak lawan yang belum dibaca. */
    public function scopeWithUnreadFor(Builder $query, string $readerSide): Builder
    {
        $from = $readerSide === 'customer' ? 'staff' : 'customer';

        return $query->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', $from)->whereNull('read_at')]);
    }
}
