<?php

namespace App\Imports;

use App\Models\Brand;

/**
 * Header: nama_brand (wajib), negara_asal, deskripsi.
 * Brand dengan nama sama -> diperbarui (negara & deskripsi).
 */
class BrandImport extends BaseRowImport
{
    protected function handleRow(array $row): string
    {
        $name = $this->pick($row, ['nama_brand', 'nama', 'name', 'brand']);

        if (! $name) {
            throw new \InvalidArgumentException('kolom nama_brand kosong.');
        }
        if (mb_strlen($name) > 255) {
            throw new \InvalidArgumentException('nama brand lebih dari 255 karakter.');
        }

        $data = array_filter([
            'country' => $this->pick($row, ['negara_asal', 'negara', 'country']),
            'description' => $this->pick($row, ['deskripsi', 'description']),
        ], fn ($v) => $v !== null);

        $brand = Brand::where('name', $name)->first();

        if ($brand) {
            if (empty($data)) {
                return 'skipped';
            }
            $brand->update($data);

            return 'updated';
        }

        Brand::create(['name' => $name] + $data);

        return 'created';
    }
}
