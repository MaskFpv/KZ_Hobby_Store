<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat pelanggan ↔ CS. Satu percakapan per member; pesan bisa berisi teks,
 * gambar (disimpan privat di storage/app/chat), dan lampiran produk/pesanan/PO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('sender_type', ['customer', 'staff']);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body')->nullable();
            $table->string('image_path')->nullable();
            $table->nullableMorphs('attachable');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'sender_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversations');
    }
};
