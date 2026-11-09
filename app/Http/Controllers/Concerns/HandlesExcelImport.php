<?php

namespace App\Http\Controllers\Concerns;

use App\Imports\BaseRowImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

trait HandlesExcelImport
{
    /**
     * Validasi file, jalankan import, lalu kembali ke $route dengan ringkasan.
     * Error per baris dikirim lewat session 'import_errors'.
     */
    protected function runImport(Request $request, BaseRowImport $import, string $route): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:2048'],
        ], [
            'file.required' => 'Pilih file Excel terlebih dahulu.',
            'file.mimes' => 'File harus berformat .xlsx atau .xls.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route($route)->with('error', 'File tidak bisa dibaca: ' . $e->getMessage());
        }

        $redirect = redirect()->route($route)->with('import_errors', $import->errors);

        return ($import->created + $import->updated) > 0 || empty($import->errors)
            ? $redirect->withSuccess($import->summary())
            : $redirect->with('error', $import->summary());
    }
}
