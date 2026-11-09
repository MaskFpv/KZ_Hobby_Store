@props([
    'action',
    'template' => null,
    'columns' => null,
])

{{-- Modal import Excel standar. Pakai: <x-import-modal :action="route('x-import')" template="import/x.xlsx" columns="..." /> --}}
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importModalLabel">Import File Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ $action }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih file (.xlsx / .xls, maks 2 MB)</label>
                        <input type="file" class="form-control" id="file" name="file" accept=".xls,.xlsx" required>
                    </div>
                    @if ($columns)
                        <p class="small text-muted mb-2">Kolom: <code>{{ $columns }}</code></p>
                    @endif
                    @if ($template)
                        <p class="small">Belum punya format? <a href="{{ asset($template) }}" download>Download template Excel</a></p>
                    @endif
                    <button type="submit" class="btn btn-primary">Import</button>
                </form>
            </div>
        </div>
    </div>
</div>
