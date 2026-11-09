<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Import baris per baris. Baris yang gagal tidak menghentikan import,
 * tapi dicatat di $errors (nomor baris Excel + alasan) untuk ditampilkan.
 */
abstract class BaseRowImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;
    public array $errors = [];

    /**
     * Proses satu baris. Return 'created' | 'updated' | 'skipped'.
     * Lempar \InvalidArgumentException untuk baris tidak valid.
     */
    abstract protected function handleRow(array $row): string;

    public function collection(Collection $rows)
    {
        foreach ($rows as $i => $row) {
            $excelRow = $i + 2; // +1 heading, +1 index mulai 0

            try {
                $result = $this->handleRow(array_map(
                    fn ($v) => is_string($v) ? trim($v) : $v,
                    $row->toArray()
                ));
                $this->{$result}++;
            } catch (\InvalidArgumentException $e) {
                $this->errors[] = "Baris {$excelRow}: {$e->getMessage()}";
            }
        }
    }

    public function summary(): string
    {
        $text = "Import selesai: {$this->created} ditambah, {$this->updated} diperbarui, {$this->skipped} dilewati";

        return count($this->errors) ? $text . ', ' . count($this->errors) . ' gagal.' : $text . '.';
    }

    /** Ambil nilai kolom pertama yang ada dari beberapa alias header. */
    protected function pick(array $row, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
                return $row[$key];
            }
        }

        return $default;
    }

    /** Ubah "Rp 1.250.000" / "1250000" / 1250000 menjadi float. */
    protected function money($value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,]/', '', (string) $value);

        return (float) str_replace(',', '.', $clean);
    }
}
