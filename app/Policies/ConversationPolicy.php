<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/** Inbox chat pelanggan di panel (CS & Admin). Pelanggan diatur di ChatService. */
class ConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('list chats');
    }

    public function view(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('view chats');
    }

    /** Membalas pesan pelanggan. */
    public function reply(User $user, Conversation $conversation): bool
    {
        return $user->hasPermissionTo('create chats');
    }
}
