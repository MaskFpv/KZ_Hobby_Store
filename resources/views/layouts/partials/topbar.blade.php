{{-- Topbar: tombol sidebar, judul halaman (dari config/menu.php), jam WIB, menu akun. --}}
<header class="app-topbar">
    <button type="button" class="btn-icon" data-toggle-sidebar aria-label="Buka/tutup menu">
        <i class="bi bi-list fs-5"></i>
    </button>

    <div class="me-auto">
        @if (!empty($currentMenu['group']))
            <div class="crumb">{{ $currentMenu['group'] }}</div>
        @endif
        <h1 class="page-title">@yield('title', $currentMenu['label'] ?? '')</h1>
    </div>

    {{-- Cari menu: ketik nama halaman lalu Enter (daftar diambil dari sidebar) --}}
    <div class="menu-search" data-menu-search>
        <i class="bi bi-search"></i>
        <input type="search" placeholder="Cari menu… (mis. stok, artikel)" aria-label="Cari menu" autocomplete="off">
    </div>

    <span class="clock-chip d-none d-xl-inline" id="topbar-clock"></span>

    <button type="button" class="btn-icon theme-toggle" data-theme-toggle aria-label="Ganti mode terang / gelap" title="Mode terang / gelap">
        <i class="bi bi-moon-stars-fill fs-5"></i><i class="bi bi-sun-fill fs-5"></i>
    </button>

    @auth
        <div class="dropdown">
            <button class="user-chip" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun">
                <span class="avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="user-chip-name d-none d-md-inline">{{ strtok(auth()->user()->name, ' ') }}</span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2">
                    <div class="fw-bold">{{ auth()->user()->name }}</div>
                    <small class="text-muted">{{ auth()->user()->email }}</small>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-left me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    @endauth
</header>
