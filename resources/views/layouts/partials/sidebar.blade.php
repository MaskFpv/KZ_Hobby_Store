{{-- Sidebar. Isi menu diatur di config/menu.php ($menuGroups dari AppServiceProvider). --}}
@php
    $authUser = auth()->user();
    $roleName = $authUser?->getRoleNames()->first();
@endphp

<aside class="app-sidebar" aria-label="Menu utama">
    <a href="{{ route('home') }}" class="brand">
        <img src="{{ asset('img/logo.svg') }}" alt="">
        <span class="brand-text">
            <span class="brand-name d-block">{{ config('store.name') }}</span>
            <span class="brand-tagline">{{ config('store.tagline') }}</span>
        </span>
    </a>

    <nav>
        @foreach ($menuGroups as $group)
            @if ($group['title'])
                <div class="side-title">{{ $group['title'] }}</div>
            @endif
            @foreach ($group['items'] as $item)
                <a href="{{ route($item['route']) }}" class="side-link {{ $item['is_active'] ? 'active' : '' }}"
                    title="{{ $item['label'] }}" @if ($item['is_active']) aria-current="page" @endif>
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    @if ($authUser)
        <div class="user-card">
            <span class="avatar">{{ strtoupper(mb_substr($authUser->name, 0, 1)) }}</span>
            <div class="user-text text-truncate">
                <div class="user-name text-truncate">{{ $authUser->name }}</div>
                <div class="user-role">{{ $roleName === 'super-admin' ? 'Super Admin' : ucfirst($roleName ?? 'User') }}</div>
            </div>
        </div>
    @endif
</aside>
