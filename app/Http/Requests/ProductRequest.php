<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Store & update Produk. Stok hanya diisi saat tambah produk (stok awal);
 * setelah itu stok berubah lewat Kasir, Stok Masuk, atau Stok Opname.
 */
class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('sku')) {
            $this->merge(['sku' => strtoupper(trim($this->input('sku')))]);
        }

        // Checkbox yang tidak dicentang tidak terkirim: jadikan false
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'is_featured' => $this->boolean('is_featured'),
        ]);
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', 'regex:/^[A-Z0-9._\-]+$/',
                Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'stock' => $creating ? ['required', 'integer', 'min:0', 'max:1000000'] : ['prohibited'],
            'condition' => ['required', Rule::in(array_keys(Product::CONDITIONS))],
            'type' => ['required', Rule::in(array_keys(Product::TYPES))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Website pelanggan
            'is_published' => ['boolean'],
            'is_featured' => ['boolean'],
            'po_deadline' => ['nullable', 'date'],
            'release_estimate' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex' => 'SKU hanya boleh huruf, angka, titik, strip, dan underscore (tanpa spasi).',
            'stock.prohibited' => 'Stok tidak bisa diubah dari form edit. Gunakan Stok Masuk / Stok Opname.',
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'kategori',
            'brand_id' => 'brand',
            'name' => 'nama produk',
            'purchase_price' => 'harga modal',
            'selling_price' => 'harga jual',
            'stock' => 'stok awal',
            'condition' => 'kondisi',
            'type' => 'tipe',
            'image' => 'gambar',
            'description' => 'deskripsi',
            'po_deadline' => 'batas PO',
            'release_estimate' => 'estimasi rilis',
        ];
    }
}
