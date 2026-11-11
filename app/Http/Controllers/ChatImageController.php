<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Gambar chat disimpan privat. Hanya pemilik percakapan (member)
 * atau staf yang boleh melihat chat yang bisa membukanya.
 */
class ChatImageController extends Controller
{
    public function __invoke(ChatMessage $message)
    {
        abort_unless($message->image_path, 404);

        $conversation = $message->conversation;
        $isOwner = (int) Auth::guard('customer')->id() === (int) $conversation->customer_id;
        $isStaff = (bool) Auth::guard('web')->user()?->can('view', $conversation);

        abort_unless($isOwner || $isStaff, 403);
        abort_unless(Storage::disk('local')->exists($message->image_path), 404);

        return Storage::disk('local')->response($message->image_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
