{{-- Pesan error umum + daftar baris import yang gagal. --}}
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('import_errors'))
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <strong>Baris yang gagal diimport:</strong>
        <ul class="mb-0 mt-1 small">
            @foreach (array_slice(session('import_errors'), 0, 20) as $err)
                <li>{{ $err }}</li>
            @endforeach
            @if (count(session('import_errors')) > 20)
                <li>... dan {{ count(session('import_errors')) - 20 }} baris lainnya</li>
            @endif
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif
