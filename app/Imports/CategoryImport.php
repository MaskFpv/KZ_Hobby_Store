<?php

namespace App\Imports;

use App\Models\Category;

/**
 * Header: nama_kategori* (alias: nama / name), deskripsi.
 * Kategori dengan nama sama -> deskripsi diperbarui.
 */
class CategoryImport extends BaseRowImport
{
    protected function handleRow(array $row): string
    {
        $name = $this->pick($row, ['nama_kategori', 'nama', 'name', 'kategori']);
        if (! $name) {
            throw new \InvalidArgumentException('kolom nama_kategori kosong.');
        }

        $description = $this->pick($row, ['deskripsi', 'description']);
        $category = Category::where('name', $name)->first();

        if ($category) {
            if ($description === null) {
                return 'skipped';
            }
            $category->update(['description' => $description]);

            return 'updated';
        }

        Category::create(['name' => mb_substr($name, 0, 255), 'description' => $description]);

        return 'created';
    }
}
