{{-- Inbox chat pelanggan. Polling 3 detik; di HP daftar & thread tampil bergantian. --}}
<div class="chat-inbox {{ $active ? 'has-active' : '' }}" wire:poll.3s="refresh">
    <aside class="inbox-list">
        <div class="inbox-search">
            <i class="bi bi-search"></i>
            <input type="search" wire:model.debounce.400ms="search" placeholder="Cari nama, kode member, email…" aria-label="Cari percakapan">
        </div>
        <div class="inbox-items">
            @forelse ($conversations as $c)
                @php
                    $last = $c->latestMessage;
                    $preview = $last?->body ? \Illuminate\Support\Str::limit($last->body, 48) : ($last?->image_path ? 'Mengirim gambar' : ($last?->attachable_type ? 'Mengirim lampiran' : ''));
                @endphp
                <button type="button" wire:key="conv-{{ $c->id }}" wire:click="select({{ $c->id }})"
                    class="inbox-item {{ $active?->id === $c->id ? 'active' : '' }} {{ $c->unread_count ? 'is-unread' : '' }}">
                    <span class="inbox-avatar">{{ mb_strtoupper(mb_substr($c->customer->nama, 0, 1)) }}</span>
                    <span class="inbox-text">
                        <span class="inbox-row"><b class="text-truncate">{{ $c->customer->nama }}</b><small>{{ $c->last_message_at?->diffForHumans(null, true, true) }}</small></span>
                        <small class="text-truncate">{{ $last?->isFromStaff() ? 'Anda: ' : '' }}{{ $preview }}</small>
                    </span>
                    @if ($c->unread_count)
                        <b class="side-badge" title="Belum dibaca">{{ $c->unread_count }}</b>
                    @endif
                </button>
            @empty
                <div class="empty-state"><i class="bi bi-chat-square-heart"></i>{{ $search ? 'Tidak ada yang cocok.' : 'Belum ada chat dari pelanggan.' }}</div>
            @endforelse
        </div>
    </aside>

    <section class="inbox-thread">
        @if ($active)
            <header class="inbox-head">
                <button type="button" class="btn btn-sm btn-light inbox-back" wire:click="back" aria-label="Kembali ke daftar"><i class="bi bi-arrow-left"></i></button>
                <span class="inbox-avatar">{{ mb_strtoupper(mb_substr($active->customer->nama, 0, 1)) }}</span>
                <div class="flex-fill" style="min-width:0">
                    <div class="fw-bold text-truncate">{{ $active->customer->nama }}</div>
                    <small class="text-muted text-truncate d-block">{{ $active->customer->kode_member }} · {{ $active->customer->telepon ?? '-' }} · {{ $active->customer->email ?? '-' }}</small>
                </div>
                @can('view', $active->customer)
                    <a href="{{ route('customers.show', $active->customer) }}" class="btn btn-sm btn-light" target="_blank"><i class="bi bi-person-vcard"></i> Profil</a>
                @endcan
            </header>

            <div class="inbox-messages" data-chat-scroll="{{ $active->id }}">
                @if ($hasMore)
                    <button type="button" class="chat-more" wire:click="loadMore">Muat pesan sebelumnya</button>
                @endif
                @forelse ($messages as $m)
                    @php $mine = $m->isFromStaff(); @endphp
                    @if ($loop->first || ! $messages[$loop->index - 1]->created_at->isSameDay($m->created_at))
                        <div class="chat-day">{{ $m->created_at->isToday() ? 'Hari ini' : $m->created_at->translatedFormat('d M Y') }}</div>
                    @endif
                    <div class="msg {{ $mine ? 'is-mine' : '' }}" wire:key="msg-{{ $m->id }}">
                        @if ($mine)
                            <span class="msg-from">{{ $m->user->name ?? 'Staf' }}</span>
                        @endif
                        @if ($m->image_url)
                            <a href="{{ $m->image_url }}" target="_blank" rel="noopener" class="msg-img"><img src="{{ $m->image_url }}" alt="Gambar chat" loading="lazy"></a>
                        @endif
                        @if ($m->attachable)
                            <x-chat-attachment :item="$m->attachable" :staff="true" />
                        @endif
                        @if ($m->body)
                            <p>{!! nl2br(e($m->body)) !!}</p>
                        @endif
                        <span class="msg-meta">
                            {{ $m->created_at->format('H:i') }}
                            @if ($mine)
                                <i class="bi {{ $m->read_at ? 'bi-check2-all is-read' : 'bi-check2' }}" title="{{ $m->read_at ? 'Dibaca pelanggan ' . $m->read_at->format('H:i') : 'Terkirim' }}"></i>
                            @endif
                        </span>
                    </div>
                @empty
                    <div class="empty-state my-auto"><i class="bi bi-chat-dots"></i>Belum ada pesan.</div>
                @endforelse
            </div>

            @if ($canReply)
                <form class="inbox-composer" wire:submit.prevent="send">
                    @if ($attachment || $image)
                        <div class="chat-pending">
                            @if ($attachment)
                                <div class="chat-pending-item">
                                    <x-chat-attachment :item="$attachment" :staff="true" />
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
                        <div class="inbox-picker">
                            <input type="search" class="form-control form-control-sm" wire:model.debounce.300ms="productSearch" placeholder="Rekomendasikan produk: ketik nama atau SKU">
                            @foreach ($products as $p)
                                <button type="button" wire:click="attach('product', {{ $p->id }})"><i class="bi bi-box-seam"></i> {{ $p->name }}
                                    <span>{{ $p->sku }} · Rp {{ number_format($p->selling_price, 0, ',', '.') }}</span></button>
                            @endforeach
                            <small>Pesanan & PO pelanggan ini</small>
                            @foreach ($customerOrders as $o)
                                <button type="button" wire:click="attach('order', {{ $o->id }})"><i class="bi bi-bag-check"></i> {{ $o->code }} <span>{{ $o->status_label }}</span></button>
                            @endforeach
                            @foreach ($customerPreOrders as $po)
                                <button type="button" wire:click="attach('preorder', {{ $po->id }})"><i class="bi bi-hourglass-split"></i> {{ $po->code }} <span>{{ $po->product->name ?? '' }}</span></button>
                            @endforeach
                            @if ($customerOrders->isEmpty() && $customerPreOrders->isEmpty())
                                <small class="text-muted">Pelanggan ini belum punya pesanan / PO.</small>
                            @endif
                        </div>
                    @endif

                    <div class="chat-input">
                        <label class="chat-tool" title="Kirim gambar (maks 2 MB)">
                            <input type="file" wire:model="image" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
                            <i class="bi bi-image" wire:loading.remove wire:target="image"></i>
                            <span class="spinner-border spinner-border-sm" wire:loading wire:target="image"></span>
                        </label>
                        <button type="button" class="chat-tool {{ $picker ? 'is-active' : '' }}" wire:click="$toggle('picker')" title="Lampirkan produk / pesanan / PO">
                            <i class="bi bi-paperclip"></i>
                        </button>
                        <textarea wire:model.defer="body" rows="1" maxlength="2000" placeholder="Balas pelanggan… (Enter kirim, Shift+Enter baris baru)" aria-label="Pesan" data-chat-input></textarea>
                        <button class="chat-send" wire:loading.attr="disabled" wire:target="send,image" aria-label="Kirim"><i class="bi bi-send-fill"></i></button>
                    </div>
                    @error('body') <div class="small text-danger">{{ $message }}</div> @enderror
                    @error('image') <div class="small text-danger">{{ $message }}</div> @enderror
                </form>
            @else
                <div class="p-3 small text-muted border-top">Anda hanya bisa membaca chat ini.</div>
            @endif
        @else
            <div class="empty-state my-auto"><i class="bi bi-chat-heart"></i>Pilih percakapan di kiri untuk membaca & membalas.</div>
        @endif
    </section>
</div>
