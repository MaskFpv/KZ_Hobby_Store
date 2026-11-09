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

    <span class="clock-chip d-none d-md-inline" id="topbar-clock"></span>

    @auth
        <div class="dropdown">
            <button class="btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu akun">
                <i class="bi bi-person-circle fs-5"></i>
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
