{{-- Widget chat member ↔ CS. Polling: 20 dtk untuk badge, 3 dtk hanya saat panel terbuka (.visible). --}}
<div class="chat-widget {{ $open ? 'is-open' : '' }}" data-chat-widget>
    @if ($customer)
        <span wire:poll.20s="refresh" hidden></span>

        <button type="button" class="chat-launcher" wire:click="toggle" aria-expanded="{{ $open ? 'true' : 'false' }}"
            aria-label="{{ $open ? 'Tutup chat' : 'Chat dengan CS' }}">
            <i class="bi {{ $open ? 'bi-x-lg' : 'bi-chat-heart-fill' }}"></i>
            @if ($unread)
                <span class="count" title="{{ $unread }} pesan belum dibaca">{{ $unread }}</span>
            @endif
        </button>

        <section class="chat-panel" @unless ($open) hidden @endunless aria-label="Chat dengan CS">
            <header class="chat-head">
                <span class="chat-avatar" aria-hidden="true">推</span>
                <div>
                    <b>CS {{ config('store.name') }}</b>
                    <small><i class="chat-online"></i> Dibalas saat jam buka · {{ config('store.hours') }}</small>
                </div>
                <button type="button" class="icon-btn" wire:click="toggle" aria-label="Tutup chat"><i class="bi bi-x-lg"></i></button>
            </header>

            <div class="chat-thread" data-chat-scroll="{{ $open ? 'open' : 'closed' }}" data-lenis-prevent wire:poll.3s.visible="refresh">
                @if ($hasMore)
                    <button type="button" class="chat-more" wire:click="loadMore">Muat pesan sebelumnya</button>
                @endif

                @forelse ($messages as $m)
                    @php $mine = ! $m->isFromStaff(); @endphp
                    @if ($loop->first || ! $messages[$loop->index - 1]->created_at->isSameDay($m->created_at))
                        <div class="chat-day">{{ $m->created_at->isToday() ? 'Hari ini' : $m->created_at->translatedFormat('d M Y') }}</div>
                    @endif
                    <div class="msg {{ $mine ? 'is-mine' : '' }}" wire:key="msg-{{ $m->id }}">
                        @unless ($mine)
                            <span class="msg-from">{{ $m->user->name ?? 'Tim CS' }}</span>
                        @endunless
                        @if ($m->image_url)
                            <a href="{{ $m->image_url }}" target="_blank" rel="noopener" class="msg-img">
                                <img src="{{ $m->image_url }}" alt="Gambar dari {{ $mine ? 'Anda' : 'CS' }}" loading="lazy">
                            </a>
                        @endif
                        @if ($m->attachable)
                            <x-chat-attachment :item="$m->attachable" />
                        @endif
                        @if ($m->body)
                            <p>{!! nl2br(e($m->body)) !!}</p>
                        @endif
                        <span class="msg-meta">
                            {{ $m->created_at->format('H:i') }}
                            @if ($mine)
                                <i class="bi {{ $m->read_at ? 'bi-check2-all is-read' : 'bi-check2' }}" title="{{ $m->read_at ? 'Dibaca' : 'Terkirim' }}"></i>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="chat-empty">
                        <span class="jp" aria-hidden="true">いらっしゃいませ!</span>
                        <p>Halo {{ strtok($customer->nama, ' ') }}-san! Tanya stok, pre-order, atau status pesanan — CS kami siap bantu.</p>
                        <div class="chip-list">
                            @foreach (['Apakah produk ini masih ada stok?', 'Kapan PO saya tiba?', 'Pesanan saya sudah bisa diambil?'] as $q)
                                <button type="button" class="chip" wire:click="$set('body', @js($q))">{{ $q }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforelse
            </div>

            <form class="chat-composer" wire:submit.prevent="send">
                @if ($attachment || $image)
                    <div class="chat-pending">
                        @if ($attachment)
                            <div class="chat-pending-item">
                                <x-chat-attachment :item="$attachment" />
                                <button type="button" wire:click="clearAttachment" aria-label="Hapus lampiran"><i class="bi bi-x"></i></button>
                            </div>
                        @endif
                        @if ($image)
                            <div class="chat-pending-item">
                                <img src="{{ $image->temporaryUrl() }}" alt="Pratinjau gambar" class="chat-pending-img">
                                <button type="button" wire:click="$set('image', null)" aria-label="Hapus gambar"><i class="bi bi-x"></i></button>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($picker)
                    <div class="chat-picker">
                        <small>Lampirkan pesanan / PO Anda</small>
                        @foreach ($myOrders as $o)
                            <button type="button" wire:click="attach('order', {{ $o->id }})"><i class="bi bi-bag-check"></i> {{ $o->code }} <span>{{ $o->status_label }}</span></button>
                        @endforeach
                        @foreach ($myPreOrders as $po)
                            <button type="button" wire:click="attach('preorder', {{ $po->id }})"><i class="bi bi-hourglass-split"></i> {{ $po->code }} <span>{{ $po->product->name ?? '' }}</span></button>
                        @endforeach
                        @if ($myOrders->isEmpty() && $myPreOrders->isEmpty())
                            <p>Belum ada pesanan atau PO. Untuk tanya produk, buka halaman produk lalu tekan <b>Tanya CS</b>.</p>
                        @endif
                    </div>
                @endif

                <div class="chat-input">
                    <label class="chat-tool" title="Kirim gambar (maks 2 MB)">
                        <input type="file" wire:model="image" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                        <i class="bi bi-image" wire:loading.remove wire:target="image"></i>
                        <span class="chat-spin" wire:loading wire:target="image"></span>
                    </label>
                    <button type="button" class="chat-tool {{ $picker ? 'is-active' : '' }}" wire:click="$toggle('picker')" title="Lampirkan pesanan / PO">
                        <i class="bi bi-paperclip"></i>
                    </button>
                    <textarea wire:model.defer="body" rows="1" maxlength="2000" placeholder="Tulis pesan…" aria-label="Pesan" data-chat-input></textarea>
                    <button class="chat-send" wire:loading.attr="disabled" wire:target="send,image" aria-label="Kirim">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
                @error('body') <div class="error">{{ $message }}</div> @enderror
                @error('image') <div class="error">{{ $message }}</div> @enderror
            </form>
        </section>
    @endif
</div>
